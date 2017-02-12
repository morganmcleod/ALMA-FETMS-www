<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.cca.php');
require_once($site_classes . '/class.logger.php');

$fname = "FrontEndControlDLL.ini";
header("Content-type: text/plain");
header("Content-Disposition: attachment; filename=$fname");
header("Pragma: no-cache");
header("Expires: 0");

$fc = $_REQUEST['fc'];
$id = $_REQUEST['keyId'];

$cca = new CCA();
$cca->Initialize_CCA($id, $fc, CCA::INIT_ALL);

$l = new Logger('exportINI.txt');
$l->WriteLogFile('test');

$band = $cca->GetValue('Band');
$sn   = ltrim($cca->GetValue('SN'),'0');
$esn  = $cca->GetValue('ESN1');

echo "[~ColdCart$band-$sn]\r\n";
$description = "Band $band SN$sn";
echo "Description=$description\r\n";
echo "Band=$band\r\n";
echo "SN=$sn\r\n";
echo "ESN=$esn\r\n";

$mstring = "";

switch ($cca->GetValue('Band')) {
    case 3:
        echo "MagnetParams=0\r\n";
        break;
    case 4:
        echo "MagnetParams=0\r\n";
        break;
    case 6:
        echo "MagnetParams=1\r\n";
        $mstring = "MagnetParam01=" . number_format($cca->MixerParams[0]->lo,3) . ",";
        $mstring .= number_format($cca->MixerParams[0]->imag01,2) . ",";
        $mstring .= "0.00,";
        $mstring .= number_format($cca->MixerParams[0]->imag11,2) . ",";
        $mstring .= "0.00\r\n";
        break;
    default:
        //Get number of magnet params
        $im01 = 'x';
        $im02 = 'x';
        $im11 = 'x';
        $im12 = 'x';
        $numMags = 0;
        for ($ic = 0; $ic < count($cca->MixerParams); $ic++){
            if (($cca->MixerParams[$ic]->imag01 != $im01)
            || ($cca->MixerParams[$ic]->imag02 != $im02)
            || ($cca->MixerParams[$ic]->imag11 != $im11)
            || ($cca->MixerParams[$ic]->imag12 != $im12))
            {
            $im01 = $cca->MixerParams[$ic]->imag01;
            $im02 = $cca->MixerParams[$ic]->imag02;
            $im11 = $cca->MixerParams[$ic]->imag11;
            $im12 = $cca->MixerParams[$ic]->imag12;
            $numMags += 2;
            }
        }

        echo "MagnetParams=$numMags\r\n";
        $im01 = 'x';
        $im02 = 'x';
        $im11 = 'x';
        $im12 = 'x';
        $magcount = 1;
        $mstring = '';
        for ($ic = 0; $ic < count($cca->MixerParams); $ic++){
            $imLO = $cca->MixerParams[$ic]->lo;
            $magcountStr = "MagnetParam0" . $magcount;
            if ($magcount > 9){
                $magcountStr = "MagnetParam" . $magcount;
            }

            //Check to see if this set of Imag values is unique
            if (($cca->MixerParams[$ic]->imag01 != $im01)
            || ($cca->MixerParams[$ic]->imag02 != $im02)
            || ($cca->MixerParams[$ic]->imag11 != $im11)
            || ($cca->MixerParams[$ic]->imag12 != $im12))
            {
                if ($ic > 0){
                    $mstring .= "$magcountStr=" . number_format($cca->MixerParams[$ic - 1]->lo,3) . ",";
                    $mstring .= number_format($im01,2) . ",";
                    $mstring .= number_format($im02,2) . ",";
                    $mstring .= number_format($im11,2) . ",";
                    $mstring .= number_format($im12,2) . "\r\n";
                    $magcount += 1;
                    $magcountStr = "MagnetParam0" . $magcount;
                    if ($magcount > 9){
                        $magcountStr = "MagnetParam" . $magcount;
                    }
                }
                $im01 = $cca->MixerParams[$ic]->imag01;
                $im02 = $cca->MixerParams[$ic]->imag02;
                $im11 = $cca->MixerParams[$ic]->imag11;
                $im12 = $cca->MixerParams[$ic]->imag12;

                $mstring .= "$magcountStr=" . number_format($cca->MixerParams[$ic]->lo,3) . ",";
                $mstring .= number_format($cca->MixerParams[$ic]->imag01,2) . ",";
                $mstring .= number_format($cca->MixerParams[$ic]->imag02,2) . ",";
                $mstring .= number_format($cca->MixerParams[$ic]->imag11,2) . ",";
                $mstring .= number_format($cca->MixerParams[$ic]->imag12,2) . "\r\n";
                $magcount += 1;
            }
            //Put the last string in
            if ($ic >= count($cca->MixerParams) - 1){
                $mstring .= "$magcountStr=" . number_format($cca->MixerParams[$ic]->lo,3) . ",";
                $mstring .= number_format($cca->MixerParams[$ic]->imag01,2) . ",";
                $mstring .= number_format($cca->MixerParams[$ic]->imag02,2) . ",";
                $mstring .= number_format($cca->MixerParams[$ic]->imag11,2) . ",";
                $mstring .= number_format($cca->MixerParams[$ic]->imag12,2) . "\r\n";
            }
        }
}

echo $mstring;


echo "MixerParams=" . (count($cca->MixerParams)) . "\r\n";

for ($i = 0; $i  < (count($cca->MixerParams)); $i++){
    if ($i < 9){
        $mstring = "MixerParam0" . ($i+1) ."=";
    }
    if ($i >= 9){
        $mstring = "MixerParam" . ($i+1) ."=";
    }
    $mstring .= number_format($cca->MixerParams[$i]->lo,3) . ",";
    $mstring .= number_format($cca->MixerParams[$i]->vj01,3) . ",";
    $mstring .= number_format($cca->MixerParams[$i]->vj02,3) . ",";
    $mstring .= number_format($cca->MixerParams[$i]->vj11,3) . ",";
    $mstring .= number_format($cca->MixerParams[$i]->vj12,3) . ",";
    $mstring .= number_format($cca->MixerParams[$i]->ij01,2) . ",";
    $mstring .= number_format($cca->MixerParams[$i]->ij02,2) . ",";
    $mstring .= number_format($cca->MixerParams[$i]->ij11,2) . ",";
    $mstring .= number_format($cca->MixerParams[$i]->ij12,2) . "\r\n";
    echo $mstring;
}

$numpa=4;
if ($cca->GetValue('Band') == 9){
    $numpa = 2;
}
$ij_precision = 2;
if ($cca->GetValue('Band') == 3){
    $ij_precision = 3;
}


echo "PreampParams=" . (count($cca->PreampParams)) . "\r\n";

for ($i = 0; $i  < (count($cca->PreampParams)); $i++){
    if ($i < 9){
        $mstring = "PreampParam0" . ($i+1) ."=";
    }
    if ($i >= 9){
        $mstring = "PreampParam" . ($i+1) ."=";
    }
    $mstring .= number_format($cca->PreampParams[$i]->GetValue('FreqLO'),3) . ",";
    $mstring .= $cca->PreampParams[$i]->GetValue('Pol') . ",";
    $mstring .= $cca->PreampParams[$i]->GetValue('SB') . ",";
    $mstring .= number_format($cca->PreampParams[$i]->GetValue('VD1'),2) . ",";
    $mstring .= number_format($cca->PreampParams[$i]->GetValue('VD2'),2) . ",";
    $mstring .= number_format($cca->PreampParams[$i]->GetValue('VD3'),2) . ",";
    $mstring .= number_format($cca->PreampParams[$i]->GetValue('ID1'),$ij_precision) . ",";
    $mstring .= number_format($cca->PreampParams[$i]->GetValue('ID2'),$ij_precision) . ",";
    $mstring .= number_format($cca->PreampParams[$i]->GetValue('ID3'),$ij_precision) . ",";
    $mstring .= number_format($cca->PreampParams[$i]->GetValue('VG1'),2) . ",";
    $mstring .= number_format($cca->PreampParams[$i]->GetValue('VG2'),2) . ",";
    $mstring .= number_format($cca->PreampParams[$i]->GetValue('VG3'),2) . "\r\n";
    echo $mstring;
}

unset($c);
echo "\r\n\r\n\r\n";

?>
