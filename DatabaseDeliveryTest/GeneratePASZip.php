<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_config_main);
require_once($site_libraries . '/pclzip/pclzip.lib.php');

$output_dir= $main_write_directory . 'PASData/';

//Create output directory if it doesn't exist.
if (!file_exists($output_dir)) {
	mkdir($output_dir);
}
		
// delete any old files there:
foreach(glob($output_dir.'*.*') AS $f) {
	unlink($f);
}
		
include('CreateXMLDoc.php');

$frontend_sn=$_GET['frontend'];
$warmconf_sn=$_GET['warmconf'];

createfiles($frontend_sn,$warmconf_sn);

header("Content-type: application/zip");
header("Content-disposition: attachment; filename=PAS-Data.zip");

$archivename = $output_dir . "PAS-Data.zip";

$archive=new PclZip($archivename);

$a_list=$archive->create($output_dir, PCLZIP_OPT_REMOVE_PATH, $main_write_directory);
if($a_list == 0) {
	die("error" .$archive->errorInfo(true));
}

readfile($archivename);

?>