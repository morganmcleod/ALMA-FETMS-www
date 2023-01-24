<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.logger.php');
require_once($site_classes . '/class.sln.php');
require_once($site_classes . '/class.testdata_table.php');
require_once($site_FEConfig . '/HelperFunctions.php');

class FEComponent extends GenericTable {
    public $ComponentType;    //object representing record of ComponentTypes table
    public $FESN;             //SN of the Front End
    public $FEid;             //keyId of the Front End
    public $FEConfig;         //Latest configuration of the Front End
    public $sln;              //Object representing record in FEStatusLocationAndNotes table
    public $FEfc;             //Facility code of front end
    public $FE_ConfigLink;    //Object representing record in FE_ConfigLink table
    public $MaxConfig;        //Max keyId value in FE_Components with the same SN and Band (if band is not NA)
    public $JSONstring;       //JSON string with basic information about the component

    // FE_Components columns
    public $keyFacility;
    public $keyId;
    public $fkFE_ComponentType;
    public $SN;
    public $ESN1;
    public $ESN2;
    public $Band;
    public $Docs;
    public $Link1;
    public $Link2;
    public $Production_Status;
    public $Notes;
    public $Description;
    public $DocumentTitle;
    public $TS;

    public function __construct($tableName, $inKeyId = "", $inKeyIdName, $inFc = '40', $inFcKeyName = 'none') {
        $this->keyId = $inKeyId;
        if ($tableName) {
            parent::__construct($tableName, $this->keyId, $inKeyIdName, $inFc, 'keyFacility');
        } else {
            parent::__construct('FE_Components', $this->keyId, 'keyId', $inFc, 'keyFacility');
        }

        $this->ComponentType = $this->getComponentDescription();


        //Find which Front End this component is in (if any)
        $q = "SELECT Front_Ends.SN,
                     FE_Config.keyFEConfig,
                     Front_Ends.keyFrontEnds,
                     Front_Ends.keyFacility,
                     FE_ConfigLink.keyId
              FROM Front_Ends, FE_ConfigLink, FE_Config
              WHERE FE_ConfigLink.fkFE_Components = {$this->keyId}
              AND FE_ConfigLink.fkFE_Config = FE_Config.keyFEConfig
              AND FE_Config.fkFront_Ends = Front_Ends.keyFrontEnds
              GROUP BY FE_ConfigLink.keyId ORDER BY FE_ConfigLink.keyId DESC LIMIT 1;";

        $r = mysqli_query($this->dbConnection, $q);
        if ($r && mysqli_num_rows($r) > 0) {
            $this->FESN           = ADAPT_mysqli_result($r, 0, 0);
            $this->FEConfig       = ADAPT_mysqli_result($r, 0, 1);
            $this->FEid           = ADAPT_mysqli_result($r, 0, 2);
            $this->FEfc           = ADAPT_mysqli_result($r, 0, 3);
            $this->FE_ConfigLink = new GenericTable('FE_ConfigLink', ADAPT_mysqli_result($r, 0, 4), 'keyId', $inFc, 'fkFE_ComponentFacility');
        }

        //Get sln
        $qsln = "SELECT MAX(keyId) FROM FE_StatusLocationAndNotes
                 WHERE fkFEComponents = $this->keyId
                 AND keyFacility = " . $inFc . ";";

        $rsln = mysqli_query($this->dbConnection, $qsln);
        //         var_dump($qsln);
        $slnid = ADAPT_mysqli_result($rsln, 0, 0);

        if ($slnid != '') $this->sln = new SLN($slnid, $inFc);
        $this->GetJSONstring();
    }

    public function getComponentDescription() {
        $this->dbConnection = site_getDbConnection();
        $q = "SELECT Description FROM ComponentTypes
              WHERE keyId={$this->fkFE_ComponentType};";
        $r = mysqli_query($this->dbConnection, $q);
        return ADAPT_mysqli_result($r, 0);
    }

    public function GetJSONstring() {
        $jstring  = "{'id':'"   . $this->keyId . "'";
        $jstring .= ",'sn':'"   . $this->SN . "'";
        $jstring .= ",'band':'" . $this->Band . "'}";
        $this->JSONstring = $jstring;
    }

    public function NewRecord_FEComponent($inFc) {
        parent::NewRecord('FE_Components', 'keyId', $inFc, 'keyFacility');
        parent::Update();
    }

    public function DisplayTable_ComponentInformation() {
        $tableheader = $this->ComponentType;

        if ($this->Band > 0) {
            $tableheader .= " Band " . $this->Band;
        }
        if (strlen($this->SN) > 0) {
            $tableheader .= " SN " . $this->SN;
        }

        echo "<div style='width:350px'>
                <table id = 'table5'>
                   <tr class='alt'><th colspan = '2'><font size='+2'>" . $this->ComponentType . "<font></th></tr>";
        echo "</tr>";

        if ($this->Band > 0) {
            echo "<tr><th>Band</th><td>" . $this->Band . "</td></tr>";
        }
        if ($this->SN != '') {
            echo "<tr><th width = '10px' align = 'right'>SN</th>
                <td width='20px'>" . $this->SN . "
                </td>
            </tr>";
        }
        echo "<tr><th width = '10px' align = 'right'>In Front End</th>
            <td width='20px'>" . $this->FESN . "</td></tr>
            <tr><th>TS</th><td>" . $this->TS . "</td>
            </tr>";

        echo "<tr><th>Config#</th><td>" . $this->keyId . "</td></tr>";
        echo "<tr><th>ESN1</th><td>" . $this->ESN1 . "</td></tr>";
        echo "<tr><th>ESN2</th><td>" . $this->ESN2 . "</td></tr>";

        $link1 = $this->Link1;
        $link2 = $this->Link2;

        $link1string = "";
        if (strlen($link1) > 5) {
            $link1string = "Link1";
        }
        echo "<tr><th>Link1 (CIDL)</th><td><a href='" . FixHyperlink($link1) . "' target = 'blank'>$link1string</td></tr>";

        $link2string = "";
        if (strlen($link2) > 5) {
            $link2string = "Link2";
        }
        echo "<tr><th>Link2 (SICL)</th><td><a href='" . FixHyperlink($link2) . "' target = 'blank'>$link2string</td></tr>";

        echo "<tr><th>Description</th><td>" . $this->Description . "</td></tr>";

        $Qty = 1;
        if (isset($this->FE_ConfigLink) && $this->FE_ConfigLink->keyId > 0) {
            $Qty = $this->FE_ConfigLink->Quantity;
        }
        echo "<tr><th>Quantity</th><td>$Qty</td></tr>";

        if ($this->DocumentTitle != '') {
            echo "<tr><th>Title</th><td>" . $this->DocumentTitle . "</td></tr>";
        }
        if ($this->Production_Status != '') {
            echo "<tr><th>Status</th><td>" . $this->Production_Status . "</td></tr>";
        }
        echo "</table>";
        echo "</div></div>";
    }

    public function DisplayTable_TestData() {
        $td = new TestDataTable($this->Band);
        $td->setComponent($this->fkFE_ComponentType, $this->SN);
        $td->DisplayAllMatching();
    }

    public function Display_UpdateConfigForm_CCA() {

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
        echo "<input type='hidden' name='fc' value='" . $this->keyFacility . "' />";
        echo "<input type='hidden' name='conf' value='$this->keyId' /><br>";

        //Options for what to update
        echo "<input type='checkbox' name='cca_updatemixers' value='cca_updatemixers' /> Update Mixers &nbsp &nbsp &nbsp &nbsp";
        echo "<input type='checkbox' name='cca_updatepreamps' value='cca_updatepreamps' />  Update Preamps</td></tr>";
        //echo "<input type='checkbox' name='cca_updatetemps' value='cca_updatetemps' /> Update Temp Sensors<br><br>";


        echo "</td></tr>";


        echo "
        </form>";
    }

    public function Display_Table_PreviousConfigurations() {
        $Band = "%";
        if ($this->Band != '') {
            $Band = $this->Band;
        }

        $q = "SELECT keyId, keyFacility FROM FE_Components
              WHERE SN = '" . $this->SN . "'
              AND Band LIKE '$Band'
              AND fkFE_ComponentType = " . $this->fkFE_ComponentType . "
              ORDER BY keyId DESC;";
        $r = mysqli_query($this->dbConnection, $q);
        if (mysqli_num_rows($r) > 1) {
            $r = mysqli_query($this->dbConnection, $q);

            echo "<div style = 'width:700px'>";

            echo "<table id='table1'>";
            echo "<tr class='alt'><th colspan='5'>Previous Configurations</th></tr>";
            echo "<tr>
                <th style='width:60px'>Config</th>
                <th>ESN1</th>
                <th>ESN2</th>
                <th style='width:60px'>FE Config</th>
                <th>TS</th>";

            while ($row = mysqli_fetch_array($r)) {
                $c_old = new FEComponent(NULL, $row['keyId'], NULL, $row['keyFacility']);

                $link_component = "ShowComponents.php?conf=$c_old->keyId&fc=" . $row['keyFacility'];
                $link_fe = "ShowFEConfig.php?key=$c_old->FEConfig&fc=$c_old->FEfc";

                echo "<tr >";
                echo "<td><a href='$link_component'>" . $c_old->keyId . "</a></td>";
                echo "<td>" . $c_old->ESN1 . "</td>";
                echo "<td>" . $c_old->ESN2 . "</td>";
                echo "<td><a href='$link_fe'>" . $c_old->FEConfig . "</a></td>";
                echo "<td>" . $c_old->TS  . "</td>";

                echo "</tr>";
                unset($c_old);
            }
        }
        echo "</table></div>";
    }

    public function Display_Table_ComponentHistory() {
        $SN = "%";
        if ($this->SN != '') {
            $SN = $this->SN;
        }
        $DocumentTitle = "%";
        if ($this->DocumentTitle != '') {
            $DocumentTitle  = $this->DocumentTitle;
        }

        $IsDoc = 0;
        switch ($this->fkFE_ComponentType) {
            case 217:
                $IsDoc = 1;
            case 218:
                $IsDoc = 1;
            case 219:
                $IsDoc = 1;
            case 220:
                $IsDoc = 1;
        }

        if ($IsDoc == 1) {
            $q = "SELECT FE_StatusLocationAndNotes.keyId AS SLNID,
                FE_StatusLocationAndNotes.fkFEComponents AS COMPID,
                FE_StatusLocationAndNotes.keyFacility AS SLNFC, FE_Components.keyFacility AS COMPFC,
                FE_ConfigLink.fkFE_Config AS FECFG
                FROM FE_StatusLocationAndNotes, FE_Components, FE_ConfigLink
                WHERE FE_StatusLocationAndNotes.fkFEComponents = FE_Components.keyId
                AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
                AND FE_ConfigLink.fkFE_ComponentFacility = FE_Components.keyFacility

                AND FE_Components.DocumentTitle LIKE '$DocumentTitle'
                GROUP BY FE_StatusLocationAndNotes.keyId ORDER BY FE_StatusLocationAndNotes.keyId DESC;";
        } else {
            $q = "SELECT FE_StatusLocationAndNotes.keyId AS SLNID,
                         FE_StatusLocationAndNotes.fkFEComponents AS COMPID,
                         FE_StatusLocationAndNotes.keyFacility AS SLNFC,
                         FE_Components.keyFacility AS COMPFC,
                         FE_ConfigLink.fkFE_Config AS FECFG
                  FROM FE_StatusLocationAndNotes, FE_Components, FE_ConfigLink
                  WHERE FE_StatusLocationAndNotes.fkFEComponents = FE_Components.keyId
                  AND FE_Components.fkFE_ComponentType = {$this->fkFE_ComponentType}
                  AND FE_Components.SN LIKE '$SN'
                  AND FE_Components.Band LIKE '{$this->Band}'
                  GROUP BY FE_StatusLocationAndNotes.keyId ORDER BY FE_StatusLocationAndNotes.keyId DESC;";
        }


        echo $q . "<br>";


        $r = mysqli_query($this->dbConnection, $q);

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

        while ($row = mysqli_fetch_array($r)) {
            $SLNID  = $row['SLNID'];
            $COMPID = $row['COMPID'];
            $SLNFC  = $row['SLNFC'];
            $COMPFC = $row['COMPFC'];

            $c = new FEComponent(NULL, $COMPID, NULL, $COMPFC);

            $sln = new SLN($SLNID, $SLNFC);

            $link_component = "ShowComponents.php?conf=$c->keyId&fc=" . $row['COMPFC'];

            echo "<tr >";

            echo "<td>" . $sln->TS . "</td>";
            echo "<td>" . $sln->location . "</td>";
            echo "<td>" . $sln->status . "</td>";
            echo "<td>" . $sln->Updated_By . "</td>";
            echo "<td><a href='$link_component'>" . $c->keyId . "</a></td>";

            $linktext = '';
            if (strlen($sln->lnk_Data) > 7) {
                $link = FixHyperlink($sln->lnk_Data);
                $linktext = "Link";
            }
            echo "<td><a href='$link'>$linktext</td>";
            echo "<td>" . $sln->Notes . "</td>";

            echo "</tr>";
            unset($c);
        }
        echo "</table></div>";
    }

    public function ComponentHistory_JSON() {

        $Band = "%";
        if ($this->Band != '') {
            $Band = $this->Band;
        }
        $SN = "%";
        if ($this->SN != '') {
            $SN = $this->SN;
        }
        $DocumentTitle = "%";
        if ($this->DocumentTitle != '') {
            $DocumentTitle  = $this->DocumentTitle;
        }

        $IsDoc = 0;
        switch ($this->fkFE_ComponentType) {
            case 217:
                $IsDoc = 1;
            case 218:
                $IsDoc = 1;
            case 219:
                $IsDoc = 1;
            case 220:
                $IsDoc = 1;
        }

        if ($IsDoc == 1) {
            $q = "SELECT FE_StatusLocationAndNotes.keyId AS SLNID, FE_StatusLocationAndNotes.TS AS SLNTS,
                FE_StatusLocationAndNotes.fkFEComponents AS COMPID,
                FE_StatusLocationAndNotes.keyFacility AS SLNFC, FE_Components.keyFacility AS COMPFC,
                FE_ConfigLink.fkFE_Config AS FECFG
                FROM FE_StatusLocationAndNotes, FE_Components, FE_ConfigLink
                WHERE FE_StatusLocationAndNotes.fkFEComponents = FE_Components.keyId
                AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
                AND FE_ConfigLink.fkFE_ComponentFacility = FE_Components.keyFacility

                AND FE_Components.DocumentTitle LIKE '$DocumentTitle'
                GROUP BY FE_StatusLocationAndNotes.keyId ORDER BY FE_StatusLocationAndNotes.keyId DESC;";
        }
        if ($IsDoc != 1) {

            $q = "SELECT FE_StatusLocationAndNotes.keyId AS SLNID,
                         FE_StatusLocationAndNotes.TS AS SLNTS,
                         FE_StatusLocationAndNotes.fkFEComponents AS COMPID,
                         FE_StatusLocationAndNotes.keyFacility AS SLNFC,
                         FE_Components.keyFacility AS COMPFC,
                         FE_ConfigLink.fkFE_Config AS FECFG
                  FROM FE_StatusLocationAndNotes, FE_Components, FE_ConfigLink
                  WHERE FE_StatusLocationAndNotes.fkFEComponents = FE_Components.keyId
                  AND FE_Components.fkFE_ComponentType =  {$this->fkFE_ComponentType}
                  AND FE_Components.SN LIKE '$SN'
                  AND FE_Components.Band LIKE '{$this->Band}
                  GROUP BY FE_StatusLocationAndNotes.keyId ORDER BY FE_StatusLocationAndNotes.keyId DESC;";
        }

        $r = mysqli_query($this->dbConnection, $q);

        $outstring = "[";
        $rowcount = 0;
        while ($row = mysqli_fetch_array($r)) {
            $SLNID  = $row['SLNID'];
            $COMPID = $row['COMPID'];
            $SLNFC  = $row['SLNFC'];
            $COMPFC = $row['COMPFC'];

            $c = new FEComponent(NULL, $COMPID, NULL, $COMPFC);

            $sln = new SLN($SLNID, $SLNFC);

            if (strlen($sln->lnk_Data) > 7) {
                $link = FixHyperlink($sln->lnk_Data);
                $linktext = "Link";
            }

            if ($rowcount == 0) {
                $outstring .= "{'TS':'" . $row['SLNTS'] . "',";
            }
            if ($rowcount > 0) {
                $outstring .= ",{'TS':'" . $row['SLNTS'] . "',";
            }

            if ($sln->keyId > 0) {
                $outstring .= "'Link':'" .    FixHyperLink($sln->lnk_Data) . "',";
                $outstring .= "'Config':'" .  $COMPID . "',";
                $outstring .= "'Who':'" .     $sln->Updated_By . "',";
                $outstring .= "'Location':'" . $sln->location . "',";
                $outstring .= "'Status':'" .     $sln->status . "',";
            }
            if ($sln->keyId < 1) {
                $outstring .= "'Link':'',";
                $outstring .= "'Config':'',";
                $outstring .= "'Who':'',";
                $outstring .= "'Location':'',";
                $outstring .= "'Status':'',";
            }

            $notes = mysqli_real_escape_string($this->dbConnection, $sln->Notes);
            $outstring .= "'Notes':'" .   str_replace('"', "'", $notes) . "'}";


            unset($c);

            $rowcount += 1;
        }

        $outstring .= "]";

        echo $outstring;
    }

    public function Display_ALLUpdateConfigForm_CCA() {
        require_once(site_get_classes() . '/class.wca.php');
        $cca = new CCA($this->keyId, $this->keyFacility, CCA::INIT_NONE);
        echo "<div style='width:850px'>";
        echo "<table id = 'table8'>";

        echo "<tr><td>";


        $cca->Display_uploadform_AnyFile($cca->keyId, $cca->keyFacility);
        echo "</td></tr>";

        echo "</table></div>";
        unset($cca);
    }


    public function GetFEConfig() {
        $q = "SELECT MAX(fkFE_Config) FROM FE_ConfigLink
              WHERE fkFE_Components = $this->keyId;";
        $r = mysqli_query($this->dbConnection, $q);
        $this->FEConfig = ADAPT_mysqli_result($r, 0, 0);
    }


    public function GetMaxConfig() {
        if ($this->SN == '') {
            $this->MaxConfig = 0;
        }
        if ($this->SN != '') {
            $band = '%';
            if ($this->Band > 0) {
                $band = $this->Band;
            }

            $qcfg = "SELECT MAX(keyId) FROM FE_Components
            WHERE SN = '" . $this->SN . "' AND Band LIKE '$band'
            AND fkFE_ComponentType = " . $this->fkFE_ComponentType . "
            and keyFacility = " . $this->keyFacility . ";";

            $rcfg = mysqli_query($this->dbConnection, $qcfg);
            $this->MaxConfig = ADAPT_mysqli_result($rcfg, 0, 0);
        }
    }
}
