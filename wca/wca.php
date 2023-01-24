<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.wca.php');

include('header_js.php');

$keyWCA = $_REQUEST['keyId'] ?? '';
$wca = new WCA($keyWCA, $fc, WCA::INIT_ALL);

if ((isset($_REQUEST['submit_datafile'])) || (isset($_REQUEST['submitted']))) {
    if ($wca->keyId == '') $wca = WCA::NewRecord_WCA();
}

$wca->RequestValues_WCA();
$wca->DisplayData_WCA();
unset($wca);

?>
<div class='footer' style="margin-top:20px;">
    <div style="margin-left:30px;">
        <?php
        include "../FEConfig/footer.php";
        ?>
    </div>
</div>