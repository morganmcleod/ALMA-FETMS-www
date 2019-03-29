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
    var $TestDataHeader;
    var $swversion;
    var $fc; //facility
    var $subheader; //Generic table object, for a record in a subheader table.

    public function Initialize_TestData_header($in_keyId, $in_fc, $in_feconfig = '') {
        $this->swversion = "1.2.0";
        // 1.2.0 refactored to move into the class stuff that was on the calling page
        //       added popupMoveToOtherFE() buttons
        // 1.1.2 added GetFetmsDescription()
        // 1.1.1 display FETMS_Description above Notes
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
            $this->Component->Initialize_FEComponent($this->GetValue('fkFE_Components'), $this->fc);
            $q = "SELECT Description FROM ComponentTypes WHERE keyId = " . $this->Component->GetValue('fkFE_ComponentType') . ";";
            $r = @mysql_query($q, $this->dbconnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);
            $this->Component->ComponentType = @mysql_result($r,0);
        }

        if (($this->GetValue('fkFE_Config') != "") && ($this->GetValue('fkFE_Config') != "0")) {
            $qfe = "SELECT fkFront_Ends from FE_Config
                    WHERE keyFEConfig = " . $this->GetValue('fkFE_Config') . ";";
            $rfe = @mysql_query($qfe,$this->dbconnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);
            $feConfigId = @mysql_result($rfe,0);
            $this->fe_keyId = $feConfigId;
            $this->FrontEnd = new FrontEnd();
            $this->FrontEnd->Initialize_FrontEnd($feConfigId, $this->fc, FrontEnd::INIT_SLN | FrontEnd::INIT_CONFIGS);
            $this->Component->Initialize("Front_Ends", $feConfigId, "keyFrontEnds", $this->fc, 'keyFacility');
            $this->Component->ComponentType = "Front End";
        }
    }

    public function RequestValues_TDH() {
        // Update the TDH record with new values for fkDataStatus or Notes
        if (isset($_REQUEST['fkDataStatus'])) {
            $this->SetValue('fkDataStatus', $_REQUEST['fkDataStatus']);
            $this->Update();
        }
        if (isset($_REQUEST['Notes'])) {
            $this->SetValue('Notes',$_REQUEST['Notes']);
            parent::Update();
        }
    }

    public function GetFetmsDescription($textBefore = "") {
        $fetms = trim($this->GetValue('FETMS_Description'));
        if (!$fetms)
            $fetms = "UNKNOWN";
        $fetms = $textBefore . $fetms;
        $fetms = str_replace("'", "", $fetms);
        return $fetms;
    }

    public function Display_TestDataButtons() {
        $showrawurl = "testdata.php?showrawdata=1&keyheader=$this->keyId&fc=$this->fc";
        $drawurl = "testdata.php?drawplot=1&keyheader=$this->keyId&fc=$this->fc";
        $exportcsvurl = "export_to_csv.php?keyheader=$this->keyId&fc=$this->fc";
        $fesn = $this->FrontEnd->GetValue('SN');

        require(site_get_config_main());  // for $rootdir_url
        $feConfigId = $this->GetValue('fkFE_Config');
        $popupScript = "javascript:popupMoveToOtherFE($fesn, \"$rootdir_url\", $this->keyId, $feConfigId);";

        echo "<table>";
        switch ($this->GetValue('fkTestData_Type')) {
            case '57':
            case '58':
                //LO Lock test or noise temperature
                $drawurl = "testdata.php?keyheader=$this->keyId&drawplot=1&fc=$this->fc";
                $datasetsurl = "testdata.php?keyheader=$this->keyId&sd=1&fc=$this->fc";
                echo "
                    <tr><td>
                        <a style='width:100px' href='$showrawurl' class='button blue2 biground'>
                        <span style='width:90px'>Show Raw Data</span></a>
                    </td></tr>
                    <tr><td>
                        <a style='width:100px' href='$drawurl' class='button blue2 biground'>
                        <span style='width:90px'>Generate Plots</span></a>
                    </td></tr>
                    <tr><td>
                        <a style='width:100px' href='$exportcsvurl' class='button blue2 biground'>
                        <span style='width:90px'>Export CSV</span></a>
                    </td></tr>";

                if (isset($this->FrontEnd)) {
                    $gridurl = "../datasets/datasets.php?id=$this->keyId&fc=$this->fc";
                    $gridurl .= "&fe=". $this->FrontEnd->keyId . "&b=". $this->GetValue('Band') . "&d=".$this->GetValue('fkTestData_Type');
                    echo "
                        <tr><td>
                            <a style='width:100px' href='$popupScript' class='button blue2 biground'>
                            <span style='width:90px'>Move to\nOther FE</span></a>
                        </td></tr>
                        <tr><td>
                            <a style='width:100px' href='$gridurl' class='button blue2 biground'>
                            <span style='width:90px'>Edit Data Sets</span></a>
                        </td></tr>";
                }
                break;

            case '28':
                //cryo pas
                echo "
                    <tr><td>
                        <a style='width:100px' href='$showrawurl' class='button blue2 biground'>
                        <span style='width:90px'>Show Raw Data</span></a>
                    </tr></td>
                    <tr><td>
                        <a style='width:100px' href='$exportcsvurl' class='button blue2 biground'>
                        <span style='width:90px'>Export CSV</span></a>
                    </tr></td>";
                break;

            case '52':
                //cryo first cooldown
                echo "
                    <tr><td>
                        <a style='width:100px' href='$showrawurl' class='button blue2 biground'>
                        <span style='width:90px'>Show Raw Data</span></a>
                    </tr></td>
                    <tr><td>
                        <a style='width:100px' href='$exportcsvurl' class='button blue2 biground'>
                        <span style='width:90px'>Export CSV</span></a>
                    </tr></td>";
                break;

            case '53':
                //cryo first warmup
                echo "
                    <tr><td>
                        <a style='width:100px' href='$showrawurl' class='button blue2 biground'>
                        <span style='width:130px'>Show Raw Data</span></a>
                    </tr></td>
                    <tr><td>
                        <a style='width:100px' href='$exportcsvurl' class='button blue2 biground'>
                        <span style='width:90px'>Export CSV</span></a>
                    </tr></td>";
                break;

            default:
                echo "
                    <tr><td>
                        <a style='width:100px' href='$showrawurl' class='button blue2 biground'>
                        <span style='width:90px'>Show Raw Data</span></a>
                    </tr></td>
                    <tr><td>
                        <a style='width:100px' href='$exportcsvurl' class='button blue2 biground'>
                        <span style='width:90px'>Export CSV</span></a>
                    </tr></td>
                    <tr><td>
                        <a style='width:100px' href='$drawurl' class='button blue2 biground'>
                        <span style='width:90px'>Generate Plot</span></a>
                    </tr></td>";

                if (isset($this->FrontEnd)) {
                    echo "
                        <tr><td>
                            <a style='width:100px' href='$popupScript' class='button blue2 biground'>
                            <span style='width:90px'>Move to\nOther FE</span></a>
                        </td></tr>";
                }break;
        }
        echo "</table>";
    }

    public function Display_Data_Cryostat($datatype) {
        //Array of TestData_header objects (TestData_header)
        //[1] = First Rate of Rise
        //[2] = Warmup
        //[3] = Cooldown
        //[4] = Final Rate of Rise
        //[5] = Rate of Rise after adding CCA

        $c = new Cryostat();
        $c->Initialize_Cryostat($this->GetValue('fkFE_Components'), $this->fc);

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
        // Display the notes form and plots:
        $showPlots = false;

        switch ($this->GetValue('fkTestData_Type')) {
    		case 7:
    			//IF Spectrum not handled by this class.   See /FEConfig/ifspectrum/ifspectrumplots.php and class IFSpectrum_impl
    			break;

    		case 56:
    			//Pol Angles
    			$this->Display_DataForm();
    			echo "<br>";
    			$this->Display_Data_PolAngles();
    			$showPlots = true;
    			break;

    		case 57:
    			//LO Lock Test
    			$this->Display_DataSetNotes();
    			echo "<br>";
    			$showPlots = true;
    			break;

    		case 58:
    			//Noise Temperature
    			$this->Display_DataSetNotes();
    			$nztemp = new NoiseTemperature();
    			$nztemp->Initialize_NoiseTemperature($this->keyId, $this->fc);
    			$nztemp->DisplayPlots();
    			unset($nztemp);
    			break;

    		case 59:
    		    //Fine LO Sweep
    		    $this->Display_DataSetNotes();
    		    $finelosweep = new FineLOSweep();
    		    $finelosweep->Initialize_FineLOSweep($this->keyId, $this->fc);
    		    $finelosweep->DisplayPlots();
    		    unset($finelosweep);
    		    break;

    		case 50:
    		    //Cryostat First Rate of Rise
    			$this->Display_DataForm();
    			echo "<br>";
    			$this->Display_Data_Cryostat(1);
    			$showPlots = true;
    			break;
    		case 52:
    		    //Cryostat First Cooldown
    		    $this->Display_DataForm();
    			echo "<br>";
    			$this->Display_Data_Cryostat(3);
    			$showPlots = true;
    			break;
    		case 53:
    		    //Cryostat First Warmup
    		    $this->Display_DataForm();
    			echo "<br>";
    			$this->Display_Data_Cryostat(2);
    			$showPlots = true;
    			break;
    		case 54:
    		    //Cryostat Final Rate of Rise
    		    $this->Display_DataForm();
    			echo "<br>";
    			$this->Display_Data_Cryostat(4);
    			$showPlots = true;
    			break;
    		case 25:
    		    //Cryostat Rate of Rise After adding Vacuum Equipment
    			$this->Display_DataForm();
    			echo "<br>";
    			$this->Display_Data_Cryostat(5);
    			$showPlots = true;
    			break;
    		case 45:
    		    //WCA Amplitude Stability
    			$this->Display_DataForm();
    			echo "<br>";
    			$wca = new WCA();
    			$wca->Initialize_WCA($this->GetValue('fkFE_Components'),$this->fc, WCA::INIT_ALL);
    			$wca->Display_AmplitudeStability();
    			unset($wca);
    			break;
    		case 44:
    		    //WCA AM Noise
    			$this->Display_DataForm();
    			echo "<br>";
    			$wca = new WCA();
    			$wca->Initialize_WCA($this->GetValue('fkFE_Components'),$this->fc, WCA::INIT_ALL);
    			$wca->Display_AMNoise();
    			unset($wca);
    			break;
    		case 46:
    		    //WCA Output Power
    			$this->Display_DataForm();
    			echo "<br>";
    			$wca = new WCA();
    			$wca->Initialize_WCA($this->GetValue('fkFE_Components'),$this->fc, WCA::INIT_ALL);
    			$wca->Display_OutputPower();
    			unset($wca);
    			break;
    		case 47:
    		    //WCA Phase Jitter
    			$this->Display_DataForm();
    			echo "<br>";
    			$wca = new WCA();
    			$wca->Initialize_WCA($this->GetValue('fkFE_Components'),$this->fc, WCA::INIT_ALL);
    			$wca->Display_PhaseNoise();
    			unset($wca);
    			break;
    		case 48:
    		    //WCA Phase Noise
    			$this->Display_DataForm();
    			echo "<br>";
    			$wca = new WCA();
    			$wca->Initialize_WCA($this->GetValue('fkFE_Components'),$this->fc, WCA::INIT_ALL);
    			$wca->Display_PhaseNoise();
    			unset($wca);
    			break;

    		case 38:
    		    //CCA Image Rejection
    		    $this->Display_DataForm();
    		    $ccair = new cca_image_rejection();
    		    $ccair->Initialize_cca_image_rejection($this->keyId, $this->fc);
    		    $ccair->DisplayPlots();
    		    unset($ccair);
    		    break;

    		default:
    		    $this->Display_DataForm();
    		    $showPlots = true;
    			break;
    	}
    	if ($showPlots) {
        	$urlarray = explode(",",$this->GetValue('PlotURL'));
        	for ($i=0;$i<count($urlarray);$i++) {
        	    if ($urlarray[$i])
        	        echo "<img src='" . $urlarray[$i] . "'><br><br>";
        	}
    	}
    }

    public function Display_DataForm() {
        //Get FETMS description:
        $fetms = $this->GetFetmsDescription("Measured at: ");

        echo "<div style='width:300px'>";
        echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
        echo "<table id = 'table1'>";
        if ($fetms)
            echo "<tr><th>$fetms</th></tr>";
        echo "<tr><th>Notes</th></tr>";
        echo "<tr><td><textarea rows='6' cols='90' name = 'Notes'>" . stripcslashes($this->GetValue('Notes')) . "</textarea>";
        echo "<input type='hidden' name='fc' value='".$this->fc."'>";
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
                if ($i % 2 == 0) {
                    $trclass = "alt";
                }
                if ($i % 2 != 0) {
                   $trclass = "";
                }
                $t = new TestData_header();
                $t->Initialize_TestData_header($rowkeys['keyId'], $this->fc, 0);
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
        fkHeader = $fkHeader AND fkFacility = ".$this->fc.";";

        $preCols = "";

        switch($this->Component->GetValue('fkFE_ComponentType')) {
            case 6:
                //Cryostat
                $q = "SELECT keyId FROM TEST_Cryostat_data_SubHeader
                      WHERE fkHeader = $this->keyId;";
                $r = @mysql_query($q,$this->dbconnection) ; //or die('Failed on query in class.testdata_header.php line ' . __LINE__);
                $fkHeader = @mysql_result($r,0,0);
                $qgetdata = "SELECT * FROM $this->TestDataTableName WHERE
                fkSubHeader = $fkHeader AND fkFacility = ".$this->fc.";";
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
                      AND keyFacility = " . $this->fc;
                $r = @mysql_query($q,$this->dbconnection);
                $subid = @mysql_result($r,0,0);
                $qgetdata = "SELECT * FROM Noise_Temp WHERE fkSub_Header = $subid AND keyFacility = "
                        . $this->fc . " ORDER BY FreqLO, CenterIF;";
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
            case 25:
    		case 44:	// WCA cartridge PAI plots
    		case 45:
    		case 46:
    		case 47:
    		case 48:
    		case 50:
            case 51:
            case 52:
            case 53:
            case 54:
            case 58: 	// Noise temperature
    		case 59:	// fine LO sweep
    		case 42:	//CCA cartridge PAI plots
    			return false;

    		case 39:    // I-V Curve
    		case 57: 	// LO lock test
    		default:
    			return true;
    	}
    }

    public function AutoShowRawDataThis() {
        switch($this->GetValue('fkTestData_Type')) {
            case 1:
            case 2:
            case 3:
            case 4:
            case 24:
            case 49:
                return true;
                break;
            default:
                return false;
        }
    }

    public function Export($outputDir) {
        $plotsOnly = false;

        switch ($this->GetValue('fkTestData_Type')) {
            case 56:    //Pol Angles
                $destFile = $outputDir . "PolAngles_B" . $this->GetValue('Band') . "_H" . $this->TestDataHeader . ".ini";
                $handle = fopen($destFile, "w");
                fwrite($handle, "[export]\n");
                fwrite($handle, "band=" . $this->GetValue('Band') . "\n");
                fwrite($handle, "FEid=" . $this->fe_keyId . "\n");
                fwrite($handle, "CCAid=" . $this->GetValue('fkFE_Components') . "\n");
                fwrite($handle, "TDHid=" . $this->TestDataHeader . "\n");
                $result = $this->Calc_PolAngles();
                $index = 0;
                foreach ($result as $row) {
                    $index++;
                    fwrite($handle, "a$index" . "pol=" . $row['pol'] . "\n");
                    fwrite($handle, "a$index" . "nominal=" . $row['nominal'] . "\n");
                    fwrite($handle, "a$index" . "actual=" . $row['actual'] . "\n");
                    fwrite($handle, "a$index" . "diff=" . $row['diff'] . "\n");
                }
                fclose($handle);
                echo "Exported '$destFile'.<br>";
                break;

            case 29:    //Amplitude Workmanship
                $destFile = $outputDir . "AmpWkm_B" . $this->GetValue('Band') . "_H" . $this->TestDataHeader . ".ini";
                $plotsOnly = true;
                break;

            case 57:    //LO Lock Test
                $destFile = $outputDir . "LOLock_B" . $this->GetValue('Band') . "_H" . $this->TestDataHeader . ".ini";
                $plotsOnly = true;
                break;

            default:
                $destFile = "";
                break;
        }
        if ($plotsOnly) {
            $handle = fopen($destFile, "w");
            fwrite($handle, "[export]\n");
            fwrite($handle, "band=" . $this->GetValue('Band') . "\n");
            fwrite($handle, "FEid=" . $this->fe_keyId . "\n");
            fwrite($handle, "WCAid=" . $this->GetValue('fkFE_Components') . "\n");
            fwrite($handle, "TDHid=" . $this->TestDataHeader . "\n");
            $urlarray = explode(",",$this->GetValue('PlotURL'));
            for ($i=0; $i<count($urlarray); $i++) {
                if ($urlarray[$i])
                    fwrite($handle, "plot" . $i+1 . "=" . $urlarray[$i] . "\n");
            }
            fclose($handle);
            echo "Exported '$destFile'.<br>";
        }
        return $destFile;
    }

    public function DrawPlot() {
        $plt = new DataPlotter();
        $plt->Initialize_DataPlotter($this->keyId,$this->dbconnection,$this->fc);

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
            $nztemp->Initialize_NoiseTemperature($this->keyId,$this->fc);
            $nztemp->DrawPlot();
            unset($nztemp);
            break;

        case "59":
            //Fine LO Sweep
            $finelosweep = new FineLOSweep();
            $finelosweep->Initialize_FineLOSweep($this->keyId,$this->fc);
            $finelosweep->DrawPlot();
            unset($finelosweep);
            break;

        case "38":
            //CCA Image Rejection (Sideband Ratio)
            $ccair = new cca_image_rejection();
            $ccair->Initialize_cca_image_rejection($this->keyId,$this->fc);
            $ccair->DrawPlot();
            unset($ccair);
            break;
        }
    }

    public function Plot_WCA($datatype) {
        $wca = new WCA();
        $wca->Initialize_WCA($this->GetValue('fkFE_Components'), $this->fc, WCA::INIT_ALL);

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

    private function Calc_PolAngles() {
        $pa = new GenericTable();
        $pa->Initialize("SourceRotationAngles", $this->GetValue('Band'), "band");

        $nom_0_m90 = $pa->GetValue('pol0_copol') - 90;
        $nom_0_p90 = $pa->GetValue('pol0_copol') + 90;
        $nom_1_m90 = $pa->GetValue('pol1_copol') - 90;
        $nom_1_p90 = $pa->GetValue('pol1_copol') + 90;

        //Pol 0, first minimum
        $qpa = "SELECT MIN(amp_pol0)
                FROM TEST_PolAngles
                WHERE fkFacility = ".$this->fc."
                        AND
                        fkHeader = $this->keyId
                        and angle < ($nom_0_m90 + 10)
                        and angle > ($nom_0_m90 - 10);";
        $rpa = @mysql_query($qpa,$this->dbconnection);

        $pol0_min1 = @mysql_result($rpa,0);

        $qpa = "SELECT angle
        FROM TEST_PolAngles
        WHERE fkHeader = $this->keyId
        and fkFacility = ".$this->fc."
                and ROUND(amp_pol0,5) = " . round($pol0_min1, 5) . "
                        and angle < ($nom_0_m90 + 10)
                        and angle > ($nom_0_m90 - 10);";
        $rpa = @mysql_query($qpa,$this->dbconnection);

        $angle_min0_1 = @mysql_result($rpa,0);

        //Pol 0, 2nd minimum
        $qpa = "SELECT MIN(amp_pol0)
        FROM TEST_PolAngles
        WHERE fkHeader = $this->keyId
        AND fkFacility = ".$this->fc."
        and angle < ($nom_0_p90 + 10)
        and angle > ($nom_0_p90 - 10);";
        $rpa = @mysql_query($qpa,$this->dbconnection);

        $pol0_min2 = @mysql_result($rpa,0);

        $qpa = "SELECT angle
        FROM TEST_PolAngles
        WHERE fkHeader = $this->keyId
        AND fkFacility = ".$this->fc."
                and ROUND(amp_pol0,5) = " . round($pol0_min2, 5) . "
                        and angle < ($nom_0_p90 + 10)
                        and angle > ($nom_0_p90 - 10);";
        $rpa = @mysql_query($qpa,$this->dbconnection);

        $angle_min0_2 = @mysql_result($rpa,0);


        //Pol 1, first minimum
        $qpa = "SELECT MIN(amp_pol1)
        FROM TEST_PolAngles
        WHERE fkHeader = $this->keyId
        AND fkFacility = ".$this->fc."
        and angle < ($nom_1_m90 + 10)
        and angle > ($nom_1_m90 - 10);";
        $rpa = @mysql_query($qpa,$this->dbconnection);

        $pol1_min1 = @mysql_result($rpa,0);

        $qpa = "SELECT angle
        FROM TEST_PolAngles
        WHERE fkHeader = $this->keyId
        AND fkFacility = ".$this->fc."
                and ROUND(amp_pol1,5) = " . round($pol1_min1, 5) . "
                        and angle < ($nom_1_m90 + 10)
                        and angle > ($nom_1_m90 - 10);";
        $rpa = @mysql_query($qpa,$this->dbconnection);

        $angle_min1_1 = @mysql_result($rpa,0);

        //Pol 1, 2nd minimum
        $qpa = "SELECT MIN(amp_pol1)
        FROM TEST_PolAngles
        WHERE fkHeader = $this->keyId
        AND fkFacility = ".$this->fc."
        and angle < ($nom_1_p90 + 10)
        and angle > ($nom_1_p90 - 10);";
        $rpa = @mysql_query($qpa,$this->dbconnection);

        $pol1_min2 = @mysql_result($rpa,0);

        $qpa = "SELECT angle
        FROM TEST_PolAngles
        WHERE fkHeader = $this->keyId
        AND fkFacility = ".$this->fc."
                and ROUND(amp_pol1,5) = " . round($pol1_min2, 5) . "
                        and angle < ($nom_1_p90 + 10)
                        and angle > ($nom_1_p90 - 10);";
        $rpa = @mysql_query($qpa,$this->dbconnection);

        $angle_min1_2 = @mysql_result($rpa,0);

        function makeRow($pol, $actual, $nom) {
            if ($actual && abs($nom) < 181) {
                return array(
                    'pol' => $pol,
                    'nominal' => $nom,
                    'actual' => $actual,
                    'diff' => round($actual - $nom, 2)
                );
            } else
                return false;
        }

        $output = array();
        $row = makeRow(0, $angle_min0_1, $nom_0_m90);
        if ($row)
            $output []= $row;
        $row = makeRow(1, $angle_min1_1, $nom_1_m90);
        if ($row)
            $output []= $row;
        $row = makeRow(0, $angle_min0_2, $nom_0_p90);
        if ($row)
            $output []= $row;
        $row = makeRow(1, $angle_min1_2, $nom_1_p90);
        if ($row)
            $output []= $row;

        return $output;
    }

    public function Display_Data_PolAngles() {
        $result = $this->Calc_PolAngles();

        echo "<div style = 'width:500px'><table id = 'table1'>";

        echo "<th colspan='4'>Band " . $this->GetValue('Band') . " Pol Angles At Minima</th>";
        echo "<tr><th>Pol</th>";
        echo "<th>Nominal Angle</th>";
        echo "<th>Actual Angle</th>";
        echo "<th>Actual - Nominal</th>";
        echo "</tr>";

        if (!$result)
            echo "<tr><td colspan='4'><b>No amplitude minima found within 10 degrees of nominal.</b></td></tr>";
        else {
            foreach ($result as $row) {
                $diff = $row['diff'];
                $hlon = (abs($diff) > 2) ? "<font color='#ff0000'>" : "";
                $hloff = (abs($diff) > 2) ? "</font>" : "";

                echo "<tr><td><b>" . $row['pol'] . "</b></td>";
                echo "<td><b>" . $row['nominal'] . "</b></td>";
                echo "<td><b>" . $row['actual'] . "</b></td>";
                echo "<td><b>$hlon$diff$hloff</b></td></tr>";
            }
        }
        echo "</table></div><br>";
    }
}
?>
