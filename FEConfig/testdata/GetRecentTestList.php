<?php
// called from dbGridRecentTestList.js
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_dbConnect);

$TestStatus = $_REQUEST['type'];
$SelectorType = $_REQUEST['stype'];

$fkTestData_Type = "%";

if ($TestStatus == 'All'){
    $TestStatus = "%";
    $fkTestData_Type = "%";
}

if ($SelectorType == 2){
    $TestStatus = "%";
    $fkTestData_Type = $_REQUEST['type'];
}


$q = "SELECT keyId, keyFacility, Band, TS, fkTestData_Type, fkFE_Config, Notes, fkFE_Components, DataSetGroup
    FROM TestData_header
    WHERE fkDataStatus LIKE '$TestStatus'
    AND fkTestData_Type LIKE '$fkTestData_Type'
    ORDER BY TS DESC LIMIT 200;";
$r = @mysql_query($q,$db);


$outstring = "[";
$rowcount = 0;


while ($row= @mysql_fetch_array($r)){
    $keyId = $row['keyId'];
    $keyFacility = $row['keyFacility'];
    $Band = $row['Band'];
    $TS = $row['TS'];
    $Notes = $row['Notes'];
    $fkTestData_Type = $row['fkTestData_Type'];
    $DataSetGroup = $row['DataSetGroup'];

    //Get Test Data type
    $qfd = "SELECT Description FROM TestData_Types
            WHERE keyId = ".$row[4].";";

    $rfd = @mysql_query($qfd,$db);
    $TestType = @mysql_result($rfd,0,0);

    //Get FE SN
    $qfe = "SELECT Front_Ends.SN, Front_Ends.keyFrontEnds
            FROM Front_Ends,FE_Config
            WHERE FE_Config.fkFront_Ends = Front_Ends.keyFrontEnds
            AND FE_Config.keyFEConfig = ".$row['fkFE_Config'].";";
    $rfe = @mysql_query($qfe,$db);
    $fesn = @mysql_result($rfe,0,0);
    $keyFrontEnd = @mysql_result($rfe,0,1);

    if ($fesn == ''){
        $c = new FEComponent();
        $c->Initialize_FEComponent($row['fkFE_Components'],$keyFacility);
        $Band = $c->GetValue('Band');
        $fesn = $c->FESN;
        unset($c);
    }

    if ($rowcount == 0 ){
        $outstring .= "{'SN':'$fesn',";
    }
    if ($rowcount > 0 ){
        $outstring .= ",{'SN':'$fesn',";
    }

    $outstring .= "'Band':'$Band',";
    $outstring .= "'TS':'$TS',";
    $outstring .= "'keyId':'$keyId',";
    $outstring .= "'TestType':'$TestType',";
    $outstring .= "'fkTestData_Type':'$fkTestData_Type',";
    $outstring .= "'keyFrontEnd':'$keyFrontEnd',";
    $outstring .= "'DataSetGroup':'$DataSetGroup',";

    $outstring .= "'Notes':'". @mysql_real_escape_string(stripslashes($Notes))."',";
    $outstring .= "'keyFacility':'$keyFacility'}";

    $rowcount += 1;
}


$outstring .= "]";


echo $outstring;



?>