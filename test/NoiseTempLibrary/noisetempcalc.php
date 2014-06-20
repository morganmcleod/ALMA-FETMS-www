<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_dbConnect);
require_once($site_classes . '/class.spec_functions.php');

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
		
		public function __construct(){}
		
		public function setParams($band, $dataSetGroup) {
			$this->band = $band;
			$this->dataSetGroup = $dataSetGroup;
			require(site_get_config_main());
			$this->db = site_getDbConnection();
		}
		
		public function testDataRet() {
			$band = $this->band;
			$dataSetGroup = $this->dataSetGroup;
		
			$new_specs = new Specifications();
			$specs = $new_specs->getSpecs('FEIC_NoiseTemperature', $band);
		
			
			//Selects data based on band and dataSetGroup (TestData_Type = 58 for FEIC_NoiseTemperature)
			$qkeyId = "SELECT keyId FROM TestData_header WHERE Band = $band AND fkTestData_Type = 58 AND DataSetGroup = $dataSetGroup";
			$rkeyId = @mysql_query($qkeyId, $this->db);
			$keyIds = array();
			while($row = @mysql_fetch_array($rkeyId)) {
				$keyIds[] = $row[0];
			}
			
			//Gets fkSub_Header for keyIds
			$q = "SELECT keyId FROM Noise_Temp_SubHeader WHERE ";
			foreach($keyIds as $k) {
				$q .= "fkHeader = $k OR ";
			}
			$q = substr($q, 0, -3);
			$r = @mysql_query($q, $this->db);
			$keys = array();
			while($row = @mysql_fetch_array($r)) {
				$keys[]= $row[0];
			}
			
			//Pulls data
			$q = "SELECT FreqLO, CenterIF, TAmbient, Pol0Sb1YFactor, Pol0Sb2YFactor, Pol1Sb1YFactor, Pol1Sb2YFactor FROM Noise_Temp WHERE IsIncluded=1 AND (";
			foreach($keys as $k) {
				$q .= "fkSub_Header = $k OR ";
			}
			$q = substr($q, 0, -3) . ") ORDER BY FreqLO ASC, CenterIF ASC";
			$r = @mysql_query($q, $this->db);
			$data = array();
			while($row = @mysql_fetch_array($r)) {
				$values = array (
						'FreqLO' => $row[0], 
						'CenterIF' => $row[1], 
						'TAmbient' => $row[2], 
						'Pol0Sb1YFactor' => $row[3], 
						'Pol0Sb2YFactor' => $row[4], 
						'Pol1Sb1YFactor' => $row[5], 
						'Pol1Sb2YFactor' => $row[6],);
				$data[] = $values;
				/*$keys = array_keys($values);
				$vals = array_values($values);
				for ($i=0; $i<count($keys); $i++) {
					echo "$keys[$i]: $vals[$i] <br>";
				}	//*/	
			}
			
			$this->data = $data;
			$this->specs = $specs;
		}
		
		public function Trx_Uncorr($TAmb, $CLTemp, $Y) {
			return ($TAmb - $CLTemp * $Y) / ($Y - 1);
		}
		
		public function Tssb_Corr($trx, $IR) {
			return $trx * (1 + pow(10, (-abs($IR)) / 10));
		}
		
		public function print_data() {
			echo "<table border='1'>";
			$keys = array_keys($this->data[0]);
			echo "<tr>";
			for ($i=0; $i<count($keys); $i++) {
				echo "<td>" . $keys[$i] . "</td>";
			}
			echo "</tr>";
			foreach ($this->data as $d) {
				echo "<tr>";
				for ($i=0; $i<count($keys); $i++) {
					echo "<td>" . $d[$keys[$i]] . "</td>";
				}
				echo "</tr>";
			}
			echo "</table>";
		}
		
		public function calcNoiseTemp() {
			$data = $this->data;
			$specs = $this->specs;
			
			$new_data = array();
			foreach ($data as $d) {
				$d['Trx_uncorr00'] = $this->Trx_Uncorr($d['TAmbient'], $specs['CLTemp'], $d['Pol0Sb1YFactor']);
				$d['Trx_uncorr02'] = $this->Trx_Uncorr($d['TAmbient'], $specs['CLTemp'], $d['Pol0Sb2YFactor']);
				$d['Trx_uncorr11'] = $this->Trx_Uncorr($d['TAmbient'], $specs['CLTemp'], $d['Pol1Sb1YFactor']);
				$d['Trx_uncorr12'] = $this->Trx_Uncorr($d['TAmbient'], $specs['CLTemp'], $d['Pol1Sb2YFactor']);
				$d['Tssb_corr00'] = $this->Tssb_Corr($d['Trx_uncorr00'], $specs['defImgRej']);
				$d['Tssb_corr02'] = $this->Tssb_Corr($d['Trx_uncorr02'], $specs['defImgRej']);
				$d['Tssb_corr11'] = $this->Tssb_Corr($d['Trx_uncorr11'], $specs['defImgRej']);
				$d['Tssb_corr12'] = $this->Tssb_Corr($d['Trx_uncorr12'], $specs['defImgRej']);
						
				$new_data[] = $d;
			}
			$this->data = $new_data;
		}
		
	}
?>