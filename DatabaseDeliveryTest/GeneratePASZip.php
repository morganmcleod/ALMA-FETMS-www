<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_libraries . '/pclzip/pclzip.lib.php');

include('CreateXMLDoc.php');

$frontend_sn=$_GET['frontend'];
$warmconf_sn=$_GET['warmconf'];

$dir= 'PASData/';
foreach(glob($dir.'*.*') AS $f)
{
	unlink($f);
}

createfiles($frontend_sn,$warmconf_sn);

header("Content-type: application/zip");
header("Content-disposition: attachment; filename=PAS-Data.zip");

$archivename= "PASData/PAS-Data.zip";

$archive=new PclZip($archivename);

$a_list=$archive->create("PASData/");
if($a_list == 0)
{
	die("error" .$archive->errorInfo(true));
}

readfile("PASData/PAS-Data.zip");

?>