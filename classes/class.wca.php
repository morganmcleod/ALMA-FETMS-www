<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.dboperations.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_classes . '/class.wcas.php');
require_once($site_dBcode . '/../dBcode/wcadb.php');
require_once($site_classes . '/class.spec_functions.php');
require_once($site_dbConnect);

if (!isset($GNUPLOT_VER)) {
    global $GNUPLOT_VER;
    $GNUPLOT_VER = 4.9;
}

class WCA extends FEComponent {
    var $_WCAs;
    var $LOParams; // array of LO Params (Generic Table objects)
    var $facility;
    var $ConfigId;
    var $ConfigLinkId;
    var $fkDataStatus;
    var $writedirectory;
    var $url_directory;
    var $fc; // facility code
    var $GNUplot; // GNUPlot location
    var $logfile;
    var $logfile_fh;
    private $tdh_amnoise; // TestData_header record object for AM Noise
    private $tdh_ampstab; // TestData_header record object for Amplitude Stability
    private $tdh_outputpower; // TestData_header record object for Output Power
    private $tdh_phasenoise; // TestData_header record object for Phase Noise
    private $tdh_phasejitter; // TestData_header record object for Phase Jitter
    private $tdh_isolation; // TDH record for Isolation
    var $db_pull;
    var $new_spec;
    private $maxSafePowerTable; // Array of rows for the Max Safe Operating Parameters table.
    var $SubmittedFileExtension; // Extension of submitted file for update (csv, ini or zip)
    var $SubmittedFileName; // Uploaded file (csv, zip, ini), base name
    var $SubmittedFileTmp; // Uploaded file (csv, zip, ini), actual path
    var $ErrorArray; // Array of errors

    function __construct($in_keyId, $in_fc, $INIT_Options = self::INIT_ALL) {
        $this->fc = $in_fc;
        parent::__construct(NULL, $in_keyId, NULL, $in_fc);
        $this->fkDataStatus = '7';
        $this->swversion = "1.3.12";
        /* 1.3.12 Fix date stamps on WCA plots
         * 1.3.11 Add LO PA VG settings to WCA max output power vs. frequency plot.
         * 1.3.10 Re-enable saving basic parameters from wca.php:  ESN, YIG low, YIG high, VG0, VG1
         *        Make Band and SN entry fields read-only.   Remove Notes entry field (wasn't working anyway.)
         * 1.3.9 Use "LO" terminology instead of "WCA" for band 1
         * 1.3.8 Delete WCA_LOParams in Upload_WCAs_file().  Will get recreated on refresh.
         *       Map VGA, VGB to VG0, VG1 in band-dependent way on WCAs fileimport 
         * 1.3.7 Fix bugs in Update_Configuration_From_INI(), GetXmlFileContent()
         * 1.3.6 Fix GetXmlFileContent() to comply with /alma/ste/config/TMCDB_DATA/ for Cycle 8
         * 1.3.5 includes OptimizationTargets in WCA data delivery XML
         * 1.3.4 Amplitude stability: force Y-axis to scientific notation.
         * 1.3.3 Updated XML to 2021 format 2020-10-22_FEND.40.00.00.00-1614-A-CRE, FECRE-87
         * 1.3.2 Amplitude stability X axis is labeled [ms].  Removed dubous code from writing data files loop.
         * 1.3.1 Made import and plot amplitude stability slightly more robust to data errors
         * 1.3.0 Changed format of WCAs.CSV file to "band, serial, CreatedDate, ESN, YIGHigh, YIGLow, VGA, VGB"
         *       Removed SAVE CHANGES.  Upload is the only way to update these things now.
         * 1.2.5 Plot Output Power vs Drain Voltage use max(VD0, VD1) rouned up to nearest 0.5
         * 1.2.4 Plot Output Power vs Drain Voltage specified X-axis ranges per-band.
         * 1.2.3 Deleted Max Safe Power upload function and button.
         * 1.2.2 Add writing WCA XML file including cold and warm mults.  Guards on database ops.
         * 1.2.1 Fix how Max Safe Power table computed.  Was using all history for (Band, SN).
         * 1.2.0 Added new-style ouput power plot and isolation plot for band 1.
         * 1.1.5 Plots guard against empty keyId
         * 1.1.4 Added GetXmlFileContent() and calls to download 'XML Data 2019'
         * 1.1.3 Deleted 2nd Max Safe Power table.  Added WCA SN and TS to Max Safe table.
         * 1.1.2 Display "Date Added" instead of "In Front End"
         * 1.1.1 Moved ini format code into this->GetIniFileContent().
         * 1.1.0 Reformatted all HTML to be not so terrible.  export_to_ini for FEMC 2.8.x
         * 1.0.8 Units -> mW on Max Safe Power tables, Output power plotting fixes and improvements.
         * 1.0.7 MM Added INIT_Options to Initialize_WCA()
         * 1.0.6 Fix more plotting errors in WCA electronic data upload (step size plots.)
         * 1.0.5 Fix plotting errors in WCA electronic data upload.
         * 1.0.4 Added XML config file upload and fixed related bugs.
         * 1.0.3 calculate max safe power table from output power data in database.
         * 1.0.2 fix "set label...screen" commands to gnuplot
         */

        require(site_get_config_main());
        $this->writedirectory = $wca_write_directory;
        $this->db_pull = new WCAdb($this->dbConnection);
        $this->new_spec = new Specifications();
        $this->url_directory = $wca_url_directory;
        $this->GNUplot = $GNUplot;
        $this->ZipDirectory = $this->writedirectory . "zip";
        $this->ErrorArray = array();

        $this->writedirectory = $this->writedirectory . "wca" . $this->Band . "_" . $this->SN . "/";
        $this->url_directory = $this->url_directory . "wca" . $this->Band . "_" . $this->SN . "/";

        // Get WCA record:
        $rWCA = $this->db_pull->q_other('WCA', $this->keyId);
        $WCAs_id = ADAPT_mysqli_result($rWCA, 0);
        $this->_WCAs = new WCAs($WCAs_id, $this->fc);

        // Get FE_Config information
        $rcfg = $this->db_pull->q_other('cfg', $this->keyId, $this->fc);
        $this->ConfigId = ADAPT_mysqli_result($rcfg, 0, 0);
        $this->ConfigLinkId = ADAPT_mysqli_result($rcfg, 0, 1);
        $this->FEId = ADAPT_mysqli_result($rcfg, 0, 2);
        $this->FESN = ADAPT_mysqli_result($rcfg, 0, 3);

        if ($INIT_Options & self::INIT_SLN) {
            // Status location and notes
            $rsln = $this->db_pull->q_other('sln', $this->keyId);
            $slnid = ADAPT_mysqli_result($rsln, 0, 0);
            $this->sln = new GenericTable("FE_StatusLocationAndNotes", $slnid, "keyId");
        }

        if ($INIT_Options & self::INIT_LOPARAMS) {
            $r = $this->db_pull->q(1, $this->keyId);
            $lopcount = 0;
            if ($r) {
                while ($row = mysqli_fetch_array($r)) {
                    $this->LOParams[$lopcount] = new GenericTable('WCA_LOParams', $row[0], 'keyId', $this->fc, 'fkFacility');
                    $lopcount += 1;
                }
            }
        }

        if ($INIT_Options & self::INIT_TESTDATA) {
            // Test data header objects
            $rtdh = $this->db_pull->qtdh('select', $this->keyId, 'WCA_PhaseJitter');
            $this->tdh_phasejitter = new TestData_header(ADAPT_mysqli_result($rtdh, 0, 0), $this->keyFacility);

            $rtdh = $this->db_pull->qtdh('select', $this->keyId, 'WCA_AmplitudeStability');
            $this->tdh_ampstab = new TestData_header(ADAPT_mysqli_result($rtdh, 0, 0), $this->keyFacility);

            $rtdh = $this->db_pull->qtdh('select', $this->keyId, 'WCA_OutputPower');
            $this->tdh_outputpower = new TestData_header(ADAPT_mysqli_result($rtdh, 0, 0), $this->keyFacility);

            $rtdh = $this->db_pull->qtdh('select', $this->keyId, 'WCA_PhaseNoise');
            $this->tdh_phasenoise = new TestData_header(ADAPT_mysqli_result($rtdh, 0, 0), $this->keyFacility);

            $rtdh = $this->db_pull->qtdh('select', $this->keyId, 'WCA_AMNoise');
            $this->tdh_amnoise = new TestData_header(ADAPT_mysqli_result($rtdh, 0, 0), $this->keyFacility);

            $rtdh = $this->db_pull->qtdh('select', $this->keyId, 'WCA_Isolation');
            $this->tdh_isolation = new TestData_header(ADAPT_mysqli_result($rtdh, 0, 0), $this->keyFacility);
        }
    }
    private function AddError($ErrorString) {
        $this->ErrorArray[] = $ErrorString;
    }
    const INIT_SLN = 0x0001;
    const INIT_LOPARAMS = 0x0002;
    const INIT_TESTDATA = 0x0004;
    const INIT_NONE = 0x0000;
    const INIT_ALL = 0x001F;

    public static function NewRecord_WCA($fc = 40) {
        require(site_get_config_main());
        $tempFE = FEComponent::NewRecord('FE_Components', 'keyId', $fc, 'keyFacility');
        $wca = new WCA($tempFE->keyId, $fc);
        $wca->fkFE_ComponentType = 11;

        $tempWCAs = WCAs::NewRecord("WCAs");
        $wcas = new WCAs($tempWCAs->keyId, $fc);
        $wcas->fc = $fc;
        $wcas->fkFE_Component = $wca->keyId;
        $wcas->fkFacility = $fc;

        $wca->_WCAs = $wcas;
        $wca->db_pull->q_other('status', $wca->keyId, $wca->fc);
        return $wca;
    }

    public function AddNewLOParams() {
        $band = $this->Band;
        if (empty($band))
            $FreqLO = 0;
        else {
            $specs = $this->new_spec->getSpecs('wca', $band);
            $FreqLO = $specs['FreqLO'];
        }

        $r = $this->db_pull->q(2, $this->keyId);
        if ($r) {
            $numrows = mysqli_num_rows($r);
            if ($numrows < 1) {
                $values = array();
                $values[] = $this->_WCAs->VG0;
                $values[] = $this->_WCAs->VG1;
                $rn = $this->db_pull->q_other('n', $this->keyId, NULL, NULL, $FreqLO, NULL, NULL, $values);
            }
        }
    }
    public function Update_WCA() {
        parent::Update();
        $this->_WCAs->Update();
    }
    public function DisplayData_WCA() {
        require(site_get_config_main());
        $where = $_SERVER["PHP_SELF"];
        $where = '';
        $band = $this->Band;
        $name = ($band == 1) ? "LO" : "WCA";
        echo "<form action='" . $where . "' method='POST'>";
        echo "<div style ='width:100%;height:50%;margin-left:30px;'>";
        echo "<br><font size='+2'><b>$name Information</b></font>";

        $this->DisplayMainData();

        echo "<br>";

        echo "<input type='hidden' name='" . $this->keyIdName . "' value='$this->keyId'>";
        if ($this->fc == '')
            echo "<input type='hidden' name='fc' value='$fc'>";
        else
            echo "<input type='hidden' name='fc' value='$this->fc'>";

        echo "<input type='submit' name = 'submitted' value='SAVE CHANGES'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

        echo "<input type='submit' name = 'deleterecord' value='DELETE RECORD'>";

        echo "</div>";

        echo "<div style ='width:100%;height:50%;margin-top:20px;margin-left:30px;'>";

        $band = $this->Band;
        if ($this->keyId != "") {
            echo "<table>";
            echo "<tr><td>";
            $this->Compute_MaxSafePowerLevels();
            $this->Display_MaxSafePowerLevels();
            echo "</td></tr>";
            echo "<tr><td><br>";
            $this->Display_LOParams();
            echo "</tr></td>";
            echo "</table>";
        }
        echo "</div>";

        if ($this->_WCAs->amp_stability_url != "") {
            echo "<div style='margin-top:20px;margin-bottom:20px;'>";
            echo "<img src='" . $this->_WCAs->amp_stability_url . "'>";
            echo "</div>";
            // echo "<br><input type='submit' name = 'exportcsv_amplitudestability' value='EXPORT TO CSV'>";
        }
        if ($band != 1 && $this->_WCAs->amnz_avgdsb_url != "") {
            echo "<div style='margin-top:20px;margin-bottom:20px;'>";
            echo "<img src='" . $this->_WCAs->amnz_avgdsb_url . "'>";
            echo "</div>";
            // echo "<br><input type='submit' name = 'exportcsv_amnz_dsb' value='EXPORT TO CSV'>";
        }
        if ($band != 1 && $this->_WCAs->amnz_pol0_url != "") {
            echo "<div style='margin-left:20px;margin-top:20px;margin-bottom:20px;'>";
            echo "<img src='" . $this->_WCAs->amnz_pol0_url . "'>";
            echo "</div>";
            // echo "<br><input type='submit' name = 'exportcsv_amnz_pol0' value='EXPORT TO CSV'>";
        }
        if ($band != 1 && $this->_WCAs->amnz_pol1_url != "") {
            echo "<div style='margin-left:20px;margin-top:20px;margin-bottom:20px;'>";
            echo "<img src='" . $this->_WCAs->amnz_pol1_url . "'>";
            echo "</div>";
            // echo "<br><input type='submit' name = 'exportcsv_amnz_pol1' value='EXPORT TO CSV'>";
        }

        echo "<div style='margin-top:20px;margin-bottom:20px;'>";
        $this->Display_PhaseNoise();
        echo "</div>";

        if ($band != 1 && $this->_WCAs->op_vs_freq_url != "") {
            echo "<div style='margin-top:20px;margin-bottom:20px;'>";
            echo "<img src='" . $this->_WCAs->op_vs_freq_url . "'>";
            echo "</div>";
            // echo "<br><input type='submit' name = 'exportcsv_op_vs_freq' value='EXPORT TO CSV'>";
        }
        if ($this->_WCAs->op_vs_dv_pol0_url != "") {
            echo "<div style='margin-top:20px;margin-bottom:20px;'>";
            echo "<img src='" . $this->_WCAs->op_vs_dv_pol0_url . "'>";
            echo "</div>";
        }
        if ($this->_WCAs->op_vs_dv_pol1_url != "") {
            echo "<div style='margin-top:20px;margin-bottom:20px;'>";
            echo "<img src='" . $this->_WCAs->op_vs_dv_pol1_url . "'>";
            echo "</div>";
        }
        if ($band != 1 && $this->_WCAs->op_vs_ss_pol0_url != "") {
            echo "<div style='margin-top:20px;margin-bottom:20px;'>";
            echo "<img src='" . $this->_WCAs->op_vs_ss_pol0_url . "'>";
            echo "</div>";
        }
        if ($band != 1 && $this->_WCAs->op_vs_ss_pol1_url != "") {
            echo "<div style='margin-top:20px;margin-bottom:20px;'>";
            echo "<img src='" . $this->_WCAs->op_vs_ss_pol1_url . "'>";
            echo "</div>";
        }
        if ($band == 1 && $this->_WCAs->isolation_url != "") {
            echo "<div style='margin-top:20px;margin-bottom:20px;'>";
            echo "<img src='" . $this->_WCAs->isolation_url . "'>";
            echo "</div>";
        }
        echo "</form>";
        $this->Display_uploadform();
    }
    public function Display_AmplitudeStability() {
        global $site_storage;
        echo "<img src='" . $site_storage . $this->_WCAs->amp_stability_url . "'>";
    }
    public function Display_AmplitudeStability_html() {
        global $site_storage;
        return "<img src='" . $site_storage . $this->_WCAs->amp_stability_url . "'>";
    }
    public function Display_AMNoise() {
        echo "<table>";
        echo "<tr><td><img src='" . $this->_WCAs->amnz_avgdsb_url . "'></td></tr>";
        echo "<tr><td><img src='" . $this->_WCAs->amnz_pol0_url . "'></td></tr>";
        echo "<tr><td><img src='" . $this->_WCAs->amnz_pol1_url . "'></td></tr>
        </table>";
    }
    public function Display_OutputPower() {
        echo "<table>";
        echo "<tr><td><img src='" . $this->_WCAs->op_vs_freq_url . "'></td></tr>";
        echo "<tr><td><img src='" . $this->_WCAs->op_vs_dv_pol0_url . "'></td></tr>";
        echo "<tr><td><img src='" . $this->_WCAs->op_vs_dv_pol1_url . "'></td></tr>";
        echo "<tr><td><img src='" . $this->_WCAs->op_vs_ss_pol0_url . "'></td></tr>";
        echo "<tr><td><img src='" . $this->_WCAs->op_vs_ss_pol1_url . "'></td></tr>";
        echo "</table>";
    }
    public function Display_PhaseNoise() {
        echo "<img src='" . $this->_WCAs->phasenoise_url . "'>";
        echo "<div style = 'margin-top:20px;width:500px;'>
                <table id = 'table2'>
                    <tr><th colspan = '3'><b>Phase Jitter</b></th></tr>
                    <tr>
                        <td>LO</td>
                        <td>Pol</td>
                        <td>Jitter (fs)</td>
                    </tr>";

        $rpj = $this->db_pull->qpj('select', $this->tdh_phasejitter->keyId);
        if ($rpj) {
            while ($rowpj = mysqli_fetch_array($rpj)) {
                $lo = $rowpj[0];
                $jitter = $rowpj[1];
                $pol = $rowpj[2];

                echo   "<tr>
                            <td>" . round($lo, 0) . "</td>
                            <td>$pol</td>
                            <td>" . round($jitter, 1) . "</td>
                        </tr>";
            }
        }
        echo "</td></table></div>";
    }
    public function DisplayMainData() {
        echo "<div style = 'width: 300px;margin-top:20px'>";
        echo "<table id = 'table1'>";

        $ts = $this->TS;

        echo "<tr>";
        echo "<th>Date Added</th>";
        echo "<td><font size='-1'>$ts</font></td></tr>";
        echo "<tr>";
        echo "<th>Band</th>";
        echo "<td><input type='text' name='Band' size='2' maxlength='200' disabled value = '" . $this->Band . "'></td>";
        echo "</tr>";
        echo "<tr>";
        echo "<th>SN</th>";
        echo "<td><input type='text' name='SN' size='2' maxlength='200' disabled value = '" . $this->SN . "'></td>";
        echo "</tr>";
        echo "<tr>";
        echo "<th>ESN</th>";
        echo "<td><input type='text' name='ESN1' size='20' maxlength='200' value = '" . $this->ESN1 . "'></td>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>YIG LOW (GHz)</th>";
        echo "<td><input type='text' name='FloYIG' size='5' maxlength='200' value = '" . $this->_WCAs->FloYIG . "'></td>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>YIG HIGH (GHz)</th>";
        echo "<td><input type='text' name='FhiYIG' size='5' maxlength='200' value = '" . $this->_WCAs->FhiYIG . "'></td>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>VG0</th>";
        echo "<td><input type='text' name='VG0' size='5' maxlength='200' value = '" . $this->_WCAs->VG0 . "'></td>";
        echo "</tr>";
        echo "<tr>";
        echo "<th>VG1</th>";
        echo "<td><input type='text' name='VG1' size='5' maxlength='200' value = '" . $this->_WCAs->VG1 . "'></td>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>INI, XML downloads</th>";
        echo "<td>";

        $xmlname = hexdec($this->ESN1);
        if ($xmlname)
            $xmlname = "&xmlname=$xmlname";
        else
            $xmlname = "";

        echo "<a href='export_to_ini_wca.php?keyId=$this->keyId&fc=$this->fc&type=xml$xmlname'>XML Data (2021)</a><br>";
        echo "<a href='export_to_ini_wca.php?keyId=$this->keyId&fc=$this->fc&type=fec'>FrontEndControl.ini</a><br>";
        echo "<a href='export_to_ini_wca.php?keyId=$this->keyId&fc=$this->fc&type=wca'>FEMC WCA.ini</a>";
        echo "</td>";
        echo "</tr>";

        echo "</table></div>";
        echo "<br>Notes:<input type='text' name='Notes' size='50'
        maxlength='200' value = '" . $this->Notes . "'>";
    }

    public function DisplayMainDataNonEdit() {
        echo "<div style = 'width: 300px'><br><br>";
        echo "<table id = 'table1'>";

        echo "<tr class='alt'>";
        echo "<th colspan = '2'>
        <font size = '+1'>
        WCA (Band " . $this->Band . " SN " . $this->SN . " )
        </font></th></tr>";

        echo "<tr>";
        echo "<th>In Front End SN</th>";
        echo "<td>" . $this->FESN . "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>YIG LOW (GHz)</th>";
        echo "<td>" . $this->_WCAs->FloYIG . "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>YIG HIGH (GHz)</th>";
        echo "<td>" . $this->_WCAs->FhiYIG . "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>VG0</th>";
        echo "<td>" . $this->_WCAs->VG0 . "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<th>VG1</th>";
        echo "<td>" . $this->_WCAs->VG1 . "</td>";
        echo "</tr>";

        echo "</table></div>";
    }

    private function GetLowLOForBand($band) {
        //Get lowest LO
        //TODO: move into specs class
        $lowlo = "0.000";
        switch ($band) {
            case 1:
                $lowlo = "31.000";
                break;
            case 2:
                $lowlo = "67.000";
                break;
            case 3:
                $lowlo = "92.000";
                break;
            case 4:
                $lowlo = "133.000";
                break;
            case 5:
                $lowlo = "171.000";
                break;
            case 6:
                $lowlo = "221.000";
                break;
            case 7:
                $lowlo = "283.000";
                break;
            case 8:
                $lowlo = "393.000";
                break;
            case 9:
                $lowlo = "614.000";
                break;
            case 10:
                $lowlo = "795.000";
                break;
        }
        return $lowlo;
    }

    public function GetIniFileContent($type) {
        // type is either 'fec' or 'wca'.

        $band = $this->Band;
        $sn   = ltrim($this->SN, '0');
        $esn  = $this->ESN1;
        $description = "Description=WCA$band-$sn";
        $lowlo = $this->GetLowLOForBand($band);

        $ret = "";
        if ($type == 'fec') {
            $ret .= "[~WCA$band-$sn]\r\n";
            $ret .= "$description\r\n";
            $ret .= "Band=$band\r\n";
            $ret .= "SN=$sn\r\n";
            $ret .= "ESN=$esn\r\n";
            $ret .= "FLOYIG=" . $this->_WCAs->FloYIG . "\r\n";
            $ret .= "FHIYIG=" . $this->_WCAs->FhiYIG . "\r\n";

            $r = $this->db_pull->q(4, $this->keyId);
            $count = 0;
            while ($row = mysqli_fetch_array($r)) {
                $countKey = $count + 1;
                $countKey = "$countKey";
                if ($count < 9) {
                    $countKey = "0" . $countKey;
                }
                $mstring = "LOParam$countKey=" . $row['FreqLO'];
                $mstring .= ", " . number_format(floatval($row['VDP0']), 2);
                $mstring .= ", " . number_format(floatval($row['VDP1']), 2);
                $mstring .= ", " . number_format(floatval($row['VGP0']), 2);
                $mstring .= ", " . number_format(floatval($row['VGP1']), 2) . "\r\n";
                $ret .= $mstring;
                $count += 1;
            }

            if ($count) {
                $ret .= "LOParams=$count\r\n";
            } else {
                $ret .= "LOParams=1\r\n";
                $mstring = "LOParam01=$lowlo";
                $mstring .= ",1.00,1.00,";

                $mstring .= number_format(floatval($this->_WCAs->VG0), 2) . ",";
                $mstring .= number_format(floatval($this->_WCAs->VG1), 2) . "\r\n";
                $ret .= $mstring;
            }
            $ret .= "\r\n\r\n\r\n";
        } else if ($type == 'wca') {

            $ret .= ";
; WCA configuration file
;
; Make sure to end every line containing data with a LF or CR/LF
;

[PA_LIMITS]";

            $ret .= "\r\n";
            $ret .= "ESN=$esn\r\n";
            $ret .= "SN=WCA$band-$sn\r\n";
            $ret .= "FLOYIG=" . $this->_WCAs->FloYIG . "\r\n";
            $ret .= "FHIYIG=" . $this->_WCAs->FhiYIG . "\r\n";

            $powerLimit = $this->maxSafePowerForBand($band);

            if ($powerLimit == 0)
                $ret .= "ENTRIES=0\r\n";
            else {
                $table = $this->Compute_MaxSafePowerLevels();
                $ret .= "ENTRIES=" . count($table) . "\r\n";

                $entry = 0;
                foreach ($table as $row) {
                    $entry++;
                    $ret .= "ENTRY_$entry=" . $row['YTO'] . ", " . number_format($row['VD0'], 2, '.', '') . ", " .
                        number_format($row['VD1'], 2, '.', '') . "\r\n";
                }
            }
            $ret .= "\r\n";
        }
        return $ret;
    }

    public function GetXmlFileContent() {
        $band = $this->Band;
        $sn   = ltrim($this->SN, '0');
        if (strlen($sn) == 1)
            $sn = "0" . $sn;
        $esn  = $this->ESN1;
        $esnDec = hexdec($esn);
        $longSn = "WCA$band-$sn";
        $FLOYIG = $this->_WCAs->FloYIG;
        $FHIYIG = $this->_WCAs->FhiYIG;
        $powerLimit = $this->maxSafePowerForBand($band);
        $lowlo = $this->GetLowLOForBand($band);

        $xw = new XMLWriter();
        $xw->openMemory();
        $xw->setIndent(true);
        $xw->setIndentString('    ');
        $xw->startDocument('1.0', 'ISO-8859-1');
        $xw->startElement("ConfigData");
        //         $xw->writeAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        //         $xw->writeAttribute("xsi:noNamespaceSchemaLocation", "membuffer.xsd");

        $xw->startElement("ASSEMBLY");
        $xw->writeAttribute("value", "WCA$band");
        $xw->endElement();

        $xw->startElement("WCAConfig");
        $xw->writeAttribute("value", $this->keyId);
        // make the MySQL timestamp into an ISO 8601 standard timestamp:
        $xw->writeAttribute("timestamp", strtr($this->TS, ' ', 'T'));
        $xw->endElement();

        $xw->startElement("ESN");
        $xw->writeAttribute("value", $esn);
        $xw->endElement();

        $xw->startElement("SN");
        $xw->writeAttribute("value", $longSn);
        $xw->endElement();

        if ($band > 1) {
            $xw->startElement("ColdMultiplier");
            $mults = array(
                1,  // band 0: no multiplier
                1,  // band 1
                1,  // band 2
                1,  // band 3
                2,  // band 4
                2,  // band 5
                3,  // band 6
                3,  // band 7
                6,  // band 8
                9,  // band 9
                9   // band 10
            );
            $xw->writeAttribute("value", $mults[$band]);
            $xw->endElement();

            $xw->startElement("PLLLoopBwMultiplier");
            $mults = array(
                1,  // band 0: no multiplier
                1,  // band 1
                4,  // band 2
                6,  // band 3
                3,  // band 4
                6,  // band 5
                6,  // band 6
                6,  // band 7
                3,  // band 8
                3,  // band 9
                6   // band 10
            );
            $xw->writeAttribute("value", $mults[$band]);
            $xw->endElement();
        }

        $xw->startElement("FLOYIG");
        $xw->writeAttribute("value", number_format(floatval($FLOYIG), 4) . "E9");  // Hz
        $xw->endElement();

        $xw->startElement("FHIYIG");
        $xw->writeAttribute("value", number_format(floatval($FHIYIG), 4) . "E9");  // Hz
        $xw->endElement();

        $r = $this->db_pull->q(4, $this->keyId);
        $count = 0;
        while ($row = mysqli_fetch_array($r)) {
            $xw->startElement("PowerAmp");
            $xw->writeAttribute("FreqLO", number_format(floatval($row['FreqLO']), 1) . "E9");   // Hz
            $xw->writeAttribute("VD0", number_format(floatval($row['VDP0']), 2));
            $xw->writeAttribute("VD1", number_format(floatval($row['VDP1']), 2));
            $xw->writeAttribute("VG0", number_format(floatval($row['VGP0']), 2));
            $xw->writeAttribute("VG1", number_format(floatval($row['VGP1']), 2));
            $xw->endElement();
            $count += 1;
        }

        if (!$count) {
            $xw->startElement("PowerAmp");
            $xw->writeAttribute("FreqLO", number_format(floatval($lowlo), 1) . "E9");   // Hz
            $xw->writeAttribute("VD0", "0.00");
            $xw->writeAttribute("VD1", "0.00");
            $xw->writeAttribute("VG0", number_format(floatval($this->_WCAs->VG0), 2));
            $xw->writeAttribute("VG1", number_format(floatval($this->_WCAs->VG1), 2));
            $xw->endElement();
        }

        $table = $this->Compute_MaxSafePowerLevels();
        foreach ($table as $row) {
            $xw->startElement("PowerAmpLimit");
            $xw->writeAttribute("count", $row['YTO']);
            $xw->writeAttribute("VD0", $row['VD0']);
            $xw->writeAttribute("VD1", $row['VD1']);
            $xw->endElement();
        }

        $xw->startElement("OptimizationTargets");
        $xw->writeAttribute("FreqLO", number_format(floatval($lowlo), 1) . "E9");   // Hz
        $xw->writeAttribute("PhotoMixerCurrent", "0");
        $xw->endElement();

        $xw->endElement(); // ConfigData
        $xw->endDocument();
        $ret = $xw->outputMemory();
        return $ret;
    }

    public function Display_LOParams() {
        $r = $this->db_pull->q(3, $this->keyId);
        $ts = ADAPT_mysqli_result($r, 0, 0);
        $band = $this->Band;
        $sn = $this->SN;
        $name = ($band == 1) ? "LO" : "WCA";

        echo "<div style= 'width: 500px'>
            <table id = 'table1' border = '1'>";
        echo "<tr class='alt'><th colspan = '5'>
            <font size = '+1'>LO PARAMS for $name $band-$sn <i>($ts)</font></i></th></tr>
            <tr>
                <th>LO (GHz)</th>
                <th>VDP0</th>
                <th>VDP1</th>
                <th>VGP0</th>
                <th>VGP1</th>
            </tr>";

        $r = $this->db_pull->q(4, $this->keyId);
        $count = 0;
        while ($row = mysqli_fetch_array($r)) {
            if ($count % 2 == 0) {
                echo "<tr>";
            } else {
                echo "<tr class = 'alt'>";
            }
            echo "<td>" . $row['FreqLO'] . "</td>";
            echo "<td>" . $row['VDP0'] . "</td>";
            echo "<td>" . $row['VDP1'] . "</td>";
            echo "<td>" . $row['VGP0'] . "</td>";
            echo "<td>" . $row['VGP1'] . "</td></tr>";
            $count += 1;
        }
        echo "</table></div>";
    }
    public function maxSafePowerForBand($band) {
        // define max safe power limit per band:
        // TODO: move into specs class.
        $spec = $this->new_spec->getSpecs('wca', $this->Band);
        return $spec['maxSafeOutput_mW'];
    }
    private function findMaxSafeRows($allRows) {
        // $allRows is an array of arrays where each row has:
        // FreqLO, VD, Power
        // sorted by FreqLO, VD
        // The final row must be an 'EOF' row where FreqLO has a invalid value != FALSE
        // see example $eof row in Compute_MaxSafePowerLevels()
        //
        // Outputs an array of arrays with same structure with one row per LO,
        // having the highest Power level found less than $powerLimit.
        $powerLimit = $this->maxSafePowerForBand($this->Band);

        $output = array();
        $lastLO = FALSE;
        $lastRow = FALSE;
        $found = FALSE;

        foreach ($allRows as $row) {
            $LO = $row['FreqLO'];
            $pwr = $row['Power'];

            // starting a new LO chunk?
            if ($LO != $lastLO) {
                // not first row of table?
                if ($lastLO !== FALSE) {
                    // next LO in table or EOF. Save max safe values found:
                    $output[] = $lastRow;
                    $lastRow = FALSE;
                    $found = FALSE;
                }
                // save for next iter on this LO chunk:
                $lastLO = $LO;
            }

            // found excessive power?
            else if ($pwr > $powerLimit) {
                // yes. Preserve lastRow for rest of this LO chunk:
                if ($powerLimit > 0 && $lastRow['Power'] <= $powerLimit) {
                    $found = TRUE;
                }
            }

            // found max safe?
            if (!$found) {
                // no. move to next row:
                $lastRow = $row;
            }
        }
        return $output;
    }
    private function loadPowerData($pol) {
        // Load the output power data for one polarization, coarse and fine combined:
        $r = $this->db_pull->q(5, NULL, $pol, $this->fc, $this->tdh_outputpower->keyId);

        $allRows = array();

        if ($r) {
            while ($row = mysqli_fetch_array($r))
                $allRows[] = $row; // append row to allRows.
        }
        return $allRows;
    }
    private function loadMaxDrainVoltages() {
        $ret = array();
        $tdh = $this->tdh_outputpower->keyId;

        // Load and return an array having the maximum drain voltages
        // found for Pol0 and Pol1 in the fine output power data.
        $q = "SELECT MAX(VD0) FROM WCA_OutputPower WHERE
              Pol = 0 AND keyDataSet=2
              AND fkHeader = $tdh";

        $r = mysqli_query($this->dbConnection, $q);
        $row = mysqli_fetch_array($r);
        $ret[0] = $row[0];

        $q = "SELECT MAX(VD1) FROM WCA_OutputPower WHERE
              Pol = 1 AND keyDataSet=2
              AND fkHeader = $tdh";

        $r = mysqli_query($this->dbConnection, $q);
        $row = mysqli_fetch_array($r);
        $ret[1] = $row[0];

        return $ret;
    }
    public function Compute_MaxSafePowerLevels() {
        $eof = array(
            'FreqLO' => 'EOF',
            'VD' => 0,
            'Power' => 0
        );

        $this->maxSafePowerTable = array();

        // load pol0 power data:
        $allRows = $this->loadPowerData(0);

        // quit now if there's no data:
        if (!$allRows || count($allRows) == 0)
            return $this->maxSafePowerTable;

        // append dummy EOF record:
        $allRows[] = $eof;

        // compute the max safe power table:
        $pol0table = $this->findMaxSafeRows($allRows);

        // load pol1 power data:
        $allRows = $this->loadPowerData(1);

        // append dummy EOF record:
        $allRows[] = $eof;

        // compute the max safe power table:
        $pol1table = $this->findMaxSafeRows($allRows);

        // compute scaling factors to convert drain voltages into control values:
        $vdMax = $this->loadMaxDrainVoltages();
        $pol0scale = 2.5 / $vdMax[0];
        $pol1scale = 2.5 / $vdMax[1];

        // define warm multiplication factor per band.
        $spec = $this->new_spec->getSpecs('wca', $this->Band);
        $warmMult = $spec['warmMult'];

        $loYig = $this->_WCAs->FloYIG;
        $hiYig = $this->_WCAs->FhiYIG;

        // combine the two tables into one output table:
        $flags = MultipleIterator::MIT_NEED_ANY | MultipleIterator::MIT_KEYS_NUMERIC;
        $iterator = new MultipleIterator($flags);
        $iterator->attachIterator(new ArrayIterator($pol0table));
        $iterator->attachIterator(new ArrayIterator($pol1table));

        $tableWithDups = array();
        $tableSize = 0;

        foreach ($iterator as $values) {
            // var_dump($values);

            $LO = $values[0]['FreqLO'];
            if ($hiYig > $loYig)
                $YIG0 = (!$hiYig) ? 0 : round(((($LO / $warmMult) - $loYig) / ($hiYig - $loYig)) * 4095);
            else
                $YIG0 = $loYig;
            $VD0 = round($values[0]['VD'] * $pol0scale, 4);
            $VD1 = round($values[1]['VD'] * $pol1scale, 4);
            $P0 = round($values[0]['Power'], 1);
            $P1 = round($values[1]['Power'], 1);

            // append to array:
            $tableWithDups[] = array(
                'FreqLO' => $LO,
                'YTO' => $YIG0,
                'VD0' => $VD0,
                'VD1' => $VD1,
                'Pwr0' => $P0,
                'Pwr1' => $P1
            );
            // increment size:
            $tableSize++;
        }

        // Remove redundant rows:
        for ($index = 0; $index < $tableSize; $index++) {
            // Always output first and last row:
            if ($index == 0 || $index == $tableSize - 1) {
                $this->maxSafePowerTable[] = $tableWithDups[$index];

                // Output any row which differs from previous row in VD0 or VD1:
            } else if (
                $tableWithDups[$index]['VD0'] != $tableWithDups[$index - 1]['VD0'] ||
                $tableWithDups[$index]['VD1'] != $tableWithDups[$index - 1]['VD1']
            ) {

                $this->maxSafePowerTable[] = $tableWithDups[$index];
            }
        }
        return $this->maxSafePowerTable;
    }
    public function Display_MaxSafePowerLevels() {
        $band = $this->Band;
        $powerLimit = $this->maxSafePowerForBand($band);
        $sn = $this->SN;
        $ts = $this->TS;

        if ($band != 1) {
            echo '
            <div style= "width:400px">
              <table id = "table1" align="left" cellspacing="1" cellpadding="1" width="60%">
                <tr class="alt">
                  <th align = "center" colspan = "6"><font size="+1">';

            echo "WCA $band-$sn &nbsp;&nbsp;&nbsp; $ts<br><b>MAX SAFE OPERATING PARAMETERS";

            if ($powerLimit > 0)
                echo '<br>limit=' . $powerLimit . ' mW';

            echo '<b></th></tr>';
            echo '
                <tr>
                  <th><b>FreqLO (GHz)</b></th>
                  <th><b>YTO Tuning Word</b></th>
                  <th><b>Digital Setting VD0</b></th>
                  <th><b>Digital Setting VD1</b></th>
                  <th><b>Power Pol0 (mW)</b></th>
                  <th><b>Power Pol1 (mW)</b></th>
                </tr>';

            if (count($this->maxSafePowerTable) > 0) {
                $bg_color = FALSE;
                foreach ($this->maxSafePowerTable as $row) {
                    $bg_color = ($bg_color == "#ffffff" ? '#dddddd' : "#ffffff");
                    echo "<tr bgcolor='$bg_color'>";
                    echo "<td>" . $row['FreqLO'] . "</td>";
                    echo "<td>" . $row['YTO'] . "</td>";
                    echo "<td>" . $row['VD0'] . "</td>";
                    echo "<td>" . $row['VD1'] . "</td>";
                    echo "<td>" . $row['Pwr0'] . "</td>";
                    echo "<td>" . $row['Pwr1'] . "</td></tr>";
                }
            }
            echo "</table></div>";
        }
    }
    public function Display_uploadform() {
        $band = $this->Band;
        $where = $_SERVER['PHP_SELF'];
        $where = '';
        echo '
        <div style="width:600px;margin-top:20px;margin-left:30px;">
        <!-- The data encoding type, enctype, MUST be specified as below -->
        <form enctype="multipart/form-data" action="' . $where . '" method="POST">
            <!-- MAX_FILE_SIZE must precede the file input field -->
            <!-- <input type="hidden" name="MAX_FILE_SIZE" value="100000" /> -->
            <!-- Name of input element determines name in $_FILES array -->
            <table id="table1"><tr class="alt"><th>Upload CSV Data files</th><th>Draw Plots</th></tr>
                <tr><td align = "right">WCAs file:           </b><input name="file_wcas" type="file" /></td><td></td></tr>';
        echo '<tr><td align = "right">Amplitude Stability: </b><input name="file_amplitudestability" type="file" /></td>
                    <td align = "left"><input type="submit" name="draw_amplitudestability" value="Redraw Amp. Stability"></td></tr>';
        if ($band != 1) {
            echo '<tr><td align = "right">AM Noise:            </b><input name="file_amnoise" type="file" /></td>
                    <td align = "left"><input type="submit" name="draw_amnoise" value="Redraw AM Noise"></td></tr>';
        }
        echo '<tr><td align = "right">Output Power:        </b><input name="file_outputpower" type="file" /></td>
                    <td align = "left"><input type="submit" name="draw_outputpower" value="Redraw Output Power">';
        if ($band == 7) {
            echo '<br><label class="switch">Teledyne PA: X is ctrl scalar&nbsp;<input type="checkbox" name="has_teledyne_pa"';
            if (isset($_REQUEST['has_teledyne_pa']))
                echo ' checked';
            echo '></label>';
        }
        echo '<br><label>Override spec freqs like "69-93"<input type="text" name="speclines_override"></label>';
        echo '</td>';
        echo '</tr>
                <tr><td align = "right">Phase Noise:         </b><input name="file_phasenoise" type="file" /></td>
                    <td align = "left"><input type="submit" name="draw_phasenoise" value="Redraw Phase Noise"></td></tr>';
        if ($band == 1) {
            echo '<tr><td align = "right">Isolation:           </b><input name="file_isolation" type="file" /></td>
                    <td align = "center"><input type="submit" name="draw_isolation" value="Redraw Isolation"></td></tr>';
        }
        echo '<tr><td align = "right">';
        echo "<input type='hidden' name= 'fc' value='$this->fc' />";
        if ($this->keyId != '') echo "<input type='hidden' name= 'keyId' value='$this->keyId' />";
        echo '<input type="submit" name= "submit_datafile" value="Upload All" /></td>
                    <td align = "left"><input type="submit" name="draw_all" value="REDRAW ALL PLOTS"></td></tr>
            </table>
        </form>
        </div>';
    }
    public function RequestValues_WCA() {
        parent::RequestValues();

        if (isset($_REQUEST['deleterecord_forsure'])) {
            $this->DeleteRecord_WCA();
        }

        if (isset($_REQUEST['fc'])) {
            $this->fc = $_REQUEST['fc'];
        }

        if (isset($_REQUEST['FloYIG'])) {
            $this->_WCAs->FloYIG = $_REQUEST['FloYIG'];
        }
        if (isset($_REQUEST['FhiYIG'])) {
            $this->_WCAs->FhiYIG = $_REQUEST['FhiYIG'];
        }
        if (isset($_REQUEST['VG0'])) {
            $this->_WCAs->VG0 = $_REQUEST['VG0'];
        }
        if (isset($_REQUEST['VG1'])) {
            $this->_WCAs->VG1 = $_REQUEST['VG1'];
        }
        if (isset($_REQUEST['submit_datafile'])) {
            if (isset($_FILES['file_wcas']['name'])) {
                if ($this->keyId == "") return false;
                if ($_FILES['file_wcas']['name'] != "") {
                    if ($this->Upload_WCAs_file($_FILES['file_wcas']['tmp_name']))
                        $this->Update_WCA();
                    else
                        return false;
                }
            }
            if (isset($_FILES['file_amplitudestability']['name'])) {
                if ($_FILES['file_amplitudestability']['name'] != "") {
                    $this->Upload_AmplitudeStability_file($_FILES['file_amplitudestability']['tmp_name']);
                    $this->Plot_AmplitudeStability();
                }
            }
            if (isset($_FILES['file_amnoise']['name'])) {
                if ($_FILES['file_amnoise']['name'] != "") {
                    $this->Upload_AMNoise_file($_FILES['file_amnoise']['tmp_name']);
                    $this->Plot_AMNoise();
                }
            }
            if (isset($_FILES['file_phasenoise']['name'])) {
                if ($_FILES['file_phasenoise']['name'] != "") {
                    $this->Upload_PhaseNoise_file($_FILES['file_phasenoise']['tmp_name']);
                    $this->Plot_PhaseNoise();
                }
            }
            if (isset($_FILES['file_outputpower']['name'])) {
                if ($_FILES['file_outputpower']['name'] != "") {
                    $this->Upload_OutputPower_file($_FILES['file_outputpower']['tmp_name']);
                    $this->Plot_OutputPower();
                }
            }
            if (isset($_FILES['file_isolation']['name'])) {
                if ($_FILES['file_isolation']['name'] != "") {
                    $this->Upload_Isolation_file($_FILES['file_isolation']['tmp_name']);
                }
            }
        }
        if (isset($_REQUEST['draw_all'])) {
            $this->RedrawAllPlots();
        } else {
            if (isset($_REQUEST['draw_amnoise'])) {
                $this->Plot_AMNoise();
            }
            if (isset($_REQUEST['draw_outputpower'])) {
                $this->Plot_OutputPower();
            }
            if (isset($_REQUEST['draw_amplitudestability'])) {
                $this->Plot_AmplitudeStability();
            }
            if (isset($_REQUEST['draw_phasenoise'])) {
                $this->Plot_PhaseNoise();
            }
            if (isset($_REQUEST['draw_isolation'])) {
                $this->Plot_Isolation();
            }
        }

        $this->Update_WCA();
        $this->AddNewLOParams();
        if (isset($_REQUEST['exportcsv_amplitudestability'])) {
            $this->ExportCSV("amplitudestability");
        }
        return true;
    }
    private function DeleteRecord_WCA() {
        $this->db_pull->qDel($this->keyId, 'WCAs', TRUE);
        $this->db_pull->qDel($this->tdh_amnoise->keyId, 'WCA_AMNoise');
        $this->db_pull->qDel($this->keyId, 'WCA_MaxSafePower', TRUE);
        $this->db_pull->qDel($this->keyId, 'WCA_LOParams', TRUE);
        $this->db_pull->qDel($this->tdh_outputpower->keyId, 'WCA_OutputPower');
        $this->db_pull->qDel($this->tdh_phasenoise->keyId, 'WCA_PhaseNoise');
        $this->db_pull->qDel($this->tdh_phasejitter->keyId, 'WCA_PhaseJitter');
        $this->db_pull->qDel($this->tdh_ampstab->keyId, 'WCA_AmplitudeStability');
        $this->db_pull->qDel($this->tdh_isolation->keyId, 'WCA_Isolation');
        $this->db_pull->qDel($this->keyId, 'FE_ConfigLink', TRUE);
        parent::Delete_record();
        echo '<meta http-equiv="Refresh" content="1;url=wca_main.php">';
    }
    private function WCA_PA_Mapping_Swap($band) {
        // Return true if PA_A is Pol1 and PA_B is Pol0.
        switch ($band) {
            case 1:
                return false;
                break;
            case 2:
                return false;
                break;
            case 3:
                return true;
                break;
            case 4:
                return true;
                break;
            case 5:
                return false;
                break;
            case 6:
                return false;
                break;
            case 7:
                return false;
                break;
            case 8:
                return true;
                break;
            case 9:
                return true;
                break;
            case 10:
                return true;
                break;
            default:
                break;
        }
        return false;
    }
    private function Upload_WCAs_file($datafile_name) {
        $ret = false;
        $filecontents = file($datafile_name);

        if (!$filecontents)
            return false;

        for ($i = 0; $i < sizeof($filecontents); $i++) {
            $line_data = trim($filecontents[$i]);
            $tempArray = explode(",", $line_data);
            $quotes = '"\'';
            $band = trim($tempArray[0], $quotes);
            $newSN = trim($tempArray[1], $quotes);
            $oldSN = trim($this->SN, $quotes);
            if (is_numeric(substr($band, 0, 1))) {
                if ($oldSN != "" && $newSN != $oldSN) {
                    $this->AddError("Upload blocked:");
                    $this->AddError("Serial number '$newSN' doesn't match '$oldSN'.");
                    $ret = false;
                } else {
                    $this->SetValue('Band', $band);
                    $this->SetValue('SN', $newSN);
                    $this->SetValue('TS', date("Y-m-d H:i:s"));
                    $this->SetValue('ESN1', trim($tempArray[3], $quotes));
                    $this->Update();
                    $this->_WCAs->FhiYIG = trim($tempArray[4], $quotes);
                    $this->_WCAs->FloYIG = trim($tempArray[5], $quotes);
                    if ($this->WCA_PA_Mapping_Swap($band)) {
                        $this->_WCAs->VG1 = trim($tempArray[6], $quotes);
                        $this->_WCAs->VG0 = trim($tempArray[7], $quotes);
                    } else {
                        $this->_WCAs->VG0 = trim($tempArray[6], $quotes);
                        $this->_WCAs->VG1 = trim($tempArray[7], $quotes);
                    }
                    $this->_WCAs->Update();
                    // Get rid of any existing LO Params.  Will get re-created on refresh.
                    $r = $this->db_pull->q(7, $this->keyId);
                    $ret = true;
                }
            }
        }
        unlink($datafile_name);
        return $ret;
    }
    public function UploadConfiguration($datafile_name, $datafile_tmpname) {
        $this->SubmittedFileName = $datafile_name;
        $this->SubmittedFileTmp = $datafile_tmpname;

        $filenamearr = explode(".", $this->SubmittedFileName);
        $this->SubmittedFileExtension = strtolower($filenamearr[count($filenamearr) - 1]);

        if ($this->SubmittedFileExtension == 'ini') {
            $this->Update_Configuration_From_INI($this->SubmittedFileTmp);
        } else if ($this->SubmittedFileExtension == 'xml') {
            $this->Update_Configuration_From_ALMA_XML($this->SubmittedFileTmp);
        } else {
            $this->AddError("Error: Unable to upload file $this->SubmittedFileName.");
        }
    }
    private function Update_Configuration_From_INI($INIfile) {
        $ini_array = parse_ini_file($INIfile, true);
        $sectionname = '~WCA' . $this->Band . "-" . $this->SN;
        $CheckBand = $ini_array[$sectionname]['Band'];
        $wcafound = false;
        if ($CheckBand == $this->Band) {
            $wcafound = true;
        }
        if (!$wcafound) {
            // Warn the user that WCA not found in file:
            $this->AddError("WCA " . $this->SN . " not found in this file!  Upload aborted.");
        } else {
            // Remove this WCA from the front end
            $dbops = new DBOperations();

            // Preserve these values in the new SLN record
            $oldStatus = $this->sln->fkStatusType;
            $oldLocation = $this->sln->fkLocationNames;

            // Get old status and location for the front end
            $wcaFE = new FrontEnd(NULL, $this->FEfc, FrontEnd::INIT_SLN, $this->FEConfig);
            $this->GetFEConfig();
            $oldStatusFE = $wcaFE->fesln->fkStatusType;
            $oldLocationFE = $wcaFE->fesln->fkLocationNames;
            $dbops->RemoveComponentFromFrontEnd($this->keyFacility, $this->keyId, '', -1, -1);
            $FEid_old = $this->FEid;
            $this->GetFEConfig();

            // Create new component record, duplicate everything from the existing.
            // Save old key value
            $keyIdOLD = $this->keyId;
            $this->DuplicateRecord_WCA();
            $keyIdNEW = $this->keyId;

            // Copy Max Safe Operating Parameters
            $keys = array();
            $keys['old'] = $keyIdOLD;
            $keys['new'] = $keyIdNEW;
            $this->db_pull->q_other('MS', NULL, NULL, NULL, NULL, NULL, $keys);

            // Notes for the SLN record of new component
            $Notes = "Configuration changed on " . date('r') . ". ";

            // Get rid of any existing LO Params
            $r = $this->db_pull->q(7, $this->keyId);
            // Get rid of any existing WCAs table records
            $r = $this->db_pull->q(8, $this->keyId);

            // Read INI file
            $NumLOParams = $ini_array[$sectionname]['LOParams'];
            for ($i = 1; $i <= $NumLOParams; $i++) {
                if ($i < 10) {
                    $LOkeyname = "LOParam0$i";
                }
                if ($i >= 10) {
                    $LOkeyname = "LOParam$i";
                }

                $LOkeyArray = explode(',', $ini_array[$sectionname][$LOkeyname]);
                $FreqLO = $LOkeyArray[0];
                $VDP0 = $LOkeyArray[1];
                $VDP1 = $LOkeyArray[2];
                $VGP0 = $LOkeyArray[3];
                $VGP1 = $LOkeyArray[4];

                $qnew = "INSERT INTO WCA_LOParams(fkComponent,FreqLO,VDP0,VDP1,VGP0,VGP1) ";
                $qnew .= " VALUES('$this->keyId','$FreqLO','$VDP0','$VDP1','$VGP0','$VGP1');";
                $rnew = $this->db_pull->run_query($qnew);

                if ($i == 1) {
                    $VG0 = $VGP0;
                    $VG1 = $VGP1;
                }
            }
            $FLOYIG = $ini_array[$sectionname]['FLOYIG'];
            $FHIYIG = $ini_array[$sectionname]['FHIYIG'];

            // Copy Yig settings
            $rYIG = $this->db_pull->q_other('YIG', $this->keyId);
            $YIGnumrows = mysqli_num_rows($rYIG);

            if ($YIGnumrows > 0) {
                $this->_WCAs->FloYIG = $FLOYIG;
                $this->_WCAs->FhiYIG = $FHIYIG;
                $this->_WCAs->VG0 = $VG0;
                $this->_WCAs->VG1 = $VG1;
                $this->_WCAs->Update();
            }
            if ($YIGnumrows < 1) {
                $qwcas = "INSERT INTO WCAs(fkFE_Component,FloYIG,FhiYIG,VG0,VG1) ";
                $qwcas .= "VALUES('$this->keyId','$FLOYIG','$FHIYIG','$VG0','$VG1');";
                $rwcas = $this->db_pull->run_query($qwcas);
            }

            // Done reading from INI file.
            $updatestring = "Updated config for WCA " . $this->Band . "-" . $this->SN . ".";

            // Add WCA to Front End
            $feconfig = $this->FEfc;
            $dbops->AddComponentToFrontEnd($FEid_old, $this->keyId, $this->FEfc, $this->keyFacility, '', $updatestring, ' ', -1);
            $dbops->UpdateStatusLocationAndNotes_Component($this->fc, $oldStatus, $oldLocation, $updatestring, $this->keyId, ' ', '');
            $this->GetFEConfig();
            $dbops->UpdateStatusLocationAndNotes_FE($this->FEfc, $oldStatusFE, $oldLocationFE, $updatestring, $this->FEConfig, $this->FEConfig, ' ', '');
            unset($dbops);
        } // end if (wcafound)
        unlink($INIfile);
    }
    private function Update_Configuration_From_ALMA_XML($XMLfile) {
        $ConfigData = simplexml_load_file($XMLfile);
        $found = false;
        if ($ConfigData) {
            $assy = (string) $ConfigData->ASSEMBLY['value'];
            list($band) = sscanf($assy, "WCA%d");
            if ($band && $band == $this->Band)
                $found = true;
        }
        if (!$found) {
            // Warn user that CCA not found in the file
            $this->AddError("WCA band " . $this->Band . " not found in this file!  Upload aborted.");
        } else {
            // Remove this WCA from the front end
            $dbops = new DBOperations();

            // Preserve these values in the new SLN record
            $oldStatus = $this->sln->fkStatusType;
            $oldLocation = $this->sln->fkLocationNames;

            // Get old status and location for the front end
            $wcaFE = new FrontEnd(NULL, $this->FEfc, FrontEnd::INIT_SLN, $this->FEConfig);
            $this->GetFEConfig();
            $oldStatusFE = $wcaFE->fesln->fkStatusType;
            $oldLocationFE = $wcaFE->fesln->fkLocationNames;
            $dbops->RemoveComponentFromFrontEnd($this->keyFacility, $this->keyId, '', -1, -1);
            $FEid_old = $this->FEid;
            $this->GetFEConfig();

            // Create new component record, duplicate everything from the existing.
            // Save old key value
            $keyIdOLD = $this->keyId;
            $this->DuplicateRecord_WCA();
            $keyIdNEW = $this->keyId;

            // Copy Max Safe Operating Parameters
            $keys = array();
            $keys['old'] = $keyIdOLD;
            $keys['new'] = $keyIdNEW;
            $this->db_pull->q_other('MS', NULL, NULL, NULL, NULL, NULL, $keys);

            // Notes for the SLN record of new component
            $Notes = "Configuration changed on " . date('r') . ". ";

            // Get rid of any existing LO Params
            $r = $this->db_pull->q(7, $this->keyId);
            // Get rid of any existing WCAs table records
            $r = $this->db_pull->q(8, $this->keyId);

            // Get LO params array indexed by LO string:
            $LOParams = array();
            foreach ($ConfigData->PowerAmp as $param) {
                $FreqLO = ((float) $param['FreqLO']) / 1E9;
                $VD0 = (float) $param['VD0'];
                $VD1 = (float) $param['VD1'];
                $VG0 = (float) $param['VG0'];
                $VG1 = (float) $param['VG1'];

                $qnew = "INSERT INTO WCA_LOParams(fkComponent,FreqLO,VDP0,VDP1,VGP0,VGP1) ";
                $qnew .= " VALUES('$this->keyId','$FreqLO','$VD0','$VD1','$VG0','$VG1');";
                $rnew = $this->db_pull->run_query($qnew);
            }
            $FLOYIG = ((float) $ConfigData->FLOYIG['value']) / 1E9;
            $FHIYIG = ((float) $ConfigData->FHIYIG['value']) / 1E9;

            // Copy Yig settings
            $rYIG = $this->db_pull->q_other('YIG', $this->keyId);
            $YIGnumrows = mysqli_num_rows($rYIG);

            if ($YIGnumrows > 0) {
                $this->_WCAs->FloYIG = $FLOYIG;
                $this->_WCAs->FhiYIG = $FHIYIG;
                $this->_WCAs->VG0 = $VG0;
                $this->_WCAs->VG1 = $VG1;
                $this->_WCAs->Update();
            }
            if ($YIGnumrows < 1) {
                $qwcas = "INSERT INTO WCAs(fkFE_Component,FloYIG,FhiYIG,VG0,VG1) ";
                $qwcas .= "VALUES('$this->keyId','$FLOYIG','$FHIYIG','$VG0','$VG1');";
                $rwcas = $this->db_pull->run_query($qwcas);
            }

            // Done reading from XML file.
            $updatestring = "Updated config for WCA " . $this->Band . "-" . $this->SN . ".";

            // Add WCA to Front End
            $feconfig = $this->FEfc;
            $dbops->AddComponentToFrontEnd($FEid_old, $this->keyId, $this->FEfc, $this->keyFacility, '', $updatestring, ' ', -1);
            $dbops->UpdateStatusLocationAndNotes_Component($this->fc, $oldStatus, $oldLocation, $updatestring, $this->keyId, ' ', '');
            $this->GetFEConfig();
            $dbops->UpdateStatusLocationAndNotes_FE($this->FEfc, $oldStatusFE, $oldLocationFE, $updatestring, $this->FEConfig, $this->FEConfig, ' ', '');
            unset($dbops);
        }
        unlink($XMLfile);
    }
    private function DuplicateRecord_WCA() {
        parent::DuplicateRecord();
        // Copy the records for LO Params
        if ($this->LOParams) {
            for ($i = 0; $i < count($this->LOParams); $i++) {
                if ($this->LOParams[$i]->keyId > 0) {
                    $this->LOParams[$i]->DuplicateRecord();
                }
            }
        }
        if ($this->_WCAs->keyId > 0) {
            $this->_WCAs->DuplicateRecord();
        }
    }
    private function Upload_AmplitudeStability_file($datafile_name) {
        // Test Data Header object
        // Delete any existing header records
        $this->db_pull->qtdh('delete', $this->keyId, 'WCA_AmplitudeStability');
        $this->tdh_ampstab = GenericTable::NewRecord("TestData_header", 'keyId', $this->keyFacility, 'keyFacility');
        $this->tdh_ampstab->SetValue('fkTestData_Type', 45);
        $this->tdh_ampstab->SetValue('fkDataStatus', $this->fkDataStatus);
        $this->tdh_ampstab->SetValue('fkFE_Components', $this->keyId);
        $this->tdh_ampstab->Update();

        $filecontents = file($datafile_name);
        $this->db_pull->del_ins('WCA_AmplitudeStability', $filecontents, $this->tdh_ampstab);
        unlink($datafile_name);
    }
    public function Plot_AmplitudeStability() {
        if (!file_exists($this->writedirectory)) {
            mkdir($this->writedirectory);
        }

        if (!$this->tdh_ampstab->keyId)
            return;

        // write data file from database
        $rFindLO = $this->db_pull->qFindLO('WCA_AmplitudeStability', $this->tdh_ampstab->keyId);
        if (!$rFindLO)
            $image_url = "";

        else {
            $LOArray = mysqli_fetch_all($rFindLO);

            if (!$LOArray)
                $image_url = "";

            else {
                $datafile_count = 0;
                foreach ($LOArray as $LORow) {
                    $LO = $LORow[0];
                    for ($pol = 0; $pol <= 1; $pol++) {

                        $r = $this->db_pull->q(9, $this->tdh_ampstab->keyId, $pol, NULL, NULL, $LO);

                        if ($r && mysqli_num_rows($r) > 1) {
                            $plottitle[$datafile_count] = "Pol $pol, $LO GHz";
                            $data_file[$datafile_count] = $this->writedirectory . "wca_as_data_" . $LO . "_" . $pol . ".txt";
                            if (file_exists($data_file[$datafile_count])) {
                                unlink($data_file[$datafile_count]);
                            }
                            $fh = fopen($data_file[$datafile_count], 'w');
                            $row = mysqli_fetch_array($r);
                            while ($row = mysqli_fetch_array($r)) {
                                $stringData = "$row[0]\t$row[1]\r\n";
                                fwrite($fh, $stringData);
                            }
                            fclose($fh);
                            $datafile_count++;
                        }
                    }
                }

                // Write command file for gnuplot
                $TS = $this->tdh_ampstab->TS;

                $plot_command_file = $this->writedirectory . "wca_as_command.txt";
                if (file_exists($plot_command_file)) {
                    unlink($plot_command_file);
                }
                $imagedirectory = $this->writedirectory . $this->Band . "_" . $this->SN . "/";
                if (!file_exists($imagedirectory)) {
                    mkdir($imagedirectory);
                }
                $imagename = "WCA_AmplitudeStability_SN" . $this->SN . "_" . date("Ymd_G_i_s") . ".png";
                $image_url = $this->url_directory . $this->Band . "_" . $this->SN . "/$imagename";

                if ($this->Band == 1)
                    $plot_title = "LO ";
                else
                    $plot_title = "WCA ";
                $plot_title .= "Band" . $this->Band . " SN" . $this->SN . " Amplitude Stability ($TS)";
                $this->_WCAs->amp_stability_url = $image_url;
                $this->_WCAs->Update();
                $imagepath = $imagedirectory . $imagename;

                $fh = fopen($plot_command_file, 'w');
                fwrite($fh, "set terminal png size 900,500\r\n");
                if ($GNUPLOT_VER >= 5.0)
                    fwrite($fh, "set colorsequence classic\r\n");
                fwrite($fh, "set output '$imagepath'\r\n");
                fwrite($fh, "set title '$plot_title'\r\n");
                fwrite($fh, "set grid\r\n");
                fwrite($fh, "set log xy\r\n");
                fwrite($fh, "set key outside\r\n");
                fwrite($fh, "set ylabel 'Allan Variance'\r\n");
                fwrite($fh, "set xlabel 'Allan Time, T (=Integration, Tau) [ms]'\r\n");

                $ymax = pow(10, -5);
                fwrite($fh, "set yrange [:$ymax]\r\n");
                fwrite($fh, "set format y \"%.2e\"\r\n");

                fwrite($fh, "f1(x)=((x>500) && (x<100000)) ? 0.00000009 : 1/0\r\n");
                fwrite($fh, "f2(x)=((x>290000) && (x<350000)) ? 0.000001 : 1/0\r\n");
                $plot_string = "plot f1(x) title 'Spec' with lines lw 3";
                $plot_string .= ", f2(x) title 'Spec' with points pt 5 pointsize 1";
                $plot_string .= ", '$data_file[0]' using 1:2 title '$plottitle[0]' with lines";
                for ($i = 1; $i < sizeof($data_file); $i++) {
                    $plot_string .= ", '$data_file[$i]' using 1:2 title '$plottitle[$i]' with lines";
                }
                $plot_string .= "\r\n";
                fwrite($fh, $plot_string);

                fclose($fh);

                // Make the plot
                require(site_get_config_main());
                $CommandString = "$GNUPLOT $plot_command_file";
                system($CommandString);
            }
        }
        $this->tdh_ampstab->SetValue('PlotURL', "$image_url");
        $this->tdh_ampstab->Update();
    }
    private function Upload_AMNoise_file($datafile_name) {
        // Test Data Header object
        // Delete any existing header records
        $rtdh = $this->db_pull->qtdh('delete', $this->keyId, 'WCA_AMNoise');
        $this->tdh_amnoise = GenericTable::NewRecord("TestData_header", 'keyId', $this->keyFacility, 'keyFacility');
        $this->tdh_amnoise->SetValue('fkTestData_Type', 44);
        $this->tdh_amnoise->SetValue('fkDataStatus', $this->fkDataStatus);
        $this->tdh_amnoise->SetValue('fkFE_Components', $this->keyId);
        $this->tdh_amnoise->Update();

        $filecontents = file($datafile_name);
        $this->db_pull->del_ins('WCA_AMNoise', $filecontents, $this->tdh_amnoise);
        unlink($datafile_name);
    }
    public function Plot_AMNoise() {
        if (!file_exists($this->writedirectory)) {
            mkdir($this->writedirectory);
        }
        $this->Plot_AMNoise_DSB();
        $this->Plot_AMNoise_Pol0_1();
    }
    private function Plot_AMNoise_DSB() {
        if (!$this->tdh_amnoise->keyId)
            return;

        $TS = $this->tdh_amnoise->TS;
        $Band = $this->Band;
        $spec_value = 10;

        $FreqLOW = 4;
        $FreqHI = 8;

        if ($Band == '6') {
            $FreqLOW = 6;
            $FreqHI = 10;
        }

        if ($Band == '10') {
            $FreqLOW = 4;
            $FreqHI = 12;
        }

        // Note, using 4-8 GHz for band 9 intentionally since it may become 2SB in the future and the worst noise
        // contribution is in the lower half.

        $imagedirectory = $this->writedirectory;
        if (!file_exists($imagedirectory)) {
            mkdir($imagedirectory);
        }
        $imagename = "WCA_AMNoiseDSB_SN" . $this->SN . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . $imagename;
        if ($this->Band == 1)
            $plot_title = "LO ";
        else
            $plot_title = "WCA ";
        $plot_title .= "Band" . $this->Band . " SN" . $this->SN . " AM Noise ($TS)";
        $this->_WCAs->amnz_avgdsb_url = $image_url;
        $this->_WCAs->Update();
        $imagepath = $imagedirectory . $imagename;

        $amnzarr[0] = "";
        for ($pol = 0; $pol <= 1; $pol++) {
            unset($amnzarr);
            $arrct = 0;
            // Get X axis values
            $rFreqLO = $this->db_pull->q_other('FreqLO', $this->tdh_amnoise->keyId, $this->fc, $pol, $FreqLOW, $FreqHI);
            while ($row = mysqli_fetch_array($rFreqLO)) {
                $amnzarr[0][$arrct] = $row[0];
                $amnzarr[1][$arrct] = $row[1];
                $arrct += 1;
            }

            $arrct = 0;
            $rlo = $this->db_pull->qlo('WCA_AMNoise', $this->tdh_amnoise->keyId, $this->fc, FALSE, $FreqLOW, $FreqHI, $pol);
            while ($rowlo = mysqli_fetch_array($rlo)) {
                $freqarr[$arrct] = $rowlo[0];
                $arrct += 1;
            }

            $data_file[$pol] = $this->writedirectory . "wca_amnoise_data$pol.txt";
            if (file_exists($data_file[$pol])) {
                unlink($data_file[$pol]);
            }
            $fh = fopen($data_file[$pol], 'w');

            $plotmax = "";

            for ($i = 0; $i < count($freqarr); $i++) {
                $avgamnz = $this->GetAvgAMNoise($amnzarr, $freqarr[$i]);
                $stringData = "$freqarr[$i]\t$avgamnz\r\n";
                fwrite($fh, $stringData);
                if ($avgamnz > 9) {
                    $plotmax = "12";
                }
            }
            unset($amnzarr);
            fclose($fh);
        } // end for pol

        // Write command file for gnuplot
        $plot_command_file = $this->writedirectory . "wca_amnz_command.txt";
        if (file_exists($plot_command_file)) {
            unlink($plot_command_file);
        }
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 700,500\r\n");
        if ($GNUPLOT_VER >= 5.0)
            fwrite($fh, "set colorsequence classic\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set xlabel 'LO Frequency (GHz)'\r\n");
        fwrite($fh, "set ylabel 'Average DSB NSR (K/uW)'\r\n");
        fwrite($fh, "set key outside\r\n");

        // Spec line
        fwrite($fh, "f1(x)= 10\r\n");

        $plot_string = "plot '$data_file[0]' using 1:2 title 'Pol 0' with lines lt 6";
        $plot_string .= ", '$data_file[1]' using 1:2 title 'Pol 1' with lines lt 3\r\n";

        fwrite($fh, $plot_string);
        fclose($fh);

        // Make the plot
        $GNUPLOT = $this->GNUplot;
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
    }
    private function GetAvgAMNoise($amnzarr, $freqlo) {
        $sum = 0;
        $count = 0;
        for ($i = 0; $i < count($amnzarr[0]); $i++) {
            if ($amnzarr[0][$i] == $freqlo) {
                $sum += $amnzarr[1][$i];
                $count += 1;
            }
        }
        $avg = $sum / $count;
        return $avg;
    }
    private function Plot_AMNoise_Pol0_1() {
        if (!$this->tdh_amnoise->keyId)
            return;

        $TS = $this->tdh_amnoise->TS;
        for ($pol = 0; $pol <= 1; $pol++) {

            $imagedirectory = $this->writedirectory;
            if (!file_exists($imagedirectory)) {
                mkdir($imagedirectory);
            }
            $imagename = "WCA_AMNoisePol$pol" . "_SN" . $this->SN . "_" . date("Ymd_G_i_s") . ".png";
            $image_url = $this->url_directory . $imagename;
            if ($this->Band == 1)
                $plot_title = "LO ";
            else
                $plot_title = "WCA ";
            $plot_title .= "Band" . $this->Band . " SN" . $this->SN . " AM Noise Pol $pol ($TS)";
            $this->_WCAs->SetValue("amnz_pol" . $pol . "_url", $image_url);
            $this->_WCAs->Update();
            $imagepath = $imagedirectory . $imagename;

            $rNumIF = $this->db_pull->q_other('NumIF', $this->tdh_amnoise->keyId, $this->fc, $pol);
            $NumIF = mysqli_num_rows($rNumIF);

            $data_file[$pol] = $this->writedirectory . "wca_amnoise_data_pol$pol.txt";
            if (file_exists($data_file[$pol])) {
                unlink($data_file[$pol]);
            }
            $fh = fopen($data_file[$pol], 'w');

            $IFcount = 0;
            $r = $this->db_pull->q(10, $this->tdh_amnoise->keyId, $pol, $this->fc);
            while ($row = mysqli_fetch_array($r)) {
                $stringData = "$row[0]\t$row[1]\t$row[2]\r\n";
                fwrite($fh, $stringData);
                $IFcount += 1;
                if ($IFcount == $NumIF) {
                    fwrite($fh, "\r\n");
                    $IFcount = 0;
                }
            }
            fclose($fh);

            $amtitle = "AMNoise Pol $pol";
            // Command file
            $plot_command_file = $this->writedirectory . "wca_as_command.txt";
            if (file_exists($plot_command_file)) {
                unlink($plot_command_file);
            }
            $fhc = fopen($plot_command_file, 'w');

            fwrite($fhc, "set output '$imagepath'\r\n");
            fwrite($fhc, "set pm3d map\r\n");
            fwrite($fhc, "set palette model RGB defined (0 'black', 2 'blue', 4 'green', 6 'yellow', 8 'orange', 10 'red')\r\n");
            fwrite($fhc, "set terminal png crop\r\n");
            if ($GNUPLOT_VER >= 5.0)
                fwrite($fh, "set colorsequence classic\r\n");
            fwrite($fhc, "set title '$plot_title'\r\n");
            fwrite($fhc, "set xlabel 'IF (GHz)'\r\n");
            fwrite($fhc, "set ylabel 'LO Frequency (GHz)'\r\n");
            fwrite($fhc, "set cblabel 'NSR (K/uW)' \r\n");
            fwrite($fhc, "set view map\r\n");
            fwrite($fhc, "set cbrange[0:]\r\n");

            $plot_string = "splot '$data_file[$pol]' using 1:2:3 title ''\r\n";
            fwrite($fhc, $plot_string);
            fclose($fhc);

            // Make the plot
            $GNUPLOT = $this->GNUplot;

            $CommandString = "$GNUPLOT $plot_command_file";
            system($CommandString);
        } // end for loop pol
    }
    private function Upload_PhaseNoise_file($datafile_name) {
        // Test Data Header object
        // Delete any existing header records
        $rtdh = $this->db_pull->qtdh('delete', $this->keyId, 'WCA_PhaseJitter', $this->fc);
        $rtdh = $this->db_pull->qtdh('delete', $this->keyId, 'WCA_PhaseNoise', $this->fc);
        $this->tdh_phasenoise = GenericTable::NewRecord("TestData_header", 'keyId', $this->keyFacility, 'keyFacility');
        $this->tdh_phasenoise->SetValue('fkTestData_Type', 48);
        $this->tdh_phasenoise->SetValue('fkDataStatus', $this->fkDataStatus);
        $this->tdh_phasenoise->SetValue('fkFE_Components', $this->keyId);
        $this->tdh_phasenoise->Update();
        $this->tdh_phasejitter = GenericTable::NewRecord("TestData_header", 'keyId', $this->keyFacility, 'keyFacility');
        $this->tdh_phasejitter->SetValue('fkTestData_Type', 47);
        $this->tdh_phasejitter->SetValue('fkDataStatus', $this->fkDataStatus);
        $this->tdh_phasejitter->SetValue('fkFE_Components', $this->keyId);
        $this->tdh_phasejitter->Update();

        $filecontents = file($datafile_name);
        $this->db_pull->del_ins('WCA_PhaseNoise', $filecontents, $this->tdh_phasenoise, $this->fc, $this->tdh_phasejitter);
        unlink($datafile_name);
    }
    public function Plot_PhaseNoise() {
        if (!$this->tdh_phasenoise->keyId)
            return;

        if (!file_exists($this->writedirectory)) {
            mkdir($this->writedirectory);
        }
        $TS = $this->tdh_phasenoise->TS;

        $loindex = 0;

        $this->db_pull->qpj('delete', $this->tdh_phasejitter->keyId, $this->fc);

        $rlo = $this->db_pull->qlo('WCA_PhaseNoise', $this->tdh_phasenoise->keyId, $this->fc);

        while ($rowlo = mysqli_fetch_array($rlo)) {
            $lo = $rowlo[0];
            $pol = $rowlo[1];
            $jitterarray[$loindex] = $this->GetPhaseJitter($lo, $pol);
            $values = array();
            $values[] = $lo;
            $values[] = $pol;
            $values[] = $jitterarray[$loindex];

            $this->db_pull->qpj('insert', $this->tdh_phasejitter->keyId, $this->fc, $values);

            $loindex += 1;
        }

        // write data file from database
        $rFindLO = $this->db_pull->qFindLO('WCA_PhaseNoise', $this->tdh_phasenoise->keyId, $this->fc);
        $rowLO = mysqli_fetch_array($rFindLO);

        $datafile_count = 0;
        for ($j = 0; $j <= 1; $j++) {
            for ($i = 0; $i <= sizeof($rowLO); $i++) {
                $CurrentLO = ADAPT_mysqli_result($rFindLO, $i);

                $r = $this->db_pull->q(11, $this->tdh_phasenoise->keyId, $j, $this->fc, NULL, $CurrentLO);

                if (mysqli_num_rows($r) > 1) {
                    $plottitle[$datafile_count] = "Pol $j, $CurrentLO GHz";
                    $data_file[$datafile_count] = $this->writedirectory . "wca_phasenz_" . $i . "_" . $j . ".txt";
                    if (file_exists($data_file[$datafile_count])) {
                        unlink($data_file[$datafile_count]);
                    }
                    $fh = fopen($data_file[$datafile_count], 'w');
                    $row = mysqli_fetch_array($r);

                    while ($row = mysqli_fetch_array($r)) {
                        $stringData = "$row[0]\t$row[1]\r\n";
                        fwrite($fh, $stringData);
                    }
                    fclose($fh);
                    $datafile_count++;
                }
            } // end for i
        } // end for j

        $imagedirectory = $this->writedirectory;

        if (!file_exists($imagedirectory)) {
            mkdir($imagedirectory);
        }
        $imagename = "WCA_PhaseNoise_SN" . $this->SN . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . $imagename;
        if ($this->Band == 1)
            $plot_title = "LO ";
        else
            $plot_title = "WCA ";
        $plot_title .= "Band" . $this->Band . " SN" . $this->SN . " Phase Noise ($TS)";
        $this->_WCAs->phasenoise_url = $image_url;
        $this->_WCAs->Update();
        $imagepath = $imagedirectory . $imagename;

        // Write command file for gnuplot
        $plot_command_file = $this->writedirectory . "wca_pn_command.txt";
        if (file_exists($plot_command_file)) {
            unlink($plot_command_file);
        }
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 900,500\r\n");
        if ($GNUPLOT_VER >= 5.0)
            fwrite($fh, "set colorsequence classic\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set log x\r\n");
        fwrite($fh, "set yrange [-140:-40]\r\n");
        fwrite($fh, "set xrange [10:10000000]\r\n");

        fwrite($fh, "set xlabel 'f (Hz)'\r\n");
        fwrite($fh, "set ylabel 'L(f) [dBc/Hz]'\r\n");
        fwrite($fh, "set key outside\r\n");
        $plot_string = "plot '$data_file[0]' using 1:2 title '$plottitle[0]' with lines";
        for ($i = 1; $i < sizeof($data_file); $i++) {
            $plot_string .= ", '$data_file[$i]' using 1:2 title '$plottitle[$i]' with lines";
        }
        $plot_string .= "\r\n";
        fwrite($fh, $plot_string);
        fclose($fh);

        // Make the plot
        $GNUPLOT = $this->GNUplot;
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
    }
    private function Upload_Isolation_file($datafile_name) {
        // Delete any existing header records
        $rtdh = $this->db_pull->qtdh('delete', $this->keyId, 'WCA_Isolation', $this->fc);

        $this->tdh_isolation = GenericTable::NewRecord("TestData_header", 'keyId', $this->keyFacility, 'keyFacility');
        $this->tdh_isolation->SetValue('fkTestData_Type', 61);
        $this->tdh_isolation->SetValue('fkDataStatus', $this->fkDataStatus);
        $this->tdh_isolation->SetValue('fkFE_Components', $this->keyId);
        $this->tdh_isolation->Update();
        $fileContents = file($datafile_name);

        $this->db_pull->del_ins('WCA_Isolation', $fileContents, $this->tdh_isolation, NULL, NULL, " ");
        unlink($datafile_name);
    }

    private function Plot_Isolation() {
        if (!$this->tdh_isolation->keyId)
            return;
        if (!file_exists($this->writedirectory))
            mkdir($this->writedirectory);

        $r = $this->db_pull->q(16, $this->tdh_isolation->keyId);
        if (!$r)
            return;

        $band = $this->Band;
        $specs = $this->new_spec->getSpecs('wca', $band);
        $specIsolation = $specs['specIsolation'];

        $data_file = $this->writedirectory . "wca_isolation.txt";
        if (file_exists($data_file))
            unlink($data_file);

        $fMin = 999.0;
        $fMax = 0.0;
        $TS = false;
        $fh = fopen($data_file, 'w');
        while ($row = mysqli_fetch_array($r)) {
            $LO = floatval($row[1]);
            fwrite($fh, "$LO\t$row[2]\t$row[3]\r\n");
            if ($LO < $fMin)
                $fMin = $LO;
            if ($LO > $fMax)
                $fMax = $LO;
            if (!$TS)
                $TS = $row[0];
        }
        fclose($fh);

        // Create command file for GnuPlot:
        $imagedirectory = $this->writedirectory;
        if (!file_exists($imagedirectory)) {
            mkdir($imagedirectory);
        }
        $imagename = "WCA_Isolation_SN" . $this->SN . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . $imagename;

        $plot_title = "LO$band-" . $this->SN . " Isolation  $TS";
        $this->_WCAs->isolation_url = $image_url;
        $this->_WCAs->Update();
        $imagepath = $imagedirectory . $imagename;

        // Write command file for gnuplot
        $plot_command_file = $this->writedirectory . "wca_iso_command.txt";
        if (file_exists($plot_command_file)) {
            unlink($plot_command_file);
        }
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 900,500\r\n");
        if ($GNUPLOT_VER >= 5.0)
            fwrite($fh, "set colorsequence classic\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set xrange [$fMin:$fMax]\r\n");

        fwrite($fh, "set xlabel 'LO (GHz)'\r\n");
        fwrite($fh, "set ylabel 'Isolation (dB)'\r\n");
        fwrite($fh, "set key outside\r\n");
        $plot_string = "plot $specIsolation title 'spec' with lines lw 4 lt 1 ";
        $plot_string .= ", '$data_file' using 1:2 title 'S12' with lines";
        $plot_string .= ", '$data_file' using 1:3 title 'S21' with lines";
        $plot_string .= "\r\n";
        fwrite($fh, $plot_string);
        fclose($fh);

        // Make the plot
        $GNUPLOT = $this->GNUplot;
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
    }

    private function GetPhaseJitter($LOfreq, $pol) {
        $counter = 0;

        $sumraw = 0;
        $sumantilog1 = 0;
        $sumtrap1 = 0;
        $sumgbe = 0;
        $sum_applygbe = 0;
        $sumantilog2 = 0;
        $sumtrap2 = 0;

        $r = $this->db_pull->q(12, $this->tdh_phasenoise->keyId, $pol, $this->fc, NULL, NULL, $LOfreq);

        $GbE_Carrier = $LOfreq * pow(10, 9);
        $GbE_Pole = 1875000;

        $RawData = -1234;

        while ($row = mysqli_fetch_array($r)) {
            $RawData_temp = $row[2];
            $OffsetFrequency_temp = $row[0];
            $AntiLog_temp = pow(10.0, $RawData_temp / 10.0);

            $GBEfilter_temp = 20 * log10($OffsetFrequency_temp / ($GbE_Pole * sqrt((1 + pow($OffsetFrequency_temp / $GbE_Pole, 2)))));
            $sumgbe += $GBEfilter_temp;

            $applygbe_temp = $RawData_temp + $GBEfilter_temp;
            $antilog2_temp = pow(10, $applygbe_temp / 10);

            if ($counter > 0) {
                $trap1 = 0.5 * ($OffsetFrequency_temp - $OffsetFrequency) * ($AntiLog_temp + $AntiLog);
                $sumtrap1 += $trap1;
                $trap2 = 0.5 * ($OffsetFrequency_temp - $OffsetFrequency) * ($antilog2_temp + $antilog2);
                $sumtrap2 += $trap2;
            }

            $AntiLog = $AntiLog_temp;
            $OffsetFrequency = $OffsetFrequency_temp;
            $RawData = $RawData_temp;
            $GBEfilter = $GBEfilter_temp;

            $sumraw += $RawData;
            $sumantilog1 += $AntiLog;

            $applygbe = $RawData + $GBEfilter_temp;
            $sum_applygbe += $applygbe;
            $antilog2 = pow(10, $applygbe / 10);
            $sumantilog2 += $antilog2;
            $counter += 1;
        }

        $Integration = $sumtrap1;
        $Phi = sqrt(2 * $Integration);
        $PhaseJitter = $Phi / (2 * 3.14159 * $GbE_Carrier);
        $PhaseJitter *= pow(10, 15);
        return $PhaseJitter;
    }
    private function Upload_OutputPower_file($datafile_name) {
        // Test Data Header object
        // Delete any existing header records
        $rtdh = $this->db_pull->qtdh('delete', $this->keyId, 'WCA_OutputPower');
        $this->tdh_outputpower = GenericTable::NewRecord("TestData_header", 'keyId', $this->keyFacility, 'keyFacility');
        $this->tdh_outputpower->SetValue('fkTestData_Type', 46);
        $this->tdh_outputpower->SetValue('fkDataStatus', $this->fkDataStatus);
        $this->tdh_outputpower->SetValue('fkFE_Components', $this->keyId);
        $this->tdh_outputpower->Update();

        $filecontents = file($datafile_name);
        $this->db_pull->del_ins('WCA_OutputPower', $filecontents, $this->tdh_outputpower, $this->fc);
        unlink($datafile_name);
    }
    public function Plot_OutputPower() {
        if (!file_exists($this->writedirectory)) {
            mkdir($this->writedirectory);
        }
        $this->Plot_OutputPower_vs_frequency();
        if ($this->Band == 1) {
            $this->Plot_OutputPower_vs_Vd_B1(0);
            $this->Plot_OutputPower_vs_Vd_B1(1);
        } else {
            $this->Plot_OutputPower_vs_Vd(0);
            $this->Plot_OutputPower_vs_Vd(1);
            $this->Plot_OutputPower_vs_stepsize(0);
            $this->Plot_OutputPower_vs_stepsize(1);
        }
    }
    private function Plot_OutputPower_vs_frequency() {
        if (!$this->tdh_outputpower->keyId)
            return;

        // Get VD0, VD1 settings from first row:
        $r = $this->db_pull->q_other('VD01', $this->tdh_outputpower->keyId, $this->fc);
        $row = mysqli_fetch_array($r);
        $VD0 = $row[0];
        $VD1 = $row[1];
        $VG0 = $this->_WCAs->VG0;
        $VG1 = $this->_WCAs->VG1;
        
        $Band = $this->Band;
        $TS = $this->tdh_outputpower->TS;

        $imagedirectory = $this->writedirectory;

        if (!file_exists($imagedirectory)) {
            mkdir($imagedirectory);
        }
        $imagename = "WCA_OPvsFreq_SN" . $this->SN . "_" . date("Ymd_G_i_s") . ".png";

        $image_url = $this->url_directory . $imagename;

        $plot_title = "WCA Band" . $this->Band . " SN" . $this->SN . " Output Power Vs. Frequency (VD0=$VD0, VD1=$VD1, VG0=$VG0, VG1=$VG1) ($TS)";

        $this->_WCAs->op_vs_freq_url = $image_url;
        $this->_WCAs->Update();
        $imagepath = $imagedirectory . $imagename;
        $data_file = array();

        for ($pol = 0; $pol <= 1; $pol++) {
            $data_file[$pol] = $this->writedirectory . "wca_opvsfreq_data$pol.txt";
            if (file_exists($data_file[$pol])) {
                unlink($data_file[$pol]);
            }
            $fh = fopen($data_file[$pol], 'w');
            $rOP = $this->db_pull->q_other('OP', $this->tdh_outputpower->keyId, $this->fc, $pol);
            while ($row = mysqli_fetch_array($rOP)) {
                $stringData = "$row[0]\t$row[1]\r\n";
                fwrite($fh, $stringData);
            }
            fclose($fh);
        }

        $plot_command_file = $this->writedirectory . "wca_opvsfreq_command.txt";

        if (file_exists($plot_command_file)) {
            unlink($plot_command_file);
        }
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 1000,600 medium\r\n");
        if ($GNUPLOT_VER >= 5.0)
            fwrite($fh, "set colorsequence classic\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");

        fwrite($fh, "set yrange[0:]\r\n");

        // set xrange
        $rx = $this->db_pull->q_other('x', $this->tdh_outputpower->keyId, $this->fc);
        $xMAX = ADAPT_mysqli_result($rx, 0) + 1;
        fwrite($fh, "set xrange[:$xMAX]\r\n");

        fwrite($fh, "set xlabel 'LO Frequency (GHz)'\r\n");
        fwrite($fh, "set ylabel 'Output Power (mW)'\r\n");

        fwrite($fh, "set key outside\r\n");

        $specLine1 = $specLine2 = false;
        if (isset($_REQUEST['speclines_override'])) {
            $str = $_REQUEST['speclines_override'];
            if (trim($str)) {
                $items = explode('-', $str, 3);
                $min = $items[0];
                $max = $items[1];
                $specLine1 = "f1(x)=((x>$min) && (x<$max)) ? 20 : 1/0";
                $specLine2 = "f2(x)=((x>$min) && (x<$max)) ? 53 : 1/0";
            }
        }
        
        $specs = $this->new_spec->getSpecs('wca', $Band);
        $i = 1;
        $done = false;
        $plot_string = "";
        while (!$done) {
            $specLineName = "specLine$i";
            $plotStringName = "plot_string$i";
            if (!isset($specs[$specLineName]))
                $done = true;
            else {
                if ($i == 1) {
                    $plot_string .= "plot ";
                } else {
                    $plot_string .= ", ";
                }
                $lineCmd = $specs[$specLineName];
                if ($i == 1 && $specLine1)
                    $lineCmd = $specLine1;
                else if ($i == 2 && $specLine2)
                    $lineCmd = $specLine2;
                fwrite($fh, $lineCmd . "\r\n");
                $plot_string .= $specs[$plotStringName];
            }
            $i++;
        }

        $plot_string .= ", '$data_file[0]' using 1:2 title 'Pol 0' with lines ";
        $plot_string .= ", '$data_file[1]' using 1:2 title 'Pol 1' with lines ";
        $plot_string .= "\r\n";

        fwrite($fh, $plot_string);
        fclose($fh);

        // Make the plot
        $GNUPLOT = $this->GNUplot;
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
    }

    private function Plot_OutputPower_vs_Vd_B1($pol) {
        // Plot output power vs drain voltage, formatted for band 1 reports.
        if (!$this->tdh_outputpower->keyId)
            return;

        // Get the spec line level and description:
        $band = $this->Band;
        $specs = $this->new_spec->getSpecs('wca', $band);
        $spec_value_1 = $specs['spec_value_1'];
        $spec_description_1 = $specs['spec_description_1'];

        // Find the LO frequencies present in the raw data:
        $datafile_count = 0;
        $rFindLO = $this->db_pull->qFindLO('WCA_OutputPower', $this->tdh_outputpower->keyId, $this->fc, $pol, '<> 1');
        $rowLO = mysqli_fetch_array($rFindLO);
        $i = 0;
        $minVd = 99.0;
        $maxVd = 0.0;
        $vg = -1.0;
        $TS = false;

        $data_file = array();

        if ($rFindLO) {
            while ($rowLO = mysqli_fetch_array($rFindLO)) {
                $CurrentLO = ADAPT_mysqli_result($rFindLO, $i);

                $r = $this->db_pull->q(14, $this->tdh_outputpower->keyId, $pol, $this->fc, NULL, $CurrentLO);

                if (mysqli_num_rows($r) > 1) {
                    $plottitle[$datafile_count] = "$CurrentLO GHz";
                    $data_file[$datafile_count] = $this->writedirectory . "wca_op_vs_dv_" . $i . "_" . $pol . ".txt";
                    if (file_exists($data_file[$datafile_count])) {
                        unlink($data_file[$datafile_count]);
                    }

                    $fh = fopen($data_file[$datafile_count], 'w');
                    $row = mysqli_fetch_array($r);
                    while ($row = mysqli_fetch_array($r)) {
                        $vd = $row[0];
                        $powerdBm = 10 * log($row[1] * 1000, 10);
                        $stringData = "$vd\t$powerdBm\r\n";
                        fwrite($fh, $stringData);
                        if ($vd < $minVd)
                            $minVd = $vd;
                        if ($vd > $maxVd)
                            $maxVd = $vd;
                        if ($row[2] > $vg)
                            $vg = $row[2];
                        if (!$TS)
                            $TS = $row[3];
                    }
                    fclose($fh);
                    $datafile_count++;
                }
                $i++;
            }
        }

        $imagedirectory = $this->writedirectory;
        if (!file_exists($imagedirectory)) {
            mkdir($imagedirectory);
        }
        $imagename = "WCA_OPvsVd_SN" . $this->SN . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . $imagename;
        sleep(1);

        $this->_WCAs->SetValue("op_vs_dv_pol$pol" . "_url", $image_url);
        $this->_WCAs->Update();
        $imagepath = $imagedirectory . $imagename;

        // Write command file for gnuplot
        $plot_title = "LO$band-" . $this->SN . " Pol $pol Output Power $TS";
        $plot_command_file = $this->writedirectory . "wca_op_vd_command.txt";
        if (file_exists($plot_command_file)) {
            unlink($plot_command_file);
        }
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 900,500\r\n");
        if ($GNUPLOT_VER >= 5.0)
            fwrite($fh, "set colorsequence classic\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        $vg = number_format($vg, 2);
        fwrite($fh, "set xlabel 'VD$pol (Volts) VG=$vg'\r\n");
        fwrite($fh, "set ylabel 'Output Power (dBm)'\r\n");
        $minVd = round($minVd - 0.1, 1);
        $maxVd = round($maxVd + 0.1, 1);
        fwrite($fh, "set xrange [$minVd:$maxVd]\r\n");
        fwrite($fh, "set key outside\r\n");

        // plot the spec lines:
        $plot_string = "plot $spec_value_1 title '$spec_description_1' with lines lw 4 lt 1 ";

        // plot each trace:
        for ($i = 0; $i < sizeof($data_file); $i++) {
            $plot_string .= ", '$data_file[$i]' using 1:2 title '$plottitle[$i]' with linespoints";
        }
        $plot_string .= "\r\n";
        fwrite($fh, $plot_string);

        fclose($fh);

        // Make the plot
        $GNUPLOT = $this->GNUplot;
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
    }

    private function Plot_OutputPower_vs_Vd($pol) {
        if (!$this->tdh_outputpower->keyId)
            return;

        $TS = $this->tdh_outputpower->TS;

        // write data files from database
        $Band = $this->Band;
        $specs = $this->new_spec->getSpecs('wca', $Band);
        $spec_value_1 = $specs['spec_value_1'];
        $spec_description_1 = $specs['spec_description_1'];

        $spec_value_2 = $specs['spec_value_2'];
        $spec_description_2 = $specs['spec_description_2'];
        $enable_spec_2 = $specs['enable_spec_2'];

        $teledynePA = False;
        if (isset($_REQUEST['has_teledyne_pa']))
            $teledynePA = True;

        // Find X-axis max:
        //$plotXMax = $specs['OPvsVD_XMax'];   Getting from data instead of specs now...
        $r = $this->db_pull->q(17, $this->tdh_outputpower->keyId);
        $row = mysqli_fetch_array($r);
        $maxVD0 = ADAPT_mysqli_result($r, 0);
        $maxVD1 = ADAPT_mysqli_result($r, 1);
        // Ceil to nearest 0.5:
        $plotXMax = ceil(max($maxVD0, $maxVD1) * 2) / 2;

        $datafile_count = 0;
        $rFindLO = $this->db_pull->qFindLO('WCA_OutputPower', $this->tdh_outputpower->keyId, $this->fc, $pol, '<> 1');
        $rowLO = mysqli_fetch_array($rFindLO);
        $i = 0;
        $data_file = array();

        if ($rFindLO) {
            while ($rowLO = mysqli_fetch_array($rFindLO)) {
                $CurrentLO = ADAPT_mysqli_result($rFindLO, $i);

                $r = $this->db_pull->q(14, $this->tdh_outputpower->keyId, $pol, $this->fc, NULL, $CurrentLO);

                if (mysqli_num_rows($r) > 1) {
                    $plottitle[$datafile_count] = "$CurrentLO GHz";
                    $data_file[$datafile_count] = $this->writedirectory . "wca_op_vs_dv_" . $i . "_" . $pol . ".txt";
                    if (file_exists($data_file[$datafile_count])) {
                        unlink($data_file[$datafile_count]);
                    }

                    $fh = fopen($data_file[$datafile_count], 'w');
                    $row = mysqli_fetch_array($r);
                    while ($row = mysqli_fetch_array($r)) {
                        $stringData = "$row[0]\t$row[1]\r\n";
                        fwrite($fh, $stringData);
                    }
                    fclose($fh);
                    $datafile_count++;
                }
                $i++;
            } // end for i
        }

        $imagedirectory = $this->writedirectory;
        if (!file_exists($imagedirectory)) {
            mkdir($imagedirectory);
        }
        $imagename = "WCA_OPvsVd_SN" . $this->SN . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . $imagename;
        sleep(1);

        $this->_WCAs->SetValue("op_vs_dv_pol$pol" . "_url", $image_url);
        $this->_WCAs->Update();
        $imagepath = $imagedirectory . $imagename;

        // Write command file for gnuplot
        $plot_title = "WCA Band" . $this->Band . " SN" . $this->SN;
        if ($teledynePA)
            $plot_title .= " Output Power Vs. Control Scalar";
        else
            $plot_title .= " Output Power Vs. Drain Voltage";

        $plot_title .= ": Pol $pol ($TS)";

        $plot_command_file = $this->writedirectory . "wca_op_vd_command.txt";
        if (file_exists($plot_command_file)) {
            unlink($plot_command_file);
        }
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 1000,600\r\n");
        if ($GNUPLOT_VER >= 5.0)
            fwrite($fh, "set colorsequence classic\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        if ($teledynePA) {
            fwrite($fh, "set xlabel 'Control Scalar (0..2.5)'\r\n");
        } else {
            fwrite($fh, "set xlabel 'Drain Voltage'\r\n");
        }
        fwrite($fh, "set ylabel 'Output Power (mW)'\r\n");
        fwrite($fh, "set key outside\r\n");

        if ($plotXMax)
            fwrite($fh, "set xrange[0:$plotXMax]\r\n");

        // plot the spec lines:
        $plot_string = "plot $spec_value_1 title '$spec_description_1' with lines lw 4 lt 1 ";
        if ($enable_spec_2) {
            $plot_string .= ", $spec_value_2 title '$spec_description_2' with lines lw 4 lt 9 ";
        }

        // plot each trace:
        for ($i = 0; $i < sizeof($data_file); $i++) {
            if ($i % 2 == 0) {
                $plot_string .= ", '$data_file[$i]' using 1:2 title '$plottitle[$i]' with lines lw 3";
            }
            if ($i % 2 != 0) {
                $plot_string .= ", '$data_file[$i]' using 1:2 title '$plottitle[$i]' with linespoints";
            }
        }
        $plot_string .= "\r\n";
        fwrite($fh, $plot_string);

        fclose($fh);

        // Make the plot
        $GNUPLOT = $this->GNUplot;
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
    }
    private function Plot_OutputPower_vs_stepsize($pol) {
        if (!$this->tdh_outputpower->keyId)
            return;

        // Get timestamp
        $TS = $this->tdh_outputpower->TS;

        // write data files from database

        $datafile_count = 0;
        $rFindLO = $this->db_pull->qFindLO('WCA_OutputPower', $this->tdh_outputpower->keyId, $this->fc, $pol, '= 3');
        $i = 0;
        $data_file = array();

        if ($rFindLO) {
            while ($rowLO = mysqli_fetch_array($rFindLO)) {
                $CurrentLO = ADAPT_mysqli_result($rFindLO, $i);
                $r = $this->db_pull->q(15, $this->tdh_outputpower->keyId, $pol, $this->fc, NULL, $CurrentLO);

                if (mysqli_num_rows($r) > 1) {
                    $plottitle[$datafile_count] = "$CurrentLO GHz";
                    $data_file[$datafile_count] = $this->writedirectory . "wca_op_vs_ss_" . $i . "_" . $pol . ".txt";
                    if (file_exists($data_file[$datafile_count])) {
                        unlink($data_file[$datafile_count]);
                    }
                    $fh = fopen($data_file[$datafile_count], 'w');
                    $row = mysqli_fetch_array($r);

                    $k = 0;
                    while ($rowSS = mysqli_fetch_array($r)) {
                        $VD_pwr_array[$k] = "$rowSS[0],$rowSS[1]";
                        $VDarray_unsorted[$k] = $rowSS[0];
                        $Pwrarray_unsorted[$k] = $rowSS[1];
                        $tempPwr = $rowSS[1];
                        $k += 1;
                    }
                    sort($VD_pwr_array);
                    for ($arr_index = 0; $arr_index < sizeof($VD_pwr_array); $arr_index++) {
                        $tempArr = explode(",", $VD_pwr_array[$arr_index]);
                        $VDarray[$arr_index] = $tempArr[0];
                        $Pwrarray[$arr_index] = $tempArr[1];
                    }

                    for ($m = 0; $m < sizeof($VDarray); $m++) {

                        if (isset($Pwrarray[$m + 1]) && ($Pwrarray[$m + 1] != $Pwrarray[$m])) {
                            $VDtemp = $VDarray[$m];
                            $ptemp1 = $Pwrarray[$m];
                            $ptemp2 = $Pwrarray[$m + 1];

                            $stepSize = 0;
                            if (($m + 1) <= sizeof($VDarray)) {
                                if ($ptemp1 == 0) {
                                    $stepSize = 0;
                                }
                                if ($ptemp1 != 0) {
                                    $stepSize = 10 * log($ptemp2 / $ptemp1, 10);
                                    if ($stepSize < 0) {
                                        $stepSize = 0;
                                    }
                                    if (($stepSize > 1) && ($ptemp1 > 2)) {
                                        $stepSize = 0;
                                    }
                                }
                            }
                            $stringData = "$Pwrarray[$m]\t$stepSize\r\n";
                            fwrite($fh, $stringData);
                        }
                    }
                    fclose($fh);
                    $datafile_count++;
                }
                $i++;
            } // end for i
        }
        // Get image path

        $imagedirectory = $this->writedirectory;
        if (!file_exists($imagedirectory)) {
            mkdir($imagedirectory);
        }
        $imagename = "WCA_OPvsStepSize_SN" . $this->SN . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . $imagename;
        sleep(1);

        $this->_WCAs->SetValue("op_vs_ss_pol$pol" . "_url", $image_url);
        $this->_WCAs->Update();
        $imagepath = $imagedirectory . $imagename;

        // Write command file for gnuplot
        $plot_title = "WCA Band" . $this->Band . " SN" . $this->SN . " Output Power Vs. Step Size Pol $pol ($TS)";
        $plot_command_file = $this->writedirectory . "wca_op_vs_ss_command.txt";
        if (file_exists($plot_command_file)) {
            unlink($plot_command_file);
        }
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 1000,600\r\n");
        if ($GNUPLOT_VER >= 5.0)
            fwrite($fh, "set colorsequence classic\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set xlabel 'Output Power (mW)'\r\n");
        fwrite($fh, "set ylabel 'Step Size (dB)'\r\n");
        fwrite($fh, "set yrange[0:1]\r\n");
        fwrite($fh, "set key outside\r\n");
        $Band = $this->Band;
        $specs = $this->new_spec->getSpecs('wca', $Band);
        fwrite($fh, $specs['xRangeSS'] . "\r\n");

        $i = 1;
        $done = false;
        $plot_string = "";
        while (!$done) {
            $specLineName = "specLineSS$i";
            $plotStringName = "plotStringSS$i";
            if (!isset($specs[$specLineName]))
                $done = true;
            else {
                if ($i == 1) {
                    $plot_string .= "plot ";
                } else {
                    $plot_string .= ", ";
                }
                fwrite($fh, $specs[$specLineName] . "\r\n");
                $plot_string .= $specs[$plotStringName];
                $i++;
            }
        }
        for ($i = 0; $i < sizeof($data_file); $i++) {
            $plot_string .= ", '$data_file[$i]' using 1:2 title '$plottitle[$i]' with lines";
        }
        $plot_string .= "\r\n";
        fwrite($fh, $plot_string);
        fclose($fh);

        // Make the plot
        $GNUPLOT = $this->GNUplot;
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
    }
    private function RedrawAllPlots() {
        $this->Plot_AmplitudeStability();
        $this->Plot_AMNoise();
        $this->Plot_OutputPower();
        $this->Plot_PhaseNoise();
        $this->Plot_Isolation();
    }
    public function convert_charset($item) {
        if ($unserialize = unserialize($item)) {
            foreach ($unserialize as $key => $value) {
                $unserialize[$key] = @iconv('windows-1256', 'UTF-8', $value);
            }
            $serialize = serialize($unserialize);
            return $serialize;
        } else {
            return @iconv('windows-1256', 'UTF-8', $item);
        }
    }
    private function ExportCSV($datatype) {
        echo '<meta http-equiv="Refresh" content="1;url=export_to_csv.php?keyId=' . $this->keyId . '&datatype=' . $datatype . '">';
    }
}
