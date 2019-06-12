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

// This query can take advantage of indexes on Front_Ends.keyFrontEnds and TestData_Types.keyId

$q="SELECT TDH.keyId, TDH.keyFacility, TDH.Band, TDH.TS, TDH.fkTestData_Type, 
           TDH.Notes, TDH.fkFE_Components, TDH.DataSetGroup, 
           Front_Ends.SN, Front_Ends.keyFrontEnds, TestData_Types.Description
    FROM TestData_header AS TDH, 
         FE_Config AS FCF,
         Front_Ends, TestData_Types 
    WHERE TDH.fkDataStatus LIKE '$TestStatus'
    AND TDH.fkTestData_Type LIKE '$fkTestData_Type'
    AND TDH.fkFE_Config = FCF.keyFEConfig
    AND FCF.fkFront_Ends = Front_Ends.keyFrontEnds
    AND TDH.fkTestData_Type = TestData_Types.keyId
    ORDER BY TS DESC LIMIT 200;";

$r = mysqli_query($link, $q);

$outstring = "[";
$rowcount = 0;

while ($row= mysqli_fetch_array($r)){
    $keyId = $row['keyId'];
    $keyFacility = $row['keyFacility'];
    $Band = $row['Band'];
    $TS = $row['TS'];
    $fkTestData_Type = $row['fkTestData_Type'];
    $Notes = $row['Notes'];
    $DataSetGroup = $row['DataSetGroup'];
    $fesn = $row['SN'];
    $keyFrontEnd = $row['keyFrontEnds'];
    $TestType = $row['Description'];

    if ($fesn == ''){
        $c = new FEComponent();
        $c->Initialize_FEComponent($row['fkFE_Components'],$keyFacility);
        $Band = $c->GetValue('Band');
        $fesn = $c->FESN;
        unset($c);
    }

    if ($rowcount != 0 )
        $outstring .= ",";
    
    $outstring .= "{'SN':'$fesn',";
    
    $outstring .= "'Band':'$Band',";
    $outstring .= "'TS':'$TS',";
    $outstring .= "'keyId':'$keyId',";
    $outstring .= "'TestType':'$TestType',";
    $outstring .= "'fkTestData_Type':'$fkTestData_Type',";
    $outstring .= "'keyFrontEnd':'$keyFrontEnd',";
    $outstring .= "'DataSetGroup':'$DataSetGroup',";
    $outstring .= "'Notes':'". mysqli_real_escape_string($link, stripslashes($Notes))."',";
    $outstring .= "'keyFacility':'$keyFacility'}";
    $rowcount += 1;
}

$outstring .= "]";
echo $outstring;
?>
