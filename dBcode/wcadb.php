<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.dboperations.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.frontend.php');

class WCAdb { //extends DBRetrieval {
	var $dbconnection;

	/**
	 * Initializes class and creates database connection
	 * 
	 * @param $db- existing database connection
	 */
	public function WCAdb($db) {
		require(site_get_config_main());
		$this->dbconnection = $db;
	}

	/**
	 * @param string $query- SQL query to be passed to database
	 * 
	 * @return Resource ID- results from query
	 */
	public function run_query($query) {
		return @mysql_query($query, $this->dbconnection);
	}
	
	/**
	 * 
	 * @param string $action- indicates if query will select , delete, or insert values from the database 
	 * @param integer $keyId
	 * @param integer $fc (default = NULL)
	 * @param array $values- integers representing LO, Jitter, and pol. (default = NULL)
	 * 
	 * @return resource for query results in action is select.
	 */
	public function qpj($action, $keyId, $fc=NULL, $values=NULL) {
		$q = '';
		if($action == 'select') {
			$q = "SELECT LO, Jitter, Pol FROM WCA_PhaseJitter WHERE fkHeader = $keyId ORDER BY Pol ASC, LO ASC;";
			return $this->run_query($q);
		}
		if($action == 'delete') {
			$q = "DELETE FROM WCA_PhaseJitter WHERE fkHeader = $keyId AND fkFacility = $fc;";
			$this->run_query($q);
		}
		if($action == 'insert') {
			$q = "INSERT INTO WCA_PhaseJitter (fkHeader,LO,Jitter,pol,fkFacility) VALUES ($keyId, $values[0], $values[2], $values[1],$fc);";
			$this->run_query($q);
		}
	}
	
	/**
	 * 
	 * @param string $request- indicates which query is desired
	 * @param integer $keyId (default = NULL)
	 * @param integer $fc (default = NULL)
	 * @param integer $pol (default = NULL)
	 * @param integer $FreqLO (default = NULL)
	 * @param integer $FreqHI (default = NULL)
	 * @param array $keys- values representing old and new keyIds (default = NULL)
	 * @param array or string $values - values representing VGP or table (default = NULL)
	 */
	public function q_other($request, $keyId=NULL, $fc=NULL, $pol=NULL, $FreqLO=NULL, $FreqHI=NULL, $keys=NULL, $values=NULL) {
		if($request == 'WCA') {
			$q = "SELECT keyId FROM WCAs WHERE fkFE_Component = $keyId LIMIT 1;";
		} elseif($request == 'cfg') {
			$q = "SELECT FE_Config.keyFEConfig AS ConfigId, FE_ConfigLink.keyId AS ConfigLinkId, Front_Ends.keyFrontEnds AS FEId, Front_Ends.SN AS FESN from FE_Config, FE_ConfigLink, Front_Ends WHERE FE_ConfigLink.fkFE_Components = $keyId AND FE_ConfigLink.fkFE_Config = FE_Config.keyFEConfig AND FE_Config.fkFront_Ends = Front_Ends.keyFrontEnds AND FE_Config.keyFacility = $fc AND FE_Config.keyFacility = FE_ConfigLink.fkFE_ConfigFacility AND Front_Ends.keyFacility = $fc;";
		} elseif($request == 'sln') {
			$q = "SELECT MAX(keyId) FROM FE_StatusLocationAndNotes WHERE fkFEComponents = $keyId;";
		} elseif($request == 'status') {
			$q = "INSERT INTO FE_StatusLocationAndNotes (fkFEComponents, fkLocationNames,fkStatusType) VALUES($keyId,$fc,'7');";
		} elseif($request == 'n') {
			$q = "Insert into WCA_LOParams(fkComponent,FreqLO,VDP0,VDP1,VGP0,VGP1) VALUES ($keyId,$FreqLO, 0,0,'".$values[0]."','".$values[1]."');";
		} elseif($request == 'MSP') {
			$q = "SELECT * FROM WCA_MaxSafePower WHERE fkFE_Component = $keyId AND fkFacility = $fc;";
		} elseif($request == 'MS') {
			$q = "SELECT * FROM WCA_MaxSafePower WHERE fkFE_Component = ".$keys['old']." ORDER BY FreqLO ASC;";
			$rMS = $this->run_query($q);
			while ($rowMS = @mysql_fetch_array($rMS)){
				$qMSnew  = "INSERT INTO WCA_MaxSafePower(fkFacility,FreqLO,VD0_setting,VD1_setting,VD0,VD1,fkFE_Component) ";
				$qMSnew .= "VALUES('". $rowMS['fkFacility'] . "','" . $rowMS['FreqLO'] . "','" . $rowMS['VD0_setting'];
				$qMSnew .= "','". $rowMS['VD1_setting'] ."','". $rowMS['VD0'] ."','". $rowMS['VD1'] ."','".$keys['new']."')";
				$rMSnew = $this->run_query($qMSnew);
			}
			return;
		} elseif($request == 'YIG') {
			$q = "SELECT * FROM WCAs WHERE fkFE_Component = $keyId;";
		} elseif($request == 'FreqLO') {
			$q = "SELECT FreqLO, AMNoise FROM WCA_AMNoise WHERE fkHeader = $keyId AND FreqIf >= $FreqLO AND FreqIF <= $FreqHI AND Pol = $pol AND fkFacility = $fc ORDER BY FreqLO ASC;";
		} elseif($request == 'NumIF') {
			$q = "SELECT DISTINCT(FreqIF) FROM WCA_AMNoise WHERE fkHeader = $keyId AND Pol = $pol AND fkFacility = $fc;";
		} elseif($request == 'TS') {
			$q = "SELECT VD0, VD1 FROM WCA_OutputPower WHERE fkHeader = $keyId AND keyDataSet = 1 AND fkFacility = $fc LIMIT 1;";
		} elseif($request == 'OP') {
			$q = "SELECT FreqLO,Power FROM WCA_OutputPower WHERE Pol = $pol AND fkHeader = $keyId AND keyDataSet = 1 AND fkFacility = $fc ORDER BY FreqLO ASC;";
		} elseif($request == 'x') {
			$q = "SELECT MAX(FreqLO) FROM WCA_OutputPower WHERE fkHeader = $keyId AND fkFacility = $fc;";
		} elseif($request == 'Check') {
			$q = "SELECT * FROM $values WHERE fkFE_Component = $keyId AND fkFacility = $fc LIMIT 3;";
		} else {
			$q = '';
		}
		return $this->run_query($q);
	}	
	
	/**
	 * 
	 * @param integer $occur- occurance of query request
	 * @param integer $keyId (default = NULL)
	 * @param integer $pol (default = NULL)
	 * @param integer $fc (default = NULL)
	 * @param integer $FormatTDHListArr- tdhArray value from FormatTDHList (default = NULL)
	 * @param integer $CurrentLO (default = NULL)
	 * @param integer $LOfreq (default = NULL)
	 * 
	 */
	public function q($occur, $keyId=NULL, $pol=NULL, $fc=NULL, $FormatTDHListArr=NULL, $CurrentLO=NULL, $LOfreq=NULL) {
		if($occur == 1) {
			$q = "SELECT keyId FROM LOParams WHERE fkComponent = $keyId ORDER BY FreqLO ASC;";
		} elseif($occur == 2) {
			$q = "Select * from WCA_LOParams WHERE fkComponent = $keyId;";
		} elseif($occur == 3) {
			$q = "SELECT TS FROM WCA_LOParams WHERE fkComponent = $keyId ORDER BY FreqLO ASC LIMIT 1;";
		} elseif($occur == 4) {
			$q = "SELECT * FROM WCA_LOParams WHERE fkComponent = $keyId ORDER BY FreqLO ASC;";
		} elseif($occur == 5) {
			$q = "SELECT FreqLO, VD$pol as VD, Power FROM WCA_OutputPower WHERE fkHeader IN $FormatTDHListArr AND fkFacility = $fc AND (keyDataSet=2 or keyDataSet=3) and Pol=$pol ORDER BY FreqLO, VD ASC";
		} elseif($occur == 6) {
			$q = "SELECT MAX(VD0), MAX(VD1) FROM WCA_OutputPower WHERE fkHeader in $FormatTDHListArr AND fkFacility = $fc AND keyDataSet=2";
		} elseif($occur == 7) {
			$q = "DELETE FROM WCA_LOParams WHERE fkComponent = $keyId;";
		} elseif($occur == 8) {
			$q = "DELETE FROM WCAs WHERE fkFE_Component = $keyId;";
		} elseif($occur == 9) {
			$q = "SELECT Time,AllanVar FROM WCA_AmplitudeStability WHERE FreqLO = $CurrentLO AND Pol = $pol AND fkHeader = $keyId ORDER BY Time ASC;";
		} elseif($occur == 10) {
			$q = "SELECT FreqIF,FreqLO,AMNoise FROM WCA_AMNoise WHERE fkHeader = $keyId AND Pol = $pol AND fkFacility = $fc ORDER BY FreqLO ASC, FreqIF ASC;";
		} elseif($occur == 11) {
			$q = "SELECT CarrierOffset,Lf FROM WCA_PhaseNoise WHERE FreqLO = $CurrentLO AND Pol = $pol AND fkHeader = $keyId AND fkFacility = $fc ORDER BY CarrierOffset ASC;";
		} elseif($occur == 12) {
			$q = "SELECT CarrierOffset,FreqLO,Lf FROM WCA_PhaseNoise WHERE fkHeader = $keyId AND FreqLO = $LOfreq AND Pol = $pol AND fkFacility = $fc ORDER BY CarrierOffset ASC;";
		} elseif($occur == 13) {
			$q = "SELECT VD$pol,Power FROM WCA_OutputPower WHERE Pol = $pol AND fkHeader = $keyId AND keyDataSet = 2 AND FreqLO = $CurrentLO AND fkFacility = $fc ORDER BY VD$pol ASC;";
		} elseif($occur == 14) {
			$q = "SELECT VD$pol,Power FROM WCA_OutputPower WHERE Pol = $pol AND fkHeader = $keyId AND keyDataSet <> 1 AND FreqLO = $CurrentLO AND fkFacility = $fc ORDER BY VD$pol ASC;";
		} elseif($occur == 15) {
			$q = "SELECT VD$pol,Power FROM WCA_OutputPower WHERE Pol = $pol AND fkHeader = $keyId AND keyDataSet = 3 AND FreqLO = $CurrentLO AND fkFacility = $fc ORDER BY Power ASC, VD$pol ASC;";
		} else {
			$q = '';
		}
		return $this->run_query($q);
	}
	
	/**
	 * 
	 * @param string $test_type- TestData_Type desired
	 * @param integer $keyId
	 * @param integer $fc
	 * @param boolean $get_pol (default = NULL)
	 * @param integer $FreqLOW (default = NULL)
	 * @param integer $FreqHI (default = NULL)
	 * @param integer $pol (default = NULL)
	 * @return resource
	 */
	public function qlo($test_type, $keyId, $fc, $get_pol=TRUE, $FreqLOW=NULL, $FreqHI=NULL, $pol=NULL) {
		$q = "SELECT DISTINCT(FreqLO)";
		if($get_pol) {
			$q .= ", Pol";
		}
		$q .= " FROM $test_type WHERE fkHeader = $keyId";
		if(!is_null($FreqLOW)) {
			$q .= " AND FreqIf >= $FreqLOW";
		}
		if(!is_null($FreqHI)) {
			$q .= " AND FreqIF <= $FreqHI";
		}
		if(!is_null($pol)) {
			$q .= " AND Pol = $pol";
		}
		$q .= " AND fkFacility = $fc ORDER BY FreqLO ASC;";
		return $this->run_query($q);
	}
	
	/**
	 * 
	 * @param string $test_type- TestData_Type desired
	 * @param integer $keyId
	 * @param integer $fc (default = NULL)
	 * @param integer $pol (default = NULL)
	 * @param integer $kDSet_comp- keyDataSet (default = NULL)
	 * @return resource
	 */
	public function qFindLO($test_type, $keyId, $fc=NULL, $pol=NULL, $kDSet_comp=NULL) {
		$q = "SELECT DISTINCT(FreqLO) FROM $test_type WHERE fkHeader = $keyId";
		if(!is_null($kDSet_comp)) {
			$q .= " AND keyDataSet $kDSet_comp";
		}
		if(!is_null($pol)){
			$q .= " AND Pol = $pol";
		}
		if(!is_null($fc)) {
			$q .= " AND fkFacility = $fc";
		}
		$q .= " ORDER BY FreqLO ASC;";
		return $this->run_query($q);
	}

	/**
	 * 
	 * @param integer $keyId
	 * @param string $test_type- TestData_Type desired
	 * @param boolean $comp- fkFE_Components? (default = NULL)
	 */
	public function qDel($keyId, $test_type, $comp = FALSE) {
		$q = "DELETE FROM $test_type WHERE ";
		if($comp) {
			if($test_type == 'FE_ConfigLink') {
				$q .= "fkFE_Components";
			} else {
				$q .= "fkFE_Component";
			}
		} else {
			$q .= "fkHeader";
		}
		$q .= " = $keyId;";
		$this->run_query($q);
	}
	
	/**
	 * 
	 * @param string $request- Table desired
	 * @param array $filecontents
	 * @param TestData_header $object- tdh desired (ex. tdh_ampstab)
	 * @param integer $fc (default = NULL)
	 * @param TestData_header $other_object- if additional tdh desired (default = NULL)
	 */
	public function del_ins($request, $filecontents, $object, $fc=NULL, $other_object=NULL) {
		$qdel = "DELETE FROM $request WHERE fkHeader = $object->keyId";
		if($request == "WCA_MaxSafePower") {
			$qdel = "DELETE FROM $request WHERE fkFE_Component = $object->keyId";
		}
		if(!is_null($fc)) {
			$qdel .= " AND fkFacility = $fc";
		}
		$qdel .= ";";
		$rdel = $this->run_query($qdel);
		$once = 0;
		for($i=0; $i<sizeof($filecontents); $i++) {
			$line_data = trim($filecontents[$i]);
			$tempArray = explode(",", $line_data);
			if (is_numeric(substr($tempArray[0],0,1)) == true){
				if($once == 0) {
					$object->SetValue('TS', $tempArray[3]);
					$object->Update();
					if(!is_null($other_object)){
						$other_object->SetValue('TS', $tempArray[3]);
						$other_object->Update();
					}
					$once = 1;
				}
				$ins_arr = array();
				if($request == 'WCA_MaxSafePower') {
					$ins_arr['TS'] = $tempArray[3];
					$ins_arr['FreqLO'] = $tempArray[4];
					$ins_arr['VD0_setting'] = $tempArray[5];
					$ins_arr['VD1_setting'] = $tempArray[6];
					$ins_arr['VD0'] = $tempArray[7];
					$ins_arr['VD1'] = $tempArray[8];
					$ins_arr['fkFE_Component'] = $object->keyId;
					$ins_arr['fkFacility'] = $fc;
				}	
				if($request == 'WCA_OutputPower') {
					$PolTemp = $tempArray[6];
					if(strtolower($tempArray[6]) == "a") {
						$PolTemp = "0";
					}
					if(strtolower($tempArray[6] == "b")) {
						$PolTemp = "1";
					}
					$ins_arr['fkHeader'] = $object->keyId;
					$ins_arr['keyDataSet'] = $tempArray[1];
					$ins_arr['FreqLO'] = $tempArray[2];
					$ins_arr['Power'] = $tempArray[3];
					$ins_arr['Pol'] = $PolTemp;
					$ins_arr['VD0'] = $tempArray[7];
					$ins_arr['VD1'] = $tempArray[8];
					$ins_arr['VG0'] = $tempArray[9];
					$ins_arr['VG1'] = $tempArray[10];
					$ins_arr['fkFacility'] = $fc;
				}
				if($request == 'WCA_AmplitudeStability') {
					$ins_arr['fkHeader'] = $object->keyId;
					$ins_arr['FreqLO'] = $tempArray[4];
					$ins_arr['Pol'] = $tempArray[5];
					$ins_arr['Time'] = $tempArray[6];
					$ins_arr['AllanVar'] = $tempArray[7];
				}
				if($request == 'WCA_AMNoise') {
					$ins_arr['fkHeader'] = $object->keyId;
					$ins_arr['AMNoise'] = $tempArray[4];
					$ins_arr['FreqLO'] = $tempArray[5];
					$ins_arr['FreqIF'] = $tempArray[6];
					$ins_arr['Pol'] = $tempArray[7];
					$ins_arr['DrainVoltage'] = $tempArray[8];
					$ins_arr['GateVoltage'] = $tempArray[9];
				}
				if($request == 'WCA_PhaseNoise') {
					$ins_arr['fkHeader'] = $object->keyId;
					$ins_arr['FreqLO'] = $tempArray[4];
					$ins_arr['Pol'] = $tempArray[5];
					$ins_arr['CarrierOffset'] = $tempArray[6];
					$ins_arr['Lf'] = $tempArray[7];
				}
				$qins = "INSERT INTO $request (";
				$inskeys = array_keys($ins_arr);
				$insvals = array_values($ins_arr);
				$keys = "";
				$vals = "";
				for ($i=0; $i<count($inskeys); $i++) {
					$keys .= "$inskeys,";
					$vals .= "'$insvals',";
				}
				$keys = substr($keys, 0, -1);
				$vals = substr($vals, 0, -1);
				$qins .= "$keys) VALUES ($vals);";
				$this->run_query($qins);
			}
		}
	}
	
	/**
	 * 
	 * @param string $action- select or delete
	 * @param integer $keyId
	 * @param string $test_type- TestData_Type desired
	 * @param integer $fc (default = NULL)
	 * @return resource
	 */
	public function qtdh($action, $keyId, $test_type, $fc=NULL) {
		if($action == 'select') {
			$q = "SELECT keyId FROM TestData_header WHERE fkFE_Components = $keyId and fkTestData_Type = ";
		} elseif($action == 'delete') {
			$q = "DELETE FROM TestData_header WHERE fkFE_Components = $keyId AND fkTestData_Type = ";
		} else {
			$q = "";
		}

		if ($test_type == 'WCA_PhaseJitter') {
			$q .= "47";
		} elseif($test_type == 'WCA_AmplitudeStability') {
			$q .= "45";
		} elseif($test_type == 'WCA_OutputPower') {
			$q .= "46";
		} elseif($test_type == 'WCA_PhaseNoise') {
			$q .= "48";
		} elseif($test_type == 'WCA_AMNoise') {
			$q .= "44";
		} else {
			$q .= "0";
		}
		if (!is_null($fc)) {
			$q .= " AND keyFacility = $fc";
		}
		$q .= ";";
		
		return $this->run_query($q);		
	}
	
}
?>