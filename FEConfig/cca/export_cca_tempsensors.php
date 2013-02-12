<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.cca.php');

$fc = $_REQUEST['fc'];
$cca = new CCA();
$id = $_REQUEST['keyId'];
$cca->Initialize_CCA($id,$fc);
$band = $cca->GetValue('Band');
$sn   = ltrim($cca->GetValue('SN'),'0');
$esn  = $cca->GetValue('ESN1');

$fname = "cart$band.ini";
header("Content-type: application/x-msdownload");
header("Content-Disposition: attachment; filename=$fname");
header("Pragma: no-cache");
header("Expires: 0");

$resistor = '0.0';

switch($band){
    case 1:
        $resistor = '10.0';
        break;
    case 2:
        $resistor = '10.0';
        break;
    case 3:
        $resistor = '5.0';
        break;
    case 4:
        $resistor = '5.0';
        break;
    case 5:
        $resistor = '10.0';
        break;
    case 6:
        $resistor = '5.0';
        break;
    case 7:
        $resistor = '50.0';
        break;
    case 8:
        $resistor = '5.0';
        break;
    case 9:
        $resistor = '10.0';
        break;
    case 10:
        $resistor = '10.0';
        break;
}


echo ";\r\n";
echo ";  Cartridge configuration file\r\n";
echo ";\r\n";
echo "; Make sure to end every line containing data with a LF or CR/LF\r\n";
echo ";\r\n";
echo "; 'Y', 'T', '1' will all be considered as TRUE\r\n";
echo "\r\n";


echo "[INFO]\r\n";
echo ";  Electronic Serial Number (ESN)\r\n";

//Insert a space between every two characters
$esn_array = str_split($esn);
$esnstring = strtolower($esn_array[0] . $esn_array[1]);
for ($i=3; $i< count($esn_array); $i+=2){
    $esnstring .= " " . strtolower($esn_array[$i-1] . $esn_array[$i]);

}
echo "ESN=$esnstring\r\n";
echo ";  Cartridge Serial Number\r\n";
echo 'SN="'. $sn . '"';

echo "\r\n\r\n";

//The location codes are:  3=4K, 4=Pol0, 5=Pol1.

//4k stage
if ($cca->TempSensors[3]->keyId != ''){
    $av = "Y";
    $os = $cca->TempSensors[3]->GetValue('OffsetK');
}
else{
    $av = "N";
    $os = "0.00";
}

echo "
[RESISTOR]
; Value of the SIS current sense resistors
VALUE=$resistor

; --- Begin of temperature sensor info ---\r\n";
echo "[4K_STAGE]\r\n";
echo "; Info for 4K stage temperature sensor\r\n";
echo "AVAILABLE=$av\r\n";
echo "OFFSET=$os\r\n";
echo "
[110K_STAGE]
; Info for 110K stage temperature sensor
AVAILABLE=Y
OFFSET=0.0

[SPARE]
; Info for spare temperature sensor
AVAILABLE=Y
OFFSET=0.0

[15K_STAGE]
; Info for 15K stage temperature sensor
AVAILABLE=Y
OFFSET=0.0\r\n";

//Pol0 stage
if ($cca->TempSensors[4]->keyId != ''){
    $av = "Y";
    $os = $cca->TempSensors[4]->GetValue('OffsetK');
}
else{
    $av = "N";
    $os = "0.00";
}
echo "\r\n\r\n[POL0_MIXER]\r\n";
echo "; Info for POL0 mixer temperature sensor\r\n";
echo "AVAILABLE=$av\r\n";
echo "OFFSET=$os";
echo "\r\n\r\n";

//Pol1 stage
if ($cca->TempSensors[5]->keyId != ''){
    $av = "Y";
    $os = $cca->TempSensors[5]->GetValue('OffsetK');
}
else{
    $av = "N";
    $os = "0.00";
}
echo "[POL1_MIXER]\r\n";
echo "; Info for POL1 temperature sensor\r\n";
echo "AVAILABLE=$av\r\n";
echo "OFFSET=$os\r\n";
echo "; --- End of temperature sensor info ---

; --- Start of Polarization 0 info ---
[P0]
; Polarization 0 information
AVAILABLE=Y

; --- Start of Polarization 0, sideband 0 info ---
[P0_S0]
; Polarization 0, Sideband 0 information
AVAILABLE=Y
[P0_S0_SIS]
; Polarization 0, Sideband 0 SIS information
AVAILABLE=Y
[P0_S0_SIS_MAG]
; Polarization 0, Sideband 0 SIS magnet information
AVAILABLE=Y
[P0_S0_LNA]
; Polarization 0, Sideband 0 LNA information
AVAILABLE=Y
[P0_S0_LNA_S0]
; Polarization 0, Sideband 0 LNA, Stage 0 information
AVAILABLE=Y
[P0_S0_LNA_S1]
; Polarization 0, Sideband 0 LNA, Stage 1 information
AVAILABLE=Y
[P0_S0_LNA_S2]
; Polarization 0, Sideband 0 LNA, Stage 2 information
AVAILABLE=Y
[P0_S0_LNA_S3]
; Polarization 0, Sideband 0 LNA, Stage 3 information
AVAILABLE=Y
[P0_S0_LNA_S4]
; Polarization 0, Sideband 0 LNA, Stage 4 information
AVAILABLE=Y
[P0_S0_LNA_S5]
; Polarization 0, Sideband 0 LNA, Stage 5 information
AVAILABLE=Y
; --- End of Polarization 0, sideband 0 info ---

; --- Start of Polarization 0, sideband 1 info ---
[P0_S1]
; Polarization 0, Sideband 1 information
AVAILABLE=Y
[P0_S1_SIS]
; Polarization 0, Sideband 1 SIS information
AVAILABLE=Y
[P0_S1_SIS_MAG]
; Polarization 0, Sideband 1 SIS magnet information
AVAILABLE=Y
[P0_S1_LNA]
; Polarization 0, Sideband 1 LNA information
AVAILABLE=Y
[P0_S1_LNA_S0]
; Polarization 0, Sideband 1 LNA, Stage 0 information
AVAILABLE=Y
[P0_S1_LNA_S1]
; Polarization 0, Sideband 1 LNA, Stage 1 information
AVAILABLE=Y
[P0_S1_LNA_S2]
; Polarization 0, Sideband 1 LNA, Stage 2 information
AVAILABLE=Y
[P0_S1_LNA_S3]
; Polarization 0, Sideband 1 LNA, Stage 3 information
AVAILABLE=Y
[P0_S1_LNA_S4]
; Polarization 0, Sideband 1 LNA, Stage 4 information
AVAILABLE=Y
[P0_S1_LNA_S5]
; Polarization 0, Sideband 1 LNA, Stage 5 information
AVAILABLE=Y
; --- End of Polarization 0, sideband 0 info ---

[P0_LNA_LED]
; Polarization 0 LNA LED information
AVAILABLE=Y
[P0_SIS_HEATER]
; Polarization 0 SIS heater information
AVAILABLE=Y
[P0_SCHOTTKY]
; Polarization 0 Schottky mixer information
AVAILABLE=Y
; --- End of Polarization 0 info ---

; --- Start of Polarization 1 info ---
[P1]
; Polarization 1 information
AVAILABLE=Y

; --- Start of Polarization 1, sideband 0 info ---
[P1_S0]
; Polarization 1, Sideband 0 information
AVAILABLE=Y
[P1_S0_SIS]
; Polarization 1, Sideband 0 SIS information
AVAILABLE=Y
[P1_S0_SIS_MAG]
; Polarization 1, Sideband 0 SIS magnet information
AVAILABLE=Y
[P1_S0_LNA]
; Polarization 1, Sideband 0 LNA information
AVAILABLE=Y
[P1_S0_LNA_S0]
; Polarization 1, Sideband 0 LNA, Stage 0 information
AVAILABLE=Y
[P1_S0_LNA_S1]
; Polarization 1, Sideband 0 LNA, Stage 1 information
AVAILABLE=Y
[P1_S0_LNA_S2]
; Polarization 1, Sideband 0 LNA, Stage 2 information
AVAILABLE=Y
[P1_S0_LNA_S3]
; Polarization 1, Sideband 0 LNA, Stage 3 information
AVAILABLE=Y
[P1_S0_LNA_S4]
; Polarization 1, Sideband 0 LNA, Stage 4 information
AVAILABLE=Y
[P1_S0_LNA_S5]
; Polarization 1, Sideband 0 LNA, Stage 5 information
AVAILABLE=Y
; --- End of Polarization 1, sideband 0 info ---

; --- Start of Polarization 1, sideband 1 info ---
[P1_S1]
; Polarization 1, Sideband 1 information
AVAILABLE=Y
[P1_S1_SIS]
; Polarization 1, Sideband 1 SIS information
AVAILABLE=Y
[P1_S1_SIS_MAG]
; Polarization 1, Sideband 1 SIS magnet information
AVAILABLE=Y
[P1_S1_LNA]
; Polarization 1, Sideband 1 LNA information
AVAILABLE=Y
[P1_S1_LNA_S0]
; Polarization 1, Sideband 1 LNA, Stage 0 information
AVAILABLE=Y
[P1_S1_LNA_S1]
; Polarization 1, Sideband 1 LNA, Stage 1 information
AVAILABLE=Y
[P1_S1_LNA_S2]
; Polarization 1, Sideband 1 LNA, Stage 2 information
AVAILABLE=Y
[P1_S1_LNA_S3]
; Polarization 1, Sideband 1 LNA, Stage 3 information
AVAILABLE=Y
[P1_S1_LNA_S4]
; Polarization 1, Sideband 1 LNA, Stage 4 information
AVAILABLE=Y
[P1_S1_LNA_S5]
; Polarization 1, Sideband 1 LNA, Stage 5 information
AVAILABLE=Y
; --- End of Polarization 1, sideband 0 info ---

[P1_LNA_LED]
; Polarization 1 LNA LED information
AVAILABLE=Y
[P1_SIS_HEATER]
; Polarization 1 SIS heater information
AVAILABLE=Y
[P1_SCHOTTKY]
; Polarization 1 Schottky mixer information
AVAILABLE=Y
; --- End of Polarization 1 info ---
";

unset($cca);
?>
