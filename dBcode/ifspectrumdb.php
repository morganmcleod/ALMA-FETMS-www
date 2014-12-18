<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.logger.php');
require_once($site_FEConfig . '/HelperFunctions.php');
require_once($site_dbConnect);
require_once($site_classes . '/class.spec_functions.php');

class IFSpectrumDB {
	var $DataSetBand;
	var $DataSetGroup;
	var $FEid;

	var $dbConnection;
	var $FacilityCode;

	/**
	 * Initialized class and creates database connection
	 *
	 * @param $db- existing database connection
	 */
	public function IFSpectrumDB($db) {
		require(site_get_config_main());
		$this->dbConnection = $db;
	}

	/**
	 * Runs a query
	 * @param string $query- SQL query
	 *
	 * @return Resource ID for query
	 */
	public function run_query($query) {
		return @mysql_query($query, $this->dbConnection);
	}

	/**
	 *
	 * @param array $TDHkeys
	 * @param string $select- values desired
	 * @param string $from- Table to find values
	 * @param string $where- parameters for query
	 * @param integer $num- Gain
	 * @param integer $total- field to be retrieved
	 * @param integer $ifchannel
	 * @param integer $lo
	 * @return float- pwr value
	 */
	public function q_num($TDHkeys, $select, $from, $where, $num, $total, $ifchannel, $lo) {
		$q = "select $select from $from where IFSpectrum_SubHeader.FreqLO =$lo and IFSpectrum_SubHeader.IFGain = {$num} and $where and IFSpectrum_SubHeader.IFChannel =  $ifchannel and ((IFSpectrum_SubHeader.fkHeader =  " . $TDHkeys[0] . ") ";

		for($iTDH=1; $iTDH<count($TDHkeys); $iTDH++) {
			$q .= "OR (IFSpectrum_SubHeader.fkHeader = " . $TDHkeys[$iTDH] . ") ";
		}
		$q .= ");";
		$r = $this->run_query($q);
		$pwr = @mysql_result($r,0,$total);
		return $pwr;
	}

	/**
	 *
	 * @param unknown $Band
	 * @param unknown $IFChannel
	 * @param unknown $FEid
	 * @param unknown $DataSetGroup
	 * @return resource
	 */
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

	/**
	 *
	 * @param array $TDHkeys
	 * @param boolean $ifchan- If IF Channel is desired in query (default = FALSE)
	 * @param number $ifchannel (default = 0)
	 * @return resource
	 */
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
	}

	/**
	 *
	 * @param integer $DataSetGroup
	 * @param integer $DataSetBand
	 * @param integer $keyId
	 * @return array- IF Sub Keys
	 */
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
			$temp = $ifsubkeys[$tempcount];
			$tempcount += 1;
		}

		return $ifsubkeys;
	}

	/**
	 *
	 * @param boolean $powervar- If power variation is desired
	 * @param array $row- previous query
	 * @param integer $offset- Power_dBm offest (defaul = NULL)
	 * @param integer $windowSize- window size (default = NULL)
	 * @return resource
	 */
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

	/**
	 *
	 * @param string $request- query desired
	 * @param array $ifsubkeys (default = NULL)
	 * @param integer $DataSetBand (default = NULL)
	 * @param integer $IFChannel (default = NULL)
	 * @param integer $FEid (default = NULL)
	 * @param integer $DataSetGroup (default = NULL)
	 * @param array $TDHkeys (default = NULL)
	 * @param integer $IFGain (default = NULL)
	 * @param integer $keyTDH (default = NULL)
	 * @param array $row (default = NULL)
	 * @param integer $windowSize (default = NULL)
	 * @return resource
	 */
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

	/**
	 *
	 * @param array $TDHkeys
	 * @return array- Generic Table for noise floor and keyId for NoiseFloorHeader
	 */
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

	/**
	 *
	 * @param array $TDHkeys
	 * @return array- GenericTable NoiseFloor and keyId NoiseFloorHeader
	 */
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

	/**
	 *
	 * @param integer $Band
	 * @param integer $FEid
	 * @param integer $DataSetGroup
	 * @return array TDHKeys and TS
	 */
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