<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);

class TestData_Types {
    public static function getTableNameFromKeyId($keyId) {
        $dbConnection = site_getDbConnection();
        $q = "SELECT TestData_TableName FROM TestData_Types
              WHERE keyId={$keyId};";
        $r = mysqli_query($dbConnection, $q);
        return ADAPT_mysqli_result($r, 0);
    }

    public static function getDescriptionFromKeyId($keyId) {
        $dbConnection = site_getDbConnection();
        $q = "SELECT Description FROM TestData_Types
              WHERE keyId={$keyId};";
        $r = mysqli_query($dbConnection, $q);
        return ADAPT_mysqli_result($r, 0);
    }
}
