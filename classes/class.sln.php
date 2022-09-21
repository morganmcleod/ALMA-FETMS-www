<?php
class SLN extends GenericTable {
    // FE_StatusLocationsAndNotes columns
    public $keyFacility;
    public $keyId;
    public $fkFEComponents;
    public $fkFEConfig;
    public $fkLocationNames;
    public $fkStatusType;
    public $TS;
    public $Notes;
    public $lnk_Data;
    public $Updated_By;

    var $fc;
    var $location;
    var $status;

    public function __construct($inKeyId, $inFc = 40) {
        parent::__construct("FE_StatusLocationAndNotes", $inKeyId, "keyId", $inFc, 'keyFacility');
        $this->fc = $inFc;

        $q = "SELECT Description, Notes FROM Locations
              WHERE keyId = " . $this->fkLocationNames . ";";
        $r = mysqli_query($this->dbConnection, $q);
        ADAPT_mysqli_result($r, 0, 0);
        $this->location = ADAPT_mysqli_result($r, 0, 0) . " (" . ADAPT_mysqli_result($r, 0, 1) . ")";

        $q = "SELECT Status FROM StatusTypes
              WHERE keyStatusType = " . $this->fkStatusType . ";";
        $r = mysqli_query($this->dbConnection, $q);
        $this->status = ADAPT_mysqli_result($r, 0, 0);
    }

    public static function getMaxIdFromComponent($fkComponent) {
        $dbConnection = site_getDbConnection();
        $q = "SELECT MAX(keyId) FROM FE_StatusLocationAndNotes
              WHERE fkComponent = $fkComponent;";
        return mysqli_query($dbConnection, $q);
    }

    public static function getMaxIdFromConfig($fkFEConfig) {
        $dbConnection = site_getDbConnection();
        $q = "SELECT MAX(keyId) FROM FE_StatusLocationAndNotes
              WHERE fkFEConfig = $fkFEConfig;";
        return mysqli_query($dbConnection, $q);
    }
}
