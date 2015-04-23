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
require_once($site_IF . '/IF_db.php');

class IFCalc {
	var $data;
	var $specs;
	var $db;
	var $dbPull;

	var $Band;
	var $IFChannel;
	var $FEid;
	var $DataSetGroup;
	var $version;
	var $maxvar;

	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Sets parameters of class
	 *
	 * @param int $Band
	 * @param int $IFChannel
	 * @param int $FEid
	 * @param int $DataSetGroup
	 */
	public function setParams ($Band, $IFChannel, $FEid, $DataSetGroup) {
		$this->Band = $Band;
		$this->IFChannel = $IFChannel;
		$this->FEid = $FEid;
		$this->DataSetGroup = $DataSetGroup;

		$newSpecs = new Specifications();
		$specs = $newSpecs->getSpecs('ifspectrum', $Band);
		$dbPull = new IF_db();
		$this->specs = $specs;
		$this->dbPull = $dbPull;

		require(site_get_config_main());
		$this->db = site_getDbConnection();

		$this->version = "1.0";

		//$this->dbPull->createTable($DataSetGroup, $Band, $FEid);
	}

	/**
	 * Creates temporary tables TEMP_IFSpectrum and TEMP_TEST_IFSpectrum_PowerVar
	 */
	public function createTables() {
		$this->dbPull->createTable($this->DataSetGroup, $this->Band, $this->FEid);
		$fwin = 31 * pow(10, 6);
		$this->dbPull->createPowVar($this->DataSetGroup, $this->Band, $this->FEid, $this->specs['fWindow_Low'] * pow(10, 9), $this->specs['fWindow_high'] * pow(10, 9), $fwin);
		$fwin = 2 * pow(10, 9);
		$this->dbPull->createPowVar($this->DataSetGroup, $this->Band, $this->FEid, $this->specs['fWindow_Low'] * pow(10, 9), $this->specs['fWindow_high'] * pow(10, 9), $fwin);
	}

	/**
	 * Gets power variation table data.
	 *
	 * @return array- Power variation data
	 */
	public function getPowVarData() {
		return $this->dbPull->qPowVar($this->DataSetGroup, $this->Band, $this->FEid);
	}

	/**
	 * Get total and in-band power data.
	 *
	 * @return array- Total and in-band power data.
	 */
	public function getTotPowData() {
		return $this->dbPull->qPowTot($this->DataSetGroup, $this->Band, $this->FEid, $this->IFChannel);
	}

	/**
	 * Gets spurious noise data
	 */
	public function getSpuriousData() {
		$data = $this->dbPull->qdata($this->Band, $this->IFChannel, $this->FEid, $this->DataSetGroup);
		$this->data = $data;
		//$this->dbPull->deleteTable();
	}

	/**
	 * Gets power variation data
	 * @param float $fwin- Window size
	 */
	public function getPowerData($fwin) {
		$temp = $this->dbPull->qpower($this->DataSetGroup, $this->IFChannel, $this->Band, $this->FEid, $fwin);
		$data = $temp[0];
		$this->maxvar = $temp[1];
		$this->data = $data;
	}

	/**
	 * Deletes temporary tables used to get spurious noise and power variation data.
	 * MUST BE CALLED!!!
	 */
	public function deleteTables() {
		$this->dbPull->deleteTable();
	}
}
?>
