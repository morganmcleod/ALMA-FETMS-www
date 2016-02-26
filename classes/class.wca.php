<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.dboperations.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_dBcode . '/../dBcode/wcadb.php');
require_once($site_classes . '/class.spec_functions.php');
require_once($site_dbConnect);

class WCA extends FEComponent{
    var $_WCAs;
    var $LOParams; //array of LO Params (Generic Table objects)
    var $facility;
    var $ConfigId;
    var $ConfigLinkId;
    var $fkDataStatus;
    var $writedirectory;
    var $url_directory;
    var $fc; //facility code
    var $GNUplot; //GNUPlot location
    var $logfile;
    var $logfile_fh;

    var $tdh_amnoise;        //TestData_header record object for AM Noise
    var $tdh_ampstab;        //TestData_header record object for Amplitude Stability
    var $tdh_outputpower;    //TestData_header record object for Output Power
    var $tdh_phasenoise;     //TestData_header record object for Phase Noise
    var $tdh_phasejitter;    //TestData_header record object for Phase Jitter

    var $db_pull;
    var $new_spec;
    var $dbconnection;

    var $maxSafePowerTable;  //Array of rows for the Max Safe Operating Parameters table.

    var $SubmittedFileExtension; //Extension of submitted file for update (csv, ini or zip)

    var $SubmittedFileName; //Uploaded file (csv, zip, ini), base name
    var $SubmittedFileTmp;  //Uploaded file (csv, zip, ini), actual path

    var $ErrorArray; //Array of errors

    function __construct() {
        $this->fkDataStatus = '7';
        $this->swversion = "1.0.7";
        /*
         * 1.0.7 MM Added INIT_Options to Initialize_WCA()
         * 1.0.6 Fix more plotting errors in WCA electronic data upload (step size plots.)
         * 1.0.5 Fix plotting errors in WCA electronic data upload.
         * 1.0.4 Added XML config file upload and fixed related bugs.
         * 1.0.3 calculate max safe power table from output power data in database.
         * 1.0.2 fix "set label...screen" commands to gnuplot
         */

        require(site_get_config_main());
        $this->writedirectory = $wca_write_directory;
        $this->dbconnection = site_getDbConnection();
        $this->db_pull = new WCAdb($this->dbconnection);
        $this->new_spec = new Specifications();
        $this->url_directory = $wca_url_directory;
        $this->GNUplot = $GNUplot;
        $this->ZipDirectory = $this->writedirectory . "zip";
        $this->ErrorArray = array();
    }

    private function AddError($ErrorString){
        $this->ErrorArray[] = $ErrorString;
    }

    const INIT_SLN      = 0x0001;
    const INIT_LOPARAMS = 0x0002;
    const INIT_TESTDATA = 0x0004;

    const INIT_NONE     = 0x0000;
    const INIT_ALL      = 0x001F;

    public function Initialize_WCA($in_keyId, $in_fc, $INIT_Options = self::INIT_ALL){
        $this->fc = $in_fc;
        parent::Initialize_FEComponent($in_keyId, $in_fc);

        $this->writedirectory = $this->writedirectory . "wca". $this->GetValue('Band') . "_" . $this->GetValue('SN') . "/";
        $this->url_directory = $this->url_directory . "wca" . $this->GetValue('Band') . "_" . $this->GetValue('SN') . "/";

        //Get WCA record:
        $rWCA = $this->db_pull->q_other('WCA', $this->keyId);
        $WCAs_id = @mysql_result($rWCA,0);
        $this->_WCAs = New GenericTable();
        $this->_WCAs->Initialize("WCAs", $WCAs_id,"keyId",$this->fc,'fkFacility');

        //Get FE_Config information
        $rcfg = $this->db_pull->q_other('cfg', $this->keyId, $this->fc);
        $this->ConfigId     = @mysql_result($rcfg,0,0);
        $this->ConfigLinkId = @mysql_result($rcfg,0,1);
        $this->FEId         = @mysql_result($rcfg,0,2);
        $this->FESN         = @mysql_result($rcfg,0,3);

        if ($INIT_Options & self::INIT_SLN) {
            //Status location and notes
            $rsln = $this->db_pull->q_other('sln', $this->keyId);
            $slnid = @mysql_result($rsln,0,0);
            $this->sln = new GenericTable();
            $this->sln->Initialize("FE_StatusLocationAndNotes",$slnid,"keyId");
        }

        if ($INIT_Options & self::INIT_LOPARAMS) {
            $r = $this->db_pull->q(1, $this->keyId);
            $lopcount = 1;
            while ($row = @mysql_fetch_array($r)){
                $this->LOParams[$lopcount] = new GenericTable();
                $this->LOParams[$lopcount]->Initialize('WCA_LOParams',$row[0],'keyId',$this->fc,'fkFacility');
                $lopcount += 1;
            }
        }

        if ($INIT_Options & self::INIT_TESTDATA) {
            //Test data header objects
    		$rtdh = $this->db_pull->qtdh('select', $this->keyId, 'WCA_PhaseJitter');
            $this->tdh_phasejitter = new TestData_header();
            $this->tdh_phasejitter->Initialize_TestData_header(@mysql_result($rtdh,0,0), $this->GetValue('keyFacility'));

            $rtdh = $this->db_pull->qtdh('select', $this->keyId, 'WCA_AmplitudeStability');
            $this->tdh_ampstab = new TestData_header();
            $this->tdh_ampstab->Initialize_TestData_header(@mysql_result($rtdh,0,0), $this->GetValue('keyFacility'));

            $rtdh = $this->db_pull->qtdh('select', $this->keyId, 'WCA_OutputPower');
            $this->tdh_outputpower = new TestData_header();
            $this->tdh_outputpower->Initialize_TestData_header(@mysql_result($rtdh,0,0), $this->GetValue('keyFacility'));

            $rtdh = $this->db_pull->qtdh('select', $this->keyId, 'WCA_PhaseNoise');
            $this->tdh_phasenoise = new TestData_header();
            $this->tdh_phasenoise->Initialize_TestData_header(@mysql_result($rtdh,0,0), $this->GetValue('keyFacility'));

            $rtdh = $this->db_pull->qtdh('select', $this->keyId, 'WCA_AMNoise');
            $this->tdh_amnoise = new TestData_header();
            $this->tdh_amnoise->Initialize_TestData_header(@mysql_result($rtdh,0,0), $this->GetValue('keyFacility'));
            /*
            echo "ids...<br>";
            echo "amnoise= " . $this->tdh_amnoise->keyId . "<br>";
            echo "pj= " . $this->tdh_phasejitter->keyId . "<br>";
            echo "pn= " . $this->tdh_phasenoise->keyId . "<br>";
            echo "op= " . $this->tdh_outputpower->keyId . "<br>";
            echo "ampstab= " . $this->tdh_ampstab->keyId . "<br>";
            */
        }
    }

    public function NewRecord_WCA(){
        require(site_get_config_main());
        parent::NewRecord('FE_Components', 'keyId',$fc,'keyFacility');
        parent::SetValue('fkFE_ComponentType',11);
        $this->fc = $fc;
        $this->_WCAs->NewRecord('WCAs');
        $this->_WCAs->fc = $fc;
        $this->_WCAs->SetValue('fkFE_Component',$this->keyId);
        $this->_WCAs->SetValue('keyFacility',$fc);

        $r_status = $this->db_pull->q_other('status', $this->keyId, $this->fc);
    }

    public function AddNewLOParams(){
        $band = $this->GetValue('Band');
        if (empty($band))
            $FreqLO = 0;
        else {
            $specs = $this->new_spec->getSpecs('wca', $band);
            $FreqLO = $specs['FreqLO'];
        }

        $r = $this->db_pull->q(2, $this->keyId);
        $numrows = @mysql_num_rows($r);
        if ($numrows < 1){
            $values = array();
            $values[] = $this->_WCAs->GetValue('VG0');
            $values[] = $this->_WCAs->GetValue('VG1');
            $rn = $this->db_pull->q_other('n', $this->keyId, NULL, NULL, $FreqLO, NULL, NULL, $values);
        }
    }

    public function Update_WCA(){
        parent::Update();
        $this->_WCAs->Update();
    }

    public function DisplayData_WCA(){
        require(site_get_config_main());
        $where = $_SERVER["PHP_SELF"];
        $where = '';
        echo "<form action='" . $where . "' method='POST'>";
        echo "<div style ='width:100%;height:50%;margin-left:30px'>";
        echo "<br><font size='+2'><b>WCA Information</b></font><br>";

        $this->DisplayMainData();

        echo "<br><br>";

        echo "<input type='hidden' name='" . $this->keyId_name . "' value='$this->keyId'>";
        if ($this->fc == ''){
            echo "<input type='hidden' name='fc' value='$fc'>";
        }
        if ($this->fc != ''){
            echo "<input type='hidden' name='fc' value='$this->fc'>";
        }

        echo "<input type='submit' name = 'submitted' value='SAVE CHANGES'>";
        echo "<input type='submit' name = 'deleterecord' value='DELETE RECORD'><br>";

        echo "</div>";
        echo "<div style ='width:100%;height:50%'>";

        if ($this->keyId != ""){
            echo "<table cellspacing='20'>";
            echo "<tr><td>";
            $this->Compute_MaxSafePowerLevels(FALSE);
            $this->Display_MaxSafePowerLevels();
            echo "</td></tr>";
            echo "<tr><td>";
            $this->Display_LOParams();
            echo "<tr><td>";
        }
        echo "</div>";
        echo "<br>";

        if ($this->_WCAs->GetValue('amp_stability_url') != ""){
            echo "<div>";
            echo "<img src='" . $this->_WCAs->GetValue('amp_stability_url') . "'>";
            echo "</div><br><br><br>";
            //echo "<br><input type='submit' name = 'exportcsv_amplitudestability' value='EXPORT TO CSV'>";
        }
        if ($this->_WCAs->GetValue('amnz_avgdsb_url') != ""){
            echo "<br><img src='" . $this->_WCAs->GetValue('amnz_avgdsb_url') . "'>";
            echo "<br><br><br>";
            //echo "<br><input type='submit' name = 'exportcsv_amnz_dsb' value='EXPORT TO CSV'>";
        }
        if ($this->_WCAs->GetValue('amnz_pol0_url') != ""){
            echo "<br><img src='" . $this->_WCAs->GetValue('amnz_pol0_url') . "'>";
            echo "<br><br><br>";
            //echo "<br><input type='submit' name = 'exportcsv_amnz_pol0' value='EXPORT TO CSV'>";
        }
        if ($this->_WCAs->GetValue('amnz_pol1_url') != ""){
            echo "<br><img src='" . $this->_WCAs->GetValue('amnz_pol1_url') . "'>";
            echo "<br><br><br>";
            //echo "<br><input type='submit' name = 'exportcsv_amnz_pol1' value='EXPORT TO CSV'>";
        }

        $this->Display_PhaseNoise();

        if ($this->_WCAs->GetValue('op_vs_freq_url') != ""){
            echo "<br><img src='" . $this->_WCAs->GetValue('op_vs_freq_url') . "'>";
            echo "<br><br><br>";
            //echo "<br><input type='submit' name = 'exportcsv_op_vs_freq' value='EXPORT TO CSV'>";
        }
        if ($this->_WCAs->GetValue('op_vs_dv_pol0_url') != ""){
            echo "<br><img src='" . $this->_WCAs->GetValue('op_vs_dv_pol0_url') . "'>";
            echo "<br><br><br>";
        }
        if ($this->_WCAs->GetValue('op_vs_dv_pol1_url') != ""){
            echo "<br><img src='" . $this->_WCAs->GetValue('op_vs_dv_pol1_url') . "'>";
            echo "<br><br><br>";
        }
        if ($this->_WCAs->GetValue('op_vs_ss_pol0_url') != ""){
            echo "<br><img src='" . $this->_WCAs->GetValue('op_vs_ss_pol0_url') . "'>";
            echo "<br><br><br>";
        }
        if ($this->_WCAs->GetValue('op_vs_ss_pol1_url') != ""){
            echo "<br><img src='" . $this->_WCAs->GetValue('op_vs_ss_pol1_url') . "'>";
            echo "<br><br><br>";
        }
        echo "</form>";
        echo "<br>";
        $this->Display_uploadform();
    }

    public function Display_AmplitudeStability(){
        echo "<img src='" . $this->_WCAs->GetValue('amp_stability_url') . "'>";
    }
    public function Display_AMNoise(){
        echo "<table>";
        echo "<tr><td><img src='" . $this->_WCAs->GetValue('amnz_avgdsb_url') . "'></td></tr>";
        echo "<tr><td><img src='" . $this->_WCAs->GetValue('amnz_pol0_url') . "'></td></tr>";
        echo "<tr><td><img src='" . $this->_WCAs->GetValue('amnz_pol1_url') . "'></td></tr>
        </table>";
    }

    public function Display_OutputPower(){
        echo "<table>";
        echo "<tr><td><img src='" . $this->_WCAs->GetValue('op_vs_freq_url') . "'></td></tr>";
        echo "<tr><td><img src='" . $this->_WCAs->GetValue('op_vs_dv_pol0_url') . "'></td></tr>";
        echo "<tr><td><img src='" . $this->_WCAs->GetValue('op_vs_dv_pol1_url') . "'></td></tr>";
        echo "<tr><td><img src='" . $this->_WCAs->GetValue('op_vs_ss_pol0_url') . "'></td></tr>";
        echo "<tr><td><img src='" . $this->_WCAs->GetValue('op_vs_ss_pol1_url') . "'></td></tr>";
        echo "</table>";
    }
    public function Display_PhaseNoise(){

        echo "<table><tr><td>";
        echo "<img src='" . $this->_WCAs->GetValue('phasenoise_url') . "'>";
        echo "</td></tr><tr><td>";
        echo "<div style = 'width:500px'>
            <table id = 'table2'>
                <tr><th colspan = '3'><b>Phase Jitter</b></td></tr>
                <tr>
                    <td>LO</td>
                    <td>Pol</td>
                    <td>Jitter (fs)</td>
                </tr>
            </div>";

        $rpj = $this->db_pull->qpj('select', $this->tdh_phasejitter->keyId);

        while ($rowpj = @mysql_fetch_array($rpj)){
            $lo = $rowpj[0];
            $jitter = $rowpj[1];
            $pol = $rowpj[2];

            echo "<tr>
                  <td>" . round($lo,0) . "</td>
                  <td>$pol</td>
                  <td>" . round($jitter,1) . "</td>
                </tr>";
        }
        echo "</td></tr></table></div>";
    }

    public function DisplayMainData(){
        echo "<div style = 'width: 300px'><br><br>";
        echo "<table id = 'table1'>";

        echo "<tr>";
        echo "<th>In Front End SN</th>";
        echo "<td><b>";

        if ($this->FESN === FALSE)
            echo "-none-";
        else {
            echo "<a href='https://safe.nrao.edu/php/ntc/FEConfig/ShowFEConfig.php?key=" . $this->ConfigId .
                 "&fc=" . $this->fc . "'>" . $this->FESN . "</a></b></td>";
        }
        echo "</tr>";

        echo "<tr>";
            echo "<th>Band</th>";
            echo "<td><input type='text' name='Band' size='2' maxlength='200' value = '".$this->GetValue('Band')."'></td>";
        echo "</tr>";
        echo "<tr>";
            echo "<th>SN</th>";
            echo "<td><input type='text' name='SN' size='2' maxlength='200' value = '".$this->GetValue('SN')."'></td>";
        echo "</tr>";
        echo "<tr>";
            echo "<th>ESN</th>";
            echo "<td><input type='text' name='ESN1' size='20' maxlength='200' value = '".$this->GetValue('ESN1')."'></td>";
        echo "</tr>";

        echo "<tr>";
            echo "<th>YIG LOW (GHz)</th>";
            echo "<td><input type='text' name='FloYIG' size='5' maxlength='200' value = '".$this->_WCAs->GetValue('FloYIG')."'></td>";
        echo "</tr>";

        echo "<tr>";
            echo "<th>YIG HIGH (GHz)</th>";
            echo "<td><input type='text' name='FhiYIG' size='5' maxlength='200' value = '".$this->_WCAs->GetValue('FhiYIG')."'></td>";
        echo "</tr>";

        echo "<tr>";
            echo "<th>VG0</th>";
            echo "<td><input type='text' name='VG0' size='5' maxlength='200' value = '".$this->_WCAs->GetValue('VG0')."'></td>";
        echo "</tr>";
        echo "<tr>";
            echo "<th>VG1</th>";
            echo "<td><input type='text' name='VG1' size='5' maxlength='200' value = '".$this->_WCAs->GetValue('VG1')."'></td>";
        echo "</tr>";

        echo "<tr>";
            echo "<th>INI file downloads</th>";
            echo "<td>";
            echo "<a href='export_to_ini_wca.php?keyId=$this->keyId&fc=$this->fc&wca=1'>FrontEndControl.ini</a><br>";
            echo "<a href='export_to_ini_wca.php?keyId=$this->keyId&fc=$this->fc&wca=1&type=wca'>FEMC WCA.ini</a>";

            echo "</td>";
        echo "</tr>";

        echo "</table></div>";
        echo "<br>Notes:<input type='text' name='Notes' size='50'
        maxlength='200' value = '".$this->GetValue('Notes')."'>";
    }

    public function DisplayMainDataNonEdit(){
        echo "<div style = 'width: 300px'><br><br>";
        echo "<table id = 'table1'>";

        echo "<tr class='alt'>";
        echo "<th colspan = '2'>
        <font size = '+1'>
        WCA (Band ". $this->GetValue('Band') ." SN ". $this->GetValue('SN') ." )
        </font></th></tr>";

        echo "<tr>";
            echo "<th>In Front End SN</th>";
            echo "<td>".$this->FESN . "</td>";
        echo "</tr>";

        echo "<tr>";
            echo "<th>YIG LOW (GHz)</th>";
            echo "<td>".$this->_WCAs->GetValue('FloYIG')."</td>";
        echo "</tr>";

        echo "<tr>";
            echo "<th>YIG HIGH (GHz)</th>";
            echo "<td>".$this->_WCAs->GetValue('FhiYIG')."</td>";
        echo "</tr>";

        echo "<tr>";
            echo "<th>VG0</th>";
            echo "<td>".$this->_WCAs->GetValue('VG0')."</td>";
        echo "</tr>";
        echo "<tr>";
            echo "<th>VG1</th>";
            echo "<td>".$this->_WCAs->GetValue('VG1')."</td>";
        echo "</tr>";

        echo "</table></div>";
    }

    public function Display_LOParams(){
        /*$q = "SELECT TS FROM WCA_LOParams
            WHERE fkComponent = $this->keyId
            ORDER BY FreqLO ASC
            LIMIT 1;";
        $r = @mysql_query($q,$this->dbconnection); //*/
        $r = $this->db_pull->q(3, $this->keyId);
        $ts = @mysql_result($r,0,0);
        $band = $this->GetValue('Band');
        $sn = $this->GetValue('SN');

        $r = @mysql_query($q,$this->dbconnection);
        echo "<div style= 'width: 500px'>
            <table id = 'table1' border = '1'>";
        echo "<tr class='alt'><th colspan = '5'>
            <font size = '+1'>LO PARAMS WCA $band-$sn <i>($ts)</font></i></th></tr>
            <tr>
                <th>LO (GHz)</th>
                <th>VDP0</th>
                <th>VDP1</th>
                <th>VGP0</th>
                <th>VGP1</th>
            </tr>";

        /*$q = "SELECT * FROM WCA_LOParams
              WHERE fkComponent = $this->keyId
              ORDER BY FreqLO ASC;";
        $r = @mysql_query($q,$this->dbconnection);//*/
        $r = $this->db_pull->q(4, $this->keyId);
        $count = 0;
        while ($row = @mysql_fetch_array($r)){
            if ($count % 2 == 0){
                echo "<tr>";
            }
            else{
                echo "<tr class = 'alt'>";
            }
            echo "<td>" . $row['FreqLO'] . "</td>";
            echo "<td>" .     $row['VDP0'] . "</td>";
            echo "<td>" .     $row['VDP1'] . "</td>";
            echo "<td>" .     $row['VGP0'] . "</td>";
            echo "<td>" .     $row['VGP1'] . "</td></tr>";
            $count += 1;
        }
        echo "</table></div>";
    }

    public function maxSafePowerForBand($band) {
        // define max safe power limit per band:
        // TODO: move into specs class.
        $spec = $this->new_spec->getSpecs('wca', $this->GetValue('Band'));
        return $spec['maxSafeOutput_dBm'];
    }

    private function findMaxSafeRows($allRows) {
        // $allRows is an array of arrays where each row has:
        // FreqLO, VD, Power
        // sorted by FreqLO, VD
        // The final row must be an 'EOF' row where FreqLO has a invalid value != FALSE
        //  see example $eof row in Compute_MaxSafePowerLevels()
        //
        // Outputs an array of arrays with same structure with one row per LO,
        //   having the highest Power level found less than $powerLimit.

        $powerLimit = $this->maxSafePowerForBand($this->GetValue('Band'));

        $output = array();
        $lastLO = FALSE;
        $lastRow = FALSE;
        $found = FALSE;

        foreach($allRows as $row) {
            $LO = $row['FreqLO'];
            $pwr = $row['Power'];

            // starting a new LO chunk?
            if ($LO != $lastLO) {
                // not first row of table?
                if ($lastLO !== FALSE) {
                    // next LO in table or EOF.  Save max safe values found:
                    $output[] = $lastRow;
                    $lastRow = FALSE;
                    $found = FALSE;
                }
                // save for next iter on this LO chunk:
                $lastLO = $LO;
            }

            // found excessive power?
            else if ($pwr > $powerLimit) {
                // yes.  Preserve lastRow for rest of this LO chunk:
                if ($powerLimit > 0 && $lastRow['Power'] <= $powerLimit) {
                    $found = TRUE;
                }
            }

            // found max safe?
            if (!$found) {
                // no.  move to next row:
                $lastRow = $row;
            }
        }
        return $output;
    }

    private function GetTestDataHeaders($testDataType) {
        $SN = $this->GetValue('SN');
        $Band = $this->GetValue('Band');
        $compType = $this->GetValue('fkFE_ComponentType');

        $q = "SELECT TestData_header.keyId
        FROM TestData_header, TestData_Types, FE_Components
        WHERE TestData_header.fkFE_Components = FE_Components.keyId
        AND FE_Components.SN LIKE '$SN'
        AND FE_Components.Band LIKE '$Band'
        AND TestData_header.fkTestData_Type = '$testDataType'
        AND TestData_header.fkTestData_Type = TestData_Types.keyId
        AND FE_Components.fkFE_ComponentType = '$compType'
        AND TestData_header.fkFE_Config < 1;";

        $output = array();

        /*$r = @mysql_query($q,$this->dbconnection);//*/
        $r = $this->db_pull->run_query($q);
        while ($row = @mysql_fetch_array($r))
            $output[]= $row[0];

        return $output;
    }

    private function FormatTDHList($tdhArray) {
        $output = "(";
        $index = 0;
        while ($index < count($tdhArray)) {
            $output .= "'$tdhArray[$index]'";
            $index++;
            if ($index < (count($tdhArray) - 1))
                $output .= ",";
        }
        $output .= ")";
        return $output;
    }

    private function loadPowerData($pol, $tdhArray) {
        // Load the output power data for one polarization, coarse and fine combined:
/*
        $q = "SELECT FreqLO, VD$pol as VD, Power FROM WCA_OutputPower WHERE
              fkHeader IN " . $this->FormatTDHList($tdhArray);

        $q .= " AND fkFacility = $this->fc
                AND (keyDataSet=2 or keyDataSet=3) and Pol=$pol
                ORDER BY FreqLO, VD ASC";

        $r = @mysql_query($q, $this->dbconnection);//*/
        $r = $this->db_pull->q(5, NULL, $pol, $this->fc, $this->FormatTDHList($tdhArray));

        $allRows = array();

        while($row = @mysql_fetch_array($r))
            $allRows[] = $row;    // append row to allRows.

        return $allRows;
    }

    private function loadMaxDrainVoltages($tdhArray) {
        // Load and return an array having the maximum drain voltages
        //  found for Pol0 and Pol1 in the fine output power data.

        $q = "SELECT MAX(VD0), MAX(VD1) FROM WCA_OutputPower WHERE
        fkHeader in " . $this->FormatTDHList($tdhArray);
        $q .= " AND fkFacility = $this->fc
                AND keyDataSet=2";

        $r = @mysql_query($q, $this->dbconnection);
        $r = $this->db_pull->q(6, NULL, NULL, $this->fc, $this->FormatTDHList($tdhArray));
        $row = @mysql_fetch_array($r);
        return $row;
    }

    public function Compute_MaxSafePowerLevels($allHistory) {
        $eof = array('FreqLO'=>'EOF', 'VD'=>0, 'Power'=>0);

        $this->maxSafePowerTable = array();

        // allHistory means find prev test data from previous configs
        if (!isset($allHistory))
            $allHistory = FALSE;

        $tdhArray = array();
        if ($allHistory)
            $tdhArray = $this->GetTestDataHeaders('46');
        else
            $tdhArray[] = $this->tdh_outputpower->keyId;

        // load pol0 power data:
        $allRows = $this->loadPowerData(0, $tdhArray);

        // quit now if there's no data:
        if (!$allRows || count($allRows) == 0)
            return $this->maxSafePowerTable;

        // append dummy EOF record:
        $allRows[] = $eof;

        // compute the max safe power table:
        $pol0table = $this->findMaxSafeRows($allRows);

        // load pol1 power data:
        $allRows = $this->loadPowerData(1, $tdhArray);

        // append dummy EOF record:
        $allRows[] = $eof;

        // compute the max safe power table:
        $pol1table = $this->findMaxSafeRows($allRows);

        // compute scaling factors to convert drain voltages into control values:
        $vdMax = $this->loadMaxDrainVoltages($tdhArray);
        $pol0scale = 2.5 / $vdMax[0];
        $pol1scale = 2.5 / $vdMax[1];

        // define warm multiplication factor per band.
        // TODO: move into specs class
        $spec = $this->new_spec->getSpecs('wca', $this->GetValue('Band'));
        $warmMult = $spec['warmMult'];

        $loYig = $this->_WCAs->GetValue('FloYIG');
        $hiYig = $this->_WCAs->GetValue('FhiYIG');

        // combine the two tables into one output table:
        $flags = MultipleIterator::MIT_NEED_ANY | MultipleIterator::MIT_KEYS_NUMERIC;
        $iterator = new MultipleIterator($flags);
        $iterator->attachIterator(new ArrayIterator($pol0table));
        $iterator->attachIterator(new ArrayIterator($pol1table));

        foreach ($iterator as $values) {
            //var_dump($values);

            $LO = $values[0]['FreqLO'];
            $YIG0 = round(((($LO / $warmMult) - $loYig) / ($hiYig - $loYig)) * 4095);
            $VD0 = round($values[0]['VD'] * $pol0scale, 3);
            $VD1 = round($values[1]['VD'] * $pol1scale, 3);
            $P0 = round($values[0]['Power'], 1);
            $P1 = round($values[1]['Power'], 1);

            // append to array:
            $this->maxSafePowerTable[] = array('FreqLO' => $LO,
                                               'YTO' => $YIG0,
                                               'VD0' => $VD0,
                                               'VD1' => $VD1,
                                               'Pwr0' => $P0,
                                               'Pwr1' => $P1);
        }
        return $this->maxSafePowerTable;
    }

    public function Display_MaxSafePowerLevels() {

        $powerLimit = $this->maxSafePowerForBand($this->GetValue('Band'));

        echo '
        <div style= "width:400px">
          <table id = "table1" align="left" cellspacing="1" cellpadding="1" width="60%">
            <tr class="alt">
              <th align = "center" colspan = "5"><font size="+1">
                <b>MAX SAFE OPERATING PARAMETERS<br>(from output power data) ';

        if ($powerLimit > 0)
            echo 'limit=' . $powerLimit . ' dBm';

        echo '<b></th></tr>';
        echo '
            <tr>
              <th><b>FreqLO (GHz)</b></th>
              <th><b>Digital Setting VD0</b></th>
              <th><b>Digital Setting VD1</b></th>
              <th><b>Power Pol0 (dBm)</b></th>
              <th><b>Power Pol1 (dBm)</b></th>
            </tr>';

        if (count($this->maxSafePowerTable) > 0) {
            $bg_color = FALSE;
            foreach($this->maxSafePowerTable as $row) {
                $bg_color = ($bg_color=="#ffffff" ? '#dddddd' : "#ffffff");
                echo "<tr bgcolor='$bg_color'>";
                echo "<td>" . $row['FreqLO'] . "</td>";
                echo "<td>" . $row['VD0'] . "</td>";
                echo "<td>" . $row['VD1'] . "</td>";
                echo "<td>" . $row['Pwr0'] . "</td>";
                echo "<td>" . $row['Pwr1'] . "</td></tr>";
            }
        }
        echo "</table></div>";

        //--------------------------------------------------------------------------------
        echo "<div style='width:400px'>&nbsp;<br></div>";

        echo '
        <div style= "width:400px">
        <table id = "table1" align="left" cellspacing="1" cellpadding="1" width="60%" >
          <tr class="alt">
            <th align = "center" colspan = "5"><font size="+1" >
              <b>MAX SAFE OPERATING PARAMETERS<br>(original values from LO group)</b>
            </th>
          </tr>
          <tr>
            <th><b>FreqLO (GHz)</b></th>
            <th><b>Digital Setting VD0</b></th>
            <th><b>Digital Setting VD1</b></th>
            <th><b>Drain Voltage VD0</b></th>
            <th><b>Drain Voltage VD1</b></th>
          </tr>';

        $rMSP = $this->db_pull->q_other('MSP', $this->keyId, $this->fc);
        $bg_color = FALSE;
        while ($rowMSP = @mysql_fetch_array($rMSP)) {
            $bg_color = ($bg_color=="#ffffff" ? '#dddddd' : "#ffffff");
            echo "<tr bgcolor='$bg_color'>
                    <td>".$rowMSP['FreqLO']."</td>
                    <td>".$rowMSP['VD0_setting']."</td>
                    <td>".$rowMSP['VD1_setting']."</td>
                    <td>".$rowMSP['VD0']."</td>
                    <td>".$rowMSP['VD1']."</td>
                  </tr>";
        }
        echo "</table></div>";
    }

    public function Display_uploadform() {
        $where = $_SERVER['PHP_SELF'];
        $where = '';
        echo '
        <p><div style="width:500px;height:80px; align = "left"></p>
        <!-- The data encoding type, enctype, MUST be specified as below -->
        <form enctype="multipart/form-data" action="' . $where . '" method="POST">
            <!-- MAX_FILE_SIZE must precede the file input field -->
            <!-- <input type="hidden" name="MAX_FILE_SIZE" value="100000" /> -->
            <!-- Name of input element determines name in $_FILES array -->
            <br>
            <table id="table1"><tr class="alt"><th>CSV Data files</th><th></th></tr>
                <tr><td align = "right">WCAs file:           </b><input name="file_wcas" type="file" /></td><td></td></tr>
                <tr class = "alt"><td align = "right">Max Safe Power:      </b><input name="file_maxsafepower" type="file" /><td></td></tr>
                <tr><td align = "right">Amplitude Stability: </b><input name="file_amplitudestability" type="file" /></td>
                    <td align = "center"><input type="submit" name="draw_amplitudestability" value="Redraw Amp. Stability"></td></tr>
                <tr><td align = "right">AM Noise:            </b><input name="file_amnoise" type="file" /></td>
                    <td align = "center"><input type="submit" name="draw_amnoise" value="Redraw AM Noise"></td></tr>
                <tr><td align = "right">Output Power:        </b><input name="file_outputpower" type="file" /></td>
                    <td align = "center"><input type="submit" name="draw_outputpower" value="Redraw Output Power"></td></tr>
                <tr><td align = "right">Phase Noise:         </b><input name="file_phasenoise" type="file" /></td>
                    <td align = "center"><input type="submit" name="draw_phasenoise" value="Redraw Phase Noise"></td></tr>
                <tr><td align = "right">';
                echo "<input type='hidden' name= 'fc' value='$this->fc' />";
                echo '<input type="submit" name= "submit_datafile" value="Upload All" /></td>
                    <td align = "center"><input type="submit" name="draw_all" value="REDRAW ALL PLOTS"></td></tr>
            </table>
        </form>
        </div>';
        echo "<br>";
        echo "<br>";
    }
    public function RequestValues_WCA(){
        parent::RequestValues();

        if (isset($_REQUEST['deleterecord_forsure'])){
            $this->DeleteRecord_WCA();
        }

        if (isset($_REQUEST['fc'])){
            $this->fc = $_REQUEST['fc'];
        }

        if (isset($_REQUEST['FloYIG'])){
            $this->_WCAs->SetValue('FloYIG',$_REQUEST['FloYIG']);
        }
        if (isset($_REQUEST['FhiYIG'])){
            $this->_WCAs->SetValue('FhiYIG',$_REQUEST['FhiYIG']);
        }
        if (isset($_REQUEST['VG0'])){
            $this->_WCAs->SetValue('VG0',$_REQUEST['VG0']);
        }
        if (isset($_REQUEST['VG1'])){
            $this->_WCAs->SetValue('VG1',$_REQUEST['VG1']);
        }
        if (isset($_REQUEST['submit_datafile'])){
//            if($this->password == "nrao1234"){
                if (isset($_FILES['file_wcas']['name'])){
                    if ($this->keyId == ""){
                        $this->NewRecord_WCA();
                    }
                    if ($_FILES['file_wcas']['name'] != ""){
                        $this->Upload_WCAs_file($_FILES['file_wcas']['tmp_name']);
                    }
                    $this->Update_WCA();
                }
                if (isset($_FILES['file_amplitudestability']['name'])){
                    if ($_FILES['file_amplitudestability']['name'] != ""){
                        $this->Upload_AmplitudeStability_file($_FILES['file_amplitudestability']['tmp_name']);
                        $this->Plot_AmplitudeStability();
                    }
                }
                if (isset($_FILES['file_amnoise']['name'])){
                    if ($_FILES['file_amnoise']['name'] != ""){
                        $this->Upload_AMNoise_file($_FILES['file_amnoise']['tmp_name']);
                        $this->Plot_AMNoise();
                    }
                }
                if (isset($_FILES['file_phasenoise']['name'])){
                    if ($_FILES['file_phasenoise']['name'] != ""){
                        $this->Upload_PhaseNoise_file($_FILES['file_phasenoise']['tmp_name']);
                        $this->Plot_PhaseNoise();
                    }
                }
                if (isset($_FILES['file_outputpower']['name'])){
                    if ($_FILES['file_outputpower']['name'] != ""){
                        $this->Upload_OutputPower_file($_FILES['file_outputpower']['tmp_name']);
                        $this->Plot_OutputPower();
                    }
                }
                if (isset($_FILES['file_maxsafepower']['name'])){
                    if ($_FILES['file_maxsafepower']['name'] != ""){
                        $this->Upload_MaxSafePower_file($_FILES['file_maxsafepower']['tmp_name']);
                    }
                }
//            }
        }
        if (isset($_REQUEST['draw_all'])){
            $this->RedrawAllPlots();
        } else {
            if (isset($_REQUEST['draw_amnoise'])){
                $this->Plot_AMNoise();
            }
            if (isset($_REQUEST['draw_outputpower'])){
                $this->Plot_OutputPower();
            }
            if (isset($_REQUEST['draw_amplitudestability'])){
                $this->Plot_AmplitudeStability();
            }
            if (isset($_REQUEST['draw_phasenoise'])){
                $this->Plot_PhaseNoise();
            }
        }

        $this->Update_WCA();
        $this->AddNewLOParams();
        if (isset($_REQUEST['exportcsv_amplitudestability'])){
            $this->ExportCSV("amplitudestability");
        }
    }

    public function DeleteRecord_WCA(){
        $this->db_pull->qDel($this->keyId, 'WCAs', TRUE);
        $this->db_pull->qDel($this->tdh_amnoise->keyId, 'WCA_AMNoise');
        $this->db_pull->qDel($this->keyId, 'WCA_MaxSafePower', TRUE);
        $this->db_pull->qDel($this->keyId, 'WCA_LOParams', TRUE);
        $this->db_pull->qDel($this->tdh_outputpower->keyId, 'WCA_OutputPower');
        $this->db_pull->qDel($this->tdh_phasenoise->keyId, 'WCA_PhaseNoise');
        $this->db_pull->qDel($this->tdh_phasejitter->keyId, 'WCA_PhaseJitter');
        $this->db_pull->qDel($this->tdh_ampstab->keyId, 'WCA_AmplitudeStability');
        $this->db_pull->qDel($this->keyId, 'FE_ConfigLink', TRUE);
        parent::Delete_record();
        echo '<meta http-equiv="Refresh" content="1;url=wca_main.php">';
    }

    public function Upload_WCAs_file($datafile_name){
        $filecontents = file($datafile_name);

        for($i=0; $i<sizeof($filecontents); $i++) {
            $line_data = trim($filecontents[$i]);
            $tempArray   = explode(",", $line_data);

              if (is_numeric(substr($tempArray[0],0,1)) == true){
                  $this->SetValue('Band',$tempArray[0]);
                  //$this->SetValue('TS',$tempArray[2]);
                  $this->SetValue('SN',$tempArray[1]);
                  $ESN = str_replace('"','',$tempArray[5]);
                  $this->SetValue('ESN1',$ESN);
                  $this->_WCAs->SetValue('FloYIG',$tempArray[6]);
                  $this->_WCAs->SetValue('FhiYIG',$tempArray[7]);
                  $this->_WCAs->SetValue('VG0',str_replace('"','',$tempArray[13]));
                  $this->_WCAs->SetValue('VG1',str_replace('"','',$tempArray[14]));
            }
        }
        unlink($datafile_name);
    }

    public function UploadConfiguration($datafile_name, $datafile_tmpname) {

        $this->SubmittedFileName = $datafile_name;
        $this->SubmittedFileTmp  = $datafile_tmpname;

        $filenamearr = explode(".",$this->SubmittedFileName);
        $this->SubmittedFileExtension = strtolower($filenamearr[count($filenamearr)-1]);

        if ($this->SubmittedFileExtension == 'ini') {
            $this->Update_Configuration_From_INI($this->SubmittedFileTmp);
        }

        else if ($this->SubmittedFileExtension == 'xml') {
            $this->Update_Configuration_From_ALMA_XML($this->SubmittedFileTmp);
        }

        else {
            $this->AddError("Error: Unable to upload file $this->SubmittedFileName.");
        }
    }

    private function Update_Configuration_From_INI($INIfile){
        $ini_array = parse_ini_file($INIfile, true);
        $sectionname = '~WCA' . $this->GetValue('Band') . "-" . $this->GetValue('SN');
        $CheckBand = $ini_array[$sectionname]['Band'];
        $wcafound = false;
        if ($CheckBand == $this->GetValue('Band')){
            $wcafound = true;
        }

        if ($wcafound) {
            // Warn the user that WCA not found in file:
            $this->AddError("WCA ". $this->GetValue('SN') . " not found in this file!  Upload aborted.");

        } else {
            //Remove this WCA from the front end
            $dbops = new DBOperations();

            //Preserve these values in the new SLN record
            $oldStatus = $this->sln->GetValue('fkStatusType');
            $oldLocation = $this->sln->GetValue('fkLocationNames');

            //Get old status and location for the front end
            $wcaFE = new FrontEnd();
            $this->GetFEConfig();
            $wcaFE->Initialize_FrontEnd_FromConfig($this->FEConfig, $this->FEfc, FrontEnd::INIT_SLN);
            $oldStatusFE = $wcaFE->fesln->GetValue('fkStatusType');
            $oldLocationFE = $wcaFE->fesln->GetValue('fkLocationNames');
            $dbops->RemoveComponentFromFrontEnd($this->GetValue('keyFacility'), $this->keyId, '',-1,-1);
            $FEid_old = $this->FEid;
            $this->GetFEConfig();

            //Create new component record, duplicate everything from the existing.
            //Save old key value
            $keyIdOLD = $this->keyId;
            $this->DuplicateRecord_WCA();
            $keyIdNEW = $this->keyId;

            //Copy Max Safe Operating Parameters
            $keys = array();
            $keys['old'] = $keyIdOLD;
            $keys['new'] = $keyIdNEW;
            $this->db_pull->q_other('MS', NULL, NULL, NULL, NULL, NULL, $keys);

            //Notes for the SLN record of new component
            $Notes = "Configuration changed on " . date('r') . ". ";

            //Get rid of any existing LO Params
            $r = $this->db_pull->q(7, $this->keyId);
            //Get rid of any existing WCAs table records
            $r = $this->db_pull->q(8, $this->keyId);

            //Read INI file
            $NumLOParams = $ini_array[$sectionname]['LOParams'];
            for($i=1; $i <= $NumLOParams; $i++) {
                if ($i<10){
                    $LOkeyname = "LOParam0$i";
                }
                if ($i>=10){
                    $LOkeyname = "LOParam$i";
                }

                $LOkeyArray = explode(',',$ini_array[$sectionname][$LOkeyname]);
                $FreqLO = $LOkeyArray[0];
                $VDP0   = $LOkeyArray[1];
                $VDP1   = $LOkeyArray[2];
                $VGP0   = $LOkeyArray[3];
                $VGP1   = $LOkeyArray[4];

                $qnew  = "INSERT INTO WCA_LOParams(fkComponent,FreqLO,VDP0,VDP1,VGP0,VGP1) ";
                $qnew .= " VALUES('$this->keyId','$FreqLO','$VDP0','$VDP1','$VGP0','$VGP1');";
				$rnew = $this->db_pull->run_query($qnew);

                if ($i == 1){
                    $VG0 = $VGP0;
                    $VG1 = $VGP1;
                }
            }
            $FLOYIG = $ini_array[$sectionname]['FLOYIG'];
            $FHIYIG = $ini_array[$sectionname]['FHIYIG'];

            //Copy Yig settings
            $rYIG = $this->db_pull->q_other('YIG', $this->keyId);
            $YIGnumrows = @mysql_num_rowS($rYIG);

            if ($YIGnumrows > 0){
                $this->_WCAs->SetValue('FloYIG',$FLOYIG);
                $this->_WCAs->SetValue('FhiYIG',$FHIYIG);
                $this->_WCAs->SetValue('VG0',$VG0);
                $this->_WCAs->SetValue('VG1',$VG1);
                $this->_WCAs->Update();
            }
            if ($YIGnumrows < 1){
                $qwcas  = "INSERT INTO WCAs(fkFE_Component,FloYIG,FhiYIG,VG0,VG1) ";
                $qwcas .= "VALUES('$this->keyId','$FLOYIG','$FHIYIG','$VG0','$VG1');";
				$rwcas = $this->db_pull->run_query($qwcas);
            }

            //Done reading from INI file.
            $updatestring = "Updated config for WCA " . $this->GetValue('Band') . "-" . $this->GetValue('SN') . ".";

            //Add WCA to Front End
            $feconfig = $this->FEfc;
            $dbops->AddComponentToFrontEnd($FEid_old, $this->keyId, $this->FEfc, $this->GetValue('keyFacility'), '', $updatestring, ' ',-1);
            $dbops->UpdateStatusLocationAndNotes_Component($this->fc, $oldStatus, $oldLocation,$updatestring,$this->keyId, ' ','');
            $this->GetFEConfig();
            $dbops->UpdateStatusLocationAndNotes_FE($this->FEfc, $oldStatusFE, $oldLocationFE,$updatestring,$this->FEConfig, $this->FEConfig, ' ','');
            unset($dbops);
        }//end if (wcafound)
        unlink($INIfile);
    }

    private function Update_Configuration_From_ALMA_XML($XMLfile) {
        $ConfigData = simplexml_load_file($XMLfile);
        $found = false;
        if ($ConfigData) {
            $assy = (string) $ConfigData->ASSEMBLY['value'];
            list($band) = sscanf($assy, "WCA%d");
            if ($band && $band == $this->GetValue('Band'))
                $found = true;
        }
        if (!$found) {
            //Warn user that CCA not found in the file
            $this->AddError("WCA band ". $this->GetValue('Band') . " not found in this file!  Upload aborted.");
        } else {
            //Remove this WCA from the front end
            $dbops = new DBOperations();

            //Preserve these values in the new SLN record
            $oldStatus = $this->sln->GetValue('fkStatusType');
            $oldLocation = $this->sln->GetValue('fkLocationNames');

            //Get old status and location for the front end
            $wcaFE = new FrontEnd();
            $this->GetFEConfig();
            $wcaFE->Initialize_FrontEnd_FromConfig($this->FEConfig, $this->FEfc, FrontEnd::INIT_SLN);
            $oldStatusFE = $wcaFE->fesln->GetValue('fkStatusType');
            $oldLocationFE = $wcaFE->fesln->GetValue('fkLocationNames');
            $dbops->RemoveComponentFromFrontEnd($this->GetValue('keyFacility'), $this->keyId, '',-1,-1);
            $FEid_old = $this->FEid;
            $this->GetFEConfig();

            //Create new component record, duplicate everything from the existing.
            //Save old key value
            $keyIdOLD = $this->keyId;
            $this->DuplicateRecord_WCA();
            $keyIdNEW = $this->keyId;

            //Copy Max Safe Operating Parameters
            $keys = array();
            $keys['old'] = $keyIdOLD;
            $keys['new'] = $keyIdNEW;
            $this->db_pull->q_other('MS', NULL, NULL, NULL, NULL, NULL, $keys);

            //Notes for the SLN record of new component
            $Notes = "Configuration changed on " . date('r') . ". ";

            //Get rid of any existing LO Params
            $r = $this->db_pull->q(7, $this->keyId);
            //Get rid of any existing WCAs table records
            $r = $this->db_pull->q(8, $this->keyId);

            //Get LO params array indexed by LO string:
            $LOParams = array();
            foreach ($ConfigData->PowerAmp as $param) {
                $FreqLO = ((float) $param['FreqLO']) / 1E9;
                $VD0 = (float) $param['VD0'];
                $VD1 = (float) $param['VD1'];
                $VG0 = (float) $param['VG0'];
                $VG1 = (float) $param['VG1'];

                $qnew  = "INSERT INTO WCA_LOParams(fkComponent,FreqLO,VDP0,VDP1,VGP0,VGP1) ";
                $qnew .= " VALUES('$this->keyId','$FreqLO','$VD0','$VD1','$VG0','$VG1');";
                $rnew = $this->db_pull->run_query($qnew);
            }
            $FLOYIG = ((float) $ConfigData->FLOYIG['value']) / 1E9;
            $FHIYIG = ((float) $ConfigData->FHIYIG['value']) / 1E9;

            //Copy Yig settings
            $rYIG = $this->db_pull->q_other('YIG', $this->keyId);
            $YIGnumrows = @mysql_num_rowS($rYIG);

            if ($YIGnumrows > 0){
                $this->_WCAs->SetValue('FloYIG',$FLOYIG);
                $this->_WCAs->SetValue('FhiYIG',$FHIYIG);
                $this->_WCAs->SetValue('VG0',$VG0);
                $this->_WCAs->SetValue('VG1',$VG1);
                $this->_WCAs->Update();
            }
            if ($YIGnumrows < 1){
                $qwcas  = "INSERT INTO WCAs(fkFE_Component,FloYIG,FhiYIG,VG0,VG1) ";
                $qwcas .= "VALUES('$this->keyId','$FLOYIG','$FHIYIG','$VG0','$VG1');";
                $rwcas = $this->db_pull->run_query($qwcas);
            }

            //Done reading from XML file.
            $updatestring = "Updated config for WCA " . $this->GetValue('Band') . "-" . $this->GetValue('SN') . ".";

            //Add WCA to Front End
            $feconfig = $this->FEfc;
            $dbops->AddComponentToFrontEnd($FEid_old, $this->keyId, $this->FEfc, $this->GetValue('keyFacility'), '', $updatestring, ' ',-1);
            $dbops->UpdateStatusLocationAndNotes_Component($this->fc, $oldStatus, $oldLocation,$updatestring,$this->keyId, ' ','');
            $this->GetFEConfig();
            $dbops->UpdateStatusLocationAndNotes_FE($this->FEfc, $oldStatusFE, $oldLocationFE,$updatestring,$this->FEConfig, $this->FEConfig, ' ','');
            unset($dbops);
        }
        unlink($XMLfile);
    }

    private function DuplicateRecord_WCA(){
        parent::DuplicateRecord();
        //Copy the records for LO Params
        for ($i = 0; $i < count($this->LOParams); $i++){
            if ($this->LOParams[$i]->keyId > 0){
                $this->LOParams[$i]->DuplicateRecord();
            }
        }
        if($this->_WCAs->keyId > 0){
            $this->_WCAs->DuplicateRecord();
        }
    }


    public function Upload_MaxSafePower_file($datafile_name){

        $filecontents = file($datafile_name);
		$this->db_pull->del_ins('WCA_MaxSafePower', $filecontents, $this, $this->fc);
        unlink($datafile_name);
        unset($tdh);
        //fclose($filecontents);
    }

    public function Upload_AmplitudeStability_file($datafile_name){
        //Test Data Header object
        //Delete any existing header records
        $rtdh = $this->db_pull->qtdh('delete', $this->keyId, 'WCA_AmplitudeStability');
        $this->tdh_ampstab = new GenericTable();
        $this->tdh_ampstab->NewRecord("TestData_header", 'keyId',$this->GetValue('keyFacility'),'keyFacility');
        $this->tdh_ampstab->SetValue('fkTestData_Type',45);
        $this->tdh_ampstab->SetValue('fkDataStatus',$this->fkDataStatus);
        $this->tdh_ampstab->SetValue('fkFE_Components',$this->keyId);
        $this->tdh_ampstab->Update();


        $filecontents = file($datafile_name);
        $this->db_pull->del_ins('WCA_AmplitudeStability', $filecontents, $this->tdh_ampstab);
        unlink($datafile_name);
        unset($tdh);
        //fclose($filecontents);
    }

    public function Plot_AmplitudeStability(){
        if (!file_exists($this->writedirectory)){
            mkdir($this->writedirectory);
        }

        $TS = $this->tdh_ampstab->GetValue('TS');

        //$TS = $tdh->GetValue('TS');

        //write data file from database
        $rFindLO = $this->db_pull->qFindLO('WCA_AmplitudeStability', $this->tdh_ampstab->keyId);
        $rowLO=@mysql_fetch_array($rFindLO);

        $datafile_count=0;
        $spec_value = 0.0000001;

        for ($j=0;$j<=1;$j++){
            for ($i=0;$i<=sizeof($rowLO);$i++){
                $CurrentLO = @mysql_result($rFindLO,$i);
                $DataSeriesName = "LO $CurrentLO GHz, Pol $j";

                $r = $this->db_pull->q(9, $this->tdh_ampstab->keyId, $j, NULL, NULL, $CurrentLO);

                if (@mysql_num_rows($r) > 1){
                    $plottitle[$datafile_count] = "Pol $j, $CurrentLO GHz";
                    $data_file[$datafile_count] = $this->writedirectory . "wca_as_data_".$i."_".$j.".txt";
                    if (file_exists($data_file[$datafile_count])){
                        unlink($data_file[$datafile_count]);
                    }
                    $fh = fopen($data_file[$datafile_count], 'w');
                    $row=@mysql_fetch_array($r);
                    $TimeVal = $row[0];

                    if ($TimeVal > 500){
                        fwrite($fh, "$row[0]\t0.00000009\r\n");
                    }
                    while($row=@mysql_fetch_array($r)){
                        $stringData = "$row[0]\t$row[1]\r\n";
                        fwrite($fh, $stringData);
                    }
                    //}

                    fclose($fh);
                    $datafile_count++;
                }
            }//end for i
        }//end for j

        //Create data file for spec

        //Write command file for gnuplot
        $plot_command_file = $this->writedirectory . "wca_as_command.txt";
        if (file_exists($plot_command_file)){
            unlink($plot_command_file);
        }
        $imagedirectory = $this->writedirectory . $this->GetValue('Band') . "_" . $this->GetValue('SN') . "/";
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagename = "WCA_AmplitudeStability_SN" . $this->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . $this->GetValue('Band') . "_" . $this->GetValue('SN') . "/$imagename";

        $this->tdh_ampstab->SetValue('PlotURL',"$image_url");
        $this->tdh_ampstab->Update();
        unset($tdh);

        $plot_title = "WCA Band" . $this->GetValue('Band') . " SN" . $this->GetValue('SN') . " Amplitude Stability ($TS)";
        $this->_WCAs->SetValue('amp_stability_url',$image_url);
        $this->_WCAs->Update();
        $imagepath = $imagedirectory . $imagename;


        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 900,500\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set log xy\r\n");
        fwrite($fh, "set key outside\r\n");
        fwrite($fh, "set ylabel 'Allan Variance'\r\n");
        fwrite($fh, "set xlabel 'Allan Time, T (=Integration, Tau)'\r\n");

        $ymax = pow(10,-5);
        fwrite($fh, "set yrange [:$ymax]\r\n");

        fwrite($fh, "f1(x)=((x>500) && (x<100000)) ? 0.00000009 : 1/0\r\n");
        fwrite($fh, "f2(x)=((x>290000) && (x<350000)) ? 0.000001 : 1/0\r\n");
        $plot_string = "plot f1(x) title 'Spec' with lines lw 3";
        $plot_string .= ", f2(x) title 'Spec' with points pt 5 pointsize 1";
        $plot_string .= ", '$data_file[0]' using 1:2 title '$plottitle[0]' with lines";
        for ($i=1;$i<sizeof($data_file);$i++){
            $plot_string .= ", '$data_file[$i]' using 1:2 title '$plottitle[$i]' with lines";
        }
        $plot_string .= "\r\n";
        fwrite($fh, $plot_string);

        fclose($fh);

        //Make the plot
        $GNUPLOT = '/usr/bin/gnuplot';
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
    }

    public function Upload_AMNoise_file($datafile_name){
        //Test Data Header object
        //Delete any existing header records
        $rtdh = $this->db_pull->qtdh('delete', $this->keyId, 'WCA_AMNoise');
        $this->tdh_amnoise = new GenericTable();
        $this->tdh_amnoise->NewRecord("TestData_header", 'keyId',$this->GetValue('keyFacility'),'keyFacility');
        $this->tdh_amnoise->SetValue('fkTestData_Type',44);
        $this->tdh_amnoise->SetValue('fkDataStatus',$this->fkDataStatus);
        $this->tdh_amnoise->SetValue('fkFE_Components',$this->keyId);
        $this->tdh_amnoise->Update();

        $filecontents = file($datafile_name);
        $this->db_pull->del_ins('WCA_AMNoise', $filecontents, $this->tdh_amnoise);
        unlink($datafile_name);
        unset($tdh);
    }

    public function Plot_AMNoise(){
        if (!file_exists($this->writedirectory)){
            mkdir($this->writedirectory);
        }
        $this->Plot_AMNoise_DSB();
        $this->Plot_AMNoise_Pol0_1();
    }


    public function Plot_AMNoise_DSB(){
        $TS = $this->tdh_amnoise->GetValue('TS');
        $Band = $this->GetValue('Band');
        $spec_value = 10;

        $FreqLOW=4;
        $FreqHI=8;

        If ($Band=='6'){
            $FreqLOW=6;
            $FreqHI=10;
        }

        If ($Band=='10'){
            $FreqLOW=4;
            $FreqHI=12;
        }

        // Note, using 4-8 GHz for band 9 intentionally since it may become 2SB in the future and the worst noise
        //     contribution is in the lower half.

        $imagedirectory = $this->writedirectory;
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagename = "WCA_AMNoiseDSB_SN" . $this->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . $imagename;

        $plot_title = "WCA Band" . $this->GetValue('Band') . " SN" . $this->GetValue('SN') . " AM Noise ($TS)";
        $this->_WCAs->SetValue('amnz_avgdsb_url',$image_url);
        $this->_WCAs->Update();
        $imagepath = $imagedirectory . $imagename;

        $amnzarr[0] = "";
        for ($pol=0;$pol<=1;$pol++){
            unset($amnzarr);
            $arrct = 0;
            //Get X axis values
            $rFreqLO = $this->db_pull->q_other('FreqLO', $this->tdh_amnoise->keyId, $this->fc, $pol, $FreqLOW, $FreqHI);
            while ($row = @mysql_fetch_array($rFreqLO)){
                $amnzarr[0][$arrct]=$row[0];
                $amnzarr[1][$arrct]=$row[1];
                $arrct += 1;
            }

            $arrct = 0;
            $rlo = $this->db_pull->qlo('WCA_AMNoise', $this->tdh_amnoise->keyId, $this->fc, FALSE, $FreqLOW, $FreqHI, $pol);
            while($rowlo = @mysql_fetch_array($rlo)){
                $freqarr[$arrct] = $rowlo[0];
                $arrct += 1;
            }

            $data_file[$pol]= $this->writedirectory . "wca_amnoise_data$pol.txt";
            if (file_exists($data_file[$pol])){
                unlink($data_file[$pol]);
            }
            $fh = fopen($data_file[$pol], 'w');

            $plotmax = "";

            for ($i = 0; $i < count($freqarr); $i++){
                $avgamnz = $this->GetAvgAMNoise($amnzarr, $freqarr[$i]);
                $stringData = "$freqarr[$i]\t$avgamnz\r\n";
                fwrite($fh, $stringData);
                if ($avgamnz > 9){
                    $plotmax = "12";
                }
            }
            unset($amnzarr);
            fclose($fh);
        }//end for pol

        //Write command file for gnuplot
        $plot_command_file = $this->writedirectory . "wca_amnz_command.txt";
        if (file_exists($plot_command_file)){
            unlink($plot_command_file);
        }
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 700,500\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set xlabel 'LO Frequency (GHz)'\r\n");
        fwrite($fh, "set ylabel 'Average DSB NSR (K/uW)'\r\n");
        fwrite($fh, "set key outside\r\n");

        //Spec line
        fwrite($fh, "f1(x)= 10\r\n");

        $plot_string = "plot '$data_file[0]' using 1:2 title 'Pol 0' with lines lt 6";
        $plot_string .= ", '$data_file[1]' using 1:2 title 'Pol 1' with lines lt 3\r\n";

        fwrite($fh, $plot_string);
        fclose($fh);

        //Make the plot
        $GNUPLOT = $this->GNUplot;
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);

    }

    public function GetAvgAMNoise($amnzarr, $freqlo){
        $sum = 0;
        $count = 0;
        for ($i = 0; $i < count($amnzarr[0]); $i++){
            if ($amnzarr[0][$i] == $freqlo){
                $sum += $amnzarr[1][$i];
                $count += 1;
            }
        }
        $avg = $sum / $count;
        return $avg;
    }

    public function Plot_AMNoise_Pol0_1(){
        $TS = $this->tdh_amnoise->GetValue('TS');
        for($pol=0;$pol<=1;$pol++){

            $imagedirectory = $this->writedirectory;
            if (!file_exists($imagedirectory)){
                mkdir($imagedirectory);
            }
            $imagename = "WCA_AMNoisePol$pol" . "_SN" . $this->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";
            $image_url = $this->url_directory . $imagename;



            $plot_title = "WCA Band" . $this->GetValue('Band') . " SN" . $this->GetValue('SN') .  " AM Noise Pol $pol ($TS)";
            $this->_WCAs->SetValue("amnz_pol".$pol."_url",$image_url);
            $this->_WCAs->Update();
            $imagepath = $imagedirectory . $imagename;

            $rNumIF = $this->db_pull->q_other('NumIF', $this->tdh_amnoise->keyId, $this->fc, $pol);
            $NumIF = @mysql_num_rows($rNumIF);


            $data_file[$pol]= $this->writedirectory . "wca_amnoise_data_pol$pol.txt";
            if (file_exists($data_file[$pol])){
                unlink($data_file[$pol]);
            }
            $fh = fopen($data_file[$pol], 'w');


            $IFcount = 0;
            $r = $this->db_pull->q(10, $this->tdh_amnoise->keyId, $pol, $this->fc);
            while($row=@mysql_fetch_array($r)){
                $stringData = "$row[0]\t$row[1]\t$row[2]\r\n";
                fwrite($fh, $stringData);
                $IFcount +=1;
                if ($IFcount == $NumIF){
                    fwrite($fh, "\r\n");
                    $IFcount = 0;
                }
            }
            fclose($fh);

            $amtitle = "AMNoise Pol $pol";
            //Command file
            $plot_command_file = $this->writedirectory . "wca_as_command.txt";
            if (file_exists($plot_command_file)){
                unlink($plot_command_file);
            }
            $fhc = fopen($plot_command_file, 'w');

            fwrite($fhc, "set output '$imagepath'\r\n");
            fwrite($fhc, "set pm3d map\r\n");
            fwrite($fhc, "set palette model RGB defined (0 'black', 2 'blue', 4 'green', 6 'yellow', 8 'orange', 10 'red')\r\n");
            fwrite($fhc, "set terminal png crop\r\n");
            fwrite($fhc, "set title '$plot_title'\r\n");
            fwrite($fhc, "set xlabel 'IF (GHz)'\r\n");
            fwrite($fhc, "set ylabel 'LO Frequency (GHz)'\r\n");
            fwrite($fhc, "set cblabel 'NSR (K/uW)' \r\n");
            fwrite($fhc, "set view map\r\n");
            fwrite($fhc, "set cbrange[0:]\r\n");

            $plot_string = "splot '$data_file[$pol]' using 1:2:3 title ''\r\n";
            fwrite($fhc, $plot_string);
            fclose($fhc);

            //Make the plot
            $GNUPLOT = $this->GNUplot;

            $CommandString = "$GNUPLOT $plot_command_file";
            system($CommandString);
        }//end for loop pol
    }

    public function Upload_PhaseNoise_file($datafile_name){
        //Test Data Header object
        //Delete any existing header records
        $rtdh = $this->db_pull->qtdh('delete', $this->keyId, 'WCA_PhaseJitter', $this->fc);
        $rtdh = $this->db_pull->qtdh('delete', $this->keyId, 'WCA_PhaseNoise', $this->fc);
        $this->tdh_phasenoise = new GenericTable();
        $this->tdh_phasenoise->NewRecord("TestData_header", 'keyId',$this->GetValue('keyFacility'),'keyFacility');
        $this->tdh_phasenoise->SetValue('fkTestData_Type',48);
        $this->tdh_phasenoise->SetValue('fkDataStatus',$this->fkDataStatus);
        $this->tdh_phasenoise->SetValue('fkFE_Components',$this->keyId);
        $this->tdh_phasenoise->Update();
        $this->tdh_phasejitter = new GenericTable();
        $this->tdh_phasejitter->NewRecord("TestData_header", 'keyId',$this->GetValue('keyFacility'),'keyFacility');
        $this->tdh_phasejitter->SetValue('fkTestData_Type',47);
        $this->tdh_phasejitter->SetValue('fkDataStatus',$this->fkDataStatus);
        $this->tdh_phasejitter->SetValue('fkFE_Components',$this->keyId);
        $this->tdh_phasejitter->Update();

        $filecontents = file($datafile_name);
        $this->db_pull->del_ins('WCA_PhaseNoise', $filecontents, $this->tdh_phasenoise, $this->fc, $this->tdh_phasejitter);
        unlink($datafile_name);
        unset($tdh);
        //fclose($filecontents);
    }


    public function Plot_PhaseNoise(){
        if (!file_exists($this->writedirectory)){
            mkdir($this->writedirectory);
        }
        $TS = $this->tdh_phasenoise->GetValue('TS');

        $loindex=0;

        $this->db_pull->qpj('delete', $this->tdh_phasejitter->keyId, $this->fc);

        $rlo = $this->db_pull->qlo('WCA_PhaseNoise', $this->tdh_phasenoise->keyId, $this->fc);

        while ($rowlo = @mysql_fetch_array($rlo)){
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




        //write data file from database
		$rFindLO = $this->db_pull->qFindLO('WCA_PhaseNoise', $this->tdh_phasenoise->keyId, $this->fc);
        $rowLO=@mysql_fetch_array($rFindLO);

        $datafile_count=0;
        for ($j=0;$j<=1;$j++){
            for ($i=0;$i<=sizeof($rowLO);$i++){
                $CurrentLO = @mysql_result($rFindLO,$i);
                $DataSeriesName = "LO $CurrentLO GHz, Pol $j";

                $r = $this->db_pull->q(11, $this->tdh_phasenoise->keyId, $j, $this->fc, NULL, $CurrentLO);

                if (@mysql_num_rows($r) > 1){
                    $plottitle[$datafile_count] = "Pol $j, $CurrentLO GHz";
                    $data_file[$datafile_count] = $this->writedirectory . "wca_phasenz_".$i."_".$j.".txt";
                    if (file_exists($data_file[$datafile_count])){
                        unlink($data_file[$datafile_count]);
                    }
                    $fh = fopen($data_file[$datafile_count], 'w');
                    $row=@mysql_fetch_array($r);

                    while($row=@mysql_fetch_array($r)){
                        $stringData = "$row[0]\t$row[1]\r\n";
                        fwrite($fh, $stringData);
                    }
                    fclose($fh);
                    $datafile_count++;
                }
            }//end for i
        }//end for j


        $imagedirectory = $this->writedirectory ;

        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagename = "WCA_PhaseNoise_SN" . $this->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . $imagename;



        $plot_title = "WCA Band" . $this->GetValue('Band') . " SN" . $this->GetValue('SN') . " Phase Noise ($TS)";
        $this->_WCAs->SetValue('phasenoise_url',$image_url);
        $this->_WCAs->Update();
        $imagepath = $imagedirectory . $imagename;

        //Write command file for gnuplot
        $plot_command_file = $this->writedirectory . "wca_pn_command.txt";
        if (file_exists($plot_command_file)){
            unlink($plot_command_file);
        }
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 900,500\r\n");
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
        for ($i=1;$i<sizeof($data_file);$i++){
            $plot_string .= ", '$data_file[$i]' using 1:2 title '$plottitle[$i]' with lines";
        }
        $plot_string .= "\r\n";
        fwrite($fh, $plot_string);
        fclose($fh);

        //Make the plot
        $GNUPLOT = $this->GNUplot;
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
        unset($tdh);
    }

    public function GetPhaseJitter($LOfreq, $pol){
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

        while ($row = @mysql_fetch_array($r)){
            $RawData_temp = $row[2];
            $OffsetFrequency_temp = $row[0];
            $AntiLog_temp = pow(10.0, $RawData_temp / 10.0);

            $GBEfilter_temp = 20 * log10 ($OffsetFrequency_temp / ($GbE_Pole * sqrt((1 + pow($OffsetFrequency_temp/$GbE_Pole, 2) ))));
            $sumgbe +=  $GBEfilter_temp;

            $applygbe_temp = $RawData_temp + $GBEfilter_temp;
            $antilog2_temp = pow(10,$applygbe_temp/10);


            if ($counter > 0){
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
            $antilog2 = pow(10,$applygbe/10);
            $sumantilog2 += $antilog2;
            $counter += 1;
        }

        $Integration = $sumtrap1;
        $Phi = sqrt(2*$Integration);
        $PhaseJitter = $Phi / (2 * 3.14159 * $GbE_Carrier);
        $PhaseJitter *= pow(10, 15);
        return $PhaseJitter;
    }


    public function Upload_OutputPower_file($datafile_name){
        //Test Data Header object
        //Delete any existing header records
        $rtdh = $this->db_pull->qtdh('delete', $this->keyId, 'WCA_OutputPower');
        $this->tdh_outputpower = new GenericTable();
        $this->tdh_outputpower->NewRecord("TestData_header", 'keyId',$this->GetValue('keyFacility'),'keyFacility');
        $this->tdh_outputpower->SetValue('fkTestData_Type',46);
        $this->tdh_outputpower->SetValue('fkDataStatus',$this->fkDataStatus);
        $this->tdh_outputpower->SetValue('fkFE_Components',$this->keyId);
        $this->tdh_outputpower->Update();

        $filecontents = file($datafile_name);
        $this->db_pull->del_ins('WCA_OutputPower', $filecontents, $this->tdh_outputpower, $this->fc);
        unlink($datafile_name);
        unset($tdh);
        //fclose($filecontents);

    }

    public function Plot_OutputPower(){
        if (!file_exists($this->writedirectory)){
            mkdir($this->writedirectory);
        }
        $this->Plot_OutputPower_vs_frequency();
        $this->Plot_OutputPower_vs_Vd(0);
        $this->Plot_OutputPower_vs_Vd(1);
        $this->Plot_OutputPower_vs_stepsize(0);
        $this->Plot_OutputPower_vs_stepsize(1);
    }

    public function Plot_OutputPower_vs_frequency(){
        $Band = $this->GetValue('Band');
        $rTS = $this->db_pull->q_other('TS', $this->tdh_outputpower->keyId, $this->fc);
        $rowTS=@mysql_fetch_array($rTS);
        $VD0=$rowTS[0];
        $VD1=$rowTS[1];

        $TS = $this->tdh_outputpower->GetValue('TS');

        $imagedirectory = $this->writedirectory;

        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagename = "WCA_OPvsFreq_SN" . $this->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";

        $image_url = $this->url_directory . $imagename;

        $plot_title = "WCA Band" . $this->GetValue('Band') . " SN" . $this->GetValue('SN') . " Output Power Vs. Frequency (VD0=$VD0, VD1=$VD1) ($TS)";

        $this->_WCAs->SetValue('op_vs_freq_url',$image_url);
        $this->_WCAs->Update();
        $imagepath = $imagedirectory . $imagename;

        for ($pol=0;$pol<=1;$pol++){

            $data_file[$pol]= $this->writedirectory . "wca_opvsfreq_data$pol.txt";
            if (file_exists($data_file[$pol])){
                unlink($data_file[$pol]);
            }
            $fh = fopen($data_file[$pol], 'w');
            $rOP = $this->db_pull->q_other('OP', $this->tdh_outputpower->keyId, $this->fc, $pol);
            while($row=@mysql_fetch_array($rOP)){
                $stringData = "$row[0]\t$row[1]\r\n";
                fwrite($fh, $stringData);
            }
            fclose($fh);
        }

        $plot_command_file = $this->writedirectory . "wca_opvsfreq_command.txt";

        if (file_exists($plot_command_file)){
            unlink($plot_command_file);
        }
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 800,500\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");

        fwrite($fh, "set yrange[0:]\r\n");

        //set xrange
        $rx = $this->db_pull->q_other('x', $this->tdh_outputpower->keyId, $this->fc);
        $xMAX = @mysql_result($rx,0) + 1;
        fwrite($fh, "set xrange[:$xMAX]\r\n");

        fwrite($fh, "set xlabel 'LO Frequency (GHz)'\r\n");
        fwrite($fh, "set ylabel 'Output Power (mW)'\r\n");

        fwrite($fh, "set key outside\r\n");

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
                $lineCmd = $specs[$specLineName];
                fwrite($fh, $lineCmd . "\r\n");
                $plot_string .= $specs[$plotStringName];
            }
            $i++;
        }

        $plot_string .= ", '$data_file[0]' using 1:2 title 'Pol 0' with lines ";
        $plot_string .= ", '$data_file[1]' using 1:2 title 'Pol 1' with lines \r\n";

        fwrite($fh, $plot_string);
        fclose($fh);

        //Make the plot
        $GNUPLOT = $this->GNUplot;
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
    }


    public function Plot_OutputPower_vs_Vd($pol){
        $TS = $this->tdh_outputpower->GetValue('TS') ;

        //write data files from database
        $Band = $this->GetValue('Band');
        $specs = $this->new_spec->getSpecs('wca', $Band);
        $spec_value_1 = $specs['spec_value_1'];
        $spec_description_1 = $specs['spec_description_1'];

        $spec_value_2 = $specs['spec_value_2'];
        $spec_description_2 = $specs['spec_description_2'];
        $enable_spec_2 = $specs['enable_spec_2'];

        $datafile_count=0;
        $rFindLO = $this->db_pull->qFindLO('WCA_OutputPower', $this->tdh_outputpower->keyId, $this->fc, $pol, '<> 1');
        $rowLO=@mysql_fetch_array($rFindLO);
        $i=0;
        $data_file = array();

        if ($rFindLO) {
            while ($rowLO = mysql_fetch_array($rFindLO)){
                $CurrentLO = @mysql_result($rFindLO,$i);

                if($Band != 3) {
                	$req = 13;
                } else {
                	$req = 14;
                }
                $r = $this->db_pull->q($req, $this->tdh_outputpower->keyId, $pol, $this->fc, NULL, $CurrentLO);

                if (@mysql_num_rows($r) > 1){
                    $plottitle[$datafile_count] = "$CurrentLO GHz";
                    $data_file[$datafile_count] = $this->writedirectory . "wca_op_vs_dv_".$i."_".$pol.".txt";
                    if (file_exists($data_file[$datafile_count])){
                        unlink($data_file[$datafile_count]);
                    }

                    $fh = fopen($data_file[$datafile_count], 'w');
                    $row=@mysql_fetch_array($r);
                    while($row=@mysql_fetch_array($r)){
                        $stringData = "$row[0]\t$row[1]\r\n";
                        fwrite($fh, $stringData);
                    }
                    fclose($fh);
                    $datafile_count++;
                }
                $i++;
            }//end for i
        }

        $imagedirectory = $this->writedirectory;
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagename = "WCA_OPvsVd_SN" . $this->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . $imagename;
        sleep(1);

        $this->_WCAs->SetValue("op_vs_dv_pol$pol"."_url",$image_url);
        $this->_WCAs->Update();
        $imagepath = $imagedirectory . $imagename;

        //Write command file for gnuplot
        $plot_title = "WCA Band" . $this->GetValue('Band') . " SN" . $this->GetValue('SN') . " Output Power Vs. Drain Voltage Pol $pol ($TS)";
        $plot_command_file = $this->writedirectory . "wca_op_vd_command.txt";
        if (file_exists($plot_command_file)){
            unlink($plot_command_file);
        }
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 1000,600\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set xlabel 'VD$pol (V)'\r\n");
        fwrite($fh, "set ylabel 'Output Power (mW)'\r\n");
        fwrite($fh, "set key outside\r\n");

        // plot the spec lines:
        $plot_string = "plot $spec_value_1 title '$spec_description_1' with lines lw 4 lt 1 ";
        if ($enable_spec_2) {
            $plot_string .= ", $spec_value_2 title '$spec_description_2' with lines lw 4 lt 9 ";
        }

        // plot each trace:
        for ($i=0;$i<sizeof($data_file);$i++){
            if ($i%2 == 0){
                $plot_string .= ", '$data_file[$i]' using 1:2 title '$plottitle[$i]' with lines lw 3";
            }
            if ($i%2 != 0){
                $plot_string .= ", '$data_file[$i]' using 1:2 title '$plottitle[$i]' with linespoints";
            }

        }
        $plot_string .= "\r\n";
        fwrite($fh, $plot_string);

        fclose($fh);

        //Make the plot
        $GNUPLOT = $this->GNUplot;
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
    }

    public function Plot_OutputPower_vs_stepsize($pol){
        //Get timestamp
        $TS = $this->tdh_outputpower->GetValue('TS');

        //write data files from database

        $datafile_count=0;
        $rFindLO = $this->db_pull->qFindLO('WCA_OutputPower', $this->tdh_outputpower->keyId, $this->fc, $pol, '= 3');
        $i=0;
        $data_file = array();

        if ($rFindLO) {
            while ($rowLO = mysql_fetch_array($rFindLO)){
                $CurrentLO = @mysql_result($rFindLO,$i);
                $r = $this->db_pull->q(15, $this->tdh_outputpower->keyId, $pol, $this->fc, NULL, $CurrentLO);


                if (@mysql_num_rows($r) > 1){
                    $plottitle[$datafile_count] = "$CurrentLO GHz";
                    $data_file[$datafile_count] = $this->writedirectory . "wca_op_vs_ss_".$i."_".$pol.".txt";
                    if (file_exists($data_file[$datafile_count])){
                        unlink($data_file[$datafile_count]);
                    }
                    $fh = fopen($data_file[$datafile_count], 'w');
                    $row=@mysql_fetch_array($r);

                    $k=0;
                    while($rowSS=@mysql_fetch_array($r)){
                        $VD_pwr_array[$k] = "$rowSS[0],$rowSS[1]";
                        $VDarray_unsorted[$k]=$rowSS[0];
                        $Pwrarray_unsorted[$k]=$rowSS[1];
                        $tempPwr = $rowSS[1];
                        $k+=1;
                    }
                    sort($VD_pwr_array);
                    for ($arr_index=0;$arr_index<sizeof($VD_pwr_array);$arr_index++){
                        $tempArr = explode(",",$VD_pwr_array[$arr_index]);
                        $VDarray[$arr_index] = $tempArr[0];
                        $Pwrarray[$arr_index] = $tempArr[1];
                    }


                    for($m=0;$m<sizeof($VDarray);$m++){

                        if (isset($Pwrarray[$m+1]) && ($Pwrarray[$m+1] != $Pwrarray[$m])) {
                            $VDtemp=$VDarray[$m];
                            $ptemp1 = $Pwrarray[$m];
                            $ptemp2 = $Pwrarray[$m+1];

                            $stepSize = 0;
                            if (($m+1)<=sizeof($VDarray)){
                                if ($ptemp1==0){
                                    $stepSize = 0;
                                }
                                if ($ptemp1!=0){
                                    $stepSize = 10 * log($ptemp2/$ptemp1,10);
                                    if ($stepSize < 0){
                                        $stepSize = 0;
                                    }
                                    if (($stepSize > 1) && ($ptemp1 > 2)){
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
            }//end for i
        }
        //Get image path

        $imagedirectory = $this->writedirectory;
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagename = "WCA_OPvsStepSize_SN" . $this->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";
        $image_url = $this->url_directory . $imagename;
        sleep(1);

        $this->_WCAs->SetValue("op_vs_ss_pol$pol"."_url",$image_url);
        $this->_WCAs->Update();
        $imagepath = $imagedirectory . $imagename;



        //Write command file for gnuplot
        $plot_title = "WCA Band" . $this->GetValue('Band') . " SN" . $this->GetValue('SN') . " Output Power Vs. Step Size Pol $pol ($TS)";
        $plot_command_file = $this->writedirectory . "wca_op_vs_ss_command.txt";
        if (file_exists($plot_command_file)){
            unlink($plot_command_file);
        }
        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 1000,600\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set xlabel 'Output Power (mW)'\r\n");
        fwrite($fh, "set ylabel 'Step Size (dB)'\r\n");
        fwrite($fh, "set yrange[0:1]\r\n");
        fwrite($fh, "set key outside\r\n");
        $Band = $this->GetValue('Band');
        $specs = $this->new_spec->getSpecs('wca', $Band);
        fwrite($fh, $specs['fwrite_set'] . "\r\n");

        $i = 1;
        $done = false;
        $plot_string = "";
        while (!$done) {
            $specLineName = "specLineSS$i";
            $plotStringName = "plotStringSS$i";
            if (!isset($specs[$specLineName]))
                $done = true;
            else {
                fwrite($fh, $specs[$specLineName] . "\r\n");
                $plot_string .= $specs[$plotStringName];
                $i++;
            }
        }
        for ($i=0;$i<sizeof($data_file);$i++){
            $plot_string .= ", '$data_file[$i]' using 1:2 title '$plottitle[$i]' with lines";
        }
        $plot_string .= "\r\n";
        fwrite($fh, $plot_string);
        fclose($fh);

        //Make the plot
        $GNUPLOT = $this->GNUplot;
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);

    }
    public function DrawPlotsIfNecessary(){
        if ($this->IsPlotNeeded('WCA_AmplitudeStability',$this->_WCAs->GetValue('amp_stability_url'))){
            $this->Plot_AmplitudeStability();
        }
        if ($this->IsPlotNeeded('WCA_AMNoise',$this->_WCAs->GetValue('amnz_pol0_url'))){
            $this->Plot_AMNoise();
        }
        if ($this->IsPlotNeeded('WCA_OutputPower',$this->_WCAs->GetValue('op_vs_freq_url'))){
            $this->Plot_OutputPower();
        }
        if ($this->IsPlotNeeded('WCA_PhaseNoise',$this->_WCAs->GetValue('phasenoise_url'))){
            $this->Plot_PhaseNoise();
        }
    }
    public function RedrawAllPlots(){
        $this->Plot_AmplitudeStability();
        $this->Plot_AMNoise();
        $this->Plot_OutputPower();
        $this->Plot_PhaseNoise();
    }

    public function IsPlotNeeded($tableName, $ImageURL){
        $PlotNeeded = false;

        if ($ImageURL == ""){
            $rCheck = $this->db_pull->q_other('Check', $this->keyId, $this->fc, NULL, NULL, NULL, NULL, $tableName);
            if (@mysql_num_rows($rCheck) > 0){
                $PlotNeeded = true;
            }
        }
        return $PlotNeeded;
    }

    public function convert_charset($item)
    {
        if ($unserialize = unserialize($item))
        {
            foreach ($unserialize as $key => $value)
            {
                $unserialize[$key] = @iconv('windows-1256', 'UTF-8', $value);
            }
            $serialize = serialize($unserialize);
            return $serialize;
        }
        else
        {
            return @iconv('windows-1256', 'UTF-8', $item);
        }
    }
    private function ExportCSV($datatype){
        echo '<meta http-equiv="Refresh" content="1;url=export_to_csv.php?keyId='.$this->keyId.'&datatype='.$datatype.'">';
    }
}
?>