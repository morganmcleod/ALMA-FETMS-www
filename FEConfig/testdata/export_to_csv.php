<?php

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_dbConnect);
$dbconnection = site_getDbConnection();

$fc = $_REQUEST['fc'];

// this block does NOT handle IF spectrum:
if (!isset($_REQUEST['ifsub'])) {

    // this block handles any other data with a TDH key provided:
    if (isset($_REQUEST['keyheader'])) {
        $TestData_header_keyId = $_REQUEST['keyheader'];
        $td = new TestData_header();
        $td->Initialize_TestData_header($TestData_header_keyId, $fc);
        $testDataType = $td->GetValue('fkTestData_Type');
        $csv_filename = str_replace(" ", "_", $td->TestDataType) . "_Band" . $td->GetValue('Band');

        if ($testDataType == 29) {
            //Workmanship Amplitude:  get the LO frequency to include in the filename.
            $q1 = "SELECT lo from TEST_Workmanship_Amplitude_SubHeader WHERE fkHeader = $td->keyId;";
            $r1 = mysqli_query($dbconnection, $q1);
            $LO = ADAPT_mysqli_result($r1, 0, 0);

            $csv_filename .= "_LO$LO";
        }
        $csv_filename .= ".csv";
        $preCols = "";

        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=$csv_filename");
        header("Pragma: no-cache");
        header("Expires: 0");

        switch ($td->GetValue('fkTestData_Type')) {

            case 57: //LO Lock Test
                $q1 = "SELECT keyId FROM TEST_LOLockTest_SubHeader WHERE fkHeader = $td->keyId;";
                $r1 = mysqli_query($dbconnection, $q1);
                $subh_id = ADAPT_mysqli_result($r1, 0, 0);
                $qdata = "SELECT DT.*
                         FROM TEST_LOLockTest as DT, TEST_LOLockTest_SubHeader as SH, TestData_header as TDH
                         WHERE DT.fkHeader = SH.keyId AND DT.fkFacility = SH.keyFacility
                         AND SH.fkHeader = TDH.keyId AND SH.keyFacility = TDH.keyFacility"
                    . " AND TDH.keyId = " . $TestData_header_keyId
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
                $r = mysqli_query($dbconnection, $q);
                $subid = ADAPT_mysqli_result($r, 0, 0);
                $qdata = "SELECT * FROM Noise_Temp WHERE fkSub_Header = $subid AND keyFacility = "
                    . $td->GetValue('keyFacility') . " ORDER BY FreqLO, CenterIF;";
                $td->TestDataTableName = 'Noise_Temp';
                break;

            case 59:
                //fine LO Sweep
                $qdata = "SELECT HT.Pol, DT.*
                        FROM TEST_FineLOSweep AS DT, TEST_FineLOSweep_SubHeader AS HT
                        WHERE HT.fkHeader = $td->keyId
                        AND DT.fkSubHeader = HT.keyId;";

                $preCols = "Pol,";
                $td->TestDataTableName = 'TEST_FineLOSweep';
                break;

            default:
                $qdata = "SELECT * FROM $td->TestDataTableName WHERE fkHeader = $td->keyId;";
                break;
        }

        switch ($td->Component->GetValue('fkFE_ComponentType')) {
            case 6:
                //Cryostat
                $q = "SELECT keyId FROM TEST_Cryostat_data_SubHeader
                      WHERE fkHeader = $td->keyId;";
                $r = mysqli_query($dbconnection, $q);
                $fkHeader = ADAPT_mysqli_result($r, 0, 0);
                $qdata = "SELECT * FROM $td->TestDataTableName WHERE
                fkSubHeader = $fkHeader AND fkFacility = " . $td->GetValue('keyFacility') . ";";
                break;
        }

        //Output records to csv file
        echo $preCols;

        $qcols = "SHOW COLUMNS FROM $td->TestDataTableName;";

        $rcols = mysqli_query($dbconnection, $qcols);
        $count = $rcols->num_rows;
        while ($rowcols = mysqli_fetch_array($rcols)) {
            echo $rowcols[0];
            if (--$count <= 0) {
                break;
            }
            echo ",";
        }
        echo "\r\n";

        $rdata = mysqli_query($dbconnection, $qdata);
        foreach($rdata as $rowdata) {
            $count = count($rowdata);
            foreach ($rowdata as $key => $value) {
                echo "$value";
                if (--$count <= 0) {
                    break;
                }
                echo ",";
            }
            echo "\r\n";
        };
    }
}

// This block handles only IF spectrum data:
if (isset($_REQUEST['ifsub'])) {
    $ifsub_id = $_REQUEST['ifsub'];
    $TestData_header_keyId = $_REQUEST['keyheader'];
    $fc = $_REQUEST['fc'];

    $ifsub = new GenericTable();
    $ifsub->Initialize('IFSpectrum_SubHeader', $ifsub_id, 'keyId', $fc, 'keyFacility');


    header("Content-type: text/csv");
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
    $r = mysqli_query($dbconnection, $q);
    while ($row = mysqli_fetch_array($r)) {
        echo $row[0] . ",";
    }
    echo "\r\n";

    $q = "SELECT * FROM IFSpectrum WHERE fkSubHeader = $ifsub_id AND fkFacility = $fc;";
    $r = mysqli_query($dbconnection, $q);
    while ($row = mysqli_fetch_array($r)) {
        for ($i = 0; $i < count($row); $i++) {
            echo "$row[$i],";
        }
        echo "\r\n";
    }
}

unset($td);
?>
