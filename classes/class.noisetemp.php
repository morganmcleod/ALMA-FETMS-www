<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.logger.php');
require_once($site_classes . '/class.spec_functions.php');
require_once($site_FEConfig. '/testdata/pas_tables.php');

class NoiseTemperature extends TestData_header{
    private $NT_SubHeader;          // array for subheader objects from Noise_Temp_SubHeader (class.generictable.php)
    private $NT_Logger;             // debug logger object
    private $CCA_SN;                // cold cartridge serial number
    private $WCA_SN;                // WCA serial number
    private $CCA_componentKeys;     // array of component keys referring to the CCA
    private $foundIRData;           // true if we have image rejection data for the CCA
    private $foundCCAData;          // true if we have cartridge noise temp data
    private $plotDir;               // Location for output plots

    // Image rejection data:
    private $IR_USB_Pol0_Sb1;       // these arrays contain the RF corresponding to the IR
    private $IR_LSB_Pol0_Sb2;
    private $IR_USB_Pol1_Sb1;
    private $IR_LSB_Pol1_Sb2;
    private $IR_Pol0_Sb1;           // these arrays contain the image rejection
    private $IR_Pol0_Sb2;
    private $IR_Pol1_Sb1;
    private $IR_Pol1_Sb2;

    // Noise temperature data:
    private $NT_data;               // 2-D array of noise temperature data being processed and plotted
    private $NT_avgData;            // 2-D array of NT data averaged across the IF band

    private $FEIC_USB;              // array of USB RFs
    private $FEIC_LSB;              // array of LSB RFs
    private $FEIC_Pol0Sb1;          // Pol0 noise temp corresponding to position in FEIC_USB
    private $FEIC_Pol0Sb2;          // Pol0 noise temp corresponding to position in FEIC_LSB
    private $FEIC_Pol1Sb1;          // Pol1 "
    private $FEIC_Pol1Sb2;          // Pol1 "

    // Specifications for display on plots:
    private $effColdLoadTemp;       // effective cold load temperature
    private $default_IR;            // default image rejection to use if no CCA data available.
    private $lowerIFLimit;          // lower IF limit
    private $upperIFLimit;          // upper IF limit
    private $NT_allRF_spec;         // spec which must me met at all points in the RF band
    private $NT_80_spec;            // spec which must be met over 80% of the RF band
    private $NT_B3Special_spec;     // special spec for band 3 average of averages
    private $lower_80_RFLimit;      // lower RF limit for 80% spec
    private $upper_80_RFLimit;      // upper RF limit for 80% spec

    // Data files:
    private $spec_datafile;         // for spec lines
    private $avSpec_datafile;       // for spec lines on averaged plot
    private $avg_datafile;          // for averaged noise temps
    private $rf_datafile;           // for NT vs RF
    private $if_datafile;           // for NT vs IF

    private $datafile_cart_0_1;     // for cartridge NT data
    private $datafile_cart_0_2;
    private $datafile_cart_1_1;
    private $datafile_cart_1_2;
    private $datafile_diff_0_1;     // for diffs between FEIC and cartridge NT data
    private $datafile_diff_0_2;
    private $datafile_diff_1_1;
    private $datafile_diff_1_2;

    // Plot contents and formatting:
    private $Plot_SWVer;            // software version
    private $plot_label_1;          // labels which go at the bottom of each plot
    private $plot_label_2;
    private $y_lim;                 // y-axis upper limit for plots

    public function Initialize_NoiseTemperature($in_keyId, $in_fc) {
        parent::Initialize_TestData_header($in_keyId, $in_fc);

        $q = "SELECT keyId, keyFacility FROM Noise_Temp_SubHeader
              WHERE fkHeader = $in_keyId AND keyFacility = $in_fc
              order by keyId ASC;" ;
        $r = @mysql_query($q, $this->dbconnection);
        $keyID = @mysql_result($r, 0, 0);
        $facility = @mysql_result($r, 0, 1);
        $this->NT_SubHeader = new GenericTable();
        $this->NT_SubHeader->Initialize('Noise_Temp_SubHeader', $keyID, 'keyId', $facility, 'keyFacility');
    }

    public function DisplayPlots() {
        $hasSB2 = (($this->GetValue('Band') != 1) && ($this->GetValue('Band') != 9) && ($this->GetValue('Band') != 10));

        $url = $this->NT_SubHeader->GetValue('ploturl1');
        if ($url)
            echo "<img src= '$url'><br><br>";

        if ($hasSB2) {
            $url = $this->NT_SubHeader->GetValue('ploturl2');
            if ($url)
                echo "<img src= '$url'><br><br>";
        }

        $url = $this->NT_SubHeader->GetValue('ploturl5');
        if ($url)
            echo "<img src= '$url'><br><br>";

        if ($hasSB2) {
            $url = $this->NT_SubHeader->GetValue('ploturl6');
            if ($url)
                echo "<img src= '$url'><br><br>";
        }

        $url = $this->NT_SubHeader->GetValue('ploturl3');
        if ($url)
            echo "<img src= '$url'><br><br>";

        $url = $this->NT_SubHeader->GetValue('ploturl4');
        if ($url)
            echo "<img src= '$url'><br><br>";

        if ($this->GetValue('Band') == 3) {
            Band3_NT_results($this->GetValue('keyId'));
            Band3_CCA_NT_results($this->GetValue('keyId'));
        }
    }

    public function DrawPlot() {
        // start a logger file for debugging
        $this->NT_Logger = new Logger("NT_Log.txt");

        // set Plot Software Version
        $this->Plot_SWVer = "1.2.5";
        /*
         * 1.2.5 Avoid div by 0 in function Trx_Uncorr
         * 1.2.4 Display averaged graphs for band 3 in addition to tables.
         * 1.2.3 Fixed bugs in band 9 & 10 plots.
         * 1.2.2 Added band 5 production changes to noise temp plots.
         * 1.2.1 Uses only MAX(keyDataSet) when loading CCA NT and IR data.
         * 1.2.0 Now pulls specifications from new class that pulls from files instead of database.
         * 1.1.4  Modified caption for band 10 averaging plot 80% spec.
         * 1.1.3  Fixed band 10 averaging calculation bug.
         * 1.1.2  Fixed bugs introduced by refactoring (not loading IR data.)
         * 1.1.1  Got band 10 special averaging plot metrics working
         * 1.1.0  Refactored into top-level function and helpers.
         * 1.0.18 cleaned up NT calc and averaging loop.  Added check for band 10 80% spec.
         * 1.0.17  MTM: fix "set label...screen" commands to gnuplot
         * 1.0.16  MTM: fix plot axis labels for Tssb and "corrected"
         */
        $this->SetValue('Plot_SWVer', $this->Plot_SWVer);
        $this->Update();

        $this->NT_Logger->WriteLogFile("class NoiseTemperature version $this->Plot_SWVer");

        // get the main data files write directory from config_main:
        require(site_get_config_main());

        $this->plotDir = $main_write_directory . "noisetemp/";
        //Create plot directory if it doesn't exist.
        if (!file_exists($this->plotDir)) {
            mkdir($this->plotDir);
        }

        // load the CCA and WCA serial numbers and configuration keys:
        $this->LoadCartridgeKeys();

        // load the image rejection data to use for correcting noise temps:
        $this->LoadImageRejectionData();

        $this->LoadSpecs();

        $this->WriteSpecsDataFile();

        $this->LoadNoiseTempData();

        if (!count($this->NT_data)) {
            $this->NT_Logger->WriteLogFile("No Data");
            return;
        }

        $this->CalculateNoiseTemps();

        $this->CalculateAvgNoiseTemps();

        if ($this->GetValue('Band') == 3)
            $this->CalculateBand3AvgNT();

        if ($this->GetValue('Band') == 10)
            $this->CalculateBand10AvgNT();

        $this->LoadAndWriteCCANoiseTempData();

        $this->MakePlotFooterLabels();

        $this->DrawPlotTrVsIF();

        $this->DrawPlotTrAverage();

        $this->DrawPlotTrVsRF();

        $this->NT_SubHeader->Update();  // save image locations to database
    }

    private function LoadCartridgeKeys() {
        //Get CCA Serial Number
        $q ="SELECT FE_Components.SN FROM FE_Components, FE_ConfigLink, FE_Config
        WHERE FE_ConfigLink.fkFE_Config = " . $this->GetValue('fkFE_Config'). "
        AND FE_Components.fkFE_ComponentType = 20
        AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
        AND FE_Components.Band = " . $this->GetValue('Band') . "
        AND FE_Components.keyFacility =" . $this->GetValue('keyFacility') ."
        AND FE_ConfigLink.fkFE_ConfigFacility = FE_Config.keyFacility
        ORDER BY Band ASC;";
        $r = @mysql_query($q,$this->dbconnection);
        $this->NT_Logger->WriteLogFile("CCA SN Query: $q");
        $this->CCA_SN = @mysql_result($r,0,0);
        $this->NT_Logger->WriteLogFile("CCA SN: $this->CCA_SN");

        //Get WCA Serial Number
        $q ="SELECT FE_Components.SN FROM FE_Components, FE_ConfigLink, FE_Config
        WHERE FE_ConfigLink.fkFE_Config = " .$this->GetValue('fkFE_Config'). "
        AND FE_Components.fkFE_ComponentType = 11
        AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
        AND FE_Components.Band = " . $this->GetValue('Band') . "
        AND FE_Components.keyFacility =" . $this->GetValue('keyFacility') ."
        AND FE_ConfigLink.fkFE_ConfigFacility = FE_Config.keyFacility
        GROUP BY Band ASC;";
        $r = @mysql_query($q,$this->dbconnection);
        $this->NT_Logger->WriteLogFile("WCA SN Query: $q");
        $this->WCA_SN = @mysql_result($r,0,0);
        $this->NT_Logger->WriteLogFile("WCA SN: $this->WCA_SN");

        //Get list of CCA FE_Component keyid, history for this CCA:
        $q ="SELECT keyId FROM FE_Components
        WHERE SN = $this->CCA_SN AND fkFE_ComponentType = 20
        AND band = " . $this->GetValue('Band') . "
        AND keyFacility =" . $this->GetValue('keyFacility') ."
        GROUP BY keyId DESC";
        $r = @mysql_query($q,$this->dbconnection);
        $this->NT_Logger->WriteLogFile("CCA FE_Component key query: $q");
        while ($row = @mysql_fetch_array($r)) {
            // append to the array of CCA component keys:
            $temp = $row[0];
            $this->CCA_componentKeys[] = $row[0];
        }
        $this->NT_Logger->WriteLogFile("CCA FE_Component key: " . $this->CCA_componentKeys[0]);
    }

    private function LoadImageRejectionData() {
        // toss any old data in the IR arrays:
        unset($this->IR_USB_Pol0_Sb1);
        unset($this->IR_LSB_Pol0_Sb2);
        unset($this->IR_USB_Pol1_Sb1);
        unset($this->IR_LSB_Pol1_Sb2);
        unset($this->IR_Pol0_Sb1);
        unset($this->IR_Pol0_Sb2);
        unset($this->IR_Pol1_Sb1);
        unset($this->IR_Pol1_Sb2);

        $CCA_TD_key = FALSE;    // the test data header ID for the IR data goes here.
        $this->foundIRData = false;

        // don't load IR data for bands 1, 9 or 10:
        $band = $this->GetValue('Band');
        if ($band != 1 && $band != 9 && $band != 10) {

            //Find the first matching Image Rejection Data corresponding to one of the CCA componentKeys:
            //TODO: should we be finding the newest IR data?
            $index = 0;
            do {
                $compKey = $this->CCA_componentKeys[$index];

                //get CCA Test Data key for cartridge image rejection:
                $q = "SELECT keyID FROM TestData_header WHERE fkTestData_Type = 38
                AND fkDataStatus = 7 AND fkFE_Components = $compKey
                AND keyFacility =" . $this->GetValue('keyFacility');
                $r = @mysql_query($q, $this->dbconnection);
                $this->NT_Logger->WriteLogFile("CCA Image Rejection Testdata_Header Query: $q");
                $CCA_TD_key = @mysql_result($r, 0, 0);
                $this->NT_Logger->WriteLogFile("CCA TD key: $CCA_TD_key");

                $index++;
            } while ($CCA_TD_key === FALSE && $index < count($this->CCA_componentKeys));
        }

        // didn't find IR data or band is 1, 9 or 10:
        if ($CCA_TD_key === FALSE) {
            $this->foundIRData = false;
            if ($band != 1 && $band != 9 && $band != 10) {
                echo "<B>NO CARTRIDGE IMAGE REJECTION DATA FOUND<B><BR><BR>";
                $this->NT_Logger->WriteLogFile("No Cartridge Image Rejection Data Found");
            }

        // found IR data for band <= 8:
        } else {
            $this->NT_Logger->WriteLogFile("Cartridge Image Rejection Data Was Found");

            // find the max keyDataSet for CCA image rejection:
            $q ="SELECT MAX(keyDataSet) FROM CCA_TEST_SidebandRatio WHERE fkheader = $CCA_TD_key";
            $r = @mysql_query($q,$this->dbconnection);
            $keyDataSet = @mysql_result($r,0,0);

            //get CCA Image Rejection data
            $q = "SELECT FreqLO, CenterIF, Pol, SB, SBR
            FROM CCA_TEST_SidebandRatio WHERE fkHeader = $CCA_TD_key
            AND fkFacility =" . $this->GetValue('keyFacility') . "
            ORDER BY POL DESC, SB DESC, FreqLO ASC, CenterIF DESC";
            $r = @mysql_query($q,$this->dbconnection);
            $this->NT_Logger->WriteLogFile("CCA Image Rejection Data Query: $q");

            // initialize arrays:
            $this->IR_USB_Pol0_Sb1 = array();    // these arrays contain the RF corresponding to the IR
            $this->IR_LSB_Pol0_Sb2 = array();
            $this->IR_USB_Pol1_Sb1 = array();
            $this->IR_LSB_Pol1_Sb2 = array();
            $this->IR_Pol0_Sb1 = array();        // these arrays contain the image rejection
            $this->IR_Pol0_Sb2 = array();
            $this->IR_Pol1_Sb1 = array();
            $this->IR_Pol1_Sb2 = array();

            $count = 0;
            while ($row = @mysql_fetch_array($r)) {
                $count++;
                if ($row[2] == 0) {                                    // Pol0
                    if ($row[3] == 2) {                                // SB2
                        $this->IR_LSB_Pol0_Sb2[] = $row[0] - $row[1];  // RF = LSB
                        $this->IR_Pol0_Sb2[] = $row[4];

                    } else {                                           // SB1 or undefinded
                        $this->IR_USB_Pol0_Sb1[] = $row[0] + $row[1];  // RF = USB
                        $this->IR_Pol0_Sb1[] = $row[4];
                    }
                } else {                                               // Pol1
                    if ($row[3] == 2) {                                // SB2
                        $this->IR_LSB_Pol1_Sb2[] = $row[0] - $row[1];  // RF = LSB
                        $this->IR_Pol1_Sb2[] = $row[4];
                    } else {                                           // SB1 or undefinded
                        $this->IR_USB_Pol1_Sb1[] = $row[0] + $row[1];  // RF = USB
                        $this->IR_Pol1_Sb1[] = $row[4];
                    }
                }
            }
            if ($count > 0) {
                // found at least one row of IR data:
                $this->foundIRData = true;
            }
        }
    }

    private function LoadSpecs() {
        //get specs from DB
    	$new_specs = new Specifications();
    	$specs = $new_specs->getSpecs('FEIC_NoiseTemperature' , $this->GetValue('Band'));

    	$keys = array_keys($specs);
    	$values = array_values($specs);

//     	for($i=0; $i<count($keys); $i++) {
//     		echo $keys[$i], $values[$i];
//     	}

    	$this->effColdLoadTemp = $specs['CLTemp'];         // effective cold load temperature
    	$this->default_IR = $specs['defImgRej'];              // default image rejection to use if no CCA data available.
    	$this->lowerIFLimit = $specs['loIFLim'];            // lower IF limit
		$this->upperIFLimit = $specs['hiIFLim'];            // upper IF limit
		$this->NT_allRF_spec = $specs['NT20'];           // spec which must me met at all points in the RF band
		$this->NT_80_spec = $specs['NT80'];              // spec which must be met over 80% of the RF band

		// extra Tssb spec applies to band 3 only:
		if ($this->GetValue('Band') == 3) {
			$this->NT_B3Special_spec=$specs['B3exSpec'];
		}
		// lower RF limit for applying 80% spec:
		$this->lower_80_RFLimit = (isset($specs['NT80RF_loLim']))? $specs['NT80RF_loLim'] : 0;

		// upper RF limit for applying 80% spec:
		$this->upper_80_RFLimit = (isset($specs['NT80RF_hiLim'])) ? $specs['NT80RF_hiLim'] : 0;

		$this->lowerRFLimit = 0;
		$this->upperRFLimit = 1000;
    }

    private function WriteSpecsDataFile() {
        $this->spec_datafile = $this->plotDir . "NoiseTemp_spec.txt";
        $fspec = fopen($this->spec_datafile,'w');

        // write specifications datafile
        $string1 = "$this->lowerIFLimit\t$this->NT_80_spec\t$this->NT_allRF_spec\r\n";
        $string2 = "$this->upperIFLimit\t$this->NT_80_spec\t$this->NT_allRF_spec\r\n";
        $writestring = $string1 . $string2;
        fwrite($fspec,$writestring);
        fclose($fspec);
    }

    private function LoadNoiseTempData() {
        unset($this->NT_data);
        $this->NT_data = array();

        // if no DataSetGroup defined, use the TDH provided to Initialize_NoiseTemperature:
        if ($this->GetValue('DataSetGroup') == 0) {
            $q = "SELECT FreqLO, CenterIF, TAmbient, Pol0Sb1YFactor, Pol0Sb2YFactor, Pol1Sb1YFactor, Pol1Sb2YFactor
            FROM Noise_Temp
            WHERE fkSub_Header=" . $this->NT_SubHeader->GetValue('keyId') . "
            AND keyFacility =" . $this->GetValue('keyFacility') . "
            AND Noise_Temp.IsIncluded = 1
            ORDER BY FreqLO ASC, CenterIF ASC";

        // load all data for the specified DataSetGroup:
        } else {
            // nested query to get front end key of the FEConfig of the TDH.
            $qfe = "SELECT fkFront_Ends FROM `FE_Config` WHERE `keyFEConfig` = ". $this->GetValue('fkFE_Config');

            // Get all Noise_Temp_SubHeader keyId values for records with the same DataSetGroup as this one
            $q = "SELECT Noise_Temp.FreqLO, Noise_Temp.CenterIF, Noise_Temp.TAmbient,
            Noise_Temp.Pol0Sb1YFactor, Noise_Temp.Pol0Sb2YFactor, Noise_Temp.Pol1Sb1YFactor, Noise_Temp.Pol1Sb2YFactor, TestData_header.keyId
            FROM FE_Config
            LEFT JOIN TestData_header ON TestData_header.fkFE_Config = FE_Config.keyFEConfig
            LEFT JOIN Noise_Temp_SubHeader ON Noise_Temp_SubHeader.`fkHeader` = `TestData_header`.`keyId`
            LEFT JOIN Noise_Temp ON Noise_Temp_SubHeader.`keyId` = Noise_Temp.fkSub_Header
            WHERE TestData_header.Band = " . $this->GetValue('Band')."
            AND TestData_header.fkTestData_Type = 58
            AND TestData_header.DataSetGroup = " . $this->GetValue('DataSetGroup')."
            AND Noise_Temp.IsIncluded = 1
            AND FE_Config.fkFront_Ends = ($qfe)
            ORDER BY Noise_Temp.FreqLO ASC, Noise_Temp.CenterIF ASC";
        }

        $this->NT_Logger->WriteLogFile("LoadNoiseTempData query: $q");
        $r = @mysql_query($q, $this->dbconnection);

        // Append the loaded noise temperature data into a 2-d array:
        while ($row = @mysql_fetch_array($r)) {
            $rowData = array (
                'FreqLO'         => $row[0],
                'CenterIF'       => $row[1],
                'TAmbient'       => $row[2],
                'Pol0Sb1YFactor' => $row[3],
                'Pol0Sb2YFactor' => $row[4],
                'Pol1Sb1YFactor' => $row[5],
                'Pol1Sb2YFactor' => $row[6]
            );
            $this->NT_data[] = $rowData;
        }
    }

    private function CalculateNoiseTemps() {

        function Trx_Uncorr($TAmb, $TColdEff, $Y) {
            // compute Tr, uncorrected (K)
            if (($Y - 1) != 0)
                return ($TAmb - $TColdEff * $Y) / ($Y - 1);
            else
                return 999999;
        }

        function Tssb_Corr($TrUncorr, $IRdB) {
            // correct Tr for image rejection
            $temp = $TrUncorr * (1 + pow(10, -abs($IRdB) / 10));
            return $temp;
        }

        // total number of rows in data set:
        $cnt = count($this->NT_data);
        if (!$cnt) {
            return;
        }

        // open data files for RF, IF, and averaging plots:
        $this->rf_datafile = $this->plotDir . "NoiseTemp_RF.txt";
        $this->NT_Logger->WriteLogFile("rf datafile: $this->rf_datafile");
        $frf = fopen($this->rf_datafile,'w');

        $this->if_datafile = $this->plotDir . "NoiseTemp_IF.txt";
        $this->NT_Logger->WriteLogFile("if_datafile: $this->if_datafile");
        $fif = fopen($this->if_datafile,'w');

        // arrays for accumulating data for RF and IF plots:
        $this->FEIC_USB = array();
        $this->FEIC_LSB = array();
        $this->FEIC_Pol0Sb1 = array();
        $this->FEIC_Pol0Sb2 = array();
        $this->FEIC_Pol1Sb1 = array();
        $this->FEIC_Pol1Sb2 = array();

        // variables to hold intermediate (uncorr) and final noise temp result (possibly corrected):
        $Pol0Sb1_Tr = 0;
        $Pol0Sb2_Tr = 0;
        $Pol1Sb1_Tr = 0;
        $Pol1Sb2_Tr = 0;

        // track the current LO frequency being processed across multiple IF steps:
        $currentLO = $this->NT_data[0]['FreqLO'];
        $done = false;
        $index = 0;
        do {
            if ($index >= $cnt)
                $done = true;
            else {
                $LO     = $this->NT_data[$index]['FreqLO'];
                $IF     = $this->NT_data[$index]['CenterIF'];
                $TAmb   = $this->NT_data[$index]['TAmbient'];

                // compute RFs:
                $RF_USB = $LO + $IF;
                $RF_LSB = $LO - $IF;

                // compute Tr, uncorrected (K)
                $Pol0Sb1_Tr = Trx_Uncorr($TAmb, $this->effColdLoadTemp, $this->NT_data[$index]['Pol0Sb1YFactor']);
                $Pol0Sb2_Tr = Trx_Uncorr($TAmb, $this->effColdLoadTemp, $this->NT_data[$index]['Pol0Sb2YFactor']);
                $Pol1Sb1_Tr = Trx_Uncorr($TAmb, $this->effColdLoadTemp, $this->NT_data[$index]['Pol1Sb1YFactor']);
                $Pol1Sb2_Tr = Trx_Uncorr($TAmb, $this->effColdLoadTemp, $this->NT_data[$index]['Pol1Sb2YFactor']);

                // Select Image Rejection data:
                if ($this->foundIRData) {
                    // pol 0 SB1
                    $IRindex = array_search($RF_USB, $this->IR_USB_Pol0_Sb1);
                    if ($IRindex !== FALSE)
                        $IR_0_1 = $this->IR_Pol0_Sb1[$IRindex];
                    else
                        $IR_0_1 = $this->default_IR;

                    // pol 0 SB2
                    $IRindex = array_search($RF_LSB, $this->IR_LSB_Pol0_Sb2);
                    if ($IRindex !== FALSE)
                        $IR_0_2 = $this->IR_Pol0_Sb2[$IRindex];
                    else
                        $IR_0_2 = $this->default_IR;

                    // pol 1 SB1
                    $IRindex = array_search($RF_USB, $this->IR_USB_Pol1_Sb1);
                    if ($IRindex !== FALSE)
                        $IR_1_1 = $this->IR_Pol1_Sb1[$IRindex];
                    else
                        $IR_1_1 = $this->default_IR;

                    // pol 1 SB2
                    $IRindex = array_search($RF_LSB, $this->IR_LSB_Pol1_Sb2);
                    if ($IRindex !== FALSE)
                        $IR_1_2 = $this->IR_Pol1_Sb2[$IRindex];
                    else
                        $IR_1_2 = $this->default_IR;

                    // correct the data using image correction
                    //Tssb, corrected (K)
                    $Pol0Sb1_Tr = Tssb_Corr($Pol0Sb1_Tr, $IR_0_1);
                    $Pol0Sb2_Tr = Tssb_Corr($Pol0Sb2_Tr, $IR_0_2);
                    $Pol1Sb1_Tr = Tssb_Corr($Pol1Sb1_Tr, $IR_1_1);
                    $Pol1Sb2_Tr = Tssb_Corr($Pol1Sb2_Tr, $IR_1_2);
                }

                // save to data array:
                $this->NT_data[$index]['Pol0Sb1Tr'] = $Pol0Sb1_Tr;
                $this->NT_data[$index]['Pol0Sb2Tr'] = $Pol0Sb2_Tr;
                $this->NT_data[$index]['Pol1Sb1Tr'] = $Pol1Sb1_Tr;
                $this->NT_data[$index]['Pol1Sb2Tr'] = $Pol1Sb2_Tr;
            }

            // do things which happen when we encounter a new LO or the end of the data:
            if (($LO != $currentLO) || $done) {
                // insert blank line between LO freqs in RF and IF trace plots:
                $writestring = "\r\n";
                fwrite($frf, $writestring);
                fwrite($fif, $writestring);

                // move to next currentLO:
                if (!$done)
                    $currentLO = $LO;
            }

            // now save and write out the current row data using currentLO:
            if (!$done) {
                // write out data for the IF plot:
                $writestring = "$IF\t$Pol0Sb1_Tr\t$Pol0Sb2_Tr\t$Pol1Sb1_Tr\t$Pol1Sb2_Tr\r\n";
                fwrite($fif, $writestring);

                // for IFs within the IF spec range...
                if ($this->lowerIFLimit <= $IF && $IF <= $this->upperIFLimit) {

                    // write out data for the RF plot:
                    $writestring = "$RF_USB\t$RF_LSB\t$Pol0Sb1_Tr\t$Pol0Sb2_Tr\t$Pol1Sb1_Tr\t$Pol1Sb2_Tr\r\n";
                    fwrite($frf, $writestring);

                    // append to arrays for compare with cart data:
                    $this->FEIC_USB[] = $RF_USB;
                    $this->FEIC_LSB[] = $RF_LSB;
                    $this->FEIC_Pol0Sb1[] = $Pol0Sb1_Tr;
                    $this->FEIC_Pol0Sb2[] = $Pol0Sb2_Tr;
                    $this->FEIC_Pol1Sb1[] = $Pol1Sb1_Tr;
                    $this->FEIC_Pol1Sb2[] = $Pol1Sb2_Tr;
                }
                $index++;
            }
        } while (!$done);

        fclose($frf);
        fclose($fif);
    }

    private function CalculateAvgNoiseTemps() {
        unset($this->NT_avgData);
        $this->NT_avgData = array();

        // total number of rows in data set:
        $cnt = count($this->NT_data);
        if (!$cnt) {
            return;
        }

        $this->avg_datafile = $this->plotDir . "NoiseTemp_avg.txt";
        $this->NT_Logger->WriteLogFile("average_datafile: $this->avg_datafile");
        $favg = fopen($this->avg_datafile,'w');

        // arrays for accumulating data points for the averaging plot:
        $Pol0_Sb1_avg = array();
        $Pol0_Sb2_avg = array();
        $Pol1_Sb1_avg = array();
        $Pol1_Sb2_avg = array();

        // track the current LO frequency being processed across multiple IF steps:
        $currentLO = $this->NT_data[0]['FreqLO'];
        $done = false;
        $index = 0;
        do {
            if ($index >= $cnt)
                $done = true;
            else {
                $LO = $this->NT_data[$index]['FreqLO'];
                $IF = $this->NT_data[$index]['CenterIF'];
                $Pol0Sb1_Tr = $this->NT_data[$index]['Pol0Sb1Tr'];
                $Pol0Sb2_Tr = $this->NT_data[$index]['Pol0Sb2Tr'];
                $Pol1Sb1_Tr = $this->NT_data[$index]['Pol1Sb1Tr'];
                $Pol1Sb2_Tr = $this->NT_data[$index]['Pol1Sb2Tr'];
            }

            // do things which happen when we encounter a new LO or the end of the data:
            if (($LO != $currentLO) || $done) {

                // calculate the averaged noise temps across the whole IF:
                $avg01 = array_sum($Pol0_Sb1_avg) / count($Pol0_Sb1_avg);
                $avg02 = array_sum($Pol0_Sb2_avg) / count($Pol0_Sb2_avg);
                $avg11 = array_sum($Pol1_Sb1_avg) / count($Pol1_Sb1_avg);
                $avg12 = array_sum($Pol1_Sb2_avg) / count($Pol1_Sb2_avg);

                // points to draw 80% and full-band spec lines for averaging plot:
                if ($this->lower_80_RFLimit <= $currentLO && $currentLO <= $this->upper_80_RFLimit)
                    $spec_line1 = $this->NT_80_spec;
                else
                    $spec_line1 = NAN;

                $spec_line2 = $this->NT_allRF_spec;

                // append to array of averaged data:
                $rowData = array (
                        'FreqLO'        => $currentLO,
                        'Pol0Sb1TrAvg'  => $avg01,
                        'Pol0Sb2TrAvg'  => $avg02,
                        'Pol1Sb1TrAvg'  => $avg11,
                        'Pol1Sb2TrAvg'  => $avg12,
                        'spec_line1'    => $spec_line1,
                        'spec_line2'    => $spec_line2
                );
                $this->NT_avgData[] = $rowData;

                // reset arrays for averaging noise temp:
                unset($Pol0_Sb1_avg);
                unset($Pol0_Sb2_avg);
                unset($Pol1_Sb1_avg);
                unset($Pol1_Sb2_avg);

                // write out data for the averaging plot:
                $writestring = "$currentLO\t$avg01\t$avg02\t$avg11\t$avg12\t$spec_line1\t$spec_line2\r\n";
                fwrite($favg, $writestring);

                // move to next currentLO:
                if (!$done)
                    $currentLO = $LO;
            }

            if (!$done) {
                // for IFs within the IF spec range...
                if ($this->lowerIFLimit <= $IF && $IF <= $this->upperIFLimit) {
                    // append to arrays for IF averaging plot:
                    $Pol0_Sb1_avg[] = $Pol0Sb1_Tr;
                    $Pol0_Sb2_avg[] = $Pol0Sb2_Tr;
                    $Pol1_Sb1_avg[] = $Pol1Sb1_Tr;
                    $Pol1_Sb2_avg[] = $Pol1Sb2_Tr;
                }
                $index++;
            }
        } while (!$done);

        fclose($favg);
    }

    private function CalculateBand3AvgNT() {
        // For band 3 read the average NT file and store the information in the db
        if ($this->GetValue('Band') == 3) {
            $favg = fopen($this->avg_datafile, 'r');

            $values = "";

            // read file and format data into a string to write out in a DB query
            while ($scan = fscanf($favg, "%f\t%f\t%f\t%f\t%f\t%f\r\n")) {
                list ($freq, $avg01, $avg02, $avg11, $avg12, $this->NT_80_spec) = $scan;
                $avg = ($avg01 + $avg02 + $avg11 + $avg12) / 4;
                $values = "(" . $this->GetValue('keyId') . ",$freq,$avg01,$avg02,$avg11,$avg12,$avg)," . $values;
            }
            //delete last "," and replace it with ";"
            $values = substr_replace ($values,";",(strlen ($values))-1);

            //query to delete any existing data in the DB with the same TD Header keyID
            $q = "DELETE FROM `Noise_Temp_Band3_Results`
            WHERE  `fkHeader` = " . $this->GetValue('keyId') . "";
            $r = @mysql_query($q, $this->dbconnection);

            // query to insert new data into table
            $q ="INSERT INTO `Noise_Temp_Band3_Results`
            (`fkHeader`,`FreqLO`,`Pol0USB`,`Pol0LSB`,`Pol1USB`,`Pol1LSB`,`AvgNT`)
            VALUES $values";
            $r = @mysql_query($q, $this->dbconnection);

            $this->NT_Logger->WriteLogFile("Band3 Replace Query: $q\r\n");

            fclose($favg);
        }
    }

    private function CalculateBand10AvgNT() {

        function truncateSpanToSpecRange($LO0, $LO1, $lower80, $upper80) {
            // compute the portion within the spec bounds:
            $span = $LO1 - $LO0;
            if ($LO0 < $lower80)
                $span = $LO1 - $lower80;
            else if ($LO1 > $upper80)
                $span = $upper80 - $LO0;
            return $span;
        }

        function freqSpanOutOfSpec($LO0, $LO1, $TR0, $TR1, $spec, $lower80, $upper80) {
            // What portion of the LO range between $LO0 and $LO1 have TR out of spec?
            // - Use linear interpolation between the two points.
            // - Truncate to the range to where the 80% spec applies.

            // $LO1 must be > $LO0
            if ($LO1 <= $LO0)
                return 0;

            // at least one point must be in the spec range:
            if ($LO0 < $lower80 && $LO1 < $lower80)
                return 0;
            if ($LO0 > $upper80 && $LO1 > $upper80)
                return 0;

            // check for endpoints in spec:
            $inSpec0 = $TR0 <= $spec;
            $inSpec1 = $TR1 <= $spec;

            // both endppoints are in spec:
            if ($inSpec0 && $inSpec1)
                return 0;

            // neither endpoint in spec:
            if (!$inSpec0 && !$inSpec1)
                // return the portion within the 80% spec range:
                return truncateSpanToSpecRange($LO0, $LO1, $lower80, $upper80);

            // calculate abs(slope) between the two points:
            $slope = abs($TR1 - $TR0) / ($LO1 - $LO0);

            // first endpoint is in spec, second isn't:
            if ($inSpec0 && !$inSpec1) {
                // how far above the spec is the second endpoint?
                $failTr = $TR1 - $spec;

                // slope crosses the spec line at:
                $failLO = $LO1 - ($failTr / $slope);

                // return the portion of the out of spec part within the 80% spec range:
                return truncateSpanToSpecRange($failLO, $LO1, $lower80, $upper80);
            }
            // second endpoint is in spec, first isn't:
            if (!$inSpec0 && $inSpec1) {
                // how far above the spec is the first endpoint?
                $failTr = $TR0 - $spec;

                // slope crosses the spec line at:
                $failLO = $LO0 + ($failTr / $slope);

                // return the portion of the out of spec part within the 80% spec range:
                return truncateSpanToSpecRange($LO0, $failLO, $lower80, $upper80);
            }
            // impossible case:
            return false;
        }

        $this->Pol0_80_metric = 0;
        $this->Pol1_80_metric = 0;

        // don't do this except for band 10:
        if ($this->GetValue('Band') != 10)
            return;

        // total number of rows in data set:
        $cnt = count($this->NT_avgData);
        if (!$cnt)
            return;

        // vars to accumulate frequency span out of spec:
        $span0 = $span1 = 0;
        unset($lastRow);

        // adjust limits for this calculation only:
        $lower = $this->lower_80_RFLimit + 12;
        $upper = $this->upper_80_RFLimit - 12;
        $specRange = $upper - $lower;

        // in the loop we'll check whether the 80% spec range is covered or not:
        $lower80measured = false;
        $upper80measured = false;

        foreach($this->NT_avgData as $thisRow) {
            $LO1 = $thisRow['FreqLO'];

            // loop on segments between data points, not indvidual points.
            if (isset($lastRow)) {
                $LO0 = $lastRow['FreqLO'];

                $span0 += freqSpanOutOfSpec($LO0, $LO1, $lastRow['Pol0Sb1TrAvg'], $thisRow['Pol0Sb1TrAvg'],
                                            $this->NT_80_spec, $lower, $upper);
                $span1 += freqSpanOutOfSpec($LO0, $LO1, $lastRow['Pol1Sb1TrAvg'], $thisRow['Pol1Sb1TrAvg'],
                                            $this->NT_80_spec, $lower, $upper);

                // check whether any range in the set touches or encloses the 80% spec endpoints:
                if ($LO0 <= $lower && $LO1 > $lower)
                    $lower80measured = true;
                if ($LO0 < $upper && $LO1 >= $upper)
                    $upper80measured = true;
            }
            $lastRow = $thisRow;
        }
        // if the 80% spec endpoints are adequately covered then we can compute the 80% spec mectric:
        if ($lower80measured && $upper80measured) {
            $this->Pol0_80_metric = 100 - round($span0 / $specRange * 100, 1);
            $this->Pol1_80_metric = 100 - round($span1 / $specRange * 100, 1);
        // otherwise we will show "N/A":
        } else {
            $this->Pol0_80_metric = $this->Pol1_80_metric = 0;
        }
    }

    private function LoadAndWriteCCANoiseTempData() {
        $this->foundCCAData = false;

        $this->datafile_cart_0_1 = $this->plotDir . "NoiseTemp_Cart_pol0_SB1.txt";
        $this->NT_Logger->WriteLogFile("datafile_cart pol 0 SB1: $this->datafile_cart_0_1");
        $fc01 = fopen($this->datafile_cart_0_1, 'w');

        $this->datafile_cart_0_2 = $this->plotDir . "NoiseTemp_Cart_pol0_SB2.txt";
        $this->NT_Logger->WriteLogFile("datafile_cart pol 0 SB2: $this->datafile_cart_0_2");
        $fc02 = fopen($this->datafile_cart_0_2, 'w');

        $this->datafile_cart_1_1 = $this->plotDir . "NoiseTemp_Cart_pol1_SB1.txt";
        $this->NT_Logger->WriteLogFile("datafile_cart pol 1 SB1: $this->datafile_cart_1_1");
        $fc11 = fopen($this->datafile_cart_1_1, 'w');

        $this->datafile_cart_1_2 = $this->plotDir . "NoiseTemp_Cart_pol1_SB2.txt";
        $this->NT_Logger->WriteLogFile("datafile_cart pol 1 SB2: $this->datafile_cart_1_2");
        $fc12 = fopen($this->datafile_cart_1_2, 'w');

        $this->datafile_diff_0_1 = $this->plotDir . "NoiseTemp_Diff_pol0_SB1.txt";
        $this->NT_Logger->WriteLogFile("datafile_diff pol 0 SB1: $this->datafile_diff_0_1");
        $fdiff01 = fopen($this->datafile_diff_0_1, 'w');

        $this->datafile_diff_0_2 = $this->plotDir . "NoiseTemp_Diff_pol0_SB2.txt";
        $this->NT_Logger->WriteLogFile("datafile_diff pol 0 SB2: $this->datafile_diff_0_2");
        $fdiff02 = fopen($this->datafile_diff_0_2, 'w');

        $this->datafile_diff_1_1 = $this->plotDir . "NoiseTemp_Diff_pol1_SB1.txt";
        $this->NT_Logger->WriteLogFile("datafile_diff pol 1 SB1: $this->datafile_diff_1_1");
        $fdiff11 = fopen($this->datafile_diff_1_1, 'w');

        $this->datafile_diff_1_2 = $this->plotDir . "NoiseTemp_Diff_pol1_SB2.txt";
        $this->NT_Logger->WriteLogFile("datafile_diff pol 1 SB2: $this->datafile_diff_1_2");
        $fdiff12 = fopen($this->datafile_diff_1_2, 'w');

        $cnt = 0;  // initialize counter for do-while loop
        $compKey = '';
        do {    // check all CCA configurations for NT data
            $compKey = $this->CCA_componentKeys[$cnt];
            //Use CCA FE_Component keyid to get keyID for Testdata header record for CCA Noise temp data
            $q ="SELECT keyId FROM TestData_header
            WHERE fkFE_Components = $compKey
            AND fkTestData_Type = 42
            AND fkDataStatus=7
            AND keyFacility =" . $this->GetValue('keyFacility') ."
            GROUP BY keyId DESC";
            $r = @mysql_query($q,$this->dbconnection);
            $this->NT_Logger->WriteLogFile("CCA Noise Temp Testdata record query: $q");
            $CCA_NT_key = @mysql_result($r,0,0);
            $this->NT_Logger->WriteLogFile("CCA NoiseTemp Testdataheader key: $CCA_NT_key");
            $cnt++;
        } while ($CCA_NT_key === FALSE && $cnt < count($this->CCA_componentKeys));

        if ($CCA_NT_key === FALSE) {
            $this->foundCCAData = false;
            echo "<B>NO CCA NOISE TEMPERATURE DATA FOUND<B><BR><BR>";

        } else {
            $this->foundCCAData = true;

            // find the max keyDataSet for CCA noise temp:
            $q ="SELECT MAX(keyDataSet) FROM CCA_TEST_NoiseTemperature WHERE fkheader = $CCA_NT_key";
            $r = @mysql_query($q,$this->dbconnection);
            $keyDataSet = @mysql_result($r,0,0);

            // finally get the CCA Noise Temp data...I'm sure there's a better way
            $q ="SELECT FreqLO, CenterIF, Pol, SB, Treceiver FROM CCA_TEST_NoiseTemperature
            WHERE fkheader = $CCA_NT_key AND keyDataSet = $keyDataSet
            ORDER BY POL DESC, SB DESC, FreqLO ASC, CenterIF DESC";
            $r = @mysql_query($q,$this->dbconnection);
            $this->NT_Logger->WriteLogFile("CCA Noise Temp data query: $q");

            $cnt_band9_0 = 0;
            $cnt_band9_1 = 0;
            $currentLO = false;
            while ($row = @mysql_fetch_array($r)) {

                // special band 3 NT_specs
                if ( $this->GetValue('Band') == 3 && $row[0] == 104) {
                   $NT_spec = $this->NT_B3Special_spec;
                } else {
                    $NT_spec = $this->NT_80_spec;
                }

                $USB = $row[0] + $row[1];
                $LSB = $row[0] - $row[1];

                // Write plot data out to files.  Only plot points within IF limits.
                if ($row[1] >= $this->lowerIFLimit && $row[1] <= $this->upperIFLimit) {

                    // pol 0 SB1
                    if ( $row[2] == 0 && $row[3] == 1) {
                        //insert empty line between LO scans to make plots look better
                        if ($currentLO != $row[0]) {
                            $currentLO = $row[0];
                            $writestring = "\r\n";
                            fwrite($fc01,$writestring);
                        }
                        // write CCA plot data to file
                        $writestring = "$USB\t$row[4]\t$row[2]\t$row[3]\r\n";
                        fwrite($fc01,$writestring);

                        // Save difference data to plot
                        $index=array_search($USB, $this->FEIC_USB);
                        if ($index !== FALSE) {
                            $diff_save=100*abs($this->FEIC_Pol0Sb1[$index]-$row[4])/$NT_spec;
                            $writestring = "$USB\t$diff_save\r\n";
                            fwrite($fdiff01,$writestring);
                        }

                    // pol 0 SB2
                    } else if ( $row[2] == 0 && $row[3] == 2) {
                        //insert empty line between LO scans to make plots look better
                        if ($currentLO != $row[0]) {
                            $currentLO = $row[0];
                            $writestring = "\r\n";
                            fwrite($fc02,$writestring);
                        }
                        // write CCA plot data to file
                        $writestring = "$LSB\t$row[4]\t$row[2]\t$row[3]\r\n";
                        fwrite($fc02,$writestring);

                        // Save difference data to plot
                        $index=array_search($LSB,$this->FEIC_LSB);
                        if ($index !== FALSE) {
                            $diff_save=100*abs($this->FEIC_Pol0Sb2[$index]-$row[4])/$NT_spec;
                            $writestring = "$LSB\t$diff_save\r\n";
                            fwrite($fdiff02,$writestring);
                        }

                    // pol 1 SB1
                    } else if ( $row[2] == 1 && $row[3] == 1 ) {
                        //insert empty line between LO scans to make plots look better
                        if ($currentLO != $row[0]) {
                            $currentLO = $row[0];
                            $writestring = "\r\n";
                            fwrite($fc11,$writestring);
                        }
                        // write CCA plot data to file
                        $writestring = "$USB\t$row[4]\t$row[2]\t$row[3]\r\n";
                        fwrite($fc11,$writestring);

                        // Save difference data to plot
                        $index=array_search($USB,$this->FEIC_USB);
                        if ($index !== FALSE) {
                            $diff_save=100*abs($this->FEIC_Pol1Sb1[$index]-$row[4])/$NT_spec;
                            $writestring = "$USB\t$diff_save\r\n";
                            fwrite($fdiff11,$writestring);
                        }

                    // pol 1 SB2
                    } else if ( $row[2] == 1 && $row[3] == 2) {
                        //insert empty line between LO scans to make plots look better
                        if ($currentLO !== $row[0]) {
                            $currentLO = $row[0];
                            $writestring = "\r\n";
                            fwrite($fc12,$writestring);
                        }
                        // write CCA plot data to file
                        $writestring = "$LSB\t$row[4]\t$row[2]\t$row[3]\r\n";
                        fwrite($fc12,$writestring);

                        // Save difference data to plot
                        $index=array_search($LSB,$this->FEIC_LSB);
                        if ($index !== FALSE) {
                        $diff_save=100*abs($this->FEIC_Pol1Sb2[$index]-$row[4])/$NT_spec;
                            $writestring = "$LSB\t$diff_save\r\n";
                            fwrite($fdiff12,$writestring);
                        }

                    // band 9 and 10 pol0 SB0
                    }  else if ($row[2] == 0 &&  $row[3] == 0) {
                        //insert empty line between LO scans to make plots look better
                        if ($currentLO != $row[0]) {
                            $currentLO = $row[0];
                            $writestring = "\r\n";
                            fwrite($fc01,$writestring);
                        }
                        // write CCA plot data to file
                        $writestring = "$USB\t$row[4]\t$row[2]\t$row[3]\r\n";
                        fwrite($fc01,$writestring);

                        // the CCA NT values are measured at a finer resolution than at the FEIC
                        // therefore the CCA NT data is averaged over a range that corresponds
                        // to a single FEIC scan.  To do this, two arrays are created here.
                        // one array is for the CCA_NT data and the other is an index array
                        // that correlates the CCA NT data to a single FEIC scan.

                        // loop through all FEIC data to correlate the CCA NT data
                        $FEIC_cnt = 0;
                        $USB_found = 0;
                        while ($USB_found != 1 && $FEIC_cnt <= count($this->FEIC_USB) ) {
                            // set window (0.1 Ghz) to correlate data
                            if ( $USB <= $this->FEIC_USB[$FEIC_cnt] + 0.05 && $USB >= $this->FEIC_USB[$FEIC_cnt] - 0.05 ) {
                                $Band9_USB_Pol0[$cnt_band9_0] = $this->FEIC_USB[$FEIC_cnt];
                                $Band9_NT_Pol0[$cnt_band9_0] = $row[4];
                                $cnt_band9_0++;
                                $USB_found = 1;
                            }
                            $FEIC_cnt++;
                        }

                    // band 9 pol1 SB0
                    } else if ( $row[2] == 1 && $row[3] == 0 ) {
                        //insert empty line between LO scans to make plots look better
                        if ($currentLO !== $row[0]) {
                            $currentLO = $row[0];
                            $writestring = "\r\n";
                            fwrite($fc11,$writestring);
                        }
                        // write CCA plot data to file
                        $writestring = "$USB\t$row[4]\t$row[2]\t$row[3]\r\n";
                        fwrite($fc11,$writestring);

                        // loop through all FEIC data to correlate the CCA NT data
                        $FEIC_cnt = 0;
                        $USB_found = 0;
                        while ($USB_found != 1 && $FEIC_cnt < count($this->FEIC_USB) ) {
                            // set window (0.1 Ghz) to correlate data
                            if ( $USB <= $this->FEIC_USB[$FEIC_cnt] + 0.05 && $USB >= $this->FEIC_USB[$FEIC_cnt] - 0.05 ) {
                                $Band9_USB_Pol1[$cnt_band9_1] = $this->FEIC_USB[$FEIC_cnt];
                                $Band9_NT_Pol1[$cnt_band9_1] = $row[4];
                                $cnt_band9_1++;
                                $USB_found = 1;
                            }
                            $FEIC_cnt++;
                        }
                        if ($USB_found == 0) {
                            echo "POL 1 USB not found: $USB<br>";
                        }
                    }
                } // end if data is in IF limits
            } // end while loop

            // Average the band 9&10 Cartridge data
            if ( $this->GetValue('Band') == 9 || $this->GetValue('Band') == 10 ) {

                // calculate difference for Pol 0
                $FEIC_cnt = 0;    // initilize index for FEIC NT data
                foreach ($this->FEIC_USB as $USB_FEIC) {
                    $indexes=array_keys($Band9_USB_Pol0,$USB_FEIC);
                    $index_cnt = count($indexes);    //  How many values need to be averaged
                    if ($index_cnt != 0) {
                        $sum =0;        // initialize sum
                        foreach ($indexes as $index) {    // average values
                            $sum = $Band9_NT_Pol0[$index] + $sum;
                        }
                        $avg = $sum / $index_cnt;        //  calculate average NT

                        // save difference data to file
                        $diff_save = 100 * abs($this->FEIC_Pol0Sb1[$FEIC_cnt] - $avg) / $NT_spec;
                        $writestring = "$USB_FEIC\t$diff_save\r\n";
                        fwrite($fdiff01,$writestring);
                    }
                    $FEIC_cnt++; // increment index
                }

                // calculate difference for Pol 1
                $FEIC_cnt = 0;    // initilize index for FEIC NT data
                foreach ($this->FEIC_USB as $USB_FEIC) {
                    $indexes=array_keys($Band9_USB_Pol1,$USB_FEIC);
                    $index_cnt = count($indexes);    //  How many values need to be averaged
                    if ($index_cnt == 0) {
                    } else {
                        $sum =0;        // initialize sum
                        foreach ($indexes as $index) {    // average values
                            $sum = $Band9_NT_Pol1[$index] + $sum;
                        }
                        $avg = $sum / $index_cnt;        //  calculate average NT

                        // save difference data to file
                        $diff_save=100*abs($this->FEIC_Pol1Sb1[$FEIC_cnt]-$avg)/$NT_spec;
                        $writestring = "$USB_FEIC\t$diff_save\r\n";
                        fwrite($fdiff11,$writestring);
                    }
                    $FEIC_cnt++; // increment index
                }
            }
        }
        fclose($fc01);
        fclose($fc02);
        fclose($fc11);
        fclose($fc12);
        fclose($fdiff01);
        fclose($fdiff02);
        fclose($fdiff11);
        fclose($fdiff12);
    }

    private function MakePlotFooterLabels() {
        // if no DataSetGroup then use data from the test TestData_header
        if ($this->GetValue('DataSetGroup') == 0) {
            $this->plot_label_1 = "set label 'TestData_header.keyId: $this->keyId, Plot SWVer: $this->Plot_SWVer, Meas SWVer: " . $this->GetValue('Meas_SWVer') . "' at screen 0.01, 0.01\r\n";
            $this->plot_label_2 = "set label '" . $this->GetValue('TS') . ", FE Configuration " . $this->GetValue('fkFE_Config') . ", TcoldEff=$this->effColdLoadTemp K' at screen 0.01, 0.04\r\n";

        // find the max timestamp and FE config number for the plot labels
        } else {
            $q = "SELECT `TestData_header`.keyID, `TestData_header`.TS,`TestData_header`.`fkFE_Config`,`TestData_header`.Meas_SWVer
            FROM FE_Config
            LEFT JOIN `TestData_header` ON TestData_header.fkFE_Config = FE_Config.keyFEConfig
            WHERE TestData_header.Band = " . $this->GetValue('Band')."
            AND TestData_header.fkTestData_Type= " . $this->GetValue('fkTestData_Type')."
            AND TestData_header.DataSetGroup= " . $this->GetValue('DataSetGroup')."
            AND FE_Config.fkFront_Ends = (SELECT fkFront_Ends FROM `FE_Config` WHERE `keyFEConfig` = ".$this->GetValue('fkFE_Config').")
            ORDER BY `TestData_header`.keyID DESC";
            $r = @mysql_query($q, $this->dbconnection);

            $cnt = 0; //initialize counter
            while ($row = @mysql_fetch_array($r)) {
                if ($cnt == 0) { // initialize label variables
                    $keyId = $row[0];
                    $maxTS = $row[1];
                    $minTS = $row[1];
                    $max_FE_Config = $row[2];
                    $min_FE_Config = $row[2];
                    $meas_ver = $row[3];
                } else { // find the max and min TS and FE_config
                    $keyId = "$keyId,$row[0]";
                    if ($row[1] > $maxTS) {
                        $maxTS = $row[1];
                    }
                    if ($row[1] < $minTS) {
                        $minTS = $row[1];
                    }
                    if ($row[1] > $max_FE_Config) {
                        $max_FE_Config = $row[2];
                    }
                    if ($row[1] < $min_FE_Config) {
                        $min_FE_Config = $row[2];
                    }
                }
                $cnt++;
            }
            // format label string variables to display
            if ($cnt > 1) {
                $TS = "($maxTS, $minTS)";
                $FE_Config = "($max_FE_Config, $min_FE_Config)";
            } else {
                $TS = "$maxTS";
                $FE_Config = "$max_FE_Config";
            }
            $this->plot_label_1 = "set label 'TestData_header.keyId: ($keyId), Plot SWVer: $this->Plot_SWVer, Meas SWVer: $meas_ver' at screen 0.01, 0.01\r\n";
            $this->plot_label_2 = "set label 'Dataset: " . $this->GetValue('DataSetGroup') . ", TS: $TS, FE Configuration $FE_Config, TcoldEff=$this->effColdLoadTemp K' at screen 0.01, 0.04\r\n";
        }
    }

    private function DrawPlotTrVsIF() {
        // Tssb vs IF frequency plot
        $imagename = "Tssb_vs_IF_NoiseTemp " . date('Y_m_d_H_i_s') . ".png";
        $imagepath = $this->plotDir . $imagename;
        $this->NT_Logger->WriteLogFile("image path: $imagepath");
        $plot_title = "Receiver Noise Temperature ";
        if ($this->GetValue('Band') == 1)
        	$plot_title .= "Tssb";
        elseif ($this->foundIRData)
            $plot_title .= "Tssb corrected";
        else
            $plot_title .= "T_rec uncorrected";
        $plot_title .= ", FE SN" . $this->FrontEnd->GetValue('SN') . ", CCA" . $this->GetValue('Band') . "-$this->CCA_SN WCA" . $this->GetValue('Band') . "-$this->WCA_SN";
        $this->y_lim = 1.3 * $this->NT_allRF_spec;  // upper limit to y axis

        // Create GNU plot command file for Tssb vs IF plot command
        $commandfile = $this->plotDir . "Tssb_vs_IF_plotcommands.txt";
        $f = fopen($commandfile, 'w');
        $this->NT_Logger->WriteLogFile("command file: $commandfile");
        fwrite($f, "set terminal png size 900,600 crop\r\n");
        fwrite($f, "set output '$imagepath'\r\n");
        fwrite($f, "set title '$plot_title'\r\n");
        fwrite($f, "set xlabel 'IF(GHz)'\r\n");
        if ($this->GetValue('Band') == 1 || $this->foundIRData)
            fwrite($f, "set ylabel 'Tssb (K)'\r\n");
        else
            fwrite($f, "set ylabel 'T_Rec (K)'\r\n");
        fwrite($f, "set yrange [0:$this->y_lim]\r\n");
        fwrite($f, "set key outside\r\n");
        fwrite($f, "set bmargin 6\r\n");
        fwrite($f, $this->plot_label_1);
        fwrite($f, $this->plot_label_2);
        // band dependent plotting
        $hasSB2 = (($this->GetValue('Band') != 1) && ($this->GetValue('Band') != 9) && ($this->GetValue('Band') != 10));

        if (!$hasSB2) {
            fwrite($f, "plot  '$this->if_datafile' using 1:2 with lines lt 1 lw 1 title 'Pol0',");
            fwrite($f, "'$this->if_datafile' using 1:4 with lines lt 3 lw 1 title 'Pol1'\r\n");
        } else {
            fwrite($f, "plot  '$this->if_datafile' using 1:2 with lines lt 1 lw 1 title 'Pol0sb1',");
            fwrite($f, "'$this->if_datafile' using 1:3 with lines lt 2 lw 1 title 'Pol0sb2',");
            fwrite($f, "'$this->if_datafile' using 1:4 with lines lt 3 lw 1 title 'Pol1sb1',");
            fwrite($f, "'$this->if_datafile' using 1:5 with lines lt 4 lw 1 title 'Pol1sb2'\r\n");
        }

        fclose($f);

        // get the main data files write directory from config_main:
        require(site_get_config_main());

        //Call gnuplot
        system("$GNUPLOT $commandfile");

        // store image location
        $image_url = $main_url_directory . "noisetemp/$imagename";
        $this->NT_SubHeader->SetValue('ploturl3',$image_url);
    }

    private function DrawPlotTrAverage() {
        // Average Tssb vs LO frequency plot
        $imagename = "Avg_Tssb_vs_LO_NoiseTemp " . date('Y_m_d_H_i_s') . ".png";
        $imagepath = $this->plotDir . $imagename;
        $this->NT_Logger->WriteLogFile("image path: $imagepath");

        $plot_title = "Receiver Noise Temperature ";
        if ($this->GetValue('Band') == 1)
        	$plot_title .= "Tssb";
      	elseif ($this->foundIRData)
            $plot_title .= "Tssb corrected";
        else
            $plot_title .= "T_Rec uncorrected";
        $plot_title .= ", FE SN" . $this->FrontEnd->GetValue('SN') . ", CCA" . $this->GetValue('Band') . "-$this->CCA_SN WCA" . $this->GetValue('Band') . "-$this->WCA_SN";

        // Create GNU plot command file averaging plot command
        $commandfile = $this->plotDir . "Avg_Tssb_vs_LO_plotcommands.txt";
        $f = fopen($commandfile,'w');
        $this->NT_Logger->WriteLogFile("command file: $commandfile");
        fwrite($f, "set terminal png size 900,600 crop\r\n");
        fwrite($f, "set output '$imagepath'\r\n");
        fwrite($f, "set title '$plot_title'\r\n");
        fwrite($f, "set xlabel 'LO(GHz)'\r\n");
        if ($this->GetValue('Band') == 1 || $this->foundIRData)
            fwrite($f, "set ylabel 'Average Tssb (K)'\r\n");
        else
            fwrite($f, "set ylabel 'Average T_Rec (K)'\r\n");
        fwrite($f, "set yrange [0:$this->y_lim]\r\n");
        fwrite($f, "set key outside\r\n");
        fwrite($f, "set bmargin 6\r\n");
        fwrite($f, $this->plot_label_1);
        fwrite($f, $this->plot_label_2);

        switch ( $this->GetValue('Band') ) {
            case 1:
            case 9:
                fwrite($f, "plot  '$this->avg_datafile' using 1:2 with linespoints lt 1 lw 1 title 'Pol0',");
                fwrite($f, "'$this->avg_datafile' using 1:4 with linespoints lt 3 lw 1 title 'Pol1',");
                fwrite($f, "'$this->avg_datafile' using 1:7 with lines lt 1 lw 3 title ' $this->NT_allRF_spec K (100%)',");
                fwrite($f, "'$this->avg_datafile' using 1:6 with lines lt -1 lw 3 title ' $this->NT_80_spec K (80%)'\r\n");

                break;

            case 10:

                $this->avSpec_datafile = $this->plotDir . "NoiseTemp_avSpec.txt";
                $fspec = fopen($this->avSpec_datafile,'w');

                // write specifications datafile
                $lower = $this->lower_80_RFLimit + 12;
                $upper = $this->upper_80_RFLimit - 12;

                fwrite($fspec,"787\tNAN\t$this->NT_allRF_spec\r\n");
                fwrite($fspec,"$lower\t$this->NT_80_spec\t$this->NT_allRF_spec\r\n");
                fwrite($fspec,"$upper\t$this->NT_80_spec\t$this->NT_allRF_spec\r\n");
                fwrite($fspec,"950\tNAN\t$this->NT_allRF_spec\r\n");
                fclose($fspec);

                fwrite($f, 'set label "' .
                        "Compliance metric for $this->NT_80_spec K spec over $this->lower_80_RFLimit-$this->upper_80_RFLimit RF " .
                        "(computed against $lower-$upper LO)" .
                        '" at screen .1, .91'."\r\n");

                $metricText = ($this->Pol0_80_metric > 0) ? "$this->Pol0_80_metric%" : "N/A";
                fwrite($f, 'set label "' .
                           "Pol0: $metricText" .
                           '" at screen .1, .88');
                if ($this->Pol0_80_metric < 80)
                    fwrite($f, ' tc lt 1');
                fwrite($f, "\r\n");

                $metricText = ($this->Pol1_80_metric > 0) ? "$this->Pol1_80_metric%" : "N/A";
                fwrite($f, 'set label "' .
                           "Pol1: $metricText" .
                           '" at screen .2, .88');
                if ($this->Pol1_80_metric < 80)
                    fwrite($f, ' tc lt 1');
                fwrite($f, "\r\n");

                fwrite($f, "plot  '$this->avg_datafile' using 1:2 with linespoints lt 1 lw 1 title 'Pol0',");
                fwrite($f, "'$this->avg_datafile' using 1:4 with linespoints lt 3 lw 1 title 'Pol1',");
                fwrite($f, "'$this->avSpec_datafile' using 1:3 with lines lt -1 lw 3 title ' $this->NT_allRF_spec K (100%)',");
                fwrite($f, "'$this->avSpec_datafile' using 1:2 with lines lt 0 lw 3 title ' $this->NT_80_spec K (80%)'\r\n");
                break;

            default:
                fwrite($f, "plot  '$this->avg_datafile' using 1:2 with linespoints lt 1 lw 1 title 'Pol0sb1',");
                fwrite($f, "'$this->avg_datafile' using 1:3 with linespoints lt 2 lw 1 title 'Pol0sb2',");
                fwrite($f, "'$this->avg_datafile' using 1:4 with linespoints lt 3 lw 1 title 'Pol1sb1',");
                fwrite($f, "'$this->avg_datafile' using 1:5 with linespoints lt 4 lw 1 title 'Pol1sb2',");
                fwrite($f, "'$this->avg_datafile' using 1:7 with lines lt 1 lw 3 title ' $this->NT_allRF_spec K (100%)',");
                fwrite($f, "'$this->avg_datafile' using 1:6 with lines lt -1 lw 3 title ' $this->NT_80_spec K (80%)'\r\n");
                break;
        }
        fclose($f);

        // get the main data files write directory from config_main:
        require(site_get_config_main());

        //Call gnuplot
        system("$GNUPLOT $commandfile");

        // store image location
        $image_url = $main_url_directory . "noisetemp/$imagename";
        $this->NT_SubHeader->SetValue('ploturl4',$image_url);
    }

    private function DrawPlotTrVsRF() {
        // get the main data files write directory from config_main:
        require(site_get_config_main());

        // start loop for TSSB vs RF freq plots
        for ($cnt = 0; $cnt < 4; $cnt++) {
            $plot_title = "Receiver Noise Temperature, $this->lowerIFLimit-$this->upperIFLimit GHz IF, FE SN" . $this->FrontEnd->GetValue('SN').
            ", CCA" . $this->GetValue('Band').
            "-$this->CCA_SN WCA" . $this->GetValue('Band'). "-$this->WCA_SN,";
            $imagename = "Tssb_vs_RF_Freq_NoiseTemp Plot$cnt " . date('Y_m_d_H_i_s') . ".png";
            $imagepath = $this->plotDir . $imagename;
            $this->NT_Logger->WriteLogFile("image path: $imagepath");
            $image_url = $main_url_directory . "noisetemp/$imagename";

            // Create GNU plot command file
            $commandfile = $this->plotDir . "plotcommands_$cnt.txt";
            $f = fopen($commandfile,'w');
            $this->NT_Logger->WriteLogFile("command file: $commandfile");
            fwrite($f, "set terminal png size 900,600 crop\r\n");
            fwrite($f, "set output '$imagepath'\r\n");
            fwrite($f, "set xlabel 'RF (GHz)'\r\n");
            if ($this->GetValue('Band') == 1) {
            	fwrite($f, "set ylabel 'Tssb (K)'\r\n");
            } elseif ($this->foundIRData) {
                fwrite($f, "set ylabel 'Tssb corrected (K)'\r\n");
            } else {
                fwrite($f, "set ylabel 'T_Rec uncorrected (K)'\r\n");
                if ( $this->GetValue('Band') != 9 && $this->GetValue('Band') != 10) {
                    fwrite($f,  " ".'set label "****** UNCORRECTED DATA ****** UNCORRECTED DATA ****** UNCORRECTED DATA ******" at screen .08, .16'."\r\n");
                    fwrite($f,  " ".'set label "****** UNCORRECTED DATA ****** UNCORRECTED DATA ****** UNCORRECTED DATA ******" at screen .08, .9'."\r\n");
                }
            }
            fwrite($f, "set y2label 'Difference from Spec(%)'\r\n");
            fwrite($f, "set y2tics\r\n");
            fwrite($f, "set y2range [0:120]\r\n");
            fwrite($f, "set key outside\r\n");
            fwrite($f, "set bmargin 6\r\n");
            fwrite($f, $this->plot_label_1);
            fwrite($f, $this->plot_label_2);
            fwrite($f, "set yrange [0:$this->y_lim]\r\n");

            switch ($cnt) {
                case 0;
                    if ($this->GetValue('Band') == 9 || $this->GetValue('Band') == 10) {
                        $SB = "";
                    } else {
                        $SB = "USB";
                    }
                    $plot_title = "$plot_title Pol 0 $SB";
                    fwrite($f, "set title '$plot_title'\r\n");
                    fwrite($f, "plot  '$this->rf_datafile' using 1:3 with lines lt 1 lw 3 title 'FEIC Meas Pol0 $SB'");
                    // if statement to plot pol graphs
                    if ($this->foundCCAData) {
                        fwrite($f, ",");
                        fwrite($f, "'$this->datafile_cart_0_1' using 1:2 with lines lt 3 title 'Cart Group Meas Pol0 $SB',");
                        fwrite($f, "'$this->datafile_diff_0_1' using 1:2 with points lt -1 axes x1y2 title 'Diff relative to Spec'\r\n");
                    } else {
                        fwrite($f, "\r\n");
                    }
                    fclose($f);

                    //Call gnuplot
                    system("$GNUPLOT $commandfile");
                    $this->NT_SubHeader->SetValue('ploturl1',$image_url);
                    break;

                case 1;
                    if ($this->GetValue('Band') != 9 && $this->GetValue('Band') != 10) {
                        $plot_title = "$plot_title Pol 0 LSB";
                        fwrite($f, "set title '$plot_title'\r\n");
                        fwrite($f, "plot  '$this->rf_datafile' using 2:4 with lines lt 1 lw 3 title 'FEIC Meas Pol0 LSB'");
                        // if statement to plot pol graphs
                        if ($this->foundCCAData) {
                            fwrite($f, ",");
                            fwrite($f, "'$this->datafile_cart_0_2' using 1:2 with lines lt 3 title 'Cart Group Meas Pol0 LSB',");
                            fwrite($f, "'$this->datafile_diff_0_2' using 1:2 with points lt -1 axes x1y2 title 'Diff relative to Spec'\r\n");
                        } else {
                            fwrite($f, "\r\n");
                        }
                        fclose($f);

                        //Call gnuplot
                        system("$GNUPLOT $commandfile");
                        $this->NT_SubHeader->SetValue('ploturl2',$image_url);
                    }
                    break;

                case 2;
                    if ($this->GetValue('Band') == 9 || $this->GetValue('Band') == 10) {
                        $SB = "";
                    } else {
                        $SB = "USB";
                    }
                    $plot_title = "$plot_title Pol 1 $SB";
                    fwrite($f, "set title '$plot_title'\r\n");
                    fwrite($f, "plot  '$this->rf_datafile' using 1:5 with lines lt 1 lw 3 title 'FEIC Meas Pol1 $SB'");
                    // if statement to plot pol graphs
                    if ($this->foundCCAData) {
                        fwrite($f, ",");
                        fwrite($f, "'$this->datafile_cart_1_1' using 1:2 with lines lt 3 title 'Cart Group Meas Pol1 $SB',");
                        fwrite($f, "'$this->datafile_diff_1_1' using 1:2 with points lt -1 axes x1y2 title 'Diff relative to Spec'\r\n");
                    } else {
                        fwrite($f, "\r\n");
                    }
                    fclose($f);

                    //Call gnuplot
                    system("$GNUPLOT $commandfile");
                    if ($this->GetValue('Band') == 9 || $this->GetValue('Band') == 10) {
                        $this->NT_SubHeader->SetValue('ploturl2',$image_url);
                    } else {
                        $this->NT_SubHeader->SetValue('ploturl5',$image_url);
                    }
                    break;

                case 3;
                    if ($this->GetValue('Band') != 9 && $this->GetValue('Band') != 10) {
                        $plot_title = "$plot_title Pol 1 LSB";
                        fwrite($f, "set title '$plot_title'\r\n");
                        fwrite($f, "plot  '$this->rf_datafile' using 2:6 with lines lt 1 lw 3 title 'FEIC Meas Pol1 LSB'");
                        // if statement to plot pol graphs
                        if ($this->foundCCAData) {
                            fwrite($f, ",");
                            fwrite($f, "'$this->datafile_cart_1_2' using 1:2 with lines lt 3 title 'Cart Group Meas Pol1 LSB',");
                            fwrite($f, "'$this->datafile_diff_1_2' using 1:2 with points lt -1 axes x1y2 title 'Diff relative to Spec'\r\n");
                        } else {
                            fwrite($f, "\r\n");
                        }
                        fclose($f);

                        //Call gnuplot
                        system("$GNUPLOT $commandfile");
                        $this->NT_SubHeader->SetValue('ploturl6',$image_url);

                    }
                    break;
            }
        }
    }
}
?>
