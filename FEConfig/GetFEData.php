<?php
// called from dbGrid.js

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_libraries . '/array_column/src/array_column.php');
require_once($site_dbConnect);
$dbconnection = site_getDbConnection();
require('dbGetQueries.php');

$ctype=$_GET['ctype'];

$getqueries=new dbGetQueries;

if($ctype==100)
{
    //Front Ends
    $q = "SELECT SN, keyFrontEnds, Docs, A.keyFEConfig, A.TS
          FROM Front_Ends, FE_Config A
          LEFT OUTER JOIN FE_Config B
          ON (A.fkFront_Ends = B.fkFront_Ends AND A.keyFEConfig < B.keyFEConfig)
          WHERE B.keyFEConfig IS NULL
          AND Front_Ends.keyFrontEnds = A.fkFront_Ends
          ORDER BY SN;";

    $rfe = mysqli_query($dbconnection, $q);

    $feRecs = array();
    $configIds = FALSE;

    while ($row = mysqli_fetch_array($rfe)) {
        $feRecs[] = $row;
        if ($configIds)
            $configIds .= ",";
        else
            $configIds = "";
        $configIds .= $row['keyFEConfig'];
    };

    // Status, location, notes
    $q = "SELECT A.fkFEConfig, A.TS, A.Notes
          FROM FE_StatusLocationAndNotes A
          LEFT OUTER JOIN FE_StatusLocationAndNotes B
          ON (A.fkFEConfig = B.fkFEConfig AND A.keyId < B.keyId)
          WHERE B.keyId IS NULL
          AND A.fkFEConfig IN ($configIds);";

    $rsl = mysqli_query($dbconnection, $q);
    
    $slnRecs = array();

    while ($row = mysqli_fetch_array($rsl))
        $slnRecs[] = $row;

    $retJSON = FALSE;

    // Build the JSON string from the front end records, finding the corresponding SLN records:
    foreach ($feRecs as $row) {
        if (!$retJSON)
            $retJSON = "[";
        else
            $retJSON .= ",";

        $slKey = array_search($row['keyFEConfig'], array_column($slnRecs, 'fkFEConfig'));

        $retJSON .= "{'config':'" . $row['keyFEConfig'] . "'";
        $retJSON .= ",'keyFacility':'40'";
        $retJSON .= ",'SN':'" . $row['SN'] . "'";
        $retJSON .= ",'TS':'" . (($slKey) ? $slnRecs[$slKey]['TS'] : $row['TS']) . "'";
        $retJSON .= ",'Docs':'" . $row['Docs'] . "'";
        $notes = (($slKey) ? $slnRecs[$slKey]['Notes'] : "");
        $retJSON .= ",'Notes':'" . mysqli_real_escape_string($dbconnection, $notes) . "'}";
    }
    $retJSON .= "]";
    echo $retJSON;
}
else
{
    //Components
    $q = "SELECT A.keyId, A.Band, A.SN, A.TS
          FROM FE_Components A
          LEFT OUTER JOIN FE_Components B
          ON (A.fkFE_ComponentType = B.fkFE_ComponentType
            AND A.Band = B.Band
            AND A.SN = B.SN
            AND A.keyId < B.keyId)
          WHERE A.fkFE_ComponentType = $ctype
          AND B.keyId IS NULL
          ORDER BY A.Band ASC, (0 + A.SN) ASC;";

    $rcm = mysqli_query($dbconnection, $q);

    $cmRecs = array();
    $configIds = FALSE;

    while ($row = mysqli_fetch_array($rcm)) {
        $cmRecs[] = $row;
        if ($configIds)
            $configIds .= ",";
        else
            $configIds = "";
        $configIds .= $row['keyId'];
    };

    // Status, location, notes
    $q = "SELECT A.fkFEComponents, A.TS, A.Notes
          FROM FE_StatusLocationAndNotes A
          LEFT OUTER JOIN FE_StatusLocationAndNotes B
          ON (A.fkFEComponents = B.fkFEComponents AND A.keyId < B.keyId)
          WHERE B.keyId IS NULL
          AND A.fkFEComponents IN ($configIds);";

    $rsl = mysqli_query($dbconnection, $q);

    $slnRecs = array();

    while ($row = mysqli_fetch_array($rsl))
        $slnRecs[] = $row;

    // Find the max ConfigLink records which refer to the components in our list,
    //  Also link in the Front_Ends record via FE_Config:
    $q = "SELECT A.fkFE_Components, A.fkFE_Config, Front_Ends.SN
          FROM FE_Config, Front_Ends, FE_ConfigLink A
          LEFT OUTER JOIN FE_ConfigLink B
          ON (A.fkFE_Components = B.fkFE_Components AND A.fkFE_Config < B.fkFE_Config)
          WHERE B.fkFE_Config IS NULL
          AND A.fkFE_Config = FE_Config.keyFEConfig
          AND FE_Config.fkFront_Ends = Front_Ends.keyFrontEnds
          AND A.fkFE_Components IN ($configIds)
          ORDER BY fkFE_Components ASC;";

    $rcl = mysqli_query($dbconnection, $q);
    $clRecs = array();

    while ($row = mysqli_fetch_array($rcl))
        $clRecs[] = $row;

    $retJSON = FALSE;

    // Build the JSON string from the components records, finding the corresponding SLN and FE records:
    foreach ($cmRecs as $row) {
        if (!$retJSON)
            $retJSON = "[";
        else
            $retJSON .= ",";

        $slKey = array_search($row['keyId'], array_column($slnRecs, 'fkFEComponents'));
        $clKey = array_search($row['keyId'], array_column($clRecs, 'fkFE_Components'));

        $retJSON .= "{'config':'" . $row['keyId'] . "'";
        $retJSON .= ",'keyFacility':'40'";
        $retJSON .= ",'Band':'" . $row['Band'] . "'";
        $retJSON .= ",'SN':'" . $row['SN'] . "'";
        $retJSON .= ",'TS':'" . (($slKey) ? $slnRecs[$slKey]['TS'] : $row['TS']) . "'";
        $retJSON .= ",'FESN':'" . (($clKey) ? $clRecs[$clKey]['SN'] : "") . "'";
        $notes = (($slKey) ? $slnRecs[$slKey]['Notes'] : "");
        $retJSON .= ",'Notes':'" . mysqli_real_escape_string($dbconnection, $notes) . "'}";
    }
    $retJSON .= "]";
    echo $retJSON;
}
?>
