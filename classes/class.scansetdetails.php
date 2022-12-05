<?php
// Reads/modifies a ScanSetDetails and its corresponding TestDataHeader in the FEIC database.
//
// TODO: possibly obsolete code in:
// GenerateAllPlots()
// -- is handled by the beameff_64 C program now.
// DisplayData_ScanSetDetails()
// Display_add_form()
// -- not called by any other code.
//

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.scandetails.php');
require_once($site_classes . '/class.testdata_header.php');


class ScanSetDetails extends GenericTable {
    // ScanSetDetails columns
    public $keyId;
    public $fkFacility;
    public $fkFE_Config;
    public $fkFrontEnd;
    public $fkHeader;
    public $f;
    public $band;
    public $tilt;
    public $notes;
    public $TS;
    public $ScanSetNumber;
    public $is_deleted;
    public $ready_to_process;
    public $software_version_vbscript;
    public $software_version_labviewvi;

    public $keyId_copol_pol0_scan;
    public $keyId_xpol_pol0_scan;
    public $keyId_copol_pol1_scan;
    public $keyId_xpol_pol1_scan;
    public $keyId_180_scan;

    public $Scan_copol_pol0;
    public $Scan_xpol_pol0;
    public $Scan_copol_pol1;
    public $Scan_xpol_pol1;
    public $Scan_180;
    public $fc; //facility key
    public $tdh;  //TestData_header record (class.generictable.php)

    public function __construct($keyId, $inFc = 40) {
        $this->fc = $inFc;
        parent::__construct("ScanSetDetails", $keyId, "keyId", $this->fc, 'fkFacility');
        $this->tdh = new TestData_header($this->fkHeader, $this->fkFacility);
    }

    public static function getIdFromHeader($fkHeader) {
        $dbConnection = site_getDbConnection();
        $q = "SELECT keyId FROM ScanSetDetails WHERE fkHeader = {$fkHeader};";
        $r = mysqli_query($dbConnection, $q);
        return ADAPT_mysqli_result($r, 0, 0);
    }

    public function requestValuesScanSetDetails() {
        if (isset($_REQUEST['notes'])) {
            $this->SetValue('notes', $_REQUEST['notes']);
            $this->Update();
            $this->tdh->SetValue('Notes', $_REQUEST['notes']);
            $this->tdh->Update();
        }

        foreach ($this->columnNames as &$column) {
            if (isset($_REQUEST[$column])) {
                $this->SetValue($column, $_REQUEST[$column]);
            }
        }
        if (isset($_REQUEST['deleterecord'])) {
            $this->Display_delete_form_SSD();
        }
        if (isset($_REQUEST['deleterecord_forsure'])) {
            $this->SetValue('is_deleted', '1');
            $this->Update();
            echo '<meta http-equiv="Refresh" content="1;url=bplist.php?fc=$this->fc&keyconfig=' . $this->fkFE_Config . '">';
        }



        //COPOL, POL 0 ScanDetails
        $q_scans = "SELECT keyId FROM ScanDetails
                    WHERE fkScanSetDetails = " . $this->keyId . "
                    AND pol = 0 and copol = 1
                    AND SourcePosition < 3
                    AND fkFacility = $this->fc
                    LIMIT 1;";
        $r_scans = mysqli_query($this->dbConnection, $q_scans);
        $this->keyId_copol_pol0_scan = ADAPT_mysqli_result($r_scans, 0);
        $this->Scan_copol_pol0 = new ScanDetails($this->keyId_copol_pol0_scan, $this->fc);

        if (isset($_REQUEST['ifatten_copol_pol0'])) {
            $this->Scan_copol_pol0->SetValue('ifatten', $_REQUEST['ifatten_copol_pol0']);
        }
        if (isset($_REQUEST['notes_copol_pol0'])) {
            $this->Scan_copol_pol0->SetValue('notes', $_REQUEST['notes_copol_pol0']);
        }

        //XPOL, POL 0 ScanDetails
        $q_scans = "SELECT keyId FROM ScanDetails
                    WHERE fkScanSetDetails = " . $this->keyId . "
                    AND pol = 0 and copol = 0
                    AND SourcePosition < 3
                    AND fkFacility = $this->fc
                    LIMIT 1;";
        $r_scans = mysqli_query($this->dbConnection, $q_scans);
        $this->keyId_xpol_pol0_scan = ADAPT_mysqli_result($r_scans, 0);
        $this->Scan_xpol_pol0 = new ScanDetails($this->keyId_xpol_pol0_scan, $this->fc);

        if (isset($_REQUEST['ifatten_copol_pol0'])) {
            $this->Scan_xpol_pol0->SetValue('ifatten', $_REQUEST['ifatten_xpol_pol0']);
        }
        if (isset($_REQUEST['notes_copol_pol0'])) {
            $this->Scan_xpol_pol0->SetValue('notes', $_REQUEST['notes_xpol_pol0']);
        }
        //$this->Scan_xpol_pol0->Update();

        //COPOL, POL 1 ScanDetails
        $q_scans = "SELECT keyId FROM ScanDetails
                    WHERE fkScanSetDetails = " . $this->keyId . "
                    AND pol = 1 and copol = 1
                    AND SourcePosition < 3
                    AND fkFacility = $this->fc
                    LIMIT 1;";
        $r_scans = mysqli_query($this->dbConnection, $q_scans);
        $this->keyId_copol_pol1_scan = ADAPT_mysqli_result($r_scans, 0);
        $this->Scan_copol_pol1 = new ScanDetails($this->keyId_copol_pol1_scan, $this->fc);
        if (isset($_REQUEST['ifatten_copol_pol1'])) {
            $this->Scan_copol_pol1->SetValue('ifatten', $_REQUEST['ifatten_copol_pol1']);
        }
        if (isset($_REQUEST['notes_copol_pol1'])) {
            $this->Scan_copol_pol1->SetValue('notes', $_REQUEST['notes_copol_pol1']);
        }
        //$this->Scan_copol_pol1->Update();


        //XPOL, POL 1 ScanDetails
        $q_scans = "SELECT keyId FROM ScanDetails
                    WHERE fkScanSetDetails = " . $this->keyId . "
                    AND pol = 1 and copol = 0
                    AND SourcePosition < 3
                    AND fkFacility = $this->fc
                    LIMIT 1;";
        $r_scans = mysqli_query($this->dbConnection, $q_scans);
        $this->keyId_xpol_pol1_scan = ADAPT_mysqli_result($r_scans, 0);
        $this->Scan_xpol_pol1 = new ScanDetails($this->keyId_xpol_pol1_scan, $this->fc);
        if (isset($_REQUEST['ifatten_copol_pol1'])) {
            $this->Scan_xpol_pol1->SetValue('ifatten', $_REQUEST['ifatten_xpol_pol1']);
        }
        if (isset($_REQUEST['notes_copol_pol1'])) {
            $this->Scan_xpol_pol1->SetValue('notes', $_REQUEST['notes_xpol_pol1']);
        }
        //$this->Scan_xpol_pol1->Update();

        //180 Scan ScanDetails
        $q_scans = "SELECT keyId FROM ScanDetails
                    WHERE fkScanSetDetails = " . $this->keyId . "
                    AND copol = 1
                    AND SourcePosition > 2
                    AND fkFacility = $this->fc
                    LIMIT 1;";
        $r_scans = mysqli_query($this->dbConnection, $q_scans);
        $this->keyId_180_scan = ADAPT_mysqli_result($r_scans, 0);
        $this->Scan_180 = new ScanDetails($this->keyId_180_scan, $this->fc);
    }

    public function Display_delete_form_SSD() {
        echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">
        <b><font size="+1">Are you sure you want to delete this record?</b></font>
        <br><input type="submit" name = "deleterecord_forsure" value="YES, DELETE RECORD"><br><br>
        <input type="hidden" name="id" value="' . $this->keyId . '" />
        </form>';
    }
}
