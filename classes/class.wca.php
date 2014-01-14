<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.dboperations.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.frontend.php');

class WCA extends FEComponent{
    var $_WCAs;
    var $LOParams; //array of LO Params (Generic Table objects)
    var $facility;
    var $swversionwca;
    var $ConfigId;
    var $ConfigLinkId;
    var $fkDataStatus;
    var $writedirectory;
    var $url_directory;
    var $fc; //facility code
    var $GNUplot; //GNUPlot location
    var $logfile;
    var $logfile_fh;

    var $tdh_amnoise;         //TestData_header record object for AM Noise
    var $tdh_ampstab;         //TestData_header record object for Amplitude Stability
    var $tdh_outputpower;     //TestData_header record object for Output Power
    var $tdh_phasenoise;     //TestData_header record object for Phase Noise
    var $tdh_phasejitter;     //TestData_header record object for Phase Jitter

    function __construct() {
        $this->fkDataStatus = '7';
        $this->swversionwca = "1.0.2";
        require(site_get_config_main());
        $this->writedirectory = $wca_write_directory;
        $this->url_directory = $wca_url_directory;
        $this->GNUplot = $GNUplot;
        $this->ZipDirectory = $this->writedirectory . "zip";
        //echo "consruct writedir= $wca_write_directory<br>";
    }

    public function Initialize_WCA($in_keyId, $in_fc){
        $this->logging = 0;
        $this->fc = $in_fc;
        $this->fkDataStatus = '7';
        $this->swversion = "1.0.2";
        /*
         * 1.0.2  MTM:  fix "set label...screen" commands to gnuplot
         */


        parent::Initialize_FEComponent($in_keyId, $in_fc);

        $this->writedirectory = $this->writedirectory . "wca"
        . $this->GetValue('Band') . "_" . $this->GetValue('SN') . "/";


        $this->url_directory = $this->url_directory . "wca"
        . $this->GetValue('Band') . "_" . $this->GetValue('SN') . "/";

        $qWCA="SELECT keyId FROM WCAs WHERE fkFE_Component = $this->keyId LIMIT 1;";
        //$qWCA="SELECT keyId FROM WCAs WHERE fkFE_Component = $this->keyId LIMIT 1;";
        $rWCA=@mysql_query($qWCA,$this->dbconnection);
        $WCAs_id = @mysql_result($rWCA,0);
        $this->_WCAs = New GenericTable();
        $this->_WCAs->Initialize("WCAs", $WCAs_id,"keyId",$this->fc,'fkFacility');

        $q = "SELECT keyId
              FROM LOParams
              WHERE fkComponent = " . $this->keyId . "
              ORDER BY FreqLO ASC;";
        $r = @mysql_query($q,$this->dbconnection);
        $lopcount = 1;
        while ($row = @mysql_fetch_array($r)){
            $this->LOParams[$lopcount] = new GenericTable();
            $this->LOParams[$lopcount]->Initialize('WCA_LOParams',$row[0],'keyId',$this->fc,'fkFacility');
            $lopcount += 1;
        }

        //Get FE_Config information
        $qcfg = "select FE_Config.keyFEConfig AS ConfigId,
        FE_ConfigLink.keyId AS ConfigLinkId, Front_Ends.keyFrontEnds AS FEId,
        Front_Ends.SN AS FESN
        from FE_Config, FE_ConfigLink, Front_Ends
        WHERE FE_ConfigLink.fkFE_Components = $this->keyId
        AND FE_ConfigLink.fkFE_Config = FE_Config.keyFEConfig
        AND FE_Config.fkFront_Ends = Front_Ends.keyFrontEnds
        AND FE_Config.keyFacility = $this->fc
        AND FE_Config.keyFacility = FE_ConfigLink.fkFE_ConfigFacility
        AND Front_Ends.keyFacility = $this->fc;";
        //echo $qcfg . "<br>";
        $rcfg = @mysql_query($qcfg,$this->dbconnection);

        $this->ConfigId     = @mysql_result($rcfg,0,0);
        $this->ConfigLinkId = @mysql_result($rcfg,0,1);
        $this->FEId         = @mysql_result($rcfg,0,2);
        $this->FESN         = @mysql_result($rcfg,0,3);

        //Status location and notes
        $qsln = "SELECT MAX(keyId) FROM FE_StatusLocationAndNotes
                             WHERE fkFEComponents = $this->keyId;";
        $rsln = @mysql_query($qsln,$this->dbconnection);
        $slnid = @mysql_result($rsln,0,0);
        $this->sln = new GenericTable();
        $this->sln->Initialize("FE_StatusLocationAndNotes",$slnid,"keyId");

        //Facility
        $this->facility = new GenericTable();
        $this->facility->Initialize('Locations',$this->GetValue('fkFacility'),'keyId');
        //echo "facility info:<br>";
        //echo $this->facility->GetValue('Notes') . "<br>";

        //Test data header objects
        $qtdh = "SELECT keyId FROM TestData_header WHERE
                 fkFE_Components = $this->keyId and fkTestData_Type = 47;";
        $rtdh = @mysql_query($qtdh,$this->dbconnection);
        $this->tdh_phasejitter = new TestData_header();
        $this->tdh_phasejitter->Initialize_TestData_header(@mysql_result($rtdh,0,0), $this->GetValue('keyFacility'));

        $qtdh = "SELECT keyId FROM TestData_header WHERE
                 fkFE_Components = $this->keyId and fkTestData_Type = 45;";
        $rtdh = @mysql_query($qtdh,$this->dbconnection);
        $this->tdh_ampstab = new TestData_header();
        $this->tdh_ampstab->Initialize_TestData_header(@mysql_result($rtdh,0,0), $this->GetValue('keyFacility'));

        $qtdh = "SELECT keyId FROM TestData_header WHERE
                 fkFE_Components = $this->keyId and fkTestData_Type = 46;";
        $rtdh = @mysql_query($qtdh,$this->dbconnection);
        $this->tdh_outputpower = new TestData_header();
        $this->tdh_outputpower->Initialize_TestData_header(@mysql_result($rtdh,0,0), $this->GetValue('keyFacility'));

        $qtdh = "SELECT keyId FROM TestData_header WHERE
                 fkFE_Components = $this->keyId and fkTestData_Type = 48;";
        $rtdh = @mysql_query($qtdh,$this->dbconnection);
        $this->tdh_phasenoise = new TestData_header();
        $this->tdh_phasenoise->Initialize_TestData_header(@mysql_result($rtdh,0,0), $this->GetValue('keyFacility'));

        $qtdh = "SELECT keyId FROM TestData_header WHERE
                 fkFE_Components = $this->keyId and fkTestData_Type = 44;";
        $rtdh = @mysql_query($qtdh,$this->dbconnection);
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

    public function NewRecord_WCA(){
        require(site_get_config_main());
        parent::NewRecord('FE_Components', 'keyId',$fc,'keyFacility');
        parent::SetValue('fkFE_ComponentType',11);
        $this->fc = $fc;
        $this->_WCAs->NewRecord('WCAs');
        $this->_WCAs->fc = $fc;
        $this->_WCAs->SetValue('fkFE_Component',$this->keyId);
        $this->_WCAs->SetValue('keyFacility',$fc);

        $q_status = "INSERT INTO FE_StatusLocationAndNotes
        (fkFEComponents, fkLocationNames,fkStatusType)
        VALUES($this->keyId,$this->fc,'7');";
        $r_status = @mysql_query($q_status, $this->dbconnection);
    }

    public function AddNewLOParams(){
        switch($this->GetValue('Band')){
            case 1:
                $FreqLO = 27.3;
                break;
            case 2:
                $FreqLO = 79;
                break;
            case 3:
                $FreqLO = 92;
                break;
            case 4:
                $FreqLO = 133;
                break;
            case 5:
                $FreqLO = 171;
                break;
            case 6:
                $FreqLO = 221;
                break;
            case 7:
                $FreqLO = 283;
                break;
            case 8:
                $FreqLO = 393;
                break;
            case 9:
                $FreqLO = 610;
                break;
            case 10:
                $FreqLO = 795;
                break;
        }

        $q = "Select * from WCA_LOParams WHERE fkComponent = $this->keyId;";
        $r = @mysql_query($q,$this->dbconnection);
        $numrows = @mysql_num_rows($r);
        if ($numrows < 1){
            $qn = "Insert into WCA_LOParams(fkComponent,FreqLO,VDP0,VDP1,VGP0,VGP1)
                VALUES (".$this->keyId.",".$FreqLO.",
                0,0,'".$this->_WCAs->GetValue('VG0')."','".$this->_WCAs->GetValue('VG1')."');";
            $rn = @mysql_query($qn,$this->dbconnection);
        }
    }

    public function Update_WCA(){
        if ($this->password == "nrao1234"){
            parent::Update();
            $this->_WCAs->Update();
        }
    }

    public function DisplayData_WCA(){
        require(site_get_config_main());
        echo "<br><font size='+2'><b>WCA Information</b></font><br>";
        echo "<form action='" . $_SERVER["PHP_SELF"] . "' method='POST'>";
        echo "<div style ='width:100%;height:50%'>";
        //echo "<div align='right' style ='width:50%;height:30%'>";

        $this->DisplayMainData();

        echo "<br><br>";

        echo "<br>Enter password to upload or save changes.<br>";
        echo "PASSWORD: <input type='text' name='password' size='10' maxlength='200' value = ''><br>";

        echo "<input type='hidden' name='" . $this->keyId_name . "' value='$this->keyId'>";
        if ($this->fc == ''){
            echo "<input type='hidden' name='fc' value='$fc'>";
        }
        if ($this->fc != ''){
            echo "<input type='hidden' name='fc' value='$this->fc'>";
        }

        echo "<input type='submit' name = 'submitted' value='SAVE CHANGES'>";
        echo "<input type='submit' name = 'deleterecord' value='DELETE RECORD'><br>";

        if ($this->keyId != ""){
            echo "<table cellspacing='20'>";
            echo "<tr><td>";
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
        $qpj = "SELECT LO, Jitter, Pol FROM WCA_PhaseJitter WHERE fkHeader = " .$this->tdh_phasejitter->keyId . "
                ORDER BY Pol ASC, LO ASC;";

        $rpj = @mysql_query($qpj,$this->dbconnection);

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
            echo "<td><b>
            <a href='https://safe.nrao.edu/php/ntc/FEConfig/ShowFEConfig.php?key=" . $this->ConfigId  . "'>
            ".$this->FESN . "</a></b></td>";
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
            echo "<th>ESN<font color='#cc3300'>*</font></th>";
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
            echo "<th></th>";
            echo "<td>";
            echo "<b><a href='export_to_ini_wca.php?keyId=$this->keyId&wca=1'>Click for INI file</b></a>";

            echo "</td>";
        echo "</tr>";

        echo "</table></div>";
        echo "<br>Notes:<input type='text' name='Notes' size='50'
        maxlength='200' value = '".$this->GetValue('Notes')."'>";

        echo '<br><br><font color = "#ff0000"><b>
                      * Caution:  </b></font>The <b>ESN</b> is recorded as CRC-SerNum-FamilyCode (MSB to LSB).<br>
                      The FEMC reports it FamilyCode first. An ESN recorded as "A1 B2 C3 D4 E5 F6 A7 B8"<br>
                      in this database will be reported as "B8 A7 F6 E5 D4 C3 B2 A1" using the FEMC.';
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

        /*
        echo "<tr>";
            echo "<th>Notes</th>";
            echo "<td>".$this->sln->GetValue('Notes')."</td>";
        echo "</tr>";
        */

        //TEMPORARILY DISABLED
        /*
        echo "<tr>";
            echo "<th></th>";
            echo "<td>";
            echo "<b><a href='../testdata/export_to_ini.php?keyId=$this->keyId&wca=1'>Click for INI file</b></a>";

            echo "</td>";
        echo "</tr>";
        */
        echo "</table></div>";
    }

    public function Display_LOParams(){
        $q = "SELECT TS FROM WCA_LOParams
            WHERE fkComponent = $this->keyId
            ORDER BY FreqLO ASC
            LIMIT 1;";
        $r = @mysql_query($q,$this->dbconnection);
        $ts = @mysql_result($r,0,0);
        $band = $this->GetValue('Band');
        $sn = $this->GetValue('SN');

        //if (@mysql_num_rows($r) > 0){
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

        $q = "SELECT * FROM WCA_LOParams
              WHERE fkComponent = $this->keyId
              ORDER BY FreqLO ASC;";
        $r = @mysql_query($q,$this->dbconnection);
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

    public function Display_MaxSafePowerLevels(){
        echo "<br><br><br>";

        echo '
        <div style= "width:400px">
        <table id = "table1" align="left" cellspacing="1" cellpadding="1" width="60%" >
          <tr class="alt">
            <th align = "center" colspan = "7"><font size="+1" >
                 <b>MAX SAFE OPERATING PARAMETERS</b>
            </th>
          </tr>
          <tr>
              <th><b>FreqLO (GHz)</b></th>
              <th><b>Digital Setting VD0</b></th>
              <th><b>Digital Setting VD1</b></th>
            <th><b>Drain Voltage VD0</b></th>
            <th><b>Drain Voltage VD1</b></th>
          </tr>';

        $qMSP="SELECT * FROM WCA_MaxSafePower WHERE
        fkFE_Component = $this->keyId
        AND fkFacility = $this->fc;";
        $rMSP=@mysql_query($qMSP,$this->dbconnection);
        $bg_color = "";
        while ($rowMSP = @mysql_fetch_array($rMSP)){
            $bg_color = ($bg_color=="#ffffff" ? '#dddddd' : "#ffffff");


            echo "<tr bgcolor='$bg_color'>
                    <td>".$rowMSP['FreqLO']."</td>
                    <td>".$rowMSP['VD0_setting']."</td>
                    <td>".$rowMSP['VD1_setting']."</td>
                    <td>".$rowMSP['VD0']."</td>
                    <td>".$rowMSP['VD1']."</td>
                  </tr>";


        }
        echo "</table></div><br>";

    }

    public function Display_GateVoltages(){


        $qbias = "select distinct(FreqLO), VG0, VG1 from WCA_OutputPower
        where fkFE_Component = $this->keyId limit 1;";
        $rbias = @mysql_query($qbias,$this->dbconnection);

        echo "<table>";
        echo "<tr>";
        echo "<td>";
        echo "<div style = 'width: 150px'><br><br>";
        echo "<table id = 'table1'>";
        echo "
        <tr>
        <th colspan = '2'>Gate Voltages</th>
        </tr>
        <tr>
        <th>VG0</th>
        <th>VG1</th>
        </tr>";
        while ($rowbias = @mysql_fetch_array($rbias)){
            echo "<tr>";
            echo "<td>$rowbias[1]</td>";
            echo "<td>$rowbias[2]</td>";
            echo "</tr>";
        }
        echo "</table></div>";

        echo "</td>";

        echo "<td>";
        echo "<div style = 'width: 300px'><br><br>";
        echo "<table id = 'table1'>";
        echo "
        <tr>
        <th colspan = '2'>YIG Frequencies</th>
        </tr>
        <tr>
        <th>YIG HIGH (GHz)</th>
        <th>YIG LOW (GHz)</th>
        </tr>";

        echo "<tr>";
        echo "<td><input type='text' name='FloYIG' size='5' maxlength='200' value = '".$this->_WCAs->GetValue('FloYIG')."'></td>";
        echo "<td><input type='text' name='FhiYIG' size='5' maxlength='200' value = '".$this->_WCAs->GetValue('FhiYIG')."'></td>";
        echo "</tr>";

        echo "</table></div>";
        echo "</td></tr>";
        echo "</table>";

    }

    public function Display_uploadform(){
        echo '
        <p><div style="width:500px;height:80px; align = "left"></p>
        <!-- The data encoding type, enctype, MUST be specified as below -->
        <form enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '" method="POST">
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
                    <td align = "center"><input type="submit" name="draw_phasenoise" value="Redraw Phase Noise"></td></tr>';
            echo "<tr><td align = 'right'>PASSWORD: <input type='text' name='password' size='10' maxlength='200' value = ''>";
            echo "<input type='hidden' name= 'fc' value='$this->fc' />";
            echo '<input type="submit" name= "submit_datafile" value="Submit" /></td>
                    <td align = "center"><input type="submit" name="draw_all" value="REDRAW ALL PLOTS"></td></tr>
            </table>
        </form>
        </div>';
        echo "<br>";
        echo "<br>";
    }
    public function RequestValues_WCA(){
        $this->password = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';
        parent::RequestValues();

        if (isset($_REQUEST['deleterecord_forsure'])){
            //if($this->password == "nrao1234"){
                $this->DeleteRecord_WCA();
            //}
            //if($this->password != "nrao1234"){
                //echo "<font color = '#ff0000'><b>Incorrect password. Record NOT deleted.</b></font><br>";
            //}
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

        if (isset($_REQUEST['password'])){
            $this->password = $_REQUEST['password'];
        }


        if (isset($_REQUEST['submit_datafile'])){
            if($this->password == "nrao1234"){
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

            }
            if($this->password != "nrao1234"){
                echo "<font color = '#ff0000'><b>Incorrect password. Files NOT uploaded.</b></font><br>";
            }
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
        $qDel = "DELETE FROM WCAs WHERE fkFE_Component = $this->keyId;";
        $rDel = @mysql_query($qDel,$this->dbconnection);
        $qDel = "DELETE FROM WCA_AMNoise WHERE fkHeader = ".$this->tdh_amnoise->keyId.";";
        $rDel = @mysql_query($qDel,$this->dbconnection);
        $qDel = "DELETE FROM WCA_MaxSafePower WHERE fkFE_Component = $this->keyId;";
        $rDel = @mysql_query($qDel,$this->dbconnection);
        $qDel = "DELETE FROM WCA_LOParams WHERE fkFE_Component = $this->keyId;";
        $rDel = @mysql_query($qDel,$this->dbconnection);
        $qDel = "DELETE FROM WCA_OutputPower WHERE fkHeader = ".$this->tdh_outputpower->keyId.";";
        $rDel = @mysql_query($qDel,$this->dbconnection);
        $qDel = "DELETE FROM WCA_PhaseNoise WHERE fkHeader = ".$this->tdh_phasenoise->keyId.";";
        $rDel = @mysql_query($qDel,$this->dbconnection);
        $qDel = "DELETE FROM WCA_PhaseJitter WHERE fkHeader = ".$this->tdh_phasejitter->keyId.";";
        $rDel = @mysql_query($qDel,$this->dbconnection);
        $qDel = "DELETE FROM WCA_AmplitudeStability WHERE fkHeader = ".$this->tdh_ampstab->keyId.";";
        $rDel = @mysql_query($qDel,$this->dbconnection);
        $qDel = "DELETE FROM FE_ConfigLink WHERE fkFE_Components = $this->keyId;";
        $rDel = @mysql_query($qDel,$this->dbconnection);
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

    public function Upload_INI_file($datafile_name, $datafile_tmpname){
        $ini_array = parse_ini_file($datafile_tmpname, true);
        $sectionname = '~WCA' . $this->GetValue('Band') . "-" . $this->GetValue('SN');
        $CheckBand = $ini_array[$sectionname]['Band'];
        $wcafound = 0;
        if ($CheckBand == $this->GetValue('Band')){
            $wcafound = 1;
        }
        if ($wcafound != 1){

            $ErrStr = "Section [$sectionname] not found in file $datafile_name.";
            $this->AddError($ErrStr);
            $this->AddError("another line.");
        }
        if ($wcafound == 1){

            //Remove this CCA from the front end
            $dbops = new DBOperations();
            //Preserve these values in the new SLN record
            $oldStatus = $this->sln->GetValue('fkStatusType');
            $oldLocation = $this->sln->GetValue('fkLocationNames');
            //Get old status and location for the front end
            $wcaFE = new FrontEnd();
            $this->GetFEConfig();
            $wcaFE->Initialize_FrontEnd_FromConfig($this->FEConfig, $this->FEfc);
            $oldStatusFE = $wcaFE->fesln->GetValue('fkStatusType');
            $oldLocationFE = $wcaFE->fesln->GetValue('fkLocationNames');
            $dbops->RemoveComponentFromFrontEnd($this->GetValue('keyFacility'), $this->keyId, '',-1,-1);
            $FEid_old       = $this->FEid;
            $this->GetFEConfig();
            //Create new component record, duplicate everything from the existing.
            //Save old key value
            $keyIdOLD = $this->keyId;
            $this->DuplicateRecord_WCA();
            $keyIdNEW = $this->keyId;

            //Copy Max Safe Operating Parameters
            $qMS = "SELECT * FROM WCA_MaxSafePower WHERE fkFE_Component = $keyIdOLD ORDER BY FreqLO ASC;";
            $rMS = @mysql_query($qMS,$this->dbconnection);
            while ($rowMS = @mysql_fetch_array($rMS)){
                $qMSnew  = "INSERT INTO WCA_MaxSafePower(fkFacility,FreqLO,VD0_setting,VD1_setting,VD0,VD1,fkFE_Component) ";
                $qMSnew .= "VALUES('". $rowMS['fkFacility'] . "','" . $rowMS['FreqLO'] . "','" . $rowMS['VD0_setting'];
                $qMSnew .= "','". $rowMS['VD1_setting'] ."','". $rowMS['VD0'] ."','". $rowMS['VD1'] ."','$keyIdNEW')";
                $rMSnew = @mysql_query($qMSnew,$this->dbconnection);
            }

            //Copy Yig settings

            //Notes for the SLN record of new component
            $Notes = "Configuration changed on " . date('r') . ". ";

            //Get rid of any existing LO Params
            $q = "DELETE FROM WCA_LOParams WHERE fkComponent = $this->keyId;";
            $r = @mysql_query($q,$this->dbconnection);
            //Get rid of any existing WCAs table records
            $q = "DELETE FROM WCAs WHERE fkFE_Component = $this->keyId;";
            $r = @mysql_query($q,$this->dbconnection);

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
                $rnew = @mysql_query($qnew);

                if ($i == 1){
                    $VG0 = $VGP0;
                    $VG1 = $VGP1;
                }
            }
            $FLOYIG = $ini_array[$sectionname]['FLOYIG'];
            $FHIYIG = $ini_array[$sectionname]['FHIYIG'];

            //Copy Yig settings
            $qYIG = "SELECT * FROM WCAs WHERE fkFE_Component = $this->keyId;";
            $rYIG = @mysql_query($qYIG,$this->dbconnection);
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
                $rwcas = @mysql_query($qwcas,$this->dbconnection);
            }

            //Done reading from INI file.
            $updatestring = "Updated config for WCA " . $this->GetValue('Band') . "-" . $this->GetValue('SN') . ".";

            //Add WCA to Front End
            $feconfig = $this->FEfc;
            $dbops->AddComponentToFrontEnd($FEid_old, $this->keyId, $this->FEfc, $this->GetValue('keyFacility'), '', $updatestring, ' ',-1);
            $dbops->UpdateStatusLocationAndNotes_Component($this->GetValue('keyFaciliy'), $oldStatus, $oldLocation,$updatestring,$this->keyId, ' ','');
            $this->GetFEConfig();
            $dbops->UpdateStatusLocationAndNotes_FE($this->FEfc, $oldStatusFE, $oldLocationFE,$updatestring,$this->FEConfig, $this->FEConfig, ' ','');
            unset($dbops);
        }//end if wca found == 1
        unlink($datafile_tmpname);
    }

    public function DuplicateRecord_WCA(){
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
        $qDelete_AS = "DELETE FROM WCA_MaxSafePower
        WHERE fkFE_Component = $this->keyId
        AND fkFacility = $this->fc;";
        $rDelete_AS = @mysql_query($qDelete_AS,$this->dbconnection);

        for($i=0; $i<sizeof($filecontents); $i++) {
            $line_data = trim($filecontents[$i]);
            $RowArray   = explode(",", $line_data);
            if (is_numeric(substr($RowArray[0],0,1)) == true){
                $TS = $RowArray[3];
                $FreqLO = $RowArray[4];
                $VD0_setting = $RowArray[5];
                $VD1_setting = $RowArray[6];
                $VD0 = $RowArray[7];
                $VD1 = $RowArray[8];

                $qAS = "INSERT INTO WCA_MaxSafePower
                (TS,FreqLO,VD0_setting,VD1_setting,VD0,VD1,fkFE_Component, fkFacility)
                VALUES ('$RowArray[3]','$RowArray[4]','$RowArray[5]','$RowArray[6]',
                '$RowArray[7]','$RowArray[8]','$this->keyId','$this->fc')";
                $rAS = @mysql_query($qAS,$this->dbconnection);
            }
        }
        unlink($datafile_name);
        unset($tdh);
        //fclose($filecontents);
    }

    public function Upload_AmplitudeStability_file($datafile_name){
        //Test Data Header object
        //Delete any existing header records
        $qtdh = "DELETE FROM TestData_header
            WHERE fkFE_Components = $this->keyId
            AND fkTestData_Type = 45;";
        $rtdh = @mysql_query($qtdh,$this->dbconnection);
        $this->tdh_ampstab = new GenericTable();
        $this->tdh_ampstab->NewRecord("TestData_header", 'keyId',$this->GetValue('keyFacility'),'keyFacility');
        $this->tdh_ampstab->SetValue('fkTestData_Type',45);
        $this->tdh_ampstab->SetValue('fkDataStatus',$this->fkDataStatus);
        $this->tdh_ampstab->SetValue('fkFE_Components',$this->keyId);
        $this->tdh_ampstab->Update();


        $filecontents = file($datafile_name);
        $qDelete_AS = "DELETE FROM WCA_AmplitudeStability WHERE fkHeader = " . $this->tdh_ampstab->keyId . ";";
        $rDelete_AS = @mysql_query($qDelete_AS,$this->dbconnection);

        $once = 0;
        for($i=0; $i<sizeof($filecontents); $i++) {
            $line_data = trim($filecontents[$i]);
            $tempArray   = explode(",", $line_data);
            if (is_numeric(substr($tempArray[0],0,1)) == true){
                $TS = $tempArray[3];

                if ($once == 0){
                    $this->tdh_ampstab->SetValue('TS',$TS);
                    $this->tdh_ampstab->Update();
                    $once = 1;
                }
                $FreqLO = $tempArray[4];
                $Pol = $tempArray[5];
                $Time = $tempArray[6];
                $AllanVar = $tempArray[7];

                $qAS = "INSERT INTO WCA_AmplitudeStability(fkHeader,FreqLO,Pol,Time,AllanVar)
                VALUES('" . $this->tdh_ampstab->keyId . "','$FreqLO','$Pol','$Time','$AllanVar')";
                $rAS = @mysql_query($qAS,$this->dbconnection);
            }
        }
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
        $qFindLO = "SELECT DISTINCT(FreqLO) FROM WCA_AmplitudeStability
        WHERE fkHeader = " . $this->tdh_ampstab->keyId . "
        ORDER BY FreqLO ASC;";
        $rFindLO = @mysql_query($qFindLO,$this->dbconnection);
        $rowLO=@mysql_fetch_array($rFindLO);

        $datafile_count=0;
        $spec_value = 0.0000001;

        for ($j=0;$j<=1;$j++){
            for ($i=0;$i<=sizeof($rowLO);$i++){
                $CurrentLO = @mysql_result($rFindLO,$i);
                $DataSeriesName = "LO $CurrentLO GHz, Pol $j";

                $q = "SELECT Time,AllanVar FROM WCA_AmplitudeStability
                WHERE FreqLO = $CurrentLO
                AND Pol = $j
                AND fkHeader = " . $this->tdh_ampstab->keyId . "
                ORDER BY Time ASC;";
                $r = @mysql_query($q,$this->dbconnection);

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

        //echo "PlotURL = $image_url<br>";
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
        //fwrite($fhc, "set label '$TS' at screen 0.5, 0.95\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set log xy\r\n");
        fwrite($fh, "set key outside\r\n");
        fwrite($fh, "set ylabel 'Allan Variance'\r\n");
        fwrite($fh, "set xlabel 'Allan Time, T (=Integration, Tau)'\r\n");

        $ymax = pow(10,-5);
        //fwrite($fh, "set yrange [:0.0001]\r\n");
        fwrite($fh, "set yrange [:$ymax]\r\n");

        fwrite($fh, "f1(x)=((x>500) && (x<100000)) ? 0.00000009 : 1/0\r\n");
        //fwrite($fh, "f2(x)=((x>299999) && (x<350000)) ? 0.000001 : 1/0\r\n");
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
        $qtdh = "DELETE FROM TestData_header
            WHERE fkFE_Components = $this->keyId
            AND fkTestData_Type = 44;";
        $rtdh = @mysql_query($qtdh,$this->dbconnection);
        $this->tdh_amnoise = new GenericTable();
        $this->tdh_amnoise->NewRecord("TestData_header", 'keyId',$this->GetValue('keyFacility'),'keyFacility');
        $this->tdh_amnoise->SetValue('fkTestData_Type',44);
        $this->tdh_amnoise->SetValue('fkDataStatus',$this->fkDataStatus);
        $this->tdh_amnoise->SetValue('fkFE_Components',$this->keyId);
        $this->tdh_amnoise->Update();

        $filecontents = file($datafile_name);
        $qDelete_AM = "DELETE FROM WCA_AMNoise WHERE fkHeader = " . $this->tdh_amnoise->keyId . ";";
        $rDelete_AM = @mysql_query($qDelete_AM,$this->dbconnection);

        $once = 0;
        for($i=0; $i<sizeof($filecontents); $i++) {
            $line_data = trim($filecontents[$i]);
            $tempArray   = explode(",", $line_data);
            if (is_numeric(substr($tempArray[0],0,1)) == true){
                $TS = $tempArray[3];
                if ($once == 0){
                    $this->tdh_amnoise->SetValue('TS',$TS);
                    $this->tdh_amnoise->Update();
                    $once = 1;
                }

                $AMNoise = $tempArray[4];
                $FreqLO = $tempArray[5];
                $FreqIF = $tempArray[6];
                $Pol = $tempArray[7];
                $DrainVoltage = $tempArray[8];
                $GateVoltage = $tempArray[9];

                $qAM = "INSERT INTO WCA_AMNoise(fkHeader,AMNoise,FreqLO,FreqIF,Pol,DrainVoltage,GateVoltage)
                VALUES('" . $this->tdh_amnoise->keyId . "','$AMNoise','$FreqLO','$FreqIF','$Pol','$DrainVoltage','$GateVoltage')";
                if ($i < 10){
                    //echo $qAM . "<br>";
                }
                $rAM = @mysql_query($qAM,$this->dbconnection);

            }
        }
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
        //echo "AMNoise id= $this->tdhid_amnoise<br>";

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
        //$imagedirectory .= $this->GetValue('Band') . "_" . $this->GetValue('SN') . "/";
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
            $qFreqLO = "SELECT FreqLO, AMNoise FROM WCA_AMNoise
            WHERE fkHeader = " . $this->tdh_amnoise->keyId . "
            AND FreqIf >= $FreqLOW
            AND FreqIF <= $FreqHI
            AND Pol = $pol
            AND fkFacility = $this->fc
            ORDER BY FreqLO ASC;";
            $rFreqLO  = @mysql_query($qFreqLO ,$this->dbconnection);
            while ($row = @mysql_fetch_array($rFreqLO)){
                $amnzarr[0][$arrct]=$row[0];
                $amnzarr[1][$arrct]=$row[1];
                $arrct += 1;
            }
            //echo $qFreqLO . "<br>";
            //echo "Array test...<br>";
            //echo count($amnzarr[0]) . "<br>";
            //echo "0: ".$amnzarr[0][5]."<br>";
            //echo "1: ".$amnzarr[1][5]."<br>";

            $arrct = 0;
            $qlo = "SELECT DISTINCT(FreqLO) FROM WCA_AMNoise
            WHERE fkHeader = " . $this->tdh_amnoise->keyId . "
            AND FreqIf >= $FreqLOW
            AND FreqIF <= $FreqHI
            AND Pol = $pol
            AND fkFacility = $this->fc
            ORDER BY FreqLO ASC;";
            $rlo = @mysql_query($qlo,$this->dbconnection);
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
                //echo "Freq, avg= $freqarr[$i], $avgamnz<br>";
                $stringData = "$freqarr[$i]\t$avgamnz\r\n";
                //echo $stringData . "<br>";
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


            $qNumIF = "SELECT DISTINCT(FreqIF) FROM WCA_AMNoise
            WHERE fkHeader = " . $this->tdh_amnoise->keyId . "
            AND Pol = $pol
            AND fkFacility = $this->fc;";
            $rNumIF=@mysql_query($qNumIF,$this->dbconnection);
            $NumIF = @mysql_num_rows($rNumIF);


            $data_file[$pol]= $this->writedirectory . "wca_amnoise_data_pol$pol.txt";
            if (file_exists($data_file[$pol])){
                unlink($data_file[$pol]);
            }
            $fh = fopen($data_file[$pol], 'w');


            $IFcount = 0;
            $q="SELECT FreqIF,FreqLO,AMNoise FROM WCA_AMNoise
            WHERE fkHeader = " . $this->tdh_amnoise->keyId . "
            AND Pol = $pol
            AND fkFacility = $this->fc
            ORDER BY FreqLO ASC, FreqIF ASC;";
            $r=@mysql_query($q,$this->dbconnection);
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
        $qtdh = "DELETE FROM TestData_header
        WHERE fkFE_Components = $this->keyId
        AND fkTestData_Type = 47
        AND keyFacility = $this->fc;";
        $rtdh = @mysql_query($qtdh,$this->dbconnection);
        $qtdh = "DELETE FROM TestData_header
        WHERE fkFE_Components = $this->keyId
        AND fkTestData_Type = 48
        AND keyFacility = $this->fc;";
        $rtdh = @mysql_query($qtdh,$this->dbconnection);
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
        $qDelete_PN = "DELETE FROM WCA_PhaseNoise WHERE fkHeader = ".$this->tdh_phasenoise->keyId."
        AND keyFacility = $this->fc;";
        $rDelete_PN = @mysql_query($qDelete_PN,$this->dbconnection);

        $once = 0;
        for($i=0; $i<sizeof($filecontents); $i++) {

            $line_data = trim($filecontents[$i]);
            $RowArray   = explode(",", $line_data);
            if (is_numeric(substr($RowArray[0],0,1)) == true){
                if ($once ==0){
                    $this->tdh_phasenoise->SetValue('TS',$RowArray[3]);
                    $this->tdh_phasenoise->Update();
                    $this->tdh_phasejitter->SetValue('TS',$RowArray[3]);
                    $this->tdh_phasejitter->Update();
                    $once = 1;
                }
                $qPN = "INSERT INTO WCA_PhaseNoise
                (fkHeader,
                FreqLO,Pol,CarrierOffset,Lf)
                VALUES ('". $this->tdh_phasenoise->keyId  ."',
                '$RowArray[4]','$RowArray[5]','$RowArray[6]','$RowArray[7]')";
                $rPN = @mysql_query($qPN,$this->dbconnection);
            }
        }
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

        $qpjdel = "DELETE FROM WCA_PhaseJitter WHERE fkHeader = ".$this->tdh_phasenoise->keyId."
        AND fkFacility = $this->fc;";
        $rpjdel = @mysql_query($qpjdel,$this->dbconnection);

        $qlo = "SELECT DISTINCT(FreqLO), Pol FROM WCA_PhaseNoise
        WHERE fkHeader = " . $this->tdh_phasenoise->keyId . "
        AND fkFacility = $this->fc
        ORDER BY FreqLO ASC;";
        $rlo = @mysql_query($qlo, $this->dbconnection);



        while ($rowlo = @mysql_fetch_array($rlo)){
            $lo = $rowlo[0];
            $pol = $rowlo[1];
            $loarray[$loindex] = $lo;
            $jitterarray[$loindex] = $this->GetPhaseJitter($lo, $pol);

            $qpj = "INSERT INTO WCA_PhaseJitter (fkHeader,LO,Jitter,pol,fkFacility)
            VALUES (" . $this->tdh_phasejitter->keyId . ", $lo, $jitterarray[$loindex],$pol,$this->fc);";
            $rpj = @mysql_query($qpj,$this->dbconnection);


            $loindex += 1;
        }




        //write data file from database
        $qFindLO = "SELECT DISTINCT(FreqLO) FROM WCA_PhaseNoise
        WHERE fkHeader = ".$this->tdh_phasenoise->keyId."
        AND fkFacility = $this->fc
        ORDER BY FreqLO ASC;";
        $rFindLO = @mysql_query($qFindLO,$this->dbconnection);
        $rowLO=@mysql_fetch_array($rFindLO);

        $datafile_count=0;
        for ($j=0;$j<=1;$j++){
            for ($i=0;$i<=sizeof($rowLO);$i++){
                $CurrentLO = @mysql_result($rFindLO,$i);
                $DataSeriesName = "LO $CurrentLO GHz, Pol $j";

                $q = "SELECT CarrierOffset,Lf FROM WCA_PhaseNoise
                WHERE FreqLO = $CurrentLO
                AND Pol = $j
                AND fkHeader = ".$this->tdh_phasenoise->keyId."
                AND fkFacility = $this->fc
                ORDER BY CarrierOffset ASC;";
                $r = @mysql_query($q,$this->dbconnection);

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

        /*
         $this->tdh->SetValue('PlotURL',"$image_url");
        $tdh->Update();
        unset($tdh);
        */


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


        $q = "SELECT CarrierOffset,FreqLO,Lf FROM WCA_PhaseNoise
        WHERE fkHeader = ". $this->tdh_phasenoise->keyId . "
        AND FreqLO = $LOfreq
        AND Pol = $pol
        AND fkFacility = $this->fc
        ORDER BY CarrierOffset ASC;";

        $r = @mysql_query($q, $this->dbconnection);

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
        $qtdh = "DELETE FROM TestData_header
        WHERE fkFE_Components = $this->keyId
        AND fkTestData_Type = 46;";
        $rtdh = @mysql_query($qtdh,$this->dbconnection);
        $this->tdh_outputpower = new GenericTable();
        $this->tdh_outputpower->NewRecord("TestData_header", 'keyId',$this->GetValue('keyFacility'),'keyFacility');
        $this->tdh_outputpower->SetValue('fkTestData_Type',46);
        $this->tdh_outputpower->SetValue('fkDataStatus',$this->fkDataStatus);
        $this->tdh_outputpower->SetValue('fkFE_Components',$this->keyId);
        $this->tdh_outputpower->Update();

        $filecontents = file($datafile_name);
        $qDelete_OP = "DELETE FROM WCA_OutputPower
        WHERE fkHeader = ". $this->tdh_outputpower->keyId . "
        AND fkFacility = $this->fc;";
        $rDelete_OP = @mysql_query($qDelete_OP,$this->dbconnection);

        $once = 0;
        for($i=0; $i<sizeof($filecontents); $i++) {
            $line_data = trim($filecontents[$i]);
            $RowArray   = explode(",", $line_data);
            if (is_numeric(substr($RowArray[0],0,1)) == true){
                if ($once == 0){
                    $this->tdh_outputpower->SetValue('TS',$RowArray[3]);
                    $this->tdh_outputpower->Update();
                    $once = 1;
                }
                $PolTemp = $RowArray[6];
                if (strtolower($RowArray[6])=="a"){
                    $PolTemp = "0";
                }
                if (strtolower($RowArray[6])=="b"){
                    $PolTemp = "1";
                }

                $qOP = "INSERT INTO WCA_OutputPower
                (fkHeader,keyDataSet,
                FreqLO,Power,Pol,
                VD0,VD1,VG0,VG1,
                fkFacility)
                VALUES ('".$this->tdh_outputpower->keyId."','$RowArray[1]',
                '$RowArray[4]','$RowArray[5]','$PolTemp',
                '$RowArray[7]','$RowArray[8]','$RowArray[9]','$RowArray[10]',
                '$this->fc')";
                $rOP = @mysql_query($qOP,$this->dbconnection);
            }
        }
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
        $qTS = "SELECT VD0, VD1 FROM WCA_OutputPower
        WHERE fkHeader = ". $this->tdh_outputpower->keyId . "
        AND keyDataSet = 1
        AND fkFacility = $this->fc
        LIMIT 1;";
        $rTS = @mysql_query($qTS,$this->dbconnection);
        $rowTS=@mysql_fetch_array($rTS);
        $VD0=$rowTS[0];
        $VD1=$rowTS[1];

        $TS = $this->tdh_outputpower->GetValue('TS');

        $imagedirectory .= $this->writedirectory;

        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }
        $imagename = "WCA_OPvsFreq_SN" . $this->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";

        $image_url = $this->url_directory . $imagename;
        sleep(1);

        $plot_title = "WCA Band" . $this->GetValue('Band') . " SN" . $this->GetValue('SN') . " Output Power Vs. Frequency (VD0=$VD0, VD1=$VD1) ($TS)";


        $this->_WCAs->SetValue('op_vs_freq_url',$image_url);
        $this->_WCAs->Update();
        $imagepath = $imagedirectory . $imagename;
        $spec_value = 100;
        for ($pol=0;$pol<=1;$pol++){

            $data_file[$pol]= $this->writedirectory . "wca_opvsfreq_data$pol.txt";
            if (file_exists($data_file[$pol])){
                unlink($data_file[$pol]);
            }
            $fh = fopen($data_file[$pol], 'w');
            $qOP = "SELECT FreqLO,Power FROM WCA_OutputPower
            WHERE Pol = $pol
            AND fkHeader = ". $this->tdh_outputpower->keyId . "
            AND keyDataSet = 1
            AND fkFacility = $this->fc
            ORDER BY FreqLO ASC;";
            $rOP = @mysql_query($qOP,$this->dbconnection);
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
        $qx = "SELECT MAX(FreqLO) FROM WCA_OutputPower
        WHERE fkHeader = ". $this->tdh_outputpower->keyId . "
        AND fkFacility = $this->fc;";
        $rx = @mysql_query($qx,$this->dbconnection);
        $xMAX = @mysql_result($rx,0) + 1;
        fwrite($fh, "set xrange[:$xMAX]\r\n");

        fwrite($fh, "set xlabel 'LO Frequency (GHz)'\r\n");
        fwrite($fh, "set ylabel 'Output Power (mW)'\r\n");

        fwrite($fh, "set key outside\r\n");


        switch ($Band) {
            case 3:
                fwrite($fh, "f2(x)=((x>91.8) && (x<108)) ? 1.6 : 1/0\r\n"); //max spec
                $plot_string = "plot f2(x) title 'Spec' with lines lw 3";
                break;

            case 4:
                fwrite($fh, "f2(x)=((x>66) && (x<75)) ? 30 : 1/0\r\n");//max safe
                fwrite($fh, "f3(x)=((x>66.5) && (x<75)) ? 15 : 1/0\r\n");//min spec
                fwrite($fh, "f4(x)=((x>66) && (x<78)) ? 30 : 1/0\r\n");//max spec
                $plot_string = "plot f3(x) title 'Spec' with lines lw 3 lt 1";
                $plot_string .= ", f4(x) notitle with lines lw 3 lt 1 ";
                $plot_string .= ", f2(x) title 'Max Safe Operation' with lines lw 3 lt 2 ";
                break;

            case 5:
                fwrite($fh, "f2(x)=((x>83) && (x<101.5)) ? 15 : 1/0\r\n"); //max spec
                fwrite($fh, "f3(x)=((x>83) && (x<101.5)) ? 40 : 1/0\r\n");//max safe
                $plot_string = "plot f2(x) title 'Spec' with lines lw 3";
                $plot_string .= ", f3(x) title 'Max Safe Operation' with lines lw 3 ";
                break;

            case 6:
                fwrite($fh, "f2(x)=((x>73.7) && (x<88.3)) ? 20 : 1/0\r\n");//max spec
                fwrite($fh, "f3(x)=((x>73.7) && (x<88.3)) ? 40 : 1/0\r\n");//max safe
                $plot_string = "plot f2(x) title 'Spec' with lines lw 3";
                $plot_string .= ", f3(x) title 'Max Safe Operation' with lines lw 3 ";
                break;
            case 7:
                fwrite($fh, "f2(x)=((x>93.3) && (x<108)) ? 12 : 1/0\r\n");//max spec
                fwrite($fh, "f4(x)=((x>108) && (x<121.7)) ? 8 : 1/0\r\n");//max spec
                fwrite($fh, "f5(x)=((x>93.3) && (x<121.7)) ? 40 : 1/0\r\n");//max safe

                $plot_string = "plot f2(x) title 'Spec' with lines lw 3 lt 1";
                $plot_string .= ", f4(x) title 'Spec' with lines lw 3 lt 1";
                $plot_string .= ", f5(x) title 'Max Safe Operation' with lines lw 3 ";
                break;
            case 8:
                fwrite($fh, "f4(x)=((x>65.5) && (x<70)) ? 90 : 1/0\r\n");//max spec 1
                fwrite($fh, "f5(x)=((x>70) && (x<82)) ? 80 : 1/0\r\n");//max spec 2
                fwrite($fh, "f6(x)=((x>65.5) && (x<82)) ? 90 : 1/0\r\n");//max safe

                $plot_string = "plot f6(x) with lines lt 2 lw 3.2 title 'Max Safe Operation'";
                $plot_string .= ", f4(x) title 'Spec' with lines lw 2.7 lt 1";
                $plot_string .= ", f5(x) title 'Spec' with lines lw 3 lt 1";

                break;

            case 9:
                fwrite($fh, "f2(x)=((x>67.3) && (x<79.1)) ? 100 : 1/0\r\n");
                fwrite($fh, "f3(x)=((x>67.3) && (x<79.1)) ? 125 : 1/0\r\n");
                $plot_string = "plot f2(x) title 'Spec' with lines lw 3";
                $plot_string .= ", f3(x) title 'Max Safe Operation' with lines lw 3 ";
                break;

            case 10:
                fwrite($fh, "f2(x)=((x>88) && (x<98)) ? 60 : 1/0\r\n");//max spec
                fwrite($fh, "f3(x)=((x>98) && (x<105)) ? 80 : 1/0\r\n");//max spec

                $plot_string = "plot f2(x) title 'Spec' with lines lw 4 lt 1";
                $plot_string .= ", f3(x) title 'Spec' with lines lw 4 lt 1";
                break;

            default:
                fwrite($fh, "f1(x)=((x>=25) && (x<=25.01)) ? 0 : 1/0\r\n");
                $plot_string = "plot f1(x) notitle with lines lw 3";
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
        $spec_value_1 = 100;
        $spec_description_1 = 'Spec';

        $spec_value_2 = 0;
        $spec_description_2 = '';
        $enable_spec_2 = false;

        $Band = $this->GetValue('Band');
        switch ($Band) {
            case 3:
                $spec_value_1 = 1.6;
                break;
            case 4:
                $spec_value_1 = 15;
                $spec_value_2 = 30;
                $spec_description_1 = 'Spec < 75 GHz';
                $spec_description_2 = 'Spec >= 75 GHz)';
                $enable_spec_2 = true;
                break;
            case 5:
                $spec_value_1 = 15;
                break;
            case 6:
                $spec_value_1 = 20;
                break;
            case 7:
                $spec_value_1 = 8;
                break;
            case 8:
                $spec_value_1 = 80;
                break;
            case 9:
                $spec_value_1 = 100;
                break;
            case 10:
                $spec_value_1 = 60;
                $spec_value_2 = 80;
                $spec_description_1 = 'Spec < 98 GHz';
                $spec_description_2 = 'Spec >= 98 GHz';
                $enable_spec_2 = true;
                break;
        }

        $datafile_count=0;
        $qFindLO = "SELECT DISTINCT(FreqLO) FROM WCA_OutputPower
        WHERE fkHeader = ". $this->tdh_outputpower->keyId . "
        AND keyDataSet <> 1
        AND Pol = $pol
        AND fkFacility = $this->fc
        ORDER BY FreqLO ASC;";

        $rFindLO = @mysql_query($qFindLO,$this->dbconnection);
        $rowLO=@mysql_fetch_array($rFindLO);
        $i=0;
        while ($rowLO = mysql_fetch_array($rFindLO)){
            $CurrentLO = @mysql_result($rFindLO,$i);

            // TODO:   special meaining of keyDataSet for band 3?   Hmmm...
            if ($Band != 3){
                $q = "SELECT VD$pol,Power FROM WCA_OutputPower
                WHERE Pol = $pol
                AND fkHeader = ". $this->tdh_outputpower->keyId . "
                AND keyDataSet = 2
                AND FreqLO = $CurrentLO
                AND fkFacility = $this->fc
                ORDER BY VD$pol ASC;";
            }
            if ($Band == 3){
                $q = "SELECT VD$pol,Power FROM WCA_OutputPower
                WHERE Pol = $pol
                AND fkHeader = ". $this->tdh_outputpower->keyId . "
                AND keyDataSet <> 1
                AND FreqLO = $CurrentLO
                AND fkFacility = $this->fc
                ORDER BY VD$pol ASC;";
            }

            $r = @mysql_query($q,$this->dbconnection);

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
        $qFindLO = "SELECT DISTINCT(FreqLO) FROM WCA_OutputPower
        WHERE fkHeader = ". $this->tdh_outputpower->keyId . "
        AND keyDataSet = 3
        AND Pol = $pol
        AND fkFacility = $this->fc
        ORDER BY FreqLO ASC;";
        $rFindLO = @mysql_query($qFindLO,$this->dbconnection);
        $i=0;
        while ($rowLO = mysql_fetch_array($rFindLO)){
            $CurrentLO = @mysql_result($rFindLO,$i);

            $q = "SELECT VD$pol,Power FROM WCA_OutputPower
            WHERE Pol = $pol
            AND fkHeader = ". $this->tdh_outputpower->keyId . "
            AND keyDataSet = 3
            AND FreqLO = $CurrentLO
            AND fkFacility = $this->fc
            ORDER BY Power ASC, VD$pol ASC;";

            $r = @mysql_query($q,$this->dbconnection);


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

                    if ($Pwrarray[$m+1] != $Pwrarray[$m]){
                        $VDtemp=$VDarray[$m];
                        $ptemp1 = $Pwrarray[$m];
                        $ptemp2= $Pwrarray[$m+1];

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
                //unlink($data_file[$datafile_count]);
                fclose($fh);
                $datafile_count++;
            }
            $i++;
        }//end for i
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
        switch ($Band) {
            case 3:
                fwrite($fh, "set xrange[0:1.6]\r\n");

                fwrite($fh, "f1(x)=((x>=0.4) && (x<=1.6)) ? 0.5 : 1/0\r\n");
                $plot_string = "plot f1(x) title 'Spec' with lines lw 3";
                break;
            case 4:
                fwrite($fh, "set xrange[0:30]\r\n");
                fwrite($fh, "f1(x)=((x>=3.75) && (x<=30)) ? 0.25 : 1/0\r\n");
                $plot_string = "plot f1(x) title 'Spec' with lines lw 3";
                break;
            case 5:
                fwrite($fh, "set xrange[0:15]\r\n");
                fwrite($fh, "f1(x)=((x>=1) && (x<3)) ? 0.5 : 1/0\r\n");
                fwrite($fh, "f2(x)=((x>=3) && (x<=15)) ? 0.3 : 1/0\r\n");
                $plot_string = "plot f1(x) title 'Spec' with lines lw 3";
                $plot_string .= ", f2(x) title 'Spec' with lines lw 3 lt 1";
                break;
            case 6:
                fwrite($fh, "set xrange[0:20]\r\n");
                fwrite($fh, "f1(x)=((x>=5) && (x<=20)) ? 0.5 : 1/0\r\n");
                $plot_string = "plot f1(x) title 'Spec' with lines lw 3";
                break;
            case 7:
                fwrite($fh, "set xrange[0:40]\r\n");
                fwrite($fh, "f1(x)=((x>=1) && (x<=8)) ? 0.5 : 1/0\r\n");
                $plot_string = "plot f1(x) title 'Spec' with lines lw 3";
                break;
            case 8:
                fwrite($fh, "set xrange[0:80]\r\n");
                fwrite($fh, "f1(x)=((x>=20) && (x<=80)) ? 0.3 : 1/0\r\n");
                $plot_string = "plot f1(x) title 'Spec' with lines lw 3";
                break;
            case 9:
                fwrite($fh, "set xrange[0:100]\r\n");
                fwrite($fh, "f1(x)=((x>=25) && (x<=100)) ? 0.3 : 1/0\r\n");
                $plot_string = "plot f1(x) title 'Spec' with lines lw 3";
                break;
            case 10:
                fwrite($fh, "set xrange[0:140]\r\n");
                fwrite($fh, "f1(x)=((x>=20) && (x<=80)) ? 0.5 : 1/0\r\n");
                $plot_string = "plot f1(x) title 'Spec' with lines lw 4";
                break;
            default:
                fwrite($fh, "f1(x)=((x>=25) && (x<=25.01)) ? 0 : 1/0\r\n");
                $plot_string = "plot f1(x) notitle with lines lw 3";

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
            $qCheck = "SELECT * FROM $tableName
            WHERE fkFE_Component = $this->keyId
            AND fkFacility = $this->fc LIMIT 3;";
            $rCheck = @mysql_query($qCheck,$this->dbconnection);
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