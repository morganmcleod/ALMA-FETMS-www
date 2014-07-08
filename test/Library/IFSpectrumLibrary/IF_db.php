<?php
require_once(dirname(__FILE__) . '/../../../SiteConfig.php');
require_once($site_dbConnect);
require_once($site_classes . '/class.spec_functions.php');

class IF_db {
	var $db;
	
	public function IF_db() {
		require(site_get_config_main());
		$this->db = site_getDbConnection();
	}
	
	public function run_query($query) {
		return @mysql_query($query);
	}
	
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
		$this->deleteTable();
	}
	
	public function qpower($Band, $IFChannel, $FEid, $DataSetGroup, $wsize) {
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
		
		$b6 = ($Band == 6);
		$b6points = array();
		$maxpowervar6 = -999;
		
		$r = $this->run_query($q);
		$maxpowervar = -999;
		
		$count = 0;
		$data_file = array();
		$FreqLO = array();
		
		while ($row = @mysql_fetch_array($r)) {
			$qmax = "SELECT MAX(Power_dBm) FROM TEMP_TEST_IFSpectrum_PowerVar WHERE fkSubHeader = $row[0] AND WindowSize_Hz = $wsize;";
			$rmax = $this->run_query($qmax);
			$temp = round(@mysql_result($rmax, 0, 0), 2);
			
			if ($temp > $maxpowervar) {
				$maxpowervar = $temp;
			}
			
			$FreqLO[$count] = $row[1];
		}
	}
	
	public function createTable($DataSetGroup, $Band, $FEid) {
		$q = "CREATE TEMPORARY TABLE IF NOT EXISTS TEMP_IFSpectrum (
				 fkSubHeader INT,
				 fkFacility INT,
				 Freq_Hz DOUBLE,
				 Power_dBm DOUBLE,
				 INDEX (fkSubHeader)
				 );";
		$this->run_query($q, $this->db);
			
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
			
		$q = "INSERT INTO TEMP_IFSpectrum
		SELECT IFSpectrum.fkSubHeader,IFSpectrum.fkFacility,IFSpectrum.Freq_Hz,
		IFSpectrum.Power_dBm
		FROM IFSpectrum WHERE (IFSpectrum.fkSubHeader = $ifsubkeys[0]) ";
		
		for ($i=1;$i<count($ifsubkeys);$i++){
		$q .= " OR (IFSpectrum.fkSubHeader = $ifsubkeys[$i]) ";
		}
		$q .= ";";
			
		$this->run_query($q, $this->db);
	}
	
	public function deleteTable() {
		$q = "DROP TABLE TEMP_IFSpectrum;";
		$this->run_query($q);
	}
}
?>