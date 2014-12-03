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
require_once($site_dbConnect);
require_once($site_FEConfig . '/HelperFunctions.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.logger.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_classes . '/class.spec_functions.php');
require_once($site_classes . '/IFSpectrum/IFSpectrum_plot.php');
require_once($site_classes . '/IFSpectrum/IFSpectrum_db.php');

class IFSpectrum_impl extends TestData_header {
    var $plotter;                 //class IFSpectrum_plot
    var $logger;                  //debugging logger
    var $dbConnection;            //mySQL connection
    var $ifSpectrumDb;            //class IFSpectrumDB
    var $ifCalc;                  //IFCalc object
    var $specProvider;		      //class Specifications
    var $specs;                   //array of specs loaded from specProvider
    var $FrontEnd;                //class FrontEnd

    var $GNUPLOT_path;            //Path to Gnuplot from config_main.php
    var $writedirectory;          //Directory for output files
    var $url_directory;           //URL stem for output files
    var $aborted;                 //If true, plotting procedure is aborted.

    var $FEid;                    //Front_Ends.keyId value
    var $dataSetGroup;            //TestData_header.dataSetGroup value for this set of traces
    var $band;                    //band number for the data set to plot
    var $FacilityCode;            //facility code for database access

    var $TDHkeys;                 //array of TestData_header.keyId values for this dataSetGroup
    var $TDHkeyString;            //string containing TestData_header keys for plot labels ("304,308,309,etc")
    var $TDHdataLabels;           //labels shown at the bottom of each plot
    var $TS;                      //timestamp string for this dataSetGroup
    var $plotURLs;                //array of plot URLs for this dataSetGroup

    var $NoiseFloor;              //Noise floor data
    var $NoiseFloorHeader;        //Record from TEST_IFSpectrum_NoiseFloor_Header

    var $fWindow_Low;             //low end of in-band IF
    var $fWindow_High;            //high end of in-band IF
    var $CCASN;                   //SN of the CCA

    var $progressfile;            //ini file to store progress information during plot procedure.
    var $progressfile_fullpath;   //full path of progressfile.ini

    private $imagename;           //file name for image output
    private $imagepath;           //full path to image output file
    private $image_url;           //full URL to image output

    var $swVersion;               //software version string for this class.

    public function __construct(){
        $this->swVersion = "1.2.0";
        // 1.2.0  MTM: refactoring from Aaron's new plotter classes.
        // 1.1.0  ATB: moved database calls to dbCode/ifspectrumdb.php
        // 1.0.24 MTM: fixed inconsistency in the two queries in Display_TotalPowerTable
        // 1.0.23 MTM: fixes so we can run with E_NOTICE enabled
        // 1.0.22 MTM: fix "set...screen" commands to gnuplot
        // 1.0.21 MTM: fix font color for Total and In-band power table.
        //        Fix using/displaying wrong noise floor profile for total and inband.
        require(site_get_config_main());
        $this->plotter = new IFSpectrumPlotter2();
        $this->logger = new Logger('IFSpectrumPlotter.php.txt', 'w');
        $this->GNUPLOT_path = $GNUPLOT;
        $this->writedirectory = $main_write_directory;
        $this->url_directory = $main_url_directory;
        $this->aborted = 0;
    }

    public function Initialize_IFSpectrum($FEid, $dataSetGroup, $fc, $band){
        $this->FEid = $FEid;
        $this->dataSetGroup = $dataSetGroup;
        $this->band = $band;
        $this->FacilityCode = $fc;

        // initialize IF spectrum database class:
        $this->dbConnection = site_getDbConnection();
        $this->ifSpectrumDb = new IFSpectrumDB($this->dbConnection);

        // initialize IF spectrum calculation class.
        $this->ifCalc = new IFCalc();
        $this->ifCalc->setParams($this->band, NULL, $this->FEid, $this->dataSetGroup);

        // create the IFSpectrumPlotter2 object:
        $this->plotter = new IFSpectrumPlotter2();
        $this->plotter->setParams($this->writedirectory, $this->band);

        // load the specifications which apply to this band:
        $this->specProvider = new Specifications();
        $this->specs = $this->specProvider->getSpecs('ifspectrum', $this->band);
        $this->plotter->setSpecs($this->specs);

        // load test data header keys:
        $val = $this->ifSpectrumDb->qTDH($this->band, $this->FEid, $this->dataSetGroup);
        $this->TDHkeys = $val[0];
        $this->TS = $val[1];

        // make test data header keys string:
        $this->TDHkeyString = $this->TDHkeys[0];
        for ($iTDH=1; $iTDH<count($this->TDHkeys); $iTDH++){
            $this->TDHkeyString .= ", " . $this->TDHkeys[$iTDH];
        }

        // load plot URLs:
        $val = $this->ifSpectrumDb->qurl($this->TDHkeys);
        $this->plotURLs = $val[0];
        $numurl = $val[1];

        // load noise floor and noise floor header data applicable to the plots:
        if ($numurl > 0) {
            $val = $this->ifSpectrumDb->qnf($this->TDHkeys);
            $this->NoiseFloor = $val[0];
            $this->NoiseFloorHeader = $val[1];
        }

        // load in-band IF limits:
        $this->fWindow_Low = $this->specs['fWindow_Low'] * pow(10,9);
        $this->fWindow_High = $this->specs['fWindow_high'] * pow(10,9);

        // load FrontEnd info:
        $this->FrontEnd = new FrontEnd();
        $this->FrontEnd->Initialize_FrontEnd($this->FEid, $this->FacilityCode);

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
        if ($this->FrontEnd->ccas[$this->band]->keyId != ''){
            $this->CCASN = $this->FrontEnd->ccas[$this->band]->GetValue('SN');
        }
    }

    public function CreateNewProgressFile() {
        //Create progress update ini file
        require(site_get_config_main());
        $testmessage = "IF Spectrum FE" . $this->FrontEnd->GetValue('SN') . " Band " . $this->band;
        $url = '"' . $rootdir_url . 'FEConfig/ALMA-FETMS-www/FEConfig/ifspectrum/ifspectrumplots.php?fc='
        . $this->FacilityCode . '&fe=' . $this->FEid . '&b=' . $this->band . '&g=' . $this->dataSetGroup . '"';
        $this->progressfile = CreateProgressFile($testmessage, '', $url);
        $this->progressfile_fullpath = $main_write_directory . $this->progressfile . ".txt";
    }

    public function DisplayTDHinfo(){
        //Display information for all TestData_header records
        echo "<br><br>
        <div style='height:900px;width:900px'>
        <table id = 'table1' border = '1'>";
        echo "<tr class = 'alt'><th colspan='3'>IF Spectrum data sets for TestData_header.dataSetGroup $this->dataSetGroup</th></tr>";
        echo "<tr><th>Key</th><th>Timestamp</th><th>Notes</th></tr>";

        for ($i = 0; $i < count($this->TDHkeys); $i++){
            if ($i % 2 == 0){
                $trclass = "alt";
            }
            if ($i % 2 != 0){
                $trclass = "";
            }
            $t = new TestData_header();
            $t->Initialize_TestData_header($this->TDHkeys[$i], $this->FacilityCode, 0);
            echo "<tr class = $trclass>";
            echo "<td>" . $t->keyId . "</td>";
            echo "<td>" . $t->GetValue('TS') . "</td>";
            echo "<td style='text-align:left !important;'>" . $t->GetValue('Notes') . "</td>";
            echo "</tr>";
        }
        echo "</table></div>";
    }

    public function Display_TotalPowerTable($ifChannel){
        $this->plotter->powerTotTables($this->dataSetGroup, $this->FEid, $ifChannel, $this->FrontEnd->feconfig_latest, $this->TS, $this->TDHdataLabels);
    }

    public function DisplayPowerVarFullBandTable(){
        $this->plotter->powerVarTables($this->dataSetGroup, $this->FEid, $this->FrontEnd->feconfig_latest, $this->TS, $this->TDHdataLabels);
    }

    public function GeneratePlots(){
        ini_set('memory_limit', '384M');
        WriteINI($this->progressfile,'progress', 1);
        WriteINI($this->progressfile,'message','Creating temporary tables...');
        $this->ifCalc->deleteTables();
        $this->ifCalc->createTables();

        WriteINI($this->progressfile,'progress', 20);
        WriteINI($this->progressfile,'message','Plotting IF Spectrum...');
        $this->Plot_IFSpectrum_Data(FALSE, 20, 5);

        WriteINI($this->progressfile,'progress', 40);
        WriteINI($this->progressfile,'message','Plotting Expanded IF Spectrum...');
        $this->Plot_IFSpectrum_Data(TRUE, 40, 5);

        WriteINI($this->progressfile,'progress',60);
        WriteINI($this->progressfile,'message','Plotting Power Variation...');
        $this->Plot_PowerVariation_Data(FALSE, 60, 5);

        WriteINI($this->progressfile,'progress',90);
        WriteINI($this->progressfile,'message','Removing temporary tables...');
        $this->ifCalc->deleteTables();

        WriteINI($this->progressfile,'progress',100);
        WriteINI($this->progressfile,'message','Finished plotting IF Spectrum.');
        ini_set('memory_limit', '128M');
    }

    public function makeImageFilenames($typeUrl, $ifChannel = "") {
        $imagedirectory = $this->writedirectory . 'tdh/';
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagedirectory .= $this->TDHkeys[0] . "/";
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagedirectory .= 'IFSpectrum';
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $this->imagepath = $imagedirectory;
        $this->imagename = $typeUrl . "_Band$this->band" . date('Y_m_d_H_i_s') . "dsg" . $this->dataSetGroup . "if" . $ifChannel;
        // partial image url.  Still need to add the full filename generated by the plotter:
        $this->image_url = $this->url_directory . "tdh/" . $this->TDHkeys[0] . "/IFSpectrum/";
    }

    public function Plot_IFSpectrum_Data($expanded, $progressStart, $progressIncrement) {
        // Create plots for spurious noise
        $iflim = $this->specs['maxch'];
        $band = $this->band;
        $fesn = $this->FrontEnd->GetValue('SN');
        $typeURL = ($expanded) ? 'spurious_url2d2' : 'spurious_url2d';
        $ifGain = 15;
        $msgExpanded = ($expanded) ? ' Expanded' : '';

        for ($ifChannel=0; $ifChannel<=$iflim; $ifChannel++) {
            if ($this->CheckForAbort())
                return;

            // update the progress file:
            WriteINI($this->progressfile,'progress', $progressStart + ($ifChannel / $iflim) * $progressIncrement);
            WriteINI($this->progressfile,'message', "Plotting Spurious$msgExpanded IF$ifChannel...");

            // Resets attributes to be used by plotter:
            $this->plotter->resetPlotter();

            // Set the image path and band into the plotter:
            $this->makeImageFilenames($typeURL, $ifChannel);
            $this->plotter->setParams($this->imagepath, $band);

            // Get spurious noise data from database:
            $this->ifCalc->IFChannel = $ifChannel;
            $this->ifCalc->getSpuriousData();

            // Set data into the plotter:
            $this->plotter->setData($this->ifCalc->data);

            // save raw data (for troubleshooting):
            if (!$expanded)
                $this->plotter->save_data("SpuriousBand$band" . "_IF$ifChannel");

            // Set the plot title and generate the plot:
            $plotTitle = "Spurious Noise FE-$fesn, Band $this->band SN $this->CCASN IF$ifChannel";
            $this->plotter->generateSpuriousPlot($expanded, $this->imagename, $plotTitle, $this->TDHdataLabels);

            // Append the actual image filename to the URL before saving:
            $this->image_url .= $this->plotter->getOutputFileName();

            if ($this->plotURLs[$ifChannel]->keyId == ''){
                $this->plotURLs[$ifChannel] = new GenericTable();
                $this->plotURLs[$ifChannel]->NewRecord('TEST_IFSpectrum_plotURLs', 'keyId', 40, 'fkFacility');
                $this->plotURLs[$ifChannel]->SetValue('fkHeader', $this->TDHkeys[0]);
                $this->plotURLs[$ifChannel]->SetValue('Band', $this->band);
                $this->plotURLs[$ifChannel]->SetValue('IFChannel', $ifChannel);
                $this->plotURLs[$ifChannel]->SetValue('IFGain', $ifGain);

            }
            $this->plotURLs[$ifChannel]->SetValue($typeURL, $this->image_url);
            $this->plotURLs[$ifChannel]->Update();
            WriteINI($this->progressfile, 'image', $this->image_url);
        }
    }

    public function Plot_PowerVariation_Data($win31MHz, $progressStart, $progressIncrement){
        $iflim = $this->specs['maxch'];
        $band = $this->band;
        $fesn = $this->FrontEnd->GetValue('SN');
        $ifGain = 15;
        if ($win31MHz) {
            $typeURL = 'powervar_31MHz_url';
            $fwin = 31 * pow(10, 6); // Window size
            $winText = '31 MHz';
        } else {
            $typeURL = 'powervar_2GHz_url';
            $fwin = 2 * pow(10, 9); // Window size
            $winText = '2 GHz';
        }
        for ($ifChannel=0; $ifChannel<=$iflim; $ifChannel++) {
            if ($this->CheckForAbort())
                return;

            WriteINI($this->progressfile,'progress', $progressStart + ($ifChannel / $iflim) * $progressIncrement);
            WriteINI($this->progressfile,'message', 'Plotting Power Variation $winText window IF$ifChannel...');

            // Resets attributes to be used by plotter:
            $this->plotter->resetPlotter();

            // Set the image path and band into the plotter:
            $this->makeImageFilenames($typeURL, $ifChannel);
            $this->plotter->setParams($this->imagepath, $band);

            // Get spurious noise data from database:
            $this->ifCalc->IFChannel = $ifChannel;
            $this->ifCalc->getPowerData($fwin); // Gets power variation from database for 2 GHz window

            // Set data into the plotter:
            $this->plotter->setData($this->ifCalc->data);
            $this->plotter->save_data("PowerVarBand$band" . "_$winText" . "_IF$ifChannel");

            // Set the plot title and generate the plot:
            $plotTitle = "Power Variation $winText Window FE-$fesn, Band $this->band SN $this->CCASN IF$ifChannel";
            $this->plotter->generatePowerVarPlot($win31MHz, $this->imagename, $plotTitle, $this->TDHdataLabels);

            // Append the actual image filename to the URL before saving:
            $this->image_url .= $this->plotter->getOutputFileName();

            if ($this->plotURLs[$ifChannel]->keyId == ''){
                $this->plotURLs[$ifChannel] = new GenericTable();
                $this->plotURLs[$ifChannel]->NewRecord('TEST_IFSpectrum_plotURLs','keyId',40,'fkFacility');
                $this->plotURLs[$ifChannel]->SetValue('fkHeader',$this->TDHkeys[0]);
                $this->plotURLs[$ifChannel]->SetValue('Band',$this->band);
                $this->plotURLs[$ifChannel]->SetValue('IFChannel',$ifChannel);
                $this->plotURLs[$ifChannel]->SetValue('IFGain',$ifGain);

            }
            $this->plotURLs[$ifChannel]->SetValue($typeURL, $this->image_url);
            $this->plotURLs[$ifChannel]->Update();
            WriteINI($this->progressfile, 'image', $this->image_url);
        }
    }

    public function CheckForAbort(){
        $ini_array = parse_ini_file($this->progressfile_fullpath);
        $this->aborted = $ini_array['abort'];
        if ($this->aborted == 1){
            WriteINI($this->progressfile,'message',"Aborted.");
            return true;
        }
        return false;
    }

} // end class
?>
