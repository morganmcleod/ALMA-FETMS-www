<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_dbConnect);

$keyScanDet = isset($_REQUEST['sdid']) ? $_REQUEST['sdid'] : false;
$which = isset($_REQUEST['which']) ? $_REQUEST['which'] : 'ff';

if ($keyScanDet){
    header("Content-type: application/x-msdownload");
    
    if ($which == 'nf')
        $csv_filename = "Nearfield_$keyScanDet.csv";
    else
        $csv_filename = "Farfield_$keyScanDet.csv";
    
    header("Content-Disposition: attachment; filename=$csv_filename");
    header("Pragma: no-cache");
    header("Expires: 0");

    $scanDet = new GenericTable();
    $scanDet->Initialize('ScanDetails',$keyScanDet,'keyId');

    if ($which == 'nf')
        echo "!Nearfield Beam Listing\r\n";
    else
        echo "!Farfield Beam Listing\r\n";
        
    echo "!ScanSetDetails.keyId=" . $scanDet->GetValue('fkScanSetDetails') . "\r\n";
    echo "!ScanDetails.keyId=$keyScanDet\r\n";
    echo "\r\n\r\n";

    if ($which == 'nf')
        $q = "SHOW COLUMNS FROM BeamListings_nearfield;";
    else
        $q = "SHOW COLUMNS FROM BeamListings_farfield;";

    $r = @mysql_query ($q, $db);
    $first = true;
    while($row = mysql_fetch_array($r)) {
        $name = $row[0];
        
        if ($which == 'ff' && $name == 'x')
            $name = 'az';
        if ($which == 'ff' && $name == 'y')
            $name = 'el';
        
        if ($first)
            $first = false;
        else
            echo ", ";
          
        echo $name;
    }
    echo "\r\n";

    if ($which == 'nf')
        $q = "SELECT * FROM BeamListings_nearfield WHERE fkScanDetails = $keyScanDet;";
    else        
        $q = "SELECT * FROM BeamListings_farfield WHERE fkScanDetails = $keyScanDet;";
    
    $r = @mysql_query ($q, $db);
    while($row = mysql_fetch_array($r, MYSQL_NUM)) {
        $first = true;
        foreach($row as $cell) {
            if ($first)
                $first = false;
            else
                echo ", ";
            echo $cell;
        }
        echo "\r\n";
    }
    unset($scanDet);
}

?>