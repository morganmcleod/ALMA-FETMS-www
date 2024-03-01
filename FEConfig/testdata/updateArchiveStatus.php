<?php

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_dbConnect);
require_once($site_classes . "/class.logger.php");
require_once($site_classes . '/class.testdata_header.php');

$fc = $_REQUEST['fc'] ?? '40';
$archiveparam = $_REQUEST['archive'] ?? false;
$keyHeader = $_REQUEST['keyheader'] ?? false;

$archive = filter_var($archiveparam, FILTER_VALIDATE_BOOLEAN);
$td = new TestData_header($keyHeader, $fc);
$td->changeArchiveStatus($archive);

header('Location: ' . $_SERVER['HTTP_REFERER']);
