<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.pwrspectools.php');
require_once($site_classes . '/class.logger.php');
require_once($site_FEConfig . '/HelperFunctions.php');
require_once($site_dbConnect);
require_once($site_classes . '/class.spec_functions.php');

class IFSpectrumDB { //extends DBRetrieval {
	var $DataSetBand;
	var $DataSetGroup;
	var $FEid;

	var $dbConnection;
	var $FacilityCode;

	public function IFSpectrumDB($db) {
		require(site_get_config_main());
		$this->dbConnection = $db;
	}

	public function run_query($query) {
		return @mysql_query($query, $this->dbConnection);
	}

	public function q_num($TDHkeys, $select, $from, $where, $num, $total, $ifchannel, $lo) {
		$q = "select $select from $from where IFSpectrum_SubHeader.FreqLO =$lo and IFSpectrum_SubHeader.IFGain = {$num} and $where and IFSpectrum_SubHeader.IFChannel =  $ifchannel and ((IFSpectrum_SubHeader.fkHeader =  " . $TDHkeys[0] . ") ";
		/*
		$q = "select ";
		
		$q .= $select . " from " . $from;
		
		$q .= " where IFSpectrum_SubHeader.FreqLO = $lo
				 and IFSpectrum_SubHeader.IFGain = " . (string)$num .
				" and " . $where . 
				" and IFSpectrum_SubHeader.IFChannel = " . $ifchannel .
				" and ((IFSpectrum_SubHeader.fkHeader =  " . $TDHkeys[0] . ") ";
				*/
		for($iTDH=1; $iTDH<count($TDHkeys); $iTDH++) {
			$q .= "OR (IFSpectrum_SubHeader.fkHeader = " . $TDHkeys[$iTDH] . ") ";
		}
		$q .= ");"; 
		/*
		$q = "select IFSpectrum_SubHeader.FreqLO, ROUND(TEST_IFSpectrum_TotalPower.InBandPower,1)
		from IFSpectrum_SubHeader, TEST_IFSpectrum_TotalPower
		where IFSpectrum_SubHeader.FreqLO =$lo
		and IFSpectrum_SubHeader.IFGain = 0
		and IFSpectrum_SubHeader.IFChannel = $ifchannel
		and TEST_IFSpectrum_TotalPower.fkSubHeader = IFSpectrum_SubHeader.keyId
		and IFSpectrum_SubHeader.IsIncluded = 1
		and ((IFSpectrum_SubHeader.fkHeader =  " . $TDHkeys[0] . ") ";
		
		 
		//Display for all TDH keys
		for ($iTDH=1;$iTDH<count($TDHkeys);$iTDH++){
		//Iterate through each TDHkey value and generate power data for each one.
			$q .= " OR (IFSpectrum_SubHeader.fkHeader =  " . $TDHkeys[$iTDH] . ") ";
		}
		$q .= ");";*/
		//echo "$q <br>";
		$r = $this->run_query($q);
		$pwr = @mysql_result($r,0,$total);
		return $pwr;
	}

	public function qifsub($Band, $IFChannel, $FEid, $DataSetGroup) {
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
		
		return $this->run_query($q);
	}
	
	public function qlo($TDHkeys, $ifchan=false, $ifchannel=0) {
		$qlo = "SELECT DISTINCT(FreqLO) FROM IFSpectrum_SubHeader
                WHERE ((fkHeader = " . $TDHkeys[0] . ")";
				
		for ($iTDH=1; $iTDH < count($TDHkeys); $iTDH++) {
            //Iterate through each TDHkey value and generate power data for each one.
            $qlo .= " OR (fkHeader =  " . $TDHkeys[$iTDH] . ") ";
        }
		
		$qlo .= ")";
		
		if ($ifchan) {
			$qlo .=  " AND IFChannel = $ifchannel";
		}
		$qlo .= " AND IsIncluded = 1 ORDER BY FreqLO ASC;";
		 
		$rlo = $this->run_query($qlo);
		 
		return $rlo;
		  /*
		$rowcount = 0;
		while ($rowlo = @mysql_fetch_array($rlo)) {
			$lo = $rowlo[0];
			if ($lo > 0) {
				if ($rowcount % 2 == 0) {
					$trclass = "alt";
				} else {
					$trclass = "";
				}
				
				$pwr_0 = q_num($TDHkeys, 0, 1);
				$pwr_15_inband = q_num($TDHkeys, 15, 0)
				$pwr_total = q_num($TDHkeys, 15, 1);
				$pwrdiff = $pwr_total - $pwr_15_inband;
				$pwr_0 = number_format(round($pwr_0,1), 1, '.', '');
				$pwr_15_inband = number_format(round($pwr_15_inband,1), 1, '.', '');
				$pwr_total = number_format(round($pwr_total,1), 1, '.', '');
				$pwrdiff = number_format(round($pwrdiff,1), 1, '.', '');
				
				$inband_diff = abs($pwr_0 - $pwr_15_inband);
				
				echo "<tr class = $trclass><td><b>$lo</b></td>";

                // Color the background light red if the 0 dB and 15 dB results are not 15 +/- 1 dB apart:
                $redHilite = FALSE;
                if ($inband_diff < 14 || $inband_diff > 16)
                    $redHilite = TRUE;

                // Color the foreground red if the 0 dB gain value is >= -22 dBm:
                $fontcolor = "#000000";
                if ($pwr_0 > -22)
                    $fontcolor = "#FF0000";

                // Output the 0 dB gain in-band power.
                // TODO:  This is totally screwy that we're getting the red highlighting from the "table7" span CSS:
                echo "<td>";
                if ($redHilite)
                    echo "<span>";
                echo "<font color='$fontcolor'>$pwr_0</font>";
                if ($redHilite)
                    echo "</span>";
                echo "</td>";

                // Color the foreground red if the 15 dB gain value is <= -22 dBm:
                $fontcolor = "#000000";
                if ($pwr_15_inband < -22)
                    $fontcolor = "#FF0000";

                // Output the 15 dB gain in-band power:
                // TODO:  This is totally screwy that we're getting the red highlighting from the "table7" span CSS:
                echo "<td style='border-left:solid 1px #000000;'>";
                if ($redHilite)
                    echo "<span>";
                echo "<font color='$fontcolor'>$pwr_15_inband</font>";
                if ($redHilite)
                    echo "</span>";
                "</td>";
				
				echo "<td><b>$pwr_total</b></td>";

                // Color the foreground red if there is more than 3 dB difference between total and in-band power:
                $fontcolor = "#000000";
                if ($pwrdiff > 3)
                    $fontcolor = "#ff0000";

                // Output the total minus in-band difference:
                echo "<td><font color = $fontcolor><b>$pwrdiff</b></font></td>";
                echo "</tr>";

                $rowcount += 1;
			}
		 }*/
	}
	
	public function qtemp($DataSetGroup, $DataSetBand, $keyId) {
	
		$qtemp = "SELECT IFSpectrum_SubHeader.keyId 
		FROM IFSpectrum_SubHeader, TestData_header, FE_Config 
		WHERE IFSpectrum_SubHeader.fkHeader =  TestData_header.keyId 
		AND IFSpectrum_SubHeader.IsIncluded =  1 AND TestData_header.DataSetGroup = $DataSetGroup 
		AND TestData_header.Band = $DataSetBand AND TestData_header.fkTestData_Type = 7 
		AND TestData_header.fkFE_Config = FE_Config.keyFEConfig 
		AND FE_Config.fkFront_Ends = " . $keyId;
		
		$ifsubkeys = '';
		
		$rtemp = $this->run_query($qtemp);
		$tempcount = 0;
		while($rowtemp = @mysql_fetch_array($rtemp)) {
			$ifsubkeys[$tempcount] = $rowtemp['keyId'];
			$tempcount += 1;
		}
		
		return $ifsubkeys;
	}

	public function qdata($powervar, $row, $offset = NULL, $windowSize = NULL) {
		if($powervar) {
			$qdata = "SELECT Freq_Hz,Power_dBm FROM TEMP_TEST_IFSpectrum_PowerVar
						 WHERE fkSubHeader = $row[0]
						 AND WindowSize_Hz = $windowSize
						 ORDER BY Freq_Hz ASC;";
		} else {
			$qdata = "SELECT Freq_Hz/1000000000,(Power_dBm + $offset) 
			FROM TEMP_IFSpectrum WHERE fkSubHeader = $row[0] 
			AND Freq_Hz > 12000000 
			ORDER BY Freq_Hz ASC;";
		}
		
		return $this->run_query($qdata);
	}
	
	public function q_other($request, $ifsubkeys = NULL, $DataSetBand = NULL, $IFChannel = NULL, $FEid = NULL, $DataSetGroup = NULL, $TDHkeys = NULL, $IFGain = NULL, $keyTDH = NULL, $row = NULL, $windowSize = NULL) {		
		//echo "Request: $request, ifsubkeys: $ifsubkeys, Band: $DataSetBand, IFChannel: $IFChannel, FEid: $FEid, DataSetGroup: $DataSetGroup, TDHkeys: $TDHkeys, IFGain: $IFGain, keyTDH: $keyTDH, row: $row, WindowSize: $windowSize <br>";
		
		$q = '';
		if($request == 'q1') {
			$q = "CREATE TEMPORARY TABLE IF NOT EXISTS TEMP_TEST_IFSpectrum_PowerVar (
				 fkSubHeader INT,
				 fkFacility INT,
				 WindowSize_Hz DOUBLE,
				 Freq_Hz DOUBLE,
				 Power_dBm DOUBLE,
				 TS TIMESTAMP,
				 INDEX(fkSubHeader)
            );"; 
		} elseif ($request == 'q2') {
			$q = "CREATE TEMPORARY TABLE IF NOT EXISTS TEMP_IFSpectrum (
				 fkSubHeader INT,
				 fkFacility INT,
				 Freq_Hz DOUBLE,
				 Power_dBm DOUBLE,
				 INDEX (fkSubHeader)
				 );";
		} elseif($request == 'q3') {
			$q = "INSERT INTO TEMP_IFSpectrum
				 SELECT IFSpectrum.fkSubHeader,IFSpectrum.fkFacility,IFSpectrum.Freq_Hz,
				 IFSpectrum.Power_dBm
				 FROM IFSpectrum WHERE (IFSpectrum.fkSubHeader = $ifsubkeys[0]) ";
			
			for ($i=1;$i<count($ifsubkeys);$i++){
				$q .= " OR (IFSpectrum.fkSubHeader = $ifsubkeys[$i]) ";
			}
			$q .= ";";
		} elseif ($request == 'lo') {
			$q = "SELECT IFSpectrum_SubHeader.FreqLO
                FROM IFSpectrum_SubHeader, TestData_header, FE_Config
                WHERE IFSpectrum_SubHeader.fkHeader = TestData_header.keyId
                AND IFSpectrum_SubHeader.Band = $DataSetBand
                AND IFSpectrum_SubHeader.IFChannel = $IFChannel
                AND IFSpectrum_SubHeader.IFGain = 15
                AND IFSpectrum_SubHeader.IsIncluded = 1
                AND TestData_header.fkFE_Config = FE_Config.keyFEConfig
                AND FE_Config.fkFront_Ends = $FEid
                AND TestData_header.DataSetGroup = $DataSetGroup
                GROUP BY IFSpectrum_SubHeader.FreqLO ASC";
			
		} elseif ($request == 'url') {
			$q = "SELECT keyId FROM TEST_IFSpectrum_urls
					 WHERE fkHeader = " . $TDHkeys[0] . "
					 AND Band = $DataSetBand
					 AND IFChannel = $IFChannel
					 AND IFGain = $IFGain;";
		} elseif ($request == 'ifsub') {
			$q = "SELECT keyId FROM IFSpectrum_SubHeader
					 WHERE fkHeader = $keyTDH
					 AND IsIncluded = 1
					 ORDER BY IFChannel ASC, FreqLO ASC;";
		} elseif($request == 'max') {
			$q = "SELECT MAX(Power_dBm) FROM TEMP_TEST_IFSpectrum_PowerVar WHERE fkSubHeader = $row[0] AND WindowSize_Hz = $windowSize;";
			return @mysql_query($q, $this->dbConnection) or die('Failed on query in dataplotter.php line ' . __LINE__);
		} elseif($request == '6') {
			$q = "SELECT MAX(Power_dBm), MIN(Power_dBm) FROM TEMP_IFSpectrum WHERE fkSubHeader = $row[0] AND Freq_Hz < 6000000000 AND Freq_Hz > 5000000000;";
		} else {
			$q = '';
		}
		return $this->run_query($q);
	}
	
	public function qurl($TDHkeys) {
		$qurl = "SELECT keyId, IFChannel FROM TEST_IFSpectrum_urls
                 WHERE((TEST_IFSpectrum_urls.fkHeader =  " . $TDHkeys[0] . ") ";
			
		for ($iTDH=1; $iTDH<count($TDHkeys); $iTDH++) {
			$qurl .= " OR (TEST_IFSpectrum_urls.fkHeader = " . $TDHkeys[$iTDH] . ") ";
		}
			
		$qurl .= ") ORDER BY IFChannel ASC;";
		$rurl = $this->run_query($qurl);
		$numurl = @mysql_num_rows($rurl);
		//return array($rurl, $numurl);
///*		
		$urls = array();
		if ($numurl > 0) {
			$rurl = $this->run_query($qurl);
			while ($rowurl = @mysql_fetch_array($rurl)) {
				$ifchannel = $rowurl[1];
				$urls[$ifchannel] = new GenericTable();
				$urls[$ifchannel]->Initialize('TEST_IFSpectrum_urls',$rowurl[0],'keyId',40,'fkFacility');
				
			}
		}
		return array($urls, $numurl);//*/
	}

	public function qnf($TDHkeys) {
		$qnf = "SELECT IFSpectrum_SubHeader.fkNoiseFloorHeader, IFSpectrum_SubHeader.Band
                    FROM TestData_header, IFSpectrum_SubHeader
                    WHERE TestData_header.keyId = " . $TDHkeys[0] .
                   " AND TestData_header.keyFacility = IFSpectrum_SubHeader.keyFacility
                    AND TestData_header.keyId = IFSpectrum_SubHeader.fkHeader LIMIT 1";
			
		$rnf = $this->run_query($qnf); // @mysql_query($qnf,$this->dbConnection);
		
		$NoiseFloor = new GenericTable();
		$NoiseFloor->Initialize('TEST_IFSpectrum_NoiseFloor_Header',@mysql_result($rnf,0,0),'keyId');
		$NoiseFloorHeader = $NoiseFloor->keyId;

		return array($NoiseFloor, $NoiseFloorHeader);
	}

	public function qTDH($Band, $FEid, $DataSetGroup) {
		require(site_get_config_main());
		$qTDH = "SELECT TestData_header.keyId, TestData_header.TS
		FROM TestData_header, FE_Config
		WHERE TestData_header.DataSetGroup = $DataSetGroup
		AND TestData_header.fkTestData_Type = 7
		AND TestData_header.Band = " . $Band . "
		AND TestData_header.fkFE_Config = FE_Config.keyFEConfig
		AND FE_Config.fkFront_Ends = $FEid
		ORDER BY TestData_header.keyId ASC";

		$rTDH = $this->run_query($qTDH);

		$TDHkeys = array();
		$TS = 0;
		$count = 0;
		while($rowTDH = @mysql_fetch_Array($rTDH)) {
			$this->TDHkeys[$count] = $rowTDH['keyId'];
			$this->TS = $rowTDH['TS'];
			$count += 1;
		}

		return array($this->TDHkeys, $this->TS);

	}
}
?>