<?php

//called from AddNotesEtc.js
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);

$notes=$_POST['notes'];
$locval=$_POST['locval'];
$statval=$_POST['statval'];
$updatedby=$_POST['updatedby'];
$keyFE=$_POST['key'];
$urls=$_POST['url'];
$facility=$_POST['facility'];

if(strpos($urls,"http") === false){
	$urls = "http://$urls";
}

//insert record into StatusLocationAndNotes table

 mysql_query("INSERT INTO FE_StatusLocationAndNotes(fkFEConfig,keyFacility,fkLocationNames,
 			  fkStatusType,Notes,Updated_By,lnk_Data)
			  Values('$keyFE','$facility','$locval','$statval','$notes','$updatedby','$urls')")
 or die("Could not insert into StatusLocationAndNotes" .mysql_error());

echo "{success:true}"

?>

