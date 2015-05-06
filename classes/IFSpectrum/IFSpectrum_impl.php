<?php
/**
 * ALMA - Atacama Large Millimeter Array
 * (c) Associated Universities Inc., 2014
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307  USA
 *
 */

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_FEConfig . '/HelperFunctions.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_classes . '/class.spec_functions.php');
require_once($site_classes . '/IFSpectrum/IFSpectrum_calc.php');
require_once($site_classes . '/IFSpectrum/IFSpectrum_db.php');
require_once($site_classes . '/IFSpectrum/IFSpectrum_plot.php');

class IFSpectrum_impl extends TestData_header {
    var $plotter;                 //class IFSpectrum_plot
    var $ifCalc;                  //class IFSpectrum_calc
    var $ifSpectrumDb;            //class IFSpectrum_db
    var $specProvider;		      //class Specifications

    var $specs;                   //array of specs loaded from specProvider
    var $FrontEnd;                //class FrontEnd

    var $GNUPLOT_path;            //Path to Gnuplot from config_main.php
    var $writedirectory;          //Directory for output files
    var $url_directory;           //URL stem for output files
    var $aborted;                 //If true, plotting procedure is aborted.
    var $debugRawDataFiles;       //If true, write CSV data files to disk for debugging.

    var $FEid;                    //Front_Ends.keyId value
    var $dataSetGroup;            //TestData_header.dataSetGroup value for this set of traces
    var $band;                    //band number for the data set to plot
    var $facilityCode;            //facility code for database access

    var $TDHkeys;                 //array of TestData_header.keyId values for this dataSetGroup
    var $TDHkeyString;            //string containing TestData_header keys for plot labels ("304, 308, 309, etc.")
    var $TDHdataLabels;           //labels shown at the bottom of each plot
    var $TS;                      //timestamp string for this dataSetGroup
    var $plotURLs;                //array of plot URLs for this dataSetGroup

    var $keyNoiseFloor;           //keyId for the noise floor header
    var $NoiseFloorHeader;        //Record from TEST_IFSpectrum_NoiseFloor_Header

    var $fWindow_Low;             //low end of in-band IF
    var $fWindow_High;            //high end of in-band IF
    var $CCASN;                   //SN of the CCA

    var $progressfile;            //ini file to store progress information during plot procedure.
    var $progressfile_fullpath;   //full path of progressfile.ini

    private $imagedirectory;      //directory for files and image output
    private $imagename;           //file name for image output
    private $image_url;           //full URL to image output

    var $swVersion;               //software version string for this class.

    public function __construct() {
        $this->swVersion = "1.3.1";
        // 1.3.1  MTM: refactoring done.  B5 special powervar plot temporarily disabled.
        // 1.3.0  MTM: still refactoring with new IFSpectrum_calc, _db, and _plot classes.
        // 1.2.0  MTM: refactoring from Aaron's new plotter classes.
        // 1.1.0  ATB: moved database calls to dbCode/ifspectrumdb.php
        // 1.0.24 MTM: fixed inconsistency in the two queries in Display_TotalPowerTable
        // 1.0.23 MTM: fixes so we can run with E_NOTICE enabled
        // 1.0.22 MTM: fix "set...screen" commands to gnuplot
        // 1.0.21 MTM: fix font color for Total and In-band power table.
        //        Fix using/displaying wrong noise floor profile for total and inband.
        require(site_get_config_main());
        $this->plotter = new IFSpectrum_plot();
        $this->GNUPLOT_path = $GNUPLOT;
        $this->writedirectory = $main_write_directory;
        $this->url_directory = $main_url_directory;
        $this->aborted = FALSE;
        $this->debugRawDataFiles = FALSE;
    }

    public function Initialize_IFSpectrum($FEid, $band, $dataSetGroup, $fc) {
        $this->FEid = $FEid;
        $this->band = $band;
        $this->dataSetGroup = $dataSetGroup;
        $this->facilityCode = $fc;

        // initialize IF spectrum database object:
        $this->ifSpectrumDb = new IFSpectrum_db();

        // initialize IF spectrum calculation object:
        $this->ifCalc = new IFSpectrum_calc();

        // create the IF spectrum plotter object:
        $this->plotter = new IFSpectrum_plot();
        $this->plotter -> setParams($this->writedirectory, $this->band);

        // load the specifications which apply to this band:
        $this->specProvider = new Specifications();
        $this->specs = $this->specProvider->getSpecs('ifspectrum', $this->band);
        $this->plotter->setSpecs($this->specs);

        // load test data header keys:
        $this->TDHkeys = $this->ifSpectrumDb -> getTestDataHeaderKeys($this->FEid, $this->band, $this->dataSetGroup);
        $this->TS = $this->ifSpectrumDb -> getLastTS();

        // make test data header keys string:
        $this->TDHkeyString = "";
        foreach ($this->TDHkeys as $key) {
            if ($this->TDHkeyString)
                $this->TDHkeyString .= ", ";
            $this->TDHkeyString .= $key;
        }

        // load plot URLs:
        $val = $this->ifSpectrumDb -> getPlotURLs($this->TDHkeys);
        $numurl = $val[0];
        $this->plotURLs = $val[1];

        // load noise floor and noise floor header data applicable to the plots:
        if ($numurl > 0) {
            $val = $this->ifSpectrumDb -> getNoiseFloorHeaders($this -> TDHkeys[0]);
            $this->keyNoiseFloor = $val[0];
            $this->NoiseFloorHeader = $val[1];
        }

        // load in-band IF limits:
        $this->fWindow_Low = $this->specs['fWindow_Low'] * pow(10,9);
        $this->fWindow_High = $this->specs['fWindow_high'] * pow(10,9);

        // load FrontEnd info:
        $this->FrontEnd = new FrontEnd();
        $this->FrontEnd->Initialize_FrontEnd($this->FEid, $this->facilityCode);

        // make the data labels which go at the bottom of every plot:
        $this->TDHdataLabels = array();
        $this->TDHdataLabels[] = "TestData_header.keyId: ". $this->TDHkeyString;
        $this->TDHdataLabels[] = "$this->TS, FE Configuration "
        . $this->FrontEnd->feconfig_latest
        . "; DataSetGroup: "
        . $this->dataSetGroup
        . "; IFSpectrum Ver. "
        . $this->swVersion;

        // load the CCA serial number:
        $this->CCASN = 0;
        if ($this->FrontEnd -> ccas[$this->band] -> keyId != '') {
            $this->CCASN = $this->FrontEnd -> ccas[$this->band] -> GetValue('SN');
        }
    }

    public function CreateNewProgressFile() {
        //Create progress update ini file
        require(site_get_config_main());
        $testmessage = "IF Spectrum FE-" . $this->FrontEnd->GetValue('SN') . " Band " . $this->band;
        $url = '"' . $rootdir_url . 'FEConfig/ifspectrum/ifspectrumplots.php?fc='
            . $this->facilityCode . '&fe=' . $this->FEid . '&b=' . $this->band . '&g=' . $this->dataSetGroup . '"';
        $this->progressfile = CreateProgressFile($testmessage, '', $url);
        $this->progressfile_fullpath = $main_write_directory . $this->progressfile . ".txt";
    }

    public function DeleteProgressFile() {
        unlink($this->progressfile_fullpath);
    }

    private function ReportProgress($percent, $msg) {
        WriteINI($this->progressfile, 'progress', round($percent, 1));
        WriteINI($this->progressfile, 'message', $msg);
    }

    private function UpdateProgressImageUrl() {
        WriteINI($this->progressfile, 'image', $this->image_url);
    }

    public function ProgressCheckForAbort() {
        $ini_array = parse_ini_file($this->progressfile_fullpath);
        $this->aborted = $ini_array['abort'];
        if ($this->aborted) {
            WriteINI($this->progressfile,'message',"Stopped.");
            return true;
        }
        return false;
    }

    public function DisplayTDHinfo() {
        //Display information for all TestData_header records
        echo "<br><br><div style='height:900px;width:900px'>";
        echo "<table id = 'table1' border = '1'>";
        echo "<tr class = 'alt'><th colspan='3'>IF Spectrum data sets for TestData_header.dataSetGroup $this->dataSetGroup</th></tr>";
        echo "<tr><th>Key</th><th>Timestamp</th><th>Notes</th></tr>";

        for ($i = 0; $i < count($this->TDHkeys); $i++) {
            if ($i % 2 == 0) {
                $trclass = "alt";
            }
            if ($i % 2 != 0) {
                $trclass = "";
            }
            $t = new TestData_header();
            $t->Initialize_TestData_header($this->TDHkeys[$i], $this->facilityCode, 0);
            echo "<tr class = $trclass>";
            echo "<td>" . $t->keyId . "</td>";
            echo "<td>" . $t->GetValue('TS') . "</td>";
            echo "<td style='text-align:left !important;'>" . $t->GetValue('Notes') . "</td>";
            echo "</tr>";
        }
        echo "</table></div>";
    }

    public function Display_TotalPowerTable($ifChannel) {
        $data = $this->ifSpectrumDb -> getTotalAndInBandPower($this->FEid, $this->band, $this->dataSetGroup, $ifChannel);
        if ($data) {
            // TODO: add back in borders/shading.
            echo "<div style = 'width:600px'>";
            echo "<table id = 'table7' border = '1'>";
            echo "<tr><th colspan = '5'>Band $this->band Total and In-Band Power</th></tr>";
            echo "<tr><th colspan = '5'><b>IF Channel $ifChannel</b></th></tr>";
            echo "<tr><td colspan = '2' align = 'center'><i>0 dB Gain</i></td><td colspan = '3' align = 'center'><i>15 dB Gain</i></td></tr>";
            echo "<tr><td>LO (GHz)</td><td>In-Band (dBm)</td><td>In-Band (dBm)</td><td>Total (dBm)</td><td>Total - In-Band</td></tr>";
            foreach ($data as $row) {
                $LO = $row['FreqLO'];
                $pwr0 = round($row['pwr0'], 1);        // in-band power 0 dB gain
                $pwr15 = round($row['pwr15'], 1);      // in-band power 15 dB gain
                $pwrT = round($row['pwrT'], 1);        // total power 15 dB gain
                $pwrDiff = round($row['pwrDiff'], 1);  // total - in-band 15 dB gain
                $gainDiff = $pwr15 - $pwr0;            // in-band gain difference measured

                // if the difference in gain is not 15 +/- 1 dB, color in red:
                $red = ($gainDiff < 14 || $gainDiff > 16);

                // LO column:
                echo "<tr><td>$LO</td>";

                // in-band 0 dB gain column:
                echo "<td>";
                if ($red)
                    echo "<span>";
                if ($pwr0 > -22)
                    echo "<font color='#FF0000'>";
                else
                    echo "<font color='#000000'>";
                echo "$pwr0</font>";
                if ($red)
                    echo "</span>";
                echo "</td>";

                // in-band 15 dB gain column:
                echo "<td>";
                if ($red)
                    echo "<span>";
                if ($pwr15 < -22)
                    echo "<font color='#FF0000'>";
                else
                    echo "<font color='#000000'>";
                echo "$pwr15</font>";
                if ($red)
                    echo "</span>";
                echo "</td>";

                // total 15 dB gain column:
                echo "<td><b>$pwrT</b></td>";

                // total - in-band column
                echo "<td>";
                if ($pwrDiff > 3)
                    echo "<font color = '#ff0000'>";
                else
                    echo "<font color = '#000000'>";
                echo "<b>$pwrDiff</b></font>";
                echo "</td></tr>";

            }
            foreach ($this->TDHdataLabels as $label)
                echo "<tr class = 'alt3'><th colspan = '5'>$label</th></tr>";
            echo "</table></div>";
        }
    }

    public function DisplayPowerVarFullBandTable() {
        $data = $this->ifSpectrumDb -> getPowerVarFullBand($this->FEid, $this->band, $this->dataSetGroup);
        if ($data) {
            // TODO: add back in borders/shading.
            echo "<div style='width:400px' border='1'>";
            echo "<table id = 'table7' border='1'>";
            echo "<tr class='alt'><th colspan = '5'>Band $this->band Power Variation Full Band</th></tr>";
            echo "<tr class='alt3'><td style='border-right:solid 1px #000000;'><b>LO (GHz)</td>";
            echo "<td><b>IF0</b></td>";
            echo "<td><b>IF1</b></td>";
            if ($this->band < 9) {
                echo "<td><b>IF2</b></td>";
                echo "<td><b>IF3</b></td>";
            }
            echo "</tr>";

            $maxVar = $this->specs['pwr'];
            $okColor = $this->specs['fontcolor'];
            $badColor = $this->specs["fontcolor$maxVar"];

            foreach ($data as $row) {
                $LO = $row['FreqLO'];
                $pVar_IF0 = round($row['pVar_IF0'], 1);
                $pVar_IF1 = round($row['pVar_IF1'], 1);
                $pVar_IF2 = round($row['pVar_IF2'], 1);
                $pVar_IF3 = round($row['pVar_IF3'], 1);
                //TODO:   use number_format($pwr, 1, '.', ''); ?

                // LO column:
                echo "<tr><td>$LO</td>";

                // IF0 column:
                $fontcolor = ($pVar_IF0 > $maxVar) ? $badColor : $okColor;
                echo "<td><font color = $fontcolor><b>$pVar_IF0</b></font></td>";

                // IF1 column:
                $fontcolor = ($pVar_IF1 > $maxVar) ? $badColor : $okColor;
                echo "<td><font color = $fontcolor><b>$pVar_IF0</b></font></td>";

                if ($this->band < 9) {
                    // IF2 column:
                    $fontcolor = ($pVar_IF2 > $maxVar) ? $badColor : $okColor;
                    echo "<td><font color = $fontcolor><b>$pVar_IF2</b></font></td>";

                    // IF3 column:
                    $fontcolor = ($pVar_IF3 > $maxVar) ? $badColor : $okColor;
                    echo "<td><font color = $fontcolor><b>$pVar_IF3</b></font></td>";
                } else {
                    echo "<td></td><td></td>";
                }
                echo "</tr>";
            }
            foreach ($this->TDHdataLabels as $label)
                echo "<tr class = 'alt3'><th colspan = '5'>$label</th></tr>";
            echo "</table></div>";
        }
    }

    public function GeneratePlots() {
        $this->ReportProgress(1, 'Creating temporary table...');
        $this->ifSpectrumDb->createTemporaryTable($this->FEid, $this->band, $this->dataSetGroup);
        $this->makeOutputDirectory(true);

        $this->ReportProgress(10, 'Plotting IF Spectrum...');
        $this->Plot_IFSpectrum_Data(FALSE, 10, 5);

        $this->ReportProgress(30, 'Plotting Expanded IF Spectrum...');
        $this->Plot_IFSpectrum_Data(TRUE, 30, 5);

        $this->ReportProgress(50, 'Plotting Power Variation...');
        $this->Plot_PowerVariation_Data(FALSE, 50, 2.5);
        $this->Plot_PowerVariation_Data(TRUE, 60, 2.5);

        $this->ReportProgress(70, 'Full-band Power Variation...');
        $this->Calc_PowerVar_FullBand(70, 2.5);

        $this->ReportProgress(85, 'Total and In-Band Power...');
        $this->Calc_TotalInbandPower(80, 2.5);

        $this->ReportProgress(95, 'Removing temporary table...');
        $this->ifSpectrumDb->deleteTemporaryTable();

        $this->ReportProgress(100, 'Finished plotting IF Spectrum.');
    }

    private static function deleteDir($dirPath) {
        if (!is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    private function makeOutputDirectory($deleteContents = false) {
        $this->imagedirectory = $this->writedirectory . 'tdh/';

        if (!file_exists($this->imagedirectory))
            mkdir($this->imagedirectory);

        $this->imagedirectory .= $this->TDHkeys[0] . "/";
        if (!file_exists($this->imagedirectory))
            mkdir($this->imagedirectory);

        $this->imagedirectory .= 'IFSpectrum';
        if ($deleteContents && file_exists($this->imagedirectory))
            self::deleteDir($this->imagedirectory);

        if (!file_exists($this->imagedirectory))
            mkdir($this->imagedirectory);
    }

    private function makeImageFilenames($typeUrl, $ifChannel = "") {
        $this->imagename = $typeUrl . "_Band$this->band" . date('Y_m_d_H_i_s') . "dsg" . $this->dataSetGroup . "if" . $ifChannel;
        // partial image url.  Still need to add the full filename generated by the plotter:
        $this->image_url = $this->url_directory . "tdh/" . $this->TDHkeys[0] . "/IFSpectrum/";
    }

    public function Plot_IFSpectrum_Data($expanded, $progressStart, $progressIncrement) {
        // Create plots for spurious noise
        $iflim = $this->specs['maxch'];
        $fesn = $this->FrontEnd -> GetValue('SN');
        $typeURL = ($expanded) ? 'spurious_url2d2' : 'spurious_url2d';
        $ifGain = 15;
        $msgExpanded = ($expanded) ? ' Expanded' : '';
        $progress = $progressStart;

        for ($ifChannel=0; $ifChannel<=$iflim; $ifChannel++) {
            if ($this->ProgressCheckForAbort())
                return;

            // update the progress file:
            $this->ReportProgress($progress, "Plotting Spurious$msgExpanded IF$ifChannel...");
            $progress += $progressIncrement;

            // Reset attributes to be used by plotter:
            $this->plotter -> resetPlotter();

            // Set the image path and band into the plotter:
            $this->makeImageFilenames($typeURL, $ifChannel);
            $this->plotter -> setParams($this->imagedirectory, $this->band);

            // Get spurious noise data from database:
            $data = $this->ifSpectrumDb -> getSpectrumData($ifChannel);

            // Set data into the plotter:
            $this->plotter -> setData($data);

            // Apply offset and find min/max power levels:
            $this->plotter -> prepareSpectrumTraces($expanded);

            // save raw data (for troubleshooting):
            if ($this->debugRawDataFiles)
                $this->plotter -> save_data("SpuriousBand" . $this->band . "_IF$ifChannel");

            // Set the plot title and generate the plot:
            $plotTitle = "Spurious Noise FE-$fesn, CCA $this->band-$this->CCASN, IF$ifChannel";
            $this->plotter -> generateSpuriousPlot($expanded, $this->imagename, $plotTitle, $this->TDHdataLabels);

            // Free memory:
            unset($data);

            // Append the actual image filename to the URL before saving:
            $this->image_url .= $this->plotter -> getOutputFileName();

            if ($this->plotURLs[$ifChannel] -> keyId == '') {
                $this->plotURLs[$ifChannel] = new GenericTable();
                $this->plotURLs[$ifChannel] -> NewRecord('TEST_IFSpectrum_plotURLs', 'keyId', 40, 'fkFacility');
                $this->plotURLs[$ifChannel] -> SetValue('fkHeader', $this->TDHkeys[0]);
                $this->plotURLs[$ifChannel] -> SetValue('Band', $this->band);
                $this->plotURLs[$ifChannel] -> SetValue('IFChannel', $ifChannel);
                $this->plotURLs[$ifChannel] -> SetValue('IFGain', $ifGain);

            }
            $this->plotURLs[$ifChannel] -> SetValue($typeURL, $this->image_url);
            $this->plotURLs[$ifChannel] -> Update();

            // Update the progress display image:
            $this->UpdateProgressImageUrl();
        }
    }

    public function Plot_PowerVariation_Data($win31MHz, $progressStart, $progressIncrement) {
        $iflim = $this->specs['maxch'];
        $fesn = $this->FrontEnd -> GetValue('SN');
        $ifGain = 15;
        $pvarData = $pvarData_special = false;
        if ($win31MHz) {
            $typeURL = 'powervar_31MHz_url';
            $fWindow = 0.031;   // Window size GHz.
            $winText = '31 MHz';
            $spec = $this->specs['spec_value_var31MHz'];    //1.35;
        } else {
            $typeURL = 'powervar_2GHz_url';
            $fWindow = 2.0;     // Window size GHz.
            $winText = '2 GHz';
            $spec = $this->specs['spec_value'];
        }
        $progress = $progressStart;

        for ($ifChannel=0; $ifChannel<=$iflim; $ifChannel++) {
            if ($this->ProgressCheckForAbort())
                return;

            $this->ReportProgress($progress, "Plotting Power Variation $winText window IF$ifChannel...");
            $progress += $progressIncrement;

            // Reset attributes to be used by plotter:
            $this->plotter -> resetPlotter();

            // Set the image path and band into the plotter:
            $this->makeImageFilenames($typeURL, $ifChannel);
            $this->plotter -> setParams($this->imagedirectory, $this->band);

            // Get spurious noise data from database:
            $data = $this->ifSpectrumDb -> getSpectrumData($ifChannel);

            // Calculate power variation:
            $this->ifCalc -> setData($data);

            $fWindowLow = $this->specs['fWindow_Low'];
            $fWindowHigh = $this->specs['fWindow_high'];
            // Temporary:  use the 'special low' window for band 6.
            if ($this->band == 6)
                $fWindowLow = $this->specs['fWindow_special_Low'];
            // TODO:  Get special band 6 plots working again.

            $pvarData = $this->ifCalc -> getPowerVarWindow($fWindowLow, $fWindowHigh, $fWindow, $spec);

            // Cache max power variation:
            $maxVar = $this->ifCalc -> getMaxVarWindow();

            // Calculate LOs with out-of spec values:
            $badLOs = $this->ifCalc -> getBadVarLOs();

            // Free memory:
            $this->ifCalc -> setData(false);
            unset($data);

            // Set data into the plotter:
            $this->plotter -> setData($pvarData);
            if ($pvarData_special) {
                $this->plotter -> setData_special($pvarData_special);
            }

            // save raw data (for troubleshooting):
            if ($this->debugRawDataFiles)
                $this->plotter -> save_data("PowerVarBand" . $this->band . "_$winText" . "_IF$ifChannel");

            // Set the plot title:
            $plotTitle = "Power Variation $winText Window FE-$fesn, CCA $this->band-$this->CCASN, IF$ifChannel";

            // Append a "Max Power Variation" line to the labels:
            $labels = $this->TDHdataLabels;
            $lastLabel = "Max Power Variation: " . round($maxVar, 2) . " dB";
            if (!empty($badLOs))
                $lastLabel .= "    * indicates out-of-spec trace";
            $labels[] = $lastLabel;

            // Generate the plot
            $this->plotter->generatePowerVarPlot($win31MHz, $this->imagename, $plotTitle, $spec, $badLOs, $labels);

            // Append the actual image filename to the URL before saving:
            $this->image_url .= $this->plotter->getOutputFileName();

            if ($this->plotURLs[$ifChannel] -> keyId == '') {
                $this->plotURLs[$ifChannel] = new GenericTable();
                $this->plotURLs[$ifChannel] -> NewRecord('TEST_IFSpectrum_plotURLs','keyId',40,'fkFacility');
                $this->plotURLs[$ifChannel] -> SetValue('fkHeader',$this->TDHkeys[0]);
                $this->plotURLs[$ifChannel] -> SetValue('Band',$this->band);
                $this->plotURLs[$ifChannel] -> SetValue('IFChannel',$ifChannel);
                $this->plotURLs[$ifChannel] -> SetValue('IFGain',$ifGain);

            }
            $this->plotURLs[$ifChannel] -> SetValue($typeURL, $this->image_url);
            $this->plotURLs[$ifChannel] -> Update();

            // Update the progress display image:
            $this->UpdateProgressImageUrl();
        }
    }

    public function Calc_PowerVar_FullBand($progressStart, $progressIncrement) {
        $iflim = $this->specs['maxch'];
        $fesn = $this->FrontEnd -> GetValue('SN');
        $ifGain = 15;
        $progress = $progressStart;

        for ($ifChannel=0; $ifChannel<=$iflim; $ifChannel++) {
            if ($this->ProgressCheckForAbort())
                return;

            $this->ReportProgress($progress, "Full-band Power Variation IF$ifChannel...");
            $progress += $progressIncrement;

            // Get spurious noise data from database:
            $data = $this->ifSpectrumDb -> getSpectrumData($ifChannel, $ifGain);

            // Calculate power variation:
            $this->ifCalc -> setData($data);
            $pvarData = $this->ifCalc -> getPowerVarFullBand($this->specs['fWindow_Low'], $this->specs['fWindow_high']);

            // Store back to database:
            $this->ifSpectrumDb -> storePowerVarFullBand($ifChannel, $pvarData);
        }
    }

    public function Calc_TotalInbandPower($progressStart, $progressIncrement) {
        $iflim = $this->specs['maxch'];
        $fesn = $this->FrontEnd -> GetValue('SN');
        $progress = $progressStart;

        for ($ifChannel=0; $ifChannel<=$iflim; $ifChannel++) {
            if ($this->ProgressCheckForAbort())
                return;

            $this->ReportProgress($progress, "Total and In-Band Power IF$ifChannel...");
            $progress += $progressIncrement;

            // Get spurious noise data from database for 0 dB gain:
            $ifGain = 0;
            $data = $this->ifSpectrumDb -> getSpectrumData($ifChannel, $ifGain);

            // Calculate total and in-band power:
            $this->ifCalc -> setData($data);
            $pwrData = $this->ifCalc -> getTotalAndInBandPower($this->specs['fWindow_Low'], $this->specs['fWindow_high']);

            // Store back to database:
            $this->ifSpectrumDb -> storeTotalAndInBandPower($ifChannel, $ifGain, $pwrData);

            // Get spurious noise data from database for 15 dB gain:
            $ifGain = 15;
            $data = $this->ifSpectrumDb -> getSpectrumData($ifChannel, $ifGain);

            // Calculate total and in-band power:
            $this->ifCalc -> setData($data);
            $pwrData = $this->ifCalc -> getTotalAndInBandPower($this->specs['fWindow_Low'], $this->specs['fWindow_high']);

            // Store back to database:
            $this->ifSpectrumDb -> storeTotalAndInBandPower($ifChannel, $ifGain, $pwrData);
        }
    }

} // end class
?>
