<?php
// called from pickComponent.js

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_dbConnect);

$json = json_decode(file_get_contents('php://input'), true);

$ctype = isset($json['ctype']) ? $json['ctype'] : FALSE;
$band = isset($json['band']) ? $json['band'] : FALSE;
$newCfg = isset($json['newCfg']) ? $json['newCfg'] : FALSE;
$tdhIdArray = isset($json['tdhIdArray']) ? $json['tdhIdArray'] : FALSE;

$query = FALSE;
$output = '{"success" : false}';

if ($tdhIdArray && $newCfg) {
    $where = "";
    foreach ($tdhIdArray as $tdh) {
        if ($where)
            $where .= " OR ";
        $where .= "keyId = $tdh";
    }
    if ($ctype == '100') {
        $query = "UPDATE TestData_header
                  SET fkFE_Config = $newCfg
                  WHERE ($where);";
    }
    if ($query) {
        $r = mysqli_query($link, $query);
        if ($r)
            $output = '{"success" : true}';
    }
}
echo $output;
?>