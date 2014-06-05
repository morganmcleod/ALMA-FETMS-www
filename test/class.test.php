<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.spec_functions.php');

class test {
	private $Band;
	private $effColdLoadTemp;       // effective cold load temperature
	private $default_IR;            // default image rejection to use if no CCA data available.
	private $lowerIFLimit;          // lower IF limit
	private $upperIFLimit;          // upper IF limit
	private $NT_allRF_spec;         // spec which must me met at all points in the RF band
	private $NT_80_spec;            // spec which must be met over 80% of the RF band
	private $NT_B3Special_spec;     // special spec for band 3 average of averages
	private $lower_80_RFLimit;      // lower RF limit for 80% spec
	private $upper_80_RFLimit;      // upper RF limit for 80% spec

	public function test() {}

	public function ntest($band) {
		$new_spec = new Specifications();
		$specs = $new_spec->getSpecs('ifspectrum', $band);
		
		$this->fWindow_Low = $specs['fWindow_Low'];
		$this->fWindow_High = $specs['fWindow_high'];
		
		echo "$this->fWindow_Low $this->fWindow_High <br>";
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
//$t = $c->test_flos($band);
//$t = $c->new_test($band);
$t = $c->ntest($band);
//$t = $c->test_pt($test_type, $band);
?>