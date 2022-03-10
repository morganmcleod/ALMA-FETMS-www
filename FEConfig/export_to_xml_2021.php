<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.wca.php');
require_once($site_classes . '/class.cca.php');

$fc = $_REQUEST['fc'];
$id = $_REQUEST['keyId'];
$comptype = $_REQUEST['comptype'];

switch ($comptype) {
    case 11:
        //WCA
        $wca = new WCA();
        $wca->Initialize_WCA($id, $fc, WCA::INIT_ALL);
        $esn = $wca->GetValue('ESN1');
        if ($esn) {
            $esn = hexdec($esn);
            $fname = "$esn.xml";
        } else {
            $band = $wca->GetValue(GetValue('Band'));
            $sn = $wca->GetValue(GetValue('SN'));
            $fname = "WCA$band-$sn";
        }
        header("Content-type: text/xml");
        header("Content-Disposition: attachment; filename=$fname");
        header("Pragma: no-cache");
        header("Expires: 0");
        echo $wca->GetXmlFileContent();
        break;
        
    case 20:
        //CCA
        $cca = new CCA();
        $cca->Initialize_CCA($id, $fc, CCA::INIT_ALL);
        $esn = $cca->GetValue('ESN1');
        if ($esn) {
            $esn = hexdec($esn);
            $fname = "$esn.xml";
        } else {
            $band = $cca->GetValue(GetValue('Band'));
            $sn = $cca->GetValue(GetValue('SN'));
            $fname = "CCA$band-$sn";
        }
        header("Content-type: text/xml");
        header("Content-Disposition: attachment; filename=$fname");
        header("Pragma: no-cache");
        header("Expires: 0");
        echo $cca->GetXmlFileContent();
        break;
    
    default:
        header("Content-type: text/plain");
        header("Content-Disposition: attachment");
        header("Pragma: no-cache");
        header("Expires: 0");
        echo "no data";
        break;
}
?>
