<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);

$key=$_GET['config'];

$getbands=mysql_query("SELECT Band FROM FE_Components WHERE
                       keyId=ANY(Select fkFE_Components FROM FE_ConfigLink WHERE fkFE_Config='$key')
                       AND (Band != '0' AND Band IS NOT NULL)
                       GROUP BY Band")
or die("Could not get data" .mysql_error());

while($bands=mysql_fetch_array($getbands))
{
		$ba=$bands['Band'];
		$BandArray[]=$ba;
}

echo json_encode($BandArray);

?>