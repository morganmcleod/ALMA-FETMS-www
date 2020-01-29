<?php

require_once('../SiteConfig.php');
require_once($site_config_main);
require_once($site_dbConnect);
$dbconnection = site_getDbConnection();

echo "DB Test<br><br>";
echo "site_root=$site_root<br>";
echo "site_hostname=$site_hostname<br>";
echo "dbserver=$dbserver<br>";
echo "dbname=$dbname<br><br>";

$q = "SELECT keyId, Description FROM Locations ORDER BY keyId ASC;";
echo "Query: $q<br><br>";
$r = mysqli_query($dbconnection, $q);

$numrows = mysqli_num_rows($r);

echo "Number of records: $numrows<br><br>";

$r = mysqli_query($dbconnection, $q);

while ($row = mysqli_fetch_array($r)){
	echo "keyId: $row[0], Description: $row[1]<br>";

}

?>