<?php
require_once(dirname(__FILE__) . '/../../../SiteConfig.php');
require_once($site_dbConnect);
require_once($site_classes . '/class.spec_functions.php');
require_once($site_IF . '/IF_db.php');

	interface ifspectrum {
		
	}
	
	class IFCalc {
		var $data;
		var $specs;
		var $db;
		var $dbPull;
		
		var $Band;
		var $IFChannel;
		var $FEid;
		var $DataSetGroup;
		
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
			$data = $this->dbPull->qpower($this->DataSetGroup, $this->IFChannel, $this->Band, $this->FEid, $fwin);
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