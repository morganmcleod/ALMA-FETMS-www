<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');

class BeamEfficencies extends GenericTable {
    public $keyBeamEfficiencies;
    public $fkFacility;
    public $TS;
    public $fkScanDetails;
    public $eff_output_file;
    public $pol;
    public $tilt;
    public $f;
    public $type;
    public $ifatten;
    public $eta_spillover;
    public $eta_taper;
    public $eta_illumination;
    public $ff_xcenter;
    public $ff_ycenter;
    public $az_nominal;
    public $el_nominal;
    public $nf_xcenter;
    public $nf_ycenter;
    public $max_ff_amp_db;
    public $max_nf_amp_db;
    public $delta_x;
    public $delta_y;
    public $delta_z;
    public $eta_phase;
    public $ampfit_amp;
    public $ampfit_width_deg;
    public $ampfit_u_off;
    public $ampfit_v_off;
    public $ampfit_d_0_90;
    public $ampfit_d_45_135;
    public $ampfit_edge_db;
    public $plot_copol_nfamp;
    public $plot_copol_nfphase;
    public $plot_copol_ffphase;
    public $plot_copol_ffamp;
    public $max_dbdifference;
    public $datetime;
    public $plot_xpol_nfamp;
    public $plot_xpol_nfphase;
    public $plot_xpol_ffamp;
    public $plot_xpol_ffphase;
    public $nf;
    public $ff;
    public $nominal_z_offset;
    public $eta_tot_np;
    public $eta_pol;
    public $eta_tot_nd;
    public $eta_pol_on_secondary;
    public $eta_pol_spill;
    public $defocus_efficiency;
    public $total_aperture_eff;
    public $shift_from_focus_mm;
    public $subreflector_shift_mm;
    public $defocus_efficiency_due_to_moving_the_subreflector;
    public $squint;
    public $squint_arcseconds;
    public $x_diff;
    public $y_diff;
    public $x_corr;
    public $y_corr;
    public $x90;
    public $y90;
    public $x0;
    public $y0;
    public $DistanceBetweenBeamCenters;
    public $software_version;
    public $software_version_class_eff;
    public $software_version_vbscript;
    public $software_version_labviewvi;
    public $pointing_angles_plot;
    public $centers;

    public function __construct($inKeyId, $inFc) {
        parent::__construct("BeamEfficiencies", $inKeyId, "keyBeamEfficiencies", $inFc, 'fkFacility');
    }

    public static function getIdFromScanDetails($fkScanDetails) {
        $dbConnection = site_getDbConnection();
        $query = "SELECT keyBeamEfficiencies FROM BeamEfficiencies WHERE fkScanDetails = $fkScanDetails;";
        $result = mysqli_query($dbConnection, $query);
        return ADAPT_mysqli_result($result, 0);
    }
}
