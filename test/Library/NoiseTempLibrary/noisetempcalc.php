<?php
/**
 * ALMA - Atacama Large Millimeter Array
 * (c) Associated Universities Inc., 2006
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307  USA
 *
 * @author Aaron Beaudoin
 * Version 1.0 (07/30/2014)
 *
 *
 * Example code can be found in /test/class.test.php
 *
 */

require_once(dirname(__FILE__) . '/../../../SiteConfig.php');
require_once($site_dbConnect);
require_once($site_classes . '/class.spec_functions.php');
require_once($site_NT . '/NT_db.php');

	interface noisetemp {
		public  function __construct();
		public function setParams($band, $dataSetGroup);
		public function testDatRet();
		public function Trx_Uncorr($TAmb, $CLTemp, $Y);
		public function Tssb_Corr($trx, $IR);
		public function print_data();
		public function calcNoiseTemp();
	}
	class NTCalc{
		var $data;
		var $specs;
		var $band;
		var $dataSetGroup;
		var $db;
		var $keyId;
		var $fc; //keyFacility
		var $SN; //serial number
		var $CCA_componentKeys;
		var $IR; // IR Data
		var $rx; //Receiver data
		var $dbPull;
		
		/**
		 * Constructor
		 */
		public function __construct(){}

		
		/**
		 * Sets initial parameters for data retrieval
		 * 
		 * @param integer $band
		 * @param integer $dataSetGroup
		 * @param integer $keyId
		 * @param integer $fc
		 * @param integer $sn
		 */
		public function setParams($band, $dataSetGroup, $keyId, $fc, $sn) {
			$this->band = $band;
			$this->dataSetGroup = $dataSetGroup;
			$this->keyId = $keyId;
			$this->fc = $fc;
			$this->SN = $sn;
			
			$new_specs = new Specifications();
			$specs = $new_specs->getSpecs('FEIC_NoiseTemperature', $band);
			$dbPull = new NT_db();
			$this->specs = $specs;
			$this->dbPull = $dbPull;
			
			require(site_get_config_main());
			$this->db = site_getDbConnection();
		}
		
		/**
		 * Retrieves noise temperature data (LO Frequency, Center IF, ambient temperature,
		 * and Y factors) from database.
		 * Places all data into $this-> data, keys are data attributes.
		 */
		public function getData() {
			$band = $this->band;
			$dataSetGroup = $this->dataSetGroup; //different query if dataSetGroup != 0??
		
			$this->data = $this->dbPull->qdata($this->keyId, $this->fc);
			
		}
		
		/**
		 * Finds Uncorrected receiver temperature.
		 * 
		 * @param float $Tamb- ambient temperature
		 * @param float $CLTemp- cold load temerature from specs
		 * @param float $Y- Yfactor
		 */
		public function Trx_Uncorr($TAmb, $CLTemp, $Y) {
			if ($Y == 1) {
				return NULL;
			} else {
				return ($TAmb - $CLTemp * $Y) / ($Y - 1);
			}
		}
		
		/**
		 * Finds corrected noise temperature.
		 * 
		 * @param float $trx- uncorrected receiver temperature (from Trx_Uncorr())
		 * @param float $IR- Image rejection data, found by getIRData()
		 * @return corrected noise temperature value.
		 */
		public function Tssb_Corr($trx, $IR) {
			$temp = $trx * (1 + pow(10, (-abs($IR)) / 10));
			return $temp;
		}		
		
		/**
		 * Retrieves image rejection data from database using parameters set in setParams()
		 * MUST call getCCAkeys() first for CCA_componentKeys!!!
		 * Saves data to $this->IR, containing RF and IR for each pol and sb
		 */
		public function getIRData() {
			$specs = $this->specs;
			
			$IR_01 = array();
			$IR_02 = array();
			$IR_11 = array();
			$IR_12 = array();
			$RF_01 = array();
			$RF_02 = array();
			$RF_11 = array();
			$RF_12 = array();
			
			$r = $this->dbPull->qIR($this->fc);
			
			$count = 0;
			while ($row = @mysql_fetch_array($r)) {
				$count++;
				if ($row[2] == 0) {
					if($row[3] == 1) {
						$RF_01[] = $row[0] + $row[1];
						$temp = $row[4];
						if($temp == FALSE) {
							$IR_01[] = $specs['defImgRej'];
						} else {
							$IR_01[] = $temp;
						}
					} else {
						$RF_02[] = $row[0] - $row[1];
						$temp = $row[4];
						if($temp == FALSE) {
							$IR_02 = $specs['defImgRej'];
						} else {
							$IR_02[] = $temp;
						}
					}
				} else {
					if($row[3] == 1) {
						$RF_11[] = $row[0] + $row[1];
						$temp = $row[4];
						if($temp == FALSE) {
							$IR_11 = $specs['defImgRej'];
						} else {
							$IR_11[] = $temp;
						}
					} else {
						$RF_12[] = $row[0] - $row[1];
						$temp= $row[4];
						if($temp == FALSE) {
							$IR_12 = $specs['defImgRej'];
						} else {
							$IR_12[] = $temp;
						}
					}
				}
			}
			$this->IR = array('RF_01' => $RF_01, 'IR_01' => $IR_01, 
								'RF_02' => $RF_02, 'IR_02' => $IR_02, 
								'RF_11' => $RF_11, 'IR_11' => $IR_11, 
								'RF_12' => $RF_12, 'IR_12' => $IR_12);
		}
		 /**
		  * Uses Trx_Uncorr() and Tssb_Corr() to calculate noise temperature for each pol and sb.
		  * Saves calculations to $this->data.
		  */
		public function calcNoiseTemp() {
			$data = $this->data;
			$specs = $this->specs;
			
			$new_data = array();
			foreach ($data as $d) {
				$t_uncorr01 = $this->Trx_Uncorr($d['TAmbient'], $specs['CLTemp'], $d['Pol0Sb1YFactor']);
				$t_uncorr02 = $this->Trx_Uncorr($d['TAmbient'], $specs['CLTemp'], $d['Pol0Sb2YFactor']);
				$t_uncorr11 = $this->Trx_Uncorr($d['TAmbient'], $specs['CLTemp'], $d['Pol1Sb1YFactor']);
				$t_uncorr12 = $this->Trx_Uncorr($d['TAmbient'], $specs['CLTemp'], $d['Pol1Sb2YFactor']);
				$index = array_search($d['RF_usb'], $this->IR['RF_01']);
				$d['Tssb_corr01'] = $this->Tssb_Corr($t_uncorr01, $this->IR['IR_01'][$index]);
				$index = array_search($d['RF_lsb'], $this->IR['RF_02']);
				$d['Tssb_corr02'] = $this->Tssb_Corr($t_uncorr02, $this->IR['IR_02'][$index]);
				$index = array_search($d['RF_usb'], $this->IR['RF_11']);
				$d['Tssb_corr11'] = $this->Tssb_Corr($t_uncorr11, $this->IR['IR_11'][$index]);
				$index = array_search($d['RF_lsb'], $this->IR['RF_12']);
				$d['Tssb_corr12'] = $this->Tssb_Corr($t_uncorr12, $this->IR['IR_12'][$index]);
						
				$new_data[] = $d;
			}
			$this->data = $new_data;
		}
		
		/**
		 * 
		 * @param array $params- array of values to search for. Keys need to be the same as in $this->data.
		 * 
		 * @return string
		 */
		public function findIndex($params) {
			$keys = array_keys($params);
			$vals = array_values($params);

			$index = -1;
			for($j=0; $j<count($this->data); $j++) {
				$cont = TRUE;
				for ($i=0; $i<count($keys); $i++) {
					if ($this->data[$j][$keys[$i]] != $vals[$i]) {
						$cont = FALSE;
					}
				}
				if($cont) {
					$index = $j;
					break;
				}
			}
			return $index;
		}
		
		/**
		 * Pulls CCA_componentKeys from database using parameters set in setParams()
		 */
		public function getCCAkeys() {
			$this->dbPull->qkeys($this->SN, $this->band, $this->fc);
		}
		
		/**
		 * Retrieves CCA data (receiver temperatures) from database
		 * Saves data to $this->data
		 */
		public function getCCANTData() {					
			$r = $this->dbPull->qcca($this->fc);
			
			$Trx01 = array();
			$Trx02 = array();
			$Trx11 = array();
			$Trx12 = array();
			$data_usb = array();
			$data_lsb = array();
			
			$count = 0;
			while ($row = @mysql_fetch_array($r)) {
				$count++;
				$NT_spec = $this->specs['NT80'];
				if ($this->band == 3 && $row[0] == 104) {
					$NT_spec = $this->specs['B3exSpec'];
				}
				
				$LO = $row[0];
				$IF = $row[1];
				$Trx = $row[4];
				$pol = $row[2];
				$sb = $row[3];
				$params = array('FreqLO' => $LO, 'CenterIF' => $IF);
				$index = $this->findIndex($params);
				if($index >= 0) {
					$key1 = "Tssb_corr$pol$sb";
					$key2 = "Trx$pol$sb";
					$key3 = "diff$pol$sb";
					$diff = 100 * abs($this->data[$index][$key1] - $Trx) / $NT_spec;
					$this->data[$index][$key2] = $Trx;
					$this->data[$index][$key3] = $diff;
				}
				/*for ($i=0; $i<count($this->data); $i++) {
					$d = $this->data[$i];
					if($d['FreqLO'] == $LO & $d['CenterIF'] == $IF) {
						$key1 = "Tssb_corr$pol$sb";
						$key2 = "Trx$pol$sb";
						$key3 = "diff$pol$sb";
						$diff = 100 * abs($d[$key1]- $Trx) / $NT_spec;
						$d[$key2] = $Trx;
						$d[$key3] = $diff;
						$this->data[$i] = $d;
						break;
					}
				}//*/
			}
		}
		
	}
?>