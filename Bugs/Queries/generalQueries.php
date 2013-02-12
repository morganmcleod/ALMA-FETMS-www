<?php

Class generalQueries{
	
	function GetOneWithCriteria($getwhat,$tablename,$where,$value)
	{
		//called from CaddNewBug.php
		$oneval=mysql_query("SELECT $getwhat FROM $tablename WHERE $where=$value")
		or die("Could not get value" .mysql_error());
		
		$return_value=mysql_result($oneval,0,$getwhat);
		
		return $return_value;
	}
}

?>