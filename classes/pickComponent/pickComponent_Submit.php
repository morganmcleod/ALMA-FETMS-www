<?php
// called from pickComponent.js

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_dbConnect);

$ctype = isset($_REQUEST['ctype']) ? $_REQUEST['ctype'] : FALSE;
$band = isset($_REQUEST['band']) ? $_REQUEST['band'] : FALSE;
$tdhId = isset($_REQUEST['tdhId']) ? $_REQUEST['tdhId'] : FALSE;
$oldCfg = isset($_REQUEST['oldCfg']) ? $_REQUEST['oldCfg'] : FALSE;
$newCfg = isset($_REQUEST['newCfg']) ? $_REQUEST['newCfg'] : FALSE;
$query = FALSE;
$output = '{"success" : false}';

if ($tdhId && $oldCfg && $newCfg) {
    if ($ctype == '100') {
        $query = "UPDATE TestData_header
                  SET fkFE_Config = $newCfg
                  WHERE keyId = $tdhId
                  AND fkFE_Config = $oldCfg;";
    }
    if ($query) {
        $r = mysql_query($query, $db);
        if ($r)
            $output = '{"success" : true}';
    }
}
echo $output;
?>