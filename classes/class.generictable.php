<?php
/*
 * class.generictable.php
 *
 * This class is the foundation for much of the Front End configuration and data browsing site.
 * It represents a record from a table. A table may have one or two primary key fields.
 * For our database, many tables have a second primary key field denoting "Facility Code" (Our facility is 40).
 *
 *
 * Methods include:
 *
 * GetValue            - Returns a specified parameter
 * SetValue            - Set a specified parameter to a specified value
 * Initialize          - Retrieves parameter names and values from a record, based on arguments supplied.
 * NewRecord           - Creates a new record in the database
 * RequestValues       - Checks for GET or POST variables with the same name as the object's parameters.
 *                            Updates any parameters accordingly.
 * Update               - Updates the database record with the current object parameter values.
 * Dispay_data         - A basic function for display object parameter names and values (only for debugging).
 * Display_delete_form - Displays a basic form asking if the user is sure they want to delete the record.
 * Delete_record       - Deletes the current record from the database.
 * DuplicateRecord     - Copies the current parameter values into a new table record.
 *
 */

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);

class GenericTable {
    var $columnNames;   // Array of column headers for a table
    var $tableName;     // Name of the table
    var $keyId;         // Primary key field values
    var $keyIdName;     // Column name of primary key field
    var $dbConnection;  // Database connection
    var $fc;            // Facility Code.
    var $fcKeyName;     // faciliity key name
    var $subHeader;     // Generic table object, for a record in a subHeader table with a
    // foreign key pointing to this object record.

    public function __construct($tableName, $inKeyId = "", $inKeyIdName, $inFc = '40', $inFcKeyName = 'none') {
        $this->dbConnection = site_getDbConnection();
        if (empty($tableName)) return;
        $this->tableName = $tableName;
        $this->keyId = $inKeyId;
        $this->keyIdName = $inKeyIdName;
        $this->fcKeyName = $inFcKeyName;

        // Get parameter names (column names in table)
        $q = "SHOW COLUMNS FROM {$tableName};";
        $r = mysqli_query($this->dbConnection, $q);
        $this->columnNames = [];
        while ($res = mysqli_fetch_array($r)) {
            array_push($this->columnNames, $res[0]);
            $this->{$res[0]} = "";
        }

        // Get parameter values. Facility code is optional, so one of two possible
        // queries will be used.
        if ($inKeyId) {
            if ($this->fcKeyName != 'none') {
                $qVals = "SELECT * FROM $tableName
                    WHERE $inKeyIdName = $inKeyId AND $this->fcKeyName = $inFc LIMIT 1;";
            }
            if ($this->fcKeyName == 'none') {
                $qVals = "SELECT * FROM $tableName
                    WHERE $inKeyIdName = $inKeyId LIMIT 1;";
            }
            $rVals = mysqli_query($this->dbConnection, $qVals);
            $arrayValues = mysqli_fetch_row($rVals);
            if (is_array($arrayValues) || is_object($arrayValues)) {
                foreach ($arrayValues as $key => $value) {
                    $colName = $this->columnNames[$key];
                    $this->{$colName} = $value ?? "";
                }
            }
        }
    }

    public function GetValue($valueName) {
        /*
         * Arguments:
         * valueName- Column name
         *
         * Searches the array propertyVals where index value equals the index in propertyNames
         * where propertyNames element = valueName.
         *
         * Returns the value of the specified field (valueName).
         */
        if (!isset($this->{$valueName})) return '';
        else return stripslashes($this->{$valueName});
    }

    public function SetValue($valueName, $setValue) {
        /*
         *Arguments:
         *valueName- Name of parameter
         *setValue- New value to apply to that parameter
         *
         *Sets the appropriate value in propertyVals to the specified value (setValue).
         */
        $this->{$valueName} = $setValue;
    }

    public static function NewRecord($tableName, $inKeyIdName = 'keyId', $inFc = '40', $inFcKeyName = 'none') {
        /*
         * This function creates a new record in the database.
         *
         * Arguments:
         * tableName    - Name of table
         * inKeyIdName  - Name of primary key field
         * inFc         - Facility code (optional)
         * inFcKeyName  - Name of facility code field (optional)
         */
        $dbConnection = site_getDbConnection();

        // If no facility code is provided, a default value is obtained in config_main.php.
        // Facility code is optional, so one of two INSERT statements will be used for the new record.
        if ($inFcKeyName != "none") {
            $qNew = "INSERT INTO $tableName($inFcKeyName) VALUES($inFc);";
        } else {
            $qNew = "INSERT INTO $tableName() VALUES();";
        }

        $rNew = mysqli_query($dbConnection, $qNew);

        // After the record has been created, get the new primary key value
        $qNew = "SELECT MAX($inKeyIdName) FROM $tableName;";
        $rNew = mysqli_query($dbConnection, $qNew);
        $keyId = ADAPT_mysqli_result($rNew, 0);

        // Call the Initialize function again, so that this object represents what is in the new record.
        return new self($tableName, $keyId, $inKeyIdName, $inFc, $inFcKeyName);
    }

    public function RequestValues() {
        /*
         * This function requests GET and POST variables sent to a PHP page.
         * It checks all of its paramter names and looks for any GET or POST variables with the same name.
         * If a GET/POST variable is found with the same name as an object parameter, that parameter value
         * is updated with the variable value.
         */

        // Iterate through all of this objects parameter names
        foreach ($this->columnNames as &$column) {
            // If a GET or POST variable has the same name, set the
            // corresponding parameter value of this object.
            if (isset($_REQUEST[$column])) {
                $this->SetValue($column, $_REQUEST[$column]);
            }
        }

        // If 'deleterecord' is set, display a basic delete form.
        if (isset($_REQUEST['deleterecord'])) $this->Display_delete_form();

        // if the 'deleterecord_forsure' value is set, delete the record.
        if (isset($_REQUEST['deleterecord_forsure'])) $this->Delete_record();
    }

    public function Update() {
        /*
         * This function updates the datbase so that all of the record fields contain
         * the current object parameter values.
         */

        // Create the update query.
        $qu = "UPDATE $this->tableName SET";
        foreach ($this->columnNames as $column) {
            if ($column != $this->keyIdName) {
                $qu .= " $column='"
                    . mysqli_real_escape_string(
                        $this->dbConnection,
                        $this->{$column}
                    )
                    . "',";
            }
        }

        // Remove the last comma from the query.
        $qu = substr($qu, 0, strlen($qu) - 1);

        // There may be a facilty code, so one of two possible "WHERE" clauses will be used accordingly.
        if ($this->fcKeyName == 'none') {
            $qu .= " WHERE {$this->keyIdName} = {$this->keyId} LIMIT 1;";
        } else {
            $qu .= " WHERE {$this->keyIdName} = {$this->keyId}
                AND {$this->fcKeyName} = {$this->GetValue($this->fcKeyName)} LIMIT 1;";
        }
        mysqli_query($this->dbConnection, $qu);
    }

    public function Display_data() {
        /*
         * This function displays a basic table showing all parameter names and values.
         * Values are shown in editable fields, and a "SAVE CHANGES" button allows for updating
         * of edited parameters. A "DELETE RECORD" button allows for deletion of the record.
         * This function is only used for debugging purposes and will not appear in any production code.
         */
        echo "<br><font size='+2'><b>$this->tableName</b></font><br>";
        echo "<form action='{$_SERVER["PHP_SELF"]}' method='post'>";
        echo "<div style='width:100%;height:30%'>";
        echo "<div align='right' style='width:70%;height:30%'>";

        $NameIndex = 0;
        foreach ($this->columnNames as $column) {
            if ($column != $this->keyIdName) {
                echo "<br>$column<input type='text' name='$column' size='50'
                    maxlength='200' value='{$this->{$column}}'>";
            }
            $NameIndex++;
        }

        echo "<input type='hidden' name='{$this->keyIdName}' value='{$this->keyId}'>";
        echo "<input type='hidden' name='fc' value='{$this->fc}'>";
        echo "<input type='hidden' name='tablename' value='{$this->tableName}'>";
        echo "<br><br><input type='submit' name='submitted' value='SAVE CHANGES'>";
        echo "<input type='submit' name='deleterecord' value='DELETE RECORD'>";
        echo "</div></div>";
        echo "</form>";
    }

    public function Display_delete_form() {
        /*
         * This function displays a form asking if the user is sure they want to
         * delete a record.
         */

        echo "<form action='{$_SERVER["PHP_SELF"]}' method='post'>
            <b><font size='+1'>Are you sure you want to delete this record?</b></font>";

        echo "<input type='hidden' name='fc' value='{$this->GetValue($this->fcKeyName)}' />";
        echo "<br><input type='submit' name='deleterecord_forsure' value='YES, DELETE RECORD'><br><br>";
        echo "<input type='hidden' name='{$this->keyIdName}' value='{$this->keyId}'></form>";
    }

    public function Delete_record() {
        /*
         * This function deletes the record from the database.
         */

        //A facility code may be present, so one of two possible delete queries is used.
        if ($this->fcKeyName == 'none') {
            $qdelete = "DELETE FROM $this->tableName WHERE $this->keyIdName =$this->keyId LIMIT 1";
        } else {
            $qdelete = "DELETE FROM $this->tableName WHERE $this->keyIdName =$this->keyId
                        AND $this->fcKeyName = $this->fc LIMIT 1";
        }
        mysqli_query($this->dbConnection, $qdelete);
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
        $this->__construct($this->tableName, $newId, $this->keyIdName, $this->GetValue($this->fcKeyName), $this->fcKeyName);
    }
}
