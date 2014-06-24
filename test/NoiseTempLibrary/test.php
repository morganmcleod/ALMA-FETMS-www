<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);

function testDataRet() {
	require(site_get_config_main());
	$db = site_getDbConnection();
	
	$qkeyId = "SELECT keyId FROM TestData_header WHERE Band = 3 AND kTestData_Type = 58 AND DataSetGroup = 0";
	$rkeyId = @mysql_query($qkeyId, $db);
	$rowkeyId = @mysql_query($qkeyId, 0, 0);
	
	echo $rowkeyId;
}
?>