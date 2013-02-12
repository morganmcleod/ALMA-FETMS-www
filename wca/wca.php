<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.wca.php');

include('header_js.php');

$fc = $_REQUEST['fc'];
$keyWCA = $_REQUEST['keyId'];

$wca = new WCA;
$wca->Initialize_WCA($keyWCA,$fc);
$wca->RequestValues_WCA();

if ((isset($_REQUEST['submit_datafile'])) || (isset($_REQUEST['submitted']))){

    if ($wca->keyId == ''){
        $wca->NewRecord_WCA();
        $wca->RequestValues_WCA();
    }

    $wca->Update_WCA();
    echo "<font size = '+10'>Record Updated</font><br><br>";
    echo '<meta http-equiv="Refresh" content="1;url=wca.php?fc='.$wca->fc.'&keyId='.$wca->keyId.'">';
}

$wca->DisplayData_WCA();
unset($wca);

echo "<br><br><br><br><br><br><br><br><br><br><br><br>";
?>