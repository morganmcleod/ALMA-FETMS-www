<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.cryostat.php');

$Cryostat_keyId = $_REQUEST['keyId'];
$datatype = $_REQUEST['datatype'];
$fc = $_REQUEST['fc'];

if ($datatype == "tempsensors") {

    $cryostat = new Cryostat;
    $cryostat->Initialize_Cryostat($Cryostat_keyId, $fc);

    $title1  = $cryostat->tempsensors[1]->GetValue('location');
    $title2  = $cryostat->tempsensors[2]->GetValue('location');
    $title3  = $cryostat->tempsensors[3]->GetValue('location');
    $title4  = $cryostat->tempsensors[4]->GetValue('location');
    $title5  = $cryostat->tempsensors[5]->GetValue('location');
    $title6  = $cryostat->tempsensors[6]->GetValue('location');
    $title7  = $cryostat->tempsensors[7]->GetValue('location');
    $title8  = $cryostat->tempsensors[8]->GetValue('location');
    $title9  = $cryostat->tempsensors[9]->GetValue('location');
    $title10 = $cryostat->tempsensors[10]->GetValue('location');
    $title11 = $cryostat->tempsensors[11]->GetValue('location');
    $title12 = $cryostat->tempsensors[12]->GetValue('location');
    $title13 = $cryostat->tempsensors[13]->GetValue('location');

    $fname = "cryo.ini";
    header("Content-type: text/plain");
    header("Content-Disposition: attachment; filename=$fname");
    header("Pragma: no-cache");
    header("Expires: 0");



    echo ";\r\n";
    echo ";Cryostat configuration file\r\n";
    echo ";\r\n";
    echo ";** Make sure to end every line containing data with a LF or CR/LF **\r\n;";
    echo ";NOTES:\r\n";
    echo "; - If using FEMC fimrware rev.2.5.0 or earlier 2.x.x, the maximum lenght for the\r\n";
    echo ";   TVO_NO field is 7 characters (+1 string terminator null).\r\n";
    echo ";   For later version, the limit is 31 (+1 string terminator null).\r\n";
    echo "; - The mapping with the designation in the 'Cryostat housekeeping and thermometry'\r\n";
    echo ";   is indicated for each sensors in the first comment line.\r\n";
    echo ";\r\n";

    echo "\r\n";
    echo "[INFO]\r\n";
    echo ";Electronic Serial Number (ESN)\r\n";
    echo "ESN='FF FF FF FF FF FF FF FF'\r\n";
    echo ";Cryostat Serial Number (SN)\r\n";
    echo "SN=" . $cryostat->GetValue('SN')   . "\r\n";


    for ($i = 1; $i < 13; $i++) {

        switch ($cryostat->tempsensors[$i]->GetValue('location')) {
            case "4K Cryocooler Stage":
                echo "\r\n";
                echo "[CRYOCOOLER_4K]\r\n";
                echo ";Use:  4K Cryocooler stage\r\n";
                echo ";TVO Sensor Serial Number\r\n";
                echo "TVO_NO=" . substr($cryostat->tempsensors[$i]->GetValue('sensor_type'), -7) . "\r\n";
                echo ";Comma separated coeffcient for TVO starting with X^0 (Tab or space work as well)\r\n";
                echo "TVO_COEFFS=" . $cryostat->tempsensors[$i]->GetValue("k1");

                for ($k = 2; $k <= 7; $k++) {
                    echo "," . $cryostat->tempsensors[$i]->GetValue("k$k");
                }
                echo "\r\n";
                break;

            case "4K Plate Near Link b":
                echo "\r\n";
                echo "[PLATE_4K_LINK_2]\r\n";
                echo ";Use:  4K plate near link b\r\n";
                echo ";TVO Sensor Serial Number\r\n";
                echo "TVO_NO=" . substr($cryostat->tempsensors[$i]->GetValue('sensor_type'),  -7) . "\r\n";
                echo ";Comma separated coeffcient for TVO starting with X^0 (Tab or space work as well)\r\n";
                echo "TVO_COEFFS=" . $cryostat->tempsensors[$i]->GetValue("k1");

                for ($k = 2; $k <= 7; $k++) {
                    echo "," . $cryostat->tempsensors[$i]->GetValue("k$k");
                }
                echo "\r\n";
                break;

            case "4K Plate Near Link a":
                echo "\r\n";
                echo "[PLATE_4K_LINK_1]\r\n";
                echo ";Use:  4K plate near link a\r\n";
                echo ";TVO Sensor Serial Number\r\n";
                echo "TVO_NO=" . substr($cryostat->tempsensors[$i]->GetValue('sensor_type'), -7) . "\r\n";
                echo ";Comma separated coeffcient for TVO starting with X^0 (Tab or space work as well)\r\n";
                echo "TVO_COEFFS=" . $cryostat->tempsensors[$i]->GetValue("k1");

                for ($k = 2; $k <= 7; $k++) {
                    echo "," . $cryostat->tempsensors[$i]->GetValue("k$k");
                }
                echo "\r\n";
                break;



            case "4K Plate Far Side A":
                echo "\r\n";
                echo "[PLATE_4K_FAR_2]\r\n";
                echo ";Use:  4K plate far side a \r\n";
                echo ";TVO Sensor Serial Number\r\n";
                echo "TVO_NO=" . substr($cryostat->tempsensors[$i]->GetValue('sensor_type'),  -7) . "\r\n";
                echo ";Comma separated coeffcient for TVO starting with X^0 (Tab or space work as well)\r\n";
                echo "TVO_COEFFS=" . $cryostat->tempsensors[$i]->GetValue("k1");

                for ($k = 2; $k <= 7; $k++) {
                    echo "," . $cryostat->tempsensors[$i]->GetValue("k$k");
                }
                echo "\r\n";
                break;

            case "4K Plate Far Side B":
                echo "\r\n";
                echo "[PLATE_4K_FAR_1]\r\n";
                echo ";Use:  4K plate far side B \r\n";
                echo ";TVO Sensor Serial Number\r\n";
                echo "TVO_NO=" . substr($cryostat->tempsensors[$i]->GetValue('sensor_type'),  -7) . "\r\n";
                echo ";Comma separated coeffcient for TVO starting with X^0 (Tab or space work as well)\r\n";
                echo "TVO_COEFFS=" . $cryostat->tempsensors[$i]->GetValue("k1");

                for ($k = 2; $k <= 7; $k++) {
                    echo "," . $cryostat->tempsensors[$i]->GetValue("k$k");
                }
                echo "\r\n";
                break;

            case "12K Cryocooler Stage":
                echo "\r\n";
                echo "[CRYOCOOLER_12K]\r\n";
                echo ";Use:  15K Cryocooler stage\r\n";
                echo ";TVO Sensor Serial Number\r\n";
                echo "TVO_NO=" . substr($cryostat->tempsensors[$i]->GetValue('sensor_type'),  -7) . "\r\n";
                echo ";Comma separated coeffcient for TVO starting with X^0 (Tab or space work as well)\r\n";
                echo "TVO_COEFFS=" . $cryostat->tempsensors[$i]->GetValue("k1");

                for ($k = 2; $k <= 7; $k++) {
                    echo "," . $cryostat->tempsensors[$i]->GetValue("k$k");
                }
                echo "\r\n";
                break;

            case "15K Cryocooler Stage":
                echo "\r\n";
                echo "[CRYOCOOLER_12K]\r\n";
                echo ";Use:  15K Cryocooler stage\r\n";
                echo ";TVO Sensor Serial Number\r\n";
                echo "TVO_NO=" . substr($cryostat->tempsensors[$i]->GetValue('sensor_type'),  -7) . "\r\n";
                echo ";Comma separated coeffcient for TVO starting with X^0 (Tab or space work as well)\r\n";
                echo "TVO_COEFFS=" . $cryostat->tempsensors[$i]->GetValue("k1");

                for ($k = 2; $k <= 7; $k++) {
                    echo "," . $cryostat->tempsensors[$i]->GetValue("k$k");
                }
                echo "\r\n";
                break;


            case "12K Plate Near Link":
                echo "\r\n";
                echo "[PLATE_12K_LINK]\r\n";
                echo ";Use:  15K plate near link\r\n";
                echo ";TVO Sensor Serial Number\r\n";
                echo "TVO_NO=" . substr($cryostat->tempsensors[$i]->GetValue('sensor_type'),  -7) . "\r\n";
                echo ";Comma separated coeffcient for TVO starting with X^0 (Tab or space work as well)\r\n";
                echo "TVO_COEFFS=" . $cryostat->tempsensors[$i]->GetValue("k1");

                for ($k = 2; $k <= 7; $k++) {
                    echo "," . $cryostat->tempsensors[$i]->GetValue("k$k");
                }
                echo "\r\n";
                break;

            case "15K Plate Near Link":
                echo "\r\n";
                echo "[PLATE_12K_LINK]\r\n";
                echo ";Use:  15K plate near link\r\n";
                echo ";TVO Sensor Serial Number\r\n";
                echo "TVO_NO=" . substr($cryostat->tempsensors[$i]->GetValue('sensor_type'),  -7) . "\r\n";
                echo ";Comma separated coeffcient for TVO starting with X^0 (Tab or space work as well)\r\n";
                echo "TVO_COEFFS=" . $cryostat->tempsensors[$i]->GetValue("k1");

                for ($k = 2; $k <= 7; $k++) {
                    echo "," . $cryostat->tempsensors[$i]->GetValue("k$k");
                }
                echo "\r\n";
                break;

            case "12K Plate Far Side":
                echo "\r\n";
                echo "[PLATE_12K_FAR]\r\n";
                echo ";Use:  15K plate far side\r\n";
                echo ";TVO Sensor Serial Number\r\n";
                echo "TVO_NO=" . substr($cryostat->tempsensors[$i]->GetValue('sensor_type'),  -7) . "\r\n";
                echo ";Comma separated coeffcient for TVO starting with X^0 (Tab or space work as well)\r\n";
                echo "TVO_COEFFS=" . $cryostat->tempsensors[$i]->GetValue("k1");

                for ($k = 2; $k <= 7; $k++) {
                    echo "," . $cryostat->tempsensors[$i]->GetValue("k$k");
                }
                echo "\r\n";
                break;

            case "15K Plate Far Side":
                echo "\r\n";
                echo "[PLATE_12K_FAR]\r\n";
                echo ";Use:  15K plate far side\r\n";
                echo ";TVO Sensor Serial Number\r\n";
                echo "TVO_NO=" . substr($cryostat->tempsensors[$i]->GetValue('sensor_type'),  -7) . "\r\n";
                echo ";Comma separated coeffcient for TVO starting with X^0 (Tab or space work as well)\r\n";
                echo "TVO_COEFFS=" . $cryostat->tempsensors[$i]->GetValue("k1");

                for ($k = 2; $k <= 7; $k++) {
                    echo "," . $cryostat->tempsensors[$i]->GetValue("k$k");
                }
                echo "\r\n";
                break;

            case "12K Shield Top":
                echo "\r\n";
                echo "[PLATE_12K_SHIELD]\r\n";
                echo ";Use:  15K shield top\r\n";
                echo ";TVO Sensor Serial Number\r\n";
                echo "TVO_NO=" . substr($cryostat->tempsensors[$i]->GetValue('sensor_type'),  -7) . "\r\n";
                echo ";Comma separated coeffcient for TVO starting with X^0 (Tab or space work as well)\r\n";
                echo "TVO_COEFFS=" . $cryostat->tempsensors[$i]->GetValue("k1");

                for ($k = 2; $k <= 7; $k++) {
                    echo "," . $cryostat->tempsensors[$i]->GetValue("k$k");
                }
                echo "\r\n";
                break;

            case "15K Shield Top":
                echo "\r\n";
                echo "[PLATE_12K_SHIELD]\r\n";
                echo ";Use:  15K shield top\r\n";
                echo ";TVO Sensor Serial Number\r\n";
                echo "TVO_NO=" . substr($cryostat->tempsensors[$i]->GetValue('sensor_type'),  -7) . "\r\n";
                echo ";Comma separated coeffcient for TVO starting with X^0 (Tab or space work as well)\r\n";
                echo "TVO_COEFFS=" . $cryostat->tempsensors[$i]->GetValue("k1");

                for ($k = 2; $k <= 7; $k++) {
                    echo "," . $cryostat->tempsensors[$i]->GetValue("k$k");
                }
                echo "\r\n";
                break;
        }
    }

    unset($cryostat);
}

?>
