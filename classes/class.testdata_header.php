<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_classes . '/class.cryostat.php');
require_once($site_classes . '/class.dataplotter.php');
require_once($site_classes . '/class.wca.php');
require_once($site_classes . '/class.spec_functions.php');

require_once($site_dbConnect);

class TestData_header extends GenericTable {
    var $TestDataType;          // text description
    var $TestDataTableName;
    var $DataStatus;
    var $FrontEnd;
    var $Component;
    var $fe_keyId;
//     var $NoiseFloorHeader;   //TODO: Removed this for 1.0.12.  Doesn't belong in this class!
    var $TestDataHeader;        //TODO: Being set after Initialize by calling code!
    var $swversion;
    var $fc; //facility
    var $subheader; //Generic table object, for a record in a subheader table

    public function Initialize_TestData_header($in_keyId, $in_fc, $in_feconfig = '') {
        $this->swversion = "1.1.0";
        // 1.1.0 added Export()
        // 1.0.13 merged CCA_IFSpec
        // 1.0.12 remove $NoiseFloorHeader and cleanup testdata.php
        // 1.0.11 delete dead code.
        // 1.0.10 fix LO Lock Test: Show Raw Data displaying results from multiple TDH.
        // 1.0.9 fixed instantiating DataPlotter in DrawPlot().
        // 1.0.8 minor fix to require(class.wca.php)
        // 1.0.7 MM fixes so that we can run with E_NOTIFY
        // version 1.0.6 Moved code from here which instantiates classes derived from this one!   (to testdata.php, pending verification.)
        // version 1.0.5 MM code formatting fixes, fix Display_RawTestData() for LO Lock test.

        $this->fc = $in_fc;
        parent::Initialize("TestData_header",$in_keyId,"keyId",$in_fc,'keyFacility');
        $this->TestDataHeader = $in_keyId;

        $q = "SELECT Description, TestData_TableName FROM TestData_Types
              WHERE keyId = " . $this->GetValue('fkTestData_Type') . ";";
        $r = @mysql_query($q,$this->dbconnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);
        $this->TestDataType      = @mysql_result($r,0);
        $this->TestDataTableName = @mysql_result($r,0,1);

        $q = "SELECT Description FROM DataStatus
              WHERE keyId = " . $this->GetValue('fkDataStatus') . ";";
        $r = @mysql_query($q,$this->dbconnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);
        $this->DataStatus        = @mysql_result($r,0);

        $this->Component = new FEComponent();

        if ($this->GetValue('fkFE_Components') != '') {
            $this->Component->Initialize_FEComponent($this->GetValue('fkFE_Components'), $this->GetValue('keyFacility'));
            $q = "SELECT Description FROM ComponentTypes WHERE keyId = " . $this->Component->GetValue('fkFE_ComponentType') . ";";
            $r = @mysql_query($q, $this->dbconnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);
            $this->Component->ComponentType = @mysql_result($r,0);
        }

        if (($this->GetValue('fkFE_Config') != "") && ($this->GetValue('fkFE_Config') != "0")) {
            $qfe = "SELECT fkFront_Ends from FE_Config
                    WHERE keyFEConfig = " . $this->GetValue('fkFE_Config') . ";";
            $rfe = @mysql_query($qfe,$this->dbconnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);
            $feid = @mysql_result($rfe,0);
            $this->fe_keyId = $feid;
            $this->FrontEnd = new FrontEnd();
            $this->FrontEnd->Initialize_FrontEnd($feid, $this->GetValue('keyFacility'), FrontEnd::INIT_SLN | FrontEnd::INIT_CONFIGS);
            $this->Component->Initialize("Front_Ends", $feid, "keyFrontEnds", $this->GetValue('keyFacility'), 'keyFacility');
            $this->Component->ComponentType = "Front End";
        }
    }

    public function RequestValues_TDH() {
        if (isset($_REQUEST['fkDataStatus'])) {
            $this->SetValue('fkDataStatus', $_REQUEST['fkDataStatus']);
            $this->Update();
        }

        if (isset($_REQUEST['Notes'])) {
            $this->SetValue('Notes',$_REQUEST['Notes']);
            parent::Update();
        }
    }

    public function Display_Data_Cryostat($datatype) {
        //Array of TestData_header objects (TestData_header)
        //[1] = First Rate of Rise
        //[2] = Warmup
        //[3] = Cooldown
        //[4] = Final Rate of Rise
        //[5] = Rate of Rise after adding CCA

        $c = new Cryostat();
        $c->Initialize_Cryostat($this->GetValue('fkFE_Components'), $this->GetValue('keyFacility'));

        echo "<table>";

        if ($c->tdheaders[$datatype]->subheader->GetValue('pic_rateofrise') != "") {
            echo "<tr><td><img src='" . $c->tdheaders[$datatype]->subheader->GetValue('pic_rateofrise') . "'></td></tr>";
        }
        if ($c->tdheaders[$datatype]->subheader->GetValue('pic_pressure') != "") {
            echo "<tr><td><img src='" . $c->tdheaders[$datatype]->subheader->GetValue('pic_pressure') . "'></td></tr>";
        }
        if ($c->tdheaders[$datatype]->subheader->GetValue('pic_temperature') != "") {
            echo "<tr><td><img src='" . $c->tdheaders[$datatype]->subheader->GetValue('pic_temperature') . "'></td></tr>";
        }
        echo "</table>";
    }

    public function Display_TestDataMain() {

    	switch ($this->GetValue('fkTestData_Type')) {
    		case 27:
    		    //Phase Stability
    			$this->Display_DataForm();
    			echo "<br>";
    			$this->Display_PhaseStabilitySubHeader();
    			break;

    		case 7:
    			//IF Spectrum
    			break;

    		case 56:
    			//Pol Angles
    			$this->Display_DataForm();
    			echo "<br>";
    			$this->Display_Data_PolAngles();
    			break;

    		case 57:
    			//LO Lock Test
    			$this->Display_DataSetNotes();
    			echo "<br>";
    			break;

    		case 58:
    			//Noise Temperature
    			$this->Display_DataSetNotes();
    			break;

    		case 50:
    		    //Cryostat First Rate of Rise
    			$this->Display_DataForm();
    			echo "<br>";
    			$this->Display_Data_Cryostat(1);
    			break;
    		case 52:
    		    //Cryostat First Cooldown
    		    $this->Display_DataForm();
    			echo "<br>";
    			$this->Display_Data_Cryostat(3);
    			break;
    		case 53:
    		    //Cryostat First Warmup
    		    $this->Display_DataForm();
    			echo "<br>";
    			$this->Display_Data_Cryostat(2);
    			break;
    		case 54:
    		    //Cryostat Final Rate of Rise
    		    $this->Display_DataForm();
    			echo "<br>";
    			$this->Display_Data_Cryostat(4);
    			break;
    		case 25:
    		    //Cryostat Rate of Rise After adding Vacuum Equipment
    			$this->Display_DataForm();
    			echo "<br>";
    			$this->Display_Data_Cryostat(5);
    			break;
    		case 45:
    		    //WCA Amplitude Stability
    			$this->Display_DataForm();
    			echo "<br>";
    			$wca = new WCA();
    			$wca->Initialize_WCA($this->GetValue('fkFE_Components'),$this->GetValue('keyFacility'), WCA::INIT_ALL);
    			$wca->Display_AmplitudeStability();
    			break;
    		case 44:
    		    //WCA AM Noise
    			$this->Display_DataForm();
    			echo "<br>";
    			$wca = new WCA();
    			$wca->Initialize_WCA($this->GetValue('fkFE_Components'),$this->GetValue('keyFacility'), WCA::INIT_ALL);
    			$wca->Display_AMNoise();
    			break;
    		case 46:
    		    //WCA Output Power
    			$this->Display_DataForm();
    			echo "<br>";
    			$wca = new WCA();
    			$wca->Initialize_WCA($this->GetValue('fkFE_Components'),$this->GetValue('keyFacility'), WCA::INIT_ALL);
    			$wca->Display_OutputPower();
    			break;
    		case 47:
    		    //WCA Phase Jitter
    			$this->Display_DataForm();
    			echo "<br>";
    			$wca = new WCA();
    			$wca->Initialize_WCA($this->GetValue('fkFE_Components'),$this->GetValue('keyFacility'), WCA::INIT_ALL);
    			$wca->Display_PhaseNoise();
    			break;
    		case 48:
    		    //WCA Phase Noise
    			$this->Display_DataForm();
    			echo "<br>";
    			$wca = new WCA();
    			$wca->Initialize_WCA($this->GetValue('fkFE_Components'),$this->GetValue('keyFacility'), WCA::INIT_ALL);
    			$wca->Display_PhaseNoise();
    			break;
    		default:
    			$this->Display_DataForm();
    			break;
    	}
    }


    public function Display_DataForm() {
        echo "<div style='width:300px'>";
        echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
        echo "<table id = 'table1'>";
        echo "<tr><th>Notes</th></tr>";
        echo "<tr><td><textarea rows='6' cols='90' name = 'Notes'>" . stripcslashes($this->GetValue('Notes')) . "</textarea>";
        echo "<input type='hidden' name='fc' value='".$this->GetValue('keyFacility')."'>";
        echo "<input type='hidden' name='keyheader' value='$this->keyId'>";
        echo "<br><input type='submit' name = 'submitted' value='SAVE'></td></tr>";
        echo "</form></table></div>";
    }

    public function Display_DataSetNotes() {
        //Display information for all TestData_header records
        if ( $this->GetValue('DataSetGroup') !=0 ) {

            echo "<br><br>
            <div style='width:900px'>
            <table id = 'table1' border = '1'>";

            echo "<tr class = 'alt'><th colspan='3'>".$this->TestDataType ." data sets for TestData_header.DataSetGroup ".$this->GetValue('DataSetGroup')."</th></tr>";
            echo "<tr>
                    <th width='60px'>Key</th>
                    <th width='140px'>Timestamp</th>
                    <th>Notes</th></tr>";

            $qkeys = "SELECT keyId FROM `TestData_header`
                LEFT JOIN `FE_Config` ON `FE_Config`.keyFEConfig = `TestData_header`.fkFE_Config
                WHERE `TestData_header`.Band = " . $this->GetValue('Band') .
                " AND `TestData_header`.DataSetGroup = " . $this->GetValue('DataSetGroup') .
                " AND `FE_Config`.fkFront_Ends = $this->fe_keyId
                AND `TestData_header`.`fkTestData_Type` = ". $this->GetValue('fkTestData_Type');

            $rkeys = @mysql_query($qkeys,$this->dbconnection);

            $i=0;
            while ($rowkeys = @mysql_fetch_array($rkeys)) {
            //for ($i=0;$i<count($this->TDHkeys);$i++) {

                if ($i % 2 == 0) {
                    $trclass = "alt";
                }
                if ($i % 2 != 0) {
                   $trclass = "";
                }
                $t = new TestData_header();
                $t->Initialize_TestData_header($rowkeys['keyId'], $this->GetValue('keyFacility'), 0);
                echo "<tr class = $trclass>";
                echo "<td>" . $t->keyId . "</td>";
                echo "<td>" . $t->GetValue('TS') . "</td>";
                echo "<td style='text-align:left !important;'>" . $t->GetValue('Notes') . "</td>";
                echo "</tr>";
                $i+=1;
            }

            echo "</table></div>";
        } else {
            $this->Display_DataForm();
        }
    }

    public function Display_RawTestData() {
        $fkHeader = $this->keyId;
        $qgetdata = "SELECT * FROM $this->TestDataTableName WHERE
        fkHeader = $fkHeader AND fkFacility = ".$this->GetValue('keyFacility').";";

        $preCols = "";

        switch($this->Component->GetValue('fkFE_ComponentType')) {
            case 6:
                //Cryostat
                $q = "SELECT keyId FROM TEST_Cryostat_data_SubHeader
                      WHERE fkHeader = $this->keyId;";
                $r = @mysql_query($q,$this->dbconnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);
                $fkHeader = @mysql_result($r,0,0);
                $qgetdata = "SELECT * FROM $this->TestDataTableName WHERE
                fkSubHeader = $fkHeader AND fkFacility = ".$this->GetValue('keyFacility').";";
                break;
        }

        switch($this->GetValue('fkTestData_Type')) {
            case 57:
                //LO Lock test
                $qgetdata = "SELECT DT.*
                            FROM TEST_LOLockTest as DT, TEST_LOLockTest_SubHeader as SH, TestData_header as TDH
                            WHERE DT.fkHeader = SH.keyId AND DT.fkFacility = SH.keyFacility
                            AND SH.fkHeader = TDH.keyId AND SH.keyFacility = TDH.keyFacility"
                          . " AND TDH.keyId = " . $fkHeader
                          . " AND TDH.Band = " . $this->GetValue('Band')
                          . " AND TDH.DataSetGroup = " . $this->GetValue('DataSetGroup')
                          . " AND TDH.fkFE_Config = " . $this->GetValue('fkFE_Config')
                          . " AND DT.IsIncluded = 1
                            ORDER BY DT.LOFreq ASC;";

                $this->TestDataTableName = 'TEST_LOLockTest';
                break;

            case 58:
                //Noise Temperature
                $q = "SELECT keyId FROM Noise_Temp_SubHeader
                      WHERE fkHeader = $this->keyId
                      AND keyFacility = " . $this->GetValue('keyFacility');
                $r = @mysql_query($q,$this->dbconnection);
                $subid = @mysql_result($r,0,0);
                $qgetdata = "SELECT * FROM Noise_Temp WHERE
                fkSub_Header = $subid AND keyFacility = ".$this->GetValue('keyFacility').";";
                $this->TestDataTableName = 'Noise_Temp';
                break;

            case 59:
                //fine LO Sweep
                $qgetdata = "SELECT HT.Pol, DT.*
                    FROM TEST_FineLOSweep AS DT, TEST_FineLOSweep_SubHeader AS HT
                    WHERE HT.fkHeader = $this->keyId
                    AND DT.fkSubHeader = HT.keyId;";

                $preCols = "Pol";
                $this->TestDataTableName = 'TEST_FineLOSweep';
                break;
        }

        $q = "SHOW COLUMNS FROM $this->TestDataTableName;";
        $r = @mysql_query($q,$this->dbconnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);

        echo        "<table id = 'table1'>";

        if ($preCols) {
            echo "
                <th>$preCols</th>";
        }

        while ($row = @mysql_fetch_array($r)) {
            echo "
                <th>$row[0]</th>";
        }


        $r = @mysql_query ($qgetdata, $this->dbconnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);
        while($row = mysql_fetch_array($r)) {
            echo "<tr>";
            for ($i=0;$i<count($row)/2;$i++) {
                echo "<td>$row[$i]</td>";
            }
            echo"</tr>";
        }

        echo "</table>";

        //echo "</div>";
    }

    public function AutoDrawThis() {
    	// return true if this plot type should be automatically drawn on page load.
    	switch($this->GetValue('fkTestData_Type')) {
    		case 1:		// health check tabular data
    		case 2:
    		case 3:
    		case 4:
    		case 5:
    		case 6:
    		case 8:
    		case 9:
    		case 10:
    		case 12:
    		case 13:
    		case 14:
    		case 15:

    		case 57: 	// LO lock test
    		case 58: 	// Noise temperature
    		case 59:	// fine LO sweep

    		case 44:	// WCA cartridge PAI plots
    		case 45:
    		case 46:
    		case 47:
    		case 48:

    		case 42:	//CCA cartridge PAI plots
    			return false;

    		default:
    			return true;
    	}
    }

    public function Export($outputDir) {
        switch ($this->GetValue('fkTestData_Type')) {
            case 1:     //CCA LNA healthcheck
            case 2:     //CCA TempSensors healthcheck
            case 3:     //CCA SIS healthcheck
            case 4:     //Cryostat temperatures
            case 5:     //FLOOG Distributor healthcheck
            case 6:     //IF total power healthcheck
            case 8:     //LPR healthcheck
            case 9:     //Photomixer healthcheck
            case 10:    //IF Switch temperatures
            case 12:    //WCA AMC healthcheck
            case 13:    //WCA PA healthcheck
            case 14:    //WCA Misc healthcheck
            case 15:    //Y-factor healthcheck
            case 24:    //CPDS healthcheck
                break;

            case 25:    //Cryostat ROR with vacuum equipment
            case 50:    //Cryostat first ROR
            case 51:    //Cryostat ROR after adding CCA
            case 52:    //Cryostat first cooldown
            case 53:    //Cryostat first warmup
            case 55:    //Cryostat final ROR
                break;

            case 7:     //IF Spectrum
                break;

            case 27:    //Phase Stability
                break;

            case 29:    //Amplitude Workmanship
                break;

            case 30:    //Phase Workmanship
            case 31:    //Repeatability
            case 32:    //Amplitude Stability
                break;

            // 33-43 CCA PAI data
            // 44-49 WCA PAI data

            case 55:    // Beam Patterns
                break;

            case 56:    //Pol Angles
                break;

            case 57:    //LO Lock Test
                break;

            case 58:    //Noise Temperature
                break;

            case 59:    //Fine LO Sweep
                break;

            default:
                break;
        }
    }

    public function DrawPlot() {

        $plt = new DataPlotter();
        $plt->Initialize_DataPlotter($this->keyId,$this->dbconnection,$this->GetValue('keyFacility'));

        //Determine which type of plot to draw...
        switch ($this->GetValue('fkTestData_Type')) {
        case "43":
            $plt->Plot_CCA_AmplitudeStability();
            break;

        case "29":
            $plt->Plot_WorkmanshipAmplitude(false);
            $plt->Plot_WorkAmpTemperatures(true);
            break;
        case "30":
            $plt->Plot_WorkmanshipPhase();
            break;
        case "31":
            //Plot_Repeatability(); obsolete
            break;
        case "33":
            $plt->Plot_CCA_PhaseDrift();
            break;
        case "36":
            $plt->Plot_CCA_InBandPower();
            break;
        case "37":
            $plt->Plot_CCA_TotalPower();
            break;
        case "34":
            $plt->Plot_CCA_GainCompression();
            break;
        case "41":
            $plt->Plot_CCA_IFSpectrum();
            break;
        case "42":
            // TODO: $plt->Plot_CCA_NoiseTemp is not implemented!
            break;
        case "35":
            $plt->Plot_CCA_PolAccuracy();
            break;
        case "39":
            $plt->Plot_CCA_IVCurve();
            break;
        case "7":
            break;
        case "56":
            $plt->Plot_PolAngles();
            break;
        case "57":
            $plt->Plot_LOLockTest();
            break;

        case "44":
            //AM Noise
            $this->Plot_WCA(45);
            break;
        case "45":
            //Amplitude Stability
            $this->Plot_WCA(45);
            break;
        case "46":
            //Output Power
            $this->Plot_WCA(46);
            break;
        case "47":
            //Phase Jitter
            $this->Plot_WCA(47);
            break;
        case "48":
            //Phase Noise
            $this->Plot_WCA(48);
            break;

        case "58":
            //FEIC Noise Temperature
            $nztemp = new NoiseTemperature();
            $nztemp->Initialize_NoiseTemperature($this->keyId,$this->GetValue('keyFacility'));
            $nztemp->DrawPlot();
            unset($nztemp);
            break;

        case "59":
            //Fine LO Sweep
            $finelosweep = new FineLOSweep();
            $finelosweep->Initialize_FineLOSweep($this->keyId,$this->GetValue('keyFacility'));
            $finelosweep->DrawPlot();
            unset($finelosweep);
            break;

        case "38":
            //CCA Image Rejection (Sideband Ratio)
            $ccair = new cca_image_rejection();
            $ccair->Initialize_cca_image_rejection($this->keyId,$this->GetValue('keyFacility'));
            $ccair->DrawPlot();
            unset($ccair);
            break;
        }
    }

    public function Plot_WCA($datatype) {
        $wca = new WCA();
        $wca->Initialize_WCA($this->GetValue('fkFE_Components'), $this->GetValue('keyFacility'), WCA::INIT_ALL);

        switch($datatype) {
            case 44:
                $wca->Plot_AMNoise();
                break;
            case 45:
                $wca->Plot_AmplitudeStability();
                break;
            case 46:
                $wca->Plot_OutputPower();
                break;
            case 47:
                $wca->Plot_AMNoise();
                break;
            case 48:
                $wca->Plot_PhaseJitter();
                break;
            case 49:
                $wca->Plot_PhaseNoise();
                break;

        }

        unset($wca);
    }

    public function Display_PhaseStabilitySubHeader() {
        $sh = new GenericTable();
        $sh->Initialize('TEST_PhaseStability_SubHeader',$this->keyId,'fkHeader',$this->GetValue('keyFacility'),'fkFacility');
    }

    public function Display_Data_PolAngles() {
        $pa = new GenericTable();
        $pa->Initialize("SourceRotationAngles",$this->GetValue('Band'),"band");

        $nom_0_m90 = $pa->GetValue('pol0_copol') - 90;
        $nom_0_p90 = $pa->GetValue('pol0_copol') + 90;
        $nom_1_m90 = $pa->GetValue('pol1_copol') - 90;
        $nom_1_p90 = $pa->GetValue('pol1_copol') + 90;

        $pol0_min1 = 999;
        $pol0_min2 = 999;
        $pol1_min1 = 999;
        $pol1_min2 = 999;
        $min0_1_found = 0;
        $min0_2_found = 0;
        $min1_1_found = 0;
        $min1_2_found = 0;
        $angle_min0_1 = 0;
        $angle_min0_2 = 0;
        $angle_min1_1 = 0;
        $angle_min1_2 = 0;

        //Pol 0, first minimum
        $qpa = "SELECT MIN(amp_pol0)
                FROM TEST_PolAngles
                WHERE fkFacility = ".$this->GetValue('keyFacility')."
                AND
                fkHeader = $this->keyId
                and angle < ($nom_0_m90 + 10)
                and angle > ($nom_0_m90 - 10);";
        $rpa = @mysql_query($qpa,$this->dbconnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);

        $pol0_min1 = @mysql_result($rpa,0);

        $qpa = "SELECT angle
                FROM TEST_PolAngles
                WHERE fkHeader = $this->keyId
                and fkFacility = ".$this->GetValue('keyFacility')."
                and ROUND(amp_pol0,5) = " . round($pol0_min1, 5) . "
                and angle < ($nom_0_m90 + 10)
                and angle > ($nom_0_m90 - 10);";
        $rpa = @mysql_query($qpa,$this->dbconnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);

        $angle_min0_1 = @mysql_result($rpa,0);

        //Pol 0, 2nd minimum
        $qpa = "SELECT MIN(amp_pol0)
                FROM TEST_PolAngles
                WHERE fkHeader = $this->keyId
                AND fkFacility = ".$this->GetValue('keyFacility')."
                and angle < ($nom_0_p90 + 10)
                and angle > ($nom_0_p90 - 10);";
        $rpa = @mysql_query($qpa,$this->dbconnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);

        $pol0_min2 = @mysql_result($rpa,0);

        $qpa = "SELECT angle
                FROM TEST_PolAngles
                WHERE fkHeader = $this->keyId
                AND fkFacility = ".$this->GetValue('keyFacility')."
                and ROUND(amp_pol0,5) = " . round($pol0_min2, 5) . "
                and angle < ($nom_0_p90 + 10)
                and angle > ($nom_0_p90 - 10);";
        $rpa = @mysql_query($qpa,$this->dbconnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);

        $angle_min0_2 = @mysql_result($rpa,0);


        //Pol 1, first minimum
        $qpa = "SELECT MIN(amp_pol1)
                FROM TEST_PolAngles
                WHERE fkHeader = $this->keyId
                AND fkFacility = ".$this->GetValue('keyFacility')."
                and angle < ($nom_1_m90 + 10)
                and angle > ($nom_1_m90 - 10);";
        $rpa = @mysql_query($qpa,$this->dbconnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);

        $pol1_min1 = @mysql_result($rpa,0);

        $qpa = "SELECT angle
                FROM TEST_PolAngles
                WHERE fkHeader = $this->keyId
                AND fkFacility = ".$this->GetValue('keyFacility')."
                and ROUND(amp_pol1,5) = " . round($pol1_min1, 5) . "
                and angle < ($nom_1_m90 + 10)
                and angle > ($nom_1_m90 - 10);";
        $rpa = @mysql_query($qpa,$this->dbconnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);

        $angle_min1_1 = @mysql_result($rpa,0);

        //Pol 1, 2nd minimum
        $qpa = "SELECT MIN(amp_pol1)
                FROM TEST_PolAngles
                WHERE fkHeader = $this->keyId
                AND fkFacility = ".$this->GetValue('keyFacility')."
                and angle < ($nom_1_p90 + 10)
                and angle > ($nom_1_p90 - 10);";
        $rpa = @mysql_query($qpa,$this->dbconnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);

        $pol1_min2 = @mysql_result($rpa,0);

        $qpa = "SELECT angle
                FROM TEST_PolAngles
                WHERE fkHeader = $this->keyId
                AND fkFacility = ".$this->GetValue('keyFacility')."
                and ROUND(amp_pol1,5) = " . round($pol1_min2, 5) . "
                and angle < ($nom_1_p90 + 10)
                and angle > ($nom_1_p90 - 10);";
        $rpa = @mysql_query($qpa,$this->dbconnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);

        $angle_min1_2 = @mysql_result($rpa,0);

        echo "<div style = 'width:500px'><table id = 'table1'>";

        echo "<th colspan = 5>Band " . $this->GetValue('Band') . " Pol Angles At Minima</th>";
        echo "<tr><th>Pol</th>";
        echo "<th>Nominal Angle</th>";
        echo "<th>Actual Angle</th>";
        echo "<th>Actual - Nominal</th>";
        echo "</tr>";

        if (abs($nom_0_m90) < 181) {
            $diff = round($angle_min0_1 - $nom_0_m90,2);
            if($angle_min0_1 != '') {
            echo "<tr><td><b>0</td><td><b>$nom_0_m90</b></td>";
            echo "<td><b>$angle_min0_1</b></td>";

            if (abs($diff) > 2) {
                echo "<td bgcolor = '#ffccff'><b><font color='#ff0000'>$diff<font></b></td>";
            }
            else{
                echo "<td><b>$diff</b></td>";
            }
            echo "</tr>";
            }
        }

        if (abs($nom_1_m90) < 181) {
            $diff1 = round($angle_min1_1 - $nom_1_m90,2);
            if ($angle_min1_1 != '') {
                echo "<tr><td><b>1</td><td><b>$nom_1_m90</b></td>";
                echo "<td><b>$angle_min1_1</b></td>";

                if (abs($diff1 - $diff) > 2) {
                    echo "<td bgcolor = '#ffccff'><b><font color='#ff0000'>$diff1<font></b></td>";
                }
                else{
                    echo "<td><b>$diff1</b></td>";
                }
                echo "</tr>";
            }
        }

        if (abs($nom_0_p90) < 181) {
            $diff0 = round($angle_min0_2 - $nom_0_p90 ,2);
            if ($angle_min0_2 != '') {
            echo "<tr><td><b>0</td><td><b>$nom_0_p90</b></td>";
            echo "<td><b>$angle_min0_2</b></td>";

            if (abs($diff0) > 2) {
                echo "<td bgcolor = '#ffccff'><b><font color='#ff0000'>$diff0<font></b></td>";
            }
            else{
                echo "<td><b>$diff0</b></td>";
            }
            echo "</tr>";
            }
        }
        if (abs($nom_1_p90) < 181) {
            $diff1 = round($angle_min1_2 - $nom_1_p90,2);
            if ($angle_min1_2 != '') {
                echo "<tr><td><b>1</td><td><b>$nom_1_p90</b></td>";
                echo "<td><b>$angle_min1_2</b></td>";

                if (abs($diff1 - $diff0) > 2) {
                    echo "<td bgcolor = '#ffccff'><b><font color='#ff0000'>$diff1<font></b></td>";
                }
                else{
                    echo "<td><b>$diff1</b></td>";
                }
                echo "</tr>";
            }
        }

        echo "</table></div><br>";

    }
}
?>
