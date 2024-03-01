<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.tempsensor.php');
require_once($site_classes . '/xlreader/reader.php');
require_once($site_dbConnect);

if (!isset($GNUPLOT_VER)) {
    global $GNUPLOT_VER;
    $GNUPLOT_VER = 4.9;
}

class Cryostat extends GenericTable {
    private $tempsensors;
    private $datadir;
    private $FESN;          //SN of the Front End
    private $FEid;          //keyId of the Front End
    private $FEConfig;      //Latest configuration of the Front End
    private $swversion;

    function __construct($in_keyId, $in_fc) {
        require(site_get_config_main());
        $this->tempsensors = array();
        parent::__construct('FE_Components', $in_keyId, "keyId", $in_fc, 'keyFacility');
        $this->swversion = '2.0.0';

        /* version history
         * 2.0.0:  MTM removed obsolete plots and data displays, added Cooldown plots, repaired tempsensor upload.
         * 1.0.4:  MTM fixed "set...screen" commands to gnuplot
        */

        $this->datadir = $main_write_directory . "cryostat" . $this->SN . "/";
        //Find which Front End this component is in (if any)
        $q = "SELECT Front_Ends.SN,
                     FE_Config.keyFEConfig,
                     Front_Ends.keyFrontEnds
              FROM Front_Ends, FE_ConfigLink, FE_Config
              WHERE FE_ConfigLink.fkFE_Components = {$this->keyId}
              AND FE_ConfigLink.fkFE_Config = FE_Config.keyFEConfig
              AND FE_Config.fkFront_Ends = Front_Ends.keyFrontEnds
              GROUP BY FE_Config.keyFEConfig ORDER BY FE_Config.keyFEConfig DESC LIMIT 1;";
        $r = mysqli_query($this->dbConnection, $q);
        $this->FESN = ADAPT_mysqli_result($r, 0, 0);
        $this->FEConfig = ADAPT_mysqli_result($r, 0, 1);
        $this->FEid = ADAPT_mysqli_result($r, 0, 2);

        //Fill the array of tempsensors
        for ($i = 1; $i <= 13; $i++) {
            $this->tempsensors[$i] = new Cryostat_tempsensor($this->keyId, $i, $in_fc);
        }
    }
    function __destruct() {
        for ($i = 1; $i <= 13; $i++) {
            unset($this->tempsensors[$i]);
        }
    }

    public function Update_Cryostat() {
        parent::Update();
    }

    public function DisplayData_Cryostat($in_DisplayType = "all") {
        require(site_get_config_main());

        echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';

        if ($this->SN != "") {
            switch ($in_DisplayType) {
                case 'all':
                    echo "<br><font size='+2'><b><u>Cryostat Information</u></b></font><br>";
                    echo "<br>SN:<input type='text' name='SN' size='10' maxlength='20' value = '" . $this->SN . "'><br>";
                    echo "<br>" . $this->TS . "<br>";
                    $this->DisplayTempSenors();
                    break;                
            }
        }

        echo "<div style ='width:100%;height:30%'>";
        echo "<div align='left' style ='width:50%;height:30%'>";
        echo "<input type='hidden' name='keyId' value='$this->keyId'>";
        if ($this->keyFacility != '') {
            $fc = $this->keyFacility;
        }
        echo "<input type='hidden' name='fc' value='$fc'>";
        if ($in_DisplayType == 'all') {
            echo "<br><br><input type='submit' name = 'submitted' value='SAVE CHANGES'>";
            echo "<input type='submit' name = 'deleterecord' value='DELETE RECORD'>";
        }
        echo "</div></div>";
        echo "</form>";
    }

    public function DisplayCoolDownPlots($tdheader) {
        echo $this->DisplayCoolDownPlots_html($tdheader);        
    }

    public function DisplayCoolDownPlots_html($tdheader) {
        $html = '';
        global $site_storage;
        if ($tdheader->keyId != '') {
            $q = "SELECT plot_url FROM TEST_Cryostat_data_SubHeader WHERE fkHeader = " . $tdheader->keyId . ";";
            $r = mysqli_query($this->dbConnection, $q);
            $urls = explode(",", ADAPT_mysqli_result($r, 0, 0));            
            for ($i = 0; $i < count($urls); $i++) {
                $url = $urls[$i];
                $html .= "<div><img src='{$site_storage}{$url}'><br><br></div>";
            }
        }
        return $html;
    }

    public function Display_uploadform_TempSensor() {
        echo '
        <!-- The data encoding type, enctype, MUST be specified as below -->
        <form enctype="multipart/form-data" action="ShowComponents.php?fc=' . $this->fc . '&conf=' . $this->keyId . '" method="POST">
        <!-- MAX_FILE_SIZE must precede the file input field -->
        <!-- <input type="hidden" name="MAX_FILE_SIZE" value="100000" /> -->
        <!-- Name of input element determines name in $_FILES array -->
        Temp Sensor Calibration (Excel): </b><input name="file_tempsensors" type="file" />
        <input type="submit" class="submit" name= "submit_datafile_cryostat" value="Upload Temp Sensor Excel File" />
        <input type="hidden" name="keyId" value="' . $this->keyId . '">
        <input type="hidden" name="fc" value="' . $this->keyFacility . '">
        </form>';
    }

    public function DisplayTempSenors() {
        echo "<div style='width:900px'>";
        echo '<table id = "table1" align="left" cellspacing="1" cellpadding="1">';
        echo '<tr class = "alt">
                <th colspan = "3">CRYOSTAT ' . $this->SN . ' TEMPERATURE SENSORS</th>
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
        for ($i = 1; $i <= count($this->tempsensors); $i++) {
            $trclass = ($trclass == "" ? "tr class = 'alt'" : "");
            echo "<tr $trclass>
                    <td>" . $this->tempsensors[$i]->sensor_number . "</td>";
            echo "<td><b>" .
                $this->tempsensors[$i]->sensor_type . "</b></td>";
            echo "<td>{$this->tempsensors[$i]->location}</td>
                <td width = '40px'>" . round($this->tempsensors[$i]->k1, 3) . "</td>
                <td width = '40px'>" . round($this->tempsensors[$i]->k2, 3) . "</td>
                <td width = '40px'>" . round($this->tempsensors[$i]->k3, 3) . "</td>
                <td width = '40px'>" . round($this->tempsensors[$i]->k4, 3) . "</td>
                <td width = '40px'>" . round($this->tempsensors[$i]->k5, 3) . "</td>
                <td width = '40px'>" . round($this->tempsensors[$i]->k6, 3) . "</td>
                <td width = '40px'>" . round($this->tempsensors[$i]->k7, 3) . "</td>";
            echo "</tr>";
        }

        $url = 'export_to_ini_cryostat.php?keyId=' . $this->keyId . '&datatype=tempsensors&fc=' . $this->keyFacility;
?>

        <tr class='alt2'>
            <th colspan=10 align='right'>
                <?php
                $this->Display_uploadform_TempSensor();
                ?>
                </td>
        </tr>

        <tr class='alt2'>
            <th colspan=10 align='right'>
                <form>
                    <INPUT TYPE="BUTTON" class="submit" VALUE="Export to INI file" ONCLICK="window.location.href='<?php echo $url; ?>'">
                </form>
                </td>
        </tr>
<?php
        echo "</table></div><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>";
    }

    public function RequestValues_Cryostat() {
        $checkbox = "off";
        if (isset($_REQUEST['checkbox_rateofrise'])) {
            $checkbox = "on";
        }

        parent::RequestValues();

        if (isset($_REQUEST['deleterecord_forsure'])) {
            $this->DeleteRecord_cryostat();
        }

        if (isset($_REQUEST['exporttempsensors'])) {
            $this->ExportINI("tempsensors");
        }

        if (isset($_REQUEST['submit_datafile_cryostat'])) {
            if (isset($_FILES['file_tempsensors']['name'])) {
                if ($_FILES['file_tempsensors']['name'] != "") {
                    $this->Upload_tempsensorfile($_FILES['file_tempsensors']['tmp_name']);
                }
            }
            $this->Update_Cryostat();
        }
    }

    public function Delete_TempSensors() {
        //delete from Cryostat_tempsensors
        $qd1 = "DELETE FROM Cryostat_tempsensors
                WHERE fkCryostat = $this->keyId;";
        $rd1 = mysqli_query($this->dbConnection, $qd1);
    }

    public function DeleteRecord_cryostat() {

        $qd1 = "DELETE FROM FE_Components
                WHERE keyId = $this->keyId;";
        $rd1 = mysqli_query($this->dbConnection, $qd1);

        //delete from Cryostat_tempsensors
        $qd1 = "DELETE FROM Cryostat_tempsensors
                WHERE fkCryostat = $this->keyId;";
        $rd1 = mysqli_query($this->dbConnection, $qd1);

        //parent::Delete_record();
        echo '<meta http-equiv="Refresh" content="1;url=cryostats.php">';
    }

    public function Upload_tempsensorfile($datafile_name) {

        $this->Delete_TempSensors();
        for ($k = 1; $k <= 13; $k++) {
            //Create Temp sensor records for this cryostat
            $tempsensor = GenericTable::NewRecord('Cryostat_tempsensors', 'keyId', $this->keyFacility, 'fkFacility');
            $tempsensor->SetValue('fkCryostat', $this->keyId);
            $tempsensor->SetValue('sensor_number', $k);
            $tempsensor->Update();
            unset($tempsensor);
        }
        //Fill the array of tempsensors
        for ($i = 1; $i <= 13; $i++) {
            $this->tempsensors[$i] = new Cryostat_tempsensor($this->keyId, $i, $this->keyFacility);
        }

        $sheetnumber = 0;
        $data = new Spreadsheet_Excel_Reader();
        $data->setOutputEncoding('CP1251');
        $data->read($datafile_name);

        $nonPRT_arr = array(3, 4, 5, 6, 7, 8, 9, 10, 12);
        $nonPRT_locations = array(
            "12K Plate Near Link", "12K Plate Far Side", "4K Cryocooler Stage",
            "12K Cryocooler Stage", "4K Plate Near Link a", "4K Plate Near Link b",
            "4K Plate Far Side B", "4K Plate Far Side A", "12K Shield Top"
        );

        $nonPRT_count = 0;

        //Get the K values for all non-PRT sensors
        for ($sheetnumber = 0; $sheetnumber < count($data->sheets); $sheetnumber++)

            if ($data->boundsheets[$sheetnumber]['name'] != "PRT") {
                //echo "Sheet name= " . $data->boundsheets[$sheetnumber]['name'] . "<br>";
                //echo "not prt<br>";
                $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->SetValue('sensor_type', $data->boundsheets[$sheetnumber]['name']);
                for ($i = 1; $i <= $data->sheets[$sheetnumber]['numRows']; $i++) {
                    //echo $data->sheets[$sheetnumber]['cells'][$i][1] . "<br>";
                    switch ($data->sheets[$sheetnumber]['cells'][$i][1]) {

                        case "K1":
                            $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->SetValue('k1', $data->sheets[$sheetnumber]['cells'][$i][3]);
                            break;
                        case "K2":
                            $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->SetValue('k2', $data->sheets[$sheetnumber]['cells'][$i][3]);
                            break;
                        case "K3":
                            $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->SetValue('k3', $data->sheets[$sheetnumber]['cells'][$i][3]);
                            break;
                        case "K4":
                            $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->SetValue('k4', $data->sheets[$sheetnumber]['cells'][$i][3]);
                            break;
                        case "K5":
                            $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->SetValue('k5', $data->sheets[$sheetnumber]['cells'][$i][3]);
                            break;
                        case "K6":
                            $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->SetValue('k6', $data->sheets[$sheetnumber]['cells'][$i][3]);
                            break;
                        case "K7":
                            $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->SetValue('k7', $data->sheets[$sheetnumber]['cells'][$i][3]);
                            break;
                    }
                }
                $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->SetValue('location', $nonPRT_locations[$nonPRT_count]);
                $this->tempsensors[$nonPRT_arr[$nonPRT_count]]->Update();
                $nonPRT_count++;
            }
        unset($data);

        //Fill up the K values for the PRT sensors
        $PRT_arr = array(1, 2, 11, 13);
        $PRT_locations = array("90K Plate Near Link", "90K Plate Far Side", "90K Cryocooler Stage", "90K Shield Top");

        $PRT_count = 0;
        for ($i = 0; $i < 4; $i++) {
            $this->tempsensors[$PRT_arr[$i]]->SetValue('sensor_type', 'PRT');
            $this->tempsensors[$PRT_arr[$i]]->SetValue('location', $PRT_locations[$i]);
            $this->tempsensors[$PRT_arr[$i]]->SetValue('k1', 28.486734);
            $this->tempsensors[$PRT_arr[$i]]->SetValue('k2', 278.38662);
            $this->tempsensors[$PRT_arr[$i]]->SetValue('k3', -260.205006);
            $this->tempsensors[$PRT_arr[$i]]->SetValue('k4', 687.754698);
            $this->tempsensors[$PRT_arr[$i]]->SetValue('k5', -891.65283);
            $this->tempsensors[$PRT_arr[$i]]->SetValue('k6', 583.15814);
            $this->tempsensors[$PRT_arr[$i]]->SetValue('k7', -152.808821);
            $this->tempsensors[$PRT_arr[$i]]->Update();
        }
    }

    public function Plot_Cooldown($tdheader) {
        //Update Plot_SWVer in TestData_header
        $tdheader->SetValue('Plot_SWVer', $this->swversion);
        $tdheader->Update();

        if (!file_exists($this->datadir)) {
            mkdir($this->datadir);
        }

        $fkCryostat = $this->keyId;
        $data_file = $this->datadir . "cryo_cooldown_temp.txt";

        if (file_exists($data_file)) {
            unlink($data_file);
        }

        //Get cryostat sensors data for temp file:
        $q = "SELECT * FROM TEST_CryostatCooldown
              WHERE fkTestDataHeader = $tdheader->keyId
              ORDER BY keyId ASC;";

        $r = mysqli_query($this->dbConnection, $q);
        $fh = fopen($data_file, 'w');

        $startTime = 0;
        while ($row = mysqli_fetch_array($r)) {
            if (!$startTime) {
                $startTime = strtotime($row['TS']);
            }
            $elapsedHours = (strtotime($row['TS']) - $startTime) / 3600;

            fwrite($fh, $elapsedHours . "\t");
            fwrite($fh, $row['BackingPumpEnable'] . "\t");
            fwrite($fh, $row['TurboPumpEnable'] . "\t");
            fwrite($fh, $row['TurboPumpError'] . "\t");
            fwrite($fh, $row['TurboPumpSpeed'] . "\t");
            fwrite($fh, $row['GateValveState'] . "\t");
            fwrite($fh, $row['SolenoidValveState'] . "\t");
            fwrite($fh, $row['SupplyCurrent230V'] . "\t");
            fwrite($fh, $row['CryoVacuumPressure'] . "\t");
            fwrite($fh, $row['PortVacuumPressure'] . "\t");
            fwrite($fh, $row['4k_CryoCooler'] . "\t");
            fwrite($fh, $row['4k_PlateLink1'] . "\t");
            fwrite($fh, $row['4k_PlateLink2'] . "\t");
            fwrite($fh, $row['4k_PlateFarSide1'] . "\t");
            fwrite($fh, $row['4k_PlateFarSide2'] . "\t");
            fwrite($fh, $row['15k_CryoCooler'] . "\t");
            fwrite($fh, $row['15k_PlateLink'] . "\t");
            fwrite($fh, $row['15k_PlateFarSide'] . "\t");
            fwrite($fh, $row['15k_Shield'] . "\t");
            fwrite($fh, $row['110k_CryoCooler'] . "\t");
            fwrite($fh, $row['110k_PlateLink'] . "\t");
            fwrite($fh, $row['110k_PlateFarSide'] . "\t");
            fwrite($fh, $row['110k_Shield'] . "\r\n");
        }
        fclose($fh);
        $temp_url = $this->Plot_Cooldown_Temps($tdheader, $data_file);
        $pres_url = $this->Plot_Cooldown_Pressure($tdheader, $data_file);
        unlink($data_file);
        
        // store image URLs in subheader:
        $q = "UPDATE TEST_Cryostat_data_SubHeader SET plot_url = '$temp_url,$pres_url' WHERE fkHeader = " . $tdheader->keyId . " LIMIT 1;";
        $r = mysqli_query($this->dbConnection, $q);
    }

    public function Plot_Cooldown_Temps($tdheader, $data_file) {        
        //Write command file for gnuplot
        $plot_command_file = $this->datadir . "cryo_cooldown_cmd.txt";
        if (file_exists($plot_command_file)) {
            unlink($plot_command_file);
        }
        $imagename = "Cryostat_cooldown_temps" . $this->SN . "_" . $this->FEConfig . "_" . $tdheader->keyId . ".png";
        $plot_title = "Cryostat " . $this->SN . " cool-down / warm-up temperatures";
        $imagepath = $this->datadir . $imagename;

        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 900,500\r\n");
        global $GNUPLOT_VER;
        if ($GNUPLOT_VER >= 5.0)
            fwrite($fh, "set colorsequence classic\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set key outside\r\n");
        fwrite($fh, "set xlabel 'Time (hours)'\r\n");
        fwrite($fh, "set ylabel 'Temperature (K)'\r\n");

        fwrite($fh, "set bmargin 7\r\n");
        fwrite($fh, "set label 'TestData header.keyId: " . $tdheader->keyId . ", Cryostat Ver. $this->swversion' at screen 0.01, 0.07\r\n");
        fwrite($fh, "set label '" . $tdheader->TS . ", FE Configuration " . $this->FEConfig . "' at screen 0.01, 0.04\r\n");

        $title1 = '4K Cryocooler';
        $title2 = '4K Plate Link 1';
        $title3 = '4K Plate Link 2';
        $title4 = '4K Plate Far Side 1';
        $title5 = '4K Plate Far Side 2';
        $title6 = '15K Cryocooler';
        $title7 = '15K Plate Link';
        $title8 = '15K Plate Far Side';
        $title9 = '15K Shield';
        $title10 = '110K Cryocooler';
        $title11 = '110K Plate Link';
        $title12 = '110K Plate Far Side';
        $title13 = '110K Shield';

        $plotstring = "plot '$data_file' using 1:11 title '$title1' with lines,";
        $plotstring .= " '$data_file' using 1:12 title '$title2' with lines,";
        $plotstring .= " '$data_file' using 1:13 title '$title3' with lines,";
        $plotstring .= " '$data_file' using 1:14 title '$title4' with lines,";
        $plotstring .= " '$data_file' using 1:15 title '$title5' with lines,";
        $plotstring .= " '$data_file' using 1:16 title '$title6' with lines,";
        $plotstring .= " '$data_file' using 1:17 title '$title7' with lines,";
        $plotstring .= " '$data_file' using 1:18 title '$title8' with lines,";
        $plotstring .= " '$data_file' using 1:19 title '$title9' with lines,";
        $plotstring .= " '$data_file' using 1:20 title '$title10' with lines,";
        $plotstring .= " '$data_file' using 1:21 title '$title11' with lines,";
        $plotstring .= " '$data_file' using 1:22 title '$title12' with lines,";
        $plotstring .= " '$data_file' using 1:23 title '$title13' with lines\r\n";
        fwrite($fh, $plotstring);
        fclose($fh);

        // Make the plot
        $GNUPLOT = '/usr/bin/gnuplot';
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
        unlink($plot_command_file);

        // send image to storage server
        $image_url = "cryostat/$imagename";
        $this->sendImage($imagepath);

        // return the image URL:
        return $image_url;
    }

    public function Plot_Cooldown_Pressure($tdheader, $data_file) {
        
        //Write command file for gnuplot
        $plot_command_file = $this->datadir . "cryo_pressure_cmd.txt";
        if (file_exists($plot_command_file)) {
            unlink($plot_command_file);
        }
        $imagename = "Cryostat_cooldown_press" . $this->SN . "_" . $this->FEConfig . "_" . $tdheader->keyId . ".png";
        $plot_title = "Cryostat " . $this->SN . " cool-down / warm-up pressures";
        $imagepath = $this->datadir . $imagename;

        $fh = fopen($plot_command_file, 'w');
        fwrite($fh, "set terminal png size 900,500\r\n");
        global $GNUPLOT_VER;
        if ($GNUPLOT_VER >= 5.0)
            fwrite($fh, "set colorsequence classic\r\n");
        fwrite($fh, "set output '$imagepath'\r\n");
        fwrite($fh, "set title '$plot_title'\r\n");
        fwrite($fh, "set grid\r\n");
        fwrite($fh, "set key outside\r\n");
        fwrite($fh, "set log y\r\n");
        fwrite($fh, "set xlabel 'Time (hours)'\r\n");
        fwrite($fh, "set ylabel 'Presure (mbar)'\r\n");

        fwrite($fh, "set bmargin 7\r\n");
        fwrite($fh, "set label 'TestData header.keyId: " . $tdheader->keyId . ", Cryostat Ver. $this->swversion' at screen 0.01, 0.07\r\n");
        fwrite($fh, "set label '" . $tdheader->TS . ", FE Configuration " . $this->FEConfig . "' at screen 0.01, 0.04\r\n");

        fwrite($fh, "plot '$data_file' using 1:9 title 'Cryostat Pressure' with lines,");
        fwrite($fh, " '$data_file' using 1:10 title 'Port Pressure' with lines\r\n");
        fclose($fh);

        //Make the plot
        $GNUPLOT = '/usr/bin/gnuplot';
        $CommandString = "$GNUPLOT $plot_command_file";
        system($CommandString);
        unlink($plot_command_file);

        // send image to storage server
        $image_url = "cryostat/$imagename";
        $this->sendImage($imagepath);

        // return the image URL:
        return $image_url;
    }


    private function sendImage($imagepath, $path = "cryostat/") {
        global $site_storage;
        $ch = curl_init("{$site_storage}upload.php");
        curl_setopt_array($ch, array(
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => array(
                'image' => new CURLFile($imagepath, 'image/png'),
                'path' => $path,
                'token' => getenv("STORAGE_TOKEN")
            )
        ));
        curl_exec($ch);
        unlink($imagepath);
    }

    private function Convert_RtoTemp($R, $K, $index_count) {
        if ($R == 0) {
            return 1;
        }
        if ($R != 0) {
            $T = $K[1] + $K[2] * pow(1000 / $R, 1) + $K[3] * pow(1000 / $R, 2) + $K[4] * pow(1000 / $R, 3) + $K[5] * pow(1000 / $R, 4)
                + $K[6] * pow(1000 / $R, 5) + $K[7] * pow(1000 / $R, 6);

            //For the PRT sensors (1,2,11,13), use a different formula.
            switch ($index_count) {
                case 1:
                    $T = $K[1] + $K[2] * pow($R, 1) + $K[3] * pow($R, 2) + $K[4] * pow($R, 3) + $K[5] * pow($R, 4)
                        + $K[6] * pow($R, 5) + $K[7] * pow($R, 6);
                    break;
                case 2:
                    $T = $K[1] + $K[2] * pow($R, 1) + $K[3] * pow($R, 2) + $K[4] * pow($R, 3) + $K[5] * pow($R, 4)
                        + $K[6] * pow($R, 5) + $K[7] * pow($R, 6);
                    break;
                case 11:
                    $T = $K[1] + $K[2] * pow($R, 1) + $K[3] * pow($R, 2) + $K[4] * pow($R, 3) + $K[5] * pow($R, 4)
                        + $K[6] * pow($R, 5) + $K[7] * pow($R, 6);
                    break;
                case 13:
                    $T = $K[1] + $K[2] * pow($R, 1) + $K[3] * pow($R, 2) + $K[4] * pow($R, 3) + $K[5] * pow($R, 4)
                        + $K[6] * pow($R, 5) + $K[7] * pow($R, 6);
                    break;

                default:
                    $T = $K[1] + $K[2] * pow(1000 / $R, 1) + $K[3] * pow(1000 / $R, 2) + $K[4] * pow(1000 / $R, 3) + $K[5] * pow(1000 / $R, 4)
                        + $K[6] * pow(1000 / $R, 5) + $K[7] * pow(1000 / $R, 6);
            }

            return $T;
        }
    }

    private function ExportCSV($datatype) {
        echo '<meta http-equiv="Refresh" content="1;url=export_to_csv.php?keyId=' . $this->keyId . '&datatype=' . $datatype . '">';
    }

    private function ExportINI($datatype) {
        echo '<meta http-equiv="Refresh" content="1;url=../cryostat/export_to_ini.php?keyId=' . $this->keyId . '&datatype=' . $datatype . '&fc=' . $this->keyFacility . '">';
    }
}
?>