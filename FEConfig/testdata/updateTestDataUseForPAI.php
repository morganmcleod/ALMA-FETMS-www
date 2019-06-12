<?php

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . "/class.logger.php");
require_once($site_dbConnect);
require_once($site_config_main);

$action = $_REQUEST['action'];
$keyTDH = $_REQUEST['key'];
$checked  = $_REQUEST['checked'];

if ($action == 'checkbox') {
//     $logger = new Logger('updateTestDataUseForPAI.txt','a');

    $q = "UPDATE TestData_header set UseForPAI=";
    if ($checked == 'true')
        $q .= "1";
    else
        $q .= "0";

    $q .= " WHERE keyFacility=$fc AND keyId=$keyTDH;";
    $r = mysqli_query($link, $q);

//     $logger -> WriteLogFile("action=checkbox, key=$keyTDH, checked=$checked, result=$r");
}

?>
