<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_dbConnect);
$dbconnection = site_getDbConnection();

$q = "SELECT Initials, Name FROM Users ORDER BY Initials ASC;";
$r = mysqli_query($dbconnection, $q);

$jsonstring = '{"sucess":true,"records":[{"UserName":" ", "UserCode":" "}';
while ($row=mysqli_fetch_array($r)){
	$jsonstring .= ',{"UserName":"' . $row[1] . '", "UserCode":"' . $row[0] . '"}';
}
$jsonstring .= ']}';
echo $jsonstring;

?>