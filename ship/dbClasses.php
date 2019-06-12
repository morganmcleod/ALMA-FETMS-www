<?php
//Classes to hold functions that perform database queries.
Include "mysql_connect.php";
class generalQueries
{
	var $field;
	var $tablename;
	var $criteria;
	var $value;
	function getDatafordropdown($tname)
	{
		$this->tablename=$tname;
		$dropdownData=mysqli_query($link, "SELECT DISTINCT SN FROM $this->tablename ORDER BY CAST(SN AS UNSIGNED) DESC")
		or die("Could not execute MySql query" .mysql_error());
		return $dropdownData;
	}
	function getLocationOrStatus($getwhat,$tablename,$tabletype)
	{
		$this->field=$getwhat;
		$this->tablename=$tablename;
		$statusOrlocation=mysqli_query($link, "SELECT $this->field FROM $this->tablename where fkTableType=$tabletype")
		or die("Could not get location or status" .mysql_error());
		return $statusOrlocation;
	}
	function getKeyValues($getwhatkey,$table,$criteria1,$value1)
	{
		$this->field=$getwhatkey;
		$this->tablename=$table;
		$this->criteria=$criteria1;
		$this->value=$value1;
		$keys=mysqli_query($link, "SELECT $this->field FROM $this->tablename WHERE $this->criteria='$this->value'");
		if(mysqli_num_rows($keys) >= 1)
		{   
		$getKeyValues=ADAPT_mysqli_result($keys,0,$this->field);
		return $getKeyValues;
		}
		else
		{
			return false;
		}
	}
	function getMultiKeyValues($kwhat,$tname,$criteria2,$value2)
	{
		$this->field=$kwhat;
		$this->tablename=$tname;
		$this->criteria=$criteria2;
		$this->value=$value2;
		$key1=mysqli_query($link, "SELECT $this->field FROM $this->tablename WHERE $this->criteria='$this->value'");
		if(mysqli_num_rows($key1) >= 1)
		{        
			$rows = ADAPT_mysqli_result($key1,0,$this->field);       
			return $rows; 
	    }   
	    else
	    { 
	    	return false;    
	    }	
	}
	function getMaxkey($what,$where,$criteria3,$value3,$ordrby)
	{
		$this->field=$what;
		$this->tablename=$where;
		$this->criteria=$criteria3;
		$this->value=$value3;
		$maxkey=mysqli_query($link, "SELECT $this->field from $this->tablename where $this->criteria='$this->value' ORDER BY $ordrby DESC")
		or die("Could not get Max Key" .mysql_error());
		return $maxkey;
	}
	function getEverything($tablename1,$ordrby1)
	{
		$this->tablename=$tablename1;
		$everything=mysqli_query($link, "SELECT * FROM $this->tablename ORDER BY $ordrby1 DESC")
		or die("Could not select from $this->tablename".mysqli_query($link, ));
		return $everything;
	}
	function getMultiCriteria($whata,$whatb,$tablename2,$criteriaa,$valuea,$criteriab,$valueb)
	{
		$multicriteria=mysqli_query($link, "SELECT $whata,$whatb FROM $tablename2 WHERE $criteriaa='$valuea' OR $criteriab='$valueb'")
		or die("Could not select from $tablename2");
		return $multicriteria;
	}
}
class getQueries
{
	var $selectedSN;
	var $key;
	//functions to execute queries.
	function getDataforPreamphome($dropdown,$maxkey)
	{
		$this->selectedSN=$dropdown;
		$this->key=$maxkey;
		$preamphomeData=mysqli_query($link, "SELECT Preamps.*,PreampPairs.fkPreamp0,PreampPairs.fkPreamp1,
							StatusLocationAndNotes.Notes,MxrPreampAssys.SN,MxrPreampAssys.SN_Hybrid
							FROM Preamps 
							LEFT JOIN PreampPairs
							ON Preamps.keyPreamps=PreampPairs.fkPreamp0 OR
							Preamps.keyPreamps=PreampPairs.fkPreamp1
							LEFT JOIN StatusLocationAndNotes
							ON Preamps.keyPreamps=StatusLocationAndNotes.fkTableKey
							LEFT JOIN MxrPreampAssys 
							ON PreampPairs.keyPreampPairs=MxrPreampAssys.fkPreampPair
							WHERE Preamps.SN='$this->selectedSN' AND Preamps.keyPreamps='$this->key' LIMIT 1") 
		or die("Could not execute Query".mysql_error());
		return $preamphomeData;
	}
	function getStatusLocationAndNotesData($keyval,$TableType)
	{
		$this->key=$keyval;
		$statuslocationAndnotesData=mysqli_query($link, "SELECT StatusLocationAndNotes.fkTableKey,StatusLocationAndNotes.TS,StatusLocationAndNotes.Updated_By,
									StatusLocationAndNotes.Lnk_Data,StatusLocationAndNotes.Notes,
									LocationNames.Name,StatusTypes.Type FROM StatusLocationAndNotes
									LEFT JOIN LocationNames 
									ON StatusLocationAndNotes.fkLocationNames=LocationNames.keyLocationNames
									LEFT JOIN StatusTypes
									ON StatusLocationAndNotes.fkStatusType=StatusTypes.keyStatusType
									WHERE StatusLocationAndNotes.fkTableKey='$this->key' AND StatusLocationAndNotes.fkTableType='$TableType'
									ORDER BY TS DESC")
		or die("Sorry".mysql_error());
		return $statuslocationAndnotesData;
	}
	function getStatusLocationAndNotesDataL($keyvalue,$TableType)
	{
		$this->key=$keyvalue;
		$statuslocationAndnotesDataL=mysqli_query($link, "SELECT StatusLocationAndNotes.fkTableKey,StatusLocationAndNotes.TS,
							StatusLocationAndNotes.Lnk_Data,
							LocationNames.Name,StatusTypes.Type FROM StatusLocationAndNotes
							LEFT JOIN LocationNames 
							ON StatusLocationAndNotes.fkLocationNames=LocationNames.keyLocationNames
							LEFT JOIN StatusTypes
							ON StatusLocationAndNotes.fkStatusType=StatusTypes.keyStatusType
							WHERE StatusLocationAndNotes.fkTableKey='$this->key' AND StatusLocationAndNotes.fkTableType='$TableType'
							ORDER BY TS DESC LIMIT 1")
		or die("Sorry".mysql_error());
		return $statuslocationAndnotesDataL;
	}
	function getDataforShowPreamps($PreampKey)
	{
		$this->key=$PreampKey;
		$ShowPreampsData=mysqli_query($link, "SELECT Preamps.*,PreampPairs.fkPreamp0,PreampPairs.fkPreamp1,
							StatusLocationAndNotes.Notes
							FROM Preamps 
							LEFT JOIN PreampPairs
							ON Preamps.keyPreamps=PreampPairs.fkPreamp0 OR
							Preamps.keyPreamps=PreampPairs.fkPreamp1
							LEFT JOIN StatusLocationAndNotes
							ON Preamps.keyPreamps=StatusLocationAndNotes.fkTableKey
							WHERE Preamps.keyPreamps='$this->key' LIMIT 1") 
		or die("Could not execute Query".mysql_error());
		return $ShowPreampsData;
	}
	function getNotes($SLandNkey,$TableType)
	{	
		$this->key=$SLandNkey;
		$getNotes=mysqli_query($link, "SELECT Notes FROM `StatusLocationAndNotes` WHERE fkTableKey = '$this->key' 
					AND fkTableType='$TableType'
					AND TS =(SELECT max( TS ) FROM `StatusLocationAndNotes` WHERE fkTableKey = '$this->key')")
		or die("Could not get Notes" .mysqli_query($link, ));
		if(mysqli_num_rows($getNotes) >= 1)
		{        
			$rows = ADAPT_mysqli_result($getNotes,0,Notes);       
			return $rows; 
	    }   
	    else
	    { 
	    	return false;    
	    }	
	}
	function getfromPreamps($keyval)
	{
		$this->key=$keyval;
		$getdata=mysqli_query($link, "SELECT lnk_WSP,lnk_CSP,GelPack1,Cell1,Epoxy1_A,
							  Epoxy1_B,GelPack2,Cell2,Epoxy2_A,Epoxy2_B,GelPack3,Cell3,Epoxy3_A,Epoxy3_B 
							  FROM Preamps WHERE keyPreamps='$this->key'")
		or die("Could not extract data from Preamps" .mysql_error());
		return $getdata;
	}
	function getDataforMxrHome($drop,$mkey)
	{
		$this->selectedSN=$drop;
		$this->key=$mkey;
		$mxrhomeData=mysqli_query($link, "SELECT MxrPreampAssys.*
							FROM MxrPreampAssys
							WHERE MxrPreampAssys.SN='$this->selectedSN' AND MxrPreampAssys.keyMxrPreampAssys='$this->key'
							LIMIT 1")
		or die("Could not get data from table MxrPreampAssys".mysql_error());
		return $mxrhomeData;
	}
}
class insertQueries
{
	function insertForAddNew($SN,$TS,$TS_Removed,$Machined_By,$Potted)
						{
							$insertAddNew=mysqli_query($link, "INSERT into Preamps(SN,TS,TS_Removed,Machined_By,Potted) 
							Values('$SN','$TS','$TS_Removed','$Machined_By','$Potted')");
							return $insertAddNew;
						}
	function insertForNewConfig($ser,$ts,$by,$lnk_WSP,$ts_warm,$by,$lnk_CSP,$ts_cold,$by,$Gel_Pack1,$Cell1,$Epoxy1A,
						$Epoxy1B,$Gel_Pack2,$Cell2,$Epoxy2A,$Epoxy2B,$Gel_Pack3,$Cell3,$Epoxy3A,
						$Epoxy3B)
						{
							$insertforNewConfig=mysqli_query($link, "INSERT into Preamps(SN,TS,WarmData_By,lnk_WSP,
							TS_WarmData,ColdData_By,lnk_CSP,TS_ColdData,Machined_By,GelPack1,Cell1,
							Epoxy1_A,Epoxy1_B,GelPack2,Cell2,Epoxy2_A,Epoxy2_B,GelPack3,Cell3,Epoxy3_A,Epoxy3_B)
						    Values('$ser','$ts','$by','$lnk_WSP','$ts_warm','$by','$lnk_CSP','$ts_cold','$by',
						    '$Gel_Pack1','$Cell1','$Epoxy1A','$Epoxy1B','$Gel_Pack2','$Cell2','$Epoxy2A','$Epoxy2B',
						    '$Gel_Pack3','$Cell3','$Epoxy3A','$Epoxy3B')");
						    return $insertforNewConfig;										
						}
	function insertIntoStatusLocationAndNotes($TableType,$key,$Lname,$Stype,$TS,$notes2,$lnk_misc,$Machined_By)
	{
		$insertAddNew2=mysqli_query($link, "INSERT into StatusLocationAndNotes(fkTableType,fkTableKey,fkLocationNames,
									fkStatusType,TS,Notes,lnk_Data,Updated_By) 
					Values('$TableType','$key','$Lname','$Stype','$TS','$notes2','$lnk_misc','$Machined_By')");
		return $insertAddNew2;
	}
	function insertPreampPairs($fkey1,$fkey2,$ts,$ts_removed,$notes2,$imagerej1,$imagerej2,$lnk_misc)
	{
		mysqli_query($link, "INSERT into PreampPairs(fkPreamp0,fkPreamp1,TS,TS_Removed,Notes,6to10IR,4to6and10to12IR,lnk_Data)
					 VALUES('$fkey1','$fkey2','$ts','$ts_removed','$notes2','$imagerej1','$imagerej2','$lnk_misc')");
	}
	function insertMixerPreamps($SN,$TS,$Machined_By,$Cleaned_By,$lnk_file,$lnk_iv,$lnk_tp,$lnk_hist)
	{
		$insertMxrPreamps=mysqli_query($link, "INSERT into MxrPreampAssys(SN,TS,Machined_by,Cleaned_Plated_by,lnk_Graphs,lnk_IV_Data,lnk_TP,lnk_Histogram)
									 Values('$SN','$TS','$Machined_By','$Cleaned_By','$lnk_file','$lnk_iv','$lnk_tp','$lnk_hist')");
									 return $insertMxrPreamps;	
	}
}
class updateQueries
{
	var $Pkey;
	function updateWarmEmpty($ts,$by,$lnk_CSP,$ts_cold,$key1)
	{
		$this->Pkey=$key1;
		mysqli_query($link, "Update Preamps set TS='$ts',TS_Removed='$ts',ColdData_By='$by',lnk_CSP='$lnk_CSP', 
		TS_ColdData='$ts_cold',Machined_By='$by' where keyPreamps='$this->Pkey'")
		or die("Could not update Preamps table" .mysql_error());
	}
	function updateColdEmpty($ts,$by,$lnk_WSP,$ts_warm,$key2)
	{
		$this->Pkey=$key2;
		mysqli_query($link, "Update Preamps set TS='$ts', TS_Removed='$ts',WarmData_By='$by', lnk_WSP='$lnk_WSP',
							TS_WarmData='$ts_warm',Machined_By='$by' where keyPreamps='$this->Pkey'")
		or die("Could not update Preamps table" .mysql_error());
	}
	function UpdateBothEmpty($ts,$by,$key3)
	{
		$this->Pkey=$key3;
		mysqli_query($link, "Update Preamps set TS='$ts', TS_Removed='$ts',Machined_By='$by' where keyPreamps='$this->Pkey'")
		or die("Could not update Preamps table" .mysql_error());	
	}
	function getInsert($getwhat1,$key4,$tabletype)
	{
		$this->Pkey=$key4;
		$get=mysqli_query($link, "Select $getwhat1 from Preamps where keyPreamps='$key4'");
		$lnk=ADAPT_mysqli_result($get,0,$getwhat1);
		$insert=mysqli_query($link, "Insert into StatusLocationAndNotes(fkTableType,fkTableKey,TS,Notes,lnk_Data)
							 VALUES('$tabletype','$this->Pkey',Now() - Interval 1 Minute,'Warm/cold data files backup','$lnk')");
		return $insert;
	}
	function UpdateNonempty($ts,$by,$lnk_WD,$ts_warm,$lnk_CD,$ts_cold,$key5)
	{
		$this->Pkey=$key5;
		mysqli_query($link, "Update Preamps set TS='$ts',TS_Removed='$ts',WarmData_By='$by',lnk_WSP='$lnk_WD',
							TS_WarmData='$ts_warm',ColdData_By='$by',lnk_CSP='$lnk_CD',TS_ColdData='$ts_cold', 
							Machined_By='$by' where keyPreamps='$this->Pkey'")
		or die("Could not update Preamps table!" .mysql_error());
	}
	function UpdateDevdata($Gel_Pack1,$Cell1,$Epoxy1A,$Epoxy1B,$Gel_Pack2,$Cell2,$Epoxy2A,$Epoxy2B,$Gel_Pack3,
						   $Cell3,$Epoxy3A,$Epoxy3B,$key6)
						   {
						   		$this->Pkey=$key6;
						   		mysqli_query($link, "Update Preamps set GelPack1='$Gel_Pack1',Cell1='$Cell1',
								Epoxy1_A='$Epoxy1A',Epoxy1_B='$Epoxy1B',GelPack2='$Gel_Pack2',Cell2='$Cell2',
								Epoxy2_A='$Epoxy2A',Epoxy2_B='$Epoxy2B',GelPack3='$Gel_Pack3',Cell3='$Cell3',
								Epoxy3_A='$Epoxy3A',Epoxy3_B='$Epoxy3B' where keyPreamps='$this->Pkey'")
								or die("Could not insert device data into table Preamps" .mysql_error());
						   }
}
?>
