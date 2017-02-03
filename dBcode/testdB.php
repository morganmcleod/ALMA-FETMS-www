<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);
require_once($site_classes . '/class.spec_functions.php');
require_once($site_dBcode . '/../dBcode/ifspectrumdb.php');

require(site_get_config_main());

$db = site_getDbConnection();

$band = 3;
$FEid = 61;
$new_spec = new Specifications();
$dbpull = new IFSpectrumDB();

$qTDH = "SELECT TestData_header.keyId, TestData_header.TS
FROM TestData_header, FE_Config
WHERE TestData_header.DataSetGroup = $band
AND TestData_header.fkTestData_Type = 7
AND TestData_header.Band = " . $Band . "
AND TestData_header.fkFE_Config = FE_Config.keyFEConfig
AND FE_Config.fkFront_Ends = $FEid
ORDER BY TestData_header.keyId ASC";

$rTDH = @mysql_query($qTDH,$db);
$count = 0;
while($rowTDH = @mysql_fetch_Array($rTDH)){
	echo $rowTDH['keyId'];
	$TDHkeys[$count] = $rowTDH['keyId'];
	$TS = $rowTDH['TS'];
	$count += 1;
}
echo count($TDHkeys), $TS;

?>