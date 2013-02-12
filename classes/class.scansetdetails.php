<?php
// Reads/modifies a ScanSetDetails and its corresponding TestDataHeader in the FEIC database.
//
// TODO: possibly obsolete code in:
// GenerateAllPlots()
// -- is handled by the beameff_64 C program now.
// DisplayData_ScanSetDetails()
// Display_add_form()
// -- not called by any other code.
//

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.scandetails.php');
require_once($site_classes . '/class.testdata_header.php');


class ScanSetDetails extends GenericTable {
    var $keyId_copol_pol0_scan;
    var $keyId_xpol_pol0_scan;
    var $keyId_copol_pol1_scan;
    var $keyId_xpol_pol1_scan;
    var $keyId_180_scan;

    var $Scan_copol_pol0;
    var $Scan_xpol_pol0;
    var $Scan_copol_pol1;
    var $Scan_xpol_pol1;
    var $Scan_180;
    var $fc; //facility key
    var $tdh;  //TestData_header record (class.generictable.php)

    public function Initialize_ScanSetDetails($keyId, $in_fc){
        $this->fc = $in_fc;
        parent::Initialize("ScanSetDetails",$keyId,"keyId",$this->fc,'fkFacility');

        $this->tdh = new TestData_header();
        $this->tdh->Initialize_TestData_header($this->GetValue('fkHeader'),$this->GetValue('fkFacility'));
    }

    public function RequestValues_ScanSetDetails(){
        if (isset($_REQUEST['notes'])){
            //echo "Notes: " .$_REQUEST['notes'] . "<br>";
            $this->SetValue('notes',$_REQUEST['notes']);
            $this->Update();
            $this->tdh->SetValue('Notes',$_REQUEST['notes']);
            $this->tdh->Update();
            }

        foreach ($this->propertyNames as &$propertyName){
            if (isset($_REQUEST[$propertyName])){
            $this->SetValue($propertyName,$_REQUEST[$propertyName]);
            }
        }
        if (isset($_REQUEST['deleterecord'])){
            $this->Display_delete_form_SSD();
        }
        if (isset($_REQUEST['deleterecord_forsure'])){
            $this->SetValue('is_deleted','1');
            $this->Update();
            echo '<meta http-equiv="Refresh" content="1;url=bplist.php?fc=$this->fc&keyconfig='.$this->GetValue('fkFE_Config') .'">';
        }



        //COPOL, POL 0 ScanDetails
        $q_scans = "SELECT keyId FROM ScanDetails
                    WHERE fkScanSetDetails = ".$this->propertyVals['keyId']."
                    AND pol = 0 and copol = 1
                    AND SourcePosition < 3
                    AND fkFacility = $this->fc
                    LIMIT 1;";
        $r_scans = @mysql_query($q_scans,$this->dbconnection);
        $this->keyId_copol_pol0_scan = @mysql_result($r_scans,0);
        $this->Scan_copol_pol0 = new ScanDetails;
        $this->Scan_copol_pol0->Initialize_ScanDetails($this->keyId_copol_pol0_scan, $this->fc);

            if (isset($_REQUEST['ifatten_copol_pol0'])){
                $this->Scan_copol_pol0->SetValue('ifatten',$_REQUEST['ifatten_copol_pol0']);
            }
            if (isset($_REQUEST['notes_copol_pol0'])){
                $this->Scan_copol_pol0->SetValue('notes',$_REQUEST['notes_copol_pol0']);
            }

        //XPOL, POL 0 ScanDetails
        $q_scans = "SELECT keyId FROM ScanDetails
                    WHERE fkScanSetDetails = ".$this->propertyVals['keyId']."
                    AND pol = 0 and copol = 0
                    AND SourcePosition < 3
                    AND fkFacility = $this->fc
                    LIMIT 1;";
        $r_scans = @mysql_query($q_scans,$this->dbconnection);
        $this->keyId_xpol_pol0_scan = @mysql_result($r_scans,0);
        $this->Scan_xpol_pol0 = new ScanDetails;
        //$this->Scan_copol_pol0->Initialize("ScanDetails",$this->keyId_copol_pol0_scan,"keyId",$this->dbconnection);
        $this->Scan_xpol_pol0->Initialize_ScanDetails($this->keyId_xpol_pol0_scan, $this->fc);

            if (isset($_REQUEST['ifatten_copol_pol0'])){
                $this->Scan_xpol_pol0->SetValue('ifatten',$_REQUEST['ifatten_xpol_pol0']);
            }
            if (isset($_REQUEST['notes_copol_pol0'])){
                $this->Scan_xpol_pol0->SetValue('notes',$_REQUEST['notes_xpol_pol0']);
            }
            //$this->Scan_xpol_pol0->Update();

        //COPOL, POL 1 ScanDetails
        $q_scans = "SELECT keyId FROM ScanDetails
                    WHERE fkScanSetDetails = ".$this->propertyVals['keyId']."
                    AND pol = 1 and copol = 1
                    AND SourcePosition < 3
                    AND fkFacility = $this->fc
                    LIMIT 1;";
        $r_scans = @mysql_query($q_scans,$this->dbconnection);
        $this->keyId_copol_pol1_scan = @mysql_result($r_scans,0);
        $this->Scan_copol_pol1 = new ScanDetails;
        $this->Scan_copol_pol1->Initialize_ScanDetails($this->keyId_copol_pol1_scan, $this->fc);
            if (isset($_REQUEST['ifatten_copol_pol1'])){
                $this->Scan_copol_pol1->SetValue('ifatten',$_REQUEST['ifatten_copol_pol1']);
            }
            if (isset($_REQUEST['notes_copol_pol1'])){
                $this->Scan_copol_pol1->SetValue('notes',$_REQUEST['notes_copol_pol1']);
            }
            //$this->Scan_copol_pol1->Update();


        //XPOL, POL 1 ScanDetails
        $q_scans = "SELECT keyId FROM ScanDetails
                    WHERE fkScanSetDetails = ".$this->propertyVals['keyId']."
                    AND pol = 1 and copol = 0
                    AND SourcePosition < 3
                    AND fkFacility = $this->fc
                    LIMIT 1;";
        $r_scans = @mysql_query($q_scans,$this->dbconnection);
        $this->keyId_xpol_pol1_scan = @mysql_result($r_scans,0);
        $this->Scan_xpol_pol1 = new ScanDetails;
        $this->Scan_xpol_pol1->Initialize_ScanDetails($this->keyId_xpol_pol1_scan, $this->fc);
            if (isset($_REQUEST['ifatten_copol_pol1'])){
                $this->Scan_xpol_pol1->SetValue('ifatten',$_REQUEST['ifatten_xpol_pol1']);
            }
            if (isset($_REQUEST['notes_copol_pol1'])){
                $this->Scan_xpol_pol1->SetValue('notes',$_REQUEST['notes_xpol_pol1']);
            }
            //$this->Scan_xpol_pol1->Update();

        //180 Scan ScanDetails
        $q_scans = "SELECT keyId FROM ScanDetails
                    WHERE fkScanSetDetails = ".$this->propertyVals['keyId']."
                    AND copol = 1
                    AND SourcePosition > 2
                    AND fkFacility = $this->fc
                    LIMIT 1;";
        $r_scans = @mysql_query($q_scans,$this->dbconnection);
        $this->keyId_180_scan = @mysql_result($r_scans,0);
        $this->Scan_180 = new ScanDetails;
        $this->Scan_180->Initialize_ScanDetails($this->keyId_180_scan, $this->fc);








    }

    public function Display_delete_form_SSD(){
        echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">
        <b><font size="+1">Are you sure you want to delete this record?</b></font>
        <br><input type="submit" name = "deleterecord_forsure" value="YES, DELETE RECORD"><br><br>
        <input type="hidden" name="id" value="' . $this->keyId . '" />
        </form>';

    }

    public function DisplayData_ScanSetDetails(){

        echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
        echo '<table align="left" cellspacing="1" cellpadding="2" width="25%" bgcolor="#000000" >';
        echo '<tr bgcolor="#ccffff">
                <td><b>Front End SN</b></td>
                <td><b>Band</b></td>
                <td><b>GHz</b></td>
                <td><b>Elevation</b></td>
              </tr>';


        echo '<tr bgcolor="#ffffff">
                    <td>
                        <input type="text" name="fkFrontEnd" size="5"
                        maxlength="200" value = "'.$this->GetValue('fkFrontEnd').'">
                    </td>
                    <td>
                        <input type="text" name="band" size="5"
                        maxlength="200" value = "'.$this->GetValue('band').'">
                    </td>
                    <td>
                        <input type="text" name="f" size="5"
                        maxlength="200" value = "'.$this->GetValue('f').'">
                    </td>
                    <td>
                        <input type="text" name="tilt" size="5"
                        maxlength="200" value = "'.$this->GetValue('tilt').'">
                    </td>
                </tr>';
            echo "</table>";

        echo "<br><br><br><br>";
        $this->DisplayPlots();


        $keyId = $this->GetValue('keyId');
        $tablename = $this->GetValue('tablename');

        echo "<br><br>";

        echo "<input type='hidden' name='keyId' value='$keyId'>";
        echo "<input type='hidden' name='tablename' value='$tablename'>";
        echo "<br><br><input type='submit' name = 'submitted' value='SAVE CHANGES'>";
        echo "<input type='submit' name = 'deleterecord' value='DELETE RECORD'>";
        echo "</form>";


        $this->Display_uploadform();
    }

    public function Display_uploadform(){
        echo '
        <p><div style="width:700px;height:80px; align = "left"></p>
        <!-- The data encoding type, enctype, MUST be specified as below -->
        <form enctype="multipart/form-data" action="' . $PHP_SELF . '" method="POST">
            <!-- MAX_FILE_SIZE must precede the file input field -->
            <!-- <input type="hidden" name="MAX_FILE_SIZE" value="30000" /> -->
            <!-- Name of input element determines name in $_FILES array -->
            <br>
            <font size="+1"><b><u>Upload Listings</u></b></font><br><br>

            <table align="left" cellspacing="1" cellpadding="3" width="300%" bgcolor="#000000" >
                <tr bgcolor="#ffffff" >
                    <td>
                        <b>Pol 0, Copol</b><br>
                        Nearfield: </b><input name="nf_copol_pol0_file" type="file" /><br>
                        Farfield: </b><input name="ff_copol_pol0_file" type="file" /><br>';

                        echo "IF Atten (dB):<input type='text' name='ifatten_copol_pol0' size='5'
                        maxlength='10' value = '".$this->Scan_copol_pol0->GetValue('ifatten')."'><br>";
                        echo "Notes:<textarea name='notes_copol_pol0' rows='3' cols='40'>"
                         .$this->Scan_copol_pol0->GetValue('notes')."</textarea>";

                    echo '

                    </td>
                    <td>
                        <b>Pol 0, Crosspol</b><br>
                        Nearfield: </b><input name="nf_xpol_pol0_file" type="file" /><br>
                        Farfield: </b><input name="ff_xpol_pol0_file" type="file" /><br>';

                        echo "IF Atten (dB):<input type='text' name='ifatten_xpol_pol0' size='5'
                        maxlength='10' value = '".$this->Scan_xpol_pol0->GetValue('ifatten')."'><br>";
                        echo "Notes:<textarea name='notes_xpol_pol0' rows='3' cols='40'>"
                         .$this->Scan_xpol_pol0->GetValue('notes')."</textarea>";

                    echo '
                    </td>
                </tr>

                <tr bgcolor="#ffffff">
                    <td>
                        <b>Pol 1, Copol</b><br>
                        Nearfield: </b><input name="nf_copol_pol1_file" type="file" /><br>
                        Farfield: </b><input name="ff_copol_pol1_file" type="file" /><br>';

                        echo "IF Atten (dB):<input type='text' name='ifatten_copol_pol1' size='5'
                        maxlength='10' value = '".$this->Scan_copol_pol1->GetValue('ifatten')."'><br>";
                        echo "Notes:<textarea name='notes_copol_pol1' rows='3' cols='40'>"
                         .$this->Scan_copol_pol1->GetValue('notes')."</textarea>";

                    echo '

                    </td>
                    <td>
                        <b>Pol 1, Crosspol</b><br>
                        Nearfield: </b><input name="nf_xpol_pol1_file" type="file" /><br>
                        Farfield: </b><input name="ff_xpol_pol1_file" type="file" /><br>';

                        echo "IF Atten (dB):<input type='text' name='ifatten_copol_pol1' size='5'
                        maxlength='10' value = '".$this->Scan_copol_pol1->GetValue('ifatten')."'><br>";
                        echo "Notes:<textarea name='notes_xpol_pol1' rows='3' cols='40'>"
                         .$this->Scan_xpol_pol1->GetValue('notes')."</textarea>";

                    echo '

                    </td>
                </tr>
            </table><br><br>

            <br><br><br><br><br><br><br><br><br><br>
            <input type="submit" name= "submit_datafile" value="Submit" />
        </form>
        </div>';
    }



    public function NewRecord_ScanSetDetails(){
        parent::NewRecord("ScanSetDetails");
        for ($i=0;$i<4;$i++){
            $qNew = "INSERT INTO ScanDetails(fkScanSetDetails, scan_type)
                     VALUES(".$this->propertyVals['keyId'].",$i);";
            $rNew = @mysql_query($qNew,$this->dbconnection);
        }

        //@mysql_close($this->dbconnection);
    }

    public function DisplayPlots(){

        //Pol 0
        echo '<table align="left" cellspacing="1" cellpadding="2" width="100%" bgcolor="#000000" >';
        echo '<tr bgcolor="#000000">
                <td colspan="4"><font color="#ffffff" size="+1"><b>Pol 0</font></b></td>
              </tr>';
        echo '<tr bgcolor="#66ff66">
                <td colspan="2"><b>Nearfield Copol</b></td>
                <td colspan="2"><b>Farfield Copol</b></td>
              </tr>';


        echo "<tr bgcolor='#ffffff'>
                  <td><img src='".$this->Scan_copol_pol0->GetValue('nf_amp_image')."'></td>
                  <td><img src='".$this->Scan_copol_pol0->GetValue('nf_phase_image')."'></td>
                  <td><img src='".$this->Scan_copol_pol0->GetValue('ff_amp_image')."'></td>
                  <td><img src='".$this->Scan_copol_pol0->GetValue('ff_phase_image')."'></td>

              </tr>";

        echo '<tr bgcolor="#ffffcc">
                <td colspan="4" ><b>IF Attenuation (dB): '.$this->Scan_copol_pol0->PropertyVals['ifatten_copol_pol0'].'<br>
                                    Notes: '.$this->Scan_copol_pol0->PropertyVals['notes_copol_pol0'].'</b></td>
              </tr>';

        echo '<tr bgcolor="#ffff00">
                <td colspan="2"><b>Nearfield Crosspol</b></td>
                <td colspan="2"><b>Farfield Crosspol</b></td>
              </tr>';

        echo "<tr  bgcolor='#ffffff'>
                  <td><img src='".$this->Scan_xpol_pol0->GetValue('nf_amp_image')."'></td>
                  <td><img src='".$this->Scan_xpol_pol0->GetValue('nf_phase_image')."'></td>
                  <td><img src='".$this->Scan_xpol_pol0->GetValue('ff_amp_image')."'></td>
                  <td><img src='".$this->Scan_xpol_pol0->GetValue('ff_phase_image')."'></td>
              </tr>";

        echo '<tr bgcolor="#ffffcc">
                <td colspan="4" ><b>IF Attenuation (dB): '.$this->Scan_xpol_pol0->PropertyVals['ifatten_xpol_pol0'].'<br>
                                    Notes: '.$this->Scan_xpol_pol0->PropertyVals['notes_xpol_pol0'].'</b></td>
              </tr>';


        echo "</table>";



        echo "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>";
        echo "<br>";

        //Pol 1
        echo '<table align="left" cellspacing="1" cellpadding="2" width="100%" bgcolor="#000000" >';
        echo '<tr bgcolor="#000000">
                <td colspan="4"><font color="#ffffff" size="+1"><b>Pol 1</font></b></td>
              </tr>';
        echo '<tr bgcolor="#66ff66">
                <td colspan="2"><b>Nearfield Copol</b></td>
                <td colspan="2"><b>Farfield Copol</b></td>
              </tr>';

        echo "<tr bgcolor='#ffffff'>
                  <td><img src='".$this->Scan_copol_pol1->GetValue('nf_amp_image')."'></td>
                  <td><img src='".$this->Scan_copol_pol1->GetValue('nf_phase_image')."'></td>
                  <td><img src='".$this->Scan_copol_pol1->GetValue('ff_amp_image')."'></td>
                  <td><img src='".$this->Scan_copol_pol1->GetValue('ff_phase_image')."'></td>
              </tr>";

        echo '<tr bgcolor="#ffffcc">
                <td colspan="4" ><b>IF Attenuation (dB): '.$this->Scan_copol_pol1->PropertyVals['ifatten_copol_pol1'].'<br>
                                    Notes: '.$this->Scan_copol_pol1->PropertyVals['notes_copol_pol1'].'</b></td>
              </tr>';


        echo '<tr bgcolor="#ffff00">
                <td colspan="2"><b>Nearfield Crosspol</b></td>
                <td colspan="2"><b>Farfield Crosspol</b></td>
              </tr>';

        echo "<tr  bgcolor='#ffffff'>
                  <td><img src='".$this->Scan_xpol_pol1->GetValue('nf_amp_image')."'></td>
                  <td><img src='".$this->Scan_xpol_pol1->GetValue('nf_phase_image')."'></td>
                  <td><img src='".$this->Scan_xpol_pol1->GetValue('ff_amp_image')."'></td>
                  <td><img src='".$this->Scan_xpol_pol1->GetValue('ff_phase_image')."'></td>
              </tr>";

        echo '<tr bgcolor="#ffffcc">
                <td colspan="4" ><b>IF Attenuation (dB): '.$this->Scan_xpol_pol1->PropertyVals['ifatten_xpol_pol1'].'<br>
                                    Notes: '.$this->Scan_xpol_pol1->PropertyVals['notes_xpol_pol1'].'</b></td>
              </tr>';

        echo "</table>";

    }

    public function Display_add_form(){
        echo "<font size='+2'><u>Scan Set</u></font>";
        echo "<br><i>Front End $this->fkFrontEnd</i>";
        echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
            echo "<div style ='width:100%;height:30%'>";
            echo "<div align='right' style ='width:30%;height:30%'>";

            echo "
            <br>Band <input type='text' name='band' size='5' maxlength='5' value = '$this->band'/>
            <br>Frequency (GHz) <input type='text' name='f' size='5' maxlength='5' value = '$this->f'/>
            <br>Tilt Angle (deg) <input type='text' name='tilt' size='5' maxlength='5' value='$this->tilt'/>

            <br><textarea rows='5' cols='40' name='notes' size='60' maxlength='500'/>$this->notes</textarea><br>Notes
            ";

            echo "<input type='hidden' name='keyId' value='$this->keyId'>";
            echo "<input type='hidden' name='fkFrontEnd' value='$this->fkFrontEnd'>";

            echo "<br><br><input type='submit' name = 'submitted' value='SAVE CHANGES'>";
            echo "<br><br><input type='submit' name = 'deleterecord' value='DELETE RECORD'>";
            echo "</div></div>";
        echo "</form>";
    }

    public function GenerateAllPlots(){
        $this->Scan_copol_pol0->GeneratePlot_NF();
        $this->Scan_copol_pol0->GeneratePlot_FF();

        $this->Scan_copol_pol1->GeneratePlot_NF();
        $this->Scan_copol_pol1->GeneratePlot_FF();

        $this->Scan_xpol_pol0->GeneratePlot_NF();
        $this->Scan_xpol_pol0->GeneratePlot_FF();

        $this->Scan_xpol_pol1->GeneratePlot_NF();
        $this->Scan_xpol_pol1->GeneratePlot_FF();
    }


}
?>