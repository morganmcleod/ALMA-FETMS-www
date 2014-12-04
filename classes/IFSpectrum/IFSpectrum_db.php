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
require_once($site_dbConnect);

class IFSpectrum_db {
    private $db;

    /**
     * Initializes IFSpectrum_db class
     */
    public function __construct() {
        require(site_get_config_main());
        $this->db = site_getDbConnection();
    }

    /**
     * Returns resource to a given query
     * @param string $query
     */
    private function run_query($query) {
        return @mysql_query($query);
    }

    /**
    * Creates temporary table TEMP_IFSpectrum on the database server for use in further queries
    *
    * @param int $DataSetGroup
    * @param int $Band
    * @param int $FEid
    */
    public function createTable($DataSetGroup, $Band, $FEid) {
        $q = "DROP TEMPORARY TABLE IF EXISTS TEMP_IFSpectrum ;";
        $this->run_query($q);

        $qcreate = "CREATE TEMPORARY TABLE TEMP_IFSpectrum (
            fkSubHeader INT,
            fkFacility INT,
            Freq_Hz DOUBLE,
            Power_dBm DOUBLE,
            INDEX (fkSubHeader)) ";

        $qcreate .= "SELECT IFSpectrum.fkSubHeader, IFSpectrum.fkFacility, IFSpectrum.Freq_Hz, IFSpectrum.Power_dBm
            FROM FE_Config, TestData_header, IFSpectrum_SubHeader LEFT JOIN IFSpectrum
            ON IFSpectrum_SubHeader.keyId = IFSpectrum.fkSubHeader
            AND IFSpectrum_SubHeader.keyFacility = IFSpectrum.fkFacility
            WHERE TestData_header.fkFE_Config = FE_Config.keyFEConfig
            AND TestData_header.keyFacility = FE_Config.keyFacility
            AND TestData_header.keyId = IFSpectrum_SubHeader.fkHeader
            AND TestData_header.keyFacility = IFSpectrum_SubHeader.keyFacility
            AND FE_Config.fkFront_Ends = $FEid
            AND TestData_header.Band = $Band AND TestData_header.fkTestData_Type = 7
            AND IFSpectrum_SubHeader.IsIncluded = 1 AND TestData_header.DataSetGroup = $DataSetGroup ;";

        $this->run_query($qcreate);
    }

    /**
	 * Deletes temporary tables
	 */
	public function deleteTable() {
		$qdel = "DROP TABLE IF EXISTS TEMP_IFSpectrum;";
		$this->run_query($qdel);
	}

	/**
	 * Retrieve data for spurious plots for the given FE, band, IF channel, and group.
	 * Output power levels are offset for subsequent LOs by $offsetamount.
	 * TODO: move $offsetamount logic into IFSpectrum_impl or _calc.
	 * TODO: get the inner query out of the loop.
	 *
	 * @param int $Band
	 * @param int $IFChannel
	 * @param int $FEid
	 * @param int $DataSetGroup
	 * @param int $offsetamount
	 * @return 2d array- data for spurious noise, where columns are 'Freq_LO', 'Freq_Hz', and 'Power_dBm'
	 */
	public function getSpectrumData($Band, $IFChannel, $FEid, $DataSetGroup, $offsetamount = 10) {
	    // Get the subheader keys for the traces:
		$r = $this->getSubHeaderKeys($Band, $IFChannel, $FEid, $DataSetGroup);

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
	 * Helper function to fetch a subset of IFSpectrum_SubHeader for the given FE, band,
	 *  IF channel, group, and IFGain.
	 *
	 * @param int $Band
	 * @param int $IFChannel
	 * @param int $FEid
	 * @param int $DataSetGroup
	 * @param int $IFGain
	 *
	 * @return resource- Resource to query results.
	 */
	private function getSubHeaderKeys($Band, $IFChannel, $FEid, $DataSetGroup, $IFGain = 15) {
	    $q = "SELECT IFSpectrum_SubHeader.keyId, IFSpectrum_SubHeader.FreqLO, TestData_header.keyId
	    FROM IFSpectrum_SubHeader, TestData_header, FE_Config
	    WHERE IFSpectrum_SubHeader.fkHeader = TestData_header.keyId
	    AND TestData_header.fkFE_Config = FE_Config.keyFEConfig
	    AND IFSpectrum_SubHeader.Band = $Band
	    AND IFSpectrum_SubHeader.IFChannel = $IFChannel
	    AND IFSpectrum_SubHeader.IFGain = $IFGain
	    AND IFSpectrum_SubHeader.IsIncluded = 1
	    AND FE_Config.fkFront_Ends = $FEid
	    AND TestData_header.DataSetGroup = $DataSetGroup
	    ORDER BY IFSpectrum_SubHeader.FreqLO ASC;";

	    return $this->run_query($q);
	}

	/**
	 * Helper function that finds TestData_Header keys for the given parameters.
	 * @param int $DataSetGroup
	 * @param int $Band
	 * @param int $FEid
	 *
	 * @return array(
	 *     [1] => string TS of newest key found
	 *     [0] => array of TDK keys
	 * )
	 */
	public function getTestDataHeaderKeys($DataSetGroup, $Band, $FEid) {
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
   	    return array($TS, $tdh);
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
	    $qurl = "SELECT keyId, IFChannel FROM TEST_IFSpectrum_urls
	    WHERE TEST_IFSpectrum_urls.fkHeader in (";
	    for ($iTDH=0; $iTDH<count($TDHkeys); $iTDH++) {
	        if ($iTDH > 0)
	            $qurl .= ",";
	        $qurl .= $TDHkeys[$iTDH];
	    }
	    $qurl .= ") ORDER BY IFChannel ASC;";

	    $urls = array();
	    $rurl = $this->run_query($qurl);
	    $numurl = @mysql_num_rows($rurl);

	    while ($rowurl = @mysql_fetch_array($rurl)) {
	        $ifchannel = $rowurl[1];
	        $urls[$ifchannel] = new GenericTable();
	        $urls[$ifchannel] -> Initialize('TEST_IFSpectrum_urls', $rowurl[0], 'keyId', 40, 'fkFacility');
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
	    $keyNF = @mysql_result($rnf,0,0);

	    $NFHeader = new GenericTable();
	    $NFHeader -> Initialize('TEST_IFSpectrum_NoiseFloor_Header', $keyNF, 'keyId');
	    $keyNF = $NoiseFloor->keyId;

	    return array($keyNF, $NFHeader);
	}

	/**
	 * Load IF spectrum data for processing into plots:
	 */
	public function getSpectrumData($TDHkeys, $IFChannel, $IFGain) {
	    $q = "SELECT


	`fkSubHeader` INT(20) UNSIGNED NOT NULL DEFAULT '0',
	`fkFacility` INT(11) NOT NULL DEFAULT '40',
	`Freq_Hz` BIGINT(20) NOT NULL DEFAULT '0',
	`Power_dBm`
	}

}
?>