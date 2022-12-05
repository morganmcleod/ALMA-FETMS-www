<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');

class CCA_TempSensorConfig extends GenericTable {
    public $keyId;
    public $fkFacility;
    public $fkComponent;
    public $TS;
    public $Location;
    public $Model;
    public $SN;
    public $OffsetK;
    public $Notes;

    public function __construct($inKeyId, $inFc) {
        parent::__construct("CCA_TempSensorConfig", $inKeyId, "keyId", $inFc, 'fkFacility');
    }

    public static function getIdFromComponent($fkComponent) {
        $dbConnection = site_getDbConnection();
        $q = "SELECT keyId, Location FROM CCA_TempSensorConfig
              WHERE fkComponent = $fkComponent
              ORDER BY Location ASC;";
        return mysqli_query($dbConnection, $q);
    }
    public function DuplicateRecord() {
        /*
         * This function creates a new record in the database, and copies the current parameter values into the
         * new record.
         */

        //Create the INSERT query using all the parameter names and values of this object.
        $qCopy = "INSERT INTO $this->tableName (";

        foreach ($this->columnNames as $column) {
            if (($column != $this->keyIdName) && ($column != "TS")) {
                $qCopy .= $column . ",";
            }
        }
        $qCopy = rtrim($qCopy, ",") . ") VALUES (";
        foreach ($this->columnNames as $column) {
            if (($column != $this->keyIdName) && ($column != "TS")) {
                $qCopy .= "'{$this->{$column}}',";
            }
        }
        $qCopy = rtrim($qCopy, ",") . ");";
        mysqli_query($this->dbConnection, $qCopy);

        //After the new duplicate record is created, get the primary key value and reinitialize
        //this object to the newly created record.
        $qMax = "SELECT MAX($this->keyIdName) FROM $this->tableName;";
        $rMax = mysqli_query($this->dbConnection, $qMax);
        $newId = ADAPT_mysqli_result($rMax, 0, 0);
        $this->__construct($newId, $this->fkFacility);
    }
}
