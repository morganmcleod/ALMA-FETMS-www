<?php
// called from pickComponent.js

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_dbConnect);

$ctype = isset($_REQUEST['ctype']) ? $_REQUEST['ctype'] : FALSE;
$band = isset($_REQUEST['band']) ? $_REQUEST['band'] : FALSE;
$query = FALSE;

if ($ctype == '100') {
    $query = "SELECT CONCAT('FE', '-', REPEAT('0', 2-LENGTH(SN)), SN), A.keyFEConfig
              FROM Front_Ends, FE_Config A
              LEFT OUTER JOIN FE_Config B
              ON (A.fkFront_Ends = B.fkFront_Ends AND A.keyFEConfig < B.keyFEConfig)
              WHERE B.keyFEConfig IS NULL
              AND Front_Ends.keyFrontEnds = A.fkFront_Ends
              ORDER BY SN;";
} else if ($ctype == '11' || $ctype == '20') {
    $what = ($ctype == '11') ? "WCA" : "CCA";

    $query = "SELECT CONCAT('$what', A.Band, '-', REPEAT('0', 2-LENGTH(A.SN)), A.SN) AS cName, A.keyId
              FROM FE_Components A
              LEFT OUTER JOIN FE_Components B
              ON (A.fkFE_ComponentType = B.fkFE_ComponentType
              AND A.Band = B.Band
              AND A.SN = B.SN
              AND A.keyId < B.keyId)
              WHERE B.keyId IS NULL
              AND A.fkFE_ComponentType = $ctype
              AND A.Band = $band
              ORDER BY cName ASC;";
}

if (!$query) {
    echo "[]";

} else {
    $r = mysql_query($query, $db);

    $output = FALSE;

    while ($row = mysql_fetch_array($r)) {

        if (!$output)
            $output = '[';
        else
            $output .= ',';

        $output .= '{"name":"' . $row[0] . '","id":"' . $row[1] . '"}';
    }

    $output .= ']';
    echo $output;
}
?>
