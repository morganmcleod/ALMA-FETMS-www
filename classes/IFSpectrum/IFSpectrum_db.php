<?php
/**
 * ALMA - Atacama Large Millimeter Array
 * (c) Associated Universities Inc., 2014
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
 * @author Aaron Beaudoin, Morgan McLeod
 * Version 1.1 MTM 10/31/2014   Refactoring to make database independent from other classes
 * Version 1.0 ATB (07/30/2014)
 *
 */

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_libraries . '/array_column/src/array_column.php');
require_once($site_dbConnect);

class IFSpectrum_db {
    private $dbconnection;
    private $FEid;
    private $CCAid;
    private $band;
    private $dataSetGroup;
    private $lastTS;

    /**
     * Initializes IFSpectrum_db class
     */
    public function __construct() {
        require(site_get_config_main());
        $this->dbconnection = site_getDbConnection();
	    $this->FEid = $this->band = $this->dataSetGroup = $this->lastTS = 0;
    }

    /**
     * Returns resource to the given query's results
     * @param string $query
     */
    private function run_query($query) {
        return mysqli_query($this->dbconnection, $query);
    }

    /**
    * Creates temporary table TEMP_IFSpectrum on the database server for use in further queries.
    *  Contains all the raw spectrum data for the given $FEid, $band, and $dataSetGroup.
    *
    * @param integer $FEid
    * @param integer $band
    * @param integer $dataSetGroup
    * @param integer $CCAid
    */
    public function createTemporaryTable($FEid, $band, $dataSetGroup, $CCAid = 0) {
        $q = "DROP TEMPORARY TABLE IF EXISTS TEMP_IFSpectrum;";
        $this->run_query($q);

        $feConfig = 0;

        $qcreate = "CREATE TEMPORARY TABLE TEMP_IFSpectrum (
            fkSubHeader INT,
            Freq_Hz DOUBLE,
            Power_dBm DOUBLE,
            INDEX (fkSubHeader)) ";

        $qcreate .= "SELECT IFSpectrum.fkSubHeader, IFSpectrum.Freq_Hz, IFSpectrum.Power_dBm
            FROM TestData_header, IFSpectrum_SubHeader LEFT JOIN IFSpectrum
            ON IFSpectrum_SubHeader.keyId = IFSpectrum.fkSubHeader
            AND IFSpectrum_SubHeader.keyFacility = IFSpectrum.fkFacility
            WHERE TestData_header.keyId = IFSpectrum_SubHeader.fkHeader
            AND TestData_header.keyFacility = IFSpectrum_SubHeader.keyFacility
            AND TestData_header.Band = $band
            AND TestData_header.fkTestData_Type = 7
            AND IFSpectrum_SubHeader.IsIncluded = 1
            AND TestData_header.DataSetGroup = $dataSetGroup";

        if ($FEid) {
            $qcreate .= " AND TestData_header.fkFE_Config in
                (SELECT keyFEConfig from FE_Config WHERE fkFront_Ends = $FEid);";

        } else if ($CCAid) {
            $qcreate .= " AND TestData_header.fkFE_Components = $CCAid;";

        } else
            return false;

        if ($this->run_query($qcreate)) {
            $this->FEid = $FEid;
            $this->CCAid = $CCAid;
            $this->band = $band;
            $this->dataSetGroup = $dataSetGroup;
        }
        return true;
    }

    /**
	 * Deletes the temporary table TEMP_IFSpectrum
	 */
	public function deleteTemporaryTable() {
	    $this->FEid = $this->band = $this->dataSetGroup = 0;
		$qdel = "DROP TEMPORARY TABLE IF EXISTS TEMP_IFSpectrum;";
		$this->run_query($qdel);
	}

	/**
	 * Helper function to fetch a IFSpectrum_SubHeader and TestData_header info
	 *  for the given $ifChannel, $ifGain.
	 *  Uses the $FEid, $band, and $dataSetGroup previously passed to createTemporaryTable().
	 *
	 * @param integer $ifChannel
	 * @param integer $ifGain
	 * @return array(
	 *     [0] => array(
	 *         'LO_GHz' => float,
	 *         'keyIFS' => string,     // IFSpectrum_SubHeader.keyId
	 *         'keyTDH' => string      // TestData_header.keyId
	 *     )
	 *     [1] => array(...
	 * )
	 */
	private function getHeaderInfo($ifChannel, $ifGain = 15) {
	    $q = "SELECT IFSpectrum_SubHeader.FreqLO, IFSpectrum_SubHeader.keyId, TestData_header.keyId
	    FROM IFSpectrum_SubHeader, TestData_header
	    WHERE IFSpectrum_SubHeader.fkHeader = TestData_header.keyId
	    AND IFSpectrum_SubHeader.Band = $this->band
	    AND IFSpectrum_SubHeader.IFChannel = $ifChannel
	    AND IFSpectrum_SubHeader.IFGain = $ifGain
	    AND IFSpectrum_SubHeader.IsIncluded = 1
	    AND TestData_header.DataSetGroup = $this->dataSetGroup";

	    if ($this->FEid) {
	        $q .= " AND TestData_header.fkFE_Config in
	            (SELECT keyFEConfig from FE_Config WHERE fkFront_Ends = $this->FEid)";

	    } else if ($this->CCAid) {
	        $q .= " AND TestData_header.fkFE_Components = $this->CCAid";

	    } else {
	        return false;
	    }

	    $q .= " ORDER BY IFSpectrum_SubHeader.FreqLO ASC;";

	    $r = $this->run_query($q);

	    $output = array();
	    while ($row = mysqli_fetch_array($r)) {
	        $output[] = array(
	                'LO_GHz' => $row[0],
	                'keyIFS' => $row[1],
	                'keyTDH' => $row[2]
            );
	    }
	    return $output;
	}

	private function getSubHeaderKeys($headerInfo) {
	    $keyList = "";
	    foreach ($headerInfo as $row) {
	        if($keyList)
	            $keyList .= ", ";
	        $keyList .= $row['keyIFS'];
	    }
	    return $keyList;
	}

	/**
	 * Retrieve data for plots and analysis.
	 *
	 * @param integer $ifChannel
	 * @param integer $ifGain
     * @return structure suitable for IFSpectrum_calc:
     * array(
     *     [0] => array(
     *          'LO_GHz' => float,    // LO frequency
     *          'Freq_GHz' => float,   // Spectrum analyzer IF center
     *          'Power_dBm' => float  // Spectrum analyzer power measurement
     *     )
     *     [1] => array(...
     * )
	 */
	public function getSpectrumData($ifChannel, $ifGain = 15) {
	    // Get the subheader keys for the traces:
	    $headerInfo = $this->getHeaderInfo($ifChannel, $ifGain);
	    $keysList = $this->getSubHeaderKeys($headerInfo);

	    $q = "SELECT IFSpectrum_SubHeader.FreqLO as LO_GHz, (TEMP_IFSpectrum.Freq_Hz / 1.0E9) as Freq_GHz, TEMP_IFSpectrum.Power_dBm
	    FROM IFSpectrum_SubHeader, TEMP_IFSpectrum
	    WHERE TEMP_IFSpectrum.fkSubHeader = IFSpectrum_SubHeader.keyId
	    AND IFSpectrum_SubHeader.keyId in ($keysList)
	    ORDER BY LO_GHz ASC, Freq_GHz ASC;";

	    $r = $this->run_query($q);

	    $output = array();
	    $count = 0;
	    while ($row = mysqli_fetch_assoc($r)) {
	        $output[] = $row;
	        $count++;
	    }
	    return $output;
	}
	
	/**
	 * Retrieve noise floor data for total power calculation
	 *
	 * @param integer $keyNF
	 * @return structure suitable for IFSpectrum_calc:
	 * array(
	 *     [0] => array(
	 *          'Freq_GHz' => float,   // Spectrum analyzer IF center
	 *          'Power_dBm' => float  // Spectrum analyzer power measurement
	 *     )
	 *     [1] => array(...
	 * )
	 */
	public function getNoiseFloorData($keyNF) {
	    $q = "SELECT (Freq_Hz / 1.0E9) as Freq_GHz, Power_dBm FROM TEST_IFSpectrum_NoiseFloor
	    WHERE fkHeader = $keyNF ORDER BY Freq_Hz;";
	    $r = $this->run_query($q);
	    
	    $output = array();
	    $count = 0;
	    while ($row = mysqli_fetch_assoc($r)) {
	        $output[] = $row;
	        $count++;
	    }
	    return $output;
	}
	
	/**
	 * Helper function that finds TestData_Header keys for the given parameters.
	 * @param int $DataSetGroup
	 * @param int $Band
	 * @param int $FEid
	 *
	 * @return array of TDH keys
	 *
	 * Side-effect:  sets $this->lastTS to the timestamp header with the max keyID.
	 *  Retrieve it using getLastTS()
	 */
	public function getTestDataHeaderKeys($FEid, $band, $dataSetGroup) {
	    $q = "SELECT TestData_header.keyId, TestData_header.TS
	    FROM TestData_header, FE_Config
	    WHERE TestData_header.DataSetGroup = $dataSetGroup
	    AND TestData_header.fkTestData_Type = 7
	    AND TestData_header.Band = $band
	    AND TestData_header.fkFE_Config = FE_Config.keyFEConfig
	    AND FE_Config.fkFront_Ends = $FEid
	    ORDER BY TestData_header.keyId ASC";

	    $r = $this->run_query($q);

	    $tdh = array();
	    $TS = 0;
	    while ($row = mysqli_fetch_array($r)) {
    	    $tdh[] = $row[0];
    	    $this->lastTS = $row[1];
    	}
   	    return $tdh;
	}

	/**
	 * Helper function that finds TestData_Header keys for the given parameters.
	 * @param int $DataSetGroup
	 * @param int $Band
	 * @param int $componentId
	 *
	 * @return array of TDH keys
	 *
	 * Side-effect:  sets $this->lastTS to the timestamp header with the max keyID.
	 *  Retrieve it using getLastTS()
	 */
	public function getTestDataHeaderKeysForComp($componentId, $band, $dataSetGroup) {
	    $q = "SELECT TestData_header.keyId, TestData_header.TS
	    FROM TestData_header
	    WHERE TestData_header.DataSetGroup = $dataSetGroup
	    AND TestData_header.fkTestData_Type = 7
	    AND TestData_header.Band = $band
	    AND TestData_header.fkFE_Components = $componentId
	    ORDER BY TestData_header.keyId ASC";

	    $r = $this->run_query($q);

	    $tdh = array();
	    $TS = 0;
	    while ($row = mysqli_fetch_array($r)) {
	        $tdh[] = $row[0];
	        $this->lastTS = $row[1];
	    }
	    return $tdh;
	}

	/**
	 * Retrieve the newest timestamp seen in the last call to getTestDataHeaderKeys()
	 * @return string TS
	 */
	public function getLastTS() {
	    return $this->lastTS;
	}

	/**
	 * Load all the plot URLs connected to the given list of $TDHkeys
	 *
	 * @param array $TDHkeys
	 * @return array (
	 *     [0] => integer number of URLs objects in list,
	 *     [1] => array of GenericTable objects for the TEST_IFSpectrum_urls table
	 * )
	 */
	public function getPlotURLs($TDHkeys) {

	    $keysList = $this->makeKeysList($TDHkeys);

	    $q = "SELECT keyId, IFChannel FROM TEST_IFSpectrum_urls
	    WHERE TEST_IFSpectrum_urls.fkHeader in ($keysList) ORDER BY IFChannel ASC;";

	    $urls = array();
	    $r = $this->run_query($q);
	    $numurl = mysqli_num_rows($r);

	    while ($row = mysqli_fetch_array($r)) {
	        $ifchannel = $row[1];
	        $urls[$ifchannel] = new GenericTable();
	        $urls[$ifchannel] -> Initialize('TEST_IFSpectrum_urls', $row[0], 'keyId', 40, 'fkFacility');
	    }
	    return array($numurl, $urls);
	}

	/**
	 * Load the noise floor headers connected to the given $TDH key
	 *
	 * @param array $TDHkeys
	 * @return array (
	 *     [0] => keyId NoiseFloorHeader,
	 *     [1] => GenericTable TEST_IFSpectrum_NoiseFloor_Header
	 * )
	 */
	public function getNoiseFloorHeaders($TDH) {
	    $qnf = "SELECT IFSpectrum_SubHeader.fkNoiseFloorHeader, IFSpectrum_SubHeader.Band
	    FROM TestData_header, IFSpectrum_SubHeader
	    WHERE TestData_header.keyId = " . $TDH .
	    " AND TestData_header.keyFacility = IFSpectrum_SubHeader.keyFacility
	    AND TestData_header.keyId = IFSpectrum_SubHeader.fkHeader LIMIT 1";

	    $rnf = $this->run_query($qnf);
	    $keyNF = ADAPT_mysqli_result($rnf,0,0);

	    $NFHeader = new GenericTable();
	    $NFHeader -> Initialize('TEST_IFSpectrum_NoiseFloor_Header', $keyNF, 'keyId');
	    $keyNF = $NFHeader -> keyId;

	    return array($keyNF, $NFHeader);
	}

	/**
	 * Helper to make an array of comma-separated key values from an array.
	 *
	 * @param unknown_type $keysArray
	 */
	private function makeKeysList($keysArray) {
	    $output = "";
	    if ($keysArray) {
	        foreach ($keysArray as $key) {
	            if ($output)
	                $output .= ",";
	            $output .= $key;
	        }
	    }
	    return $output;
	}

	/**
	 * Load the data to display for the Power Variation Full Band table
	 *
	 * @param int $FEid
	 * @param int $band
	 * @param int $dataSetGroup
	 *
	 * @return data ordered by FreqLO:
	 * array(
	 *     [0] => array(
	 *         'FreqLO' => float,
	 *         'pVar_IF0' => float,
	 *         'pVar_IF1' => float,
	 *         'pVar_IF2' => float,
	 *         'pVar_IF3' => float
	 *     )
	 *     [1] => array...
	 * )
	 */
	public function getPowerVarFullBand($FEid, $band, $dataSetGroup, $CCAid = 0) {
	    if ($FEid)
	        $TDHkeys = $this->getTestDataHeaderKeys($FEid, $band, $dataSetGroup);
	    else if ($CCAid)
	        $TDHkeys = $this->getTestDataHeaderKeysForComp($CCAid, $band, $dataSetGroup);
	    else
	        return false;

	    $keysList = $this->makeKeysList($TDHkeys);

	    $q = "SELECT IFSpectrum_SubHeader.FreqLO, IFSpectrum_SubHeader.IFChannel,
	    TEST_IFSpectrum_PowerVarFullBand.Power_dBm
	    FROM IFSpectrum_SubHeader, TEST_IFSpectrum_PowerVarFullBand
	    WHERE IFSpectrum_SubHeader.fkHeader IN ($keysList)
	    AND TEST_IFSpectrum_PowerVarFullBand.fkSubHeader = IFSpectrum_SubHeader.keyId
	    AND IFSpectrum_SubHeader.IsIncluded = 1
	    AND IFSpectrum_SubHeader.IFGain = 15
	    ORDER BY FreqLO, IFChannel ASC;";

	    $output = array();

	    $PV0 = 0;    // IF channel 0 variation
	    $PV1 = 0;    // IF channel 1 variation
	    $PV2 = 0;    // IF channel 2 variation
	    $PV3 = 0;    // IF channel 3 variation
	    $outputRow = false;
	    $noLSB = ($band == 1 || $band == 9 || $band == 10);

		// loop on results sorted by LO, IFChannel
	    $r = $this->run_query($q);
	    while ($row = mysqli_fetch_array($r)) {
	        $LO = $row[0];
	        $IF = $row[1];

	        if ($IF == 0) {
	            $PV0 = $row[2];
	            $outputRow = false;	// row not yet finished
	        }
	        if ($IF == 1) {
	            $PV1 = $row[2];
	            $outputRow = $noLSB; // row finished for DSB/SSB bands
	        }
	        if ($IF == 2) {
	            $PV2 = $row[2];
	            $outputRow = false; // row not yet finished if we got here
	        }
	        if ($IF == 3) {
	            $PV3 = $row[2];
	            $outputRow = true;  // row finished for 2SB bands
	        }

	        if ($outputRow) {
	        	if ($noLSB)
	                $PV2 = $PV3 = 0;  // clear the IF2, IF3 values for DSB/2SB bands

	            $output[] = array(
	                    'FreqLO' => $LO,
	                    'pVar_IF0' => $PV0,
	                    'pVar_IF1' => $PV1,
	                    'pVar_IF2' => $PV2,
	                    'pVar_IF3' => $PV3
	            );
	            $PV0 = $PV1 = $PV2 = $PV3 = 0;
	        }
	    }
	    return $output;
	}


	/**
	 * Insert (and replace existing) full-band power variation data
	 *  for the specified ifChannel.
	 *
	 *  @param int $ifChannel
	 *  @param data array(
 	 *     [0] => array(
     *         'LO_GHz' => float,
     *         'pVar_dB' => float
     *     )
     *     [1] => array(...
     *  )
     */
	public function storePowerVarFullBand($ifChannel, $data) {
	    // Get the subheader keys and LO freqs for the traces:
	    $ifGain = 15;
	    $headerInfo = $this->getHeaderInfo($ifChannel, $ifGain);
	    $keysList = $this->getSubHeaderKeys($headerInfo);

	    // Delete previous data for the specified channel:
	    $q = "DELETE FROM TEST_IFSpectrum_PowerVarFullBand WHERE fkSubHeader IN ($keysList)";

	    if (!$this->run_query($q))
	        return false;

	    $q = "INSERT INTO TEST_IFSpectrum_PowerVarFullBand (fkSubHeader, Power_dBm) VALUES ";

	    $v = "";
	    foreach ($data as $row) {
	        $LO = $row['LO_GHz'];
	        $pVar = $row['pVar_dB'];
	        $i = array_search($LO, array_column($headerInfo, 'LO_GHz'));
	        if (!($i === FALSE)) {
	            $key = $headerInfo[$i]['keyIFS'];
	            if ($v)
	                $v .= ", ";
	            $v .= "($key, $pVar)";
	        }
	    }
	    $q .= $v . ";";
	    if (!$this->run_query($q))
	        return false;
	}

	/**
	 * Load the data to display for the Total and In-Band Power table
	 *
	 * @param int $FEid
	 * @param int $band
	 * @param int $dataSetGroup
	 * @param int $ifChannel
	 *
	 * @return data ordered by FreqLO:
	 * array(
	 *     [0] => array(
	 *         'FreqLO' => float,
	 *         'pwr0' => float,
	 *         'pwr15' => float,
	 *         'pwrT' => float,
	 *         'pwrDiff' => float
	 *     )
	 *     [1] => array...
	 * )
	 */
	public function getTotalAndInBandPower($FEid, $band, $dataSetGroup, $ifChannel, $CCAid = 0) {
	    if ($FEid)
	        $TDHkeys = $this->getTestDataHeaderKeys($FEid, $band, $dataSetGroup);
        else if ($CCAid)
            $TDHkeys = $this->getTestDataHeaderKeysForComp($CCAid, $band, $dataSetGroup);
        else
            return false;

	    $keysList = $this->makeKeysList($TDHkeys);

	    $q = "SELECT IFSpectrum_SubHeader.FreqLO, IFSpectrum_SubHeader.IFGain,
	    TEST_IFSpectrum_TotalPower.TotalPower, TEST_IFSpectrum_TotalPower.InBandPower
	    FROM IFSpectrum_SubHeader, TEST_IFSpectrum_TotalPower
	    WHERE IFSpectrum_SubHeader.fkHeader IN ($keysList)
	    AND TEST_IFSpectrum_TotalPower.fkSubHeader = IFSpectrum_SubHeader.keyId
	    AND IFSpectrum_SubHeader.IsIncluded = 1
	    AND IFSpectrum_SubHeader.IFChannel = $ifChannel
	    ORDER BY FreqLO, IFGain ASC;";

	    $output = array();

	    $PT0 = 0;    // total power with 0 dB gain
	    $PT15 = 0;   // total power 15 dB
	    $PI0 = 0;    // in-band power 0 dB
	    $PI15 = 0;   // in-band power 15 dB

	    $r = $this->run_query($q);
	    while ($row = mysqli_fetch_array($r)) {
	        $LO = $row[0];
	        $gain = $row[1];

	        if ($gain == 0) {
	            $PT0 = $row[2];
	            $PI0 = $row[3];

	        } elseif ($gain == 15) {
	            $PT15 = $row[2];
	            $PI15 = $row[3];

	            $output[] = array(
	                    'FreqLO' => $LO,
	                    'pwr0' => $PI0,
	                    'pwr15' => $PI15,
	                    'pwrT' => $PT15,
	                    'pwrDiff' => $PT15 - $PI15
	            );
	        }
	    }
	    return $output;
	}

	public function storeTotalAndInBandPower($ifChannel, $ifGain, $data) {
	    $headerInfo = $this->getHeaderInfo($ifChannel, $ifGain);
	    $keysList = $this->getSubHeaderKeys($headerInfo);

	    // Delete previous data for the specified channel:
	    $q = "DELETE FROM TEST_IFSpectrum_TotalPower WHERE fkSubHeader IN ($keysList)";

	    if (!$this->run_query($q))
	        return false;

	    $q = "INSERT INTO TEST_IFSpectrum_TotalPower (fkSubHeader, TotalPower, InBandPower) VALUES ";

	    $v = "";
	    foreach ($data as $row) {
	        $LO = $row['LO_GHz'];
	        $pTot = $row['pTotal_dBm'];
	        $pInB = $row['pInBand_dBm'];

	        $i = array_search($LO, array_column($headerInfo, 'LO_GHz'));
	        if (!($i === FALSE)) {
	            $key = $headerInfo[$i]['keyIFS'];
	            if ($v)
	                $v .= ", ";
	            $v .= "($key, $pTot, $pInB)";
	        }
	    }
	    $q .= $v . ";";
	    if (!$this->run_query($q))
	        return false;
	}

} // end class
?>