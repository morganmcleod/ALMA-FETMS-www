<?php
include('/home/safe.nrao.edu/active/php/ntc/libraries/pclzip/pclzip.lib.php');

$filearr[0] = 'Names/names1.txt';
$filearr[1] = 'Names/names2.txt';
$folderpath = '../PASData/';
$archivename = $folderpath . "names.zip";

header("Content-type: application/zip");
header("Content-disposition: attachment; filename=names.zip");

$archive = new PclZip($archivename);
  $v_list = $archive->create("Names/");
if ($v_list == 0) {
    die("Error : ".$archive->errorInfo(true));
  }
readfile("/home/safe.nrao.edu/active/php/ntc/PASData/names.zip");
// header("Content-type: application/zip");
//header("Content-disposition: attachment; filename=names.zip");

?>