<?php

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_dbConnect);

$fc = $_REQUEST['fc'];

if ((isset($_REQUEST['keyheader']) && (!isset($_REQUEST['ifsub'])))){
    $TestData_header_keyId = $_REQUEST['keyheader'];
    $td = new TestData_header();
    $td->Initialize_TestData_header($TestData_header_keyId,$fc);

    header("Content-type: application/x-msdownload");
    $csv_filename = str_replace(" ","_",$td->TestDataType . ".csv");
    //$csv_filename = "exported.csv";
    header("Content-Disposition: attachment; filename=$csv_filename");
    header("Pragma: no-cache");
    header("Expires: 0");


    switch($td->GetValue('fkTestData_Type')){

        case 57: //LO Lock Test
            $q1 = "select keyId from TEST_LOLockTest_SubHeader where fkHeader = $td->keyId;";
            $r1 = @mysql_query($q1,$db);
            $subh_id = @mysql_result($r1,0,0);
            $qdata = "SELECT DT.*
                     FROM TEST_LOLockTest as DT, TEST_LOLockTest_SubHeader as SH, TestData_header as TDH
                     WHERE DT.fkHeader = SH.keyId AND DT.fkFacility = SH.keyFacility
                     AND SH.fkHeader = TDH.keyId AND SH.keyFacility = TDH.keyFacility"
                   . " AND TDH.Band = " . $td->GetValue('Band')
                   . " AND TDH.DataSetGroup = " . $td->GetValue('DataSetGroup')
                   . " AND TDH.fkFE_Config = " . $td->GetValue('fkFE_Config')
                   . " AND DT.IsIncluded = 1
                     ORDER BY DT.LOFreq ASC;";

            $td->TestDataTableName = 'TEST_LOLockTest';
            break;

        case 58:
                //Noise Temperature
                $q = "SELECT keyId FROM Noise_Temp_SubHeader
                      WHERE fkHeader = $td->keyId
                      AND keyFacility = " . $td->GetValue('keyFacility');
                $r = @mysql_query($q,$db);
                $subid = @mysql_result($r,0,0);
                $qdata = "SELECT * FROM Noise_Temp WHERE
                fkSub_Header = $subid AND keyFacility = ".$td->GetValue('keyFacility').";";
                $td->TestDataTableName = 'Noise_Temp';
                break;
        case 59:
                //fine LO Sweep
                $q = "SELECT keyId FROM TEST_FineLOSweep_SubHeader
                      WHERE fkHeader = $td->keyId
                      AND keyFacility = " . $td->GetValue('keyFacility');
                $r = @mysql_query($q,$td->dbconnection);
                $subid = @mysql_result($r,0,0);
                $qdata = "SELECT * FROM TEST_FineLOSweep WHERE
                fkSubHeader = $subid AND fkFacility = ".$td->GetValue('keyFacility').";";
                $td->TestDataTableName = 'TEST_FineLOSweep';
                break;


        default:
            $qdata = "SELECT * FROM $td->TestDataTableName WHERE fkHeader = $td->keyId;";ls;

        break;
    }

    switch($td->Component->GetValue('fkFE_ComponentType')){
            case 6:
                //Cryostat
                $q = "SELECT keyId FROM TEST_Cryostat_data_SubHeader
                      WHERE fkHeader = $td->keyId;";
                $r = @mysql_query($q,$td->dbconnection);
                $fkHeader = @mysql_result($r,0,0);
                $qdata = "SELECT * FROM $td->TestDataTableName WHERE
                fkSubHeader = $fkHeader AND fkFacility = ".$td->GetValue('keyFacility').";";
                break;
        }

//Output records to csv file
$qcols = "SHOW COLUMNS FROM $td->TestDataTableName;";

$rcols = @mysql_query ($qcols, $db);
while($rowcols = mysql_fetch_array($rcols)){
    echo $rowcols[0] . ",";
}
echo "\r\n";

$rdata = @mysql_query ($qdata, $db);
while($rowdata = mysql_fetch_array($rdata)){
        for ($i=0;$i<count($rowdata);$i++){
            echo "$rowdata[$i],";
        }
        echo "\r\n";
    }
}


if (isset($_REQUEST['ssdid'])){
    $ssdid = $_REQUEST['ssdid'];


    header("Content-type: application/x-msdownload");
    $csv_filename = "Farfield_$ssdid.csv";
    header("Content-Disposition: attachment; filename=$csv_filename");
    header("Pragma: no-cache");
    header("Expires: 0");

    $sdet = new GenericTable();
    $sdet->Initialize('ScanDetails',$ssdid,'keyId');

    echo "!Farfield Beam Listing\r\n";
    echo "!ScanSetDetails.keyId=" . $sdet->GetValue('fkScanSetDetails') . "\r\n";
    echo "!ScanDetails.keyId=$ssdid\r\n";
    echo "\r\n\r\n";



    $q = "SHOW COLUMNS FROM BeamListings_farfield;";
    $r = @mysql_query ($q, $db);
    while($row = mysql_fetch_array($r)){
        echo $row[0] . ",";
    }
    echo "\r\n";

    $q = "SELECT * FROM BeamListings_farfield WHERE fkScanDetails = $ssdid;";
    $r = @mysql_query ($q, $db);
    while($row = mysql_fetch_array($r)){
        for ($i=0;$i<count($row);$i++){
            echo "$row[$i],";
        }
        echo "\r\n";
    }
}


if (isset($_REQUEST['ifsub'])){
    $ifsub_id = $_REQUEST['ifsub'];
    $TestData_header_keyId = $_REQUEST['keyheader'];
    $fc = $_REQUEST['fc'];

    $ifsub = new GenericTable();
    $ifsub->Initialize('IFSpectrum_SubHeader',$ifsub_id,'keyId',$fc,'keyFacility');


    header("Content-type: application/x-msdownload");
    $csv_filename = "IFSpectrum_$ifsub_id.csv";
    header("Content-Disposition: attachment; filename=$csv_filename");
    header("Pragma: no-cache");
    header("Expires: 0");



    echo "!IF Spectrum\r\n";
    echo "!IFSpectrum_SubHeader.keyId,$ifsub_id\r\n";
    echo "!TestData_Header.keyId,$TestData_header_keyId\r\n";
    echo "!IF Channel," . $ifsub->GetValue('IFChannel') . "\r\n";
    echo "!IF Gain," . $ifsub->GetValue('IFGain') . "\r\n";
    echo "!IF LO (GHz)," . $ifsub->GetValue('FreqLO') . "\r\n";
    echo "!Date," . $ifsub->GetValue('TS') . "\r\n";
    echo "\r\n\r\n";



    $q = "SHOW COLUMNS FROM IFSpectrum;";
    $r = @mysql_query ($q, $db);
    while($row = mysql_fetch_array($r)){
        echo $row[0] . ",";
    }
    echo "\r\n";

    $q = "SELECT * FROM IFSpectrum WHERE fkSubHeader = $ifsub_id AND fkFacility = $fc;";
    $r = @mysql_query ($q, $db);
    while($row = mysql_fetch_array($r)){
        for ($i=0;$i<count($row);$i++){
            echo "$row[$i],";
        }
        echo "\r\n";
    }
}

unset($td);
?>