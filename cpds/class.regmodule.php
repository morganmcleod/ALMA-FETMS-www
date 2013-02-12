<?php
class RegulatorModule extends GenericTable{
	public function Initialize_RegulatorModule($in_keyId,$in_dbconnection){
		$this->dbconnection = $in_dbconnection;
		parent::Initialize("CPDS_RegModules",$in_keyId,"keyId",$this->dbconnection);
	}
	public function NewRecord_RegulatorModule(){
		parent::NewRecord('CPDS_RegModules');
	}	
	public function DisplayData_RegulatorModule(){
		echo "<a href= 'regmodule_list.php'><b>Return to Regulator Module List</a></b><br><br>";
		parent::Display_data();
	}

}
?>