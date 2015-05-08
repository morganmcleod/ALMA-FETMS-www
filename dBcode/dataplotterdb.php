<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_classes . '/class.cca.php');
require_once($site_classes . '/class.testdata_component.php');
require_once($site_classes . '/class.logger.php');
require_once($site_dbConnect);

class DPdb{ //extends DBRetrieval{
	var $dbConnection;

	/**
	 * Initializes class and creates database connection
	 *
	 * @param $dB- existing database connection
	 */
	public function DPdb($dB) {
		require(site_get_config_main());
		$this->dbConnection = $dB;
	}

	/**
	 *
	 * @param string $query- SQL query
	 *
	 * @return resource Id for SQL query
	 */
	public function run_query($query) {
		return @mysql_query($query, $this->dbConnection);
	}


	/**
	 *
	 * @param string $temp- w or c
	 * @param $FEcfg
	 * @param $TestDataHeader
	 * @param integer $fc
	 * @return array- results from query
	 */
	public function qca($temp, $FEcfg, $TestDataHeader, $fc) {
		$type = 0;
		if ($temp == 'c') {
			$type = 20;
		}
		if ($temp == 'w') {
			$type = 11;
		}

		$q = "SELECT FE_Components.SN FROM FE_Components, FE_ConfigLink, FE_Config
        WHERE FE_ConfigLink.fkFE_Config = $FEcfg
        AND FE_Components.fkFE_ComponentType = $type
        AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
        AND FE_Components.Band = " . $TestDataHeader->GetValue('Band') . "
        AND FE_Components.keyFacility = $fc
        AND FE_ConfigLink.fkFE_ConfigFacility = FE_Config.keyFacility
        ORDER BY Band ASC;";

		$r = $this->run_query($q);
		return @mysql_result($r, 0, 0);
	}

	/**
	 *
	 * @param string $request
	 * @param $t- table (default = NULL)
	 * @param $TestData_Id (default = NULL)
	 * @param $TestDataHeader (default = NULL)
	 * @param string $image_url (default = NULL)
	 * @param string $td_header (default = NULL)
	 */
	public function q_other($request, $t=NULL, $TestData_Id=NULL, $TestDataHeader=NULL, $image_url=NULL, $td_header=NULL) {
		if ($request == 'sh') {
			$q = "SELECT keyTEST_Workmanship_Phase_SubHeader
			FROM TEST_Workmanship_Phase_SubHeader
			WHERE fkHeader = $TestData_Id;";
			$r = $this->run_query($q);
			return @mysql_result($r, 0, 0);
		} elseif ($request == 'sub') {
			$q = "SELECT MAX(keyId) FROM TEST_LOLockTest_SubHeader
					WHERE fkHeader = ".$TestDataHeader->keyId."
					AND TEST_LOLockTest_SubHeader.keyFacility = " . $TestDataHeader->getValue('keyFacility') . ";";
			$r = $this->run_query($q);
			$t->WriteLogFile($q);
			return @mysql_result($r, 0, 0);
		} elseif ($request == 'URL') {
			$q = "UPDATE TestData_header SET PlotURL = '$image_url' WHERE keyId = $td_header;";
			$r = $this->run_query($q);
			$t->WriteLogFile($q);
			return;
		} else {
			return;
		}
	}

	/**
	 *
	 * @param integer $occur- occurance that function is called
	 * @param  $td_header
	 * @param $fc (default = NULL)
	 * @param $t- table (default = NULL)
	 * @param $TestDataHeader (default = NULL)
	 * @return resource
	 */
	public function qdata($occur, $td_header, $fc = NULL, $t=NULL, $TestDataHeader=NULL) {
		if ($occur == 1) {
			$q = "SELECT angle,amp_pol0,phase_pol0,amp_pol1,phase_pol1
			FROM TEST_PolAngles
			WHERE fkHeader = $td_header
			AND fkFacility = $fc ORDER BY angle ASC;";
		} elseif($occurr == 2) {
			if($TestDataHeader->GetValue('DataSetGroup') == 0) {
				$q = "SELECT TEST_LOLockTest.LOFreq,
				TEST_LOLockTest.PhotomixerCurrent, TEST_LOLockTest.PLLRefTotalPower
				FROM TEST_LOLockTest, TEST_LOLockTest_SubHeader, TestData_header
				WHERE TEST_LOLockTest.fkHeader = TEST_LOLockTest_SubHeader.keyId
				AND TEST_LOLockTest_SubHeader.fkHeader = TestData_header.keyId
				AND TestData_header.keyId = $td_header
				AND TEST_LOLockTest.IsIncluded = 1
				GROUP BY TEST_LOLockTest.LOFreq ASC;";
			} else {
				$qfe = "SELECT fkFront_Ends FROM `FE_Config` WHERE `keyFEConfig` = ".
						$TestDataHeader->GetValue('fkFE_Config');
				$q = "SELECT TEST_LOLockTest.LOFreq, TEST_LOLockTest.PhotomixerCurrent,
						TEST_LOLockTest.PLLRefTotalPower FROM FE_Config LEFT JOIN TestData_header
						ON TestData_header.fkFE_Config = FE_Config.keyFEConfig LEFT JOIN TEST_LOLockTest_SubHeader
						ON TEST_LOLockTest_SubHeader.`fkHeader` = `TestData_header`.`keyId` LEFT
						JOIN TEST_LOLockTest ON TEST_LOLockTest_SubHeader.`keyId` = TEST_LOLockTest.fkHeader
						WHERE TestData_header.Band = " . $TestDataHeader->GetValue('Band')."
						AND TestData_header.fkTestData_Type= 57 AND TestData_header.DataSetGroup= " .
						$TestDataHeader->GetValue('DataSetGroup')." AND TEST_LOLockTest.IsIncluded = 1
						AND FE_Config.fkFront_Ends = ($qfe) GROUP BY TEST_LOLockTest.LOFreq ASC;";
			}
			$t->WriteLogFile($q);
		} else {
			$q = '';
		}
		return $this->run_query($q);
	}
	 /**
	  *
	  * @param integer $occur- occurance which function is called
	  * @param $TestData_Id (default = NULL)
	  * @param $fc (default = NULL)
	  * @param $data (default = NULL)
	  * @param $l- logger (default = NULL)
	  * @param $TestDataHeader (default = NULL)
	  * @return resource
	  */
	public function q($occur, $TestData_Id=NULL, $fc=NULL, $data=NULL, $l=NULL, $TestDataHeader=NULL) {
		if ($occur==1) {
			$q = "SELECT TimeValue,$data
			FROM TEST_Repeatability
			WHERE fkHeader = $TestData_Id
			ORDER BY TimeValue ASC;";
		} elseif ($occur==2) {
			$q = "SELECT MIN(tilt), MAX(tilt),
			MIN(power_pol0_chA), MIN(power_pol0_chB),
			MIN(power_pol1_chA),MIN(power_pol1_chB),
			MAX(power_pol0_chA),MAX(power_pol0_chB),
			MAX(power_pol1_chA),MAX(power_pol1_chB)
			FROM TEST_Workmanship_Amplitude
			WHERE fkHeader = $TestData_Id AND fkFacility = $fc;";
		} elseif ($occur==3) {
			$q = "SELECT MIN(tilt), MAX(tilt), MIN(power_pol0_chA),
			MIN(power_pol1_chA), MAX(power_pol0_chA), MAX(power_pol1_chA)
			FROM TEST_Workmanship_Amplitude
			WHERE fkHeader = $TestData_Id AND fkFacility = $fc;";
		} elseif ($occur==4) {
			$q = "SELECT tilt,power_pol0_chA,power_pol0_chB,
			power_pol1_chA,power_pol1_chB
			FROM TEST_Workmanship_Amplitude
			WHERE fkHeader = $TestData_Id AND fkFacility = $fc ORDER BY TS ASC;";
		} elseif ($occur==5) {
			$q = "SELECT MIN(tilt), MAX(tilt)
			FROM TEST_Workmanship_Phase
			WHERE fkHeader = $TestData_Id
			AND fkFacility = $fc;";
		} elseif ($occur==6) {
			$q = "SELECT phase, tilt FROM TEST_Workmanship_Phase WHERE fkHeader = $TestData_Id
			AND fkFacility = $fc ORDER BY TS ASC;";
			$l->WriteLogFile($q);
		} elseif ($occur==7) {
			$q = "SELECT `TestData_header`.keyID, `TestData_header`.TS,
					`TestData_header`.`fkFE_Config`,`TestData_header`.Meas_SWVer
					FROM FE_Config LEFT JOIN `TestData_header`
					ON TestData_header.fkFE_Config = FE_Config.keyFEConfig
					WHERE TestData_header.Band = " . $TestDataHeader->GetValue('Band')."
					AND TestData_header.fkTestData_Type= " . $TestDataHeader->GetValue('fkTestData_Type')."
					AND TestData_header.DataSetGroup= " . $TestDataHeader->GetValue('DataSetGroup')."
					AND FE_Config.fkFront_Ends = (SELECT fkFront_Ends FROM `FE_Config`
					WHERE `keyFEConfig` = ".$TestDataHeader->GetValue('fkFE_Config').")
					ORDER BY `TestData_header`.keyID DESC;";
		} else {
			$q='';
		}
		return $this->run_query($q);
	}
}
?>