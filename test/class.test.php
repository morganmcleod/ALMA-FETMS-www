<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.spec_functions.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.pwrspectools.php');
require_once($site_classes . '/class.logger.php');
require_once($site_FEConfig . '/HelperFunctions.php');
require_once($site_dBcode . '/../dBcode/ifspectrumdb.php');
require_once($site_dBcode . '/../dBcode/beameffdb.php');
require_once($site_dbConnect);

class test {
	var $logger;
	var $GNUPLOT_path;
	var $writedirectory;
	var $url_directory;
	var $aborted;
	var $DataSetBand;
	var $DataSetGroup;
	var $FEid;
	var $dbConnection;
	var $FacilityCode;
	var $new_spec;
	var $test_type;
	var $db_pull;
	var $TDHkeys;
	var $TS;
	var $urls;
	var $NoiseFloor;

	public function __construct() {
		require(site_get_config_main());
		$this->logger = new Logger('IFSpectrumPlotter.php.txt', 'w');
        $this->GNUPLOT_path = $GNUPLOT;
        $this->writedirectory = $main_write_directory;
        $this->url_directory = $main_url_directory;
        //$swver = $this->plotswversion;
        $this->aborted = 0;
	}

	public function beff_test($band) {
		require(site_get_config_main());
		$db = site_getDbConnection();
		$db_pull = new BeamEffDB($db);
		$new_spec = new Specifications();
		
		$rf = 139;
		
		$spec = $new_spec->getSpecs('beameff', 4);
		if (count($spec['rf_cond']) > 1) {
			$p0spec = $p1spec = $spec['pspec'];
			for ($i=0; $i<count($spec['rf_cond']); $i+=2) {	
				if ($spec['rf_cond'][$i] <= $rf && $rf <= $spec['rf_cond'][$i+1]) {
					$p0spec = $spec['rf_val'][$i];
					$p1spec = $spec['rf_val'][$i+1];
				}
			}
		} else {
			$p0spec = $p1spec = $spec['pspec'];
		}
		echo $p0spec;
	}
	
	public function int_test($band) {
		require(site_get_config_main());
		require_once(site_get_classes() . '/class.frontend.php');
		$this->DataSetBand = $band;
		$this->dbConnection = site_getDbConnection();
		$this->new_spec = new Specifications();
		$this->test_type = 'ifspectrum';
		$this->db_pull = new IFSpectrumDB($this->dbConnection);
		
		$val = $this->db_pull->qTDH(3,87,0);
		$TDHkeys = $val[0];
		$this->TS = $val[1];
		
		$val = $this->db_pull->qurl($TDHkeys);
		$this->urls = $val[0];
		$numurl = $val[1];
		echo $this->urls[0]->keyId;
		
		if ($numurl > 0) {
			//$val = $this->db_pull->qnf($this->TDHkeys);
			$this->NoiseFloor = new GenericTable(); //$val[0];
			$this->NoiseFloor->Initialize('TEST_IFSpectrum_NoiseFloor_Header',$this->db_pull->qnf($TDHkeys),'keyId');
			$this->NoiseFloorHeader = $this->NoiseFloor->keyId; //$val[1];
		}
		
		$select_0 = 'IFSpectrum_SubHeader.FreqLO, ROUND(TEST_IFSpectrum_TotalPower.InBandPower, 1)';
		$from_0 = 'IFSpectrum_SubHeader, TEST_IFSpectrum_TotalPower';
		$where_0 = 'TEST_IFSpectrum_TotalPower.fkSubHeader = IFSpectrum_SubHeader.keyId <br>and IFSpectrum_SubHeader.IsIncluded = 1';
		$pwr = $this->db_pull->q_num($TDHkeys, $select_0, $from_0, $where_0, 0, 1, 0,92);
		/*
		$rifsub = $this->db_pull->qifsub(3, 0, 87, 0);
		echo "$rifsub <br>";
		$offset = 0;
		while ($rowifsub = @mysql_fetch_array($rifsub)){
			echo "$rowifsub[1] $rowifsub[2] <br>";
			$rdata = $this->db_pull->qdata(False, $rowifsub, $offset, NULL);
			echo $rdata . "<br>";
			while ($rowdata = @mysql_fetch_array($rdata)) {
				$stringData = "$rowdata[0]\t$rowdata[1]\r\n";
				echo "$stringData <br>";
			}
			$offset += 10;
		}
		for($iTDH=0;$iTDH<count($TDHkeys);$iTDH++) {
			$keyTDH = $TDHkeys[$iTDH];
			$rifsub = $this->db_pull->q_other('ifsub',NULL,NULL,NULL,NULL,NULL,NULL,NULL,$keyTDH);
			while ($rowifsub = @mysql_fetch_array($rifsub)) {
				echo $rowifsub[0] . "<br>";
			}
		}
		$rifsub = $this->db_pull->qifsub(3, 0, 87, 0);
		while($rowifsub = @mysql_fetch_array($rifsub)) {
			$rmax = $this->db_pull->q_other('max',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,$rowifsub, 31 * pow(10,6));
			//echo "$rmax <br>";
		}*/
		
		
	}
	
	public function ntest($band) {
		$new_spec = new Specifications();
		$rf = 135;
		$p0spec = $p1spec = 0;
		$spec = $new_spec->getSpecs('beameff', $band);
		echo count($spec['rf_cond']);
			if (count($spec['rf_cond']) > 0) {
				$p0spec = $p1spec = $spec['pspec'];
				for ($i=0; $i<count($spec['rf_cond']); $i+=2) {	
					if ($spec['rf_cond'][$i] <= $rf && $rf <= $spec['rf_cond'][$i+1]) {
						$p0spec = $spec['rf_val'][$i];
						$p1spec = $spec['rf_val'][$i+1];
					}
				}
			} else {
				$p0spec = $p1spec = $spec['pspec'];
			}
		
		echo $p0spec, $p1spec;
	}
	
	public function new_test($Band) {
		$n = new Specifications();
		$this->Band = 3;
		$specs = $n->getSpecs('FEIC_NoiseTemperature', $this->Band);
	
		foreach ($specs as $s) {
			echo "$s <br>";
		}
		echo $specs['CLTemp'], $specs['defImgRej'], $specs['loIFLim'], $specs['hiIFLim'], $specs['NT20'], $specs['NT80'];
		echo "<br>";
		$this->effColdLoadTemp = $specs['CLTemp']; // effective cold load temperature
		$this->default_IR = $specs['defImgRej']; // default image rejection to use if no CCA data available.
		$this->lowerIFLimit = $specs['loIFLim']; // lower IF limit
		$this->upperIFLimit = $specs['hiIFLim']; // upper IF limit
		$this->NT_allRF_spec = $specs['NT20']; // spec which must me met at all points in the RF band
		$this->NT_80_spec = $specs['NT80']; // spec which must be met over 80% of the RF band
		
		// extra Tssb spec applies to band 3 only:
		if ($this->Band == 3) {
			$this->NT_B3Special_spec=$specs['B3exSpec'];
		}
		// lower RF limit for applying 80% spec:
		$this->lower_80_RFLimit = (isset($specs['NT80RF_loLim']))? $specs['NT80RF_loLim'] : 0;
		
		// upper RF limit for applying 80% spec:
		$this->upper_80_RFLimit = (isset($specs['NT80RF_hiLim'])) ? $specs['NT80RF_hiLim'] : 0;
		
		$this->lowerRFLimit = 0;
		$this->upperRFLimit = 1000;
		
		echo "effColdLoadTemp: ", $this->effColdLoadTemp, "; default_IR: ", $this->default_IR,
		"; lowerIFLimit: ". $this->lowerIFLimit, ";<br> upperIFLimit: ", $this->upperIFLimit,
		"; NT_allRF_spec: ", $this->NT_allRF_spec, "; NT_80_spec: ", $this->NT_80_spec, "<br>";
		echo "NT_B3Special_spec: ", $this->NT_B3Special_spec, "; lower_80_RFLimit: ", $this->lower_80_RFLimit,
		"; upper_80_RFLimit: ", $this->upper_80_RFLimit, "; lowerRFLimit: ", $this->lowerRFLimit,
		"; upperRFLimit: ", $this->upperRFLimit, "<br>";
		
		$keys = array_keys($specs);
		$values = array_values($specs);
		for($i=0; $i<count($specs); $i++) {
		echo "$keys[$i]: $values[$i] <br>";
		}
	}
	/*
	 public function test_NT(){
	//$this->$Band = 3;
	$NT = new NoiseTemperature();
	$NT->Load
	}*/
}
$c = new test();
$test_type = array('Yfactor');
$band = 3;
//$t = $c->beff_test($band);
//$t = $c->test_flos($band);
//$t = $c->new_test($band);
$t = $c->int_test($band);
//$t = $c->test_pt($test_type, $band);
?>