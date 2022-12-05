<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');

class WCAs extends GenericTable {
    public $keyId;
    public $TS;
    public $fkFacility;
    public $fkFE_Component;
    public $SN_PwrAmp;
    public $FloYIG;
    public $FhiYIG;
    public $amnz_avgdsb_url;
    public $amnz_pol0_url;
    public $amnz_pol1_url;
    public $amp_stability_url;
    public $op_vs_freq_url;
    public $op_vs_dv_pol0_url;
    public $op_vs_dv_pol1_url;
    public $op_vs_ss_pol0_url;
    public $op_vs_ss_pol1_url;
    public $phasenoise_url;
    public $VG0;
    public $VG1;

    public function __construct($inKeyId, $inFc) {
        parent::__construct("WCAs", $inKeyId, "keyId", $inFc, 'fkFacility');
    }
}
