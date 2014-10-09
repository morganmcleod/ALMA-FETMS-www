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

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
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
		$r = $this->qkeys($Band, $IFChannel, $FEid, $DataSetGroup); // Gets Test Data Header keys

		$offset = 0;
		$data = array();
		while ($row = @mysql_fetch_array($r)) {
			$FreqLO = $row[1]; // LO Frequency
			$TDHkey = $row[2];
			// Gets IF frequency and Power data from database
			$qdata = "SELECT Freq_Hz/1000000000,(Power_dBm + $offset)
				FROM TEMP_IFSpectrum WHERE fkSubHeader = $row[0]
				AND Freq_Hz > 12000000
				ORDER BY Freq_Hz ASC;";
			$rdata = $this->run_query($qdata);

			while($rowdata = @mysql_fetch_array($rdata)) {
				$Freq_Hz = $rowdata[0]; // IF frequency
				$pow = $rowdata[1]; // Power
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

		$fmin = $specs['fWindow_Low'] * pow (10, 9);
		$fmax = $specs['fWindow_high'] * pow(10, 9);

		$rkeys = $this->qkeys($Band, $IFChannel, $FEid, $DataSetGroup); // Test Data Header Keys

		$maxvar = -999;
		while($rowkeys = @mysql_fetch_array($rkeys)) {
			// Gets IF frequency and power data for window size $fwin (2 * pow(10, 9) or 31 * pow(10, 6))
			$qvar = "SELECT Freq_Hz, Power_dBm FROM TEMP_TEST_IFSpectrum_PowerVar
					WHERE WindowSize_Hz = $fwin
					AND fkSubHeader = $rowkeys[0]
					ORDER BY Freq_Hz ASC;";

			$rvar = $this->run_query($qvar);

			while($rowvar = @mysql_fetch_array($rvar)) {
				$freq = $rowvar[0] / pow(10, 9); // IF frequency
				$pow = $rowvar[1]; // power

				if ($pow > $maxvar) {
					$maxvar = $pow;
				}

				if ($Band == 6 && $fwin == 2 * pow(10,9)) {
					if ($freq < 7) {
						$pow = "-1";
					}
				}

				if($pow != -1) {
					$d = array('FreqLO' => $rowkeys[1], 'Freq_Hz' => $freq, 'Power_dBm' => $pow);
					$data[] = $d;
				}
			}
		}
		return array($data, $maxvar);

	}

	/**
	 * Returns power variation between 5 and 6 GHz for band 6 case.
	 *
	 * @param int $Band
	 * @param int $IFChannel
	 * @param int $FEid
	 * @param int $DataSetGroup
	 * @return array- Power variation values for band 6 case.
	 */
	public function q6 ($Band, $IFChannel, $FEid, $DataSetGroup) {
		$r = $this->qkeys($Band, $IFChannel, $FEid, $DataSetGroup);

		$b6points = array();
		$maxvar = -999;
		while($row = @mysql_fetch_array($r)) {
			$q6 = "SELECT MAX(Power_dBm), MIN(Power_dBm)
					FROM IFSpectrum WHERE fkSubHeader = $row[0]
					AND Freq_Hz < 6000000000 AND Freq_Hz > 5000000000;";

			$r6 = $this->run_query($q6);
			$b6val = @mysql_result($r6, 0, 0) - @mysql_result($r6, 0, 1);
			if ($b6val != 0) {
				$b6points[] = $b6val;
				if ($b6val > $maxvar) {
					$maxvar = $b6val;
				}
			}
			$fwin = 2 * pow(10, 9);
			$qdata = "SELECT Power_dBm, Freq_Hz FROM TEMP_TEST_IFSpectrum_PowerVar
						WHERE fkSubHeader = $row[0]
						AND WindowSize_Hz = $fwin
						ORDER BY Freq_Hz ASC;";
			$rdata = $this->run_query($qdata);
			while ($rowdata = @mysql_fetch_array($rdata)) {
				$pval = $rowdata[0];
				$fval = $rowdata[1] * pow(10, -9);
				if ($fval < 6) {
					$pval = "-1";
				}
				if ($fval >= 7 && $fval <= 9) {
					if ($pval > $maxvar) {
						$maxvar = $pval;
					}
				}
			}
		}

		return array($b6points, $maxvar);
	}

	/**
	 * Finds data for total and in-band power table.
	 *
	 * @param int $DataSetGroup
	 * @param int $Band
	 * @param int $FEid
	 * @param int $if
	 * @return 2d array- columns are LO, IF, power.
	 */
	public function qPowTot($DataSetGroup, $Band, $FEid, $if) {
		$new_specs = new Specifications();
		$specs = $new_specs->getSpecs('ifspectrum', $Band);

		$tdh = $this->qtdh($DataSetGroup, $Band, $FEid);

		$select0 = 'IFSpectrum_SubHeader.FreqLO, ROUND(TEST_IFSpectrum_TotalPower.InBandPower,1)';
		$from0 = 'IFSpectrum_SubHeader, TEST_IFSpectrum_TotalPower';
		$where0 = 'TEST_IFSpectrum_TotalPower.fkSubHeader = IFSpectrum_SubHeader.keyId and IFSpectrum_SubHeader.IsIncluded = 1';
		$select15 = 'ROUND(TEST_IFSpectrum_TotalPower.InBandPower,1), ROUND(TEST_IFSpectrum_TotalPower.TotalPower,1)';
		$from15 = 'IFSpectrum_SubHeader, TEST_IFSpectrum_TotalPower';
		$where15 = 'TEST_IFSpectrum_TotalPower.fkSubHeader = IFSpectrum_SubHeader.keyId and IFSpectrum_SubHeader.IsIncluded = 1';
		$data = array();
		$rlo = $this->qlo($tdh, $if);
		while ($row = @mysql_fetch_array($rlo)) {
			$lo = $row[0];
			if ($lo > 0) {
				$d = array('FreqLO' => $lo);
				$pwr0 = round($this->qpow($tdh, $select0, $from0, $where0, 0, 1, $if, $lo), 1);
				$pwr15 = round($this->qpow($tdh, $select15, $from15, $where15, 15, 0, $if, $lo), 1);
				$pwrT = round($this->qpow($tdh, $select15, $from15, $where15, 15, 1, $if, $lo), 1);
				$pwrdiff = $pwrT - $pwr15;

				$pwr0 = number_format($pwr0, 1, '.', '');
				$pwr15 = number_format($pwr15, 1, '.', '');
				$pwrT = number_format($pwrT, 1, '.', '');
				$pwrdiff = number_format($pwrdiff, 1, '.', '');

				$diff = abs($pwr0 - $pwr15);

				$red = FALSE;
				if ($diff < 14 || $diff > 16) {
					$red = TRUE;
				}

				$tstr = "";
				if ($red) {
					$tstr .= "<span>";
				}
				if ($pwr0 > -22) {
					$tstr .= "<font color='#FF0000'>$pwr0</font>";
				} else {
					$tstr .= "<font color='#000000'>$pwr0</font>";
				}
				if ($red) {
					$tstr .= "</span>";
				}

				$d['pwr0'] = $tstr;

				$tstr = "";
				if ($red) {
					$tstr .= "<span>";
				}
				if ($pwr15 < -22) {
					$tstr .= "<font color='#FF0000'>$pwr15</font>";
				} else {
					$tstr .= "<font color='#000000'>$pwr15</font>";
				}
				if ($red) {
					$tstr .= "</span>";
				}
				$d['pwr15'] = $tstr;

				$d['pwrT'] = "<b>$pwrT</b>";

				if ($pwrdiff > 3) {
					$d['pwrdiff'] = "<font color = '#ff0000'><b>$pwrdiff</b></font>";
				} else {
					$d['pwrdiff'] = "<font color = '#000000'><b>$pwrdiff</b></font>";
				}

				$data[] = $d;
			}
		}
		return $data;
	}

	/**
	 * Finds data for power variation tables.
	 *
	 * @param int $DataSetGroup
	 * @param int $Band
	 * @param int $FEid
	 *
	 * @return 2d array- columns are LO, in-band power for 0 and 15 gain, total power and power diff
	 * formatted to be placed in table.
	 */
	public function qPowVar($DataSetGroup, $Band, $FEid) {
		$new_specs = new Specifications();
		$specs = $new_specs->getSpecs('ifspectrum', $Band);

		$tdh = $this->qtdh($DataSetGroup, $Band, $FEid);

		$select = "TEST_IFSpectrum_PowerVarFullBand.Power_dBm";
		$from = "IFSpectrum_SubHeader, TEST_IFSpectrum_PowerVarFullBand";
		$where = "IFSpectrum_SubHeader.IsPAI = 1
							and TEST_IFSpectrum_PowerVarFullBand.fkSubHeader = IFSpectrum_SubHeader.keyId";
		$data = array();
		$rlo = $this->qlo($tdh);
		while ($row = @mysql_fetch_array($rlo)) {
			$lo = $row[0];
			if ($lo > 80) {
				$max = $specs['maxch'];

				for($if=0; $if<=$max; $if++) {
					$pwr = round($this->qpow($tdh, $select, $from, $where, 15, 0, $if, $lo), 1);
					$pwr = number_format($pwr, 1, '.', '');

					$temp = $specs['pwr'];
					if ($pwr >= $temp) {
						$fontcolor = $specs["fontcolor$temp"];
					} else {
						$fontcolor = $specs['fontcolor'];
					}

					$tstr = "<font color = $fontcolor><b>$pwr</b></font>";

					$data[] = array('FreqLO' => $lo, 'IF' => $if, 'value' => $tstr);
				}
			}
		}
		return $data;
	}

	/**
	 * Helper function that returns power values for tables.
	 *
	 * @param array $TDHkeys
	 * @param string $select- Values to select
	 * @param string $from- Table to select from
	 * @param string $where- Query parameters
	 * @param int $num- Gain
	 * @param int $total- field to be retrieved
	 * @param int $ifchannel
	 * @param int $lo
	 *
	 * @return float- power value
	 */
	public function qpow($TDHkeys, $select, $from, $where, $num, $total, $ifchannel, $lo) {
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
	 * Helper functin that finds LO values given TDH keys and IF Channel (optional).
	 *
	 * @param unknown $TDHkeys
	 * @param string $ifchan
	 * @param number $ifchannel
	 */
	public function qlo($TDHkeys, $ifchannel = -1) {
		$qlo = "SELECT DISTINCT(FreqLO) FROM IFSpectrum_SubHeader
                WHERE ((fkHeader = " . $TDHkeys[0] . ")";

		for ($iTDH=1; $iTDH < count($TDHkeys); $iTDH++) {
			$qlo .= " OR (fkHeader =  " . $TDHkeys[$iTDH] . ") ";
		}

		$qlo .= ")";

		if ($ifchannel >= 0) {
			$qlo .=  " AND IFChannel = $ifchannel";
		}
		$qlo .= " AND IsIncluded = 1 ORDER BY FreqLO ASC;";

		return $this->run_query($qlo);
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

		$tdh = $this->qtdh($DataSetGroup, $Band, $FEid);

		$keyIds = array();
		$qkeyid = "SELECT keyId FROM IFSpectrum_SubHeader
					WHERE (fkHeader = $tdh[0] ";
		for ($i=0; $i<count($tdh); $i++) {
			$qkeyid .= "OR fkHeader = $tdh[$i] ";
		}
		$qkeyid .= ") AND IsIncluded = 1
					ORDER BY IFChannel ASC, FreqLO ASC";
		$rkeyid = $this->run_query($qkeyid);
		while($rowkeyid = @mysql_fetch_array($rkeyid)) {
			$keyIds[] = $rowkeyid[0];
		}//*/

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
				$tmin = abs($rowpow[1] - $fmin);
				$tmax = abs($rowpow[1] - $fmax);
				$tlo = abs($rowpow[1] - $flo);
				$thi = abs($rowpow[1] - $fhi);

				if($tmin < $min) {
					$min = $tmin;
					$imin = $index;
				}


				if ($tmax < $max) {
					$max = $tmax;
					$imax = $index;
				}


				if ($tlo < $lo) {
					$lo = $tlo;
					$ilo = $index - 1;
				}

				if ($thi < $hi) {
					$hi = $thi;
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
	 * Helper function that finds keyIds.
	 *
	 * @param int $Band
	 * @param int $IFChannel
	 * @param int $FEid
	 * @param int $DataSetGroup
	 *
	 * @return resource- Resource to query results.
	 */
	public function qkeys($Band, $IFChannel, $FEid, $DataSetGroup) {
		$q = "SELECT IFSpectrum_SubHeader.keyId, IFSpectrum_SubHeader.FreqLO, TestData_header.keyId
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
	 * Helper function that finds TDH Keys
	 * @param int $DataSetGroup
	 * @param int $Band
	 * @param int $FEid
	 *
	 * @return array- Test Data Header keys
	 */
	public function qtdh($DataSetGroup, $Band, $FEid, $ts = FALSE) {
		$q = "SELECT TestData_header.keyId, TestData_header.TS
		FROM TestData_header, FE_Config
		WHERE TestData_header.DataSetGroup = $DataSetGroup
		AND TestData_header.fkTestData_Type = 7
		AND TestData_header.Band = $Band
		AND TestData_header.fkFE_Config = FE_Config.keyFEConfig
		AND FE_Config.fkFront_Ends = $FEid
		ORDER BY TestData_header.keyId ASC";

		$r = $this->run_query($q);

		$tdh = array();
		$TS = 0;
		while ($row = @mysql_fetch_array($r)) {
			$tdh[] = $row[0];
			$TS = $row[1];
		}
		if ($ts) {
			return array($tdh, $TS);
		} else {
			return $tdh;
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