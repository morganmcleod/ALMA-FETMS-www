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
		
		public function __construct() {}
		
		/*public function __destruct() {
			$this->dbPull->deleteTable();
		}*/
		
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
		
		public function deleteTempTable() {
			$this->dbPull->deleteTable();
		}
		
		public function getSpuriousData() {
			$data = $this->dbPull->qdata($this->Band, $this->IFChannel, $this->FEid, $this->DataSetGroup);
			$this->data = $data;
		}
	}
?>