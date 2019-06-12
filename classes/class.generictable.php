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

class GenericTable{
    var $propertyNames; //Array of column headers for a table
    var $propertyVals;  //Array of field values for a tabel record.
    var $tableName;     //Name of the table
    var $keyId;         //Primary key field values
    var $keyId_name;    //Column name of primary key field
    var $dbconnection;  //Database connection
    var $fc;            //Facility Code.
    var $fckeyname;     //faciliity key name
    var $subheader;     //Generic table object, for a record in a subheader table with a
                        //foreign key pointing to this object record.

    public function GetValue($ValueName){
        /*
         * Arguments:
         * ValueName- Column name
         *
         * Searches the array propertyVals where index value equals the index in propertyNames
         * where propertyNames element = ValueName.
         *
         * Returns the value of the specified field (ValueName).
         */
        $index = array_search($ValueName, $this->propertyNames, true);
        if ($index === false || !isset($this->propertyVals[$index]))
            return '';
        else
            return stripslashes($this->propertyVals[$index]);
    }

    public function SetValue($ValueName,$SetValue){
        /*
         *Arguments:
         *ValueName- Name of parameter
         *SetValue- New value to apply to that parameter
         *
         *Sets the appropriate value in propertyVals to the specified value (SetValue).
         */
        $index = array_search($ValueName,$this->propertyNames,true);
        if ($index !== false)
            $this->propertyVals[$index] = $SetValue;
    }

    public function Initialize($tableName, $in_keyId = "", $in_keyId_name, $in_fc = '0', $in_fckeyname = 'none'){
        /*
         * This function retrieves all field values and field names for a spefcific table record.
         * The retrieved values and names comprise the parameter names and values of this object.
         *
         * Arguments:
         * tableName    - Name of database table
         * in_keyId     - Value of primary key field
         * in_keyId_name- Name of primary key field
         * in_fc        - Facility code value (optional)
         * in_fckeyname - Name of facility code key field (optional)
         */

        $this->tableName = $tableName;
        $this->keyId = $in_keyId;
        $this->keyId_name = $in_keyId_name;
        $this->fckeyname = $in_fckeyname;
        $this->dbconnection = site_getDbConnection();

        //Get parameter names (column names in table)
        $q = "show columns from $tableName;";
        $r = mysqli_query($this->dbconnection, $q);
        $counter=0;
        while ($res = mysqli_fetch_array($r)){
            $this->propertyNames[$counter] = $res[0];
            $counter++;
        }

        //Get parameter values. Facility code is optional, so one of two possible
        //queries will be used.
        if ($this->fckeyname != 'none'){
            $qVals = "SELECT * FROM $tableName WHERE $in_keyId_name = $in_keyId AND $this->fckeyname = $in_fc;";
        }
        if ($this->fckeyname == 'none'){
            $qVals = "SELECT * FROM $tableName WHERE $in_keyId_name = $in_keyId;";
        }
        $rVals = mysqli_query($this->dbconnection, $qVals);
        $this->propertyVals = mysqli_fetch_array($rVals);
    }

    public function NewRecord($tableName, $in_keyIdname = 'keyId', $in_fc = '0', $in_fckeyname = 'none'){
        /*
         * This function creates a new record in the database.
         *
         * Arguments:
         * tableName    - Name of table
         * in_keyIdname - Name of primary key field
         * in_fc        - Facility code (optional)
         * in_fckeynamee- Name of facility code field (optional)
         */
        $this->tableName = $tableName;
        $this->keyId_name = $in_keyIdname;
        $this->fckeyname = $in_fckeyname;

        //If no facility code is provided, a default value is obtained in config_main.php.
        //dbconnection is created as a persisten connection in dbConnect.php.
        $this->dbconnection = site_getDbConnection();

        //Facility code is optional, so one of two INSERT statements will be used for the new record.
        if ($this->fckeyname != "none"){
            $qNew = "INSERT INTO $this->tableName($in_fckeyname) VALUES($in_fc);";
        }
        if ($this->fckeyname == "none"){
            $qNew = "INSERT INTO $this->tableName() VALUES();";
        }

        $rNew = mysqli_query($this->dbconnection, $qNew);

        //After the record has been created, get the new primary key value
        $qNew = "SELECT MAX($this->keyId_name) FROM $this->tableName;";
        $rNew = mysqli_query($this->dbconnection, $qNew);
        $this->keyId = ADAPT_mysqli_result($rNew,0);

        //Call the Initialize function again, so that this object represents what is in the new record.
        $this->Initialize($tableName,$this->keyId,$this->keyId_name,$in_fc,$in_fckeyname);
    }

    public function RequestValues(){
        /*
         * This function requests GET and POST variables sent to a PHP page.
         * It checks all of its paramter names and looks for any GET or POST variables with the same name.
         * If a GET/POST variable is found with the same name as an object parameter, that parameter value
         * is updated with the variable value.
         */

        //Iterate through all of this objects parameter names
        foreach ($this->propertyNames as &$propertyName){
            //If a GET or POST variable has the same name, set the
            //corresponding parameter value of thsi object.
            if (isset($_REQUEST[$propertyName])){
            $this->SetValue($propertyName,$_REQUEST[$propertyName]);
            }
        }

        //If 'deleterecord' is set, display a basic delete form.
        if (isset($_REQUEST['deleterecord'])){
            $this->Display_delete_form();
        }
        //if the 'deleterecord_forsure' value is set, delete the record.
        if (isset($_REQUEST['deleterecord_forsure'])){
            $this->Delete_record();
        }
    }

    public function Update(){
        /*
         * This function updates the datbase so that all of the record fields contain
         * the current object parameter values.
         */

        //Create the update query.
        $qu = "UPDATE $this->tableName SET ";
        foreach($this->propertyNames as $tempName){
            if ($tempName != $this->keyId_name){
                $qu .= " $tempName='" . mysqli_real_escape_string($this->dbconnection, $this->propertyVals[array_search($tempName,$this->propertyNames,true)]) . "',";
            }
        }

        //Remove the last comma from the query.
        $qu=substr($qu,0,strlen($qu)-1);

        //There may be a facilty code, so one of two possible "WHERE" clauses will be used accordingly.
        if ($this->fckeyname == 'none'){
            $qu .= " WHERE $this->keyId_name = $this->keyId LIMIT 1;";
        }
        if ($this->fckeyname != 'none'){
            $qu .= " WHERE $this->keyId_name = $this->keyId
                    AND $this->fckeyname = " . $this->GetValue($this->fckeyname) . " LIMIT 1;";
        }
        $ru = mysqli_query($this->dbconnection, $qu);
    }

    public function Display_data(){
        /*
         * This function displays a basic table showing all parameter names and values.
         * Values are shown in editable fields, and a "SAVE CHANGES" button allows for updating
         * of edited parameters. A "DELETE RECORD" button allows for deletion of the record.
         * This function is only used for debugging purposes and will not appear in any production code.
         */
        echo "<br><font size='+2'><b>$this->tableName</b></font><br>";
        echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
            echo "<div style ='width:100%;height:30%'>";
            echo "<div align='right' style ='width:70%;height:30%'>";

            $NameIndex=0;
            foreach($this->propertyNames as $tempName){
                if ($tempName != $this->keyId_name){
                    echo "<br>$tempName<input type='text' name='$tempName' size='50'
                    maxlength='200' value = '".$this->propertyVals[$NameIndex]."'>";
                }
                $NameIndex++;
            }

            echo "<input type='hidden' name='$this->keyId_name' value='$this->keyId'>";
            echo "<input type='hidden' name='fc' value='$this->fc'>";
            echo "<input type='hidden' name='tablename' value='$this->tableName'>";
            echo "<br><br><input type='submit' name = 'submitted' value='SAVE CHANGES'>";
            echo "<input type='submit' name = 'deleterecord' value='DELETE RECORD'>";
            echo "</div></div>";
        echo "</form>";
    }

    public function Display_delete_form(){
        /*
         * This function displays a form asking if the user is sure they want to
         * delete a record.
         */

        echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">
        <b><font size="+1">Are you sure you want to delete this record?</b></font>';

        echo "<input type='hidden' name= 'fc' value='" . $this->GetValue($this->fckeyname) . "' />";
        echo "<br><input type='submit' name = 'deleterecord_forsure' value='YES, DELETE RECORD'><br><br>";
        echo "<input type='hidden' name='" . $this->keyId_name . "' value='" . $this->keyId . "'></form>";

    }
    public function Delete_record(){
        /*
         * This function deletes the record from the database.
         */

        //A facility code may be present, so one of two possible delete queries is used.
        if ($this->fckeyname == 'none'){
            $qdelete = "DELETE FROM $this->tableName WHERE $this->keyId_name =$this->keyId LIMIT 1";
        }
        if ($this->fckeyname != 'none'){
            $qdelete = "DELETE FROM $this->tableName WHERE $this->keyId_name =$this->keyId
                        AND $this->fckeyname = $this->fc LIMIT 1";
        }
        $rdelete = mysqli_query($this->dbconnection, $qdelete);
    }

    public function DuplicateRecord(){
        /*
         * This function creates a new record in the database, and copies the current parameter values into the
         * new record.
         */

        //Create the INSERT query using all the parameter names and values of this object.
        $qCopy = "INSERT INTO $this->tableName (";

        for ($i = 0; $i< count($this->propertyNames); $i++){
            if (($this->propertyNames[$i] != $this->keyId_name) && ($this->propertyNames[$i] != "TS")){
                $qCopy .= $this->propertyNames[$i];
                $qCopy .= ",";
            }
        }
        $qCopy = rtrim($qCopy,",") . ") VALUES (";
        for ($i = 0; $i< count($this->propertyNames); $i++){
            if (($this->propertyNames[$i] != $this->keyId_name) && ($this->propertyNames[$i] != "TS")){
                $qCopy .= "'" . $this->propertyVals[$i] . "'";
                $qCopy .= ",";
            }
        }
        $qCopy = rtrim($qCopy,",") . ");";
        $rCopy = mysqli_query($this->dbconnection, $qCopy);

        //After the new duplicate record is created, get the primary key value and reinitialize
        //this object to the newly created record.
        $qMax = "SELECT MAX($this->keyId_name) FROM $this->tableName;";
        $rMax = mysqli_query($this->dbconnection, $qMax);
        $newId = ADAPT_mysqli_result($rMax,0,0);
        $this->Initialize($this->tableName,$newId,$this->keyId_name,$this->GetValue($this->fckeyname), $this->fckeyname);
    }
}
?>