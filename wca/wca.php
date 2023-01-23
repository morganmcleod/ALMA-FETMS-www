<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.wca.php');

include('header_js.php');

$fc = isset($_REQUEST['fc']) ? $_REQUEST['fc'] : '';
$keyWCA = isset($_REQUEST['keyId']) ? $_REQUEST['keyId'] : '';

$wca = new WCA($keyWCA, $fc, WCA::INIT_ALL);
$wca->RequestValues_WCA();

// if ((isset($_REQUEST['submit_datafile'])) || (isset($_REQUEST['submitted']))) {

//     $ret = false;

//     if ($wca->keyId == '') {
//         $wca->NewRecord_WCA();
//         $ret = $wca->RequestValues_WCA();
//     }

//     $wca->Update_WCA();
//     if ($ret)
//         echo "<font size = '+10'>Record Updated</font><br><br>";
//     else {
//         $errorstring = "";
//         if (count($wca->ErrorArray) > 0) {
//             for ($i = 0; $i < count($wca->ErrorArray); $i++) {
//                 $errorstring .= $wca->ErrorArray[$i] . '<br><br>';
//             }
//         }
//         echo "<font size = '+3'>$errorstring</font><br><br>";
//     }
// }

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