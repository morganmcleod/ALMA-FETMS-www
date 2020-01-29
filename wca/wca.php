<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.wca.php');

include('header_js.php');

$fc = isset($_REQUEST['fc']) ? $_REQUEST['fc'] : '';
$keyWCA = isset($_REQUEST['keyId']) ? $_REQUEST['keyId'] : '';

$wca = new WCA;
$wca->Initialize_WCA($keyWCA, $fc, WCA::INIT_ALL);
$wca->RequestValues_WCA();

if ((isset($_REQUEST['submit_datafile'])) || (isset($_REQUEST['submitted']))){

    if ($wca->keyId == ''){
        $wca->NewRecord_WCA();
        $wca->RequestValues_WCA();
    }

    $wca->Update_WCA();
    echo "<font size = '+10'>Record Updated</font><br><br>";
//     echo '<meta http-equiv="Refresh" content="1;url=wca.php?fc='.$wca->fc.'&keyId='.$wca->keyId.'">';
}

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