<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_dbConnect);

$fc = $_REQUEST['fc'];


if (isset($_REQUEST['keyheader'])){
    $TestData_header_keyId = $_REQUEST['keyheader'];
    $td = new TestData_header();
    $td->Initialize_TestData_header($TestData_header_keyId,$fc);


    header("Content-type: application/x-msdownload");
    $csv_filename = str_replace(" ","_",$td->TestDataType . ".csv");
    header("Content-Disposition: attachment; filename=$csv_filename");
    header("Pragma: no-cache");
    header("Expires: 0");


    switch($td->GetValue('fkTestData_Type')){

        case 57: //LO Lock Test
            $q1 = "select keyId from TEST_LOLockTest_SubHeader where fkHeader = $td->keyId;";
            $r1 = @mysql_query($q1,$db);
            $subh_id = @mysql_result($r1,0,0);
            $qdata = "SELECT * FROM TEST_LOLockTest WHERE fkHeader = $subh_id;";
            //echo $qdata;

            break;

        default:
            $qdata = "SELECT * FROM $td->TestDataTableName WHERE fkHeader = $td->keyId;";

            //echo $qcols;

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

    echo $qcols;
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

unset($td);
?>