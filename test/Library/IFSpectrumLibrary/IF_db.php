<?php
require_once(dirname(__FILE__) . '/../../../SiteConfig.php');
require_once($site_dbConnect);
require_once($site_classes . '/class.spec_functions.php');

class IF_db {
	var $db;
	
	/**
	 * Initializes IF_db class
	 */
	public function IF_db() {
		require(site_get_config_main());
		$this->db = site_getDbConnection();
	}
	
	/**
	 * Returns resource to a given query
	 * @param string $query
	 */
	public function run_query($query) {
		return @mysql_query($query);
	}
	
	/**
	 * Finds spurious noise data.
	 * 
	 * @param int $Band
	 * @param int $IFChannel
	 * @param int $FEid
	 * @param int $DataSetGroup
	 * @param int $offsetamount
	 * @return 2d array- data for spurious noise, where columns are 'Freq_LO', 'Freq_Hz', and 'Power_dBm'
	 */
	public function qdata($Band, $IFChannel, $FEid, $DataSetGroup, $offsetamount = 10) {
		$this->createTable($DataSetGroup, $Band, $FEid);
		
		$q ="SELECT IFSpectrum_SubHeader.keyId, IFSpectrum_SubHeader.FreqLO, TestData_header.keyId
		FROM IFSpectrum_SubHeader, TestData_header,FE_Config
		WHERE IFSpectrum_SubHeader.fkHeader = TestData_header.keyId
		AND IFSpectrum_SubHeader.Band = $Band
		AND IFSpectrum_SubHeader.IFChannel = $IFChannel
		AND IFSpectrum_SubHeader.IFGain = 15
		AND IFSpectrum_SubHeader.IsIncluded = 1
		AND TestData_header.fkFE_Config = FE_Config.keyFEConfig
		AND FE_Config.fkFront_Ends = $FEid
		AND TestData_header.DataSetGroup = $DataSetGroup
		ORDER BY IFSpectrum_SubHeader.FreqLO ASC;";
		
		$r = $this->run_query($q);
		
		$offset = 0;
		$data = array();
		while ($row = @mysql_fetch_array($r)) {
			$FreqLO = $row[1];
			$TDHkey = $row[2];
			$qdata = "SELECT Freq_Hz/1000000000,(Power_dBm + $offset)
				FROM TEMP_IFSpectrum WHERE fkSubHeader = $row[0]
				AND Freq_Hz > 12000000
				ORDER BY Freq_Hz ASC;";
			$rdata = $this->run_query($qdata);
			
			while($rowdata = @mysql_fetch_array($rdata)) {
				$Freq_Hz = $rowdata[0];
				$pow = $rowdata[1];
				$d = array('FreqLO' => $FreqLO,
						   'Freq_Hz' => $Freq_Hz,
						   'Power_dBm' => $pow);
				$data[] = $d;
			}
			$offset += $offsetamount;
		}
		return $data;
	}
	
	/**
	 * Finds power variation data
	 * 
	 *  @param int $DataSetGroup
	 *  @param int $IFChannel
	 *  @param int $Band
	 *  @param int FEid
	 *  @param float $fwin- Window size
	 *  
	 *  @return 2d array- Data for power variation. Columns are 'Freq_LO', 'Freq_Hz', 'Power_dBm'
	 */
	public function qpower($DataSetGroup, $IFChannel, $Band, $FEid, $fwin) {
		$data = array();
		$newSpec = new Specifications();
		$specs = $newSpec->getSpecs('ifspectrum', $Band);
		
		$b6case = ($Band == 6);
		
		$fmin = $specs['fWindow_Low'] * pow (10, 9);
		$fmax = $specs['fWindow_high'] * pow(10, 9);
		$this->createPowVar($DataSetGroup, $Band, $FEid, $fmin, $fmax, $fwin);
		
		$qkeys ="SELECT IFSpectrum_SubHeader.keyId, IFSpectrum_SubHeader.FreqLO, TestData_header.keyId
		FROM IFSpectrum_SubHeader, TestData_header,FE_Config
		WHERE IFSpectrum_SubHeader.fkHeader = TestData_header.keyId
		AND IFSpectrum_SubHeader.Band = $Band
		AND IFSpectrum_SubHeader.IFChannel = $IFChannel
		AND IFSpectrum_SubHeader.IFGain = 15
		AND IFSpectrum_SubHeader.IsIncluded = 1
		AND TestData_header.fkFE_Config = FE_Config.keyFEConfig
		AND FE_Config.fkFront_Ends = $FEid
		AND TestData_header.DataSetGroup = $DataSetGroup
		ORDER BY IFSpectrum_SubHeader.FreqLO ASC;";
		
		$rkeys = $this->run_query($qkeys);
		
		$b6count = 0;
		$b6points = array();
		$maxpow6 = -999;
		while($rowkeys = @mysql_fetch_array($rkeys)) {
			if($b6case) {
				$q6 = "SELECT MAX(Power_dBm), MIN(Power_dBm) 
				FROM TEMP_IFSpectrum WHERE fkSubHeader = $rowkeys[0] 
				AND Freq_Hz < 6000000000 AND Freq_Hz > 5000000000;";
				
				$r6 = $this->run_query($q6);
				$b6val = @mysql_result($r6, 0, 0) - @mysql_result($r6, 0, 1);
				if ($b6val != 0) {
					$b6points[$b6count] = $b6val;
					$b6count++;
				}
			}
			
			for ($i=0; $i<count($b6points); $i++) {
				if ($b6points[$i] > $maxpow6) {
					$maxpow6 = $b6points[$i];
				}
			}
			
			$qvar = "SELECT Freq_Hz, Power_dBm FROM TEMP_TEST_IFSpectrum_PowerVar 
					WHERE WindowSize_Hz = $fwin 
					AND fkSubHeader = $rowkeys[0] 
					ORDER BY Freq_Hz ASC;";
			
			$rvar = $this->run_query($qvar);
			
			while($rowvar = @mysql_fetch_array($rvar)) {
				$freq = $rowvar[0] / pow(10, 9);
				$pow = $rowvar[1];
				
				if($b6case) {
					if ($freq < 6) {
						$pow = "-1";
					}
					if (($freq >= 7) && ($freq <= 9)) {
						if ($pow > $maxpow6) {
							$maxpow6 = $pow;
						}
					}
				}
				
				if($pow != -1) {
					$d = array('FreqLO' => $rowkeys[1], 'Freq_Hz' => $freq, 'Power_dBm' => $pow);
					$data[] = $d;
				}
			}
		}
		if ($b6case) {
			$maxpow = $maxpow6;
			$tempData = array();
			foreach($data as $d) {
				if((($d['Freq_Hz'] > 5.45) && ($d['Freq_Hz'] < 5.52)) || ($d['Freq_Hz'] > 7)) {
					$tempData[] = $d;
				}
			}
			$data = $tempData;
		}
		return $data;
		
	}
	
	/**
	 * Creates temporary table for IF Spectrum to be used by qdata() and createPowVar()
	 * 
	 * @param int $DataSetGroup
	 * @param int $Band
	 * @param int $FEid
	 */
	public function createTable($DataSetGroup, $Band, $FEid) {
		$q = "DROP TABLE IF EXISTS TEMP_IFSpectrum ;";
		$this->run_query($q);
		$qcreate = "CREATE TEMPORARY TABLE IF NOT EXISTS TEMP_IFSpectrum (
				 fkSubHeader INT,
				 fkFacility INT,
				 Freq_Hz DOUBLE,
				 Power_dBm DOUBLE,
				 INDEX (fkSubHeader)
				 );";
		$this->run_query($qcreate);
			
		$qtemp = "SELECT IFSpectrum_SubHeader.keyId
		FROM IFSpectrum_SubHeader, TestData_header, FE_Config
		WHERE IFSpectrum_SubHeader.fkHeader =  TestData_header.keyId
		AND IFSpectrum_SubHeader.IsIncluded =  1 AND TestData_header.DataSetGroup = $DataSetGroup
		AND TestData_header.Band = $Band AND TestData_header.fkTestData_Type = 7
		AND TestData_header.fkFE_Config = FE_Config.keyFEConfig
		AND FE_Config.fkFront_Ends = " . $FEid;
			
		$ifsubkeys = '';
			
		$rtemp = $this->run_query($qtemp, $this->db);
		$tempcount = 0;
		while($rowtemp = @mysql_fetch_array($rtemp)) {
			$ifsubkeys[$tempcount] = $rowtemp['keyId'];
			$tempcount += 1;
		}
			
		$qins = "INSERT INTO TEMP_IFSpectrum
		SELECT IFSpectrum.fkSubHeader,IFSpectrum.fkFacility,IFSpectrum.Freq_Hz,
		IFSpectrum.Power_dBm
		FROM IFSpectrum WHERE (IFSpectrum.fkSubHeader = $ifsubkeys[0]) ";
		
		for ($i=1;$i<count($ifsubkeys);$i++){
			$qins .= " OR (IFSpectrum.fkSubHeader = $ifsubkeys[$i]) ";
		}
		$qins .= ";";
			
		$this->run_query($qins);
		
	}
	
	/**
	 * Creates temporary table for power variation to be used in qpower()
	 * 
	 * @param int $DataSetGroup
	 * @param int $Band
	 * @param int $FEid
	 * @param float $fmin- Minimum frequency
	 * @param float $fmax- Maximum frequency
	 * @param float $fwin- Window size
	 * @param int $fc- key facility number
	 */
	public function createPowVar($DataSetGroup, $Band, $FEid, $fmin, $fmax, $fwin, $fc = 40) {
		$q = "DROP TABLE IF EXISTS TEMP_TEST_IFSpectrum_PowerVar;";
		$this->run_query($q);
		$qcreate = "CREATE TEMPORARY TABLE IF NOT EXISTS TEMP_TEST_IFSpectrum_PowerVar (
				 fkSubHeader INT,
				 fkFacility INT,
				 WindowSize_Hz DOUBLE,
				 Freq_Hz DOUBLE,
				 Power_dBm DOUBLE,
				 INDEX(fkSubHeader)
            );"; 
		$this->run_query($qcreate);
		
		$qtdhkeys = "SELECT TestData_header.keyId
		FROM TestData_header, FE_Config
		WHERE TestData_header.DataSetGroup = $DataSetGroup
		AND TestData_header.fkTestData_Type = 7
		AND TestData_header.Band = $Band
		AND TestData_header.fkFE_Config = FE_Config.keyFEConfig
		AND FE_Config.fkFront_Ends = $FEid
		ORDER BY TestData_header.keyId ASC";
		$rtdhkeys = $this->run_query($qtdhkeys);
		$tdh = array();
		while($rowtdhkeys = @mysql_fetch_array($rtdhkeys)) {
			$tdh[] = $rowtdhkeys[0];
		}
		
		$keyIds = array();
		foreach ($tdh as $t) {
			$qkeyid = "SELECT keyId FROM IFSpectrum_SubHeader
				WHERE fkHeader = $t
				AND IsIncluded = 1
				ORDER BY IFChannel ASC, FreqLO ASC";
			$rkeyid = $this->run_query($qkeyid);
			while($rowkeyid = @mysql_fetch_array($rkeyid)) {
				$keyIds[] = $rowkeyid[0];
			}
		}
		
		$flo = $fmin + ($fwin / 2);
		$fhi = $fmax - ($fwin / 2);

		foreach($keyIds as $k) {
			$qpow = "SELECT Power_dBm, Freq_Hz FROM TEMP_IFSpectrum
			WHERE fkSubHeader = $k
			AND fkFacility = $fc
			order by Freq_Hz ASC";
				
			$rpow = $this->run_query($qpow);
			
			$index = 1;
			$freq = array();
			$pow = array();
			$max = 9 * pow (10,11);
			$min = 9 * pow (10,11);
			$hi = 9 * pow (10,11);
			$lo = 9 * pow (10,11);
				
			while ($rowpow = @mysql_fetch_array($rpow)) {
				$freq[$index] = $rowpow[1];
				$pow[$index] = $rowpow[0];
				$temp = abs($rowpow[1] - $fmin);
				if($temp < $min) {
					$min = $temp;
					$imin = $index;
				}
				
				$temp = abs($rowpow[1] - $fmax);
				if ($temp < $max) {
					$max = $temp;
					$imax = $index;
				}
					
				$temp = abs($rowpow[1] - $flo);
				if ($temp < $lo) {
					$lo = $temp;
					$ilo = $index - 1;
				}
				$temp = abs($rowpow[1] - $fhi);
				if ($temp < $hi) {
					$hi = $temp;
					$ihi = $index - 1;
				}
				$index += 1;
			}
				
			$imax -= 1;
			$imin -= 1;
			$fmin = $freq[$imin + 1];
			$fmax = $freq[$imax + 1];
				
			$span = $ilo - $imin;
				
			$newfreq = array();
			$newpow = array();
				
			for ($i=1; $i<count($pow); $i++) {
				if(($i >= $imin) && ($i <= $imax)) {
					$newfreq[$i] = $freq[$i];
					$newpow[$i] = $pow[$i];
				}
			}
			
			for ($i=$ilo; $i<$ihi; $i++) {
				$start = $i - $span;
				$end = $i + $span;
					
				$vmin = 1000;
				$vmax = -1000;
						
				for($j=$start; $j<$end; $j++) {
					$temp = $newpow[$j];
					if ($temp < $vmin) {
						$vmin = $temp;
					}
					if ($temp > $vmax) {
						$vmax = $temp;
					}
				}
						
				$dv = $vmax - $vmin;
				$f = $newfreq[$i];
				$win = (float)$fwin;
						
				$qins = "INSERT INTO TEMP_TEST_IFSpectrum_PowerVar
				(fkSubHeader, WindowSize_Hz, Freq_Hz, Power_dBm, fkFacility)
				VALUES
				('$k','$win','$f','$dv','$fc');";
				$this->run_query($qins);
			}
		}
	}
	
	/**
	 * Deletes temporary tables
	 */
	public function deleteTable() {
		$qdel = "DROP TABLE TEMP_IFSpectrum;";
		$this->run_query($qdel);
		$qdel = "DROP TABLE TEMP_TEST_IFSpectrum_PowerVar;";
		$this->run_query($qdel);
	}
}
?>