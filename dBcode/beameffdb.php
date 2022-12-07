<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.scansetdetails.php');
require_once($site_classes . '/class.scandetails.php');
require_once($site_classes . '/class.spec_functions.php');
require_once($site_dbConnect);

class BeamEffDB { //extends DBRetrieval {
    var $dbConnection;

    /**
     * Initializes class and creates database connection
     *
     * @param $db- existing database connection
     */
    public function __construct($db) {
        require(site_get_config_main());
        $this->dbConnection = $db;
    }
    /**
     * @param string $query- SQL query
     *
     * @return mysqli_result|bool Id for SQL query
     */
    public function run_query($query) {
        return mysqli_query($this->dbConnection, $query);
    }

    /**
     * @param integer $keyScanDetails
     * @param integer $fc (default = NULL)
     *
     * @return mysqli_result|bool
     */
    public function qdelete($keyScanDetails, $fc = NULL) {
        $q = "DELETE FROM BeamEfficiencies WHERE fkScanDetails = $keyScanDetails";
        if (!is_null($fc)) {
            $q .= " AND fkFacility = $fc;";
        }
        return $this->run_query($q);
    }

    public function qTDH($in_TDHId, $fc = NULL) {
        $q = "SELECT keyId FROM ScanSetDetails WHERE fkHeader = " . $in_TDHId;
        if (!is_null($fc)) {
            $q .= " AND fkFacility = $fc;";
        }
        return $this->run_query($q);
    }

    /**
     * @param string $request- s or n
     * @param integer $keyScanDetails (default = NULL)
     * @param integer $band (default = NULL)
     *
     * @return mysqli_result|bool
     */
    public function q_other($request, $keyScanDetails = NULL, $band = NULL) {
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
    /**
     * @param boolean $near- nearfield or farfield
     * @param string $path- $fh
     * @param integer $scan_id
     *
     * @return none
     */
    public function q($near, $path, $scan_id) {
        $handle = fopen($path, 'w');
        $q = '';
        if ($near) {
            $q = "SELECT x,y,amp,phase FROM BeamListings_nearfield WHERE fkScanDetails = $scan_id;";
        } else {
            $q = "SELECT x,y,amp,phase FROM BeamListings_farfield WHERE fkScanDetails = $scan_id;";
        }
        $r = $this->run_query($q);
        while ($row = mysqli_fetch_array($r)) {
            fwrite($handle, "$row[0]\t$row[1]\t$row[2]\t$row[3]\r\n");
        }
        fclose($handle);
    }
    /**
     * @param array $scansets
     *
     * @return integer- 0 or 1 stating if processed
     */
    public function qeff($scansets) {
        $qeff = "SELECT * FROM BeamEfficiencies
                 WHERE fkScanDetails = " . $scansets[0]->keyId_copol_pol0_scan . ";";

        $reff = $this->run_query($qeff);
        $numrows = mysqli_num_rows($reff);
        $processed = 0;
        if ($numrows > 0) {
            $processed = 1;
        }
        return $processed;
    }


    /**
     * @param integer $occur- occurance function is called
     * @param integer $fe_id (default = NULL)
     * @param integer $in_keyId (default = NULL)
     * @param integer $band (default = NULL)
     * @param integer $fc (default = NULL)
     * @param integer $scanSetId (default = NULL)
     *
     * @return mysqli_result|bool
     */
    public function qss($occur, $fe_id = NULL, $in_keyId = NULL, $band = NULL, $fc = NULL, $scanSetId = NULL) {
        $q = "";
        if ($occur == 1) {
            $q = "SELECT keyId FROM ScanSetDetails
			WHERE fkFE_Config = $fe_id
			ORDER BY band ASC, f ASC, tilt ASC, ScanSetNumber ASC;";
        } elseif ($occur == 2) {
            $q = "SELECT keyId, band, fkFE_Config
			FROM ScanSetDetails
			WHERE keyId = $in_keyId
			AND fkFacility = $fc;";
        } elseif ($occur == 3) {
            $q = "SELECT keyId
			FROM ScanSetDetails
			WHERE fkFE_Config = $fe_id
			AND band = $band
			AND fkFacility = $fc
			ORDER BY f ASC, tilt ASC, ScanSetNumber ASC;";
        } elseif ($occur == 4) {
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
