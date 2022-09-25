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

class FrontEnd extends GenericTable {
    public $feconfig;           //FE_Config record object to use. TODO: make private with accessor.
    public $feconfig_id_latest; //newest found FE_Config id. TODO: make private with accessor.

    public $fc;                 //facility code, considered part of keyId.  TODO: inherited from GenericTable.  Get rid of?
    private $feconfig_id;       //FEConfig id used to initialize the object.
                                //may not be the latest config value.
                                //used on ShowFEConfig.php when viewing a previous configuration.

    private $feconfig_ids_all;  //array of all FrontEnd config IDs for $in_keyId passed to this FrontEnd.

    public $ccas;               //array of CCA objects. TODO: make private with accessor.
    public $wcas;               //array of WCA objects. TODO: make private with accessor.
    public $lpr;                //LPR object. TODO: make private with accessor.
    public $cryostat;           //Cryostat object. TODO: make private with accessor.
    public $fesln;              //FE_StatusLocationAndNotes record object. TODO: make private with accessor.

    private $JSONstring;        //JSON string representing the front end and some components

    public function __construct() {
        parent::__construct();
    }
    
    private function GetJSONstring() {
        // TODO:  This seems to be dead code -- not called from anywhere.
        //http://labs.adobe.com/technologies/spry/samples/data_region/JSONDataSetSample.html

        $jstring  = '{"frontend":{"id":"' . $this->keyId . '"';
        $jstring .= ',"sn":"' . $this->GetValue('SN') . '"';
        $jstring .= ',"feconfig":"' . $this->feconfig->keyId . '"';

        //Get CCAs portion of the string
        $jstring .=  ',"ccas":[';
        $cnt = 0;
        for ($iband = 1; $iband <= 10; $iband++) {
            if (isset($this -> ccas[$iband])) {
                $ccas = $this -> ccas[$iband];
                if ($ccas -> keyId > 0) {
                    if ($cnt > 0) {
                        $jstring .= ",";
                    }
                    $jstring .=  '{ "id": "' . $this->ccas[$iband]->keyId . '", "sn": "' . $this->ccas[$iband]->GetValue('SN') . '", "band": "' . $this->ccas[$iband]->GetValue('Band') . '" }';
                    $cnt += 1;
                }
            }
        }

        //Get WCAs portion of the string
        $jstring .=  '],"wcas":[';
        $cnt = 0;
        for ($iband = 1; $iband <= 10; $iband++) {
            if (isset($this -> wcas[$iband])) {
                $wcas = $this->wcas[$iband];
                if ($wcas -> keyId > 0) {
                    if ($cnt > 0) {
                        $jstring .= ",";
                    }
                    $jstring .=  '{ "id": "' . $this->wcas[$iband]->keyId . '", "sn": "' . $this->wcas[$iband]->GetValue('SN') . '", "band": "' . $this->wcas[$iband]->GetValue('Band') . '" }';
                    $cnt += 1;
                }
            }
        }
        $jstring .=  "]}}";

        $this->JSONstring = $jstring;
    }

    const INIT_SLN      = 0x01;
    const INIT_CONFIGS  = 0x02;
    const INIT_LPR      = 0x04;
    const INIT_CRYOSTAT = 0x08;
    const INIT_WCA      = 0x10;
    const INIT_WCAPARAM = 0x20;
    const INIT_CCA      = 0x40;
    const INIT_CCAPARAM = 0x80;

    const INIT_NONE     = 0x00;
    const INIT_CART     = 0x50;   //self::INIT_WCA | self::INIT_CCA;
    const INIT_ALL      = 0xFF;

    public function Initialize_FrontEnd($in_keyId, $in_fc, $INIT_Options = self::INIT_ALL, $in_cfg=0) {
        $this->keyId = $in_keyId;
        parent::Initialize('Front_Ends',$this->keyId,'keyFrontEnds',$in_fc,'keyFacility');
        $this->fc = $in_fc;

        //Get FE Config object
        $qcfg = "SELECT MAX(FE_Config.keyFEConfig)
                FROM FE_Config
                WHERE FE_Config.fkFront_Ends = $this->keyId;";
        $rcfg = mysqli_query($this->dbconnection, $qcfg);
        $this->feconfig_id = ADAPT_mysqli_result($rcfg,0,0);
        $this->feconfig_id_latest = $this->feconfig_id;
        if ($in_cfg != 0) {
            $this->feconfig_id = $in_cfg;
        }
        $this->feconfig = new GenericTable();
        $this->feconfig->Initialize('FE_Config',$this->feconfig_id,'keyFEConfig');

        if ($INIT_Options & self::INIT_CONFIGS) {
            //Get FE Configs
            $qcfg = "SELECT keyFEConfig
                    FROM FE_Config
                    WHERE FE_Config.fkFront_Ends = $this->keyId
                    ORDER BY keyFEConfig DESC;";
            $rcfg = mysqli_query($this->dbconnection, $qcfg);
            $count = 0;
            while($rowcfg = mysqli_fetch_array($rcfg)) {
                $this->feconfig_ids_all[$count] = $rowcfg[0];
                $count += 1;
            }
        }

        if ($INIT_Options & self::INIT_SLN) {
            //Get sln
            $slnid = '';

            if ($this->feconfig->keyId > 0) {
                $qsln = "SELECT MAX(keyId) FROM FE_StatusLocationAndNotes
                         WHERE fkFEConfig = ".$this->feconfig->keyId."
                         AND keyFacility = ". $this->GetValue('keyFacility') ."
                         ORDER BY keyId DESC LIMIT 1;";
                $rsln = mysqli_query($this->dbconnection, $qsln);
                $slnid = ADAPT_mysqli_result($rsln,0,0);
            }

            if ($slnid != '') {
                $this->fesln = new SLN();
                $this->fesln->Initialize_SLN($slnid,$this->GetValue('keyFacility'));
            }
        }

        if ($INIT_Options & self::INIT_LPR) {
            //Get LPR object
            $qlpr = "SELECT FE_Components.keyId, FE_Components.SN,FE_Components.ESN1
                    FROM FE_Components, FE_ConfigLink
                     WHERE FE_ConfigLink.fkFE_Config = ".$this->feconfig->keyId."
                     AND FE_Components.fkFE_ComponentType = 17
                     AND FE_ConfigLink.fkFE_Components = FE_Components.keyId;";
            $rlpr = mysqli_query($this->dbconnection, $qlpr);
            $lpr_id = ADAPT_mysqli_result($rlpr,0,0);
            $this->lpr = new GenericTable();
            $this->lpr->Initialize('FE_Components',$lpr_id,'keyId',$this->fc,'keyFacility');
        }

        if ($INIT_Options & self::INIT_CRYOSTAT) {
            //Get cryostat object
            $qcryo = "SELECT FE_Components.keyId, FE_Components.SN,FE_Components.ESN1
                    FROM FE_Components, FE_ConfigLink
                     WHERE FE_ConfigLink.fkFE_Config = ".$this->feconfig->keyId."
                     AND FE_Components.fkFE_ComponentType = 6
                     AND FE_Components.keyFacility = $this->fc
                     AND FE_ConfigLink.fkFE_Components = FE_Components.keyId;";
            $rcryo = mysqli_query($this->dbconnection, $qcryo);
            $cryo_id = ADAPT_mysqli_result($rcryo,0,0);
            $this->cryostat = new GenericTable();
            $this->cryostat->Initialize('FE_Components',$cryo_id,'keyId',$this->fc,'keyFacility');
        }

        if ($INIT_Options & self::INIT_WCA) {
            $wca_INIT = ($INIT_Options & self::INIT_WCAPARAM) ? WCA::INIT_ALL : WCA::INIT_NONE;

            //Get WCA objects
            for ($band=1; $band<=10; $band++) {
                $qwcas= "SELECT FE_Components.keyId, FE_Components.fkFE_ComponentType, FE_Config.keyFEConfig,
                        FE_Components.SN, FE_Components.Band,
                        FE_ConfigLink.keyId, Front_Ends.keyFrontEnds
                        FROM FE_Components, Front_Ends, FE_ConfigLink, FE_Config
                        WHERE FE_Components.fkFE_ComponentType = 11
                        AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
                        AND FE_ConfigLink.fkFE_Config = FE_Config.keyFEConfig
                        AND FE_Components.Band = $band
                        AND FE_Config.fkFront_Ends = $this->keyId
                        AND FE_Config.keyFEConfig = ".$this->feconfig->keyId.";";
                //echo $qwcas . "<br>";
                $rwcas = mysqli_query($this->dbconnection, $qwcas);
                $wca_id = ADAPT_mysqli_result($rwcas,0,0);
                if ($wca_id != '') {
                    $this->wcas[$band] = new WCA();
                    $this->wcas[$band]->Initialize_WCA($wca_id, $this->fc, $wca_INIT);
                }
            }
        }

        if ($INIT_Options & self::INIT_CCA) {
            $cca_INIT = ($INIT_Options & self::INIT_CCAPARAM) ? CCA::INIT_ALL : CCA::INIT_NONE;

            //Get CCA objects
            for ($band=1; $band<=10; $band++) {
                $qccas= "SELECT FE_Components.keyId, FE_Components.fkFE_ComponentType, FE_Config.keyFEConfig,
                        FE_Components.SN, FE_Components.Band,
                        FE_ConfigLink.keyId, Front_Ends.keyFrontEnds
                        FROM FE_Components, Front_Ends, FE_ConfigLink, FE_Config
                        WHERE FE_Components.fkFE_ComponentType = 20
                        AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
                        AND FE_ConfigLink.fkFE_Config = FE_Config.keyFEConfig
                        AND FE_Components.Band = $band
                        AND FE_Config.fkFront_Ends = $this->keyId
                        AND FE_Config.keyFEConfig = ".$this->feconfig->keyId.";";
                $rccas = mysqli_query($this->dbconnection, $qccas);
                $cca_id = ADAPT_mysqli_result($rccas,0,0);
                if ($cca_id != '') {
                    $this->ccas[$band] = new CCA();
                    $this->ccas[$band]->Initialize_CCA($cca_id, $this->fc, $cca_INIT);
                }
            }
        }
        $this->GetJSONstring();
    }

    public function Initialize_FrontEnd_FromSN($in_sn, $in_fc, $INIT_Options = self::INIT_ALL) {
        $q = "SELECT max(keyFrontEnds) FROM Front_Ends WHERE SN = $in_sn;";
        $r = mysqli_query($this->dbconnection, $q);
        $this->keyId = ADAPT_mysqli_result($r,0,0);
        $this->Initialize_FrontEnd($this->keyId, $in_fc, $INIT_Options);
    }

    public function Initialize_FrontEnd_FromConfig($in_cfg, $in_fc, $INIT_Options = self::INIT_ALL) {
        $q = "SELECT fkFront_Ends FROM FE_Config WHERE keyFEConfig = $in_cfg;";
        $r = mysqli_query($this->dbconnection, $q);
        $this->keyId = ADAPT_mysqli_result($r,0,0);
        $this->Initialize_FrontEnd($this->keyId, $in_fc, $INIT_Options, $in_cfg);
        $this->feconfig_id = $in_cfg;
    }

    public function DisplayTable_BeamPatterns($in_band = "%") {
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
            GROUP BY ScanSetDetails.band, ScanSetDetails.TS;";

        $r = mysqli_query($this->dbconnection, $q);

        $trclass = '';
        while ($row = mysqli_fetch_array($r)) {
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

        for($i=0;$i<count($this->feconfig_ids_all);$i++) {
            $keyconfig = $this->feconfig_ids_all[$i];

            $q = "SELECT band, keyId, TS, notes FROM TestData_header
                  WHERE fkFE_Config = $keyconfig
                  AND fkTestData_Type = 7
                  ORDER BY band ASC, TS ASC";

            //echo "<td>$q</td>";
            $r = mysqli_query($this->dbconnection, $q);
            if (mysqli_num_rows($r) > 0) {
                while ($row = mysqli_fetch_array($r)) {
                    echo "<tr><td width='50px'>$row[0]</td>";
                    echo "<td width='150px'><a href='testdata.php?keyheader=$row[1]&fc=$this->fc'>$row[2]</a></td></tr>";
                }
            }
        }
        echo "</table></div>";
    }

    public function DisplayTable_LOLockTest() {
        echo "<br><br>";
        echo "<div style= 'width:200px'>";
        echo "<table id='table1'>";
        echo "<tr class='alt'><th colspan='2'>LO LOCK TEST</th>";
        echo "<tr><th>Band</th>
                  <th>Date/Time</th>";

        for($i=0;$i<count($this->feconfig_ids_all);$i++) {
            $keyconfig = $this->feconfig_ids_all[$i];
            for ($iBand=0; $iBand<=10; $iBand++) {
                for($i=0;$i<count($this->feconfig_ids_all);$i++) {
                    $keyconfig = $this->feconfig_ids_all[$i];
                    $q="SELECT TestData_header.keyId, TestData_header.TS
                    FROM TestData_header, TEST_LOLockTest_SubHeader
                    WHERE fkFE_Config = $keyconfig
                    AND fkTestData_Type = 57
                    AND TestData_header.Band = $iBand
                    AND TEST_LOLockTest_SubHeader.fkHeader = TestData_header.keyId
                    AND TEST_LOLockTest_SubHeader.fkFacility = $this->fc
                    GROUP BY TestData_header.Band ORDER BY TestData_header.Band ASC;";

                    //echo $q ."<br><br>";
                    $r = mysqli_query($this->dbconnection, $q);
                    $tdhid = ADAPT_mysqli_result($r,0,0);
                    $ts = ADAPT_mysqli_result($r,0,1);
                    if ($tdhid != "") {
                        echo "<tr><td width='50px'>$iBand</td>";
                        echo "<td width='180px'><a href='testdata.php?keyheader=$tdhid&fc=$this->fc'>$ts</a></td></tr>";
                    }
                }
            }

            //echo "<td>$q</td>";
            $r = mysqli_query($this->dbconnection, $q);
            if (mysqli_num_rows($r) > 0) {
                while ($row = mysqli_fetch_array($r)) {
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

    public function DisplayTable_PolAngles() {
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
             GROUP BY TestData_header.band, TestData_header.TS;";

        $r = mysqli_query($this->dbconnection, $q);
        if (mysqli_num_rows($r) > 0) {
            while ($row = mysqli_fetch_array($r)) {
                echo "<tr><td width='50px'>$row[0]</td>";
                echo "<td width='150px'><a href='testdata.php?keyheader=$row[1]&fc=$this->fc'>$row[2]</a></td></tr>";
            }
        }
        echo "</table></div>";
    }

    public function DisplayTable_WorkmanshipAmplitude() {
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
        GROUP BY TestData_header.band, TestData_header.TS;";
        $r = mysqli_query($this->dbconnection, $q);
        if (mysqli_num_rows($r) > 0) {
            while ($row = mysqli_fetch_array($r)) {
                echo "<tr><td width='50px'>$row[0]</td>";
                echo "<td width='150px'><a href='testdata.php?keyheader=$row[1]&fc=$this->fc'>$row[2]</a></td></tr>";
            }
        }
        echo "</table></div>";
    }

    public function DisplayTable_WorkmanshipPhase() {
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
        GROUP BY TestData_header.band, TestData_header.TS;";

        $r = mysqli_query($this->dbconnection, $q);
        if (mysqli_num_rows($r) > 0) {
            while ($row = mysqli_fetch_array($r)) {
                echo "<tr><td width='50px'>$row[0]</td>";
                echo "<td width='50px'>$row[5]</td>";
                echo "<td width='50px'>$row[6]</td>";
                echo "<td width='50px'>$row[4]</td>";
                echo "<td width='150px'><a href='testdata.php?keyheader=$row[1]&fc=$this->fc'>$row[2]</a></td></tr>";
            }
        }
        echo "</table></div>";
    }

    public function DisplayTable_Cryostat() {
        echo "<br><br><h2>CRYOSTAT</h2>";
        echo "<div style= 'width:350px'>";
        echo "<table id='table1'>";

        $q = "SELECT Cryostat_SubHeader.keyId, Cryostat_SubHeader.SN, Cryostat_SubHeader.TS
             FROM Cryostat_SubHeader, Front_Ends
             WHERE Cryostat_SubHeader.SN = Front_Ends.SN
             AND Front_Ends.keyFrontEnds = $this->keyId;";

        $r = mysqli_query($this->dbconnection, $q);
        if (mysqli_num_rows($r) > 0) {
            while ($row = mysqli_fetch_array($r)) {
                echo "<tr><th>Cryostat $row[1]</th>";
                echo "<td width='200px'><a href='https://safe.nrao.edu/php/ntc/cryostat/cryostat.php?keyId=$row[0]'>CLICK TO VIEW DATA</a></td></tr>";
            }
        }
        echo "</table></div>";
    }

    public function DisplayTable_SLN_History() {
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

        $trclass = '';
        for ($i = 0; $i < count ($this->feconfig_ids_all); $i++) {
            $q = "SELECT keyId FROM FE_StatusLocationAndNotes
                  WHERE fkFEConfig = " .$this->feconfig_ids_all[$i]."
                  ORDER BY keyId DESC";
            $r = mysqli_query($this->dbconnection, $q);

            while ($row = mysqli_fetch_array($r)) {
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
                <a href='../FEConfig/ShowFEConfig.php?key=".$this->feconfig_ids_all[$i]."&fc=".$this->GetValue('keyFacility') ."'>".
                $this->feconfig_ids_all[$i] . "</a></td>";

                if (strlen($sln->GetValue('lnk_Data')) > 5) {
                    echo "<td width = '40px'>
                    <a href='".FixHyperLink($sln->GetValue('lnk_Data'))."'>
                    Link
                    </a></td>";
                    //echo "<td width = '40px'>
                    //<a href= '".$sln->GetValue('lnk_Data')."'>LINK
                    //</a></td>";
                }
                if (strlen($sln->GetValue('lnk_Data')) <= 5) {
                    echo "<td></td>";
                }
                echo "<td>" .substr($sln->GetValue('Notes'),0,35) . "</td>";


                echo "</tr>";
            }
        }
        echo "</table></font></div>";
    }


    public function SLN_History_JSON() {
        $outstring = "[";
        $rowcount = 0;

        for ($i = 0; $i < count ($this->feconfig_ids_all); $i++) {
            $q = "SELECT keyId FROM FE_StatusLocationAndNotes
                  WHERE fkFEConfig = " .$this->feconfig_ids_all[$i]."
                  ORDER BY keyId DESC";
            $r = mysqli_query($this->dbconnection, $q);

            while ($row = mysqli_fetch_array($r)) {
                $sln = new SLN();
                $sln->Initialize_SLN($row[0], $this->fc);
            if ($rowcount == 0 ) {
                $outstring .= "{'TS':'".$sln->GetValue('TS')."',";
            }
            if ($rowcount > 0 ) {
                $outstring .= ",{'TS':'".$sln->GetValue('TS')."',";
            }
            $outstring .= "'Location':'".$sln->location."',";
            $outstring .= "'Who':'".     $sln->GetValue('Updated_By')."',";
            $outstring .= "'Status':'".  $sln->status."',";
            $outstring .= "'Config':'".  $this->feconfig_ids_all[$i]."',";
            $outstring .= "'Link':'".    FixHyperLink($sln->GetValue('lnk_Data'))."',";

            $notes = mysqli_real_escape_string($this->dbconnection, $sln->GetValue('Notes'));
            $outstring .= "'Notes':'".   str_replace('"', "'", $notes)."'}";
            $rowcount += 1;
            }
        }
        $outstring .= "]";
        echo $outstring;
    }

    public function DisplayTable_ComponentList($band) {

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
        if ($this->feconfig_id > 0) {
            $searchconfig = $this->feconfig_id;
        }

        $bandLike = ($band) ? "$band" : "0";

        $q = "SELECT CT.keyId AS keyCompType, CT.Description,
              COMP.keyFacility, COMP.SN, COMP.ESN1, COMP.ESN2, COMP.TS, COMP.keyId AS keyComponent, COMP.Link1, COMP.Link2
              FROM FE_ConfigLink AS LINK, FE_Components as COMP, ComponentTypes AS CT
              WHERE LINK.fkFE_Components = COMP.keyId
              AND COMP.fkFE_ComponentType = CT.keyId
              AND COMP.Band LIKE '$bandLike'
              AND LINK.fkFE_Config = $searchconfig
              ORDER BY CT.Description ASC, COMP.SN ASC;";

        $r = mysqli_query($this->dbconnection, $q);

        $trclass = '';
        while ($row = mysqli_fetch_array($r)) {
            $Display = 1;
            if ($band == '') {
                switch($row['keyCompType']) {
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

            if ($Display == '1') {
                $trclass = ($trclass=='class="alt5"' ? 'class="alt4"' : 'class="alt5"');
                echo "<tr $trclass><td align = 'center'>".$row['Description']."</td>";

                $link = "ShowComponents.php?conf=". $row['keyComponent'] . "&fc=" . $row['keyFacility'];
                $SN = "NA";
                if (strlen($row['SN']) > 0) {
                    $SN = $row['SN'];
                }
                echo "<td  align = 'center'><a href='$link'>$SN</a></td>";
                echo "<td  align = 'center'>".$row['ESN1']."</td>";
                echo "<td  align = 'center'>".$row['ESN2']."</td>";
                echo "<td  align = 'center'>".$row['TS']."</td>";

                $Link1 = FixHyperLink($row['Link1']);
                $Link2 = FixHyperLink($row['Link2']);

                $Links = "";
                if (strlen($Link1) > 5) {
                    $Links .= "<a href='$Link1'>SICL</a>";
                    if ($Link2 != '') {
                        $Links .= ",";
                    }

                }
                if (strlen($Link2) > 5) {
                    $Links .= "<a href='$Link2'>CIDL</a>";
                }
                echo "<td  align = 'center'>$Links</td></tr>";
            }
        }
        echo "</table></div>";
    }

    public function DisplayTable_BeamPatternsNoHeader($in_band = "%") {
        $q = "SELECT ScanSetDetails.band, ScanSetDetails.keyId,
        ScanSetDetails.TS, ScanSetDetails.notes, ScanSetDetails.f, FE_Config.keyFEConfig
            FROM ScanSetDetails, Front_Ends, FE_Config
            WHERE ScanSetDetails.fkFE_Config = FE_Config.keyFEConfig
            AND FE_Config.fkFront_Ends = Front_Ends.keyFrontEnds
            AND Front_Ends.keyFrontEnds = $this->keyId
            AND ScanSetDetails.band LIKE '$in_band'
            AND is_deleted <> 1
            GROUP BY ScanSetDetails.band, ScanSetDetails.TS;";


        $r = mysqli_query($this->dbconnection, $q);

        $trclass = '';
        while ($row = mysqli_fetch_array($r)) {

            $trclass = ($trclass=="" ? 'class="alt"' : "");
            echo "<tr $trclass><td width = '10px' align = 'center'>$row[5]</td>";
            echo "<td>Cold PAI</td>";
            if ($in_band == '%') {
                echo "<td width = '30px' align = 'center'>$row[0]</td>";
            }
            echo "<td><a href='../testdata/bp.php?id=$row[1]&band=$row[0]&keyconfig=$row[5]&fc=$this->fc' target = 'blank'>Beam Patterns ($row[4] GHz)</a></td>";
            echo "<td>$row[3]</td>";
            echo "<td>$row[2]</td></tr>";
        }
    }

    public function DisplayTable_PAITestData_Summary($band = "%") {

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

        $r = mysqli_query($this->dbconnection, $q);

        $prev_data_status = -1;
        $prev_fe_config = -1;
        $trclass = '';
        while ($row = mysqli_fetch_array($r)) {
            if ($prev_data_status != $row[6] || $prev_fe_config != $row[3]) {
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
