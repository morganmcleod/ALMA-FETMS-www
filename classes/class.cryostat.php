<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.tempsensor.php');
require_once($site_dbConnect);

class Cryostat extends GenericTable{
    var $tempsensors;
    var $datadir;
    var $urldir;
    var $dbconnection;
    var $fc;
    var $tdheaders;     //Array of TestData_header objects (TestData_header)
                        //[1] = First Rate of Rise
                        //[2] = Warmup
                        //[3] = Cooldown
                        //[4] = Final Rate of Rise
                        //[5] = Rate of Rise after adding CCA

    var $generatedby;   //1= Cryostat Logging app
                        //2= FE Control Software

    var $rk;            //Format of raw data
                        //r = resistance
                        //k = Kelvin

    var $FESN;          //SN of the Front End
    var $FEid;          //keyId of the Front End
    var $FEConfig;      //Latest configuration of the Front End
    var $swversioncryo;

    function __construct() {
        require(site_get_config_main());
        $this->swversioncryo = '1.0.4';

        /*
         * version 1.0.4:  MTM fixed "set...screen" commands to gnuplot
        */

        $this->fc = $fc;
        $this->datadir = $main_write_directory;
        $this->urldir = $main_url_directory;
    }
    function __destruct() {
        for ($i=1;$i<=13;$i++){
            unset($this->tempsensors[$i]);
        }
    }

    public function Initialize_Cryostat($in_keyId, $in_fc){

        require(site_get_config_main());

        //$this->dbconnection = $in_dbconnection;
        $this->tempsensors = array();
        parent::Initialize('FE_Components',$in_keyId,"keyId", $in_fc, 'keyFacility');

        $this->datadir = $main_write_directory . "cSN". $this->Getvalue('SN') ."/";
        $this->urldir = $main_url_directory . "cSN". $this->Getvalue('SN') ."/";


        //Find which Front End this component is in (if any)
        $q = "select Front_Ends.SN, FE_Config.keyFEConfig,Front_Ends.keyFrontEnds
            FROM Front_Ends, FE_ConfigLink, FE_Config
            WHERE FE_ConfigLink.fkFE_Components = $this->keyId
            AND FE_ConfigLink.fkFE_Config = FE_Config.keyFEConfig
            AND FE_Config.fkFront_Ends = Front_Ends.keyFrontEnds
            GROUP BY FE_Config.keyFEConfig DESC LIMIT 1;";
        $r = @mysql_query($q,$this->dbconnection);
        $this->FESN = @mysql_result($r,0,0);
        $this->FEConfig = @mysql_result($r,0,1);
        $this->FEid = @mysql_result($r,0,2);

        //Fill the array of tempsensors
        for ($i=1;$i<=13;$i++){
            $this->tempsensors[$i] = new Cryostat_tempsensor;
            $this->tempsensors[$i]->Initialize_tempsensor($this->keyId,$i,$in_fc);
        }

        //Get TestData_header and SubHeader objects
        $qtdh = "SELECT * FROM TestData_header
                 WHERE fkFE_Components = $this->keyId;";
        $rtdh = @mysql_query($qtdh,$this->dbconnection);
        while ($rowtdh = @mysql_fetch_array($rtdh)){
            switch ($rowtdh['fkTestData_Type']){
                case 50:
                    //First Rate of Rise
                    $tdhIndex = 1;
                    break;
                case 53:
                    //Warmup
                    $tdhIndex = 2;
                    break;
                case 52:
                    //Cooldown
                    $tdhIndex = 3;
                    break;
                case 54:
                    //Final Rate of Rise
                    $tdhIndex = 4;
                    break;
                case 25:
                    //Rate of Rise after adding CCA
                    $tdhIndex = 5;
                    break;
            }
            if (isset($tdhIndex)) {
                $this->tdheaders[$tdhIndex] = new GenericTable();
                $this->tdheaders[$tdhIndex]->Initialize('TestData_header', $rowtdh['keyId'], "keyId", $in_fc,'keyFacility');
                $this->tdheaders[$tdhIndex]->subheader = new GenericTable();
                //Initialize first using fkHeader as key. This gets us the keyId value without having
                //to do an extra query.
                $this->tdheaders[$tdhIndex]->subheader->Initialize('TEST_Cryostat_data_SubHeader', $rowtdh['keyId'], "fkHeader", $in_fc,'keyFacility');
                $keyid_subheader = $this->tdheaders[$tdhIndex]->subheader->GetValue('keyId');
                //Use key value to initialize the subheader object again.
                $this->tdheaders[$tdhIndex]->subheader->Initialize('TEST_Cryostat_data_SubHeader', $keyid_subheader, "keyId", $in_fc,'keyFacility');
            }
        }
    }

    public function Initialize_CryostatFromFEConfig($in_fecfg, $in_fc = 40){
        $this->dbconnection = site_getDbConnection();
        $q = "SELECT FE_Components.keyId FROM
        FE_Components, FE_Config, FE_ConfigLink
        where
        FE_Config.keyFEConfig = $in_fecfg
        AND FE_ConfigLink.fkFE_Config = FE_Config.keyFEConfig
        AND FE_Components.keyId = FE_ConfigLink.fkFE_Components
        AND FE_Components.fkFE_ComponentType = 6;";
        $r = @mysql_query($q,$this->dbconnection);
        $id = @mysql_result($r,0,0);

        $this->Initialize_Cryostat($id, $in_fc);
    }

    public function NewRecord_Cryostat(){
        require(site_get_config_main());
        //fc is defined in config_main.php
        parent::NewRecord('FE_Components','keyId',$fc,'keyFacility');
        $this->SetValue('fkFE_ComponentType',6);
        $this->Update();

        $tdtypes = array(0,50,53,52,54,25);
    }

    public function Update_Cryostat(){
        parent::Update();
        //echo '<meta http-equiv="Refresh" content="1;url=cryostat.php?keyId='.$this->keyId.'">';
    }

    public function DisplayData_Cryostat($in_DisplayType = "all"){
        require(site_get_config_main());

        //if ($in_DisplayType == "all"){
        echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
        //}
        if ($_SERVER['SERVER_NAME'] == "webtest.cv.nrao.edu"){
            //echo "<input type='submit' name = 'deleterecord' value='DELETE RECORD'><br>";
        }

        if ($this->GetValue('SN') != ""){
            switch ($in_DisplayType){
            //$data_type
            //[1] = First Rate of Rise
            //[2] = Warmup
            //[3] = Cooldown
            //[4] = Final Rate of Rise
            //[5] = Rate of Rise after adding CCA
                case 'all':
                    echo "<br><font size='+2'><b><u>Cryostat Information</u></b></font><br>";
                    echo "<br>SN:<input type='text' name='SN' size='10' maxlength='20' value = '".$this->GetValue('SN')."'><br>";
                    echo "<br>".$this->GetValue('TS')."<br>";
                    $this->DisplayTempSenors();
                    echo "<br><br><br><br><br><br><br><br>";
                    $this->DisplayData_FirstCooldown();
                    $this->DisplayData_FirstWarmup();
                    $this->Display_FinalCooldownTemps();
                    echo "<br><br><br>";
                    $this->DisplayData_ROR_First();
                    $this->DisplayData_ROR_AfterCCA();
                    $this->DisplayData_ROR_Final();
                    break;
                case 50:
                    //First Rate of Rise
                    $this->DisplayData_ROR_First();
                    echo "<input type='hidden' name='keyheader' value='".$this->tdheaders[1]->keyId."'>";
                    break;
                case 53:
                    //Warmup
                    $this->DisplayData_FirstWarmup();
                    $this->Display_FinalCooldownTemps();
                    echo "<input type='hidden' name='keyheader' value='".$this->tdheaders[2]->keyId."'>";
                    break;
                case 52:
                    //Cooldown
                    $this->DisplayData_FirstCooldown();
                    echo "<input type='hidden' name='keyheader' value='".$this->tdheaders[3]->keyId."'>";
                    break;
                case 54:
                    //Final Rate of Rise
                    $this->DisplayData_ROR_Final();
                    echo "<input type='hidden' name='keyheader' value='".$this->tdheaders[4]->keyId."'>";
                    break;
                case 25:
                    //Rate of Rise after adding CCA
                    $this->DisplayData_ROR_AfterCCA();
                    echo "<input type='hidden' name='keyheader' value='".$this->tdheaders[5]->keyId."'>";
                    break;

            }

        }//end if SN != ""

        echo "<div style ='width:100%;height:30%'>";
        echo "<div align='left' style ='width:50%;height:30%'>";
        echo "<input type='hidden' name='keyId' value='$this->keyId'>";

        if ($this->GetValue('keyFacility') != ''){
            $fc = $this->GetValue('keyFacility');
        }
        echo "<input type='hidden' name='fc' value='$fc'>";


        if ($in_DisplayType == 'all'){
            echo "<br><br><input type='submit' name = 'submitted' value='SAVE CHANGES'>";
            echo "<input type='submit' name = 'deleterecord' value='DELETE RECORD'>";
        }
        //echo "<br><input type='submit' name = 'export_to_word' value='EXPORT TO WORD'>";
        echo "</div></div>";
        //if ($in_DisplayType == "all"){
        echo "</form>";
        //}

        if ($this->GetValue('SN') != ""){
            if ($in_DisplayType == 'all'){
                $this->Display_uploadform();
            }
        }
    }

    public function DisplayData_FirstCooldown(){
        if ($this->tdheaders[3]->keyId != ''){
            echo "
            <div style='width:700px'>
            <table id = 'table1' bg color = '#ff0000'>
            <tr class = 'alt'><th>FIRST COOLDOWN</th></tr>
            <tr><th><img src='".$this->PicURL_Prefix($this->tdheaders[3]->subheader->GetValue('pic_pressure'))."'></th></tr>

            <tr><th><img src='".$this->PicURL_Prefix($this->tdheaders[3]->subheader->GetValue('pic_temperature'))."'></th></tr>

            </table></div>";
        }
    }

    public function DisplayData_FirstWarmup(){
        if ($this->tdheaders[2]->keyId != ''){
            echo "
            <div style='width:700px'>
            <table id = 'table1' bg color = '#ff0000'>
            <tr class = 'alt'><th>FIRST WARMUP</th></tr>
            <tr><th><img src='".$this->PicURL_Prefix($this->tdheaders[2]->subheader->GetValue('pic_pressure'))."'></th></tr>

            <tr><th><img src='".$this->PicURL_Prefix($this->tdheaders[2]->subheader->GetValue('pic_temperature'))."'></th></tr>

            </table></div>";
        }
    }

    public function DisplayData_ROR_First(){
        if ($this->tdheaders[1]->keyId != ''){
            echo "
            <div style='width:400px'>
            <table id = 'table1' bg color = '#ff0000'>
            <tr class = 'alt'><th colspan = '2'>FIRST RATE OF RISE</th></tr>
            <tr><th><img src='".$this->PicURL_Prefix($this->tdheaders[1]->subheader->GetValue('pic_rateofrise'))."'></th>
            <th>";
            $this->Display_RORselector('ror_start', 'Start Time', 'ror_starttime',$o1,1);
            echo "<br><br>";
            $this->Display_RORselector('ror_stop', 'Stop Time', 'ror_stoptime',$o1,1);

            echo "<br><br><input type='hidden' name = 'keyId' value='$this->keyId'>";

            echo "<br><br>
            <input type='submit' name = 'submitted_ror' class = 'button blue2 biground' value='REDRAW RATE OF RISE'>";
            $exportcsvurl = "export_to_csv.php?keyheader=".$this->tdheaders[1]->keyId."&fc=".$this->GetValue('keyFacility');
            echo "<br><br><a style='width:130px' href='$exportcsvurl' class='button blue2 biground'>
                        <span style='width:130px'>Export CSV</span></a></table>";
            echo "
            </th></tr>
            </table></div>";
        }
    }

    public function DisplayData_ROR_AfterCCA(){

        if ($this->tdheaders[5]->keyId != ''){
            echo "
            <div style='width:400px'>
            <table id = 'table1' bg color = '#ff0000'>
            <tr class = 'alt'><th colspan = '2'>RATE OF RISE AFTER ADDING VACUUM EQUIPMENT</th></tr>
            <tr><th><img src='".$this->PicURL_Prefix($this->tdheaders[5]->subheader->GetValue('pic_rateofrise'))."'></th>
            <th>";
            $this->Display_RORselector('ror_start_aftercca', 'Start Time', 'ror_starttime',$o2,5);
            echo "<br><br>";
            $this->Display_RORselector('ror_stop_aftercca', 'Stop Time', 'ror_stoptime',$o2,5);

            echo "<br><br><input type='submit' name = 'submitted_ror_aftercca' class = 'button blue2 biground' value='REDRAW RATE OF RISE'>";
            $exportcsvurl = "export_to_csv.php?keyheader=".$this->tdheaders[5]->keyId."&fc=".$this->GetValue('keyFacility');
            echo "<br><br><a style='width:130px' href='$exportcsvurl' class='button blue2 biground'>
                        <span style='width:130px'>Export CSV</span></a>";
            echo "
            </th></tr>
            </table></div>";
        }
    }
    public function DisplayData_ROR_Final(){


        if ($this->tdheaders[4]->keyId != ''){
            echo "
            <div style='width:400px'>
            <table id = 'table1' bg color = '#ff0000'>
            <tr class = 'alt'><th colspan = '2'>FINAL RATE OF RISE</th></tr>
            <tr><th><img src='".$this->PicURL_Prefix($this->tdheaders[4]->subheader->GetValue('pic_rateofrise'))."'></th>
            <th>";
            $this->Display_RORselector('ror_start_final', 'Start Time', 'ror_starttime',$o3,4);
            echo "<br><br>";
            $this->Display_RORselector('ror_stop_final', 'Stop Time', 'ror_stoptime',$o3,4);

            echo "<br><br><input type='submit' name = 'submitted_ror_final' class = 'button blue2 biground' value='REDRAW RATE OF RISE'>";
            $exportcsvurl = "export_to_csv.php?keyheader=".$this->tdheaders[4]->keyId."&fc=".$this->GetValue('keyFacility');
            echo "<br><br><a style='width:130px' href='$exportcsvurl' class='button blue2 biground'>
                        <span style='width:130px'>Export CSV</span></a>";
            echo "
            </th></tr>
            </table></div>";
        }
    }

    public function Display_uploadform_TempSensor(){
        echo '
        <!-- The data encoding type, enctype, MUST be specified as below -->
        <form enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '" method="POST">
        <!-- MAX_FILE_SIZE must precede the file input field -->
        <!-- <input type="hidden" name="MAX_FILE_SIZE" value="100000" /> -->
        <!-- Name of input element determines name in $_FILES array -->

        Temp Sensor Calibration (Excel): </b><input name="file_tempsensors" type="file" />
        <input type="submit" class="submit" name= "submit_datafile_cryostat" value="Upload Temp Sensor Excel File" />


        <input type="hidden" name="keyId" value="'.$this->keyId.'">
        <input type="hidden" name="fc" value="'.$this->GetValue('keyFacility').'">
        </form>';
    }

    public function Display_uploadform(){
        echo '
        <div style="width:750px">
        <!-- The data encoding type, enctype, MUST be specified as below -->
        <form enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '" method="POST">
            <!-- MAX_FILE_SIZE must precede the file input field -->
            <!-- <input type="hidden" name="MAX_FILE_SIZE" value="100000" /> -->
            <!-- Name of input element determines name in $_FILES array -->
            <br>
            <table id = "table1">

            <tr class = "alt"><th colspan = "2">Upload Data Files</th></tr>

            <tr><td align = "right">
            Temp Sensor Calibration (Excel): </b><input name="file_tempsensors" type="file" />
            </td><td></td>

                <tr>
                <td align = "right">First Rate of Rise File (txt): </b><input name="file_rateofrise" type="file" /></td>
                <td align = "right">Time step size (sec): </b><input name="time_rateofrise" type="text" size="3" maxlength="3" value = "30" /></td>
                </tr>

                <tr>
                <td align = "right">First Warmup File (txt): </b><input name="file_firstwarmup" type="file" /></td>
                <td align = "right">Time step size (sec): </b><input name="time_firstwarmup" type="text" size="3" maxlength="3" value = "30" /></td>
                </tr>

                <tr>
                <td align = "right">First Cooldown File (txt): </b><input name="file_firstcooldown" type="file" /></td>
                <td align = "right">Time step size (sec): </b><input name="time_firstcooldown" type="text" size="3" maxlength="3" value = "30" /></td>
                </tr>

                <tr>
                <td align = "right">Rate of Rise File after adding cold cartridges(txt): </b><input name="file_rateofrise_aftercca" type="file" /></td>
                <td align = "right">Time step size (sec): </b><input name="time_rateofrise_aftercca" type="text" size="3" maxlength="3" value = "30" /></td>
                </tr>

                <tr>
                <td align = "right">Final Rate of Rise File (txt): </b><input name="file_rateofrise_final" type="file" /></td>
                <td align = "right">Time step size (sec): </b><input name="time_rateofrise_final" type="text" size="3" maxlength="3" value = "30" /></td>
                </tr>
            <tr >

            <th>
                <select name ="generatedby">
                    <option value="1" selected = "selected">Cryostat Logging Application</option>
                    <option value="2">FE Control Software</option>
                </select>

                <select name ="rk">
                    <option value="r" selected = "selected">Resistance Values</option>
                    <option value="k">Temperature (K)</option>
                </select>


                </th>
                <td align = "right"><input type="submit" name= "submit_datafile_cryostat" value="Submit" /></td>

                </tr>

        </table>
        <input type="hidden" name="keyId" value="'.$this->keyId.'">
        <input type="hidden" name="fc" value="'.$this->GetValue('keyFacility').'">
        </form>

        </div>';
    }

    public function Display_uploadform_Notempsensors(){
        echo '
        <div style="width:750px">
        <!-- The data encoding type, enctype, MUST be specified as below -->
        <form enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '" method="POST">
            <!-- MAX_FILE_SIZE must precede the file input field -->
            <!-- <input type="hidden" name="MAX_FILE_SIZE" value="100000" /> -->
            <!-- Name of input element determines name in $_FILES array -->
            <br>
            <table id = "table1">

            <tr class = "alt"><th colspan = "2">Upload Data Files</th></tr>

            <tr>

                <tr>
                <td align = "right">First Rate of Rise File (txt): </b><input name="file_rateofrise" type="file" /></td>
                <td align = "right">Time step size (sec): </b><input name="time_rateofrise" type="text" size="3" maxlength="3" value = "30" /></td>
                </tr>

                <tr>
                <td align = "right">First Warmup File (txt): </b><input name="file_firstwarmup" type="file" /></td>
                <td align = "right">Time step size (sec): </b><input name="time_firstwarmup" type="text" size="3" maxlength="3" value = "30" /></td>
                </tr>

                <tr>
                <td align = "right">First Cooldown File (txt): </b><input name="file_firstcooldown" type="file" /></td>
                <td align = "right">Time step size (sec): </b><input name="time_firstcooldown" type="text" size="3" maxlength="3" value = "30" /></td>
                </tr>

                <tr>
                <td align = "right">Rate of Rise File after adding cold cartridges(txt): </b><input name="file_rateofrise_aftercca" type="file" /></td>
                <td align = "right">Time step size (sec): </b><input name="time_rateofrise_aftercca" type="text" size="3" maxlength="3" value = "30" /></td>
                </tr>

                <tr>
                <td align = "right">Final Rate of Rise File (txt): </b><input name="file_rateofrise_final" type="file" /></td>
                <td align = "right">Time step size (sec): </b><input name="time_rateofrise_final" type="text" size="3" maxlength="3" value = "30" /></td>
                </tr>
            <tr >

            <th>
                <select name ="generatedby">
                    <option value="1" selected = "selected">Cryostat Logging Application</option>
                    <option value="2">FE Control Software</option>
                </select>

                <select name ="rk">
                    <option value="r" selected = "selected">Resistance Values</option>
                    <option value="k">Temperature (K)</option>
                </select>


                </th>
                <td align = "right"><input type="submit" name= "submit_datafile_cryostat" value="Submit" /></td>

                </tr>

        </table>
        <input type="hidden" name="keyId" value="'.$this->keyId.'">
        <input type="hidden" name="fc" value="'.$this->GetValue('keyFacility').'">
        </form>

        </div>';
    }

    public function DisplayTempSenors(){
        echo "<div style='width:900px'>";
        echo '<table id = "table1" align="left" cellspacing="1" cellpadding="1">';
        echo '<tr class = "alt">
                <th colspan = "3">CRYOSTAT '. $this->GetValue('SN') .' TEMPERATURE SENSORS</th>
                <th colspan = "7">POLYNOMIAL COEFFICIENTS<br>T=K1+K2*(1000/R)+ K3*[(1000/R)^2]+..+K7*[(1000/R)^6]</th>
            </tr>';
        echo '<tr>
                <th><b>Sensor</b></td>
                <th><b>Component & Ident.</b></th>
                <th><b>Location</b></th>
                <th><b>K1</b></th>
                <th><b>K2</b></th>
                <th><b>K3</b></th>
                <th><b>K4</b></th>
                <th><b>K5</b></th>
                <th><b>K6</b></th>
                <th><b>K7</b></th>
            </tr>';

        $trclass = "";
        for ($i=1;$i<count($this->tempsensors);$i++){
            $trclass = ($trclass=="" ? "tr class = 'alt'" : "");


            echo "<tr $trclass>
                    <td>".$this->tempsensors[$i]->GetValue('sensor_number')."</td>";

                    echo "<td><b>".
                    $this->tempsensors[$i]->GetValue('sensor_type')."</b></td>";

                    echo "
                    <td>".$this->tempsensors[$i]->GetValue('location')."</td>
                    <td width = '40px'>".round($this->tempsensors[$i]->GetValue('k1'),3)."</td>
                    <td width = '40px'>".round($this->tempsensors[$i]->GetValue('k2'),3)."</td>
                    <td width = '40px'>".round($this->tempsensors[$i]->GetValue('k3'),3)."</td>
                    <td width = '40px'>".round($this->tempsensors[$i]->GetValue('k4'),3)."</td>
                    <td width = '40px'>".round($this->tempsensors[$i]->GetValue('k5'),3)."</td>
                    <td width = '40px'>".round($this->tempsensors[$i]->GetValue('k6'),3)."</td>
                    <td width = '40px'>".round($this->tempsensors[$i]->GetValue('k7'),3)."</td>";
                    echo "</tr>";
        }

        $url= 'export_to_ini_cryostat.php?keyId='.$this->keyId.'&datatype=tempsensors&fc='. $this->GetValue('keyFacility');
        ?>

        <tr class= 'alt2'><th colspan=10 align = 'right'>
        <?php
            $this->Display_uploadform_TempSensor();
        ?>
        </td></tr>

        <tr class= 'alt2'><th colspan=10 align = 'right'>
        <form>
        <INPUT TYPE="BUTTON" class = "submit" VALUE="Export to INI file" ONCLICK="window.location.href='<?php echo $url;?>'">
        </form></td></tr>
        <?php
        echo "</table></div><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>";
    }

    public function RequestValues_Cryostat(){
        $checkbox = "off";
        if (isset($_REQUEST['checkbox_rateofrise'])){
            $checkbox = "on";
        }

        if (isset($_REQUEST['generatedby'])){
            $this->generatedby = $_REQUEST['generatedby'];
        }
        if (isset($_REQUEST['rk'])){
            $this->rk = $_REQUEST['rk'];
        }


        //$this->SetValue('checkbox_rateofrise',$checkbox);


        parent::RequestValues();

        if (isset($_REQUEST['deleterecord_forsure'])){
                $this->DeleteRecord_cryostat();
        }

        if (isset($_REQUEST['exporttempsensors'])){
                $this->ExportINI("tempsensors");
        }

        $this->rk = 'r';
        if (isset($_REQUEST['rk'])){
                $this->rk = $_REQUEST['rk'];
        }


        if (isset($_REQUEST['submitted_ror'])){
            //$data_type
            //[1] = First Rate of Rise
            //[2] = Warmup
            //[3] = Cooldown
            //[4] = Final Rate of Rise
            //[5] = Rate of Rise after adding CCA

            $this->showspinner();
            $this->tdheaders[1]->subheader->SetValue('ror_starttime',$_REQUEST['ror_start']);
            $this->tdheaders[1]->subheader->SetValue('ror_stoptime',$_REQUEST['ror_stop']);
            $this->tdheaders[1]->subheader->Update();
            //$this->SetValue('checkbox_rateofrise',$_REQUEST['checkbox_rateofrise']);
            echo '<meta http-equiv="Refresh" content="1;url=testdata_cryostat.php?keyheader='.$this->tdheaders[1]->keyId.'&fc='.$this->GetValue('keyFacility').'">';

            parent::Update();
            $this->Plot_RateOfRise(1);
            $this->Update_Cryostat();
        }
        if (isset($_REQUEST['submitted_ror_final'])){
            $this->showspinner();
            $this->tdheaders[4]->subheader->SetValue('ror_starttime',$_REQUEST['ror_start_final']);
            $this->tdheaders[4]->subheader->SetValue('ror_stoptime',$_REQUEST['ror_stop_final']);
            $this->tdheaders[4]->subheader->Update();
            parent::Update();
            $this->Plot_RateOfRise(4);
            $this->Update_Cryostat();
            echo '<meta http-equiv="Refresh" content="1;url=testdata_cryostat.php?keyheader='.$this->tdheaders[4]->keyId.'&fc='.$this->GetValue('keyFacility').'">';

        }
        if (isset($_REQUEST['submitted_ror_aftercca'])){
            $this->showspinner();
            $this->tdheaders[5]->subheader->SetValue('ror_starttime',$_REQUEST['ror_start_aftercca']);
            $this->tdheaders[5]->subheader->SetValue('ror_stoptime',$_REQUEST['ror_stop_aftercca']);
            $this->tdheaders[5]->subheader->Update();
            parent::Update();
            $this->Plot_RateOfRise(5);
            $this->Update_Cryostat();
            echo '<meta http-equiv="Refresh" content="1;url=testdata_cryostat.php?keyheader='.$this->tdheaders[5]->keyId.'&fc='.$this->GetValue('keyFacility').'">';

        }

        if (isset($_REQUEST['export_to_word'])){
            $this->DownloadToWord();
        }


        if (isset($_REQUEST['submit_datafile_cryostat'])){
            if (isset($_FILES['file_tempsensors']['name'])){
                if ($_FILES['file_tempsensors']['name'] != ""){
                    //echo "name: " . $_FILES['file_tempsensors']['tmp_name'] . "<br>";

                    $this->Upload_tempsensorfile($_FILES['file_tempsensors']['tmp_name']);
                }
            }
            if (isset($_FILES['file_rateofrise']['name'])){
                if ($_FILES['file_rateofrise']['name'] != ""){
                    $this->Upload_datafile("1",$_FILES['file_rateofrise']['tmp_name'],$_REQUEST['time_rateofrise']);
                    $this->tdheaders[1]->subheader->SetValue('checkbox_rateofrise',$_REQUEST['checkbox_rateofrise']);
                    $this->tdheaders[1]->subheader->Update();
                    $this->Plot_RateOfRise("1");
                }
            }
            if (isset($_FILES['file_rateofrise_aftercca']['name'])){
                if ($_FILES['file_rateofrise_aftercca']['name'] != ""){
                    $this->Upload_datafile("5",$_FILES['file_rateofrise_aftercca']['tmp_name'],$_REQUEST['time_rateofrise_aftercca']);
                    $this->tdheaders[5]->subheader->SetValue('checkbox_rateofrise',$_REQUEST['checkbox_rateofrise']);
                    $this->tdheaders[5]->subheader->Update();
                    $this->Plot_RateOfRise("5");
                }
            }
            if (isset($_FILES['file_rateofrise_final']['name'])){
                if ($_FILES['file_rateofrise_final']['name'] != ""){
                    $this->Upload_datafile("4",$_FILES['file_rateofrise_final']['tmp_name'],$_REQUEST['time_rateofrise_final']);
                    $this->tdheaders[4]->subheader->SetValue('checkbox_rateofrise',$_REQUEST['checkbox_rateofrise']);
                    $this->tdheaders[4]->subheader->Update();
                    $this->Plot_RateOfRise("4");
                }
            }
            if (isset($_FILES['file_firstwarmup']['name'])){
                if ($_FILES['file_firstwarmup']['name'] != ""){
                    $this->Upload_datafile("2",$_FILES['file_firstwarmup']['tmp_name'],$_REQUEST['time_firstwarmup']);
                    $this->Plot_pressure("2");
                    $this->Plot_TemperatureCurves("2");
                }
            }
            if (isset($_FILES['file_firstcooldown']['name'])){
                if ($_FILES['file_firstcooldown']['name'] != ""){
                    $this->Upload_datafile("3",$_FILES['file_firstcooldown']['tmp_name'],$_REQUEST['time_firstcooldown']);
                    $this->Plot_pressure("3");
                    $this->Plot_TemperatureCurves("3");
                }
            }

            $this->Update_Cryostat();
        }

        if (isset($_REQUEST['exportcsv_firstwarmup'])){
            $this->ExportCSV("firstwarmup_pressure");
        }
        if (isset($_REQUEST['exportcsv_firstwarmup_temps'])){
            $this->ExportCSV("firstwarmup_temps");
        }
        if (isset($_REQUEST['exportcsv_firstcooldown'])){
            $this->ExportCSV("firstcooldown_pressure");
        }
        if (isset($_REQUEST['exportcsv_firstcooldown_temps'])){
            $this->ExportCSV("firstcooldown_temps");
        }
        if (isset($_REQUEST['exportcsv_rateofrise'])){
            $this->ExportCSV("rateofrise");
        }
    }

    public function Delete_TDH($tdh_number){
    }

    public function Delete_TempSensors(){
        //delete from Cryostat_tempsensors
        $qd1 = "DELETE FROM Cryostat_tempsensors
                WHERE fkCryostat = $this->keyId;";
        $rd1 = @mysql_query($qd1,$this->dbconnection);
    }

    public function DeleteRecord_cryostat(){

        $qd1 = "DELETE FROM FE_Components
                WHERE keyId = $this->keyId;";
        $rd1 = @mysql_query($qd1,$this->dbconnection);

        //delete from Cryostat_tempsensors
        $qd1 = "DELETE FROM Cryostat_tempsensors
                WHERE fkCryostat = $this->keyId;";
        $rd1 = @mysql_query($qd1,$this->dbconnection);

        //Delete TestData_header and Subheader records
        for ($i = 1; $i <= 5; $i++){
            if ($this->tdheaders[$i]->keyId != ''){
            $this->tdheaders[$i]->subheader->Delete_record();
            $this->tdheaders[$i]->Delete_record();


            $qd = "DELETE FROM TEST_Cryostat_data
                WHERE fkSubHeader = ".$this->tdheaders[$i]->subheader->keyId . ";";
            $rd = @mysql_query($qd,$this->dbconnection);
            }
        }

        //parent::Delete_record();
        echo '<meta http-equiv="Refresh" content="1;url=cryostats.php">';
    }

    public function Upload_datafile($data_type, $datafile_name,$timestepsize){
        //$data_type
        //[1] = First Rate of Rise
        //[2] = Warmup
        //[3] = Cooldown
        //[4] = Final Rate of Rise
        //[5] = Rate of Rise after adding CCA

        //echo "datatype= $data_type<br>";

        //Clear existing test of this type
        if ($this->tdheaders[$data_type]->keyId != ''){
            $this->tdheaders[$data_type]->subheader->Delete_record();
            $this->tdheaders[$data_type]->Delete_record();


            $qd = "DELETE FROM TEST_Cryostat_data
                WHERE fkSubHeader = ".$this->tdheaders[$data_type]->subheader->keyId . ";";
            $rd = @mysql_query($qd,$this->dbconnection);
        }
        $tdtypes = array(0,50,53,52,54,25);


        $this->tdheaders[$data_type] = new GenericTable();
        $this->tdheaders[$data_type]->NewRecord('TestData_header','keyId',$this->GetValue('keyFacility'),'keyFacility');
        $this->tdheaders[$data_type]->SetValue('fkTestData_Type',$tdtypes[$data_type]);
        $this->tdheaders[$data_type]->SetValue('fkDataStatus',1);
        $this->tdheaders[$data_type]->SetValue('fkFE_Components',$this->keyId);
        $this->tdheaders[$data_type]->Update();

        $this->tdheaders[$data_type]->subheader = new GenericTable();
        $this->tdheaders[$data_type]->subheader->NewRecord('TEST_Cryostat_data_SubHeader','keyId',$this->GetValue('keyFacility'),'keyFacility');
        $this->tdheaders[$data_type]->subheader->SetValue('fkHeader',$this->tdheaders[$data_type]->keyId);
        $this->tdheaders[$data_type]->subheader->Update();


        $keySubheader = $this->tdheaders[$data_type]->subheader->keyId;
        $filecontents = file($datafile_name);

        $i_step=1;




        $qDelete = "DELETE FROM TEST_Cryostat_data WHERE fkSubHeader =
                    " . $this->tdheaders[$data_type]->subheader->keyId . ";";
        $rDelete = @mysql_query ($qDelete, $this->dbconnection);

        $timestep=0;

        $dcount=0;
        for($i=5; $i<sizeof($filecontents); $i+=$i_step) {
              $line_data = trim($filecontents[$i]);
              $tempArray   = explode("\t", $line_data);


              if (is_numeric(substr($tempArray[0],0,1)) == true){
                  //Convert R values to Temp Kelvin

                  if ($this->generatedby == 2){
                      $R[1]  = $tempArray[19] ; //90k plate near link (PRT)
                       $R[2]  = $tempArray[20] ; //90k plate far side (PRT)
                       $R[3]  = $tempArray[15] ; //12k plate near link
                       $R[4]  = $tempArray[16] ; //12k plate far side
                       $R[5]  = $tempArray[9] ; //4k cryocooler stage
                       $R[6]  = $tempArray[14] ; //12k cryocooler stage
                       $R[7]  = $tempArray[10]; //4k plate near link a
                       $R[8]  = $tempArray[11]; //4k plate near link b
                       $R[9]  = $tempArray[12]; //4k plate far side a
                       $R[10] = $tempArray[13]; //4k plate far side b
                       $R[11] = $tempArray[18] ; //90k cryocooler stage (PRT)
                       $R[12] = $tempArray[17] ; //12k shield top
                       $R[13] = $tempArray[21] ; //90k shield top (PRT)
                       $Pressure1 = $tempArray[22];
                       if ($dcount < 4){
                       //echo "checked, p1= $Pressure1<br><br>";
                       }

                      for($j=1; $j<=13; $j++){
                           $T[$j] = $R[$j];
                       }

                  }

                  if ($this->generatedby == 1){
                      //Divide the PRT values by 1000
                      $tempArray[1] = $tempArray[1]/1000;
                      $tempArray[2] = $tempArray[2]/1000;
                      $tempArray[3] = $tempArray[3]/1000;
                      $tempArray[4] = $tempArray[4]/1000;

                       $R[1]  = $tempArray[2] ; //90k plate near link (PRT)
                       $R[2]  = $tempArray[3] ; //90k plate far side (PRT)
                       $R[3]  = $tempArray[6] ; //12k plate near link
                       $R[4]  = $tempArray[7] ; //12k plate far side
                       $R[5]  = $tempArray[9] ; //4k cryocooler stage
                       $R[6]  = $tempArray[5] ; //12k cryocooler stage
                       $R[7]  = $tempArray[10]; //4k plate near link a
                       $R[8]  = $tempArray[11]; //4k plate near link b
                       $R[9]  = $tempArray[12]; //4k plate far side a
                       $R[10] = $tempArray[13]; //4k plate far side b
                       $R[11] = $tempArray[1] ; //90k cryocooler stage (PRT)
                       $R[12] = $tempArray[8] ; //12k shield top
                       $R[13] = $tempArray[4] ; //90k shield top (PRT)
                       $Pressure1 = $tempArray[14];
                       if ($dcount < 4){
                       //echo "unchecked, p1= $Pressure1<br><br>";
                       }

                       for($j=1; $j<=13; $j++){
                           $K[1] = $this->tempsensors[$j]->GetValue('k1');
                           $K[2] = $this->tempsensors[$j]->GetValue('k2');
                           $K[3] = $this->tempsensors[$j]->GetValue('k3');
                           $K[4] = $this->tempsensors[$j]->GetValue('k4');
                           $K[5] = $this->tempsensors[$j]->GetValue('k5');
                           $K[6] = $this->tempsensors[$j]->GetValue('k6');
                           $K[7] = $this->tempsensors[$j]->GetValue('k7');
                           if ($this->rk == 'r'){
                               $T[$j] = $this->Convert_RtoTemp($R[$j], $K, $j);
                           }
                           if ($this->rk == 'k'){
                               $T[$j] = $R[$j];
                           }

                       }
                  }

                   $qInsert = "INSERT INTO TEST_Cryostat_data
                (fkSubHeader, date_time,
                sensor1_r,sensor2_r,sensor3_r,sensor4_r,sensor5_r,sensor6_r,sensor7_r,sensor8_r,
                sensor9_r,sensor10_r,sensor11_r,sensor12_r,sensor13_r,
                Pressure1,Pressure2,MixerA,MixerB,Cart4K,Cart90K,T5,T6,T7,Cart12K,
                Time_hours,
                sensor1_k,sensor2_k,sensor3_k,sensor4_k,sensor5_k,sensor6_k,sensor7_k,sensor8_k,
                sensor9_k,sensor10_k,sensor11_k,sensor12_k,sensor13_k)

                   VALUES ('$keySubheader','$tempArray[0]',
                   '$R[1]','$R[2]','$R[3]', '$R[4]','$R[5]','$R[6]', '$R[7]','$R[8]',
                   '$R[9]','$R[10]','$R[11]', '$R[12]','$R[13]',
                   '$Pressure1','$tempArray[15]','$tempArray[16]', '$tempArray[17]', '$tempArray[18]',
                   '$tempArray[19]','$tempArray[20]','$tempArray[21]', '$tempArray[22]', '$tempArray[23]',
                   '$timestep',
                   '$T[1]','$T[2]','$T[3]','$T[4]','$T[5]','$T[6]','$T[7]','$T[8]',
                   '$T[9]','$T[10]','$T[11]','$T[12]','$T[13]'
                   )";

                   if ($dcount < 4){

                //echo $qInsert . "<br><br><br>";
                   }
                   $dcount +=1;
                   $rInsert = @mysql_query ($qInsert, $this->dbconnection);
                   $timestep+=($timestepsize/60/60);
            }
        }
        //fclose($filecontents);
        unlink($datafile_name);
    }

    public function Upload_tempsensorfile($datafile_name){

        $this->Delete_TempSensors();
        for ($k=1;$k<=13;$k++){
            //Create Temp sensor records for this cryostat
            $tempsensor = new GenericTable();
            $tempsensor->NewRecord('Cryostat_tempsensors','keyId',$this->GetValue('keyFacility'),'fkFacility');
            $tempsensor->SetValue('fkCryostat',$this->keyId);
            $tempsensor->SetValue('sensor_number',$k);
            $tempsensor->Update();
            unset($tempsensor);
        }
        //Fill the array of tempsensors
        for ($i=1;$i<=13;$i++){
            $this->tempsensors[$i] = new Cryostat_tempsensor;
            $this->tempsensors[$i]->Initialize_tempsensor($this->keyId,$i,$this->GetValue('keyFacility'));
        }

        $sheetnumber= 0;
        $data = new Spreadsheet_Excel_Reader();
        $data->setOutputEncoding('CP1251');
        $data->read($datafile_name);

        $nonPRT_arr = array(3,4,5,6,7,8,9,10,12);
        $nonPRT_locations = array("12K Plate Near Link","12K Plate Far Side","4K Cryocooler Stage",
                                  "12K Cryocooler Stage","4K Plate Near Link a","4K Plate Near Link b",
                                  "4K Plate Far Side B","4K Plate Far Side A","12K Shield Top");

        $nonPRT_count = 0;

        //Get the K values for all non-PRT sensors
        for($sheetnumber=0;$sheetnumber<count($data->sheets);$sheetnumber++)

        if ($data->boundsheets[$sheetnumber]['name'] != "PRT"){
            //echo "Sheet name= " . $data->boundsheets[$sheetnumber]['name'] . "<br>";
            //echo "not prt<br>";
            $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->SetValue('sensor_type',$data->boundsheets[$sheetnumber]['name']);
                for ($i = 1; $i <= $data->sheets[$sheetnumber]['numRows']; $i++) {
                    //echo $data->sheets[$sheetnumber]['cells'][$i][1] . "<br>";
                    switch ($data->sheets[$sheetnumber]['cells'][$i][1]){

                        case "K1":

                            $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->SetValue('k1',$data->sheets[$sheetnumber]['cells'][$i][3]);


                          break;
                        case "K2":
                            $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->SetValue('k2',$data->sheets[$sheetnumber]['cells'][$i][3]);
                          break;
                        case "K3":
                            $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->SetValue('k3',$data->sheets[$sheetnumber]['cells'][$i][3]);
                          break;
                        case "K4":
                            $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->SetValue('k4',$data->sheets[$sheetnumber]['cells'][$i][3]);
                          break;
                        case "K5":
                            $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->SetValue('k5',$data->sheets[$sheetnumber]['cells'][$i][3]);
                          break;
                        case "K6":
                            $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->SetValue('k6',$data->sheets[$sheetnumber]['cells'][$i][3]);
                          break;
                        case "K7":
                            $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->SetValue('k7',$data->sheets[$sheetnumber]['cells'][$i][3]);
                          break;
                        }
                    }
                $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->SetValue('location',$nonPRT_locations[$nonPRT_count]);
                $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->Update();
                $nonPRT_count++;
            }
            unset($data);

            //Fill up the K values for the PRT sensors
            $PRT_arr = array(1,2,11,13);
            $PRT_locations = array("90K Plate Near Link","90K Plate Far Side","90K Cryocooler Stage","90K Shield Top");

            $PRT_count = 0;
            for($i=0;$i<4;$i++){
                $this->tempsensors[$PRT_arr[$i]]->SetValue('sensor_type','PRT');
                $this->tempsensors[$PRT_arr[$i]]->SetValue('location',$PRT_locations[$i]);
                $this->tempsensors[$PRT_arr[$i]]->SetValue('k1',28.486734);
                $this->tempsensors[$PRT_arr[$i]]->SetValue('k2',278.38662);
                $this->tempsensors[$PRT_arr[$i]]->SetValue('k3',-260.205006);
                $this->tempsensors[$PRT_arr[$i]]->SetValue('k4',687.754698);
                $this->tempsensors[$PRT_arr[$i]]->SetValue('k5',-891.65283);
                $this->tempsensors[$PRT_arr[$i]]->SetValue('k6',583.15814);
                $this->tempsensors[$PRT_arr[$i]]->SetValue('k7',-152.808821);
                $this->tempsensors[$PRT_arr[$i]]->Update();
            }
    }

    public function Plot_pressure($datatype){
        //Update Plot_SWVer in TestData_header
        $this->tdheaders[$datatype]->SetValue('Plot_SWVer',$this->swversioncryo);
        $this->tdheaders[$datatype]->Update();

        if (!file_exists($this->datadir)){
            mkdir($this->datadir);
        }
        if (!file_exists($this->urldir)){
            mkdir($this->urldir);
        }

        $suffix = $datatype;
        switch ($datatype){
            case 2:
                $suffix = "firstwarmup";
                break;
            case 3:
                $suffix = "firstcooldown";
                break;
        }
        $fkCryostat = $this->keyId;
        $data_file = $this->datadir . "cryo_dataPressure$datatype.txt";
        if (file_exists($data_file)){
            unlink($data_file);
        }

        $q = "SELECT date_time, Pressure1, Time_hours FROM TEST_Cryostat_data
                WHERE fkSubHeader = ". $this->tdheaders[$datatype]->subheader->keyId ."
                AND fkFacility = ".$this->tdheaders[$datatype]->GetValue('keyFacility')."
                ORDER BY Time_hours ASC;";
        $r = @mysql_query($q,$this->dbconnection);
        $fh = fopen($data_file, 'w');

        while($row = @mysql_fetch_array($r)){
            $stringData = "$row[2]\t$row[1]\r\n";
            fwrite($fh, $stringData);
        }
        fclose($fh);

        //Write command file for gnuplot
        $plot_command_file = $this->datadir ."cryo_command_pressure$datatype.txt";
        //unlink($plot_command_file);
        $imagedirectory = $this->datadir;
        $urldirectory = $this->urldir;


        if ($datatype==2){
            $imagename = "Cryostat_warmup_pressure_SN" . $this->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";
            $plot_title = "Cryostat " . $this->GetValue('SN') . " pressure during first warmup";
        }
        if ($datatype==3){
            $imagename = "Cryostat_cooldown_pressure_SN" . $this->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";
            $plot_title = "Cryostat " . $this->GetValue('SN') . " pressure during first cooldown";
        }
        $imageurl = $urldirectory . $imagename;
        $this->tdheaders[$datatype]->subheader->SetValue('pic_pressure',$imageurl);
        $this->tdheaders[$datatype]->subheader->Update();
        $imagepath = $imagedirectory . $imagename;
        //echo "image url = $imageurl<br>";
        //echo "image path= $imagepath<br>";

        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set log y\r\n");
        fwrite($fh, "set xlabel 'Time (hours)'\r\n");
        fwrite($fh, "set ylabel 'Presure (mbar)'\r\n");

        fwrite($fh, "set bmargin 7\r\n");
        fwrite($fh, "set label 'TestData_header.keyId: " .$this->tdheaders[$datatype]->keyId.", Cryostat Ver. $this->swversioncryo' at screen 0.01, 0.07\r\n");
        fwrite($fh, "set label '".$this->tdheaders[$datatype]->GetValue('TS').", FE Configuration ".$this->FEConfig."' at screen 0.01, 0.04\r\n");



        fwrite($fh, "plot '$data_file' using 1:2 title '' with linespoints pointsize 0.2 pt 5 lt 3 \r\n");
        fclose($fh);

        //Make the plot
        $GNUPLOT = '/usr/bin/gnuplot';
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);


    }

    public function Plot_RateOfRise($datatype){
        if (!file_exists($this->datadir)){
            mkdir($this->datadir);
        }
        if (!file_exists($this->urldir)){
            mkdir($this->urldir);
        }

        //Update Plot_SWVer in TestData_header
        $this->tdheaders[$datatype]->SetValue('Plot_SWVer',$this->swversioncryo);
        $this->tdheaders[$datatype]->Update();
        $starttime = $this->tdheaders[$datatype]->subheader->GetValue('ror_starttime');
        $endtime   = $this->tdheaders[$datatype]->subheader->GetValue('ror_stoptime');
        $RateOfRise = sprintf("%.2e", $this->GetRateOfRise($starttime,$endtime,$datatype));

        $data_file = $this->datadir . "cryo_dataROR$datatype.txt";

        //unlink($data_file);

        $q = "SELECT date_time, Pressure1, Time_hours FROM TEST_Cryostat_data
                WHERE fkSubHeader = ". $this->tdheaders[$datatype]->subheader->keyId ."
                AND Time_hours >= $starttime
                AND Time_hours <= $endtime
                ORDER BY Time_hours ASC;";
        $r = @mysql_query($q,$this->dbconnection);

        $fh = fopen($data_file, 'w');

        while($row = @mysql_fetch_array($r)){
            $stringData = "$row[2]\t$row[1]\r\n";
            fwrite($fh, $stringData);
        }
        fclose($fh);

        //Write command file for gnuplot
        $plot_command_file = $this->datadir . "cryo_commandROR$datatype.txt";
        //unlink($plot_command_file);
        $imagedirectory = $this->datadir;
        $urldirectory = $this->urldir;
        $imagename = "Cryostat_rateofrise_SN" . $this->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";
        $imageurl = $urldirectory . $imagename;
        $imagepath = $imagedirectory . $imagename;

        $this->tdheaders[$datatype]->subheader->SetValue('pic_rateofrise',$imageurl);
        $this->tdheaders[$datatype]->subheader->Update();

        if ($datatype == 1){
        $plot_title = "Cryostat " . $this->GetValue('SN') . " First Rate of Rise Test. Rate of Rise= " .
                       $RateOfRise . " mbar*l/sec.";
        }
        if ($datatype == 4){
        $plot_title = "Cryostat " . $this->GetValue('SN') . " Final Rate of Rise Test. Rate of Rise= " .
                       $RateOfRise . " mbar*l/sec.";
        }
        if ($datatype == 5){
        $plot_title = "Cryostat " . $this->GetValue('SN') . " Rate of Rise after adding Cold Cartidges. Rate of Rise= " .
                       $RateOfRise . " mbar*l/sec.";
        }


        //Get linear approximation
        $q_slope='
        SELECT
        @n := COUNT(Pressure1) AS N,
        @meanX := AVG(Time_hours) AS "X mean",
        @sumX := SUM(Time_hours) AS "X sum",
        @sumXX := SUM(Time_hours*Time_hours) AS "X sum of squares",
        @meanY := AVG(Pressure1) AS "Y mean",
        @sumY := SUM(Pressure1) AS "Y sum",
        @sumYY := SUM(Pressure1*Pressure1) AS "Y sum of square",
        @sumXY := SUM(Time_hours*Pressure1) AS "X*Y sum"
        FROM TEST_Cryostat_data
        WHERE Time_hours >= "' . $starttime . '"AND
        Time_hours <= "' . $endtime . '" AND
        fkSubHeader = '. $this->tdheaders[$datatype]->subheader->keyId .';';


        $r_slope=@mysql_query($q_slope,$this->dbconnection);
        $res=@mysql_fetch_array($r_slope);

        $N          =$res[0];
        $Xmean      =$res[1];
        $Xsum       =$res[2];
        $Xsumsquares=$res[3];
        $Ymean      =$res[4];
        $Ysum       =$res[5];
        $Ysumsquares=$res[6];
        $XYsum      =$res[7];

        $slope = round(($N * $XYsum - $Xsum*$Ysum) / ($N * $Xsumsquares - $Xsum * $Xsum),4);
        $intercept = round($Ymean - ($slope * $Xmean),4);

        if ($intercept < 0){
            $LinearEquation = "y = " . $slope . "x " . $intercept;
        }
        else {
            $LinearEquation = "y = " . $slope . "x + " . $intercept;
        }

        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        //fwrite($fh, "set log y\r\n");
        fwrite($fh, "set xlabel 'Time (hours)'\r\n");
        fwrite($fh, "set ylabel 'Presure (mbar)'\r\n");

        fwrite($fh, "set bmargin 7\r\n");
        fwrite($fh, "set label 'TestData_header.keyId: " .$this->tdheaders[$datatype]->keyId.", Cryostat Ver. $this->swversioncryo' at screen 0.01, 0.07\r\n");
        fwrite($fh, "set label '".$this->tdheaders[$datatype]->GetValue('TS').", FE Configuration ".$this->FEConfig."' at screen 0.01, 0.04\r\n");


        $f_x = str_replace("x","*x",$LinearEquation);
        $f_x = str_replace("y","f(x)",$f_x);
        fwrite($fh, "$f_x\r\n");

        fwrite($fh, "plot '$data_file' using 1:2 title '' with linespoints pointsize 0.2 pt 5 lt 3,f(x) lt 1 lw 3 title '$LinearEquation'  \r\n");
        //fwrite($fh, "plot '$data_file' using 1:2 title '' with linespoints pointsize 0.2 pt 5 lt 3,f(x) lt 1 lw 3 title ''  \r\n");

        fclose($fh);

        //Make the plot
        $GNUPLOT = '/usr/bin/gnuplot';
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);

    }

    private function GetRateOfRise($starttime,$endtime, $datatype){
        $q = "SELECT Pressure1, Time_hours FROM TEST_Cryostat_data
                WHERE fkSubHeader = ". $this->tdheaders[$datatype]->subheader->keyId ."
                AND Time_hours >= $starttime
                AND Time_hours <= $endtime
                ORDER BY Time_hours ASC LIMIT 1;";
        $r = @mysql_query($q,$this->dbconnection);
        $row = @mysql_fetch_array($r);
        $t_start =$row[1]*60*60;
        $p_start =$row[0];

        $q = "SELECT Pressure1, Time_hours FROM TEST_Cryostat_data
                WHERE fkSubHeader = ". $this->tdheaders[$datatype]->subheader->keyId ."
                AND Time_hours >= $starttime
                AND Time_hours <= $endtime
                ORDER BY Time_hours DESC LIMIT 1;";
        $r = @mysql_query($q,$this->dbconnection);
        $row = @mysql_fetch_array($r);
        $t_stop = $row[1]*60*60;
        $p_stop = $row[0];

        $delta_p = $p_start - $p_stop;
        $delta_t = $t_start - $t_stop;


        $RateOfRise = ($delta_p * 350)/$delta_t;

        $this->tdheaders[$datatype]->subheader->SetValue('RateOfRise',$RateOfRise);
        $this->tdheaders[$datatype]->subheader->Update();

        parent::Update();
        return $RateOfRise;
    }

    public function Plot_TemperatureCurves($datatype){
        //Update Plot_SWVer in TestData_header
        $this->tdheaders[$datatype]->SetValue('Plot_SWVer',$this->swversioncryo);
        $this->tdheaders[$datatype]->Update();

        if (!file_exists($this->datadir)){
            mkdir($this->datadir);
        }
        if (!file_exists($this->urldir)){
            mkdir($this->urldir);
        }

        $fkCryostat = $this->keyId;
        $data_file = $this->datadir . "cryo_tempdata$datatype.txt";

        //echo "datafile= $data_file<br>";
        if (file_exists($data_file)){
            unlink($data_file);
        }

        $q = "SELECT * FROM TEST_Cryostat_data
              WHERE fkSubHeader = ". $this->tdheaders[$datatype]->subheader->keyId ."
              ORDER BY Time_hours ASC;";

        //echo $q . "<br>";
        $r = @mysql_query($q,$this->dbconnection);
        $fh = fopen($data_file, 'w');

        while($row = @mysql_fetch_array($r)){
            $stringData = $row['Time_hours'] . "\t" . $row['sensor1_k'] . "\t" . $row['sensor2_k'];
            $stringData .= "\t" . $row['sensor3_k'] . "\t" . $row['sensor4_k'] . "\t" . $row['sensor5_k'];
            $stringData .= "\t" . $row['sensor6_k'] . "\t" . $row['sensor7_k'] . "\t" . $row['sensor8_k'];
            $stringData .= "\t" . $row['sensor9_k'] . "\t" . $row['sensor10_k'] . "\t" . $row['sensor11_k'];
            $stringData .= "\t" . $row['sensor12_k'] . "\t" . $row['sensor13_k'] . "\r\n";
            fwrite($fh, $stringData);
        }
        fclose($fh);

        //Write command file for gnuplot
        $plot_command_file = $this->datadir . "cryo_commandTEMP$datatype.txt";

        if (file_exists($plot_command_file)){
            unlink($plot_command_file);
        }

        $imagedirectory = $this->datadir;
        $urldirectory = $this->urldir;
        if (!file_exists($imagedirectory)){
            mkdir($imagedirectory);
        }


        if ($datatype==2){
            $imagename = "Cryostat_warmuptemps_SN" . $this->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";
            $plot_title = "Cryostat " . $this->GetValue('SN') . " Temperatures First Warmup";
        }
        if ($datatype==3){
            $imagename = "Cryostat_cooldowntemps_SN" . $this->GetValue('SN') . "_" . date("Ymd_G_i_s") . ".png";
            $plot_title = "Cryostat " . $this->GetValue('SN') . " Temperatures First Cooldown";
        }
        $imageurl = $urldirectory . $imagename;
        $this->tdheaders[$datatype]->subheader->SetValue('pic_temperature',$imageurl);
        $this->tdheaders[$datatype]->subheader->Update();

        $imagepath = $imagedirectory . $imagename;

        //echo "image url = $imageurl<br>";
        //echo "image path= $imagepath<br>";

        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 900,500\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set key outside\r\n");
        fwrite($fh, "set xlabel 'Time (hours)'\r\n");
        fwrite($fh, "set ylabel 'Temperature (K)'\r\n");

        fwrite($fh, "set bmargin 7\r\n");
        fwrite($fh, "set label 'TestData_header.keyId: " .$this->tdheaders[$datatype]->keyId.", Cryostat Ver. $this->swversioncryo' at screen 0.01, 0.07\r\n");
        fwrite($fh, "set label '".$this->tdheaders[$datatype]->GetValue('TS').", FE Configuration ".$this->FEConfig."' at screen 0.01, 0.04\r\n");


        $title1 = $this->tempsensors[1]->GetValue('location');
        $title2 = $this->tempsensors[2]->GetValue('location');
        $title3 = $this->tempsensors[3]->GetValue('location');
        $title4 = $this->tempsensors[4]->GetValue('location');
        $title5 = $this->tempsensors[5]->GetValue('location');
        $title6 = $this->tempsensors[6]->GetValue('location');
        $title7 = $this->tempsensors[7]->GetValue('location');
        $title8 = $this->tempsensors[8]->GetValue('location');
        $title9 = $this->tempsensors[9]->GetValue('location');
        $title10 = $this->tempsensors[10]->GetValue('location');
        $title11 = $this->tempsensors[11]->GetValue('location');
        $title12 = $this->tempsensors[12]->GetValue('location');
        $title13 = $this->tempsensors[13]->GetValue('location');


        $plotstring = "plot '$data_file' using 1:2 title '$title1' with lines,";
        $plotstring .= " '$data_file' using 1:3 title '$title2' with lines,";
        $plotstring .= " '$data_file' using 1:4 title '$title3' with lines,";
        $plotstring .= " '$data_file' using 1:5 title '$title4' with lines,";
        $plotstring .= " '$data_file' using 1:6 title '$title5' with lines,";
        $plotstring .= " '$data_file' using 1:7 title '$title6' with lines,";
        $plotstring .= " '$data_file' using 1:8 title '$title7' with lines,";
        $plotstring .= " '$data_file' using 1:9 title '$title8' with lines,";
        $plotstring .= " '$data_file' using 1:10 title '$title9' with lines,";
        $plotstring .= " '$data_file' using 1:11 title '$title10' with lines,";
        $plotstring .= " '$data_file' using 1:12 title '$title11' with lines,";
        $plotstring .= " '$data_file' using 1:13 title '$title12' with lines,";
        $plotstring .= " '$data_file' using 1:14 title '$title13' with lines \r\n";
        fwrite($fh, $plotstring);
        fclose($fh);

        //Make the plot
        $GNUPLOT = '/usr/bin/gnuplot';
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
    }

    private function Convert_RtoTemp($R,$K, $index_count){
        if ($R == 0){
            return 1;
        }
        if ($R != 0){
            $T = $K[1] + $K[2]*pow(1000/$R, 1) + $K[3]*pow(1000/$R, 2) + $K[4]*pow(1000/$R, 3) + $K[5]*pow(1000/$R, 4)
                + $K[6]*pow(1000/$R, 5) + $K[7]*pow(1000/$R, 6);

            //For the PRT sensors (1,2,11,13), use a different formula.
            switch ($index_count)
            {
            case 1:
              $T = $K[1] + $K[2]*pow($R, 1) + $K[3]*pow($R, 2) + $K[4]*pow($R, 3) + $K[5]*pow($R, 4)
                + $K[6]*pow($R, 5) + $K[7]*pow($R, 6);
              break;
            case 2:
              $T = $K[1] + $K[2]*pow($R, 1) + $K[3]*pow($R, 2) + $K[4]*pow($R, 3) + $K[5]*pow($R, 4)
                + $K[6]*pow($R, 5) + $K[7]*pow($R, 6);
              break;
            case 11:
              $T = $K[1] + $K[2]*pow($R, 1) + $K[3]*pow($R, 2) + $K[4]*pow($R, 3) + $K[5]*pow($R, 4)
                + $K[6]*pow($R, 5) + $K[7]*pow($R, 6);
              break;
            case 13:
              $T = $K[1] + $K[2]*pow($R, 1) + $K[3]*pow($R, 2) + $K[4]*pow($R, 3) + $K[5]*pow($R, 4)
                + $K[6]*pow($R, 5) + $K[7]*pow($R, 6);
              break;

            default:
              $T = $K[1] + $K[2]*pow(1000/$R, 1) + $K[3]*pow(1000/$R, 2) + $K[4]*pow(1000/$R, 3) + $K[5]*pow(1000/$R, 4)
                + $K[6]*pow(1000/$R, 5) + $K[7]*pow(1000/$R, 6);
            }

            return $T;
        }
    }

    private function ExportCSV($datatype){
        echo '<meta http-equiv="Refresh" content="1;url=export_to_csv.php?keyId='.$this->keyId.'&datatype='.$datatype.'">';
    }

    private function ExportINI($datatype){
        echo '<meta http-equiv="Refresh" content="1;url=../cryostat/export_to_ini.php?keyId='.$this->keyId.'&datatype='.$datatype.'&fc='. $this->GetValue('keyFacility') . '">';
    }


    public function DownloadToWord(){

        $docname = "cryostat_" . date("Ymd_G_i_s") . ".doc";
        header("Content-type: application/vnd.ms-word");
        header("Content-Disposition: attachment;Filename=$docname");

        echo "<html>";
        echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=Windows-1252\">";
        echo "<body>";

        echo "<img src= '" . parent::GetValue('pic_rateofrise')  . "'>";
        echo "<img src= '" . parent::GetValue('pic_firstwarmup')  . "'>";
        echo "<img src= '" . parent::GetValue('pic_firstwarmup_temps')  . "'>";
        echo "<img src= '" . parent::GetValue('pic_firstcooldown')  . "'>";
        echo "<img src= '" . parent::GetValue('pic_firstcooldown_temps')  . "'>";


        echo "</body>";
        echo "</html>";
    }

    public function Display_FinalCooldownTemps(){

        //Cooldown datatype=3
        $q="SELECT * FROM TEST_Cryostat_data
        WHERE fkSubHeader = ". $this->tdheaders[3]->subheader->keyId ."
        ORDER BY Time_hours DESC
        LIMIT 1;";

        $r=@mysql_query($q,$this->dbconnection);
        $cryodata=@mysql_fetch_object($r);

        echo '
        <div style = "width:300px">
        <table id = "table1" align="left" cellspacing="1" cellpadding="1" >
          <tr class = "alt">
            <th colspan = "2">Final Temperatures</b></th>
          </tr>
          <tr>
              <th>Location</th>
            <th>Temperature (K)</th>
          </tr>';

        echo "<tr bgcolor='#ffffff'>
                <td>" . $this->tempsensors[5]->GetValue('location') . "</td>
                <td>". round($cryodata->sensor5_k,2) ."</td>
              </tr>";
        echo "<tr bgcolor='#ffffff'>
                <td>" . $this->tempsensors[7]->GetValue('location') . "</td>
                <td>". round($cryodata->sensor7_k,2) ."</td>
              </tr>";
        echo "<tr bgcolor='#ffffff'>
                <td>" . $this->tempsensors[8]->GetValue('location') . "</td>
                <td>". round($cryodata->sensor8_k,2) ."</td>
              </tr>";
        echo "<tr bgcolor='#ffffff'>
                <td>" . $this->tempsensors[9]->GetValue('location') . "</td>
                <td>". round($cryodata->sensor9_k,2) ."</td>
              </tr>";
        echo "<tr bgcolor='#ffffff'>
                <td>" . $this->tempsensors[10]->GetValue('location') . "</td>
                <td>". round($cryodata->sensor10_k,2) ."</td>
              </tr>";
        echo "<tr bgcolor='#ffffff'>
                <td>" . $this->tempsensors[6]->GetValue('location') . "</td>
                <td>". round($cryodata->sensor6_k,2) ."</td>
              </tr>";
        echo "<tr bgcolor='#ffffff'>
                <td>" . $this->tempsensors[3]->GetValue('location') . "</td>
                <td>". round($cryodata->sensor3_k,2) ."</td>
              </tr>";
        echo "<tr bgcolor='#ffffff'>
                <td>" . $this->tempsensors[4]->GetValue('location') . "</td>
                <td>". round($cryodata->sensor4_k,2) ."</td>
              </tr>";


        echo "<tr bgcolor='#ffffff'>
                <td>" . $this->tempsensors[12]->GetValue('location') . "</td>
                <td>". round($cryodata->sensor12_k,2) ."</td>
              </tr>";
        echo "<tr bgcolor='#ffffff'>
                <td>" . $this->tempsensors[11]->GetValue('location') . "</td>
                <td>". round($cryodata->sensor11_k,2) ."</td>
              </tr>";
        echo "<tr bgcolor='#ffffff'>
                <td>" . $this->tempsensors[1]->GetValue('location') . "</td>
                <td>". round($cryodata->sensor1_k,2) ."</td>
              </tr>";
        echo "<tr bgcolor='#ffffff'>
                <td>" . $this->tempsensors[2]->GetValue('location') . "</td>
                <td>". round($cryodata->sensor2_k,2) ."</td>
              </tr>";
        echo "<tr bgcolor='#ffffff'>
                <td>" . $this->tempsensors[13]->GetValue('location') . "</td>
                <td>". round($cryodata->sensor13_k,2) ."</td>
              </tr>";

        unset($cryodata );

        echo "</table></div>";
        echo "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>";
    }


    public function Display_RORselector($selname,$message,$getval,$optname,$datatype){
        $q_ror = "SELECT Time_hours FROM TEST_Cryostat_data
                  WHERE fkSubHeader = ". $this->tdheaders[$datatype]->subheader->keyId ."
                  ORDER BY Time_hours ASC;";
        //echo $q_ror . "<br>";

        //echo "$selname subheader ROR info...<br><br>";

        //echo "keyId= " . $this->tdheaders[$datatype]->subheader->keyId . "<br>";
        //echo "rorstart= " . $this->tdheaders[$datatype]->subheader->GetValue('ror_starttime') . "<br>";
        //echo "rorstop= " . $this->tdheaders[$datatype]->subheader->GetValue('ror_stoptime') . "<br>";
        //echo "rorgetval= " . $this->tdheaders[$datatype]->subheader->GetValue($getval) . "<br>";

        $optvar = "";
        //$optvar = ${"optvar$i"};


        $r_ror = @mysql_query($q_ror,$this->dbconnection);
        echo "$message <select name='$selname'>";
        while ($row_ror=@mysql_fetch_array($r_ror)){
            //echo "val= $row_ror[0]<br>";
            if (round($row_ror[0],0)== round($this->tdheaders[$datatype]->subheader->GetValue($getval),0)){
            ${"optvar$datatype"} .= "<option value='$row_ror[0]' selected = 'selected'>".round($row_ror[0],2)."</option>";
            }

            else{
            ${"optvar$datatype"} .= "<option value='$row_ror[0]' >".round($row_ror[0],2)."</option>";
            }
        }
        echo ${"optvar$datatype"};
        echo "</select>";
    }

    public function PicURL_Prefix($inurl){
        $result = $inurl;
        if (stripos($inurl,"/php") < 6){
            $result = "https://safe.nrao.edu" . $inurl;
        }
        return $result;
    }

    public function showspinner(){
        echo '<script type="text/javascript" src="../spin.js"></script>';
        //Show a spinner while plots are being drawn.
        echo "<div id='spinner' style='position:absolute;
        left:400px;
        top:25px;'>
        <font color = '#00ff00'><b>
            &nbsp &nbsp &nbsp &nbsp
            &nbsp &nbsp &nbsp &nbsp
            &nbsp &nbsp &nbsp &nbsp
            &nbsp &nbsp &nbsp &nbsp
            &nbsp &nbsp &nbsp &nbsp
            Drawing Plots...
            </font></b>

        </div>";
        echo "<script language = 'javascript'>
            var opts = {
              lines: 12, // The number of lines to draw
              length: 10, // The length of each line
              width: 3, // The line thickness
              radius: 10, // The radius of the inner circle
              color: '#00ff00', // #rgb or #rrggbb
              speed: 1, // Rounds per second
              trail: 60, // Afterglow percentage
              shadow: false, // Whether to render a shadow

            };
            var target = document.getElementById('spinner');
            var spinner = new Spinner(opts).spin(target);
        </script>
        ";
    }
}
?>