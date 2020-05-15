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
    var $scansets;              //Array of scan set detail objects
    var $ReadyToProcess;        //True if the scan sets here are ready to be post-processed

    private $fe_id;             //keyId of the Front End
    private $scanSetId;         //ScanSetDetails keyId
    private $scanSetFc;         //Facility code for ScanSetDetails and all other tables
    private $basedir;           //base directory for all efficiency files
    private $newbasedir;        //working directory for output files
    private $listingsdir;       //directory for temporary nf and ff listings
    private $eff_inputfile;     //full absolute path of input text file for efficiency program.
    private $outputdirectory;   //Output directory for efficiency plots
    private $eff_outputfile;    //Output file from calling BeamEff calculator
    private $beameff_exe;       //BeamEff calculator executable from config_main.php
    private $url_dir;           //Root of web URL for output files from config_main.php
    private $NumberOfScanSets;
    private $effBand;           //Cartridge band number for these results
    private $pointingOption;    //'nominal', 'actual', or '7meter'
    private $software_version_class_eff;    //version text for display
    private $software_version_analysis;     //version text for display
    private $pointingOption_analysis;       //pointingOption text for display
    private $GNUPLOT_path;      //Path to Gnuplot from config_main.php
    private $specsProvider;     //class Specifications

    public function __construct() {
        $this->software_version_class_eff = "1.3.1";
        $this->software_version_analysis = "";
        $this->pointingOption_analysis = "";

        /* Version history:
         * 1.3.1  Display defocus efficiency with 3 decimal places
         * 1.3.0  Add Export(), made most vars private and deleted unused vars.
         * 1.2.4  Remove dubious secondary defocus calculation.
         * 1.2.3  Display defocus effs as percent
         * 1.2.2  Export NF as well as FF plots.  FF axes labeled az, el.  Include Pol, RF, tilt, SrcRot
         * 1.2.1  Get phase center corrections x_corr, y_corr from pol1 if necessary.
         *        Formatting in Phase Center Offset table.
         * 1.2.0  Switch to BeamEff 2.0.2
         *        Removed ReplacePlotURLs() instead calling PlotPathToURL() inline.
         *        Remove unused initializer functions.  Removed dead code.  Marked methods private.
         *        Remove special handling for phase center correction and squint calculation.  BeamEff2 handles it now.
         *        TODO: Using table beameff x90, y90 to store corrected_x, corrected_y.  Need to alter table and code.
         *        Seting tdh_id in command file for Beameff2. Spread out nf/ff plots a bit.
         *        Fix 'Eff. calculations using' on Pointing Angles table to take in to account 'actual'.
         * 1.1.10 Removed writing 'keyscandetails' added writing 'scan_id' + fix for 180 scans.
         * 1.1.9  Displaying total_aperture_eff which includes defocus as Aperture Efficiency.
         *        Explicit table header: 'Amplitude Taper Efficiency'
         *        Display pol+spill equations with crossrefs in tables.
         *        Improved (?) page display formatting.
         * 1.1.8  Removed TICRA pol. and spill switch.  Always compute pol.eff on secondary.
         *        Fix 'eta pol + spill' was displaying wrong value, eta_tot_np.
         *        Uses ProbeZDistance from ScanDetails and passes it to Beameff as zdistance.
         * 1.1.7  Added switch for pol. and spill eff calculation using default or TICRA method.
         * 1.1.6  Added efficiency and squint calculation for ACA 7 meter antenna.
         * 1.1.5  Added selectable pointing option.
         * 1.1.4  Standardized software version string for all data tables, includes analysis.
         * 1.1.3  Fixed MakeOutputEnvironment to delete old files first.
         * 1.1.2  Added Display_PhaseEff()
         * 1.1.1  Added download for cross-pol .csv files.
         * 1.1.0  Uses database calls from dbCode/beameffdb.php
         * 1.0.23 Supressed E_NOTICE errors in the Upload...() functions
         * 1.0.22 Reduced number of copies of NominalAngles tables being consulted
         * 1.0.21 Fixed bugs in pol.eff display, comparing to specs
         * 1.0.20 MTM updated band 4 PolEff display per FEND-40.02.04.00-0236-C-CRE
         *        PolEff: modified band 8 display per Whyborn comment on AIVPNCR-24
         * 1.0.19 MTM no longer writing out Notes to input file for beameff_64.
         *        Equal sign and others were causing errors from parse_ini_file().
         * 1.0.18 MTM fix display of NSI filenames.
         *        Updates to phase center offsets table.
         *        Fix fonts in tables.
         *        Comments fixes from meeting with Todd and Saini.
         */

        require(site_get_config_main());

        $this->specsProvider = new Specifications();
        $this->db_pull = new BeamEffDB(site_getDbConnection());
        $this->basedir = $main_write_directory;
        $this->url_dir = $main_url_directory;
        $this->ReadyToProcess = 0;
        $this->beameff_exe = $beameff_64;
        $this->GNUPLOT_path = $GNUPLOT;
    }

    public function Initialize_eff_TDH($in_TDHId) {
        $in_fc = 40;
        $r = $this->db_pull->qTDH($in_TDHId, $in_fc);
        $ssid = ADAPT_mysqli_result($r,0,0);
        $this->Initialize_eff_SingleScanSet($ssid, $in_fc);
    }

    public function Initialize_eff_SingleScanSet($in_keyId, $in_fc) {
        $this->scanSetId = $in_keyId;
        $this->scanSetFc = $in_fc;

        $rss = $this->db_pull->qss(2, NULL, $in_keyId, NULL, $this->scanSetFc, NULL);
        $this->effBand = ADAPT_mysqli_result($rss,0,1);
        $this->fe_id = ADAPT_mysqli_result($rss,0,2);

        $this->scansets[0] = new ScanSetDetails();
        $this->scansets[0]->Initialize_ScanSetDetails($in_keyId, $this->scanSetFc);
        $this->scansets[0]->RequestValues_ScanSetDetails();

        if ($this->scansets[0]->keyId_180_scan > 0)
            $this->ReadyToProcess = 1;

        $this->NumberOfScanSets = 1;
    }

    public function GetEfficiencies($pointingOption) {
        // Main function to calculate beam efficiencies from scan sets.
        $this->pointingOption = $pointingOption;
        // Create the input file for beameff_64:
        $this->MakeInputFile();
        // Execute beameff_64:
        $CommandString = "$this->beameff_exe $this->eff_inputfile";
        system($CommandString);
        // Upload the efficiency results to the database:
        $this->UploadEfficiencyFile($this->eff_outputfile);
        $this->Initialize_eff_SingleScanSet($this->scanSetId, $this->scanSetFc);
    }

    private function SoftwareVersionString() {
        $version = $this->scansets[0]->tdh->GetValue('Meas_SWVer');

        if (!$version) {
            $version = "measurement (unknown)";
        } else {
            $version = "measurement " . $version;
        }

        $version .= ", class.eff " . $this->software_version_class_eff;

        if ($this->software_version_analysis == "") {
            if (isset($this->scansets[0]->Scan_copol_pol0->BeamEfficencies)) {
                $this->software_version_analysis = $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('software_version');
            }
        }

        if ($this->software_version_analysis != "")
            $version .= ", analysis " . $this->software_version_analysis;

        return "Software versions: " . $version;
    }

    private function PointingOptionString() {
        if ($this->pointingOption_analysis == "") {
            if (isset($this->scansets[0]->Scan_copol_pol0->BeamEfficencies)) {
                $this->pointingOption_analysis = $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('centers');
            }
            switch ($this->pointingOption_analysis) {
                case "nominal" :
                    $this->pointingOption_analysis = "Nominal subreflector direction";
                    break;
                case "actual" :
                    $this->pointingOption_analysis = "Actual beam direction";
                    break;
                case "7meter" :
                    $this->pointingOption_analysis = "ACA 7 meter nominal";
                    break;
                case "band1test" :
                    $this->pointingOption_analysis = "Band 1 test dewar";
                    break;
                default:
                    break;
            }
        }
        $optionString = "";
        if ($this->pointingOption_analysis != "")
            $optionString = "Pointing: " . $this->pointingOption_analysis . "<br>";
        return $optionString;
    }

    private function MakeOutputEnvironment() {
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
        if (file_exists($this->outputdirectory))
            self::deleteDir($this->outputdirectory);
        mkdir($this->outputdirectory);

        $this->listingsdir = $this->newbasedir . "listings/";
        if (file_exists($this->listingsdir))
            self::deleteDir($this->listingsdir);
        mkdir($this->listingsdir);

        // we will tell beameff_64 to put its output here:
        $this->eff_outputfile = $this->outputdirectory . "output.txt";

        // and to get its command input here:
        $this->eff_inputfile = $this->newbasedir;
        $this->eff_inputfile .= "input_file.txt";
        if (file_exists($this->eff_inputfile)) {
            unlink($this->eff_inputfile);
        }
    }

    private function GetScanSideband($scanSetIdx) {
        $scanSetId = $this->scansets[$scanSetIdx]->GetValue('keyId');

        $rss = $this->db_pull->qss(4, NULL, NULL, NULL, $this->scanSetFc, $scanSetId);
        $rowss = mysqli_fetch_array($rss);
        return $rowss[0];
    }

    private function MakeInputFile() {
        $this -> MakeOutputEnvironment();

        // start writing the command input file:
        $fhandle = fopen($this->eff_inputfile, 'w');

        //Fill in values for settings section
        fwrite($fhandle,"[settings]\r\n");
        fwrite($fhandle,'gnuplot="' . $this->GNUPLOT_path . '"' . "\r\n");
        fwrite($fhandle,'outputdirectory="' . $this->outputdirectory . '"' . "\r\n");
        fwrite($fhandle,"delimiter=tab\r\n");
        fwrite($fhandle,"centers=" . $this->pointingOption . "\r\n");
        fwrite($fhandle,"\r\n");

        //Fill in the individual scan sections
        $scanNumber=0;
        for ($scanSetIdx = 0; $scanSetIdx < count($this->scansets); $scanSetIdx++) {
            $scanSet = $scanSetIdx + 1;

            $sb = $this -> GetScanSideband($scanSetIdx);

            //Copol pol 0 scan
            $scanNumber++;
            fwrite($fhandle,"[scan_$scanNumber]\r\n");
            fwrite($fhandle,"type=copol\r\n");
            fwrite($fhandle,"pol=0\r\n");
            fwrite($fhandle,"scanset=" . ($scanSet) ."\r\n");
            fwrite($fhandle,"f=" . $this->scansets[$scanSetIdx]->GetValue('f') ."\r\n");
            fwrite($fhandle,"sb=" . $sb ."\r\n");
            fwrite($fhandle,"tilt=" . $this->scansets[$scanSetIdx]->GetValue('tilt') ."\r\n");
            fwrite($fhandle,"band=" . $this->scansets[$scanSetIdx]->GetValue('band') ."\r\n");
            fwrite($fhandle,"notes=\r\n");
            fwrite($fhandle,"ifatten=" . $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('ifatten') ."\r\n");
            fwrite($fhandle,"zdistance=" . $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('ProbeZDistance') ."\r\n");

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
            fwrite($fhandle,"tdh_id=" . $this->scansets[$scanSetIdx]->tdh->keyId . "\r\n");
            fwrite($fhandle,"scanset_id=" . $this->scansets[$scanSetIdx]->keyId . "\r\n");
            fwrite($fhandle,"scan_id=" . $this->scansets[$scanSetIdx]->keyId_copol_pol0_scan. "\r\n");
            fwrite($fhandle,"fecfg=" . $this->scansets[$scanSetIdx]->GetValue('fkFE_Config') . "\r\n");

            $ts = strftime("%a",strtotime($this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('TS'))) . " " .  $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('TS');
            fwrite($fhandle,"ts='$ts'\r\n");

            fwrite($fhandle,"\r\n");

            //Crosspol pol 0 scan
            $scanNumber++;
            fwrite($fhandle,"[scan_$scanNumber]\r\n");
            fwrite($fhandle,"type=xpol\r\n");
            fwrite($fhandle,"pol=0\r\n");
            fwrite($fhandle,"scanset=" . ($scanSet) ."\r\n");
            fwrite($fhandle,"f=" . $this->scansets[$scanSetIdx]->GetValue('f') ."\r\n");
            fwrite($fhandle,"sb=" . $sb ."\r\n");
            fwrite($fhandle,"tilt=" . $this->scansets[$scanSetIdx]->GetValue('tilt') ."\r\n");
            fwrite($fhandle,"band=" . $this->scansets[$scanSetIdx]->GetValue('band') ."\r\n");
            fwrite($fhandle,"notes=\r\n");
            fwrite($fhandle,"ifatten=" . $this->scansets[$scanSetIdx]->Scan_xpol_pol0->GetValue('ifatten') ."\r\n");
            fwrite($fhandle,"zdistance=" . $this->scansets[$scanSetIdx]->Scan_xpol_pol0->GetValue('ProbeZDistance') ."\r\n");
            fwrite($fhandle,"tdh_id=" . $this->scansets[$scanSetIdx]->tdh->keyId . "\r\n");
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
            fwrite($fhandle,"type=copol\r\n");
            fwrite($fhandle,"pol=1\r\n");
            fwrite($fhandle,"scanset=" . ($scanSet) ."\r\n");
            fwrite($fhandle,"f=" . $this->scansets[$scanSetIdx]->GetValue('f') ."\r\n");
            fwrite($fhandle,"sb=" . $sb ."\r\n");
            fwrite($fhandle,"tilt=" . $this->scansets[$scanSetIdx]->GetValue('tilt') ."\r\n");
            fwrite($fhandle,"band=" . $this->scansets[$scanSetIdx]->GetValue('band') ."\r\n");
            fwrite($fhandle,"notes=\r\n");
            fwrite($fhandle,"ifatten=" . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('ifatten') ."\r\n");
            fwrite($fhandle,"zdistance=" . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('ProbeZDistance') ."\r\n");
            fwrite($fhandle,"tdh_id=" . $this->scansets[$scanSetIdx]->tdh->keyId . "\r\n");
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
            fwrite($fhandle,"type=xpol\r\n");
            fwrite($fhandle,"pol=1\r\n");
            fwrite($fhandle,"scanset=" . ($scanSet) ."\r\n");
            fwrite($fhandle,"f=" . $this->scansets[$scanSetIdx]->GetValue('f') ."\r\n");
            fwrite($fhandle,"sb=" . $sb ."\r\n");
            fwrite($fhandle,"tilt=" . $this->scansets[$scanSetIdx]->GetValue('tilt') ."\r\n");
            fwrite($fhandle,"band=" . $this->scansets[$scanSetIdx]->GetValue('band') ."\r\n");
            fwrite($fhandle,"notes=\r\n");
            fwrite($fhandle,"ifatten=" . $this->scansets[$scanSetIdx]->Scan_xpol_pol1->GetValue('ifatten') ."\r\n");
            fwrite($fhandle,"zdistance=" . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('ProbeZDistance') ."\r\n");
            fwrite($fhandle,"tdh_id=" . $this->scansets[$scanSetIdx]->tdh->keyId . "\r\n");
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

            //180-degree scan for squint
            if ($this->scansets[$scanSetIdx]->keyId_180_scan > 0) {
                $scanNumber++;
                fwrite($fhandle,"[scan_$scanNumber]\r\n");
                fwrite($fhandle,"type=copol180\r\n");
                //Source position
                //3= pol0 + 180
                //4= pol1 + 180
                $sp = $this->scansets[$scanSetIdx]->Scan_180->GetValue('SourcePosition');
                $pol = 0;
                if ($sp == 4)
                    $pol = 1;
                fwrite($fhandle,"pol=$pol\r\n");
                fwrite($fhandle,"scanset=" . ($scanSet) ."\r\n");
                fwrite($fhandle,"f=" . $this->scansets[$scanSetIdx]->GetValue('f') ."\r\n");
                fwrite($fhandle,"sb=" . $sb ."\r\n");
                fwrite($fhandle,"tilt=" . $this->scansets[$scanSetIdx]->GetValue('tilt') ."\r\n");
                fwrite($fhandle,"band=" . $this->scansets[$scanSetIdx]->GetValue('band') ."\r\n");
                fwrite($fhandle,"notes=\r\n");
                fwrite($fhandle,"ifatten=" . $this->scansets[$scanSetIdx]->Scan_180->GetValue('ifatten') ."\r\n");
                fwrite($fhandle,"zdistance=" . $this->scansets[$scanSetIdx]->Scan_180->GetValue('ProbeZDistance') ."\r\n");
                fwrite($fhandle,"tdh_id=" . $this->scansets[$scanSetIdx]->tdh->keyId . "\r\n");
                fwrite($fhandle,"scanset_id=" . $this->scansets[$scanSetIdx]->keyId . "\r\n");
                fwrite($fhandle,"scan_id=". $this->scansets[$scanSetIdx]->keyId_180_scan ."\r\n");

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
            }
        }
        fclose($fhandle);
    }

    private function ExportNF($nf_path, $scan_id) {
        if (file_exists($nf_path)) {
            unlink($nf_path);
        }
        $this->db_pull->q(TRUE, $nf_path, $scan_id);
    }

    private function ExportFF($ff_path, $scan_id) {
        if (file_exists($ff_path)) {
            unlink($ff_path);
        }
        $this->db_pull->q(FALSE, $ff_path, $scan_id);
    }

    private static function PlotPathToURL($plotPath) {
        // Convert a file-system path to a plot to a web-server URL to the same plot.
        require(site_get_config_main());
        return $main_url_directory . substr($plotPath, stripos($plotPath, "eff/"));
    }

    private function UploadEfficiencyFile($ini_filename) {
        require(site_get_config_main());

        $ini_array = parse_ini_file($ini_filename, true);

        // Load the analysis software version and pointing option from the analysis output file:
        $software_version_analysis = $ini_array['settings']['software_version'];
        $pointingOption_analysis = $ini_array['settings']['centers'];

        // Find the [results_ssidx] section, having the squint, pointingangles, and phase center correction info:
        $pointingangles_plot = '';
        $nominal_z_offset = 0;
        $x_corr = 0;
        $y_corr = 0;
        $corrected_pol = -1;
        $dist_between_centers_mm = 0;
        $squint_percent = 0;
        $squint_arcseconds = 0;

        foreach ($ini_array as $section => $contents) {
            if (!(stripos($section, "results_") === FALSE)) {

                // Suppress error reports for undefined index for the next chunk:
                global $errorReportSettingsNo_E_NOTICE;
                global $errorReportSettingsNormal;
                error_reporting($errorReportSettingsNo_E_NOTICE);

                // Get the pointing angles plot:
                $pointingangles_plot = $ini_array[$section]['pointingangles'];
                if (isset($pointingangles_plot))
                    $pointingangles_plot = self::PlotPathToURL($pointingangles_plot);
                else
                    $pointingangles_plot = '';

                // Get phase center differences and corrections:
                $nominal_z_offset = $ini_array[$section]['nominal_z_offset'];
                $x_corr = $ini_array[$section]['x_corr'];
                $y_corr = $ini_array[$section]['y_corr'];
                $corrected_pol = $ini_array[$section]['corrected_pol'];
                $dist_between_centers_mm = $ini_array[$section]['dist_between_centers_mm'];

                // Get beam squint:
                $squint_percent = $ini_array[$section]['squint_percent'];
                $squint_arcseconds = $ini_array[$section]['squint_arcseconds'];

                // Restore error reporting:
                error_reporting($errorReportSettingsNormal);
            }
        }

        // Loop on [scan_x] sections:
        foreach ($ini_array as $section => $contents) {
            if (!(stripos($section, "scan_") === FALSE)) {

                $keyScanDetails = $ini_array[$section]['scan_id'];

                // Delete any existing efficiencies record for this scan:
                $rdelete = $this->db_pull->qdelete($keyScanDetails, NULL);

                // Create and initialize a new efficies record:
                $beameff = new GenericTable;
                $beameff->Initialize("BeamEfficiencies","","keyBeamEfficiencies",$this->scanSetFc,'fkFacility');
                $beameff->NewRecord("BeamEfficiencies","keyBeamEfficiencies",$this->scanSetFc,'fkFacility');

                // Store overall/settings values:
                $beameff-> SetValue("fkScanDetails", $keyScanDetails);
                $beameff-> SetValue("eff_output_file", $ini_filename);
                $beameff-> SetValue("pointing_angles_plot", $pointingangles_plot);
                $beameff-> SetValue("software_version", $software_version_analysis);
                $beameff-> SetValue("centers", $pointingOption_analysis);

                // Suppress error reports for undefined index for the next chunk:
                global $errorReportSettingsNo_E_NOTICE;
                global $errorReportSettingsNormal;
                error_reporting($errorReportSettingsNo_E_NOTICE);

                // Save all the data loaded from the ini file to the new beameffs record:
                $scanType = $ini_array[$section]['type'];
                $beameff-> SetValue("type", $scanType);

                $beameff-> SetValue("pol", $ini_array[$section]['pol']);
                $beameff-> SetValue("tilt", $ini_array[$section]['tilt']);
                $beameff-> SetValue("f", $ini_array[$section]['f']);
                $beameff-> SetValue("datetime", $ini_array[$section]['ts']);
                $beameff-> SetValue("tilt", $ini_array[$section]['tilt']);
                $beameff-> SetValue("ifatten", $ini_array[$section]['ifatten']);
                $beameff-> SetValue("eta_spillover", $ini_array[$section]['eta_spillover']);
                $beameff-> SetValue("eta_taper", $ini_array[$section]['eta_taper']);
                $beameff-> SetValue("eta_illumination", $ini_array[$section]['eta_illumination']);
                $beameff-> SetValue("ff_xcenter", $ini_array[$section]['ff_xcenter']);
                $beameff-> SetValue("ff_ycenter", $ini_array[$section]['ff_ycenter']);
                $beameff-> SetValue("az_nominal", $ini_array[$section]['az_nominal']);
                $beameff-> SetValue("el_nominal", $ini_array[$section]['el_nominal']);
                $beameff-> SetValue("nf_xcenter", $ini_array[$section]['nf_xcenter']);
                $beameff-> SetValue("nf_ycenter", $ini_array[$section]['nf_ycenter']);
                $beameff-> SetValue("max_ff_amp_db", $ini_array[$section]['max_ff_amp_db']);
                $beameff-> SetValue("max_nf_amp_db", $ini_array[$section]['max_nf_amp_db']);
                $deltaX = $ini_array[$section]['delta_x'];
                $beameff-> SetValue("delta_x", $deltaX);
                $deltaY = $ini_array[$section]['delta_y'];
                $beameff-> SetValue("delta_y", $deltaY);
                $beameff-> SetValue("delta_z", $ini_array[$section]['delta_z']);
                $beameff-> SetValue("eta_phase", $ini_array[$section]['eta_phase']);
                $beameff-> SetValue("ampfit_amp", $ini_array[$section]['ampfit_amp']);
                $beameff-> SetValue("ampfit_width_deg", $ini_array[$section]['ampfit_width_deg']);
                $beameff-> SetValue("ampfit_u_off", $ini_array[$section]['ampfit_u_off_deg']);
                $beameff-> SetValue("ampfit_v_off", $ini_array[$section]['ampfit_v_off_deg']);
                $beameff-> SetValue("ampfit_d_0_90", $ini_array[$section]['ampfit_d_0_90']);
                $beameff-> SetValue("ampfit_edge_db", $ini_array[$section]['edge_db']);
                $beameff-> SetValue("ampfit_d_45_135", $ini_array[$section]['ampfit_d_45_135']);
                $beameff-> SetValue("datetime", $ini_array[$section]['datetime']);
                $beameff-> SetValue("nf", $ini_array[$section]['nf']);
                $beameff-> SetValue("ff", $ini_array[$section]['ff']);
                $beameff-> SetValue("eta_tot_np", $ini_array[$section]['eta_tot_np']);
                $beameff-> SetValue("eta_pol", $ini_array[$section]['eta_pol']);
                $beameff-> SetValue("eta_pol_on_secondary", $ini_array[$section]['eta_pol_on_secondary']);
                $beameff-> SetValue("eta_tot_nd", $ini_array[$section]['eta_tot_nd']);
                $beameff-> SetValue("eta_pol_spill", $ini_array[$section]['eta_pol_spill']);
                $beameff-> SetValue("defocus_efficiency", $ini_array[$section]['defocus_efficiency']);
                $beameff-> SetValue("total_aperture_eff", $ini_array[$section]['total_aperture_eff']);
                $beameff-> SetValue("squint", $ini_array[$section]['squint']);
                $beameff-> SetValue("squint_arcseconds", $ini_array[$section]['squint_arcseconds']);
                $beameff-> SetValue("max_dbdifference", $ini_array[$section]['max_dbdifference']);
                $beameff-> SetValue("software_version_class_eff", $this->software_version_class_eff);

                if ($scanType == 'copol') {
                    // TODO: rename x90, y90.  Now they contain corrected_x corrected_y.  Not using x0, y0 anymore.
                    $correctedX = $ini_array[$section]['corrected_x'];
                    $correctedY = $ini_array[$section]['corrected_y'];

                    $beameff-> SetValue('x90', $correctedX);
                    $beameff-> SetValue('y90', $correctedY);
                    // Set x_corr, y_corr if this is the pol which was corrected:
                    if ($corrected_pol == $ini_array[$section]['pol']) {
                        $beameff-> SetValue('x_corr', $x_corr);
                        $beameff-> SetValue('y_corr', $y_corr);
                    }
                    $beameff-> SetValue('DistanceBetweenBeamCenters', $dist_between_centers_mm);
                    $beameff-> SetValue("nominal_z_offset", $nominal_z_offset);
                    $beameff-> SetValue('squint', $squint_percent);
                    $beameff-> SetValue('squint_arcseconds', $squint_arcseconds);
                }

                if ($scanType == 'copol' || $scanType == 'xpol') {
                    $beameff-> SetValue("plot_copol_nfamp", self::PlotPathToURL($ini_array[$section]['plot_copol_nfamp']));
                    $beameff-> SetValue("plot_copol_nfphase", self::PlotPathToURL($ini_array[$section]['plot_copol_nfphase']));
                    $beameff-> SetValue("plot_copol_ffamp", self::PlotPathToURL($ini_array[$section]['plot_copol_ffamp']));
                    $beameff-> SetValue("plot_copol_ffphase", self::PlotPathToURL($ini_array[$section]['plot_copol_ffphase']));
                    $beameff-> SetValue("plot_xpol_nfamp", self::PlotPathToURL($ini_array[$section]['plot_xpol_nfamp']));
                    $beameff-> SetValue("plot_xpol_nfphase", self::PlotPathToURL($ini_array[$section]['plot_xpol_nfphase']));
                    $beameff-> SetValue("plot_xpol_ffamp", self::PlotPathToURL($ini_array[$section]['plot_xpol_ffamp']));
                    $beameff-> SetValue("plot_xpol_ffphase", self::PlotPathToURL($ini_array[$section]['plot_xpol_ffphase']));
                }

                // Restore error reporting:
                error_reporting($errorReportSettingsNormal);

                // Save the completed efficiencies record
                $beameff->Update();
                unset($beameff);
            }
        }
    }

    private static function deleteDir($dirPath) {
        if (!is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    function Display_PointingAngles() {
//         $nomAZ = ADAPT_mysqli_result($rn,0,0);
//         $nomEL = ADAPT_mysqli_result($rn,0,1);
        //Get nominal Az, El
        $sd = new ScanDetails();

        $nomAZ = round($this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('az_nominal'),4);
        $nomEL = round($this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('el_nominal'),4);

        if ($this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('centers') == "actual") {
            $nomAZ = round($this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('ff_xcenter'),4);
            $nomEL = round($this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('ff_ycenter'),4);
        }

        echo "<div style = 'width:200px'><table id = 'table1'>";

        echo "<tr class='alt'><th colspan = 5>Pointing Angles Band $this->effBand <i><br>(Eff. calculations using: $nomAZ, $nomEL)</i></th></tr>";
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
        echo "<tr><td colspan='5'><font size='-1'><i>"
                . $this->PointingOptionString()
                . $this->SoftwareVersionString()
                . "</i></font></td></tr>";
        echo "</table></div>";
    }

    function Display_ApertureEff() {
        echo "<div style = 'width:300px'><table id = 'table1' border='1'>";

        echo "<tr class='alt'><th colspan = 4>Aperture Efficiency Band $this->effBand</th></tr>";
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
            $ae = round(100 * $this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('total_aperture_eff'),2);
            if ($ae < 80) {
                echo "<td><font color = '#ff0000'>$ae</font></td>";
            } else {
                echo "<td>$ae</td>";
            }
            echo "<tr>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            $ae = round(100 * $this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('total_aperture_eff'),2);
            if ($ae < 80) {
                echo "<td><font color = '#ff0000'>$ae</font></td>";
            } else {
                echo "<td>$ae</td>";
            }
        }
        //Meas SW Ver
        echo "<tr><td colspan='4'><font size='-1'><i>"
                . $this->PointingOptionString()
                . $this->SoftwareVersionString()
                . "</i></font></td></tr>";
        echo "</table></div>";
    }

    function Display_TaperEff() {
        echo "<div style = 'width:300px'><table id = 'table1' border='1' border='1'>";
        echo "<tr class='alt'><th colspan = 4>Amplitude Taper Efficiency Band $this->effBand</th></tr>";
        echo "<tr>
            <th>RF GHz</th>
            <th>pol</th>
            <th>Elevation</th>
            <th>Amplitude<br>Taper Eff</th>
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
        echo "<tr><td colspan='4'><font size='-1'><i>" . $this->SoftwareVersionString() . "</i></font></td></tr>";
        echo "</table></div>";
    }

    function Display_PhaseEff() {
        echo "<div style = 'width:300px'><table id = 'table1' border='1' border='1'>";
        echo "<tr class='alt'><th colspan = 4>Phase Efficiency Band $this->effBand</th></tr>";
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
        echo "<tr><td colspan='4'><font size='-1'><i>" . $this->SoftwareVersionString() . "</i></font></td></tr>";
        echo "</table></div>";
    }

    function Display_SpilloverEff() {
        echo "<div style = 'width:300px'><table id = 'table1' border='1'>";
        echo "<tr class='alt'><th colspan = 4>Spillover Efficiency Band $this->effBand</th></tr>";
        echo "<tr>
            <th>RF GHz</th>
            <th>pol</th>
            <th>Elevation</th>
            <th>Spillover Eff<br><font color='blue' size='-2'>(equation 4 below)</font></th>
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
        echo "<tr><td colspan='4'><font size='-1'><i>"
                . $this->PointingOptionString()
                . $this->SoftwareVersionString()
                . "</i></font></td></tr>";
        echo "</table></div>";
    }

    function Display_Equations() {
        require(site_get_config_main());
        $img = $url_root . "classes/images/pol_spill_equations.png";
        $paper = "https://safe.nrao.edu/wiki/pub/ALMA/FEICBeamScanningResults/Calculation_of_Efficiencies.pdf";

        echo "<div style='width:300px'><table id = 'table1' border='1'>";
        echo "<tr><th><font size='-2'>&nbsp Excerpted from R.Hills' <a href='$paper'>paper</a> on calculation of efficiencies.</font></th></tr>";
        echo "<tr><td><img src='$img' alt='Polarization and Spillover equations' style='width:608px;'>";
        echo "</td></tr></table></div><br>";
    }

    function Display_PolEff() {
        echo "<div style = 'width:600px'><table id = 'table1' border='1'>";

        echo "<tr class='alt'><th colspan = 7>Polarization Efficiency Band $this->effBand</th></tr>";
        echo "<tr>
            <th>RF GHz</th>
            <th>pol</th>
            <th>Elevation</th>
            <th>Peak Cross dB</th>
            <th>Eta Pol+spill<br><font color='blue' size='-2'>(equation 1 below)</font></th>
            <th>Eta Pol on secondary<br><font color='blue' size='-2'>(equation 3)</font></th>
            </tr>";

        for ($scanSetIdx = 0; $scanSetIdx < $this->NumberOfScanSets; $scanSetIdx++) {
            // if the polarization efficiency is less than the value calculated for $p0spec/$p1spec, display it as red:
            $rf = floatval($this->scansets[$scanSetIdx]->GetValue('f'));
            $p0spec = $p1spec = 0.0;

            $spec = $this->specsProvider->getSpecs('beameff', $this->effBand);

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
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_xpol_pol0->BeamEfficencies->GetValue('max_dbdifference'), 2) . "</td>";
            echo "<td>" . round(100 * $this->scansets[$scanSetIdx]->Scan_xpol_pol0->BeamEfficencies->GetValue('eta_pol_spill'), 2) . "</td>";

            $pe = round(100 * $this->scansets[$scanSetIdx]->Scan_xpol_pol0->BeamEfficencies->GetValue('eta_pol_on_secondary'), 2);
            if ($pe < $p0spec)
                echo "<td><font color ='#ff0000'>$pe</font></td>";
            else
                echo "<td>$pe</td>";

            echo "<tr>";
            echo "<td>" . $rf . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_xpol_pol1->BeamEfficencies->GetValue('max_dbdifference'), 2) . "</td>";
            echo "<td>" . round(100 * $this->scansets[$scanSetIdx]->Scan_xpol_pol1->BeamEfficencies->GetValue('eta_pol_spill'), 2) . "</td>";

            $pe = round(100 * $this->scansets[$scanSetIdx]->Scan_xpol_pol1->BeamEfficencies->GetValue('eta_pol_on_secondary'), 2);
            if ($pe < $p1spec)
                echo "<td><font color ='#ff0000'>$pe</font></td>";
            else
                echo "<td>$pe</td>";
        }
        //Meas SW Ver
        echo "<tr><td colspan='6'><font size='-1'><i>"
                . $this->PointingOptionString()
                . $this->SoftwareVersionString()
                . "</i></font></td></tr>";
        echo "</table></div>";
    }

    function Display_DefocusEff() {
         echo "<div style = 'width:300px'><table id = 'table1' border='1'>";

         echo "<tr class='alt'><th colspan = 4>Defocus Efficiency Band $this->effBand</th></tr>";
         echo "<tr>
             <th>RF GHz</th>
             <th>pol</th>
             <th>Elevation</th>
             <th>Defocus Eff</th>
             </tr>";

         for ($scanSetIdx = 0; $scanSetIdx < $this->NumberOfScanSets; $scanSetIdx++) {
            echo "<tr>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            echo "<td>" . round(100 * $this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('defocus_efficiency'), 3) . "</td>";

            echo "<tr>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->Scan_copol_pol1->GetValue('pol') . "</td>";
            echo "<td>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            echo "<td>" . round(100 * $this->scansets[$scanSetIdx]->Scan_copol_pol1->BeamEfficencies->GetValue('defocus_efficiency'), 3) . "</td>";
        }
        //Meas SW Ver
        echo "<tr><td colspan='7'><font size='-1'><i>" . $this->SoftwareVersionString() . "</i></font></td></tr>";
        echo "</table></div>";
    }

    function Display_PointingAngleDiff() {
        echo "<div style = 'width:200px'><table id = 'table1' border='1'>";
        echo "<tr class='alt'><th colspan = 4>Pointing Difference between <br>Pol 0, Pol 1 Band $this->effBand</th></tr>";
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
        echo "<tr><td colspan='4'><font size='-1'>" . $this->SoftwareVersionString() . "</i></font></td></tr>";
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
        echo "<tr class='alt'><th colspan = 4>Beam Squint Band $this->effBand</th></tr>";
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
        $squintAlgo = "https://safe.nrao.edu/wiki/bin/view/ALMA/BeamSquintFromSingleScan#correctionProcedure";
        echo "<tr><td colspan='4'><font size='-2'>Squint algorithm is described <a href='$squintAlgo'>here.</a></font>";
        echo "<br><font size='-1'><i>" . $this->SoftwareVersionString() . "</i></font></td></tr>";
        echo "</table></div>";
        if ($thirdscanpresent != 1) {
            echo "<font size='+2'><b>WARNING <br>Third scan not present. Squint value is incorrect.</b></font></div>";
        }
    }

    function Display_PhaseCenterOffset() {
        echo "<div style = 'width:500px'><table id = 'table1' border='1'>";
        echo "<tr class='alt'><th colspan = 6>Phase Center Offset Band $this->effBand</th></tr>";
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
        $corrected_pol = 0;
        $x_corr = $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('x_corr');
        $y_corr = $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('y_corr');

        if ($x_corr == 0 && $y_corr == 0) {
            $corrected_pol = 1;
            // UGLY: have to get this from pol1 since we're not storing corrected_pol:
            $x_corr = $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('x_corr');
            $y_corr = $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('y_corr');
        }

        echo "<tr>";
        echo "<td>" . $this->scansets[0]->GetValue('f') . "</td>";
        echo "<td>" . $this->scansets[0]->Scan_copol_pol0->GetValue('pol') . "</td>";
        echo "<td>" . $this->scansets[0]->GetValue('tilt') . "</td>";
        echo "<td>" . round($this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('delta_x'),2) . "</td>";
        echo "<td>" . round($this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('delta_y'),2) . "</td>";
        echo "<td>" . round($this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('delta_z'),2) . "</td></tr>";

        echo "<tr>";
        echo "<td>" . $this->scansets[0]->GetValue('f') . "</td>";
        echo "<td>" . $this->scansets[0]->Scan_copol_pol1->GetValue('pol') . "</td>";
        echo "<td>" . $this->scansets[0]->GetValue('tilt') . "</td>";
        echo "<td>" . round($this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('delta_x'),2) . "</td>";
        echo "<td>" . round($this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('delta_y'),2) . "</td>";
        echo "<td>" . round($this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('delta_z'),2) . "</td></tr>";

        echo "<tr><th colspan='3'>Phase center correction (pol $corrected_pol):</th>";
        echo "<td>" . round($x_corr, 2) . "</td>";
        echo "<td>" . round($y_corr, 2) . "</td>";
        echo "<th></th></tr>";

        // corrected_x and corrected_y are calcuated by beameff2.x  Only one pol actually differs from delta_x, delta_y
        // Since we are only displaying the differences we don't need to know which one.
        // TODO:  Using x90, y90, not for their original purpose
        $x_difference = $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('x90') -
                        $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('x90');

        $y_difference = $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('y90') -
                        $this->scansets[0]->Scan_copol_pol1->BeamEfficencies->GetValue('y90');

        //x_diff, y_diff from https://safe.nrao.edu/wiki/bin/view/ALMA/BeamSquintFromSingleScan#correctionProcedure
        echo "<tr><th colspan='3'>Corrected difference between phase centers:</th>";
        echo "<td>" . round(abs($x_difference), 2) . "</td>";
        echo "<td>" . round(abs($y_difference), 2) . "</td>";
        echo "<th></th></tr>";

        //echo "<tr class = 'alt'><th colspan='6'></th></tr>";
        echo "<tr><th colspan='3'>Distance between pol 0 and pol 1 phase centers:</th>";
        echo"<td colspan='2'><center>" . round($this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('DistanceBetweenBeamCenters'),2) . "</th><th></td></tr>";
        echo "</tr>";
        echo "<tr><td colspan='6'><font size='-1'><i>" . $this->SoftwareVersionString() . "</i></font></td></tr>";
        echo "</table></div>";
    }

    function Display_AmpFit() {
        echo "<div style='width:800px'>";
        echo "<table id = 'table1' border='1'>";
        echo "<tr class='alt'><th colspan = 10>Amp Fit Parameters Band $this->effBand</th></tr>";
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
        echo "</table><br>";
    }

    function Display_ScanInformation() {
        echo "<div style='width:950px'>";
        echo "<table id = 'table1' border='1'>";

        echo "<tr class = 'alt'>
            <th colspan = 6>Scan Information Band $this->effBand (" . $this->scansets[0]->GetValue('TS') . ")</th>
            <th colspan = 4>Export CSV</th>
            </tr>";

        echo "<tr>
            <th>RF GHz</th>
            <th>Pol</th>
            <th>Elevation</th>
            <th>Date/Time</th>
            <th>File Name</th>
            <th>Amp, Phase Drift</th>
            <th colspan = 2>NF</th>
            <th colspan = 2>FF</th>
            </tr>";

        $count = 0;
        for ($scanSetIdx = 0; $scanSetIdx < $this->NumberOfScanSets; $scanSetIdx++) {
            $count +=1;
            $trclass = '';
            if ($count % 2 == 0) {
                  $trclass = 'alt';
            }
            $ts = strftime("%a",strtotime($this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('TS'))) . " " . $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('TS');

            echo "<tr class='$trclass'>";
            echo "<td width = '30px'>" . $this->scansets[$scanSetIdx]->GetValue('f') . "</td>";
            echo "<td width = '30px'>" . $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('pol') . "</td>";
            echo "<td width = '80px'>" . $this->scansets[$scanSetIdx]->GetValue('tilt') . "</td>";
            echo "<td width ='180px'>" . $ts . "</td>";

            $nsi_filename = $this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('nsi_filename');
            $nameOffset = strripos($nsi_filename, "band");    // find last occurance, case-insensitive
            $nsi_filename = substr($nsi_filename, $nameOffset);

            echo "<td>" . $nsi_filename ."</a></td>";
            echo "<td>" . round($this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('ampdrift'),2) . " dB, "
                        . round($this->scansets[$scanSetIdx]->Scan_copol_pol0->GetValue('phasedrift'),2) . " deg</td>";

            $keyScanSet = $this->scansets[$scanSetIdx] -> keyId;

            echo "<td><a href='export_to_csv.php?which=nf&setid=$keyScanSet&detid="
                . $this->scansets[$scanSetIdx]->Scan_copol_pol0->keyId . "'>copol</a></td>";

            echo "<td><a href='export_to_csv.php?which=nf&setid=$keyScanSet&detid="
                . $this->scansets[$scanSetIdx]->Scan_xpol_pol0->keyId . "'>xpol</a></td>";

            echo "<td><a href='export_to_csv.php?which=ff&setid=$keyScanSet&detid="
                . $this->scansets[$scanSetIdx]->Scan_copol_pol0->keyId . "'>copol</a></td>";

            echo "<td><a href='export_to_csv.php?which=ff&setid=$keyScanSet&detid="
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

            echo "<td><a href='export_to_csv.php?which=nf&setid=$keyScanSet&detid="
                . $this->scansets[$scanSetIdx]->Scan_copol_pol1->keyId . "'>copol</a></td>";

            echo "<td><a href='export_to_csv.php?which=nf&setid=$keyScanSet&detid="
                . $this->scansets[$scanSetIdx]->Scan_xpol_pol1->keyId . "'>xpol</a></td>";

            echo "<td><a href='export_to_csv.php?which=ff&setid=$keyScanSet&detid="
                . $this->scansets[$scanSetIdx]->Scan_copol_pol1->keyId . "'>copol</a></td>";

            echo "<td><a href='export_to_csv.php?which=ff&setid=$keyScanSet&detid="
                . $this->scansets[$scanSetIdx]->Scan_xpol_pol1->keyId . "'>xpol</a></td></tr>";
        }
        //Meas SW Ver
        echo "<tr><td colspan='10'><font size='-1'><i>" . $this->SoftwareVersionString() . "</i></font></td></tr>";
        echo "</table></div>";
    }

    function Display_SetupParameters() {
        echo "<div style='width:950px'>";
        echo "<table id = 'table1' border='1'>";

        echo "<tr class = 'alt'><th colspan = 6>Scan Setup Parameters Band $this->effBand</th></tr>";
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
        echo "<tr><td colspan='6'><font size='-1'><i>" . $this->SoftwareVersionString() . "</i></font></td></tr>";
        echo "</table></div>";
    }

    function Display_PointingAnglesPlot() {
        echo "<img src='" . $this->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('pointing_angles_plot') . "'>";
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
                echo "</td>";

                echo "<td><img src='" . $scanset->Scan_copol_pol0->BeamEfficencies->GetValue('plot_copol_nfphase') . "'>";
                echo "</td>";
                echo "</tr>";

                echo "<tr><th colspan=2 style='background:#FFFFFF'></th></tr>";

                echo "<tr>";
                echo "<td><img src='" . $scanset->Scan_xpol_pol0->BeamEfficencies->GetValue('plot_xpol_nfamp') . "'>";
                echo "</td>";

                echo "<td><img src='" . $scanset->Scan_xpol_pol0->BeamEfficencies->GetValue('plot_xpol_nfphase') . "'>";
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
                echo "</td>";

                echo "<td><img src='" . $scanset->Scan_copol_pol0->BeamEfficencies->GetValue('plot_copol_ffphase') . "'>";
                echo "</td>";
                echo "</tr>";

                echo "<tr><th colspan=2 style='background:#FFFFFF'></th></tr>";

                echo "<tr>";
                echo "<td><img src='" . $scanset->Scan_xpol_pol0->BeamEfficencies->GetValue('plot_xpol_ffamp') . "'>";
                echo "</td>";

                echo "<td><img src='" . $scanset->Scan_xpol_pol0->BeamEfficencies->GetValue('plot_xpol_ffphase') . "'>";
                echo "</td>";
                echo "</tr>";
                echo "</table>";
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
                echo "</td>";

                echo "<td><img src='" . $scanset->Scan_copol_pol1->BeamEfficencies->GetValue('plot_copol_nfphase') . "'>";
                echo "</td>";
                echo "</tr>";

                echo "<tr><th colspan=2 style='background:#FFFFFF'></th></tr>";

                echo "<tr>";
                echo "<td><img src='" . $scanset->Scan_xpol_pol1->BeamEfficencies->GetValue('plot_xpol_nfamp') . "'>";
                echo "</td>";

                echo "<td><img src='" . $scanset->Scan_xpol_pol1->BeamEfficencies->GetValue('plot_xpol_nfphase') . "'>";
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
                echo "</td>";

                echo "<td><img src='" . $scanset->Scan_copol_pol1->BeamEfficencies->GetValue('plot_copol_ffphase') . "'>";
                echo "</td>";
                echo "</tr>";

                echo "<tr><th colspan=2 style='background:#FFFFFF'></th></tr>";

                echo "<tr>";
                echo "<td><img src='" . $scanset->Scan_xpol_pol1->BeamEfficencies->GetValue('plot_xpol_ffamp') . "'>";
                echo "</td>";

                echo "<td><img src='" . $scanset->Scan_xpol_pol1->BeamEfficencies->GetValue('plot_xpol_ffphase') . "'>";
                echo "</td>";
                echo "</tr>";
                echo "</table>";
            }
        }
    }

    public function Export($outputDir) {
        for ($scanSetIdx = 0; $scanSetIdx < $this->NumberOfScanSets; $scanSetIdx++) {
            $keyScanSet = $this->scansets[$scanSetIdx] -> keyId;
            $tdh = $this->scansets[$scanSetIdx]->tdh->keyId;
            $destFile = $outputDir . "BeamEff_B" . $this->effBand . "_H" . $tdh . ".ini";
            $sourceFile = $this->scansets[$scanSetIdx]->Scan_copol_pol0->BeamEfficencies->GetValue('eff_output_file');
            if (file_exists($sourceFile)) {
                copy($sourceFile, $destFile);
                echo "Exported '$destFile'.<br>";
            } else
                echo "No BeamEff output file found for header $tdh.<br>";
        }
        return $destFile;
    }
}
?>
