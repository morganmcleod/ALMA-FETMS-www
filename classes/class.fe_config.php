<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);

class FE_Config {
    public static function getFrontEndFromKeyId($keyId) {
        $dbConnection = site_getDbConnection();
        $qfe = "SELECT fkFront_Ends FROM FE_Config
                WHERE keyFEConfig={$keyId};";
        $rfe = mysqli_query($dbConnection, $qfe);
        return ADAPT_mysqli_result($rfe, 0);
    }
}
