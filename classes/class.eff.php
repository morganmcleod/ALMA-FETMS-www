<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.scansetdetails.php');
require_once($site_classes . '/class.scandetails.php');
require_once($site_classes . '/class.spec_functions.php');
require_once($site_dbConnect);
require_once($site_dBcode . '/beameffdb.php');

// Class eff:  Main interface to beam efficiency calculations
//

class eff {
    var $fe_id;             //keyId of the Front End
    var $scansets;          //Array of scan set detail objects
    var $dbconnection;      //database connection from dbConnect.php
    var $basedir;           //base directory for all efficiency files
    var $newbasedir;        //working directory for output files
    var $listingsdir;       //directory for temporary nf and ff listings
    var $eff_inputfile;     //full absolute path of input text file for efficiency program.
    var $outputdirectory;   //Output directory for efficiency plots
    var $eff_outputfile;
    var $beameff_exe;
    var $NumberOfScanSets;
    var $url_dir;
    var $band;
    var $ReadyToProcess;
    var $Processed;         //0 = Has not been processed. 1 = Has been processed.
    var $root_datadir;
    var $root_urldir;
    var $software_version;
    var $ssid;
    var $fc;                //facility key
    var $GNUPLOT_path;
    var $new_spec;

    public function __construct() {
        $this->software_version = "1.1.2";
        // 1.1.2  Added Display_PhaseEff()
        // 1.1.1  Added download for cross-pol .csv files.
        // 1.1.0  Uses database calls from dbCode/beameffdb.php
        // 1.0.23 Supressed E_NOTICE errors in the Upload...() functions
        // 1.0.22 Reduced number of copies of NominalAngles tables being consulted
        // 1.0.21 Fixed bugs in pol.eff display, comparing to specs
        // 1.0.20 MTM updated band 4 PolEff display per FEND-40.02.04.00-0236-C-CRE
        //          PolEff: modified band 8 display per Whyborn comment on AIVPNCR-24
        // 1.0.19 MTM no longer writing out Notes to input file for beameff_64.
        //        Equal sign and others were causing errors from parse_ini_file().
        // 1.0.18 MTM fix display of NSI filenames.
        //        Updates to phase center offsets table.
        //        Fix fonts in tables.
        //        Comments fixes from meeting with Todd and Saini.

        require(site_get_config_main());

        $this->new_spec = new Specifications();
        $this->dbconnection = site_getDbConnection();
        $this->db_pull = new BeamEffDB($this->dbconnection);
        $this->basedir = $main_write_directory;
        $this->root_datadir = $rootdir_data;
        $this->root_urldir = $rootdir_url;
        $this->url_dir = $main_url_directory;
        $this->ReadyToProcess = 0;
        $this->beameff_exe = $beameff_64;
        $this->Processed = 0;
        $this->GNUPLOT_path = $GNUPLOT;
    }

    function ReplacePlotURLs() {
        require(site_get_config_main());

        $plots[0] = "pointing_angles_plot";
        $plots[1] = "plot_copol_nfamp";
        $plots[2] = "plot_copol_ffamp";
        $plots[3] = "plot_xpol_nfamp";
        $plots[4] = "plot_xpol_ffamp";
        $plots[5] = "plot_copol_nfphase";
        $plots[6] = "plot_copol_ffphase";
        $plots[7] = "plot_xpol_nfphase";
        $plots[8] = "plot_xpol_ffphase";

        for ($scanSetIdx = 0; $scanSetIdx < $this->NumberOfScanSets; $scanSetIdx++) {
            // fix scan URLs.   TODO:  could this move into class.scansetdetails ?
            for ($p = 0; $p <= 8; $p++) {
                $oldurl = $this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue($plots[$p]);
                $newurl = $main_url_directory . substr($oldurl, stripos($oldurl, "eff/") , -1) . "g";
                $this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->SetValue($plots[$p], "$newurl");
            }
            $this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->Update();

            for ($p = 0; $p <= 8; $p++) {
                $oldurl = $this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue($plots[$p]);
                $newurl = $main_url_directory . substr($oldurl, stripos($oldurl, "eff/") , -1) . "g";
                $this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->SetValue($plots[$p], $newurl);
            }
            $this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->Update();

            for ($p = 0; $p <= 8; $p++) {
                $oldurl = $this->scansets[$scanSetIdx]->Scan_xpol_pol0->BeamEfficencies->GetValue($plots[$p]);
                $newurl = $main_url_directory . substr($oldurl, stripos($oldurl, "eff/") , -1) . "g";
                $this->scansets[$scanSetIdx]->Scan_xpol_pol0->BeamEfficencies->SetValue($plots[$p], $newurl);
            }
            $this->scansets[$scanSetIdx]->Scan_xpol_pol0->BeamEfficencies->Update();

            for ($p = 0; $p <= 8; $p++) {
                $oldurl =  $this->scansets[$scanSetIdx]->Scan_xpol_pol1->BeamEfficencies->GetValue($plots[$p]);
                $newurl = $main_url_directory . substr($oldurl, stripos($oldurl, "eff/") , -1) . "g";
                $this->scansets[$scanSetIdx]->Scan_xpol_pol1->BeamEfficencies->SetValue($plots[$p], $newurl);
            }
            $this->scansets[$scanSetIdx]->Scan_xpol_pol1->BeamEfficencies->Update();

            // fix pointing angles
            $oldurl = $this->scansets[$scanSetIdx]->Scan_180->BeamEfficencies->GetValue("pointing_angles_plot");
            $newurl = $main_url_directory . substr($oldurl, stripos($oldurl, "eff/") , -1) . "g";
            $this->scansets[$scanSetIdx]->Scan_180->BeamEfficencies->SetValue("pointing_angles_plot", $newurl);
            $this->scansets[$scanSetIdx]->Scan_180->BeamEfficencies->Update();
        }
    }

    public function Initialize_eff($in_fe_id, $in_fc) {
        $this->fe_id = $in_fe_id;
        $this->fc = $in_fc;
        $rss = $this->db_pull->qss(1, $this->fe_id, NULL, NULL, NULL, NULL);
        $ssindex = 0;

        while ($rowss = @mysql_fetch_array($rss)) {
            $ssid = $rowss[0];

            $this->scansets[$ssindex] = new ScanSetDetails();
            $this->scansets[$ssindex]->Initialize_ScanSetDetails($ssid, $this->fc);
            $this->scansets[$ssindex]->RequestValues_ScanSetDetails();

            $ssindex += 1;
        }
        $this->NumberOfScanSets = $ssindex;
    }

    public function Initialize_eff_SingleScanSet($in_keyId, $in_fc) {
        $this->ssid = $in_keyId;
        $this->fc = $in_fc;

        $rss = $this->db_pull->qss(2, NULL, $in_keyId, NULL, $this->fc, NULL);
        $this->band = @mysql_result($rss,0,1);
        $this->fe_id = @mysql_result($rss,0,2);

        $this->scansets[0] = new ScanSetDetails();
        $this->scansets[0]->Initialize_ScanSetDetails($in_keyId, $this->fc);
        $this->scansets[0]->RequestValues_ScanSetDetails();

        if ($this->scansets[0]->keyId_180_scan > 0)
            $this->ReadyToProcess = 1;

        $this->Processed = $this->db_pull->qeff($this->scansets);

        $this->NumberOfScanSets = 1;
    }

    public function Initialize_eff_single_band($in_fe_id, $band, $in_fc) {
        $this->fc = $in_fc;
        $this->band = $band;
        $this->fe_id = $in_fe_id;

        $rss = $this->db_pull->qss(3, $this->fe_id, NULL, $this->band, $this->fc, NULL);
        $ssindex = 0;

        while ($rowss = @mysql_fetch_array($rss)) {
            $ssid = $rowss[0];

            $this->scansets[$ssindex] = new ScanSetDetails();
            $this->scansets[$ssindex]->Initialize_ScanSetDetails($ssid, $this->fc);
            $this->scansets[$ssindex]->RequestValues_ScanSetDetails();

            $ssindex += 1;
        }
        $this->NumberOfScanSets = $ssindex;
    }

    private function MakeOutputEnvironment($squint) {
        // Create directories if needed, initiailize input and output file names.

        // top-level directory for data:
        if (!file_exists($this->basedir)) {
            mkdir($this->basedir);
        }
        // base directory for beam efficiency data:
        $this->newbasedir = $this->basedir . "eff/";
        if (!file_exists($this->newbasedir)) {
            mkdir($this->newbasedir);
        }
        // data directory for this data set will be like "$basedir/eff/fecfg697/ssid1591", possibly including "SQUINT":
        $this->newbasedir .= "fecfg" . $this->fe_id;
        if ($squint)
            $this->newbasedir .= "_SQUINT";
        $this->newbasedir .= "/";
        if (!file_exists($this->newbasedir)) {
            mkdir($this->newbasedir);
        }
        $this->newbasedir = $this->newbasedir . "ssid" . $this->scansets[0]->keyId . "/";
        if (!file_exists($this->newbasedir)) {
            mkdir($this->newbasedir);
        }
        // inside it will have an "output" and a "listings" subdirectory:
        $this->outputdirectory = $this->newbasedir . "output/";
        if (!file_exists($this->outputdirectory)) {
            mkdir($this->outputdirectory);
        }
        $this->listingsdir = $this->newbasedir . "listings/";
        if (!file_exists($this->listingsdir)) {
            mkdir($this->listingsdir);
        }
        // we will tell beameff_64 to put its output here:
        $this->eff_outputfile = $this->outputdirectory . "output.txt";
        if (file_exists($this->eff_outputfile)) {
            unlink($this->eff_outputfile);
        }
        // and to get its command input here:
        $this->eff_inputfile = $this->newbasedir;
        if ($squint)
            $this->eff_inputfile .= "input_fileSQUINT.txt";
        else
            $this->eff_inputfile .= "input_file.txt";
        if (file_exists($this->eff_inputfile)) {
            unlink($this->eff_inputfile);
        }
    }

    public function GetScanSideband($scanSetIdx) {
        $scanSetId = $this->scansets[$scanSetIdx]->GetValue('keyId');

        $rss = $this->db_pull->qss(4, NULL, NULL, NULL, $this->fc, $scanSetId);
        $rowss = @mysql_fetch_array($rss);
        return $rowss[0];
    }

    public function MakeInputFile() {
        $this -> MakeOutputEnvironment(FALSE);

        // start writing the command input file:
        $fhandle = fopen($this->eff_inputfile, 'w');

        //Fill in values for settings section
        fwrite($fhandle,"[settings]\r\n");
        fwrite($fhandle,'gnuplot="' . $this->GNUPLOT_path . '"' . "\r\n");
        fwrite($fhandle,'outputdirectory="' . $this->outputdirectory . '"' . "\r\n");
        fwrite($fhandle,"delimiter=tab\r\n");
        fwrite($fhandle,"centers=nominal\r\n");
        fwrite($fhandle,"\r\n");

        //Fill in the individual scan sections
        $scanNumber=0;
        for ($scanSetIdx = 0; $scanSetIdx < count($this->scansets); $scanSetIdx++) {
            $scanSet = $scanSetIdx + 1;

            $sb = $this -> GetScanSideband($scanSetIdx);

            //Copol pol 0 scan
            $scanNumber++;
            fwrite($fhandle,"[scan_$scanNumber]\r\n");
            fwrite($fhandle,"keyscandetails=". $this->scansets[$scanSetIdx]->keyId_copol_pol0_scan ."\r\n");
            fwrite($fhandle,"type=copol\r\n");
            fwrite($fhandle,"pol=0\r\n");
            fwrite($fhandle,"scanset=" . ($scanSet) ."\r\n");
            fwrite($fhandle,"f=" . $this->scansets[$scanSetIdx]->GetValue('f') ."\r\n");
            fwrite($fhandle,"sb=" . $sb ."\r\n");
            fwrite($fhandle,"tilt=" . $this->scansets[$scanSetIdx]->GetValue('tilt') ."\r\n");
            fwrite($fhandle,"band=" . $this->scansets[$scanSetIdx]->GetValue('band') ."\r\n");
            fwrite($fhandle,"notes=\r\n");
            fwrite($fhandle,"ifatten=" . $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('ifatten') ."\r\n");

            $nf_path = $this->listingsdir . "scanset_" . ($scanSet) . "_copol_pol0_nf.txt";
            $this->ExportNF($nf_path,$this->scansets[$scanSetIdx]->keyId_copol_pol0_scan);
            $ff_path = $this->listingsdir . "scanset_" . ($scanSet) . "_copol_pol0_ff.txt";
            $this->ExportFF($ff_path,$this->scansets[$scanSetIdx]->keyId_copol_pol0_scan);

            fwrite($fhandle,'nf="' . $nf_path . '"' . "\r\n");
            fwrite($fhandle,'ff="' . $ff_path . '"' . "\r\n");
            fwrite($fhandle,"nf_startrow=0\r\n");
            fwrite($fhandle,"nf2_startrow=0\r\n");
            fwrite($fhandle,"ff_startrow=0\r\n");
            fwrite($fhandle,"ff2_startrow=0\r\n");
            fwrite($fhandle,"scanset_id=" . $this->scansets[$scanSetIdx]->keyId . "\r\n");
            fwrite($fhandle,"scan_id=" . $this->scansets[$scanSetIdx]->keyId_copol_pol0_scan. "\r\n");
            fwrite($fhandle,"fecfg=" . $this->scansets[$scanSetIdx]->GetValue('fkFE_Config') . "\r\n");

            $ts = strftime("%a",strtotime($this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('TS'))) . " " .  $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('TS');
            fwrite($fhandle,"ts='$ts'\r\n");

            fwrite($fhandle,"\r\n");

            //Crosspol pol 0 scan
            $scanNumber++;
            fwrite($fhandle,"[scan_$scanNumber]\r\n");
            fwrite($fhandle,"keyscandetails=". $this->scansets[$scanSetIdx]->keyId_xpol_pol0_scan ."\r\n");
            fwrite($fhandle,"type=xpol\r\n");
            fwrite($fhandle,"pol=0\r\n");
            fwrite($fhandle,"scanset=" . ($scanSet) ."\r\n");
            fwrite($fhandle,"f=" . $this->scansets[$scanSetIdx]->GetValue('f') ."\r\n");
            fwrite($fhandle,"sb=" . $sb ."\r\n");
            fwrite($fhandle,"tilt=" . $this->scansets[$scanSetIdx]->GetValue('tilt') ."\r\n");
            fwrite($fhandle,"band=" . $this->scansets[$scanSetIdx]->GetValue('band') ."\r\n");
            fwrite($fhandle,"notes=\r\n");
            fwrite($fhandle,"ifatten=" . $this->scansets[$scanSetIdx]->Scan_xpol_pol0->GetValue('ifatten') ."\r\n");
            fwrite($fhandle,"scanset_id=" . $this->scansets[$scanSetIdx]->keyId . "\r\n");
            fwrite($fhandle,"scan_id=" . $this->scansets[$scanSetIdx]->keyId_xpol_pol0_scan. "\r\n");
            fwrite($fhandle,"fecfg=" . $this->scansets[$scanSetIdx]->GetValue('fkFE_Config') . "\r\n");

            $ts = strftime("%a",strtotime($this->scansets[$scanSetIdx]->Scan_xpol_pol0->GetValue('TS'))) . " " .  $this->scansets[$scanSetIdx]->Scan_xpol_pol0->GetValue('TS');
            fwrite($fhandle,"ts='$ts'\r\n");

            $nf_path = $this->listingsdir . "scanset_" . ($scanSet) . "_xpol_pol0_nf.txt";
            $this->ExportNF($nf_path,$this->scansets[$scanSetIdx]->keyId_xpol_pol0_scan);
            $ff_path = $this->listingsdir . "scanset_" . ($scanSet) . "_xpol_pol0_ff.txt";
            $this->ExportFF($ff_path,$this->scansets[$scanSetIdx]->keyId_xpol_pol0_scan);

            fwrite($fhandle,'nf="' . $nf_path . '"' . "\r\n");
            fwrite($fhandle,'ff="' . $ff_path . '"' . "\r\n");
            fwrite($fhandle,"nf_startrow=0\r\n");
            fwrite($fhandle,"nf2_startrow=0\r\n");
            fwrite($fhandle,"ff_startrow=0\r\n");
            fwrite($fhandle,"ff2_startrow=0\r\n");
            fwrite($fhandle,"\r\n");

            //Copol pol 1 scan
            $scanNumber++;
            fwrite($fhandle,"[scan_$scanNumber]\r\n");
            fwrite($fhandle,"keyscandetails=". $this->scansets[$scanSetIdx]->keyId_copol_pol1_scan ."\r\n");
            fwrite($fhandle,"type=copol\r\n");
            fwrite($fhandle,"pol=1\r\n");
            fwrite($fhandle,"scanset=" . ($scanSet) ."\r\n");
            fwrite($fhandle,"f=" . $this->scansets[$scanSetIdx]->GetValue('f') ."\r\n");
            fwrite($fhandle,"sb=" . $sb ."\r\n");
            fwrite($fhandle,"tilt=" . $this->scansets[$scanSetIdx]->GetValue('tilt') ."\r\n");
            fwrite($fhandle,"band=" . $this->scansets[$scanSetIdx]->GetValue('band') ."\r\n");
            fwrite($fhandle,"notes=\r\n");
            fwrite($fhandle,"ifatten=" . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('ifatten') ."\r\n");
            fwrite($fhandle,"scanset_id=" . $this->scansets[$scanSetIdx]->keyId . "\r\n");
            fwrite($fhandle,"scan_id=" . $this->scansets[$scanSetIdx]->keyId_copol_pol1_scan. "\r\n");
            $ts = strftime("%a",strtotime($this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('TS'))) . " " .  $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('TS');

            fwrite($fhandle,"ts='$ts'\r\n");
            fwrite($fhandle,"fecfg=" . $this->scansets[$scanSetIdx]->GetValue('fkFE_Config') . "\r\n");

            $nf_path = $this->listingsdir . "scanset_" . ($scanSet) . "_copol_pol1_nf.txt";
            $this->ExportNF($nf_path,$this->scansets[$scanSetIdx]->keyId_copol_pol1_scan);
            $ff_path = $this->listingsdir . "scanset_" . ($scanSet) . "_copol_pol1_ff.txt";
            $this->ExportFF($ff_path,$this->scansets[$scanSetIdx]->keyId_copol_pol1_scan);

            fwrite($fhandle,'nf="' . $nf_path . '"' . "\r\n");
            fwrite($fhandle,'ff="' . $ff_path . '"' . "\r\n");
            fwrite($fhandle,"nf_startrow=0\r\n");
            fwrite($fhandle,"nf2_startrow=0\r\n");
            fwrite($fhandle,"ff_startrow=0\r\n");
            fwrite($fhandle,"ff2_startrow=0\r\n");
            fwrite($fhandle,"\r\n");

            //Crosspol pol 1 scan
            $scanNumber++;
            fwrite($fhandle,"[scan_$scanNumber]\r\n");
            fwrite($fhandle,"keyscandetails=". $this->scansets[$scanSetIdx]->keyId_xpol_pol1_scan ."\r\n");
            fwrite($fhandle,"type=xpol\r\n");
            fwrite($fhandle,"pol=1\r\n");
            fwrite($fhandle,"scanset=" . ($scanSet) ."\r\n");
            fwrite($fhandle,"f=" . $this->scansets[$scanSetIdx]->GetValue('f') ."\r\n");
            fwrite($fhandle,"sb=" . $sb ."\r\n");
            fwrite($fhandle,"tilt=" . $this->scansets[$scanSetIdx]->GetValue('tilt') ."\r\n");
            fwrite($fhandle,"band=" . $this->scansets[$scanSetIdx]->GetValue('band') ."\r\n");
            fwrite($fhandle,"notes=\r\n");
            fwrite($fhandle,"ifatten=" . $this->scansets[$scanSetIdx]->Scan_xpol_pol1->GetValue('ifatten') ."\r\n");
            fwrite($fhandle,"scanset_id=" . $this->scansets[$scanSetIdx]->keyId . "\r\n");
            fwrite($fhandle,"scan_id=" . $this->scansets[$scanSetIdx]->keyId_xpol_pol1_scan. "\r\n");
            $ts = strftime("%a",strtotime($this->scansets[$scanSetIdx]->Scan_xpol_pol1->GetValue('TS'))) . " " .  $this->scansets[$scanSetIdx]->Scan_xpol_pol1->GetValue('TS');
            fwrite($fhandle,"ts='$ts'\r\n");
            fwrite($fhandle,"fecfg=" . $this->scansets[$scanSetIdx]->GetValue('fkFE_Config') . "\r\n");

            $nf_path = $this->listingsdir . "scanset_" . ($scanSet) . "_xpol_pol1_nf.txt";
            $this->ExportNF($nf_path,$this->scansets[$scanSetIdx]->keyId_xpol_pol1_scan);
            $ff_path = $this->listingsdir . "scanset_" . ($scanSet) . "_xpol_pol1_ff.txt";
            $this->ExportFF($ff_path,$this->scansets[$scanSetIdx]->keyId_xpol_pol1_scan);

            fwrite($fhandle,'nf="' . $nf_path . '"' . "\r\n");
            fwrite($fhandle,'ff="' . $ff_path . '"' . "\r\n");
            fwrite($fhandle,"nf_startrow=0\r\n");
            fwrite($fhandle,"nf2_startrow=0\r\n");
            fwrite($fhandle,"ff_startrow=0\r\n");
            fwrite($fhandle,"ff2_startrow=0\r\n");
            fwrite($fhandle,"\r\n");
        }
        fclose($fhandle);
    }

    public function MakeInputFileSquint() {
        $this -> MakeOutputEnvironment(TRUE);

        // start writing the command input file:
        $fhandle = fopen($this->eff_inputfile, 'w');

        //Fill in values for settings section
        fwrite($fhandle,"[settings]\r\n");
        fwrite($fhandle,'gnuplot="' . $this->GNUPLOT_path . '"' . "\r\n");
        fwrite($fhandle,'outputdirectory="' . $this->outputdirectory . '"' . "\r\n");
        fwrite($fhandle,"delimiter=tab\r\n");
        fwrite($fhandle,"centers=nominal\r\n");
        fwrite($fhandle,"\r\n");

        //Fill in the individual scan sections
        $scanNumber=0;
        for ($scanSetIdx=0; $scanSetIdx < count($this->scansets); $scanSetIdx++) {
            $scanSet = $scanSetIdx + 1;

            $sb = $this -> GetScanSideband($scanSetIdx);

            //Copol pol 0 scan
            $scanNumber++;
            fwrite($fhandle,"[scan_$scanNumber]\r\n");
            fwrite($fhandle,"keyscandetails=". $this->scansets[$scanSetIdx]->keyId_180_scan ."\r\n");
            fwrite($fhandle,"type=copol\r\n");
            fwrite($fhandle,"pol=0\r\n");
            fwrite($fhandle,"scanset=" . ($scanSet) ."\r\n");
            fwrite($fhandle,"f=" . $this->scansets[$scanSetIdx]->GetValue('f') ."\r\n");
            fwrite($fhandle,"sb=" . $sb ."\r\n");
            fwrite($fhandle,"tilt=" . $this->scansets[$scanSetIdx]->GetValue('tilt') ."\r\n");
            fwrite($fhandle,"band=" . $this->scansets[$scanSetIdx]->GetValue('band') ."\r\n");
            fwrite($fhandle,"notes=\r\n");
            fwrite($fhandle,"ifatten=0\r\n");

            $nf_path = $this->listingsdir . "scanset_" . ($scanSet) . "_copol_180_nf.txt";
            $this->ExportNF($nf_path,$this->scansets[$scanSetIdx]->keyId_180_scan);
            $ff_path = $this->listingsdir . "scanset_" . ($scanSet) . "_copol_180_ff.txt";
            $this->ExportFF($ff_path,$this->scansets[$scanSetIdx]->keyId_180_scan);

            fwrite($fhandle,'nf="' . $nf_path . '"' . "\r\n");
            fwrite($fhandle,'ff="' . $ff_path . '"' . "\r\n");
            fwrite($fhandle,"nf_startrow=0\r\n");
            fwrite($fhandle,"nf2_startrow=0\r\n");
            fwrite($fhandle,"ff_startrow=0\r\n");
            fwrite($fhandle,"ff2_startrow=0\r\n");
            fwrite($fhandle,"\r\n");

            //Crosspol pol 0 scan
            $scanNumber++;
            fwrite($fhandle,"[scan_$scanNumber]\r\n");
            fwrite($fhandle,"keyscandetails=". $this->scansets[$scanSetIdx]->keyId_180_scan ."\r\n");
            fwrite($fhandle,"type=xpol\r\n");
            fwrite($fhandle,"pol=0\r\n");
            fwrite($fhandle,"scanset=" . ($scanSet) ."\r\n");
            fwrite($fhandle,"f=" . $this->scansets[$scanSetIdx]->GetValue('f') ."\r\n");
            fwrite($fhandle,"sb=" . $sb ."\r\n");
            fwrite($fhandle,"tilt=" . $this->scansets[$scanSetIdx]->GetValue('tilt') ."\r\n");
            fwrite($fhandle,"band=" . $this->scansets[$scanSetIdx]->GetValue('band') ."\r\n");
            fwrite($fhandle,"notes=\r\n");
            fwrite($fhandle,"ifatten=0\r\n");


            fwrite($fhandle,'nf="' . $nf_path . '"' . "\r\n");
            fwrite($fhandle,'ff="' . $ff_path . '"' . "\r\n");
            fwrite($fhandle,"nf_startrow=0\r\n");
            fwrite($fhandle,"nf2_startrow=0\r\n");
            fwrite($fhandle,"ff_startrow=0\r\n");
            fwrite($fhandle,"ff2_startrow=0\r\n");
            fwrite($fhandle,"\r\n");

            //Copol pol 1 scan
            $scanNumber++;
            fwrite($fhandle,"[scan_$scanNumber]\r\n");
            fwrite($fhandle,"keyscandetails=". $this->scansets[$scanSetIdx]->keyId_180_scan ."\r\n");
            fwrite($fhandle,"type=copol\r\n");
            fwrite($fhandle,"pol=1\r\n");
            fwrite($fhandle,"scanset=" . ($scanSet) ."\r\n");
            fwrite($fhandle,"f=" . $this->scansets[$scanSetIdx]->GetValue('f') ."\r\n");
            fwrite($fhandle,"sb=" . $sb ."\r\n");
            fwrite($fhandle,"tilt=" . $this->scansets[$scanSetIdx]->GetValue('tilt') ."\r\n");
            fwrite($fhandle,"band=" . $this->scansets[$scanSetIdx]->GetValue('band') ."\r\n");
            fwrite($fhandle,"notes=\r\n");
            fwrite($fhandle,"ifatten=0\r\n");


            fwrite($fhandle,'nf="' . $nf_path . '"' . "\r\n");
            fwrite($fhandle,'ff="' . $ff_path . '"' . "\r\n");
            fwrite($fhandle,"nf_startrow=0\r\n");
            fwrite($fhandle,"nf2_startrow=0\r\n");
            fwrite($fhandle,"ff_startrow=0\r\n");
            fwrite($fhandle,"ff2_startrow=0\r\n");
            fwrite($fhandle,"\r\n");

            //Crosspol pol 1 scan
            $scanNumber++;
            fwrite($fhandle,"[scan_$scanNumber]\r\n");
            fwrite($fhandle,"keyscandetails=". $this->scansets[$scanSetIdx]->keyId_180_scan ."\r\n");
            fwrite($fhandle,"type=xpol\r\n");
            fwrite($fhandle,"pol=1\r\n");
            fwrite($fhandle,"scanset=" . ($scanSet) ."\r\n");
            fwrite($fhandle,"f=" . $this->scansets[$scanSetIdx]->GetValue('f') ."\r\n");
            fwrite($fhandle,"sb=" . $sb ."\r\n");
            fwrite($fhandle,"tilt=" . $this->scansets[$scanSetIdx]->GetValue('tilt') ."\r\n");
            fwrite($fhandle,"band=" . $this->scansets[$scanSetIdx]->GetValue('band') ."\r\n");
            fwrite($fhandle,"notes=\r\n");
            fwrite($fhandle,"ifatten=0\r\n");

            fwrite($fhandle,'nf="' . $nf_path . '"' . "\r\n");
            fwrite($fhandle,'ff="' . $ff_path . '"' . "\r\n");
            fwrite($fhandle,"nf_startrow=0\r\n");
            fwrite($fhandle,"nf2_startrow=0\r\n");
            fwrite($fhandle,"ff_startrow=0\r\n");
            fwrite($fhandle,"ff2_startrow=0\r\n");
            fwrite($fhandle,"\r\n");
        }
        fclose($fhandle);
    }

    function ExportNF($nf_path, $scan_id) {
        if (file_exists($nf_path)) {
            unlink($nf_path);
        }

        $this->db_pull->q(TRUE, $nf_path, $scan_id);
    }

    function ExportFF($ff_path, $scan_id) {
        if (file_exists($ff_path)) {
            unlink($ff_path);
        }

        $this->db_pull->q(FALSE, $ff_path, $scan_id);
    }

    public function GetEfficiencies() {
        // Main function to calculate beam efficiencies from scan sets.
        // Create the input file for beameff_64:
        $this->MakeInputFile();
        // Execute beameff_64:
        $CommandString = "$this->beameff_exe $this->eff_inputfile";
        system($CommandString);
        // Upload the efficiency results to the database:
        $this->UploadEfficiencyFile($this->eff_outputfile);
        $this->Initialize_eff_SingleScanSet($this->ssid, $this->fc);

        if ($this->scansets[0]->Scan_180->keyId != "") {
            echo "GETTING SQUINT..." . $this->scansets[0]->Scan_180->keyId . "<br>";
            // Calculate phase center from the 180-degree scans.
            // Create the input file for beameff_64:
            $this->MakeInputFileSquint();
            // Execute beameff_64:
            $CommandString = "$this->beameff_exe $this->eff_inputfile";
            system($CommandString);
            // Upload the efficiency results to the database:
            $this->UploadEfficiencyFile_180($this->eff_outputfile);
            $this->Initialize_eff_SingleScanSet($this->ssid, $this->fc);
            $this->CalculateSquint();
        }
        //Fix the URLs in the generated plots:
        $this->ReplacePlotURLs();
    }

    function CalculateSquint() {
        $f = $this->scansets[0]->GetValue('f');

        //Source position
        //1= pol 0
        //2= pol 1
        //3= pol0 + 180
        //4= pol1 + 180
        $sp = $this->scansets[0]->Scan_180->GetValue('SourcePosition');

        switch ($sp) {
            case '3':
                $x0  = $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('delta_x');
                $y0  = $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('delta_y');
                $x90 = $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('delta_x');
                $y90 = $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('delta_y');
                break;
            case '4':
                $x0  = $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('delta_x');
                $y0  = $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('delta_y');
                $x90 = $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('delta_x');
                $y90 = $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('delta_y');
                break;
        }

        $x180 = $this->scansets[0]->Scan_180->BeamEfficencies->GetValue('delta_x');
        $y180 = $this->scansets[0]->Scan_180->BeamEfficencies->GetValue('delta_y');

        $dX = $x180 - $x0;
        $dY = $y180 - $y0;
        $x_diff = $x0 - $x90;
        $y_diff = $y0 - $y90;
        $abs_diff = Abs($dX) - Abs($dY);


        /* There are only two possible corrections (rigorously proven by this diagram),
         * depending on whether the relative scan angle of the second polarization was +90 or -90.
         * To determine whether it was +90 or -90, compare the signs of x_diff, y_diff, and abs_diff.
         * If all three of them are negative or exactly two of them are positive, then it was +90. Otherwise it was -90. */

        $pol_angle = -90;
        if (($x_diff * $y_diff * $abs_diff) < 0) {
            $pol_angle = 90;
        }

        /* compute x_corr and y_corr.
         */

        If ($pol_angle == 90) {
            $x_corr = ((-1 * $dX) - $dY) / 2;
            $y_corr =          ($dX - $dY)/ 2;
        }

        If ($pol_angle == -90) {
            $x_corr = ((-1 * $dX) + $dY) / 2;
            $y_corr = ((-1 * $dX) - $dY) / 2;
        }

        //Write values to database
        // this seems to be writing the same values to both pols.  It works out oK but isn't technically correct.
        // The corrections should be kept at a higher level in the data structure as they don't really apply to the individual scan.
        $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->SetValue('x_diff',$x_diff);
        $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->SetValue('y_diff',$y_diff);
        $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->SetValue('x_diff',$x_diff);
        $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->SetValue('y_diff',$y_diff);
        $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->SetValue('x_corr',$x_corr);
        $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->SetValue('y_corr',$y_corr);
        $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->SetValue('x_corr',$x_corr);
        $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->SetValue('y_corr',$y_corr);

        // save the uncorrected values back into the scansets data structure:
        switch ($sp) {
            case '3':
                $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->SetValue('x0',$x0);
                $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->SetValue('y0',$y0);
                $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->SetValue('x90',$x90);
                $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->SetValue('y90',$y90);

                $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->SetValue('x0',0);
                $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->SetValue('y0',0);
                $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->SetValue('x90',0);
                $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->SetValue('y90',0);

                break;
            case '4':
                $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->SetValue('x0',$x0);
                $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->SetValue('y0',$y0);
                $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->SetValue('x90',$x90);
                $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->SetValue('y90',$y90);

                $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->SetValue('x0',0);
                $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->SetValue('y0',0);
                $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->SetValue('x90',0);
                $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->SetValue('y90',0);
                break;
        }

        // apply corrections to x90 and y90 for distance and squint calculations:
        $x90 = $x90 + $x_corr;
        $y90 = $y90 + $y_corr;

        //Corrected distance between two beam centers in mm:
        $DistanceBetweenBeamCenters = Abs((Sqrt(pow($x0 - $x90, 2.0) + pow($y0 - $y90, 2.0))));

        // Squint calculation based on corrected values.
        // square root of the sum of the differences squared
        // 2.148 is plate factor in arc-s/mm.
        $squint_arcseconds = Abs((Sqrt(pow($x0 - $x90, 2.0) + pow($y0 - $y90, 2.0)) ) * 2.148);

        // Calculate squint in percentage of units of FWHM of the beam:
        // 1.15 is the coefficent to mutply by lambda/D to get FWHM.  D=diameter of primary mirror in mm.
        // 299.79 is c in appropriate units.
        // $f is in GHz.
        // 57.3 is degrees in a radian.  Could be expresed as 180/pi.
        // 60.0 * 60.0 convers to arcseconds.   12000 mm is diameter of dish.

        $lambda = 299.79 / $f;    // c in mm/ns.  $f in GHz.
        $squint = (100.0 * $squint_arcseconds) / (1.15 * $lambda * 57.3 * 60.0 * 60.0 / 12000.0);

        // save the distance between corrected centers back to the scansets data structure:
        $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->SetValue('DistanceBetweenBeamCenters',$DistanceBetweenBeamCenters);
        $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->SetValue('DistanceBetweenBeamCenters',$DistanceBetweenBeamCenters);

        // save computed squint and write to DB:
        $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->SetValue('squint', $squint);
        $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->SetValue('squint_arcseconds', $squint_arcseconds);
        $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->Update();

        $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->SetValue('squint', $squint);
        $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->SetValue('squint_arcseconds', $squint_arcseconds);
        $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->Update();

        $this->scansets[0]->Scan_180->BeamEfficencies->SetValue('squint', $squint);
        $this->scansets[0]->Scan_180->BeamEfficencies->SetValue('squint_arcseconds', $squint_arcseconds);
        $this->scansets[0]->Scan_180->BeamEfficencies->Update();
    }

    function UploadEfficiencyFile($ini_filename) {
        require(site_get_config_main());

        $ini_array = parse_ini_file($ini_filename, true);

        $software_version = $ini_array['settings']['software_version'];

        $band = 1;

        while ($band <= 10) {
            $key = "pointingangles_band_$band";
            if (isset ($ini_array['settings'][$key]) && $ini_array['settings'][$key] != '') {
                $pointing_angles_plot = $ini_array['settings'][$key];
                $band = 11;
            } else
                $band++;
        }

        foreach ($ini_array as $key => $value) {
            if ($key != "settings") {
                $keyScanDetails = $ini_array[$key]['keyscandetails'];

                //Delete any existing efficiency record for this scan
                $rdelete = $this->db_pull->qdelete($keyScanDetails, NULL);
                $rds = $this->db_pull->q_other('s', $keyScanDetails, NULL);
                $beameffID = @mysql_result($rds,0);
                $beameff = new GenericTable;

                if ($beameffID == "") {
                    $beameff->Initialize("BeamEfficiencies","","keyBeamEfficiencies",$this->fc,'fkFacility');
                    $beameff-> NewRecord("BeamEfficiencies","keyBeamEfficiencies",$this->fc,'fkFacility');
                }
                if ($beameffID != "") {
                    $beameff->Initialize("BeamEfficiencies",$beameffID,"keyBeamEfficiencies",$this->fc,'fkFacility');
                }

                $beameff-> SetValue("fkScanDetails", $keyScanDetails);
                $beameff-> SetValue("eff_output_file", $ini_filename);
                $beameff-> SetValue("pointing_angles_plot", $pointing_angles_plot);
                $beameff-> SetValue("software_version", $software_version);

                global $errorReportSettingsNo_E_NOTICE;
                global $errorReportSettingsNormal;
                error_reporting($errorReportSettingsNo_E_NOTICE);

                $beameff-> SetValue("pol", $ini_array[$key]['pol']);
                $beameff-> SetValue("tilt", $ini_array[$key]['tilt']);
                $beameff-> SetValue("f", $ini_array[$key]['f']);
                $beameff-> SetValue("type", $ini_array[$key]['type']);
                $beameff-> SetValue("tilt", $ini_array[$key]['tilt']);
                $beameff-> SetValue("ifatten", $ini_array[$key]['ifatten']);
                $beameff-> SetValue("eta_spillover", $ini_array[$key]['eta_spillover']);
                $beameff-> SetValue("eta_taper", $ini_array[$key]['eta_taper']);
                $beameff-> SetValue("eta_illumination", $ini_array[$key]['eta_illumination']);
                $beameff-> SetValue("ff_xcenter", $ini_array[$key]['ff_xcenter']);
                $beameff-> SetValue("ff_ycenter", $ini_array[$key]['ff_ycenter']);
                $beameff-> SetValue("az_nominal", $ini_array[$key]['az_nominal']);
                $beameff-> SetValue("el_nominal", $ini_array[$key]['el_nominal']);
                $beameff-> SetValue("nf_xcenter", $ini_array[$key]['nf_xcenter']);
                $beameff-> SetValue("nf_ycenter", $ini_array[$key]['nf_ycenter']);
                $beameff-> SetValue("max_ff_amp_db", $ini_array[$key]['max_ff_amp_db']);
                $beameff-> SetValue("max_nf_amp_db", $ini_array[$key]['max_nf_amp_db']);
                $beameff-> SetValue("delta_x", $ini_array[$key]['delta_x']);
                $beameff-> SetValue("delta_y", $ini_array[$key]['delta_y']);
                $beameff-> SetValue("delta_z", $ini_array[$key]['delta_z']);
                $beameff-> SetValue("eta_phase", $ini_array[$key]['eta_phase']);
                $beameff-> SetValue("ampfit_amp", $ini_array[$key]['ampfit_amp']);
                $beameff-> SetValue("ampfit_width_deg", $ini_array[$key]['ampfit_width_deg']);
                $beameff-> SetValue("ampfit_u_off", $ini_array[$key]['ampfit_u_off_deg']);
                $beameff-> SetValue("ampfit_v_off", $ini_array[$key]['ampfit_v_off_deg']);
                $beameff-> SetValue("ampfit_d_0_90", $ini_array[$key]['ampfit_d_0_90']);
                $beameff-> SetValue("ampfit_edge_db", $ini_array[$key]['edge_db']);
                $beameff-> SetValue("ampfit_d_45_135", $ini_array[$key]['ampfit_d_45_135']);
                $beameff-> SetValue("plot_copol_nfamp", $ini_array[$key]['plot_copol_nfamp']);
                $beameff-> SetValue("plot_copol_nfphase", $ini_array[$key]['plot_copol_nfphase']);
                $beameff-> SetValue("plot_copol_ffamp", $ini_array[$key]['plot_copol_ffamp']);
                $beameff-> SetValue("plot_copol_ffphase", $ini_array[$key]['plot_copol_ffphase']);
                $beameff-> SetValue("plot_xpol_nfamp", $ini_array[$key]['plot_xpol_nfamp']);
                $beameff-> SetValue("plot_xpol_nfphase", $ini_array[$key]['plot_xpol_nfphase']);
                $beameff-> SetValue("plot_xpol_ffamp", $ini_array[$key]['plot_xpol_ffamp']);
                $beameff-> SetValue("plot_xpol_ffphase", $ini_array[$key]['plot_xpol_ffphase']);
                $beameff-> SetValue("datetime", $ini_array[$key]['datetime']);
                $beameff-> SetValue("nf", $ini_array[$key]['nf']);
                $beameff-> SetValue("ff", $ini_array[$key]['ff']);
                $beameff-> SetValue("nominal_z_offset", $ini_array[$key]['nominal_z_offset']);
                $beameff-> SetValue("eta_tot_np", $ini_array[$key]['eta_tot_np']);
                $beameff-> SetValue("eta_pol", $ini_array[$key]['eta_pol']);
                $beameff-> SetValue("eta_tot_nd", $ini_array[$key]['eta_tot_nd']);
                $beameff-> SetValue("defocus_efficiency", $ini_array[$key]['defocus_efficiency']);
                $beameff-> SetValue("total_aperture_eff", $ini_array[$key]['total_aperture_eff']);
                $beameff-> SetValue("shift_from_focus_mm", $ini_array[$key]['shift_from_focus_mm']);
                $beameff-> SetValue("subreflector_shift_mm", $ini_array[$key]['subreflector_shift_mm']);
                $beameff-> SetValue("defocus_efficiency_due_to_moving_the_subreflector", $ini_array[$key]['defocus_efficiency_due_to_moving_the_subreflector']);
                $beameff-> SetValue("squint", $ini_array[$key]['squint']);
                $beameff-> SetValue("squint_arcseconds", $ini_array[$key]['squint_arcseconds']);
                $beameff-> SetValue("max_dbdifference", $ini_array[$key]['max_dbdifference']);
                $beameff-> SetValue("software_version_class_eff", $this->software_version);

                error_reporting($errorReportSettingsNormal);

                $oldurl =  $beameff->GetValue("pointing_angles_plot");
                $newurl = $main_url_directory . substr($oldurl, stripos($oldurl, "eff/") , -1) .  "g";
                $beameff->SetValue("pointing_angles_plot", $newurl);
                $beameff->Update();
                unset($beameff);
            }
        }
    }

    function UploadEfficiencyFile_180($ini_filename) {
        require(site_get_config_main());

        $ini_array = parse_ini_file($ini_filename, true);

        $software_version = $ini_array['settings']['software_version'];

        $band = 1;
        while ($band <= 10) {
            $key = "pointingangles_band_$band";
            if (isset ($ini_array['settings'][$key]) && $ini_array['settings'][$key] != '') {
                $pointing_angles_plot = $ini_array['settings'][$key];
                $band = 11;
            } else
                $band++;
        }

        foreach ($ini_array as $key => $value) {
            if ($key != "settings" && $ini_array[$key]['type'] == "copol" && $ini_array[$key]['pol'] == "0") {

                $keyScanDetails = $ini_array[$key]['keyscandetails'];
                echo "$key; keyscandetails=$keyScanDetails<br>";

                //Delete any existing efficiency record for this scan
                $rdelete = $this->db_pull->qdelete($keyScanDetails, $this->fc);

                $beameff = new GenericTable;
                $beameff-> NewRecord("BeamEfficiencies","keyBeamEfficiencies",$this->fc,'fkFacility');
                $beameff-> SetValue("fkScanDetails", $keyScanDetails);
                $beameff-> SetValue("eff_output_file", $ini_filename);
                $beameff-> SetValue("pointing_angles_plot", $pointing_angles_plot);
                $beameff-> SetValue("software_version", $software_version);

                global $errorReportSettingsNo_E_NOTICE;
                global $errorReportSettingsNormal;
                error_reporting($errorReportSettingsNo_E_NOTICE);

                $beameff-> SetValue("tilt", $ini_array[$key]['tilt']);
                $beameff-> SetValue("f", $ini_array[$key]['f']);
                $beameff-> SetValue("type", $ini_array[$key]['type']);
                $beameff-> SetValue("tilt", $ini_array[$key]['tilt']);
                $beameff-> SetValue("ifatten", $ini_array[$key]['ifatten']);
                $beameff-> SetValue("eta_spillover", $ini_array[$key]['eta_spillover']);
                $beameff-> SetValue("eta_taper", $ini_array[$key]['eta_taper']);
                $beameff-> SetValue("eta_illumination", $ini_array[$key]['eta_illumination']);
                $beameff-> SetValue("ff_xcenter", $ini_array[$key]['ff_xcenter']);
                $beameff-> SetValue("ff_ycenter", $ini_array[$key]['ff_ycenter']);
                $beameff-> SetValue("az_nominal", $ini_array[$key]['az_nominal']);
                $beameff-> SetValue("el_nominal", $ini_array[$key]['el_nominal']);
                $beameff-> SetValue("nf_xcenter", $ini_array[$key]['nf_xcenter']);
                $beameff-> SetValue("nf_ycenter", $ini_array[$key]['nf_ycenter']);
                $beameff-> SetValue("max_ff_amp_db", $ini_array[$key]['max_ff_amp_db']);
                $beameff-> SetValue("max_nf_amp_db", $ini_array[$key]['max_nf_amp_db']);
                $beameff-> SetValue("delta_x", $ini_array[$key]['delta_x']);
                $beameff-> SetValue("delta_y", $ini_array[$key]['delta_y']);
                $beameff-> SetValue("delta_z", $ini_array[$key]['delta_z']);
                $beameff-> SetValue("eta_phase", $ini_array[$key]['eta_phase']);
                $beameff-> SetValue("ampfit_amp", $ini_array[$key]['ampfit_amp']);
                $beameff-> SetValue("ampfit_width_deg", $ini_array[$key]['ampfit_width_deg']);
                $beameff-> SetValue("ampfit_u_off", $ini_array[$key]['ampfit_u_off']);
                $beameff-> SetValue("ampfit_v_off", $ini_array[$key]['ampfit_v_off']);
                $beameff-> SetValue("ampfit_d_0_90", $ini_array[$key]['ampfit_d_0_90']);
                $beameff-> SetValue("ampfit_d_45_135", $ini_array[$key]['ampfit_d_45_135']);
                $beameff-> SetValue("plot_copol_nfamp", $ini_array[$key]['plot_copol_nfamp']);
                $beameff-> SetValue("plot_copol_nfphase", $ini_array[$key]['plot_copol_nfphase']);
                $beameff-> SetValue("plot_copol_ffamp", $ini_array[$key]['plot_copol_ffamp']);
                $beameff-> SetValue("plot_copol_ffphase", $ini_array[$key]['plot_copol_ffphase']);
                $beameff-> SetValue("plot_xpol_nfamp", $ini_array[$key]['plot_xpol_nfamp']);
                $beameff-> SetValue("plot_xpol_nfphase", $ini_array[$key]['plot_xpol_nfphase']);
                $beameff-> SetValue("plot_xpol_ffamp", $ini_array[$key]['plot_xpol_ffamp']);
                $beameff-> SetValue("plot_xpol_ffphase", $ini_array[$key]['plot_xpol_ffphase']);
                $beameff-> SetValue("datetime", $ini_array[$key]['datetime']);
                $beameff-> SetValue("nf", $ini_array[$key]['nf']);
                $beameff-> SetValue("ff", $ini_array[$key]['ff']);
                $beameff-> SetValue("nominal_z_offset", $ini_array[$key]['nominal_z_offset']);
                $beameff-> SetValue("eta_tot_np", $ini_array[$key]['eta_tot_np']);
                $beameff-> SetValue("eta_pol", $ini_array[$key]['eta_pol']);
                $beameff-> SetValue("eta_tot_nd", $ini_array[$key]['eta_tot_nd']);
                $beameff-> SetValue("defocus_efficiency", $ini_array[$key]['defocus_efficiency']);
                $beameff-> SetValue("total_aperture_eff", $ini_array[$key]['total_aperture_eff']);
                $beameff-> SetValue("shift_from_focus_mm", $ini_array[$key]['shift_from_focus_mm']);
                $beameff-> SetValue("subreflector_shift_mm", $ini_array[$key]['subreflector_shift_mm']);
                $beameff-> SetValue("defocus_efficiency_due_to_moving_the_subreflector", $ini_array[$key]['defocus_efficiency_due_to_moving_the_subreflector']);

                error_reporting($errorReportSettingsNormal);

                //Get pol of the 180 scan
                $sd180 = new ScanDetails();
                $sd180->Initialize_ScanDetails($keyScanDetails,$this->fc);
                $beameff-> SetValue("pol",$sd180->GetValue('pol'));
                unset($sd180);

                $oldurl =  $beameff->GetValue("pointing_angles_plot");
                $newurl = $main_url_directory . substr($oldurl, stripos($oldurl, "eff/") , -1) .  "g";
                $beameff->SetValue("pointing_angles_plot", $newurl);
                $beameff->Update();
                unset($beameff);
            }
        }
        $this->ReplacePlotURLs();
        echo "done uploading...<br>";
    }

    function DisplayEffReport() {
        $this->DisplayData();
        $this->Display_ScanInformation();
        $this->Display_SetupParameters();

        echo "<table width = '800'>";
        echo "<tr><td>";
        $this->Display_PointingAngles();
        echo "</td><td>";
        $this->Display_ApertureEff();
        echo "</td></tr>";
        echo "<tr><td>";
        $this->Display_TaperEff();
        echo "</td><td>";
        $this->Display_PhaseEff();
        echo "</td><td>";
        $this->Display_SpilloverEff();
        echo "</td></tr>";

        echo "</table>";

        $this->Display_PolEff();
        $this->Display_DefocusEff();

        $this->Display_PointingAngleDiff();
        $this->Display_PointingAngleDiff();
        $this->Display_PointingAngleDiff();
        $this->Display_PointingAngleDiff();
        $this->Display_PointingAngleDiff();
        $this->Display_Squint();
        $this->Display_AmpFit();
        $this->Display_PointingAnglesPlot();
        $this->ReplacePlotURLs();
        $this->Display_AllAmpPhasePlots();
        $this->Display_SoftwareVersions();
    }

    private function rrmdir($dir) {
        // TODO: this helper function was used but commented out in MakeInputFile().  Made private; Delete?
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir")
                        rrmdir($dir."/".$object);
                    else
                        unlink($dir."/".$object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    function Display_PointingAngles() {

        $nomAZ = @mysql_result($rn,0,0);
        $nomEL = @mysql_result($rn,0,1);
        //Get nominal Az, El
        $sd = new ScanDetails();
        $nomAZ = $nomEL = 0;
        $sd -> GetNominalAnglesDB($this->band, $nomAZ, $nomEL);

        echo "<div style = 'width:200px'><table id = 'table1'>";

        echo "<tr class='alt'><th colspan = 5>Pointing Angles Band $this->band <i><br>(Nominal: $nomAZ, $nomEL)</i></th></tr>";
        echo "<tr>
        <th>RF GHz</th>
        <th>pol</th>
        <th>Elevation</th>
        <th>AZ Center</th>
        <th>EL Center</th>
        </tr>";

        for ($scanSetIdx = 0; $scanSetIdx < $this->NumberOfScanSets; $scanSetIdx++) {
            echo "<tr>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            $x = round($this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('ff_xcenter'),4);
            $y = round($this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('ff_ycenter'),4);

            echo "<td>$x</td>";
            echo "<td>$y</td></tr>";

            echo "<tr>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            $x = round($this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('ff_xcenter'),4);
            $y = round($this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('ff_ycenter'),4);

            echo "<td>$x</td>";
            echo "<td>$y</td></tr>";
        }
        //Meas SW Ver
        echo "<tr><td colspan='5'><font size='-1'><i>Meas. software version " . $this->scansets[0]->tdh->GetValue('Meas_SWVer')
            . ", Class.eff version " . $this->software_version . "</i></font></td></tr>";
        echo "</table></div>";
    }

    function Display_ApertureEff() {
        echo "<div style = 'width:300px'><table id = 'table1' border='1'>";

        echo "<tr class='alt'><th colspan = 4>Aperture Efficiency Band $this->band</th></tr>";
        echo "<tr>
            <th>RF GHz</th>
            <th>pol</th>
            <th>Elevation</th>
            <th>Aperture Eff</th>
            </tr>";

        for ($scanSetIdx = 0; $scanSetIdx < $this->NumberOfScanSets; $scanSetIdx++) {
            echo "<tr>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            $ae = round(100 * $this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('eta_tot_nd'),2);
            if ($ae < 80) {
                echo "<td><font color = '#ff0000'>$ae</font></td>";
            } else {
                echo "<td>$ae</td>";
            }
            echo "<tr>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            $ae = round(100 * $this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('eta_tot_nd'),2);
            if ($ae < 80) {
                echo "<td><font color = '#ff0000'>$ae</font></td>";
            } else {
                echo "<td>$ae</td>";
            }
        }
        //Meas SW Ver
        echo "<tr><td colspan='4'><font size='-1'><i>Meas. software version " . $this->scansets[0]->tdh->GetValue('Meas_SWVer')
            . ", Class.eff version " . $this->software_version . "</i></font></td></tr>";
        echo "</table></div><br><br>";
    }

    function Display_TaperEff() {
        echo "<div style = 'width:300px'><table id = 'table1' border='1' border='1'>";
        echo "<tr class='alt'><th colspan = 4>Taper Efficiency Band $this->band</th></tr>";
        echo "<tr>
            <th>RF GHz</th>
            <th>pol</th>
            <th>Elevation</th>
            <th>Amp Taper Eff</th>
            </tr>";


        for ($scanSetIdx = 0; $scanSetIdx < $this->NumberOfScanSets; $scanSetIdx++) {
            echo "<tr>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            echo "<td>" . round(100 * $this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('eta_taper'),2) . "</td>";

            echo "<tr>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            echo "<td>" . round(100 * $this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('eta_taper'),2) . "</td>";
        }
        //Meas SW Ver
        echo "<tr><td colspan='4'><font size='-1'><i>Meas. software version " . $this->scansets[0]->tdh->GetValue('Meas_SWVer')
            . ", Class.eff version " . $this->software_version . "</i></font></td></tr>";
        echo "</table></div><br><br>";
    }

    function Display_PhaseEff() {
        echo "<div style = 'width:300px'><table id = 'table1' border='1' border='1'>";
        echo "<tr class='alt'><th colspan = 4>Phase Efficiency Band $this->band</th></tr>";
        echo "<tr>
        <th>RF GHz</th>
        <th>pol</th>
        <th>Elevation</th>
        <th>Phase Eff</th>
        </tr>";

        for ($scanSetIdx = 0; $scanSetIdx < $this->NumberOfScanSets; $scanSetIdx++) {
            echo "<tr>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            echo "<td>" . round(100 * $this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('eta_phase'),2) . "</td>";

            echo "<tr>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            echo "<td>" . round(100 * $this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('eta_phase'),2) . "</td>";
        }
        //Meas SW Ver
        echo "<tr><td colspan='4'><font size='-1'><i>Meas. software version " . $this->scansets[0]->tdh->GetValue('Meas_SWVer')
        . ", Class.eff version " . $this->software_version . "</i></font></td></tr>";
        echo "</table></div><br><br>";
    }

    function Display_SpilloverEff() {
        echo "<div style = 'width:300px'><table id = 'table1' border='1'>";
        echo "<tr class='alt'><th colspan = 4>Spillover Efficiency Band $this->band</th></tr>";
        echo "<tr>
            <th>RF GHz</th>
            <th>pol</th>
            <th>Elevation</th>
            <th>Spillover Eff</th>
            </tr>";

        for ($scanSetIdx = 0; $scanSetIdx < $this->NumberOfScanSets; $scanSetIdx++) {
            echo "<tr>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            echo "<td>" . round(100 * $this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('eta_spillover'),2) . "</td>";

            echo "<tr>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            echo "<td>" . round(100 * $this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('eta_spillover'),2) . "</td>";
        }
        //Meas SW Ver
        echo "<tr><td colspan='4'><font size='-1'><i>Meas. software version " . $this->scansets[0]->tdh->GetValue('Meas_SWVer')
            . ", Class.eff version " . $this->software_version . "</i></font></td></tr>";
        echo "</table></div><br><br>";
    }

    function Display_SoftwareVersions() {
        echo "<div style = 'width:300px'><table id = 'table1' border='1'>";
        echo "<tr>
            <th>Software</th>
            <th>Version</th>
            </tr>";

        echo "<tr>";
        echo "<td>Beam Efficiency Calculator (beameff_64.exe)</td>";
        echo "<td>" . $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('software_version') . "</td>";

        echo "<tr>";
        echo "<td>class.eff.php</td>";
        echo "<td>" . $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('software_version_class_eff') . "</td>";

        echo "</tr>";
        echo "</table></div><br><br>";
    }

    function Display_PolEff() {
        echo "<div style = 'width:600px'><table id = 'table1' border='1'>";

        echo "<tr class='alt'><th colspan = 7>Polarization Efficiency Band $this->band</th></tr>";
        echo "<tr>
            <th>RF GHz</th>
            <th>pol</th>
            <th>Elevation</th>
            <th>Peak Cross dB</th>
            <th>eta pol + spill</th>
            <th>Polarization Eff</th>
            </tr>";

        for ($scanSetIdx = 0; $scanSetIdx < $this->NumberOfScanSets; $scanSetIdx++) {
            // if the polarization efficiency is less than the value calculated for $p0spec/$p1spec, display it as red:
            $rf = floatval($this->scansets[$scanSetIdx]->GetValue('f'));
            $p0spec = $p1spec = 0.0;

            $spec = $this->new_spec->getSpecs('beameff', $this->band);

            if (count($spec['rf_cond']) > 1) {
                $p0spec = $p1spec = $spec['pspec'];
                for ($i=0; $i<count($spec['rf_cond']); $i+=2) {
                    if ($spec['rf_cond'][$i] <= $rf && $rf <= $spec['rf_cond'][$i+1]) {
                        $p0spec = $spec['rf_val'][$i];
                        $p1spec = $spec['rf_val'][$i+1];
                    }
                }
            } else {
                $p0spec = $p1spec = $spec['pspec'];
            }

            echo "<tr>";
            echo "<td>" . $rf . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_xpol_pol0->BeamEfficencies->GetValue('max_dbdifference'),2) . "</td>";
            echo "<td>" . round(100 * $this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('eta_tot_np'),2) . "</td>";

            $pe = round(100 * $this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('eta_pol'),2);
            if ($pe < $p0spec)
                echo "<td><font color ='#ff0000'>$pe</font></td>";
            else
                echo "<td>$pe</td>";

            echo "<tr>";
            echo "<td>" . $rf . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_xpol_pol1->BeamEfficencies->GetValue('max_dbdifference'),2) . "</td>";
            echo "<td>" . round(100 * $this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('eta_tot_np'),2) . "</td>";

            $pe = round(100 * $this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('eta_pol'),2);
            if ($pe < $p1spec)
                echo "<td><font color ='#ff0000'>$pe</font></td>";
            else
                echo "<td>$pe</td>";
        }
        //Meas SW Ver
        echo "<tr><td colspan='6'><font size='-1'><i>Meas. software version " . $this->scansets[0]->tdh->GetValue('Meas_SWVer')
            . ", Class.eff version " . $this->software_version . "</i></font></td></tr>";
        echo "</table></div><br><br>";
    }

    function Display_DefocusEff() {
         echo "<div style = 'width:700px'><table id = 'table1' border='1'>";

         echo "<tr class='alt'><th colspan = 8>Focus Efficiency Band $this->band</th></tr>";
         echo "<tr>
             <th>RF GHz</th>
             <th>pol</th>
             <th>Elevation</th>
             <th>Shift from Focus (mm)</th>
             <th>Eff. after subreflector shift</th>
             <th>Subreflector Shift (mm)</th>
             <th>Eff. if subreflector not shifted</th>
             </tr>";

         for ($scanSetIdx = 0; $scanSetIdx < $this->NumberOfScanSets; $scanSetIdx++) {
            echo "<tr>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('shift_from_focus_mm'),2) . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('defocus_efficiency'),2) . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('subreflector_shift_mm'),2) . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('defocus_efficiency_due_to_moving_the_subreflector'),2) . "</td>";

            echo "<tr>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('shift_from_focus_mm'),2) . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('defocus_efficiency'),2) . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('subreflector_shift_mm'),2) . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('defocus_efficiency_due_to_moving_the_subreflector'),2) . "</td>";
        }
        //Meas SW Ver
        echo "<tr><td colspan='7'><font size='-1'><i>Meas. software version " . $this->scansets[0]->tdh->GetValue('Meas_SWVer')
         . ", Class.eff version " . $this->software_version . "</i></font></td></tr>";
        echo "</table></div><br><br>";
    }

    function Display_PointingAngleDiff() {
        echo "<div style = 'width:200px'><table id = 'table1' border='1'>";
        echo "<tr class='alt'><th colspan = 4>Pointing Difference between <br>Pol 0, Pol 1 Band $this->band</th></tr>";
        echo "<tr>
            <th>RF GHz</th>
            <th>Elevation</th>
            <th>AZ Difference (deg)</th>
            <th>EL Difference (deg)</th>
            </tr>";

        for ($scanSetIdx = 0; $scanSetIdx < $this->NumberOfScanSets; $scanSetIdx++) {
            echo "<tr>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            $az_diff = round($this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('ff_xcenter') - $this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('ff_xcenter'),2);
            $el_diff = round($this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('ff_ycenter') - $this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('ff_ycenter'),2);
            echo "<td>$az_diff</td>";
            echo "<td>$el_diff</td></tr>";
        }
        //Meas SW Ver
        echo "<tr><td colspan='4'><font size='-1'><i>Meas. software version " . $this->scansets[0]->tdh->GetValue('Meas_SWVer')
            . ", Class.eff version " . $this->software_version . "</i></font></td></tr>";
        echo "</table></div>";
    }

    function Display_Squint() {
        $thirdscanpresent = 1;
        if ($this->scansets[0]->keyId_180_scan == '') {
            $thirdscanpresent = 0;
        }
        if ($thirdscanpresent != 1) {
            echo "<div style = 'width:500px;background-color:#ff0000'>";
        }

        echo "<div style = 'width:400px'><table id = 'table1' border='1'>";
        echo "<tr><td>Squint algorithm is described <a href='https://safe.nrao.edu/wiki/bin/view/ALMA/BeamSquintFromSingleScan#correctionProcedure'>here.</a></td></tr>";
        echo "</table></div>";

        echo "<div style = 'width:400px'><table id = 'table1' border='1'>";

        echo "<tr class='alt'><th colspan = 4>Beam Squint Band $this->band</th></tr>";
        echo "<tr>
            <th>RF GHz</th>
            <th>Elevation</th>
            <th>Squint (%FPBW)</th>
            <th>squint (arc seconds)</th>
            </tr>";

        for ($scanSetIdx = 0; $scanSetIdx < $this->NumberOfScanSets; $scanSetIdx++) {
            echo "<tr>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";

            $s = round($this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('squint'),2);
            $sas = round($this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('squint_arcseconds'),2);
            if ($s > 10) {
                echo "<td><font color='#ff0000'>$s</font></td>";
                echo "<td><font color='#ff0000'>$sas</font></td>";
            } else {
                echo "<td>$s</td>";
                echo "<td>$sas</td>";
            }
        }
        //Meas SW Ver
        echo "<tr><td colspan='4'><font size='-1'><i>Meas. software version " . $this->scansets[0]->tdh->GetValue('Meas_SWVer')
            . ", Class.eff version " . $this->software_version . "</i></font></td></tr>";
        echo "</table></div><br>";
        if ($thirdscanpresent != 1) {
            echo "<font size='+2'><b>WARNING <br>Third scan not present. Squint value is incorrect.</b></font></div>";
        }
    }

    function Display_PhaseCenterOffset() {
        echo "<div style = 'width:500px'><table id = 'table1' border='1'>";
        echo "<tr class='alt'><th colspan = 6>Phase Center Offset Band $this->band</th></tr>";
        echo "<tr><th colspan='6'>Uncorrected phase centers (mm): </th></tr>";
        echo "<tr>
            <th>RF GHz</th>
            <th>pol</th>
            <th>Elevation</th>
            <th>X</th>
            <th>Y</th>
            <th>Z</th>
            </tr>";

        // get the x and y correction factor to be applied to x90, y90 for computing difference below:
        $x_corr = $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('x_corr');
        $y_corr = $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('y_corr');

        if ($this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('x0') != 0) {
            //Display uncorrected x0, y0, x90, y90 for the case where the 180 scan was pol 0.
            echo "<tr>";
            echo "<td>" . $this->scansets[0]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[0]->Scan_copol_pol0->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[0]->GetValue('tilt') . "</td>";
            echo "<td>" . round($this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('x0'),2) . "</td>";
            echo "<td>" . round($this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('y0'),2) . "</td>";
            echo "<td>" . round($this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('delta_z'),2) . "</td></tr>";

            echo "<tr>";
            echo "<td>" . $this->scansets[0]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[0]->Scan_copol_pol1->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[0]->GetValue('tilt') . "</td>";
            echo "<td>" . round($this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('x90'),2) . "</td>";
            echo "<td>" . round($this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('y90'),2) . "</td>";
            echo "<td>" . round($this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('delta_z'),2) . "</td></tr>";

            // POL0 - POl1 used to find phase center differences, apply correction to x90, y90 first:
            $x_difference = $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('x0')
                          - ($this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('x90') + $x_corr);

            $y_difference = $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('y0')
                          - ($this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('y90') + $y_corr);

        } elseif ($this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('x0') != 0) {
            //Display uncorrected x90, y90, x0, y0 for the case where the 180 scan was pol 1.
            echo "<tr>";
            echo "<td>" . $this->scansets[0]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[0]->Scan_copol_pol0->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[0]->GetValue('tilt') . "</td>";
            echo "<td>" . round($this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('x90'),2) . "</td>";
            echo "<td>" . round($this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('y90'),2) . "</td>";
            echo "<td>" . round($this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('delta_z'),2) . "</td></tr>";

            echo "<tr>";
            echo "<td>" . $this->scansets[0]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[0]->Scan_copol_pol1->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[0]->GetValue('tilt') . "</td>";
            echo "<td>" . round($this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('x0'),2) . "</td>";
            echo "<td>" . round($this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('y0'),2) . "</td>";
            echo "<td>" . round($this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('delta_z'),2) . "</td></tr>";

            //POL1 - POl0 used to find phase center differences, apply correction to x90, y90 first:
            $x_difference = $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('x0')
                          - ($this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('x90') + $x_corr);

            $y_difference = $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('y0')
                          - ($this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('y90') + $y_corr);
        }

        echo "<tr><th colspan='3'>Phase center correction:</th>";
        echo "<td>" . round($x_corr, 2) . "</td>";
        echo "<td>" . round($y_corr, 2) . "</td>";
        echo "<th></th></tr>";

        //x_diff, y_diff from https://safe.nrao.edu/wiki/bin/view/ALMA/BeamSquintFromSingleScan#correctionProcedure
        echo "<tr><th colspan='3'>Corrected difference between phase centers:</th>";
        echo "<td>" . round($x_difference, 2) . "</td>";
        echo "<td>" . round($y_difference, 2) . "</td>";
        echo "<th></th></tr>";

        //echo "<tr class = 'alt'><th colspan='6'></th></tr>";
        echo "<tr><th colspan='5'>Distance between pol 0 and pol 1 phase centers (mm):</th>";
        echo"<td>" . round($this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('DistanceBetweenBeamCenters'),2) . "</td></tr>";
        echo "</tr>";
        echo "<tr><td colspan='6'><font size='-1'><i>Meas. software version " . $this->scansets[0]->tdh->GetValue('Meas_SWVer')
             . ", Class.eff version " . $this->software_version . "</i></font></td></tr>";
        echo "</table></div><br><br>";
    }

    function Display_AmpFit() {
        echo "<div style='width:800px'>";
        echo "<table id = 'table1' border='1'>";
        echo "<tr class='alt'><th colspan = 10>Amp Fit Parameters Band $this->band</th></tr>";
        echo "<tr>
            <th>RF GHz</th>
            <th>pol</th>
            <th>Elevation</th>
            <th>Amp</th>
            <th>Width (deg)</th>
            <th>u_off(deg)</th>
            <th>v_off(deg)</th>
            <th>d 0-90</th>
            <th>d 45-135</th>
            <th>edge (dB)</th>
            </tr>";

        for ($scanSetIdx = 0; $scanSetIdx < $this->NumberOfScanSets; $scanSetIdx++) {
            echo "<tr>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('ampfit_amp'),2) . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('ampfit_width_deg'),2) . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('ampfit_u_off'),2) . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('ampfit_v_off'),2) . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('ampfit_d_0_90'),2) . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('ampfit_d_45_135'),2) . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('ampfit_edge_db'),2) . "</td>";

            echo "<tr>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('ampfit_amp'),2) . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('ampfit_width_deg'),2) . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('ampfit_u_off'),2) . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('ampfit_v_off'),2) . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('ampfit_d_0_90'),2) . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('ampfit_d_45_135'),2) . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('ampfit_edge_db'),2) . "</td>";
        }
        echo "</table><br><br>";
    }

    function Display_ScanInformation() {
        echo "<div style='width:950px'>";
        echo "<table id = 'table1' border='1'>";

        echo "<tr class = 'alt'><th colspan = 8>Scan Information Band $this->band    (". $this->scansets[0]->GetValue('TS')   . ")</th></tr>";
        echo "<tr>
            <th>RF GHz</th>
            <th>pol</th>
            <th>Elevation</th>
            <th>Date/Time</th>
            <th>File Name</th>
            <th>Amp/Phase Drift</th>
            <th colspan = 2>Export CSV</th>
            </tr>";

        $count = 0;
        for ($scanSetIdx = 0; $scanSetIdx < $this->NumberOfScanSets; $scanSetIdx++) {
            $count +=1;
            $trclass = '';
            if ($count % 2 == 0) {
                  $trclass = 'alt';
            }
            echo "<tr class='$trclass'>";
            echo "<td width = '30px'>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td width = '30px'>" . $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('pol') . "</td>";
            echo "<td width = '80px'>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            echo "<td width ='200px'>" . strftime("%a",strtotime($this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('TS'))) . " "
                                       . $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('TS') . "</td>";

            $nsi_filename = $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('nsi_filename');
            $nameOffset = strripos($nsi_filename, "band");    // find last occurance, case-insensitive
            $nsi_filename = substr($nsi_filename, $nameOffset);

            echo "<td>" . $nsi_filename ."</a></td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('ampdrift'),2) . " dB, "
                        . round($this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('phasedrift'),2) . " deg</td>";

            echo "<td><a href='export_to_csv.php?fc=" . $this->fc . "&ssdid="
                . $this->scansets[$scanSetIdx]->Scan_copol_pol0->keyId . "'>copol</a></td>";

            echo "<td><a href='export_to_csv.php?fc=" . $this->fc . "&ssdid="
                . $this->scansets[$scanSetIdx]->Scan_xpol_pol0->keyId . "'>xpol</a></td></tr>";

            $count +=1;
            $trclass = '';
            if ($count % 2 == 0) {
                $trclass = 'alt';
            }
            echo "<tr class='$trclass'>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            echo "<td width='160px'>"
                . strftime("%a",strtotime($this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('TS'))) . " "
                . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('TS') . "</td>";

            $nsi_filename = $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('nsi_filename');
            $nameOffset = strripos($nsi_filename, "band");    // find last occurance, case-insensitive
            $nsi_filename = substr($nsi_filename, $nameOffset);

            echo "<td>" . $nsi_filename ."</a></td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('ampdrift'),2) . " dB, "
                        . round($this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('phasedrift'),2) . " deg</td>";

            echo "<td><a href='export_to_csv.php?fc=" . $this->fc . "&ssdid="
                . $this->scansets[$scanSetIdx]->Scan_copol_pol1->keyId . "'>copol</a></td>";

            echo "<td><a href='export_to_csv.php?fc=" . $this->fc . "&ssdid="
                . $this->scansets[$scanSetIdx]->Scan_xpol_pol1->keyId . "'>xpol</a></td></tr>";
        }
        //Meas SW Ver
        echo "<tr><td colspan='8'><font size='-1'><i>Meas. software version " . $this->scansets[0]->tdh->GetValue('Meas_SWVer')
            . ", Class.eff version " . $this->software_version . "</i></font></td></tr>";
        echo "</table></div><br><br>";
    }

    function Display_SetupParameters() {
        echo "<div style='width:950px'>";
        echo "<table id = 'table1' border='1'>";

        echo "<tr class = 'alt'><th colspan = 6>Scan Setup Parameters Band $this->band</th></tr>";
        echo "<tr>
            <th>RF GHz</th>
            <th>pol</th>
            <th>Elevation</th>
            <th>File Name</th>
            <th>IF Atten <br>(Co, Cross)</th>
            <th>Source Rotation Angle</th>
            </tr>";

        for ($scanSetIdx = 0; $scanSetIdx < $this->NumberOfScanSets; $scanSetIdx++) {

            echo "<tr>";
            echo "<td width = '50px'>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td width = '50px'>" . $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('pol') . "</td>";
            echo "<td width = '50px'>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";

            $nsi_filename = $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('nsi_filename');
            $nameOffset = strripos($nsi_filename, "band");    // find last occurance, case-insensitive
            $nsi_filename = substr($nsi_filename, $nameOffset);
            echo "<td align = 'left'>" . $nsi_filename ."</td>";

            echo "<td width = '80px'>" . $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('ifatten') . ", " . $this->scansets[$scanSetIdx]->Scan_xpol_pol0->GetValue('ifatten') . "</td>";
            echo "<td width = '50px'>" . $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('SourceRotationAngle') . "</td>";
            echo "<tr>";

            echo "<tr class='alt'>";
            echo "<td width = '50px'>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td width = '50px'>" . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('pol') . "</td>";
            echo "<td width = '50px'>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";

            $nsi_filename = $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('nsi_filename');
            $nameOffset = strripos($nsi_filename, "band");    // find last occurance, case-insensitive
            $nsi_filename = substr($nsi_filename, $nameOffset);
            echo "<td align = 'left'>" . $nsi_filename ."</td>";

            echo "<td width = '80px'>" . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('ifatten') . ", " . $this->scansets[$scanSetIdx]->Scan_xpol_pol1->GetValue('ifatten') . "</td>";
            echo "<td width = '50px'>" . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('SourceRotationAngle') . "</td>";
            echo "<tr>";
            echo "<tr>";
            echo "<td width = '50px'>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td width = '50px'>" . $this->scansets[$scanSetIdx]->Scan_180->GetValue('pol') . "</td>";
            echo "<td width = '50px'>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";

            $nsi_filename = $this->scansets[$scanSetIdx]->Scan_180->GetValue('nsi_filename');
            $nameOffset = strripos($nsi_filename, "band");    // find last occurance, case-insensitive
            $nsi_filename = substr($nsi_filename, $nameOffset);
            echo "<td align = 'left'>" . $nsi_filename ."<font color = '#ff0000' size='-2'> * used to correct phase centers</font></td>";

            echo "<td width = '100px'>" . $this->scansets[$scanSetIdx]->Scan_180->GetValue('ifatten') . ", " . $this->scansets[$scanSetIdx]->Scan_xpol_pol1->GetValue('ifatten') . "</td>";
            echo "<td width = '50px'>" . $this->scansets[$scanSetIdx]->Scan_180->GetValue('SourceRotationAngle') . "</td>";
            echo "<tr>";
        }
        //Meas SW Ver
        echo "<tr><td colspan='6'><font size='-1'><i>Meas. software version " . $this->scansets[0]->tdh->GetValue('Meas_SWVer')
            . ", Class.eff version " . $this->software_version . "</i></font></td></tr>";
        echo "</table></div>";
    }

    function DisplayData() {
        if ($this->Processed != 1) {
            if ($this->ReadyToProcess == 1) {
                echo "<font size = '+1' color = '#33ff33'>READY TO PROCESS</b></font><br>";
                echo " Click 'Get Effs' to generate data and plots.";
            } else {
                echo "<font size = '+1' color = '#ff0033'>NOT READY TO PROCESS</b></font>";
            }
        }
        echo '<br><br>Notes:<br><textarea name = "notes" rows="15" cols="100">';
        echo stripslashes($this->scansets[0]->GetValue('notes'));
        echo '</textarea><br><br><br><br>';
    }

    function Display_PointingAnglesPlot() {
        echo "<img src='" . $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('pointing_angles_plot') . "'>";
        echo "<div style='height:50px;background:#E9FA89'>";
        echo "<br><font size='+1'>";
        echo "<br>The plot below shows pointing angles for the two scans used for squint (one of which is the third scan).<br><br>";
        echo "<br>Do not use the plot below in the PAI report.</font><br></div>";
        echo "<br><br><img src='" . $this->scansets[0]->Scan_180->BeamEfficencies->GetValue('pointing_angles_plot') . "'>";
    }

    function Display_AllAmpPhasePlots($pol = 'both', $nf_ff='both') {
        for ($scanSetIdx = 0; $scanSetIdx < $this->NumberOfScanSets; $scanSetIdx++) {
            $this->Display_AmpPhasePlots($this->scansets[$scanSetIdx], $pol, $nf_ff);
        }
    }

    function Display_AmpPhasePlots($scanset, $inpol = 'both', $nf_ff='both') {
        $f = $scanset->GetValue('f');
        $tilt = $scanset->GetValue('tilt');

        if ($inpol == 0 || $inpol == 'both') {
            if ($nf_ff == 'nf' || $nf_ff == 'both') {
                // display Pol0 NF plots:

                echo "<table id = 'table8'>";
                echo "<tr class='alt'><th colspan = 2>Pol 0 $f GHz, Elevation $tilt</th></tr>";
                echo "<tr class = 'alt'>
                    <td style='border-bottom:solid 1px'>Nearfield Amplitude</td>
                    <td style='border-bottom:solid 1px'>Nearfield Phase</td>
                    </tr>";

                echo "<tr>";
                echo "<td><img src='" . $scanset->Scan_copol_pol0->BeamEfficencies->GetValue('plot_copol_nfamp') . "'>";
                $this->Display_ScanSetDBInfo($scanset,$scanset->Scan_copol_pol0);
                echo "</td>";

                echo "<td><img src='" . $scanset->Scan_copol_pol0->BeamEfficencies->GetValue('plot_copol_nfphase') . "'>";
                $this->Display_ScanSetDBInfo($scanset,$scanset->Scan_copol_pol0);
                echo "</td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td><img src='" . $scanset->Scan_xpol_pol0->BeamEfficencies->GetValue('plot_xpol_nfamp') . "'>";
                $this->Display_ScanSetDBInfo($scanset,$scanset->Scan_xpol_pol0);
                echo "</td>";

                echo "<td><img src='" . $scanset->Scan_xpol_pol0->BeamEfficencies->GetValue('plot_xpol_nfphase') . "'>";
                $this->Display_ScanSetDBInfo($scanset,$scanset->Scan_xpol_pol0);
                echo "</td>";
                echo "</tr>";
                echo "</table>";

            } elseif ($nf_ff == 'ff' || $nf_ff == 'both') {
                // display Pol0 FF plots:

                echo "<table id = 'table8'>";
                echo "<tr class='alt'><th colspan = 2>Pol 0 $f GHz, Elevation $tilt</th></tr>";
                echo "<tr class = 'alt'>
                    <td style='border-bottom:solid 1px'>Farfield Amplitude</td>
                    <td style='border-bottom:solid 1px'>Farfield Phase</td>
                    </tr>";

                echo "<tr>";
                echo "<td><img src='" . $scanset->Scan_copol_pol0->BeamEfficencies->GetValue('plot_copol_ffamp') . "'>";
                $this->Display_ScanSetDBInfo($scanset,$scanset->Scan_copol_pol0);
                echo "</td>";

                echo "<td><img src='" . $scanset->Scan_copol_pol0->BeamEfficencies->GetValue('plot_copol_ffphase') . "'>";
                $this->Display_ScanSetDBInfo($scanset,$scanset->Scan_copol_pol0);
                echo "</td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td><img src='" . $scanset->Scan_xpol_pol0->BeamEfficencies->GetValue('plot_xpol_ffamp') . "'>";
                $this->Display_ScanSetDBInfo($scanset,$scanset->Scan_xpol_pol0);
                echo "</td>";

                echo "<td><img src='" . $scanset->Scan_xpol_pol0->BeamEfficencies->GetValue('plot_xpol_ffphase') . "'>";
                $this->Display_ScanSetDBInfo($scanset,$scanset->Scan_xpol_pol0);
                echo "</td>";
                echo "</tr>";
                echo "</table><br><br><br>";
            }

        } elseif ($inpol == 1 || $inpol == 'both') {
            if ($nf_ff == 'nf' || $nf_ff == 'both') {
                // display Pol1 NF plots:

                echo "<table id = 'table8'>";
                echo "<tr class='alt'><th colspan = 2>Pol 1 $f GHz, Elevation $tilt</th></tr>";
                echo "<tr class = 'alt'>
                    <td style='border-bottom:solid 1px'>Nearfield Amplitude</td>
                    <td style='border-bottom:solid 1px'>Nearfield Phase</td>
                    </tr>";

                echo "<tr>";
                echo "<td><img src='" . $scanset->Scan_copol_pol1->BeamEfficencies->GetValue('plot_copol_nfamp') . "'>";
                $this->Display_ScanSetDBInfo($scanset,$scanset->Scan_copol_pol1);
                echo "</td>";

                echo "<td><img src='" . $scanset->Scan_copol_pol1->BeamEfficencies->GetValue('plot_copol_nfphase') . "'>";
                $this->Display_ScanSetDBInfo($scanset,$scanset->Scan_copol_pol1);
                echo "</td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td><img src='" . $scanset->Scan_xpol_pol1->BeamEfficencies->GetValue('plot_xpol_nfamp') . "'>";
                $this->Display_ScanSetDBInfo($scanset,$scanset->Scan_xpol_pol1);
                echo "</td>";

                echo "<td><img src='" . $scanset->Scan_xpol_pol1->BeamEfficencies->GetValue('plot_xpol_nfphase') . "'>";
                $this->Display_ScanSetDBInfo($scanset,$scanset->Scan_xpol_pol1);
                echo "</td>";
                echo "</tr>";
                echo "</table>";

            } elseif ($nf_ff == 'ff' || $nf_ff == 'both') {
                // display Pol1 FF plots:

                echo "<table id = 'table8'>";
                echo "<tr class='alt'><th colspan = 2>Pol 1 $f GHz, Elevation $tilt</th></tr>";
                echo "<tr class = 'alt'>
                    <td style='border-bottom:solid 1px'>Farfield Amplitude</td>
                    <td style='border-bottom:solid 1px'>Farfield Phase</td>
                    </tr>";

                echo "<tr>";
                echo "<td><img src='" . $scanset->Scan_copol_pol1->BeamEfficencies->GetValue('plot_copol_ffamp') . "'>";
                $this->Display_ScanSetDBInfo($scanset,$scanset->Scan_copol_pol1);
                echo "</td>";

                echo "<td><img src='" . $scanset->Scan_copol_pol1->BeamEfficencies->GetValue('plot_copol_ffphase') . "'>";
                $this->Display_ScanSetDBInfo($scanset,$scanset->Scan_copol_pol1);
                echo "</td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td><img src='" . $scanset->Scan_xpol_pol1->BeamEfficencies->GetValue('plot_xpol_ffamp') . "'>";
                $this->Display_ScanSetDBInfo($scanset,$scanset->Scan_xpol_pol1);
                echo "</td>";

                echo "<td><img src='" . $scanset->Scan_xpol_pol1->BeamEfficencies->GetValue('plot_xpol_ffphase') . "'>";
                $this->Display_ScanSetDBInfo($scanset,$scanset->Scan_xpol_pol1);
                echo "</td>";
                echo "</tr>";
                echo "</table><br><br><br>";
            }
        }
    }

    function Display_ScanSetDBInfo($scanset,$scandetails) {
        // This function has been empty (commented-out code) for a long time.   TODO: delete this and calls.
    }
}
?>