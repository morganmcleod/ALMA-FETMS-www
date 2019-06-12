<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);
$dbconnection = site_getDbConnection();

$NumArgs = strlen($_REQUEST['query']);
$SearchVal = '%';

if (isset($_REQUEST['query'])){
	$SearchVal = $_REQUEST['query'];
}
if ($NumArgs > 0){
		$q = "SELECT Description, keyId FROM ComponentTypes
		WHERE Description LIKE '$SearchVal%'
		ORDER BY Description ASC;";
}
else{
		$q = "SELECT Description, keyId FROM ComponentTypes
		WHERE Description in ('CCA', 'WCA')
		ORDER BY Description ASC;";
}
$r = mysqli_query($dbconnection, $q);

if ($NumArgs == 0){
	$jsonstring = '{"sucess":true,"records":[{"stateName":"Front End", "stateCode":"100"}';
	while ($row=mysqli_fetch_array($r)){
		$jsonstring .= ',{"stateName":"' . $row[0] . '", "stateCode":"' . $row[1] . '"}';
	}
	$jsonstring .= ']}';
}

if ($NumArgs >= 1){
	$cnt = 0;
	$jsonstring = '{"sucess":true,"records":[';
	while ($row=mysqli_fetch_array($r)){
		if ($cnt == 0){
			$jsonstring .= '{"stateName":"' . $row[0] . '", "stateCode":"' . $row[1] . '"}';
		}
		if ($cnt > 0){
			$jsonstring .= ',{"stateName":"' . $row[0] . '", "stateCode":"' . $row[1] . '"}';
		}
		$cnt += 1;
	}
	$jsonstring .= ']}';
}

echo $jsonstring;
?>