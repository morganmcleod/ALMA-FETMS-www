<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');

class TEST_IFSpectrum_urls extends GenericTable {
    public $keyId;
    public $fkHeader;
    public $fkFacility;
    public $Band;
    public $IFChannel;
    public $IFGain;
    public $spurious_url;
    public $spurious_url2;
    public $spurious_url2d;
    public $spurious_url2d2;
    public $powervar_31MHz_url;
    public $powervar_2GHz_url;
    public $TS;

    public function __construct($inKeyId, $inFc) {
        parent::__construct("TEST_IFSpectrum_urls", $inKeyId, "keyId", $inFc, 'fkFacility');
    }

    public static function getIdAndIFChannelFromHeader($fkHeader) {
        $dbConnection = site_getDbConnection();
        $q = "SELECT keyId, IFChannel FROM TEST_IFSpectrum_urls
              WHERE fkHeader in ($fkHeader) ORDER BY IFChannel ASC;";
        return mysqli_query($dbConnection, $q);
    }
}
