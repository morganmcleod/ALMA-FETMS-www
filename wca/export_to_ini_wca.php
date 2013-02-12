<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.wca.php');

$fc = $_REQUEST['fc'];
$id = $_REQUEST['keyId'];

$wca = new WCA();
$wca->Initialize_WCA($id,$fc);

$fname = "FrontEndControlDLL.ini";
header("Content-type: application/x-msdownload");
header("Content-Disposition: attachment; filename=$fname");
header("Pragma: no-cache");
header("Expires: 0");

$band = $wca->GetValue('Band');
$sn   = ltrim($wca->GetValue('SN'),'0');
$esn  = $wca->GetValue('ESN1');
$description = "Description=Band $band SN$sn";
if ($esn == $wca->keyId){
    $esn = '';
}
echo "[~WCA$band-$sn]\r\n";
echo "$description\r\n";
echo "Band=$band\r\n";
echo "SN=$sn\r\n";
echo "ESN=$esn\r\n";
echo "FLOYIG=" . $wca->_WCAs->GetValue('FloYIG') . "\r\n";
echo "FHIYIG=" . $wca->GetValue('FhiYIG') . "\r\n";

//Get lowest LO
$lowlo = "0.000";
switch ($band){
    case 3:
        $lowlo = "92.00";
        break;
    case 4:
        $lowlo = "133.00";
        break;
    case 6:
        $lowlo = "221.00";
        break;
    case 7:
        $lowlo = "283.00";
        break;
    case 8:
        $lowlo = "393.00";
        break;
    case 9:
        $lowlo = "614.00";
        break;
}

echo "LOParams=1\r\n";
$mstring = "LOParam01=$lowlo";
$mstring .= ",1.00,1.00,";

//TODO: number_format calls are blowing up below.  Why?
$mstring .= number_format($wca->_WCAs->GetValue('VG0'),2) . ",";
$mstring .= number_format($wca->_WCAs->GetValue('VG1'),2) . "\r\n";
echo $mstring;

unset($w);
echo "\r\n\r\n\r\n";

?>
