<?php
class GenericTable{
	var $propertyNames;
	var $propertyVals;
	var $tableName;
	var $keyId;
	var $keyId_name;
	var $dbconnection;
	
	
	public function GetValue($ValueName){
		return $this->propertyVals[array_search($ValueName,$this->propertyNames,true)];
	}
	public function SetValue($ValueName,$SetValue){
		$this->propertyVals[array_search($ValueName,$this->propertyNames,true)] = $SetValue;
	}
	
	public function Initialize($tableName, $in_keyId = "", $in_keyId_name = "keyId", $in_dbconnection){
		$this->dbconnection = $in_dbconnection;	
		$this->tableName = $tableName;
		$this->keyId_name = $in_keyId_name;
		$this->keyId = $in_keyId;
		$q = "show columns from $tableName;";
		$r = mysqli_query($link, $q);
		$counter=0;
			while ($res = mysqli_fetch_array($r)){
				$this->propertyNames[$counter] = $res[0];
				$counter++;
			}

		$qVals = "SELECT * FROM $tableName WHERE $in_keyId_name = $in_keyId;";
		$rVals = mysqli_query($link, $qVals);
		$this->propertyVals = mysqli_fetch_array($rVals);	
		
	}
	
	public function NewRecord($tableName){
		$this->tableName = $tableName;
		$qNew = "INSERT INTO $this->tableName() VALUES();";
		$rNew = mysqli_query($link, $qNew);
		$qNew = "SELECT MAX($this->keyId_name) FROM $this->tableName;";
		$rNew = mysqli_query($link, $qNew);
		$this->keyId = ADAPT_mysqli_result($rNew,0);
		$this->Initialize($tableName,$this->keyId,$this->keyId_name);
	}
	
	
	public function RequestValues(){
		foreach ($this->propertyNames as &$propertyName){
			if (isset($_REQUEST[$propertyName])){
			$this->SetValue($propertyName,$_REQUEST[$propertyName]);
			}
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
				$qu .= " $tempName='" . $this->propertyVals[array_search($tempName,$this->propertyNames,true)] . "',";
			}
		}
		//Remove the last comma
		$qu=substr($qu,0,strlen($qu)-1);
		$qu .= " WHERE $this->keyId_name = $this->keyId LIMIT 1;";	
		$ru = mysqli_query($link, $qu);
	}	
	
	public function Display_data(){
		echo "<br><font size='+2'><b>$this->tableName</b></font><br>";
		echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
			echo "<div style ='width:80%;height:30%'>";
			echo "<div align='right' style ='width:100%;height:30%'>";
			
			$NameIndex=0;
			foreach($this->propertyNames as $tempName){
				if ($tempName != $this->keyId_name){
					echo "<br>$tempName<input type='text' name='$tempName' size='100' 
					maxlength='200' value = '".$this->propertyVals[$NameIndex]."'>";
				}
				$NameIndex++;
			}

			echo "<input type='hidden' name='$this->keyId_name' value='$this->keyId'>";
			echo "<input type='hidden' name='tablename' value='$this->tableName'>";
			echo "<br><br><input type='submit' name = 'submitted' value='SAVE CHANGES'>";
			echo "<input type='submit' name = 'deleterecord' value='DELETE RECORD'>";
			echo "</div></div>";	
		echo "</form>";	
	}
	
	public function Display_delete_form(){
		echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">
		<b><font size="+1">Are you sure you want to delete this record?</b></font>
		<br><input type="submit" name = "deleterecord_forsure" value="YES, DELETE RECORD"><br><br>
		<input type="hidden" name="' . $this->keyId_name . '" value="' . $this->keyId . '" />
		</form>';
	}
	public function Delete_record(){
		$qdelete = "DELETE FROM $this->tableName WHERE $this->keyId_name =$this->keyId";		
		$rdelete = mysql_query ($qdelete, $this->dbconnection);
		echo '<p>The record has been deleted.</p>';	
	}
	
}

?>