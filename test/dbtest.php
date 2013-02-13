<?php

require_once('../SiteConfig.php');
require_once($site_dbConnect);
require_once($site_config_main);

echo "DB Test<br><br>";
echo "site_root=$site_root<br>";
echo "site_hostname=$site_hostname<br>";
echo "dbserver=$dbserver<br>";
echo "dbname=$dbname<br><br>";

$q = "SELECT keyId, Description FROM Locations ORDER BY keyId ASC;";
echo "Query: $q<br><br>";
$r = @mysql_query($q,$db);

$numrows = @mysql_num_rows($r);

echo "Number of records: $numrows<br><br>";

$r = @mysql_query($q,$db);

while ($row = @mysql_fetch_array($r)){
	echo "keyId: $row[0], Description: $row[1]<br>";

}

?>