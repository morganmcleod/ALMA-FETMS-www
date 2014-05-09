<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.logger.php');
require_once($site_classes . '/class.sln.php');
require_once($site_FEConfig . '/HelperFunctions.php');

class FEComponent extends GenericTable{
    var $ComponentType;    //object representing record of ComponentTypes table
    var $FESN;             //SN of the Front End
    var $FEid;             //keyId of the Front End
    var $FEConfig;         //Latest configuration of the Front End
    var $sln;              //Object representing record in FEStatusLocationAndNotes table
    var $FEfc;             //Facility code of front end
    var $FE_ConfigLink;    //Object representing record in FE_ConfigLink table
    var $MaxConfig;        //Max keyId value in FE_Components with the same SN and Band (if band is not NA)
    var $IsDocument;       //If 1, this is a document
    var $JSONstring;       //JSON string with basic information about the component

    public function GetJSONstring(){
        $jstring  = "{'id':'"   . $this->keyId . "'";
        $jstring .= ",'sn':'"   . $this->GetValue('SN') . "'";
        $jstring .= ",'band':'" . $this->GetValue('Band') . "'}";
        $this->JSONstring = $jstring;
    }

    public function Initialize_FEComponent($in_keyId, $in_fc){
        $this->IsDocument = 0;
        $this->keyId = $in_keyId;
        parent::Initialize('FE_Components',$this->keyId,'keyId',$in_fc,'keyFacility');

        $q = "SELECT keyId FROM ComponentTypes
              WHERE keyId = " . $this->GetValue('fkFE_ComponentType') . ";";
        $r = @mysql_query($q,$this->dbconnection);
        $this->ComponentType = new GenericTable();
        $CompType = @mysql_result($r,0,0);
        $this->ComponentType->Initialize('ComponentTypes', $CompType,'keyId');

        $this->IsDocument = 0;
        switch($CompType){
            case(217):
                $this->IsDocument = 1;
                break;
            case(218):
                $this->IsDocument = 1;
                break;
            case(219):
                $this->IsDocument = 1;
                break;
            case(220):
                $this->IsDocument = 1;
                break;
            case(222):
                $this->IsDocument = 1;
                break;
        }

        //Find which Front End this component is in (if any)
        $q = "select Front_Ends.SN, FE_Config.keyFEConfig,Front_Ends.keyFrontEnds, Front_Ends.keyFacility,
            FE_ConfigLink.keyId
            FROM Front_Ends, FE_ConfigLink, FE_Config
            WHERE FE_ConfigLink.fkFE_Components = $this->keyId
            AND FE_ConfigLink.fkFE_Config = FE_Config.keyFEConfig
            AND FE_Config.fkFront_Ends = Front_Ends.keyFrontEnds
            GROUP BY FE_ConfigLink.keyId DESC LIMIT 1;";

        $r = @mysql_query($q,$this->dbconnection);
        if (@mysql_numrows($r) > 0){
            $r = @mysql_query($q,$this->dbconnection);
            $this->FESN              = @mysql_result($r,0,0);
            $this->FEConfig       = @mysql_result($r,0,1);
            $this->FEid           = @mysql_result($r,0,2);
            $this->FEfc           = @mysql_result($r,0,3);
            $this->FE_ConfigLink = new GenericTable();
            $this->FE_ConfigLink->Initialize('FE_ConfigLink',@mysql_result($r,0,4),'keyId',$this->GetValue('keyFacility'),'fkFE_ComponentFacility');
        }

        //Get sln
        $qsln = "SELECT MAX(keyId) FROM FE_StatusLocationAndNotes
                 WHERE fkFEComponents = $this->keyId
                 AND keyFacility = ". $this->GetValue('keyFacility') .";";

        $rsln = @mysql_query($qsln,$this->dbconnection);

        $slnid = @mysql_result($rsln,0,0);


        //echo "id, fc= $slnid, $slnfc<br><br>";
        if ($slnid != ''){
            //$this->sln = new GenericTable();
            //$this->sln->Initialize('FE_StatusLocationAndNotes',$slnid,'keyId',$in_fc,'keyFacility');

            $this->sln = new SLN();
            $this->sln->Initialize_SLN($slnid,$in_fc);
        }
        $this->GetJSONstring();

    }

    public function NewRecord_FEComponent($in_fc){
            parent::NewRecord('FE_Components','keyId',$in_fc,'keyFacility');
            parent::Update();
    }

    public function DisplayTable_ComponentInformation(){
        $isDoc = 0;

        $tableheader = $this->ComponentType->GetValue('Description');

        if ($this->GetValue('Band') > 0){
            $tableheader .= " Band " . $this->GetValue('Band');
        }
        if (strlen($this->GetValue('SN')) > 0){
            $tableheader .= " SN " . $this->GetValue('SN');
        }

        echo "
        <div style='width:350px'>
                <table id = 'table5'>
                    <tr class='alt'><th colspan = '2'><font size='+2'>" . $this->ComponentType->GetValue('Description') . "<font></th></tr>";

        echo "</tr>";

                    if ($this->GetValue('Band') > 0){
                    echo "<tr><th>Band</th><td>".$this->GetValue('Band')."</td></tr>";
                    }
                    if ($this->GetValue('SN') != ''){
                    echo "
                    <tr><th width = '10px' align = 'right'>SN</th>
                        <td width='20px'>".$this->GetValue('SN')."
                        </td>
                    </tr>";
                    }
                    echo "
                    <tr><th width = '10px' align = 'right'>In Front End</th>
                    <td width='20px'>".$this->FESN."</td></tr>
                    <tr><th>TS</th><td>".$this->GetValue('TS')."</td>
                    </tr>";

                    if ($this->IsDocument != 1){
                    echo "<tr><th>Config#</th><td>".$this->keyId."</td></tr>";
                        echo "<tr><th>ESN1</th><td>".$this->GetValue('ESN1')."</td></tr>";
                        echo "<tr><th>ESN2</th><td>".$this->GetValue('ESN2')."</td></tr>";

                    }

                    if ($this->IsDocument != 1){
                        $link2 = $this->GetValue('Link2');
                        $Link2string = "";
                        if (strlen($link2) > 5){
                            $Link2string = "Link";
                        }
                            echo"
                            <tr><th>Link1 (CIDL)</th><td><a href='".FixHyperlink($link2)."' target = 'blank'>$Link2string</td></tr>";

                        $link1 = $this->GetValue('Link1');
                        $Link1string = "";
                        if (strlen($link1) > 5){
                            $Link1string = "Link";
                        }
                            echo"
                            <tr><th>Link2 (SICL)</th><td><a href='".FixHyperlink($link1)."' target = 'blank'>$Link1string</td></tr>";
                    }

                    if ($this->IsDocument == 1){
                        $link2 = $this->GetValue('Link2');
                        $Link2string = "";
                        if (strlen($link2) > 5){
                            $Link2string = "Link";
                        }
                            echo"
                            <tr><th>Link</th><td><a href='".FixHyperlink($link2)."' target = 'blank'>$Link2string</td></tr>";

                    }






                    echo"<tr><th>Description</th><td>".$this->GetValue('Description')."</td></tr>";

                    if ($this->IsDocument != 1){
                        $Qty = 1;
                        if (isset($this->FE_ConfigLink) && $this->FE_ConfigLink->keyId > 0){
                            $Qty = $this->FE_ConfigLink->GetValue('Quantity');
                        }
                        echo"<tr><th>Quantity</th><td>$Qty</td></tr>";
                    }

                    if ($this->GetValue('DocumentTitle') != ''){
                        echo "<tr><th>Title</th><td>".$this->GetValue('DocumentTitle')."</td></tr>";
                    }
                    if ($this->GetValue('Production_Status') != ''){
                        echo "<tr><th>Status</th><td>".$this->GetValue('Production_Status')."</td></tr>";
                    }
                    echo"
                </table>";



                    echo "</div>
                </div>";
    }

    public function DisplayTable_TestData(){
        require_once(site_get_classes() . '/class.testdata_header.php');

        $databutton = "";
        $note = "";
        $testdatapage = "testdata/testdata.php";


        if ($this->GetValue('fkFE_ComponentType') == 6){
            $testdatapage = "testdata/testdata_cryostat.php";
            $url = "../cryostat/cryostat.php?keyId=$this->keyId&fc=" . $this->GetValue('keyFacility');
        }

        echo "
        <div style='width:1000px'>
                <font size='+1'>
                <table id = 'table1'>
                    <tr class='alt'><th colspan = '8'>TEST DATA $note</th></tr>
                    <tr>
                        <th>Component<br>Config#</th>
                        <th>Data Status</th>
                        <th>Description</th>
                        <th>Notes</th>
                        <th>TS</th>
                    </tr>
                    <tr>";


        $SN = "%";
        if ($this->GetValue('SN') != ''){
            $SN = $this->GetValue('SN');
        }
        $Band = "%";
        if ($this->GetValue('Band') != ''){
            $Band = $this->GetValue('Band');
        }
        $q = "SELECT TestData_header.keyId, FE_Components.keyId as COMPID
            FROM TestData_header, TestData_Types, FE_Components
            WHERE TestData_header.fkFE_Components = FE_Components.keyId
            AND FE_Components.SN LIKE '$SN'
            AND FE_Components.Band LIKE '$Band'
            AND TestData_header.fkTestData_Type = TestData_Types.keyId
            AND FE_Components.fkFE_ComponentType = ".$this->GetValue('fkFE_ComponentType')."
            AND TestData_header.fkFE_Config < 1
            ORDER BY COMPID DESC,
            TestData_Types.Description ASC;";


        $r = @mysql_query($q,$this->dbconnection);
        $trclass = "";
        while ($row = @mysql_fetch_array($r)){
            $trclass = ($trclass=="" ? 'class="alt"' : "");

            $tdh = new TestData_header();
            $tdh->Initialize_TestData_header($row[0],$this->GetValue('keyFacility'));
            echo "<tr $trclass>";
            $url = "ShowComponents.php?conf=$row[1]&fc=" . $this->GetValue('keyFacility');
            echo "<td width = '20px'><a href='$url'>$row[1]</a></td>";
            echo "<td width = '60px'>" . $tdh->DataStatus . "</td>";
            echo "<td width = '140px'><a href='$testdatapage?keyheader=$tdh->keyId&fc=" . $this->GetValue('keyFacility') . "'>" . $tdh->TestDataType . "</a></td>";
            echo "<td width = '150px'>" . $tdh->GetValue('Notes') . "</td>";
            echo "<td width = '100px'>" . $tdh->GetValue('TS') . "</td>";
            echo "</tr>";
            unset($tdh);
        }
        echo "</font></table>";

    }

    public function Display_UpdateConfigForm_CCA(){

        echo "<tr class='alt4' back><td colspan='2'>Update configuration from FrontEndControlDLL.ini</td></tr>";


              echo '<tr class="alt"><td colspan="2">';

              echo '
                <!-- The data encoding type, enctype, MUST be specified as below -->
                <form enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '" method="POST">
                <!-- MAX_FILE_SIZE must precede the file input field -->
                <!-- <input type="hidden" name="MAX_FILE_SIZE" value="32000000000" /> -->
                <!-- Name of input element determines name in $_FILES array -->';

              echo '<input name="ccaconfig_file" type="file" size = "100" />';
              echo '&nbsp &nbsp &nbsp &nbsp<input type="submit" class = "submit" name= "submitted_ccaconfig" value="Submit" />';
              echo "<input type='hidden' name='fc' value='".$this->GetValue('keyFacility')."' />";
              echo "<input type='hidden' name='conf' value='$this->keyId' /><br>";

              //Options for what to update
              echo "<input type='checkbox' name='cca_updatemixers' value='cca_updatemixers' /> Update Mixers &nbsp &nbsp &nbsp &nbsp";
                echo "<input type='checkbox' name='cca_updatepreamps' value='cca_updatepreamps' />  Update Preamps</td></tr>";
              //echo "<input type='checkbox' name='cca_updatetemps' value='cca_updatetemps' /> Update Temp Sensors<br><br>";


              echo "</td></tr>";


              echo"
        </form>";

    }

    public function Display_Table_PreviousConfigurations(){
        $Band = "%";
        if ($this->GetValue('Band') != ''){
            $Band = $this->GetValue('Band');
        }

        $q = "SELECT keyId, keyFacility FROM FE_Components
              WHERE SN = " . $this->GetValue('SN') . "
              AND Band LIKE '$Band'
              AND fkFE_ComponentType = ".$this->GetValue('fkFE_ComponentType')."
              ORDER BY keyId DESC;";
        $r = @mysql_query($q,$this->dbconnection);
        if (@mysql_num_rows($r) > 1){
            $r = @mysql_query($q,$this->dbconnection);

            echo "<div style = 'width:700px'>";

            echo "<table id='table1'>";
            echo "<tr class='alt'><th colspan='5'>Previous Configurations</th></tr>";
            echo "<tr>
                <th style='width:60px'>Config</th>
                <th>ESN1</th>
                <th>ESN2</th>
                <th style='width:60px'>FE Config</th>
                <th>TS</th>";

            while ($row = @mysql_fetch_array($r)){
                $c_old = new FEComponent();
                $c_old->Initialize_FEComponent($row['keyId'],$row['keyFacility']);

                $link_component = "ShowComponents.php?conf=$c_old->keyId&fc=" . $row['keyFacility'];
                $link_fe = "ShowFEConfig.php?key=$c_old->FEConfig&fc=$c_old->FEfc";

                echo "<tr >";
                echo "<td><a href='$link_component'>".$c_old->keyId."</a></td>";
                echo "<td>".$c_old->GetValue('ESN1')."</td>";
                echo "<td>".$c_old->GetValue('ESN2')."</td>";
                echo "<td><a href='$link_fe'>".$c_old->FEConfig ."</a></td>";
                echo "<td>".$c_old->GetValue('TS')  ."</td>";

                echo "</tr>";
                unset($c_old);
            }
        }
        echo "</table></div>";
    }

    public function Display_Table_ComponentHistory(){
        $Band = "%";
        if ($this->GetValue('Band') != ''){
            $Band = $this->GetValue('Band');
        }
        $SN = "%";
        if ($this->GetValue('SN') != ''){
            $SN = $this->GetValue('SN');
        }
        $DocumentTitle = "%";
        if ($this->GetValue('DocumentTitle') != ''){
            $DocumentTitle  = $this->GetValue('DocumentTitle');
        }

        $IsDoc = 0;
        switch($this->GetValue('fkFE_ComponentType')){
            case 217:
                $IsDoc = 1;
            case 218:
                $IsDoc = 1;
            case 219:
                $IsDoc = 1;
            case 220:
                $IsDoc = 1;
        }

        if ($IsDoc == 1){
        $q = "SELECT FE_StatusLocationAndNotes.keyId AS SLNID,
                FE_StatusLocationAndNotes.fkFEComponents AS COMPID,
                FE_StatusLocationAndNotes.keyFacility AS SLNFC, FE_Components.keyFacility AS COMPFC,
                FE_ConfigLink.fkFE_Config AS FECFG
                FROM FE_StatusLocationAndNotes, FE_Components, FE_ConfigLink
                WHERE FE_StatusLocationAndNotes.fkFEComponents = FE_Components.keyId
                AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
                AND FE_ConfigLink.fkFE_ComponentFacility = FE_Components.keyFacility

                AND FE_Components.DocumentTitle LIKE '$DocumentTitle'
                GROUP BY FE_StatusLocationAndNotes.keyId DESC;";
        }
        if ($IsDoc != 1){

        $q = "SELECT FE_StatusLocationAndNotes.keyId AS SLNID,
                FE_StatusLocationAndNotes.fkFEComponents AS COMPID,
                FE_StatusLocationAndNotes.keyFacility AS SLNFC, FE_Components.keyFacility AS COMPFC,
                FE_ConfigLink.fkFE_Config AS FECFG
                FROM FE_StatusLocationAndNotes, FE_Components, FE_ConfigLink
                WHERE FE_StatusLocationAndNotes.fkFEComponents = FE_Components.keyId
                AND FE_Components.fkFE_ComponentType =  ".$this->GetValue('fkFE_ComponentType')."
                AND FE_Components.SN LIKE '$SN'
                AND FE_Components.Band LIKE '".$this->GetValue('Band')."'
                GROUP BY FE_StatusLocationAndNotes.keyId DESC;";
        }


        echo $q . "<br>";


            $r = @mysql_query($q,$this->dbconnection);

            echo "<div style = 'width:1100px'>";

            echo "<table id='table1'>";
            echo "<tr class='alt'><th colspan='7'>COMPONENT HISTORY</th></tr>";
            echo "<tr>

                <th style='width:120px'>Date</th>
                <th style='width:280px'>Location</th>
                <th style='width:120px'>Status</th>
                <th style='width:30px'>Who</th>
                <th style='width:50px'>Config#</th>
                <th style='width:30px'>Link</th>
                <th style='width:300px'>Notes</th>
                ";

            while ($row = @mysql_fetch_array($r)){
                $SLNID  = $row['SLNID'];
                $COMPID = $row['COMPID'];
                $SLNFC  = $row['SLNFC'];
                $COMPFC = $row['COMPFC'];

                $c = new FEComponent();
                $c->Initialize_FEComponent($COMPID,$COMPFC);

                $sln = new SLN();
                $sln->Initialize_SLN($SLNID,$SLNFC);

                $link_component = "ShowComponents.php?conf=$c->keyId&fc=" . $row['COMPFC'];
                //$link_fe = "ShowFEConfig.php?key=$c_old->FEConfig&fc=$c_old->FEfc";

                echo "<tr >";

                echo "<td>".$sln->GetValue('TS')."</td>";
                echo "<td>".$sln->location."</td>";
                echo "<td>".$sln->status."</td>";
                echo "<td>".$sln->GetValue('Updated_By')."</td>";
                echo "<td><a href='$link_component'>".$c->keyId."</a></td>";

                $linktext = '';
                if (strlen($sln->GetValue('lnk_Data')) > 7){
                    $link = FixHyperlink($sln->GetValue('lnk_Data'));
                    $linktext = "Link";
                }
                echo "<td><a href='$link'>$linktext</td>";
                echo "<td>".$sln->GetValue('Notes')."</td>";

                echo "</tr>";
                unset($c);

            }
            echo "</table></div>";
        //}

    }

    public function ComponentHistory_JSON(){

        $Band = "%";
        if ($this->GetValue('Band') != ''){
            $Band = $this->GetValue('Band');
        }
        $SN = "%";
        if ($this->GetValue('SN') != ''){
            $SN = $this->GetValue('SN');
        }
        $DocumentTitle = "%";
        if ($this->GetValue('DocumentTitle') != ''){
            $DocumentTitle  = $this->GetValue('DocumentTitle');
        }

        $IsDoc = 0;
        switch($this->GetValue('fkFE_ComponentType')){
            case 217:
                $IsDoc = 1;
            case 218:
                $IsDoc = 1;
            case 219:
                $IsDoc = 1;
            case 220:
                $IsDoc = 1;
        }

        if ($IsDoc == 1){
        $q = "SELECT FE_StatusLocationAndNotes.keyId AS SLNID, FE_StatusLocationAndNotes.TS AS SLNTS,
                FE_StatusLocationAndNotes.fkFEComponents AS COMPID,
                FE_StatusLocationAndNotes.keyFacility AS SLNFC, FE_Components.keyFacility AS COMPFC,
                FE_ConfigLink.fkFE_Config AS FECFG
                FROM FE_StatusLocationAndNotes, FE_Components, FE_ConfigLink
                WHERE FE_StatusLocationAndNotes.fkFEComponents = FE_Components.keyId
                AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
                AND FE_ConfigLink.fkFE_ComponentFacility = FE_Components.keyFacility

                AND FE_Components.DocumentTitle LIKE '$DocumentTitle'
                GROUP BY FE_StatusLocationAndNotes.keyId DESC;";
        }
        if ($IsDoc != 1){

        $q = "SELECT FE_StatusLocationAndNotes.keyId AS SLNID, FE_StatusLocationAndNotes.TS AS SLNTS,
                FE_StatusLocationAndNotes.fkFEComponents AS COMPID,
                FE_StatusLocationAndNotes.keyFacility AS SLNFC, FE_Components.keyFacility AS COMPFC,
                FE_ConfigLink.fkFE_Config AS FECFG
                FROM FE_StatusLocationAndNotes, FE_Components, FE_ConfigLink
                WHERE FE_StatusLocationAndNotes.fkFEComponents = FE_Components.keyId
                AND FE_Components.fkFE_ComponentType =  ".$this->GetValue('fkFE_ComponentType')."
                AND FE_Components.SN LIKE '$SN'
                AND FE_Components.Band LIKE '".$this->GetValue('Band')."'
                GROUP BY FE_StatusLocationAndNotes.keyId DESC;";
        }

        $r = @mysql_query($q,$this->dbconnection);

            $outstring = "[";
            $rowcount = 0;
            while ($row = @mysql_fetch_array($r)){
                $SLNID  = $row['SLNID'];
                $COMPID = $row['COMPID'];
                $SLNFC  = $row['SLNFC'];
                $COMPFC = $row['COMPFC'];

                $c = new FEComponent();
                $c->Initialize_FEComponent($COMPID,$COMPFC);

                $sln = new SLN();
                $sln->Initialize_SLN($SLNID,$SLNFC);

                if (strlen($sln->GetValue('lnk_Data')) > 7){
                    $link = FixHyperlink($sln->GetValue('lnk_Data'));
                    $linktext = "Link";
                }

                if ($rowcount == 0 ){
                    $outstring .= "{'TS':'".$row['SLNTS']."',";
                }
                if ($rowcount > 0 ){
                    $outstring .= ",{'TS':'".$row['SLNTS']."',";
                }

                if ($sln->keyId > 0){
                    $outstring .= "'Link':'".    FixHyperLink($sln->GetValue('lnk_Data'))."',";
                    $outstring .= "'Config':'".  $COMPID."',";
                    $outstring .= "'Who':'".     $sln->GetValue('Updated_By')."',";
                    $outstring .= "'Location':'".$sln->location."',";
                    $outstring .= "'Status':'".     $sln->status."',";
                }
                if ($sln->keyId < 1){
                    $outstring .= "'Link':'',";
                    $outstring .= "'Config':'',";
                    $outstring .= "'Who':'',";
                    $outstring .= "'Location':'',";
                    $outstring .= "'Status':'',";
                }

                $notes = @mysql_real_escape_string($sln->GetValue('Notes'));
                $outstring .= "'Notes':'".   str_replace('"', "'", $notes)."'}";


                unset($c);

                $rowcount += 1;
            }

            $outstring .= "]";

            echo $outstring;


    }

    public function Display_ALLUpdateConfigForm_CCA(){
        require_once(site_get_classes() . '/class.wca.php');
        $cca = new CCA();
        $cca->Initialize_CCA($this->keyId,$this->GetValue('keyFacility'));
        echo "<div style='width:850px'>";
        echo "<table id = 'table8'>";

        echo "<tr><td>";


        $cca->Display_uploadform_AnyFile($cca->keyId, $cca->GetValue('keyFacility'));
        echo "</td></tr>";

        echo "</table></div>";
        unset($cca);
    }


    public function GetFEConfig(){
        $q = "SELECT MAX(fkFE_Config) FROM FE_ConfigLink
              WHERE fkFE_Components = $this->keyId;";
        $r = @mysql_query($q,$this->dbconnection);
        $this->FEConfig = @mysql_result($r,0,0);
    }


    public function GetMaxConfig(){
        if ($this->GetValue('SN') == ''){
            $this->MaxConfig = 0;
        }
        if ($this->GetValue('SN') != ''){
            $band = '%';
            if ($this->GetValue('Band') > 0){
                $band = $this->GetValue('Band');
            }

            $qcfg = "SELECT MAX(keyId) FROM FE_Components
            WHERE SN = " . $this->GetValue('SN') . " AND Band LIKE '$band'
            AND fkFE_ComponentType = " . $this->GetValue('fkFE_ComponentType') . "
            and keyFacility = " . $this->GetValue('keyFacility') .";";

            $rcfg = @mysql_query($qcfg,$this->dbconnection);
            $this->MaxConfig = @mysql_result($rcfg,0,0);
        }
    }
}
?>
