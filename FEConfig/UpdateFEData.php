<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);

$cansn=$_POST['canser'];
$url=$_POST['url'];
$notes=$_POST['notes'];
$keyFE=$_POST['key'];
$facility=40;

if(strpos($url,"http") === false){
	$url = "http://$url";
}

//get front end config value
$updateFE=mysql_query("UPDATE Front_Ends SET ESN='$cansn',Docs='$url',Description='$notes'
					  WHERE keyFrontEnds=(SELECT fkFront_Ends FROM FE_Config WHERE
					  keyFEConfig='$keyFE' AND FE_Config.keyFacility='$facility')
					  AND Front_Ends.keyFacility='$facility'")
or die("could not update FE" .mysql_error());

echo "{success:true}";

?>