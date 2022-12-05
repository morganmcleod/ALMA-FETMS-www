<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.scandetails.php');
require_once($site_classes . '/class.scansetdetails.php');
require_once($site_dbConnect);

$keyScanSet = isset($_REQUEST['setid']) ? $_REQUEST['setid'] : false;
$keyScanDet = isset($_REQUEST['detid']) ? $_REQUEST['detid'] : false;
$which = isset($_REQUEST['which']) ? $_REQUEST['which'] : 'ff';

if ($keyScanDet) {
    header("Content-type: text/csv");

    if ($which == 'nf')
        $csv_filename = "Nearfield_$keyScanDet.csv";
    else
        $csv_filename = "Farfield_$keyScanDet.csv";

    header("Content-Disposition: attachment; filename=$csv_filename");
    header("Pragma: no-cache");
    header("Expires: 0");

    $scanDet = new ScanDetails($keyScanDet);

    if ($which == 'nf')
        echo "!Nearfield Beam Listing\r\n";
    else
        echo "!Farfield Beam Listing\r\n";

    echo "!ScanSetDetails.keyId=" . $scanDet->fkScanSetDetails . "\r\n";
    echo "!ScanDetails.keyId=$keyScanDet\r\n";
    echo "!Pol=" . $scanDet->pol . ($scanDet->copol ? " copol" : " xpol") . "\r\n";
    echo "!IFAtten=" . $scanDet->ifatten . "\r\n";
    echo "!SourceRotAngle=" . $scanDet->SourceRotationAngle . "\r\n";

    if ($keyScanSet) {
        $scanSet = new ScanSetDetails($keyScanSet);
        echo "!Elevation=" . $scanSet->tilt . "\r\n";
        echo "!RF_GHz=" . $scanSet->f . "\r\n";
        unset($scanSet);
    }

    echo "\r\n\r\n";

    if ($which == 'nf')
        $q = "SHOW COLUMNS FROM BeamListings_nearfield;";
    else
        $q = "SHOW COLUMNS FROM BeamListings_farfield;";

    $r = mysqli_query(site_getDbConnection(), $q);
    $first = true;
    while ($row = mysqli_fetch_array($r)) {
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

    $r = mysqli_query(site_getDbConnection(), $q);
    while ($row = mysqli_fetch_array($r, MYSQLI_NUM)) {
        $first = true;
        foreach ($row as $cell) {
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
