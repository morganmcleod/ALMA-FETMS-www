<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_dbConnect);
require_once($site_classes . '/class.spec_functions.php');

	class NT_db{
		var $db;
		var $CCA_componentKeys;
		
		/**
		 * Initializes database connection
		 */
		public function NT_db() {
			require(site_get_config_main());
			$this->db = site_getDbConnection();
		}
		
		/**
		 * Runs query
		 * 
		 * @param string $query- SQL query 
		 */
		public function run_query($query) {
			return @mysql_query($query, $this->db);
		}
		
		/**
		 * Initializes CCA_componentKeys
		 * 
		 * IMPORTANT: This function must be called in order to call qIR() and qSpec()
		 * 
		 * @param int $SN- serial number
		 * @param int $band- band
		 * @param int $fc- keyFacility
		 */
		public function qkeys($SN, $band, $fc) {
			$qckeys = "SELECT keyId FROM FE_Components WHERE SN = $SN AND fkFE_ComponentType = 20 AND band = $band ";
			$qckeys .= "AND keyFacility = $fc GROUP BY keyId DESC";
			$rckeys = @mysql_query($qckeys, $this->db);
			$CCA_componentKeys = array();
			while ($row = @mysql_fetch_array($rckeys)) {
				$temp = $row[0];
				$CCA_componentKeys[] = $row[0];
			}
			$this->CCA_componentKeys = $CCA_componentKeys;
		}
		
		/**
		 * Retrieves noise temperature data from database
		 * 
		 * @param int $keyId
		 * @param int $fc- keyFacility
		 * 
		 * @return array- Data structured into rows of values for columns of data type
		 */
		public function qdata($keyId, $fc) {
			$q = "SELECT FreqLO, CenterIF, TAmbient, Pol0Sb1YFactor, Pol0Sb2YFactor, Pol1Sb1YFactor, Pol1Sb2YFactor
			FROM Noise_Temp
			WHERE fkSub_Header= $keyId
			AND keyFacility = $fc
			AND Noise_Temp.IsIncluded = 1
			ORDER BY FreqLO ASC, CenterIF ASC";
				
			$r = $this->run_query($q);
			$data = array();
			while($row = @mysql_fetch_array($r)) {
			$values = array (
				'FreqLO' => $row[0],
				'CenterIF' => $row[1],
				'TAmbient' => $row[2],
				'Pol0Sb1YFactor' => $row[3],
				'Pol0Sb2YFactor' => $row[4],
				'Pol1Sb1YFactor' => $row[5],
						'Pol1Sb2YFactor' => $row[6],
								'RF_usb' => $row[0] + $row[1],
										'RF_lsb' => $row[0] - $row[1]);
										$data[] = $values;
			}
				
			return $data;
		}
		
		/**
		 * Returns resource to database table with image rejection data.
		 * 
		 * @param int $fc- keyFacility
		 * 
		 * @return resource
		 */
		public function qIR($fc) {
			$CCA_componentKeys = $this->CCA_componentKeys;
			$CCA_TD_key = FALSE;
			$index = 0;
			do {
				$compKey = $CCA_componentKeys[$index];
			
				$q = "SELECT keyID FROM TestData_header WHERE fkTestData_Type = 38 AND fkDataStatus = 7 AND fkFE_Components = $compKey AND keyFacility = $fc";
				$r = $this->run_query($q);
				$CCA_TD_key = @mysql_result($r, 0, 0);
				$index++;
			} while($CCA_TD_key == FALSE && $index < count($CCA_componentKeys));
				
			$q = "SELECT FreqLO, CenterIF, Pol, SB, SBR FROM CCA_TEST_SidebandRatio WHERE fkHeader = $CCA_TD_key AND fkFacility = $fc ORDER BY POL DESC, SB DESC, FreqLO ASC, CenterIF DESC";
			return $this->run_query($q);
		}
		
		/**
		 * Returns resource to database table with CCA temperature data
		 * 
		 * @param int $fc- keyFacility
		 * 
		 * @return resource
		 */
		public function qcca($fc) {
			$CCA_componentKeys = $this->CCA_componentKeys;
			$cnt = 0;
			$compKey = '';
			$CCA_NT_key = FALSE;
			while ($CCA_NT_key == FALSE && $cnt < count($CCA_componentKeys)) {
				$compKey = $CCA_componentKeys[$cnt];
				$q ="SELECT keyId FROM TestData_header WHERE fkFE_Components = $compKey AND fkTestData_Type = 42 AND fkDataStatus=7 AND keyFacility = $fc GROUP BY keyId DESC";
				$r = @mysql_query($q, $this->db);
				$CCA_NT_key = @mysql_result($r, 0, 0);
				$cnt++;
			}
				
			$q = "SELECT MAX(keyDataSet) FROM CCA_TEST_NoiseTemperature WHERE fkheader = $CCA_NT_key";
			$r = $this->run_query($q);
			$keyDataSet = @mysql_result($r, 0, 0);
				
			$q = "SELECT FreqLO, CenterIF, Pol, SB, Treceiver FROM CCA_TEST_NoiseTemperature WHERE fkheader = $CCA_NT_key AND keyDataSet = $keyDataSet ORDER BY POL DESC, SB DESC, FreqLO ASC, CenterIF DESC";
			return $this->run_query($q);
		}
	}
?>
