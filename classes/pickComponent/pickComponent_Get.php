<?php
// called from pickComponent.js

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
// require_once($site_classes . '/class.frontend.php');
require_once($site_dbConnect);

$q = "SELECT SN, A.keyFEConfig
      FROM Front_Ends, FE_Config A
      LEFT OUTER JOIN FE_Config B
      ON (A.fkFront_Ends = B.fkFront_Ends AND A.keyFEConfig < B.keyFEConfig)
      WHERE B.keyFEConfig IS NULL
      AND Front_Ends.keyFrontEnds = A.fkFront_Ends
      ORDER BY SN;";

$r = mysql_query($q, $db);

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
?>
