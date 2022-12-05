<?php
// Reads/modifies a ScanDetails and its corresponding BeamEfficiencies record from the FEIC database.
//
// Utility methods to upload nearfield and farfield listing text files into the database.
//
// TODO: Includes a number of possibly obsolete helper functions:
// GetNominalAngles()
// GeneratePlot_NF()
// GeneratePlot_FF()
// MakePlot_NF()
// MakePlot_FF()
// MakePlot_FF2()
// -- these appear to be handled by the beameff_64 C program now and may not be needed anymore.
//

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.beamefficiencies.php');


class ScanDetails extends GenericTable {
    // ScanDetails columns
    public $keyId;
    public $fkFacility;
    public $fkScanSetDetails;
    public $notes;
    public $sb;
    public $ifatten;
    public $scan_type;
    public $TS;
    public $nf_filename;
    public $ff_filename;
    public $nf_amp_image;
    public $nf_phase_image;
    public $ff_amp_image;
    public $ff_phase_image;
    public $pol;
    public $copol;
    public $nsi_filename;
    public $SourceRotationAngle;
    public $SourcePosition;
    public $ProbeZDistance;
    public $ampdrift;
    public $phasedrift;
    public $rfpa_percent;

    public $nominal_az;
    public $nominal_el;
    public $BeamEfficencies;
    public $fc; //facility key

    public function __construct($keyId, $inFc = 40) {
        parent::__construct("ScanDetails", $keyId, "keyId", $inFc, 'fkFacility');

        //Get keyId for Beam Efficiencies record for this scan
        $keyBeamEfficiencies = BeamEfficencies::getIdFromScanDetails($keyId);
        $this->BeamEfficencies = new BeamEfficencies($keyBeamEfficiencies, $inFc);
    }
}
