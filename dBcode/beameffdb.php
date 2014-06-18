<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.scansetdetails.php');
require_once($site_classes . '/class.scandetails.php');
require_once($site_classes . '/class.spec_functions.php');
require_once($site_dbConnect);

class BeamEffDB { //extends DBRetrieval {
	var $dbConnection;
	
	public function BeamEffDB($db) {
		require(site_get_config_main());
		$this->dbConnection = $db;
	}
	
	public function run_query($query) {
		return @mysql_query($query, $this->dbConnection);
	}
	
	public function qdelete($keyScanDetails, $fc=NULL) {
		$q = "DELETE FROM BeamEfficiencies WHERE fkScanDetails = $keyScanDetails";
		if(!is_null($fc)) {
			$q .= "AND fkFacility = $this->fc;";
		}
		return $this->run_query($q);
	}
	
	public function q_other($request, $keyScanDetails=NULL, $band=NULL) {
		$q = '';
		if ($request == 's') {
			$q = "Select keyBeamEfficiencies FROM BeamEfficiencies WHERE fkScanDetails = $keyScanDetails;";
		} elseif ($request == 'n') {
			$q = "SELECT AZ, EL FROM NominalAngles WHERE Band = $band;";
		} else {
			$q = '';
		}
		
		return $this->run_query($q);
	}
	
	public function q($near, $path, $scan_id) {
		$handle = fopen($path,'w');
		$q = '';
		if($near) {
			$q = "SELECT x,y,amp,phase FROM BeamListings_nearfield WHERE fkScanDetails = $scan_id;";
		} else {
			$q = "SELECT x,y,amp,phase FROM BeamListings_farfield WHERE fkScanDetails = $scan_id;";
		}
		$r = $this->run_query($q);
		while ($row = @mysql_fetch_array($r)) {
			fwrite($handle,"$row[0]\t$row[1]\t$row[2]\t$row[3]\r\n");
		}
		fclose($handle);
		
	}
	
	public function qeff($scansets) {
		$qeff = "SELECT * FROM BeamEfficiencies
                 WHERE fkScanDetails = ". $scansets[0]->keyId_copol_pol0_scan . ";";
		
		$reff = $this->run_query($qeff);
		$numrows = @mysql_numrows($reff);
		$processed = 0;
		if ($numrows > 0) {
			$proccessed = 1;
		}
		return $proccessed;
	}
	
	
	
	public function qss($occur, $fe_id=NULL, $in_keyId=NULL, $band=NULL, $fc=NULL, $scanSetId=NULL) {
		$q = "";
		if($occur==1) {
			$q = "SELECT keyId FROM ScanSetDetails 
			WHERE fkFE_Config = $fe_id 
			ORDER BY band ASC, f ASC, tilt ASC, ScanSetNumber ASC;";
		} elseif($occur==2) {
			$q = "SELECT keyId, band, fkFE_Config 
			FROM ScanSetDetails 
			WHERE keyId = $in_keyId 
			AND fkFacility = $fc;";
		} elseif($occur==3) {
			$q = "SELECT keyId 
			FROM ScanSetDetails 
			WHERE fkFE_Config = $fe_id 
			AND band = $band 
			AND fkFacility = $fc 
			ORDER BY f ASC, tilt ASC, ScanSetNumber ASC;";
		} elseif($occur==4) {
			$q = "SELECT sb FROM ScanDetails 
			WHERE fkScanSetDetails = $scanSetId 
			AND SourcePosition = 1 
			AND copol = 1 
			AND fkFacility = $fc 
			LIMIT 1;";
		} else {
			$q = '';
		}
		
		return $this->run_query($q);
	}
}
?>