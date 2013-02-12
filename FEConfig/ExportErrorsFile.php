<?php
$fname = "Errors.txt";
header("Content-type: application/x-msdownload");
header("Content-Disposition: attachment; filename=$fname");
header("Pragma: no-cache");
header("Expires: 0");

$errors = $_REQUEST['e'];
$uploadtime = date('r');

echo $uploadtime . "\r\n";
echo "There were errors reported in the upload\r\n";
echo "$errors\r\n";

?>