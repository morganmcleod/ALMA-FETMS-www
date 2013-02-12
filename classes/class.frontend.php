<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_classes . '/class.cca.php');
require_once($site_classes . '/class.wca.php');
require_once($site_classes . '/class.sln.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_FEConfig . '/HelperFunctions.php');
require_once($site_dbConnect);

class FrontEnd extends GenericTable{
    var $feconfig;          //fe config object
    var $feconfigs;         //All config numbers for this front end
    var $feconfig_id;       //FEConfig id used to initialize the object. This
                            //may not be the latest config value. It is used
                            //on ShowFEConfig.php when viewing a previous configuration.
    var $feconfig_latest;   //LAtest FE Config value
    var $ccas;              //array of cca objects
    var $wcas;              //array of wca objects
    var $lpr;               //lpr object
    var $sln;               //Status location and notes
    var $cryostat;          //cryostat object
    var $fc;                //facility code
    var $fesln;             //Object representing record in FEStatusLocationAndNotes

    var $bgcolor1;

    var $JSONstring; //JSON string representing the front end and some components

    public function GetJSONstring() {
        //http://labs.adobe.com/technologies/spry/samples/data_region/JSONDataSetSample.html

        $jstring  = '{"frontend":{"id":"' . $this->keyId . '"';
        $jstring .= ',"sn":"' . $this->GetValue('SN') . '"';
        $jstring .= ',"feconfig":"' . $this->feconfig->keyId . '"';

        //Get CCAs portion of the string
        $jstring .=  ',"ccas":[';
        $cnt = 0;
        for ($iband=1; $iband<= 10; $iband++){
            if ($this->ccas[$iband]->keyId > 0){
                if ($cnt > 0){
                    $jstring .= ",";
                }
                $jstring .=  '{ "id": "' . $this->ccas[$iband]->keyId . '", "sn": "' . $this->ccas[$iband]->GetValue('SN') . '", "band": "' . $this->ccas[$iband]->GetValue('Band') . '" }';
                $cnt += 1;
            }
        }

        //Get WCAs portion of the string
        $jstring .=  '],"wcas":[';
        $cnt = 0;
        for ($iband=1; $iband<= 10; $iband++){
            if ($this->wcas[$iband]->keyId > 0){
                if ($cnt > 0){
                    $jstring .= ",";
                }
                $jstring .=  '{ "id": "' . $this->wcas[$iband]->keyId . '", "sn": "' . $this->wcas[$iband]->GetValue('SN') . '", "band": "' . $this->wcas[$iband]->GetValue('Band') . '" }';
                $cnt += 1;
            }
        }
        $jstring .=  "]}}";

        $this->JSONstring = $jstring;
    }

    public function Initialize_FrontEnd($in_keyId, $in_fc, $GetSubComponents = 1, $in_cfg=0){
        $this->bgcolor1 = '#c0f7fe';
        $this->keyId = $in_keyId;
        parent::Initialize('Front_Ends',$this->keyId,'keyFrontEnds',$in_fc,'keyFacility');
        $this->fc = $in_fc;

        //Get FE Config object
        $qcfg = "SELECT MAX(FE_Config.keyFEConfig)
                FROM Front_Ends,  FE_Config
                WHERE FE_Config.fkFront_Ends = $this->keyId
                GROUP BY FE_Config.keyFEConfig DESC LIMIT 1;";
        $rcfg = @mysql_query($qcfg,$this->dbconnection);
        $feconfig_id = @mysql_result($rcfg,0,0);
        $this->feconfig_latest = $feconfig_id;
        if ($in_cfg != 0){
            $feconfig_id = $in_cfg;
        }
        $this->feconfig = new GenericTable();
        $this->feconfig->Initialize('FEConfig',$feconfig_id,'keyFEConfig');
        //$this->feconfig_latest = $this->feconfig->keyId;

        //TODO: Temporarily commented out. Will uncomment later when debugged.
        //Get sln
        if ($this->feconfig->keyId > 0){
            $qsln = "SELECT MAX(keyId) FROM FE_StatusLocationAndNotes
                     WHERE fkFEConfig = ".$this->feconfig->keyId."
                     AND keyFacility = ". $this->GetValue('keyFacility') ."
                     ORDER BY keyId DESC LIMIT 1;";
            $rsln = @mysql_query($qsln,$this->dbconnection);
            $slnid = @mysql_result($rsln,0,0);
        }

        if ($slnid != ''){
            //$this->fesln = new GenericTable();
            //$this->fesln->Initialize('FE_StatusLocationAndNotes',$slnid,'keyId',$this->GetValue('keyFacility'),'keyFacility');

            $this->fesln = new SLN();
            $this->fesln->Initialize_SLN($slnid,$this->GetValue('keyFacility'));

        }

        //Get FE Configs
        $qcfg = "SELECT FE_Config.keyFEConfig
                FROM Front_Ends,  FE_Config
                WHERE FE_Config.fkFront_Ends = $this->keyId
                GROUP BY FE_Config.keyFEConfig DESC;";
        $rcfg = @mysql_query($qcfg,$this->dbconnection);
        $count = 0;
        while($rowcfg = @mysql_fetch_array($rcfg)){
            $this->feconfigs[$count] = $rowcfg[0];
            //echo $this->feconfigs[$count] . "<br>";
            $count += 1;
        }

        if ($GetSubComponents == 1){
            //Get LPR object
            $qlpr = "SELECT FE_Components.keyId, FE_Components.SN,FE_Components.ESN1
                    FROM FE_Components, FE_ConfigLink
                     WHERE FE_ConfigLink.fkFE_Config = ".$this->feconfig->keyId."
                     AND FE_Components.fkFE_ComponentType = 17
                     AND FE_ConfigLink.fkFE_Components = FE_Components.keyId;";
            $rlpr = @mysql_query($qlpr,$this->dbconnection);
            $lpr_id = @mysql_result($rlpr,0,0);
            $this->lpr = new GenericTable();
            $this->lpr->Initialize('FE_Components',$lpr_id,'keyId',$this->fc,'keyFacility');

            //Get cryostat object
            $qcryo = "SELECT FE_Components.keyId, FE_Components.SN,FE_Components.ESN1
                    FROM FE_Components, FE_ConfigLink
                     WHERE FE_ConfigLink.fkFE_Config = ".$this->feconfig->keyId."
                     AND FE_Components.fkFE_ComponentType = 6
                     AND FE_Components.keyFacility = $this->fc
                     AND FE_ConfigLink.fkFE_Components = FE_Components.keyId;";
            $rcryo = @mysql_query($qcryo,$this->dbconnection);
            $cryo_id = @mysql_result($rcryo,0,0);
            $this->cryostat = new GenericTable();
            $this->cryostat->Initialize('FE_Components',$cryo_id,'keyId',$this->fc,'keyFacility');


            for ($iw=1;$iw<=10;$iw++){
                //Get WCA objects
                $qwcas= "SELECT FE_Components.keyId, FE_Components.fkFE_ComponentType, FE_Config.keyFEConfig,
                        FE_Components.SN, FE_Components.Band,
                        FE_ConfigLink.keyId, Front_Ends.keyFrontEnds
                        FROM FE_Components, Front_Ends, FE_ConfigLink, FE_Config
                        WHERE FE_Components.fkFE_ComponentType = 11
                        AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
                        AND FE_ConfigLink.fkFE_Config = FE_Config.keyFEConfig
                        AND FE_Components.Band = $iw
                        AND FE_Config.fkFront_Ends = $this->keyId
                        AND FE_Config.keyFEConfig = ".$this->feconfig->keyId.";";
                //echo $qwcas . "<br>";
                $rwcas = @mysql_query($qwcas,$this->dbconnection);
                $wca_id = @mysql_result($rwcas,0,0);
                if ($wca_id != ''){
                    $this->wcas[$iw] = new WCA();

                    $this->wcas[$iw]->Initialize_WCA($wca_id,$this->fc);
                }

            }

            for ($iw=1;$iw<=10;$iw++){
                //Get CCA objects
                $qccas= "SELECT FE_Components.keyId, FE_Components.fkFE_ComponentType, FE_Config.keyFEConfig,
                        FE_Components.SN, FE_Components.Band,
                        FE_ConfigLink.keyId, Front_Ends.keyFrontEnds
                        FROM FE_Components, Front_Ends, FE_ConfigLink, FE_Config
                        WHERE FE_Components.fkFE_ComponentType = 20
                        AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
                        AND FE_ConfigLink.fkFE_Config = FE_Config.keyFEConfig
                        AND FE_Components.Band = $iw
                        AND FE_Config.fkFront_Ends = $this->keyId
                        AND FE_Config.keyFEConfig = ".$this->feconfig->keyId.";";
                $rccas = @mysql_query($qccas,$this->dbconnection);
                $cca_id = @mysql_result($rccas,0,0);
                if ($cca_id != ''){
                    $this->ccas[$iw] = new CCA();
                    $this->ccas[$iw]->Initialize_CCA($cca_id,$this->fc);
                }
            }
        }//end if GetSubComponents = 1
        $this->GetJSONstring();
    }

    public function Initialize_FrontEnd_FromSN($in_sn, $in_fc, $GetSubComponents = 1){
        $db = site_getDbConnection();
        $q = "SELECT max(keyFrontEnds) FROM Front_Ends WHERE SN = $in_sn;";
        $r = @mysql_query($q,$db);
        $this->keyId = @mysql_result($r,0,0);
        $this->Initialize_FrontEnd($this->keyId, $in_fc, $GetSubComponents);
    }

    public function Initialize_FrontEnd_FromConfig($in_cfg, $in_fc, $GetSubComponents = 1){
        $db = site_getDbConnection();
        $q = "SELECT fkFront_Ends FROM FE_Config WHERE keyFEConfig = $in_cfg;";
        $r = @mysql_query($q,$db);
        $this->keyId = @mysql_result($r,0,0);
        $this->Initialize_FrontEnd($this->keyId, $in_fc, $GetSubComponents, $in_cfg);
        $this->feconfig_id = $in_cfg;
    }

    public function DisplayTable_BeamPatterns($in_band = "%"){
        echo "<div style= 'width:650px'>";
        echo "<table id='table1'>";
        echo "<tr class = 'alt'><th colspan='4'>BEAM PATTERNS</th></tr>";
        echo "<tr><th>Band</th>
                  <th>GHz</th>
                  <th>Date/Time</th>
                  <th>Notes</th></tr>";

        $q = "SELECT ScanSetDetails.band, ScanSetDetails.keyId,
        	ScanSetDetails.TS, ScanSetDetails.notes, ScanSetDetails.f, FE_Config.keyFEConfig
            FROM ScanSetDetails, Front_Ends, FE_Config
            WHERE ScanSetDetails.fkFE_Config = FE_Config.keyFEConfig
            AND FE_Config.fkFront_Ends = Front_Ends.keyFrontEnds
            AND Front_Ends.keyFrontEnds = $this->keyId
            AND ScanSetDetails.band LIKE '$in_band'
            AND is_deleted <> 1
            GROUP BY ScanSetDetails.band ASC, ScanSetDetails.TS ASC;";

        $r = @mysql_query($q,$this->dbconnection);

        while ($row = @mysql_fetch_array($r)) {
            $trclass = ($trclass=="" ? 'class="alt"' : "");
            echo "<tr $trclass><td width = '10px'>$row[0]</td>";
            echo "<td width = '10px'>$row[4]</td>";
            echo "<td width='150px'><a href='../testdata/bp.php?id=$row[1]&band=$row[0]&keyconfig=$row[5]&fc=$this->fc' target = 'blank'>$row[2]</a></td>";
            echo "<td width='350px'>$row[3]</td>";
        }
        echo "</table></div>";
    }

    public function DisplayTable_IFSpectrum() {
        echo "<br><br>";
        echo "<div style= 'width:200px'>";
        echo "<table id='table1'>";
        echo "<tr class='alt'><th colspan='2'>IF SPECTRUM</th>";
        echo "<tr><th>Band</th>
                  <th>Date/Time</th>";

        for($i=0;$i<count($this->feconfigs);$i++) {
            $keyconfig = $this->feconfigs[$i];

            $q = "SELECT band, keyId, TS, notes FROM TestData_header
                  WHERE fkFE_Config = $keyconfig
                  AND fkTestData_Type = 7
                  ORDER BY band ASC, TS ASC";

            //echo "<td>$q</td>";
            $r = @mysql_query($q,$this->dbconnection);
            if (@mysql_num_rows($r) > 0) {
                while ($row = @mysql_fetch_array($r)){
                    echo "<tr><td width='50px'>$row[0]</td>";
                    echo "<td width='150px'><a href='testdata.php?keyheader=$row[1]&fc=$this->fc'>$row[2]</a></td></tr>";
                }
            }
        }
        echo "</table></div>";
    }

    public function DisplayTable_LOLockTest(){
        echo "<br><br>";
        echo "<div style= 'width:200px'>";
        echo "<table id='table1'>";
        echo "<tr class='alt'><th colspan='2'>LO LOCK TEST</th>";
        echo "<tr><th>Band</th>
                  <th>Date/Time</th>";

        for($i=0;$i<count($this->feconfigs);$i++) {
            $keyconfig = $this->feconfigs[$i];
            for ($iBand=0; $iBand<=10; $iBand++) {
                for($i=0;$i<count($this->feconfigs);$i++) {
                    $keyconfig = $this->feconfigs[$i];
                    $q="SELECT TestData_header.keyId, TestData_header.TS
                    FROM TestData_header, TEST_LOLockTest_SubHeader
                    WHERE fkFE_Config = $keyconfig
                    AND fkTestData_Type = 57
                    AND TestData_header.Band = $iBand
                    AND TEST_LOLockTest_SubHeader.fkHeader = TestData_header.keyId
                    AND TEST_LOLockTest_SubHeader.fkFacility = $this->fc
                    GROUP BY TestData_header.Band ASC;";

                    //echo $q ."<br><br>";
                    $r = @mysql_query($q,$this->dbconnection);
                    $tdhid = @mysql_result($r,0,0);
                    $ts = @mysql_result($r,0,1);
                    if ($tdhid != "") {
                        echo "<tr><td width='50px'>$iBand</td>";
                        echo "<td width='180px'><a href='testdata.php?keyheader=$tdhid&fc=$this->fc'>$ts</a></td></tr>";
                    }
                }
            }

            //echo "<td>$q</td>";
            $r = @mysql_query($q,$this->dbconnection);
            if (@mysql_num_rows($r) > 0) {
                while ($row = @mysql_fetch_array($r)) {
                    echo "<tr><td width='50px'>$row[0]</td>";
                    echo "<td width='80px'>$row[3]</td>";
                    echo "<td width='80px'>$row[4]</td>";
                    echo "<td width='80px'>$row[5]</td>";
                    echo "<td width='180px'><a href='testdata.php?keyheader=$row[1]'>$row[6]</a></td></tr>";
                }
            }
        }
        echo "</table></div>";
    }

    public function DisplayTable_PolAngles(){
        echo "<br><br>";

        echo "<div style= 'width:300px'>";
        echo "<table id='table1'>";
        echo "<tr class='alt'><th colspan='2'>POL ANGLES</th>";
        echo "<tr><th>Band</th>
            <th>Date/Time</th>";

        $q = "SELECT TestData_header.band, TestData_header.keyId, TestData_header.TS,
             TestData_header.notes, TEST_PolAngles.f
             FROM TestData_header,FE_Config,TEST_PolAngles
             WHERE TestData_header.fkFE_Config = FE_Config.keyFEConfig
             AND TestData_header.fkTestData_Type = 56
             AND TEST_PolAngles.fkHeader = TestData_header.keyId
             AND FE_Config.fkFront_Ends = $this->keyId
             GROUP BY TestData_header.band ASC, TestData_header.TS ASC;";

        $r = @mysql_query($q,$this->dbconnection);
        if (@mysql_num_rows($r) > 0){
            while ($row = @mysql_fetch_array($r)){
                echo "<tr><td width='50px'>$row[0]</td>";
                echo "<td width='150px'><a href='testdata.php?keyheader=$row[1]&fc=$this->fc'>$row[2]</a></td></tr>";
            }
        }
        echo "</table></div>";
    }

    public function DisplayTable_WorkmanshipAmplitude(){
        echo "<br><br>";
        echo "<div style= 'width:200px'>";
        echo "<table id='table1'>";
        echo "<tr class='alt'><th colspan='2'>WORKMANSHIP AMPLITUDE</th>";

        echo "<tr><th>Band</th>
                <th>Date/Time</th>";

        $q = "SELECT TestData_header.band, TestData_header.keyId, TestData_header.TS,
        TestData_header.notes
        FROM TestData_header,FE_Config
        WHERE TestData_header.fkFE_Config = FE_Config.keyFEConfig
        AND TestData_header.fkTestData_Type = 29
        AND FE_Config.fkFront_Ends = $this->keyId
        GROUP BY TestData_header.band ASC, TestData_header.TS ASC;";
        $r = @mysql_query($q,$this->dbconnection);
        if (@mysql_num_rows($r) > 0){
            while ($row = @mysql_fetch_array($r)){
                echo "<tr><td width='50px'>$row[0]</td>";
                echo "<td width='150px'><a href='testdata.php?keyheader=$row[1]&fc=$this->fc'>$row[2]</a></td></tr>";
            }
        }
        echo "</table></div>";
    }

    public function DisplayTable_WorkmanshipPhase(){
        echo "<br><br>";
        echo "<div style= 'width:400px'>";
        echo "<table id='table1'>";
        echo "<tr class = 'alt'><th colspan='5' bgcolor='#000000'>WORKMANSHIP PHASE</th>";
        echo "<tr><th>Band</th>
        <th>RF</th>
        <th>LO</th>
        <th>Pol</th>
            <th>Date/Time</th>";

        $q = "SELECT TestData_header.band, TestData_header.keyId, TestData_header.TS,
        TestData_header.notes,
        TEST_Workmanship_Phase_SubHeader.pol,TEST_Workmanship_Phase_SubHeader.rf,
        TEST_Workmanship_Phase_SubHeader.lo,
        TEST_Workmanship_Phase_SubHeader.sb
        FROM TestData_header,FE_Config,TEST_Workmanship_Phase_SubHeader
        WHERE TestData_header.fkFE_Config = FE_Config.keyFEConfig
        AND TestData_header.fkTestData_Type = 30
        AND FE_Config.fkFront_Ends = $this->keyId
        GROUP BY TestData_header.band ASC, TestData_header.TS ASC;";

        $r = @mysql_query($q,$this->dbconnection);
        if (@mysql_num_rows($r) > 0){
            while ($row = @mysql_fetch_array($r)){
                echo "<tr><td width='50px'>$row[0]</td>";
                echo "<td width='50px'>$row[5]</td>";
                echo "<td width='50px'>$row[6]</td>";
                echo "<td width='50px'>$row[4]</td>";
                echo "<td width='150px'><a href='testdata.php?keyheader=$row[1]&fc=$this->fc'>$row[2]</a></td></tr>";
            }
        }
        echo "</table></div>";
    }

    public function DisplayTable_Cryostat(){
        echo "<br><br><h2>CRYOSTAT</h2>";
        echo "<div style= 'width:350px'>";
        echo "<table id='table1'>";

        $q = "SELECT Cryostat_SubHeader.keyId, Cryostat_SubHeader.SN, Cryostat_SubHeader.TS
             FROM Cryostat_SubHeader, Front_Ends
             WHERE Cryostat_SubHeader.SN = Front_Ends.SN
             AND Front_Ends.keyFrontEnds = $this->keyId;";

        $r = @mysql_query($q,$this->dbconnection);
        if (@mysql_num_rows($r) > 0){
            while ($row = @mysql_fetch_array($r)){
                echo "<tr><th>Cryostat $row[1]</th>";
                echo "<td width='200px'><a href='https://safe.nrao.edu/php/ntc/cryostat/cryostat.php?keyId=$row[0]'>CLICK TO VIEW DATA</a></td></tr>";
            }
        }
        echo "</table></div>";
    }

    public function DisplayTable_SLN_History(){
        echo "<div style= 'width:1100px'><font size='2'>";
        echo "<table id='table1'>";
        echo "<tr class = 'alt'><th colspan='7'>CONFIGURATION HISTORY</th></tr>";
        echo "<tr><th>Date</th>
                <th>Location</th>
                <th>Status</th>
                <th>Who</th>
                <th>Config #</th>
                <th>Link</th>
                <th>Notes</th>
                </tr>";

        for ($i = 0; $i < count ($this->feconfigs); $i++){
            $q = "SELECT keyId FROM FE_StatusLocationAndNotes
                  WHERE fkFEConfig = " .$this->feconfigs[$i]."
                  ORDER BY keyId DESC";
            $r = @mysql_query($q,$this->dbconnection);

            while ($row = @mysql_fetch_array($r)){
                $bg = ($bg=="#ffffff" ? "#E6E6FF" : "#ffffff");
                $trclass = ($trclass=="" ? 'class="alt"' : "");
                $sln = new SLN();

                $sln->Initialize_SLN($row[0], $this->fc);

                echo "<tr bgcolor='$bg'>";
                echo "<td width = '160px'>" .$sln->GetValue('TS') . "</td>";
                echo "<td width = '300px'>" .$sln->location . "</td>";
                echo "<td width = '160px'>" .$sln->status . "</td>";
                echo "<td>" .$sln->GetValue('Updated_By') . "</td>";
                echo "<td width = '60px' align='center'>
                <a href='../FEConfig/ShowFEConfig.php?key=".$this->feconfigs[$i]."&fc=".$this->GetValue('keyFacility') ."'>".
                $this->feconfigs[$i] . "</a></td>";

                if (strlen($sln->GetValue('lnk_Data')) > 5){
                    echo "<td width = '40px'>
                    <a href='".FixHyperLink($sln->GetValue('lnk_Data'))."'>
                    Link
                    </a></td>";
                    //echo "<td width = '40px'>
                    //<a href= '".$sln->GetValue('lnk_Data')."'>LINK
                    //</a></td>";
                }
                if (strlen($sln->GetValue('lnk_Data')) <= 5){
                    echo "<td></td>";
                }
                echo "<td>" .substr($sln->GetValue('Notes'),0,35) . "</td>";


                echo "</tr>";
            }
        }
        echo "</table></font></div>";
    }


    public function SLN_History_JSON(){
        $outstring = "[";
        $rowcount = 0;

        for ($i = 0; $i < count ($this->feconfigs); $i++){
            $q = "SELECT keyId FROM FE_StatusLocationAndNotes
                  WHERE fkFEConfig = " .$this->feconfigs[$i]."
                  ORDER BY keyId DESC";
            $r = @mysql_query($q,$this->dbconnection);

            while ($row = @mysql_fetch_array($r)){
                $sln = new SLN();
                $sln->Initialize_SLN($row[0], $this->fc);
            if ($rowcount == 0 ){
                $outstring .= "{'TS':'".$sln->GetValue('TS')."',";
            }
            if ($rowcount > 0 ){
                $outstring .= ",{'TS':'".$sln->GetValue('TS')."',";
            }
            $outstring .= "'Location':'".$sln->location."',";
            $outstring .= "'Who':'".     $sln->GetValue('Updated_By')."',";
            $outstring .= "'Status':'".     $sln->status."',";
            $outstring .= "'Config':'".  $this->feconfigs[$i]."',";
            $outstring .= "'Link':'".    FixHyperLink($sln->GetValue('lnk_Data'))."',";

            $notes = @mysql_real_escape_string($sln->GetValue('Notes'));
            $outstring .= "'Notes':'".   str_replace('"', "'", $notes)."'}";
            $rowcount += 1;
            }
        }
        $outstring .= "]";
        echo $outstring;
    }

    public function DisplayTable_ComponentData($band = "%", $ComponentType = "%"){
        $bandstring = "";
        echo "<div style= 'width:890px'>";
        echo "<table id='table1'>";
        echo "<tr class = 'alt'><th colspan='6'>TEST DATA</th></tr>";
        echo "<tr><th>FE Config</th>
                  <th>Data Status</th>";

        if ($band == '%'){
            echo "<th>Band</th>";
        }
        echo "<th>Description</th>
              <th>Notes</th>
              <th>TS</th>
              </tr>";
        $this->DisplayTable_BeamPatternsNoHeader($band);
        $q = "SELECT DISTINCT(TestData_header.keyId),TestData_Types.Description
            FROM TestData_header,FE_Config,Front_Ends,TestData_Types, FE_Components
            WHERE TestData_header.fkFE_Config = FE_Config.keyFEConfig
            AND FE_Config.fkFront_Ends = $this->keyId
            AND TestData_header.band LIKE '$band'
            AND TestData_header.fkTestData_Type = TestData_Types.keyId
            ORDER BY
            TestData_Types.Description ASC, TestData_header.Band ASC,
            TestData_header.TS DESC;";
        echo $q;

        $r = @mysql_query($q,$this->dbconnection);
        while ($row = @mysql_fetch_array($r)){
            $tdh = new TestData_header();
            $tdh->Initialize_TestData_header($row['keyId'],$this->fc, $this->feconfig->keyId);
            $tdh->subheader = new GenericTable();
            switch($tdh->GetValue('fkTestData_Type')){
                case 29:
                    $tdh->subheader->Initialize('TEST_Workmanship_Amplitude_SubHeader',$tdh->keyId,'fkHeader');
                    $polstr = "(LO " . $tdh->subheader->GetValue('lo') . ")";
                    break;
                case 30:
                    $tdh->subheader->Initialize('TEST_Workmanship_Phase_SubHeader',$tdh->keyId,'fkHeader');
                    $polstr = "(Pol " . $tdh->subheader->GetValue('pol');
                    $polstr .= ", LO " . $tdh->subheader->GetValue('lo') . ")";
                    break;
            }
            unset($tdh->subheader);

            $trclass = ($trclass=="" ? 'class="alt"' : "");
            echo "<tr $trclass><td width = '10px' align = 'center'>".$tdh->GetValue('fkFE_Config')."</td>";
            echo "<td width = '40px'>$tdh->DataStatus</td>";
            if ($band == '%'){
                echo "<td width = '10px' align = 'center'>".$tdh->GetValue('Band')."</td>";
            }
            echo "<td width='200px'><a href='../testdata/testdata.php?keyheader=$tdh->keyId' target = 'blank'>".$tdh->TestDataType." $polstr</a></td>";
            echo "<td width = '150px'>".$tdh->GetValue('Notes')."</td>";
            echo "<td width = '120px'>".$tdh->GetValue('TS')."</td></tr>";
            unset($tdh);
        }
        echo "</table></div>";
    }

    public function DisplayTable_ComponentList($band = "%", $componenttype = "%"){
        $bandstring = "FE_Components.Band LIKE '$band'";
        $componenttype_string = "FE_Components.fkFE_ComponentType LIKE '$componenttype'";

        switch ($componenttype){
            case "other":
                $componenttype = "%";
                $componenttype_string = "FE_Components.fkFE_ComponentType LIKE '%'";
                $bandstring = " FE_Components.Band < 1 ";
                break;
        }

        echo "<div style= 'width:900px'>";
        echo "<table id='table1'>";
        echo "<tr class = 'alt'><th colspan='6'>";
        if ($band >= '1' && $band <= 10) {
            echo "BAND " . $band . " ";
        }
        echo "COMPONENTS</th></tr>";
        echo "<tr><th style = 'width:110px'>DESCRIPTION</th>
                <th style = 'width:70px'>SN</th>
                <th style = 'width:40px'>ESN1</th>
                <th style = 'width:80px'>ESN2</th>
                <th style = 'width:90px'>TS</th>
                <th style = 'width:50px'>LINKS</th>
              </tr>";
        $searchconfig = $this->feconfig->keyId;
        if ($this->feconfig_id > 0){
            $searchconfig = $this->feconfig_id;
        }

        $q = "SELECT ComponentTypes.Description, FE_Components.SN,
        FE_Components.ESN1, FE_Components.ESN2, FE_Components.TS,
        FE_Components.keyFacility, FE_Components.keyId,
        FE_Components.Link1,FE_Components.Link2, ComponentTypes.keyId AS KeyComponent
        FROM FE_Components,FE_ConfigLink,FE_Config, ComponentTypes
        WHERE FE_ConfigLink.fkFE_Components = FE_Components.keyId
        AND FE_ConfigLink.fkFE_ComponentFacility = FE_Components.keyFacility
        AND FE_ConfigLink.fkFE_ConfigFacility = FE_Config.keyFacility
        AND ComponentTypes.keyId = FE_Components.fkFE_ComponentType
        AND $bandstring
        AND $componenttype_string
        AND FE_ConfigLink.fkFE_Config = ".$searchconfig ."
        GROUP BY ComponentTypes.Description ASC, FE_Components.SN ASC;";

        $r = @mysql_query($q,$this->dbconnection);

        while ($row = @mysql_fetch_array($r)){
            $Display = 1;
            if ($band == ''){
                switch($row['KeyComponent']){
                    case 217:
                        $Display = 0;
                        break;
                    case 218:
                        $Display = 0;
                        break;
                    case 219:
                        $Display = 0;
                        break;
                    case 220:
                        $Display = 0;
                        break;
                    case 222:
                        $Display = 0;
                        break;
                }
            }

            if ($Display == '1'){
                $trclass = ($trclass=='class="alt5"' ? 'class="alt4"' : 'class="alt5"');
                echo "<tr $trclass><td align = 'center'>".$row['Description']."</td>";

                $link = "ShowComponents.php?conf=". $row['keyId'] . "&fc=" . $row['keyFacility'];
                $SN = "NA";
                if (strlen($row['SN']) > 0){
                    $SN = $row['SN'];
                }
                echo "<td  align = 'center'><a href='$link'>$SN</a></td>";
                echo "<td  align = 'center'>".$row['ESN1']."</td>";
                echo "<td  align = 'center'>".$row['ESN2']."</td>";
                echo "<td  align = 'center'>".$row['TS']."</td>";

                $Link1 = FixHyperLink($row['Link1']);
                $Link2 = FixHyperLink($row['Link2']);

                $Links = "";
                if (strlen($Link1) > 5){
                    $Links .= "<a href='$Link1'>SICL</a>";
                    if ($Link2 != ''){
                        $Links .= ",";
                    }

                }
                if (strlen($Link2) > 5){
                    $Links .= "<a href='$Link2'>CIDL</a>";
                }
                echo "<td  align = 'center'>$Links</td></tr>";
            }
        }
        echo "</table></div>";
    }


    public function DisplayTable_AllPAITestData($band = "%"){
        $bandstring = "";
        echo "<div style= 'width:900px'>";
        echo "<table id='table1'>";
        echo "<tr class = 'alt'><th colspan='7'>TEST DATA</th></tr>";
        echo "<tr>
                <th width='10px'>FE Config</th>
                <th>Data Status</th>";

        if ($band == '%'){
            echo "<th align = 'center'>Band</th>";
        }

        echo "  <th>Description</th>
                <th>Notes</th>
                <th>TS</th>
                <th width='10px'>for PAI</th>
              </tr>";

        $q = "SELECT DISTINCT(TestData_header.keyId),TestData_Types.Description,
        TestData_header.fkTestData_Type,FE_Config.keyFEConfig,
        TestData_header.Band,TestData_header.Notes,
        TestData_header.fkDataStatus, TestData_header.TS,
        TestData_header.keyFacility, DataStatus.Description,
        TestData_header.DataSetGroup, TestData_header.UseForPAI
        FROM TestData_header,FE_Config,Front_Ends,TestData_Types, DataStatus
        WHERE TestData_header.fkFE_Config = FE_Config.keyFEConfig
        AND FE_Config.fkFront_Ends = $this->keyId
        AND TestData_header.fkDataStatus = DataStatus.keyId
        AND TestData_header.band LIKE '$band'
        AND TestData_header.fkTestData_Type = TestData_Types.keyId
        ORDER BY TestData_Types.Description ASC, TestData_header.Band ASC, TestData_header.TS DESC;";

        $r = @mysql_query($q,$this->dbconnection);

        while ($row = @mysql_fetch_array($r)){
            // exclude test data types which are shown health check and PAS reference data table:
            if (!(in_array($row[2], array(1,2,3,4,5,6,8,9,10,12,13,14,15,24,39)))) {

                // save newdata header record data set in a two dimentional array
                // first dimension is test data type, second is the dataset group
                $record_list[$row[2]][] = $row[10];
                // count how many times each dataset group occurs
                $dataset_cnt = array_count_values ( $record_list[$row[2]]);

                // display row if there is only one entry for the dataset or the dataset is 0
                if ($dataset_cnt[$row[10]] <= 1 || $row[10] == 0 ){

                    $TestNotes = $row[5];
                    $trclass = ($trclass=="" ? 'class="alt"' : "");
                    echo "<tr $trclass><td width = '10px' align = 'center'>".$row[3]."</td>";
                    echo "<td width = '70px'>$row[9]</td>";
                    if ($band == '%'){
                        echo "<td width = '10px' align = 'center'>".$row[4]."</td>";
                    }

                    $testpage = 'testdata/testdata.php';

                    switch($row[fkTestData_Type]){
                        case 55:
                            //Beam patterns
                            $testpage = 'bp/bp.php';
                            echo "<td width='180px'><a href='$testpage?keyheader=$row[0]&fc=". $row[8] ."' target = 'blank'>".$row[1]."</a></td>";
                            echo "<td width = '150px'>".$TestNotes."</td>";
                            echo "<td width = '120px'>".$row[7]."</td>";
                            break;
                        case 7:
                            //IFSpectrum
                            $testpage = 'ifspectrum/ifspectrumplots.php';

                            $url  = $testpage . "?fc=". $row['keyFacility'];
                            $url .= "&fe=" . $this->keyId . "&b=" . $row['Band'];
                            $url .= "&id=" . $row[0];

                            echo "<td width='180px'><a href='$url' target = 'blank'>".$row[1]." Group " . $row[10]. "</a></td>";
                            echo "<td width = '150px'>".$TestNotes."</td>";
                            echo "<td width = '120px'>".$row[7]."</td>";
                            break;

                        case 57:
                        case 58:
                            //LO Lock Test or noise temp
                            if ($row[10] != 0){
                                $Description = "$row[1] Group " . $row[10];
                                echo "<td width='180px'><a href='$testpage?keyheader=$row[0]" . "&g=" . $row[10] . "&fc=". $row[8] ."' target = 'blank'>$Description</a></td>";
                                echo "<td width = '150px'>".$TestNotes."</td>";
                                echo "<td width = '120px'>".$row[7]."</td>";
                            } else {
                                echo "<td width='180px'><a href='$testpage?keyheader=$row[0]&fc=". $row[8] ."' target = 'blank'>".$row[1]."</a></td>";
                                echo "<td width = '150px'>".$TestNotes."</td>";
                                echo "<td width = '120px'>".$row[7]."</td>";
                            }
                            break;

                        default:
                            echo "<td width='180px'><a href='$testpage?keyheader=$row[0]&fc=". $row[8] ."' target = 'blank'>".$row[1]."</a></td>";
                            echo "<td width = '150px'>".$TestNotes."</td>";
                            echo "<td width = '120px'>".$row[7]."</td>";
                            break;
                    }
                    // In the "for PAI" column show a checkbox corresponding to the value of UseForPAI:
                    echo "<td width = '10px'>";
                    if ($row[11])
                        $checked = "checked='checked'";
                    else
                        $checked = "";
                    $cboxId = "PAI_" . $row[0];
                    // Call the PAIcheckBox JS function when the checkbox is clicked:
                    echo "<input type='checkbox'
                            name='$cboxId'
                            id='$cboxId'
                            $checked
                            onchange=\"PAIcheckBox($row[0], document.getElementById('$cboxId').checked);\" />";
                    echo "</td></tr>";
                }
                unset($tdh);
            }
        }
        echo "</table></div>";
    }

    public function DisplayTable_BeamPatternsNoHeader($in_band = "%"){
        $q = "SELECT ScanSetDetails.band, ScanSetDetails.keyId,
        ScanSetDetails.TS, ScanSetDetails.notes, ScanSetDetails.f, FE_Config.keyFEConfig
            FROM ScanSetDetails, Front_Ends, FE_Config
            WHERE ScanSetDetails.fkFE_Config = FE_Config.keyFEConfig
            AND FE_Config.fkFront_Ends = Front_Ends.keyFrontEnds
            AND Front_Ends.keyFrontEnds = $this->keyId
            AND ScanSetDetails.band LIKE '$in_band'
            AND is_deleted <> 1
            GROUP BY ScanSetDetails.band ASC, ScanSetDetails.TS ASC;";


        $r = @mysql_query($q,$this->dbconnection);

        while ($row = @mysql_fetch_array($r)){

            $trclass = ($trclass=="" ? 'class="alt"' : "");
            echo "<tr $trclass><td width = '10px' align = 'center'>$row[5]</td>";
            echo "<td>Cold PAI</td>";
            if ($in_band == '%'){
                echo "<td width = '30px' align = 'center'>$row[0]</td>";
            }
            echo "<td><a href='../testdata/bp.php?id=$row[1]&band=$row[0]&keyconfig=$row[5]&fc=$this->fc' target = 'blank'>Beam Patterns ($row[4] GHz)</a></td>";
            echo "<td>$row[3]</td>";
            echo "<td>$row[2]</td></tr>";
        }
    }

    public function Display_Table_Documents(){
        $doctypeNames = array('PAI and PAS Reports','Requests for Waiver','Non-Conformances','CAR Notices','Other Documents');
        $doctypeKeys = array(217,218,219,222,220);

        echo "<div style = 'width:820px'>";

        for ($i = 0; $i< count($doctypeKeys); $i++){
            //Get all docs of this type
            $q = "SELECT FE_Components.keyId, FE_Components.keyFacility
                    FROM FE_Components, FE_ConfigLink
                    WHERE FE_Components.fkFE_ComponentType = $doctypeKeys[$i]
                    AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
                    AND FE_ConfigLink.fkFE_Config = " . $this->feconfig->keyId . "
                    GROUP BY FE_Components.Documenttitle ASC
                    ;";

            $r = @mysql_query($q,$this->dbconnection);

            echo "<table id='table1'>";
            echo "<tr class='alt'><th colspan='4'>$doctypeNames[$i]</th></tr>";
            echo "<tr>";
                echo "<th style='width:400px'>Title</th>
                      <th style='width:300px'>Comments</th>
                      <th>Status</th>";
            echo "</tr>";

            while ($row = @mysql_fetch_array($r)){
                $trclass = ($trclass=='class="alt5"' ? 'class="alt4"' : 'class="alt5"');
                $doc = new FEComponent();
                $doc->Initialize_FEComponent($row['keyId'], $row['keyFacility']);
                echo "<tr $trclass>";

                $url = "ShowComponents.php?conf=" . $doc->keyId . "&fc=" . $doc->GetValue('keyFacility');

                echo "<td><a href=$url>" . $doc->GetValue('DocumentTitle')."</a></td>";
                echo "<td>" . $doc->GetValue('Description')."</td>";
                echo "<td>" . $doc->GetValue('Production_Status')."</td>";
                echo "</tr>";
                unset($doc);
            }
            echo "</table><br>";
        }
        echo "</div>";
    }

    public function DisplayTable_PAITestData_Summary($band = "%"){

        echo "<div style= 'width:500px'>";
        echo "<table id='table1'>";
        echo "<tr class = 'alt'><th colspan='6'>PAS Reference and Health Check Data</th></tr>";
        echo "<tr><th>FE Config</th>
                <th>Data Status</th>
                <th>TS</th></tr>";

        $q = "SELECT DISTINCT(TestData_header.keyId),TestData_Types.Description,
            TestData_header.fkTestData_Type,FE_Config.keyFEConfig,
            TestData_header.Band,TestData_header.Notes,
            TestData_header.fkDataStatus, TestData_header.TS,
            TestData_header.keyFacility, DataStatus.Description
            FROM TestData_header,FE_Config,Front_Ends,TestData_Types, DataStatus
            WHERE TestData_header.fkFE_Config = FE_Config.keyFEConfig
            AND FE_Config.fkFront_Ends = $this->keyId
            AND TestData_header.fkDataStatus = DataStatus.keyId
            AND TestData_header.band LIKE '$band'
            AND TestData_header.fkTestData_Type = TestData_Types.keyId
            AND TestData_header.fkTestData_Type IN (1,2,3,13,24)
            ORDER BY TestData_header.fkFE_Config DESC, TestData_header.fkDataStatus ASC, TestData_header.TS DESC";

        $r = @mysql_query($q,$this->dbconnection);

        $prev_data_status = -1;
        $prev_fe_config = -1;
        while ($row = @mysql_fetch_array($r)){
            if ($prev_data_status != $row[6] || $prev_fe_config != $row[3]){
                        $trclass = ($trclass=="" ? 'class="alt"' : "");
                echo "<tr $trclass ><td width = '75px' align = 'center'>".$row[3]."</td>
                    <td width = '125px'><a href='testdata/pas_results.php?FE_Config=".$row[3]."&band=$band&Data_Status=".$row[6]."' target = 'blank'>$row[9]</a>
                    <td width = '300px'>".$row[7]."</td>";
            }
        $prev_data_status = $row[6];
        $prev_fe_config = $row[3];
        }
        echo "</table></div>";
    }

}// end class FrontEnd
