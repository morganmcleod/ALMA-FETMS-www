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
			$q = "SELECT * FROM WCA_MaxSafePower WHERE fkFE_Component = $keyId AND fkFacility = $fc ORDER By FreqLO ASC;";
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
		switch ($request) {
		    case 'WCA_MaxSafePower':
		        $colNames = array('TS', 'FreqLO', 'VD0_setting', 'VD1_setting', 'VD0', 'VD1', 'fkFE_Component', 'fkFacility');
		        break;
		    case 'WCA_OutputPower':
		        $colNames = array('fkHeader', 'keyDataSet', 'TS', 'FreqLO', 'Power', 'Pol', 'VD0', 'VD1', 'VG0', 'VG1', 'fkFacility');
		        break;
		    case 'WCA_AmplitudeStability':
		        $colNames = array('fkHeader', 'FreqLO', 'Pol', 'Time', 'AllanVar');
		        break;
			case 'WCA_AMNoise':
			    $colNames = array('fkHeader', 'AMNoise', 'FreqLO', 'FreqIF', 'Pol', 'DrainVoltage', 'GateVoltage');
			    break;
			case 'WCA_PhaseNoise':
			    $colNames = array('fkHeader', 'FreqLO', 'Pol', 'CarrierOffset', 'Lf');
		        break;
			default:
			    return;
		}

		$qdel = "DELETE FROM $request WHERE fkHeader = $object->keyId";
		if($request == "WCA_MaxSafePower") {
		    $qdel = "DELETE FROM $request WHERE fkFE_Component = $object->keyId";
		}
		if(!is_null($fc)) {
		    $qdel .= " AND fkFacility = $fc";
		}
		$qdel .= ";";
		$rdel = $this->run_query($qdel);

		$qins = "INSERT INTO $request (";
		$first = true;
		foreach ($colNames as $col) {
		    if (!$first)
		        $qins .= ",";
		    else
		        $first = false;
		    $qins .= $col;
		}
		$qins .= ") VALUES ";

		$first = true;
		foreach ($filecontents as $line) {
		    $line_data = trim($line);
		    $tempArray = explode(",", $line_data);
		    // skip header line:
		    if (is_numeric(substr($tempArray[0],0,1))) {
		        if (!$first)
		            // prepend a comma if not the first set of values:
		            $qins .= ",";
		        else {
		            // update the TDH time stamp with the data from the first row:
		            $object->SetValue('TS', $tempArray[3]);
		            $object->Update();
		            // TODO:  what is other_object used for?
		            if(!is_null($other_object)){
		                $other_object->SetValue('TS', $tempArray[3]);
		                $other_object->Update();
		            }
		            $first = false;
		        }
		        switch ($request) {
		            case 'WCA_MaxSafePower':
		                $values = array("'" . $tempArray[3] . "'",   //TS
		                                $tempArray[4],   //FreqLO
		                                $tempArray[5],   //VD0_setting
		                                $tempArray[6],   //VD1_setting
		                                $tempArray[7],   //VD0
		                                $tempArray[8],   //VD1
		                                $object->keyId,  //fkFE_Component
		                                $fc              //fkFacility
		                );
		                break;
		            case 'WCA_OutputPower':
		                $PolTemp = $tempArray[6];
		                if(strtolower($tempArray[6]) == "a") {
		                    $PolTemp = "0";
		                }
		                if(strtolower($tempArray[6] == "b")) {
		                    $PolTemp = "1";
		                }
		                $values = array($object->keyId, //fkHeader
		                                $tempArray[1], //keyDataSet
		                                "'" . $tempArray[3] . "'",   //TS
		                                $tempArray[4], //FreqLO
		                                $tempArray[5], //Power
		                                $PolTemp,      //Pol
		                                $tempArray[7], //VD0
		                                $tempArray[8], //VD1
		                                $tempArray[9], //VG0
		                                $tempArray[10],//VG1
		                                $fc            //fkFacility
		                );
		                break;
		            case 'WCA_AmplitudeStability':
		                $values = array($object->keyId, //fkHeader
		                                $tempArray[4], //FreqLO
		                                $tempArray[5], //Pol
		                                $tempArray[6], //Time
		                                $tempArray[7]  //AllanVar
		                );
		                break;
		            case 'WCA_AMNoise':
		                $values = array($object->keyId, //fkHeader
		                                $tempArray[4], //AMNoise
		                                $tempArray[5], //FreqLO
		                                $tempArray[6], //FreqIF
		                                $tempArray[7], //Pol
		                                $tempArray[8], //DrainVoltage
		                                $tempArray[9]  //GateVoltage


		                );
		                break;
		            case 'WCA_PhaseNoise':
		                $values = array($object->keyId, //fkHeader
		                                $tempArray[4], //FreqLO
		                                $tempArray[5], //Pol
		                                $tempArray[6], //CarrierOffset
		                                $tempArray[7]  //Lf
		                );
		                break;
		        }
		        $qins .= "(";
		        $first = true;
		        foreach ($values as $col) {
		            if (!$first)
		                $qins .= ",";
		            else
		                $first = false;
		            $qins .= $col;
		        }
		        $qins .= ")";
		    }
		}
		$qins .= ";";
		$ret = $this->run_query($qins);
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