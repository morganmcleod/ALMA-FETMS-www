<?php
class GenericTable{
	var $propertyNames;
	var $propertyVals;
	var $tableName;
	var $keyId;
	
	public function GetValue($ValueName){
		return $this->propertyVals[array_search($ValueName,$this->propertyNames,true)];
	}
	public function SetValue($ValueName,$SetValue){
		$this->propertyVals[array_search($ValueName,$this->propertyNames,true)] = $SetValue;
	}
	
	public function Initialize($tableName, $in_keyId = ""){
		include('mysql_connect.php');

		$this->tableName = $tableName;
		$this->keyId = $in_keyId;

		$q = "show columns from $tableName;";
		$r = @mysql_query($q,$dbc);
		$counter=0;
			while ($res = @mysql_fetch_array($r)){
				$this->propertyNames[$counter] = $res[0];
				$counter++;
			}
		$qVals = "SELECT * FROM $tableName WHERE keyId = $in_keyId;";
		$rVals = @mysql_query($qVals,$dbc);
		$this->propertyVals = @mysql_fetch_array($rVals);	
		@mysql_close($dbc);
	}
	
	public function NewRecord($tableName){
		$this->tableName = $tableName;
		include('mysql_connect.php');
		$qNew = "INSERT INTO $this->tableName() VALUES();";
		$rNew = @mysql_query($qNew,$dbc);
		$qNew = "SELECT keyId FROM $this->tableName ORDER BY keyId DESC LIMIT 1;";
		$rNew = @mysql_query($qNew,$dbc);
		$rowNew = @mysql_fetch_array($rNew);
		$this->keyId = $rowNew[0];
		$this->Initialize($tableName,$this->keyId);
		@mysql_close($dbc);
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
		include('mysql_connect.php');
		$qu = "UPDATE $this->tableName SET ";
		foreach($this->propertyNames as $tempName){
			if ($tempName != "keyId"){
				$qu .= " $tempName='" . $this->propertyVals[array_search($tempName,$this->propertyNames,true)] . "',";
			}
		}
		//Remove the last comma
		$qu=substr($qu,0,strlen($qu)-1);
		$qu .= " WHERE keyId = $this->keyId LIMIT 1;";	
		$ru = @mysql_query($qu,$dbc);
		@mysql_close($dbc);
	}	
	
	public function Display_data(){
		include('mysql_connect.php');
		echo "<br><font size='+2'><b>$this->tableName</b></font><br>";
		echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
			echo "<div style ='width:100%;height:30%'>";
			echo "<div align='right' style ='width:50%;height:30%'>";
			
			$NameIndex=0;
			foreach($this->propertyNames as $tempName){
				if ($tempName != "keyId"){
					echo "<br>$tempName<input type='text' name='$tempName' size='50' 
					maxlength='200' value = '".$this->propertyVals[$NameIndex]."'>";
				}
				$NameIndex++;
			}

			echo "<input type='hidden' name='keyId' value='$this->keyId'>";
			echo "<input type='hidden' name='tablename' value='$this->tableName'>";
			echo "<br><br><input type='submit' name = 'submitted' value='SAVE CHANGES'>";
			echo "<input type='submit' name = 'deleterecord' value='DELETE RECORD'>";
			echo "</div></div>";	
		echo "</form>";	
		@mysql_close($dbc);
	}
	
	public function Display_delete_form(){
		echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">
		<b><font size="+1">Are you sure you want to delete this record?</b></font>
		<br><input type="submit" name = "deleterecord_forsure" value="YES, DELETE RECORD"><br><br>
		<input type="hidden" name="keyId" value="' . $this->keyId . '" />
		</form>';
	}
	public function Delete_record(){
		include('mysql_connect.php');
		$qdelete = "DELETE FROM $this->tableName WHERE keyId=$this->keyId";		
		$rdelete = @mysql_query ($qdelete, $dbc);
		echo '<p>The record has been deleted.</p>';	
		@mysql_close($dbc);
		//echo '<meta http-equiv="Refresh" content="1;url=view_scansets_sort.php">';
	}
	
}
?>