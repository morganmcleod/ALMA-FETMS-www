<?php
class GenericTable{
	var $propertyNames;
	var $propertyVals;
	var $tableName;
	var $keyId;
	var $keyId_name;
	var $dbconnection;
	var $fc;  //Facility Code.
	var $fckeyname; //faciliity hey name
	var $logging; //If 1, logging will happen
	var $logfile;
	var $logfile_fh;
	var $password;
	var $subheader; //Generic table object, for a record in a subheader table
	
	
	function __construct() {

		
	}
	public function GetValue($ValueName){
		return stripslashes($this->propertyVals[array_search($ValueName,$this->propertyNames,true)]);
	}
	public function SetValue($ValueName,$SetValue){
		$this->propertyVals[array_search($ValueName,$this->propertyNames,true)] = $SetValue;
	}
	
	public function Initialize($tableName, $in_keyId = "", $in_keyId_name = "keyId", $in_fc = '0', $in_fckeyname = 'none'){
		include('dbConnect.php');


		$this->dbconnection = $db;
		//$this->fc = $in_fc;
		$this->fckeyname = $in_fckeyname;
		
		
		$this->tableName = $tableName;
		$this->keyId_name = $in_keyId_name;
		$this->keyId = $in_keyId;
		$q = "show columns from $tableName;";

		$r = @mysql_query($q,$this->dbconnection);
		$counter=0;
			while ($res = @mysql_fetch_array($r)){
				$this->propertyNames[$counter] = $res[0];
				$counter++;
			}
		if ($this->fckeyname != 'none'){
			$qVals = "SELECT * FROM $tableName 
					  WHERE $in_keyId_name = $in_keyId
					  AND $this->fckeyname = $in_fc;";
			$rVals = @mysql_query($qVals,$this->dbconnection);
		}
		if ($this->fckeyname == 'none'){
			$qVals = "SELECT * FROM $tableName 
					  WHERE $in_keyId_name = $in_keyId;";
			$rVals = @mysql_query($qVals,$this->dbconnection);
		}

		$this->propertyVals = @mysql_fetch_array($rVals);	
		
	}
	
	public function NewRecord($tableName, $in_keyIdname = 'keyId', $in_fc = '0', $in_fckeyname = 'none'){
		$this->keyId_name = $in_keyIdname;

		include('dbConnect.php');


		if(file_exists('../dbConnect/config_main.php')){
			include('../dbConnect/config_main.php');
		}
		if(file_exists('../../dbConnect/config_main.php')){
			include('../../dbConnect/config_main.php');
		}
		//fc is defined in config_main.php
		//$this->fc = $fc;
		if ($in_fc != '0'){
			//$this->fc = $in_fc;
		}
		$this->fckeyname = $in_fckeyname;
		//echo "fckeyname1= " . $this->fckeyname . "<br>";
		$this->dbconnection = $db;
		$this->tableName = $tableName;
		if ($this->fckeyname != "none"){
			$qNew = "INSERT INTO $this->tableName($in_fckeyname) VALUES($in_fc);";
		}
		if ($this->fckeyname == "none"){
			$qNew = "INSERT INTO $this->tableName() VALUES();";
		}
		if ($tableName == 'TestData_header'){
		//echo $qNew . "<br>";
		}
		$rNew = @mysql_query($qNew,$this->dbconnection);
		$qNew = "SELECT MAX($this->keyId_name) FROM $this->tableName;";
		$rNew = @mysql_query($qNew,$this->dbconnection);
		$this->keyId = @mysql_result($rNew,0);
		
		$this->Initialize($tableName,$this->keyId,$this->keyId_name,$in_fc,$in_fckeyname);
		
		$this->SetValue($in_fckeyname, $in_fc);
		//$this->Update();
	if ($this->tableName == 'TestData_header'){
			//echo "keyId ($this->keyId), TS= " . $this->GetValue('TS');
		}
		//echo "fckeyval= " . $this->GetValue($this->fckeyname) . "<br>";
		//echo "fckeyname3= " . $this->fckeyname . "<br>";
		//echo "fckeyname4= " . $in_fckeyname . "<br>";
	}
	
	
	public function RequestValues(){
		foreach ($this->propertyNames as &$propertyName){
			if (isset($_REQUEST[$propertyName])){
			$this->SetValue($propertyName,$_REQUEST[$propertyName]);
			}
		}
		if (isset($_REQUEST['pw'])){
			$this->password = $_REQUEST['password'];
			//echo "request pw= " . $this->password . "<br>";
		}
		if (isset($_REQUEST['deleterecord'])){
			$this->Display_delete_form();
		}
		if (isset($_REQUEST['deleterecord_forsure'])){
			$this->Delete_record();
		}
	}
	
	public function Update(){
		$qu = "UPDATE $this->tableName SET ";
		foreach($this->propertyNames as $tempName){
			if ($tempName != $this->keyId_name){
				$qu .= " $tempName='" . @mysql_real_escape_string($this->propertyVals[array_search($tempName,$this->propertyNames,true)]) . "',";
			}
		}
		
		
		//Remove the last comma
		$qu=substr($qu,0,strlen($qu)-1);
		if ($this->fckeyname == 'none'){
			$qu .= " WHERE $this->keyId_name = $this->keyId LIMIT 1;";	
		}
		if ($this->fckeyname != 'none'){
			$qu .= " WHERE $this->keyId_name = $this->keyId 
			        AND $this->fckeyname = " . $this->GetValue($this->fckeyname) . " LIMIT 1;";	
		}
		
		
		$qu = str_replace(", Column 12=''", '', $qu);
		$ru = @mysql_query($qu,$this->dbconnection);

	}	
	
	public function Display_data(){
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
			echo "<input type='hidden' name='pw' value='$this->password'>";
			echo "<input type='hidden' name='$this->keyId_name' value='$this->keyId'>";
			echo "<input type='hidden' name='fc' value='$this->fc'>";
			echo "<input type='hidden' name='tablename' value='$this->tableName'>";
			echo "<br><br><input type='submit' name = 'submitted' value='SAVE CHANGES'>";
			echo "<input type='submit' name = 'deleterecord' value='DELETE RECORD'>";
			echo "</div></div>";	
		echo "</form>";	
	}
	
	public function Display_delete_form(){
		//echo "password= " . $_REQUEST['pw'];
		echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">
		<b><font size="+1">Are you sure you want to delete this record?</b></font>';
		
		echo "<input type='hidden' name= 'fc' value='$this->fc' />";
		echo "<input type='hidden' name= 'pw' value='$this->password' />";
		echo "<br><input type='submit' name = 'deleterecord_forsure' value='YES, DELETE RECORD'><br><br>";
		echo "<input type='hidden' name='" . $this->keyId_name . "' value='" . $this->keyId . "'></form>";
	
	}
	public function Delete_record(){
		if ($this->fckeyname == 'none'){
			$qdelete = "DELETE FROM $this->tableName WHERE $this->keyId_name =$this->keyId LIMIT 1";	
		}
		if ($this->fckeyname != 'none'){
			$qdelete = "DELETE FROM $this->tableName WHERE $this->keyId_name =$this->keyId
					    AND $this->fckeyname = $this->fc LIMIT 1";	
		}
		$rdelete = @mysql_query ($qdelete, $this->dbconnection);
		//echo '<p>The record has been deleted.</p>';	
	}
	
	public function OpenLogfile($in_logfilename){
		$this->logfile = $in_logfilename;
		if ($this->logging == 1){
			//fclose($this->logfile_fh);	
			$this->logfile_fh = fopen($this->logfile,'a');
		}
	}
	
	public function WriteLogfile($instring){
		//$instring = str_replace("\r"," ",$instring);
		if ($this->logging == 1){
			$writestr = date("D_Y-m-d_H-i-s") . "__$instring\r\n\r\n";
			fwrite($this->logfile_fh,$writestr);
		}
	}
	public function CloseLogfile(){
		if ($this->logging == 1){
			fclose($this->logfile_fh);
		}
	}
	
	public function DuplicateRecord(){
		//Create new record, copy everything from the current record into the new one.
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
		$rCopy = @mysql_query($qCopy,$this->dbconnection);
		$qMax = "SELECT MAX($this->keyId_name) FROM $this->tableName;";
		$rMax = @mysql_query($qMax,$this->dbconnection);
		$newId = @mysql_result($rMax,0,0);
		$this->Initialize($this->tableName,$newId,$this->keyId_name,$this->GetValue($this->fckeyname), $this->fckeyname);
	}
}

?>