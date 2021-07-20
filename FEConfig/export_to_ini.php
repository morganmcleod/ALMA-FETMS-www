<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.frontend.php');

$fname = "FrontEndControlDLL.ini";
header("Content-type: text/plain");
header("Content-Disposition: attachment; filename=$fname");
header("Pragma: no-cache");
header("Expires: 0");

//$fc = $_REQUEST['fc'];
$fc = 40;

$Cryostat_keyId = $_REQUEST['keyId'];
$id = $_REQUEST['keyId'];
$get_cca = isset($_REQUEST['cca']) ? $_REQUEST['cca'] : 0;
$get_wca = isset($_REQUEST['wca']) ? $_REQUEST['wca'] : 0;
$get_all = isset($_REQUEST['all']) ? $_REQUEST['all'] : 0;

if ($get_wca == 1) {
    $wca_id[0] = $id;
}
if ($get_cca == 1) {
    $cca_id[0] = $id;
}

if ($get_all == 1) {
    $get_cca = 1;
    $get_wca = 1;
}


$fe = new FrontEnd();
$fe->Initialize_FrontEnd_FromConfig($id, $fc, FrontEnd::INIT_ALL);

$lprSN = 1;
if ($fe->lpr->GetValue('SN') != '') {
    $lprSN = ltrim($fe->lpr->GetValue('SN'), '0');
}

if ($get_all == 1) {
    echo "[configuration]\r\n";
    echo "; options to select which configuration data is loaded\r\n";
    echo "providerCode=$fc\r\n";
    echo "configId=" . ltrim($fe->GetValue('SN'), '0') . "\r\n\r\n";

    echo "[~Configuration$fc-" . ltrim($fe->GetValue('SN'), '0')  . "]\r\n";
    echo "Description=FE-" . ltrim($fe->GetValue('SN'), '0') . " bands ";
    for ($i = 1; $i <= 10; $i++) {
        if (isset($fe->ccas[$i]->keyId) && $fe->ccas[$i]->keyId != '') {
            echo $fe->ccas[$i]->GetValue('Band');
        }
    }
    echo ", WCAs";
    for ($i = 1; $i <= 10; $i++) {
        if (isset($fe->wcas[$i]->keyId) && $fe->wcas[$i]->keyId != '') {
            echo $fe->wcas[$i]->GetValue('Band');
        }
    }
    echo "\r\n";
    echo "FrontEnd=$fc," . ltrim($fe->GetValue('SN'), '0')  . "\r\n";
    echo "CartAssembly=\r\n";
    echo "TS=" . date('Y-m-d H:i:s') . "\r\n\r\n";

    /*

[~FrontEnd49-9999]
; Contents of the Front End:
Carts=6
Cart01=3,3,3,9999,3,9999
Cart02=4,4,4,9999,4,9999
Cart03=6,6,6,9999,6,9999
Cart04=7,7,7,9999,7,9999
Cart05=8,8,8,9999,8,9999
Cart06=9,9,9,9999,9,9999
; CartNN=port, band, CCAprovider, CCAid, WCAprovider, WCAid
; where:
;   port is where the cartridge assembly is actually attached to the FEMC module
;   band is what band to operate it as
;   WCAprovider, WCAid specify which section to get the WCA config from.
;   CCAprovider, CCAid specify which section to get the cold cart config from.
; So for band 3:
;   it's attached to port 3; operate it as a band 3 cartridge;
;   get the WCA config from [~WCA3-9999];
;   get the cold cart config from [~ColdCart3-9999].
Cryostat=9999
; If >0, get the cryostat config from the specified section, [~Cryostat49-9999]
; If =0, don't try to operate the cryostat M&C module.
LPR=9999
; If >0, get the LPR config from the specified section, [~LPR49-9999]
; If =0, don't try to operate the LPR.
IFSwitch=1
; If =0, don't operate the IF switch.
PowerDist=1
; If =0, don't operate the CPDS module.
SN=9999
; THe human-readable serail number of the front end.
 */


    echo "[~FrontEnd$fc-" . ltrim($fe->GetValue('SN'), '0')  . "]\r\n";

    //Get number of carts
    $cartcount = 0;
    for ($iband = 1; $iband <= 11; $iband++) {
        if (isset($fe->wcas[$iband]->keyId) && $fe->wcas[$iband]->keyId != '') {
            $cartbands[$cartcount] = $iband;
            $cartcount += 1;
        }
        if (isset($fe->ccas[$iband]->keyId) && $fe->ccas[$iband]->keyId != '') {
            $cartbands[$cartcount] = $iband;
            $cartcount += 1;
        }
    }



    $cartbands = array_values(array_unique($cartbands));


    echo "Carts=" . count($cartbands) . "\r\n";
    for ($i = 0; $i < count($cartbands); $i++) {
        echo "Cart0" . ($i + 1) . "=";

        //CCAs
        if (isset($fe->ccas[$cartbands[$i]]->keyId) && $fe->ccas[$cartbands[$i]]->keyId != '') {
            echo $fe->ccas[$cartbands[$i]]->GetValue('Band') . "," . $fe->ccas[$cartbands[$i]]->GetValue('Band')
                . "," . $fe->ccas[$cartbands[$i]]->GetValue('Band') . "," . ltrim($fe->ccas[$cartbands[$i]]->GetValue('SN'), '0') . ",";
        } else {
            echo $fe->wcas[$cartbands[$i]]->GetValue('Band') . ",";
            echo $fe->wcas[$cartbands[$i]]->GetValue('Band') . ",0,0,";
        }
        //WCAs
        if (isset($fe->wcas[$cartbands[$i]]->keyId) && $fe->wcas[$cartbands[$i]]->keyId != '') {
            echo $fe->wcas[$cartbands[$i]]->GetValue('Band') . "," . ltrim($fe->wcas[$cartbands[$i]]->GetValue('SN'), '0') . "\r\n";
        } else {
            echo "0,0\r\n";
        }
    }

    echo
    "Cryostat=" . ltrim($fe->GetValue('SN'), '0')  . "
LPR=$lprSN
IFSwitch=1
PowerDist=1
SN=FE-" . ltrim($fe->GetValue('SN'), '0')  . "\r\n\r\n";

    echo "
[~Cryostat$fc-" . ltrim($fe->GetValue('SN'), '0')  . "]
SN=" . ltrim($fe->GetValue('SN'), '0')  . "
ESN=" . $fe->cryostat->GetValue('ESN1')  . "

[~LPR$fc-" . ltrim($lprSN, '0') . "]
SN=" . ltrim($lprSN, '0') . "
ESN=" . $fe->lpr->GetValue('ESN1')  . "\r\n\r\n";
} //end if getall = 1

/*
[~WCA3-9999]
; WCA configuration section
Band=3
Description=Configuration for warm testing of cartridges
SN=9999
ESN=
; YIG oscillator limits for the WCA:
FHIYIG=18.165
FLOYIG=15.317
; LO PA operating parameters for the WCA:
LOParams=1
LOParam01=92.000,0,0,0,0
; LOParamNN=FreqLO, PAVD0, PAVD1, PAVG0, PAVG1
 */
if ($get_wca == '1') {

    for ($iwca = 1; $iwca <= 10; $iwca++) {
        if (isset($fe->wcas[$iwca]->keyId) && $fe->wcas[$iwca]->keyId != '') {
            $band = $fe->wcas[$iwca]->GetValue('Band');
            $sn   = ltrim($fe->wcas[$iwca]->GetValue('SN'), '0');
            $esn  = $fe->wcas[$iwca]->GetValue('ESN1');
            $description = "Description=Band $band SN$sn";
            if ($esn == $fe->wcas[$iwca]->keyId) {
                $esn = '';
            }
            echo "[~WCA$band-" . ltrim($sn, '0') . "]\r\n";
            echo "$description\r\n";
            echo "Band=$band\r\n";
            //echo "SN=WCA$band-$sn\r\n";
            echo "SN=$sn\r\n";
            echo "ESN=$esn\r\n";
            echo "FLOYIG=" . $fe->wcas[$iwca]->_WCAs->GetValue('FloYIG') . "\r\n";
            echo "FHIYIG=" . $fe->wcas[$iwca]->_WCAs->GetValue('FhiYIG') . "\r\n";


            //Get lowest LO
            $lowlo = "0.000";
            switch ($band) {
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
            //$mstring .= number_format($w->LOParams[$i]->GetValue('FreqLO'),3) . ",";
            $mstring .= ",1.00,1.00,";
            $mstring .= number_format($fe->wcas[$iwca]->_WCAs->GetValue('VG0'), 2) . ",";
            $mstring .= number_format($fe->wcas[$iwca]->_WCAs->GetValue('VG1'), 2) . "\r\n";
            echo $mstring;


            unset($w);
            echo "\r\n\r\n\r\n";
        } //end if keyId != ''
    } //end for iwca

}

/*
[~ColdCart3-9999]
; Cold cartridge configuration section
Band=3
Description=Configuration for warm testing of cartridges
SN=9999
ESN=
MagnetParams=0
; Band 3 has no magnets
MixerParams=1
MixerParam01=92.000,10,10,10,10,0,0,0,0
; MixerParamNN=FreqLO, VSIS01, VSIS02, VSIS11, VSIS12, ISIS01, ISIS02, ISIS11, ISIS12
PreampParams=4
PreampParam01=92.000,0,1,0.80,0.80,0.80,5,5,5,0,0,0
PreampParam02=92.000,0,2,0.80,0.80,0.80,5,5,5,0,0,0
PreampParam03=92.000,1,1,0.80,0.80,0.80,5,5,5,0,0,0
PreampParam04=92.000,1,2,0.80,0.80,0.80,5,5,5,0,0,0
; PreampParamNN=FreqLO, pol, sb, VD1, VD2, VD3, ID1, ID2, ID3, VG1, VG2, VG3
*/

if ($get_cca == '1') {

    for ($icca = 0; $icca <= 10; $icca++) {
        if (isset($fe->ccas[$icca]->keyId) && $fe->ccas[$icca]->keyId != '') {
            $band = $fe->ccas[$icca]->GetValue('Band');
            $sn   = ltrim($fe->ccas[$icca]->GetValue('SN'), '0');
            $esn  = $fe->ccas[$icca]->GetValue('ESN1');
            if ($esn == $fe->ccas[$icca]->keyId) {
                $esn = '';
            }

            echo "[~ColdCart$band-" . ltrim($sn, '0') . "]\r\n";
            $description = "Band $band SN$sn";
            echo "Description=$description\r\n";
            echo "Band=$band\r\n";
            //echo "SN=CCA$band-$sn\r\n";
            echo "SN=$sn\r\n";
            echo "ESN=$esn\r\n";

            switch ($fe->ccas[$icca]->GetValue('Band')) {
                case 3:
                    $mstring = "MagnetParams=0\r\n";
                    break;
                case 4:
                    $mstring = "MagnetParams=0\r\n";
                    break;
                case 6:
                    $mstring = "MagnetParams=1\r\n";
                    $mstring .= "MagnetParam01=" . number_format($fe->ccas[$icca]->MixerParams[0]->lo, 3) . ",";
                    $mstring .= number_format($fe->ccas[$icca]->MixerParams[0]->imag01, 2) . ",";
                    $mstring .= "0.00,";
                    $mstring .= number_format($fe->ccas[$icca]->MixerParams[0]->imag11, 2) . ",";
                    $mstring .= "0.00\r\n";
                    break;
                default:
                    $mstring = "MagnetParams=1\r\n";
                    $mstring .= "MagnetParam01=" . number_format($fe->ccas[$icca]->MixerParams[0]->lo, 3) . ",";
                    $mstring .= number_format($fe->ccas[$icca]->MixerParams[0]->imag01, 2) . ",";
                    $mstring .= number_format($fe->ccas[$icca]->MixerParams[0]->imag02, 2) . ",";
                    $mstring .= number_format($fe->ccas[$icca]->MixerParams[0]->imag11, 2) . ",";
                    $mstring .= number_format($fe->ccas[$icca]->MixerParams[0]->imag12, 2) . "\r\n";
            }

            echo $mstring;


            echo "MixerParams=" . (count($fe->ccas[$icca]->MixerParams)) . "\r\n";

            for ($i = 0; $i  < (count($fe->ccas[$icca]->MixerParams)); $i++) {
                if ($i < 9) {
                    $mstring = "MixerParam0" . ($i + 1) . "=";
                }
                if ($i >= 9) {
                    $mstring = "MixerParam" . ($i + 1) . "=";
                }
                $mstring .= number_format($fe->ccas[$icca]->MixerParams[$i]->lo, 3) . ",";
                $mstring .= number_format($fe->ccas[$icca]->MixerParams[$i]->vj01, 3) . ",";
                $mstring .= number_format($fe->ccas[$icca]->MixerParams[$i]->vj02, 3) . ",";
                $mstring .= number_format($fe->ccas[$icca]->MixerParams[$i]->vj11, 3) . ",";
                $mstring .= number_format($fe->ccas[$icca]->MixerParams[$i]->vj12, 3) . ",";
                $mstring .= number_format($fe->ccas[$icca]->MixerParams[$i]->ij01, 2) . ",";
                $mstring .= number_format($fe->ccas[$icca]->MixerParams[$i]->ij02, 2) . ",";
                $mstring .= number_format($fe->ccas[$icca]->MixerParams[$i]->ij11, 2) . ",";
                $mstring .= number_format($fe->ccas[$icca]->MixerParams[$i]->ij12, 2) . "\r\n";
                echo $mstring;
            }

            $numpa = 4;
            if ($fe->ccas[$icca]->GetValue('Band') == 9) {
                $numpa = 2;
            }
            $ij_precision = 2;
            if ($fe->ccas[$icca]->GetValue('Band') == 3) {
                $ij_precision = 3;
            }


            //if ($band != 7){
            echo "PreampParams=" . (count($fe->ccas[$icca]->PreampParams)) . "\r\n";

            for ($i = 0; $i  < (count($fe->ccas[$icca]->PreampParams)); $i++) {
                if ($i < 9) {
                    $mstring = "PreampParam0" . ($i + 1) . "=";
                }
                if ($i >= 9) {
                    $mstring = "PreampParam" . ($i + 1) . "=";
                }
                $mstring .= number_format($fe->ccas[$icca]->PreampParams[$i]->GetValue('FreqLO'), 3) . ",";
                $mstring .= $fe->ccas[$icca]->PreampParams[$i]->GetValue('Pol') . ",";
                $mstring .= $fe->ccas[$icca]->PreampParams[$i]->GetValue('SB') . ",";
                $mstring .= number_format($fe->ccas[$icca]->PreampParams[$i]->GetValue('VD1'), 2) . ",";
                $mstring .= number_format($fe->ccas[$icca]->PreampParams[$i]->GetValue('VD2'), 2) . ",";
                $mstring .= number_format($fe->ccas[$icca]->PreampParams[$i]->GetValue('VD3'), 2) . ",";
                $mstring .= number_format($fe->ccas[$icca]->PreampParams[$i]->GetValue('ID1'), $ij_precision) . ",";
                $mstring .= number_format($fe->ccas[$icca]->PreampParams[$i]->GetValue('ID2'), $ij_precision) . ",";
                $mstring .= number_format($fe->ccas[$icca]->PreampParams[$i]->GetValue('ID3'), $ij_precision) . ",";
                $mstring .= number_format($fe->ccas[$icca]->PreampParams[$i]->GetValue('VG1'), 2) . ",";
                $mstring .= number_format($fe->ccas[$icca]->PreampParams[$i]->GetValue('VG2'), 2) . ",";
                $mstring .= number_format($fe->ccas[$icca]->PreampParams[$i]->GetValue('VG3'), 2) . "\r\n";
                echo $mstring;
            }
            //}//end !7


            unset($c);
            echo "\r\n\r\n\r\n";
        } //end if keyId != ''
    } //end for icca
}

?>
