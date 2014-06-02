<?php	
	//require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once(dirname(__FILE__) . '/../../classes/class.spec_functions.php');
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
	
	public function test_pt($test_type, $band=None){
		if ($band==None) {
			$band=array(0);
		}
		$n = new Specifications();
		$specs = $n->getSpecs($test_type, $band);
		$spec=array();
		$i=1;
		foreach ($specs as $s){
			$spec[]=$s[3];
			echo "$s[2]: $s[3] for band$i <br> ";
			$i++;
		}
	}
	
	public function test_flos($band){
		$band = array(4);
		$new_spec = new Specifications();
		$spec = $new_spec->getSpecs(array('FLOSweep'), $band);
		$specs = array();
		foreach($spec as $s) {
			$specs[$s[2]] = $s[3];
		}
		echo $specs['FLOSpts_win'], $specs['FLOSstdev'];
	}
	
	public function test_bspec($band){
		$spec_names = array();
	    for ($i=1; $i<6; $i++) {
	    	$name = 'Bspec_bbTSSB' . $i . 'f';
	    	$spec_names[] = $name;
	    	$spec_names[] = 'Bspec_bbTSSB' . $i . 's';
	    }
		$new_spec = new Specifications();
	    $spec = $new_spec->getSpecs(array('FEIC_NoiseTemperature'), $band, $spec_names);
	    $specs = array();
	    $i=0;
	    while ($i<count($spec_names)) {
	    	$x = $i+1;
	    	echo (string)$spec[$i][3], ": ", $spec[$i+1][3], "<br>";
	    	$specs[] = array((string)$spec[$i][3] => $spec[$i+1][3]);
	    	$i+=2;
	    }
	    echo $specs[0]['92'], "<br>";
	    /*
	    foreach ($spec as $s) {
	    	$specs[] = $s[3];
	    	echo "$s[3] <br>";
	    }*/
	    $freqs = array('92','96','100','104','108');
	    foreach ($specs as $s){
    		
		    foreach ($freqs as $f) {
		    	if(!empty($s[$f])) {
		    		echo "$f: $s[$f] <br>";
		    	}
		    }
	    }
	}
	
	public function test_NT(){
		//$this->$Band = 3;
		
		$new_specs = new Specifications();
		$specs = $new_specs->getSpecs(array('FEIC_NoiseTemperature') , array(3));
		
		$this->lower_80_RFLimit = 0;
		$this->upper_80_RFLimit = 0;
		
		foreach($specs as $s) {
			if($s[2]=='CLTemp') {
				$this->effColdLoadTemp = $s[3];         // effective cold load temperature
			}
			if($s[2]=='defImgRej') {
				$this->default_IR = $s[3];              // default image rejection to use if no CCA data available.
			}
			if($s[2]=='loIFLim') {
				$this->lowerIFLimit = $s[3];            // lower IF limit
			}
			if($s[2]=='hiIFLim') {
				$this->upperIFLimit = $s[3];            // upper IF limit
			}
			if($s[2]=='NT20') {
				$this->NT_allRF_spec = $s[3];           // spec which must me met at all points in the RF band
			}
			if($s[2]=='NT80') {
				$this->NT_80_spec = $s[3];              // spec which must be met over 80% of the RF band
			}
			// extra Tssb spec applies to band 3 only:
			if ((3 == 3) & ($s[2]=='B3exSpec'))
				$this->NT_B3Special_spec=$s[3];
		
			// lower RF limit for applying 80% spec:
			if($s[2]=='NT80RF_loLim') {
				$this->lower_80_RFLimit = $s[3];
			}
			//$this->lower_80_RFLimit = ($s[2]=='NT80RF_loLim')? $s[3] : 0;
			 
			// upper RF limit for applying 80% spec:
			if($s[2]=='NT80RF_hiLim') {
				$this->upper_80_RFLimit = $s[3];
			}
			//$this->upper_80_RFLimit = ($s[2]=='NT80RF_hiLim') ? $s[3] : 0;
			 
			$this->lowerRFLimit = 0;
			$this->upperRFLimit = 1000;
		}
		echo "$this->effColdLoadTemp $this->default_IR $this->lowerIFLimit $this->upperIFLimit $this->NT_allRF_spec <br>";
		echo "$this->NT_80_spec. $this->NT_B3Special_spec $this->lower_80_RFLimit $this->upper_80_RFLimit <br>";
		/*$test=array("FEIC_NoiseTemperature");
		$band=array(3);
		$spec=array("CLTemp","NT80","NT20","hiIFLim");
		
		$s = new Specifications();
		$t = $s->getSpecs($test, $band);
		foreach($t as $ex) {
			//echo "$ex[0], $ex[1], $ex[2], $ex[3] <br>";
		}
		$c= count($t);
		//echo $c . "<br>";
		
		for($a=0; $a<$c; $a++){
			$new_spec = $t[$a][3];
			//echo $new_spec . "<br>";
			$color = $s->chkNumAgnstSpec(70, "<", $new_spec);
			//echo $color . "<br>";
			
			$perc = $s->numWithinPercent(70, 75, $new_spec);
			//echo $perc . "<br>";
		}
		*/
		echo "done";
	}
}
$c = new test();
$test_type = array('Yfactor');
$band = array(3);
//$t = $c->test_flos($band);
$t = $c->test_NT();
//$t = $c->test_pt($test_type, $band);
?>