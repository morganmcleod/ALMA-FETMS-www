<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_classes . '/class.cryostat.php');
require_once($site_classes . '/class.dataplotter.php');
require_once($site_classes . '/class.wca.php');
require_once($site_classes . '/class.spec_functions.php');
require_once($site_classes . '/class.testdata_types.php');
require_once($site_classes . '/class.fe_config.php');

require_once($site_dbConnect);

class TestData_header extends GenericTable {
    // TestData_header columns
    public $keyFacility;
    public $keyId;
    public $fkTestData_Type;
    public $DataSetGroup;
    public $fkFE_Config;
    public $fkFE_Components;
    public $fkDataStatus;
    public $Band;
    public $Notes;
    public $FETMS_Description;
    public $TS;
    public $PlotURL;
    public $Meas_SWVer;
    public $Plot_SWVer;
    public $UseForPAI;

    // Foreign data
    public $testDataType;
    public $testDataTableName;
    public $frontEnd;
    public $Component;
    public $feKeyId;
    public $swversion;
    public $fc;
    public $subheader;

    public $notes;
    public $fetms;
    public $band;
    public $dataSetGroup;

    public function __construct($inKeyId, $inFc = 40) {
        parent::__construct("TestData_header", $inKeyId, "keyId", $inFc, 'keyFacility');
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
        // 1.0.9 fixed instantiating DataPlotter in drawPlot().
        // 1.0.8 minor fix to require(class.wca.php)
        // 1.0.7 MM fixes so that we can run with E_NOTIFY
        // version 1.0.6 Moved code from here which instantiates classes derived from this one!   (to testdata.php, pending verification.)
        // version 1.0.5 MM code formatting fixes, fix Display_RawTestData() for LO Lock test.

        $this->fc = $inFc;
        $this->keyId = $inKeyId;
        $this->notes = stripcslashes($this->Notes);
        $this->fetms = trim($this->FETMS_Description) ?? "UNKNOWN";
        $this->Band = $this->Band;
        $this->dataSetGroup = $this->DataSetGroup;

        $this->testDataType      = TestData_Types::getDescriptionFromKeyId($this->fkTestData_Type);
        $this->testDataTableName = TestData_Types::getTableNameFromKeyId($this->fkTestData_Type);

        if ($this->fkFE_Components) {
            $this->Component = new FEComponent(NULL, $this->fkFE_Components, NULL, $this->fc);
        }

        if ($this->fkFE_Config) {
            $this->feKeyId = FE_Config::getFrontEndFromKeyId($this->fkFE_Config);
            $this->frontEnd = new FrontEnd($this->feKeyId, $this->fc, FrontEnd::INIT_SLN | FrontEnd::INIT_CONFIGS);
            $this->Component  = new FEComponent("Front_Ends", $this->feKeyId, "keyFrontEnds", $this->fc, 'keyFacility');
        }
    }

    public static function getIdFromArguments($fkFE_Components, $fkTestData_Type, $fkDataStatus, $keyFacility) {
        $dbConnection = site_getDbConnection();
        $q = "SELECT keyId FROM TestData_header
              WHERE fkFE_Components={$fkFE_Components}
              AND fkTestData_Type={$fkTestData_Type}
              AND fkDataStatus={$fkDataStatus}
              AND keyFacility={$keyFacility}
              GROUP BY keyId DESC";
        $r = mysqli_query($dbConnection, $q);
        return ADAPT_mysqli_result($r, 0, 0);
    }

    public function requestValuesHeader($fkDataStatus = NULL, $Notes = NULL) {
        // Update the TDH record with new values for fkDataStatus or Notes
        if ($fkDataStatus) $this->fkDataStatus = $fkDataStatus;
        if ($Notes) $this->Notes = $Notes;
        $this->Update();
    }

    public function GetFetmsDescription($textBefore = "") {
        return str_replace("'", "", $textBefore . $this->fetms);
    }

    public function displayTestDataButtons() {
        $showrawurl = "testdata.php?showrawdata=1&keyheader={$this->keyId}&fc={$this->fc}";
        $drawurl = "testdata.php?drawplot=1&keyheader={$this->keyId}&fc={$this->fc}";
        $exportcsvurl = "export_to_csv.php?keyheader={$this->keyId}&fc={$this->fc}";
        if ($this->frontEnd) {
            $fesn = $this->frontEnd->SN;
            require(site_get_config_main());  // for $url_root
            $popupScript = "javascript:popupMoveToOtherFE(\"FE-{$fesn}\", \"{$url_root}\", [{$this->keyId}]);";
        }
        echo "<table>";
        switch ($this->fkTestData_Type) {
            case '57':
            case '58':
                //LO Lock test or noise temperature
                $drawurl = "testdata.php?keyheader={$this->keyId}&drawplot=1&fc={$this->fc}";
                echo "
                    <tr>
                        <td>
                        <a style='width:100px' href='{$showrawurl}' class='button blue2 biground'>
                        <span style='width:90px'>Show Raw Data</span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        <a style='width:100px' href='{$drawurl}' class='button blue2 biground'>
                        <span style='width:90px'>Generate Plots</span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        <a style='width:100px' href='{$exportcsvurl}' class='button blue2 biground'>
                        <span style='width:90px'>Export CSV</span></a>
                        </td>
                    </tr>";

                if (isset($this->frontEnd)) {
                    $gridurl = "../datasets/datasets.php?id={$this->keyId}&fc=$this->fc" .
                        "&fe={$this->frontEnd->keyId}&b={$this->Band}&d={$this->fkTestData_Type}";
                    echo "
                        <tr>
                            <td>
                            <a style='width:100px' href='{$popupScript}' class='button blue2 biground'>
                            <span style='width:90px'>Move to\nOther FE</span></a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                            <a style='width:100px' href='{$gridurl}' class='button blue2 biground'>
                            <span style='width:90px'>Edit Data Sets</span></a>
                            </td>
                        </tr>";
                }
                break;

            case '28':
                //cryo pas
                echo "
                    <tr>
                        <td>
                        <a style='width:100px' href='$showrawurl' class='button blue2 bigrounded'
                        <span style='width:90px'>Show Raw Data</span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        <a style='width:100px' href='$exportcsvurl' class='button blue2 bigrounded'
                        <span style='width:90px'>Export CSV</span></a>
                        </td>
                    </tr>";
                break;

            case '52':
                //cryo first cooldown
                echo "
                    <tr>
                        <td>
                        <a style='width:100px' href='$showrawurl' class='button blue2 bigrounded'
                        <span style='width:90px'>Show Raw Data</span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        <a style='width:100px' href='$exportcsvurl' class='button blue2 bigrounded'
                        <span style='width:90px'>Export CSV</span></a>
                        </td>
                    </tr>";
                break;

            case '53':
                //cryo first warmup
                echo "
                    <tr>
                        <td>
                        <a style='width:100px' href='$showrawurl' class='button blue2 bigrounded'
                        <span style='width:130px'>Show Raw Data</span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        <a style='width:100px' href='$exportcsvurl' class='button blue2 bigrounded'
                        <span style='width:90px'>Export CSV</span></a>
                        </td>
                    </tr>";
                break;

            default:
                echo "
                    <tr>
                        <td>
                        <a style='width:100px' href='$showrawurl' class='button blue2 bigrounded'
                        <span style='width:90px'>Show Raw Data</span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        <a style='width:100px' href='$exportcsvurl' class='button blue2 bigrounded'
                        <span style='width:90px'>Export CSV</span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        <a style='width:100px' href='$drawurl' class='button blue2 bigrounded'
                        <span style='width:90px'>Generate Plot</span></a>
                        </td>
                    </tr>";

                if (isset($this->frontEnd)) {
                    echo "
                        <tr>
                            <td>
                            <a style='width:100px' href='$popupScript' class='button blue2 bigrounded'
                            <span style='width:90px'>Move to\nOther FE</span></a>
                            </td>
                        </tr>";
                }
                break;
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

        $c = new Cryostat($this->fkFE_Components, $this->fc);
        echo "<table>";

        $pic_rateofrise = $c->tdheaders[$datatype]->subheader->pic_rateofrise;
        $pic_pressure = $c->tdheaders[$datatype]->subheader->pic_pressure;
        $pic_temperature = $c->tdheaders[$datatype]->subheader->pic_temperature;
        if (!empty($pic_rateofrise)) echo "<tr><td><img src='{$pic_rateofrise}'></td></tr>";
        if (!empty($pic_pressure)) echo "<tr><td><img src='{$pic_pressure}'></td></tr>";
        if (!empty($pic_temperature)) echo "<tr><td><img src='{$pic_temperature}'></td></tr>";
        echo "</table>";
    }

    public function Display_TestDataMain() {
        // Display the notes form and plots:
        $showPlots = false;

        switch ($this->fkTestData_Type) {
            case 7:
                //IF Spectrum not handled by this class.
                // See /FEConfig/ifspectrum/ifspectrumplots.php and class IFSpectrum_impl
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
                $nztemp = new NoiseTemperature($this->keyId, $this->fc);
                $nztemp->displayPlots();
                unset($nztemp);
                break;

            case 59:
                //Fine LO Sweep
                $this->Display_DataSetNotes();
                $finelosweep = new FineLOSweep($this->keyId, $this->fc);
                $finelosweep->displayPlots();
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
                $wca = new WCA($this->fkFE_Components, $this->fc, WCA::INIT_ALL);
                $wca->Display_AmplitudeStability();
                unset($wca);
                break;
            case 44:
                //WCA AM Noise
                $this->Display_DataForm();
                echo "<br>";
                $wca = new WCA($this->fkFE_Components, $this->fc, WCA::INIT_ALL);
                $wca->Display_AMNoise();
                unset($wca);
                break;
            case 46:
                //WCA Output Power
                $this->Display_DataForm();
                echo "<br>";
                $wca = new WCA($this->fkFE_Components, $this->fc, WCA::INIT_ALL);
                $wca->Display_OutputPower();
                unset($wca);
                break;
            case 47:
                //WCA Phase Jitter
                $this->Display_DataForm();
                echo "<br>";
                $wca = new WCA($this->fkFE_Components, $this->fc, WCA::INIT_ALL);
                $wca->Display_PhaseNoise();
                unset($wca);
                break;
            case 48:
                //WCA Phase Noise
                $this->Display_DataForm();
                echo "<br>";
                $wca = new WCA($this->fkFE_Components, $this->fc, WCA::INIT_ALL);
                $wca->Display_PhaseNoise();
                unset($wca);
                break;

            case 38:
                //CCA Image Rejection
                $this->Display_DataForm();
                $ccair = new cca_image_rejection($this->keyId, $this->fc);
                $ccair->displayPlots();
                unset($ccair);
                break;

            default:
                $this->Display_DataForm();
                $showPlots = true;
                break;
        }
        if ($showPlots) {
            $urlarray = explode(",", $this->PlotURL);
            for ($i = 0; $i < count($urlarray); $i++) {
                if ($urlarray[$i]) {
                    global $site_storage;
                    echo "<img src='" . $site_storage . $urlarray[$i] . "'><br><br>";
                }
            }
        }
    }

    public function Display_TestDataMain_html() {
        // Display the notes form and plots:
        $showPlots = false;
        $html = ["", ""];

        switch ($this->fkTestData_Type) {
            case 7:
                //IF Spectrum not handled by this class.
                // See /FEConfig/ifspectrum/ifspectrumplots.php and class IFSpectrum_impl
                break;

            case 56:
                //Pol Angles
                $html[0] = $this->Display_DataForm_html();
                $this->Display_Data_PolAngles();
                $showPlots = true;
                break;

            case 57:
                //LO Lock Test
                $html[0] = $this->Display_DataSetNotes_html();
                $showPlots = true;
                break;

            case 58:
                //Noise Temperature
                $html[0] = $this->Display_DataSetNotes_html();
                $nztemp = new NoiseTemperature($this->keyId, $this->fc);
                $temp_html = $nztemp->DisplayPlots_html();
                $html[0] .= $temp_html[0];
                $html[1] .= $temp_html[1];
                unset($nztemp);
                break;

            case 59:
                //Fine LO Sweep
                $html[0] = $this->Display_DataSetNotes_html();
                $finelosweep = new FineLOSweep($this->keyId, $this->fc);
                $html[0] .= $finelosweep->DisplayPlots_html();
                unset($finelosweep);
                break;

            case 50:
                //Cryostat First Rate of Rise
                $html[0] = $this->Display_DataForm_html();
                $this->Display_Data_Cryostat(1);
                $showPlots = true;
                break;
            case 52:
                //Cryostat First Cooldown
                $html[0] = $this->Display_DataForm_html();
                $this->Display_Data_Cryostat(3);
                $showPlots = true;
                break;
            case 53:
                //Cryostat First Warmup
                $html[0] = $this->Display_DataForm_html();
                $html[0] = $this->Display_Data_Cryostat(2);
                $showPlots = true;
                break;
            case 54:
                //Cryostat Final Rate of Rise
                $html[0] = $this->Display_DataForm_html();
                $this->Display_Data_Cryostat(4);
                $showPlots = true;
                break;
            case 25:
                //Cryostat Rate of Rise After adding Vacuum Equipment
                $html[0] = $this->Display_DataForm_html();
                $this->Display_Data_Cryostat(5);
                $showPlots = true;
                break;
            case 45:
                //WCA Amplitude Stability
                $html[0] = $this->Display_DataForm_html();
                $wca = new WCA($this->fkFE_Components, $this->fc, WCA::INIT_ALL);
                $html[0] .= $wca->Display_AmplitudeStability_html();
                unset($wca);
                break;
            case 44:
                //WCA AM Noise
                $html[0] = $this->Display_DataForm_html();
                $wca = new WCA($this->fkFE_Components, $this->fc, WCA::INIT_ALL);
                $wca->Display_AMNoise();
                unset($wca);
                break;
            case 46:
                //WCA Output Power
                $html[0] = $this->Display_DataForm_html();
                $wca = new WCA($this->fkFE_Components, $this->fc, WCA::INIT_ALL);
                $wca->Display_OutputPower();
                unset($wca);
                break;
            case 47:
                //WCA Phase Jitter
                $html[0] = $this->Display_DataForm_html();
                $wca = new WCA($this->fkFE_Components, $this->fc, WCA::INIT_ALL);
                $wca->Display_PhaseNoise();
                unset($wca);
                break;
            case 48:
                //WCA Phase Noise
                $html[0] = $this->Display_DataForm_html();
                $wca = new WCA($this->fkFE_Components, $this->fc, WCA::INIT_ALL);
                $wca->Display_PhaseNoise();
                unset($wca);
                break;

            case 38:
                //CCA Image Rejection
                $html[0] = $this->Display_DataForm_html();
                $ccair = new cca_image_rejection($this->keyId, $this->fc);
                $ccair->displayPlots();
                unset($ccair);
                break;

            default:
                $html[0] = $this->Display_DataForm_html();
                $showPlots = true;
                break;
        }
        if ($showPlots) {
            $urlarray = explode(",", $this->PlotURL);
            for ($i = 0; $i < count($urlarray); $i++) {
                if ($urlarray[$i]) {
                    if (count($urlarray) == 1)
                        $html[0] .= "<div class='ploturlunique'><img src='" . $urlarray[$i] . "'></div>";
                    else
                        $html[0] .= "<div class='ploturl" . ($i + 1) . "'><img src='" . $urlarray[$i] . "'></div>";
                }
            }
        }
        return $html;
    }

    public function Display_DataForm() {
        // Get FETMS description:
        $fetms = $this->GetFetmsDescription("Measured at: ");
        $action = $_SERVER['PHP_SELF'];

        echo "<div style='width:300px'>";
        echo "<form action='{$action}' method='post'>";
        echo "<table id='table1'>";
        if ($fetms) echo "<tr><th>{$fetms}</th></tr>";
        echo "<tr><th>Notes</th></tr>";
        echo "<tr><td><textarea rows='6' cols='90' name='Notes'>{$this->notes}</textarea>";
        echo "<input type='hidden' name='fc' value='{$this->fc}'>";
        echo "<input type='hidden' name='keyheader' value='{$this->keyId}'>";
        echo "<br><input type='submit' name='submitted' value='SAVE'></td></tr>";
        echo "</form></table></div>";
    }

    public function Display_DataForm_html() {
        //Get FETMS description:
        $fetms = $this->GetFetmsDescription("Measured at: ");
        $html = "";
        $html .= "<div class='form'>";
        $html .= "<table class='table-health'>";
        if ($fetms) $html .= "<tr><th colspan='1'>$fetms</th></tr>";
        $html .= "<tr><th colspan='1'>Notes</th></tr>";
        $notes = $this->notes;
        if ($notes == "") $notes = "-";
        $html .= "<tr><td colspan='1'><textarea rows='6' width='100%' name = 'Notes'>" . $notes . "</textarea>";
        $html .= "</td></tr>";
        $html .= "</table></div>";
        return $html;
    }

    public function Display_DataSetNotes() {
        // Display information for all TestData_header records
        if ($this->dataSetGroup != 0) {
            echo "<br><br><div style='width:900px'><table id='table1' border='1'>";
            echo "<tr class='alt'><th colspan='3'>{$this->testDataType} data sets for TestData_header.DataSetGroup {$this->dataSetGroup}</th></tr>";
            echo "<tr><th width='60px'>Key</th><th width='140px'>Timestamp</th><th>Notes</th></tr>";

            $qkeys = "SELECT keyId FROM TestData_header
                LEFT JOIN FE_Config ON FE_Config.keyFEConfig = TestData_header.fkFE_Config
                WHERE TestData_header.Band = {$this->Band}
                AND TestData_header.DataSetGroup = {$this->dataSetGroup}
                AND FE_Config.fkFront_Ends = {$this->feKeyId}
                AND TestData_header.fkTestData_Type = {$this->fkTestData_Type}";

            $rkeys = mysqli_query($this->dbConnection, $qkeys);

            $i = 0;
            while ($rowkeys = mysqli_fetch_array($rkeys)) {
                if ($i % 2 == 0) {
                    $trclass = "alt";
                }
                if ($i % 2 != 0) {
                    $trclass = "";
                }
                $t = new TestData_header($rowkeys['keyId'], $this->fc);
                echo "<tr class = $trclass>";
                echo "<td>" . $t->keyId . "</td>";
                echo "<td>" . $t->TS . "</td>";
                echo "<td style='text-align:left !important;'>{$this->notes}</td>";
                echo "</tr>";
                $i += 1;
            }

            echo "</table></div>";
        } else {
            $this->Display_DataForm();
        }
    }

    public function Display_DataSetNotes_html() {
        //Display information for all TestData_header records
        $html = "";
        if ($this->dataSetGroup != 0) {

            $html .= "<br><br>
            <div style='width:900px'>
            <table id = 'table1' border = '1'>";

            $html .= "<tr class = 'alt'><th colspan='3'>{$this->testDataType} data sets for TestData_header.DataSetGroup {$this->dataSetGroup}</th></tr>";
            $html .= "<tr>
                    <th width='60px'>Key</th>
                    <th width='140px'>Timestamp</th>
                    <th>Notes</th></tr>";

            $qkeys = "SELECT keyId FROM `TestData_header`
                LEFT JOIN `FE_Config` ON `FE_Config`.keyFEConfig = `TestData_header`.fkFE_Config
                WHERE `TestData_header`.Band = {$this->Band}
                AND `TestData_header`.DataSetGroup = {$this->dataSetGroup}
                AND `FE_Config`.fkFront_Ends = {$this->feKeyId}
                AND `TestData_header`.`fkTestData_Type` = {$this->fkTestData_Type}";

            $rkeys = mysqli_query($this->dbConnection, $qkeys);

            $i = 0;
            while ($rowkeys = mysqli_fetch_array($rkeys)) {
                if ($i % 2 == 0) {
                    $trclass = "alt";
                }
                if ($i % 2 != 0) {
                    $trclass = "";
                }
                $t = new TestData_header($rowkeys['keyId'], $this->fc);
                $html .= "<tr class = $trclass>";
                $html .= "<td>" . $t->keyId . "</td>";
                $html .= "<td>" . $t->TS . "</td>";
                $html .= "<td style='text-align:left !important;'>{$this->notes}</td>";
                $html .= "</tr>";
                $i += 1;
            }

            $html .= "</table></div>";
        } else {
            $html .= $this->Display_DataForm_html();
        }
        return $html;
    }

    public function Display_RawTestData() {
        $fkHeader = $this->keyId;
        $qgetdata = "SELECT * FROM $this->testDataTableName WHERE
        fkHeader = $fkHeader AND fkFacility = " . $this->fc . ";";

        $preCols = "";

        switch ($this->Component->GetValue('fkFE_ComponentType')) {
            case 6:
                //Cryostat
                $q = "SELECT keyId FROM TEST_Cryostat_data_SubHeader
                      WHERE fkHeader = $this->keyId;";
                $r = mysqli_query($this->dbConnection, $q);
                $fkHeader = ADAPT_mysqli_result($r, 0, 0);
                $qgetdata = "SELECT * FROM $this->testDataTableName WHERE
                fkSubHeader = $fkHeader AND fkFacility = " . $this->fc . ";";
                break;
        }

        switch ($this->fkTestData_Type) {
            case 57:
                //LO Lock test
                $qgetdata = "SELECT DT.*
                            FROM TEST_LOLockTest as DT, TEST_LOLockTest_SubHeader as SH, TestData_header as TDH
                            WHERE DT.fkHeader = SH.keyId AND DT.fkFacility = SH.keyFacility
                            AND SH.fkHeader = TDH.keyId AND SH.keyFacility = TDH.keyFacility
                    AND TDH.keyId = {$fkHeader}
                    AND TDH.Band = {$this->Band}
                    AND TDH.DataSetGroup = {$this->dataSetGroup}
                    AND TDH.fkFE_Config = " . $this->fkFE_Config
                    . " AND DT.IsIncluded = 1
                            ORDER BY DT.LOFreq ASC;";

                $this->testDataTableName = 'TEST_LOLockTest';
                break;

            case 58:
                //Noise Temperature
                $q = "SELECT keyId FROM Noise_Temp_SubHeader
                      WHERE fkHeader = $this->keyId
                      AND keyFacility = " . $this->fc;
                $r = mysqli_query($this->dbConnection, $q);
                $subid = ADAPT_mysqli_result($r, 0, 0);
                $qgetdata = "SELECT * FROM Noise_Temp WHERE fkSub_Header = $subid AND keyFacility = "
                    . $this->fc . " ORDER BY FreqLO, CenterIF;";
                $this->testDataTableName = 'Noise_Temp';
                break;

            case 59:
                //fine LO Sweep
                $qgetdata = "SELECT HT.Pol, DT.*
                    FROM TEST_FineLOSweep AS DT, TEST_FineLOSweep_SubHeader AS HT
                    WHERE HT.fkHeader = $this->keyId
                    AND DT.fkSubHeader = HT.keyId;";

                $preCols = "Pol";
                $this->testDataTableName = 'TEST_FineLOSweep';
                break;
        }

        $q = "SHOW COLUMNS FROM $this->testDataTableName;";
        $r = mysqli_query($this->dbConnection, $q);

        echo        "<table id = 'table1'>";

        if ($preCols) {
            echo "
                <th>$preCols</th>";
        }

        while ($row = mysqli_fetch_array($r)) {
            echo "
                <th>$row[0]</th>";
        }


        $r = mysqli_query($this->dbConnection, $qgetdata);
        while ($row = mysqli_fetch_array($r)) {
            echo "<tr>";
            for ($i = 0; $i < count($row) / 2; $i++) {
                echo "<td>$row[$i]</td>";
            }
            echo "</tr>";
        }

        echo "</table>";

        //echo "</div>";
    }

    public function AutoDrawThis() {
        // return true if this plot type should be automatically drawn on page load.
        switch ($this->fkTestData_Type) {
            case 1:        // health check tabular data
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
            case 44:    // WCA cartridge PAI plots
            case 45:
            case 46:
            case 47:
            case 48:
            case 50:
            case 51:
            case 52:
            case 53:
            case 54:
            case 58:     // Noise temperature
            case 59:    // fine LO sweep
            case 42:    //CCA cartridge PAI plots
                return false;

            case 39:    // I-V Curve
            case 57:     // LO lock test
            default:
                return true;
        }
    }

    public function AutoShowRawDataThis() {
        switch ($this->fkTestData_Type) {
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

        switch ($this->fkTestData_Type) {
            case 56:    //Pol Angles
                $destFile = "{$outputDir}PolAngles_B{$this->Band}_H{$this->keyId}.ini";
                $handle = fopen($destFile, "w");
                fwrite($handle, "[export]\n");
                fwrite($handle, "band={$this->Band}\n");
                fwrite($handle, "FEid={$this->feKeyId}\n");
                fwrite($handle, "CCAid={$this->fkFE_Components}\n");
                fwrite($handle, "TDHid={$this->keyId}\n");
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
                $destFile = $outputDir . "AmpWkm_B{$this->Band}_H" . $this->keyId . ".ini";
                $plotsOnly = true;
                break;

            case 57:    //LO Lock Test
                $destFile = $outputDir . "LOLock_B{$this->Band}_H" . $this->keyId . ".ini";
                $plotsOnly = true;
                break;

            default:
                $destFile = "";
                break;
        }
        if ($plotsOnly) {
            $handle = fopen($destFile, "w");
            fwrite($handle, "[export]\n");
            fwrite($handle, "band={$this->Band}\n");
            fwrite($handle, "FEid=" . $this->feKeyId . "\n");
            fwrite($handle, "WCAid=" . $this->fkFE_Components . "\n");
            fwrite($handle, "TDHid=" . $this->keyId . "\n");
            $urlarray = explode(",", $this->PlotURL);
            for ($i = 0; $i < count($urlarray); $i++) {
                if ($urlarray[$i])
                    fwrite($handle, "plot" . $i + 1 . "=" . $urlarray[$i] . "\n");
            }
            fclose($handle);
            echo "Exported '$destFile'.<br>";
        }
        return $destFile;
    }

    public function drawPlot() {
        $plt = new DataPlotter($this->keyId, $this->fc);

        //Determine which type of plot to draw...
        switch ($this->fkTestData_Type) {
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
                $nztemp = new NoiseTemperature($this->keyId, $this->fc);
                $nztemp->drawPlot();
                unset($nztemp);
                break;

            case "59":
                //Fine LO Sweep
                $finelosweep = new FineLOSweep($this->keyId, $this->fc);
                $finelosweep->drawPlot();
                unset($finelosweep);
                break;

            case "38":
                //CCA Image Rejection (Sideband Ratio)
                $ccair = new cca_image_rejection($this->keyId, $this->fc);
                $ccair->drawPlot();
                unset($ccair);
                break;
        }
    }

    public function Plot_WCA($datatype) {
        $wca = new WCA($this->fkFE_Components, $this->fc, WCA::INIT_ALL);

        switch ($datatype) {
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
        $pa = new GenericTable("SourceRotationAngles", $this->Band, "band");

        $nom_0_m90 = $pa->GetValue('pol0_copol') - 90;
        $nom_0_p90 = $pa->GetValue('pol0_copol') + 90;
        $nom_1_m90 = $pa->GetValue('pol1_copol') - 90;
        $nom_1_p90 = $pa->GetValue('pol1_copol') + 90;

        //Pol 0, first minimum
        $qpa = "SELECT MIN(amp_pol0)
                FROM TEST_PolAngles
                WHERE fkFacility = " . $this->fc . "
                        AND
                        fkHeader = $this->keyId
                        and angle < ($nom_0_m90 + 10)
                        and angle > ($nom_0_m90 - 10);";
        $rpa = mysqli_query($this->dbConnection, $qpa);

        $pol0_min1 = ADAPT_mysqli_result($rpa, 0);

        $qpa = "SELECT angle
        FROM TEST_PolAngles
        WHERE fkHeader = $this->keyId
        and fkFacility = " . $this->fc . "
                and ROUND(amp_pol0,5) = " . round($pol0_min1, 5) . "
                        and angle < ($nom_0_m90 + 10)
                        and angle > ($nom_0_m90 - 10);";
        $rpa = mysqli_query($this->dbConnection, $qpa);

        $angle_min0_1 = ADAPT_mysqli_result($rpa, 0);

        //Pol 0, 2nd minimum
        $qpa = "SELECT MIN(amp_pol0)
        FROM TEST_PolAngles
        WHERE fkHeader = $this->keyId
        AND fkFacility = " . $this->fc . "
        and angle < ($nom_0_p90 + 10)
        and angle > ($nom_0_p90 - 10);";
        $rpa = mysqli_query($this->dbConnection, $qpa);

        $pol0_min2 = ADAPT_mysqli_result($rpa, 0);

        $qpa = "SELECT angle
        FROM TEST_PolAngles
        WHERE fkHeader = $this->keyId
        AND fkFacility = " . $this->fc . "
                and ROUND(amp_pol0,5) = " . round($pol0_min2, 5) . "
                        and angle < ($nom_0_p90 + 10)
                        and angle > ($nom_0_p90 - 10);";
        $rpa = mysqli_query($this->dbConnection, $qpa);

        $angle_min0_2 = ADAPT_mysqli_result($rpa, 0);


        //Pol 1, first minimum
        $qpa = "SELECT MIN(amp_pol1)
        FROM TEST_PolAngles
        WHERE fkHeader = $this->keyId
        AND fkFacility = " . $this->fc . "
        and angle < ($nom_1_m90 + 10)
        and angle > ($nom_1_m90 - 10);";
        $rpa = mysqli_query($this->dbConnection, $qpa);

        $pol1_min1 = ADAPT_mysqli_result($rpa, 0);

        $qpa = "SELECT angle
        FROM TEST_PolAngles
        WHERE fkHeader = $this->keyId
        AND fkFacility = " . $this->fc . "
                and ROUND(amp_pol1,5) = " . round($pol1_min1, 5) . "
                        and angle < ($nom_1_m90 + 10)
                        and angle > ($nom_1_m90 - 10);";
        $rpa = mysqli_query($this->dbConnection, $qpa);

        $angle_min1_1 = ADAPT_mysqli_result($rpa, 0);

        //Pol 1, 2nd minimum
        $qpa = "SELECT MIN(amp_pol1)
        FROM TEST_PolAngles
        WHERE fkHeader = $this->keyId
        AND fkFacility = " . $this->fc . "
        and angle < ($nom_1_p90 + 10)
        and angle > ($nom_1_p90 - 10);";
        $rpa = mysqli_query($this->dbConnection, $qpa);

        $pol1_min2 = ADAPT_mysqli_result($rpa, 0);

        $qpa = "SELECT angle
        FROM TEST_PolAngles
        WHERE fkHeader = $this->keyId
        AND fkFacility = " . $this->fc . "
                and ROUND(amp_pol1,5) = " . round($pol1_min2, 5) . "
                        and angle < ($nom_1_p90 + 10)
                        and angle > ($nom_1_p90 - 10);";
        $rpa = mysqli_query($this->dbConnection, $qpa);

        $angle_min1_2 = ADAPT_mysqli_result($rpa, 0);

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
            $output[] = $row;
        $row = makeRow(1, $angle_min1_1, $nom_1_m90);
        if ($row)
            $output[] = $row;
        $row = makeRow(0, $angle_min0_2, $nom_0_p90);
        if ($row)
            $output[] = $row;
        $row = makeRow(1, $angle_min1_2, $nom_1_p90);
        if ($row)
            $output[] = $row;

        return $output;
    }

    public function Display_Data_PolAngles() {
        $result = $this->Calc_PolAngles();

        echo "<div style = 'width:500px'><table id = 'table1'>";

        echo "<th colspan='4'>Band {$this->Band} Pol Angles At Minima</th>";
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
