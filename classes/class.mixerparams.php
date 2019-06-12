<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_dbConnect);

class MixerParams extends GenericTable {
    var $lo;
    var $vj01;
    var $vj02;
    var $vj11;
    var $vj12;
    var $ij01;
    var $ij02;
    var $ij11;
    var $ij12;
    var $imag01;
    var $imag02;
    var $imag11;
    var $imag12;

    var $vj01_key;
    var $vj02_key;
    var $vj11_key;
    var $vj12_key;
    var $ij01_key;
    var $ij02_key;
    var $ij11_key;
    var $ij12_key;
    var $imag01_key;
    var $imag02_key;
    var $imag11_key;
    var $imag12_key;
    var $fkCCA;
    
    public function __construct() {
        parent::__construct();
    }

    public function Initialize_MixerParam($in_fkCCA, $in_lo, $in_fc){
        $this->lo = $in_lo;
        $this->fkCCA = $in_fkCCA;

        $q = "SELECT IMAG, keyId FROM CCA_MixerParams
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 0
              AND SB = 1
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);
        $this->imag01 = ADAPT_mysqli_result($r,0,0);
        $this->imag01_key = ADAPT_mysqli_result($r,0,1);

        $q = "SELECT IMAG, keyId FROM CCA_MixerParams
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 0
              AND SB = 2
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);
        $this->imag02 = ADAPT_mysqli_result($r,0);
        $this->imag02_key = ADAPT_mysqli_result($r,0,1);

        $q = "SELECT IMAG, keyId FROM CCA_MixerParams
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 1
              AND SB = 1
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);
        $this->imag11 = ADAPT_mysqli_result($r,0);
        $this->imag11_key = ADAPT_mysqli_result($r,0,1);

        $q = "SELECT IMAG, keyId FROM CCA_MixerParams
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 1
              AND SB = 2
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);
        $this->imag12 = ADAPT_mysqli_result($r,0);
        $this->imag12_key = ADAPT_mysqli_result($r,0,1);


        $q = "SELECT VJ, keyId FROM CCA_MixerParams
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 0
              AND SB = 1
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);
        $this->vj01 = ADAPT_mysqli_result($r,0);
        $this->vj01_key = ADAPT_mysqli_result($r,0,1);


        $q = "SELECT VJ, keyId FROM CCA_MixerParams
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 0
              AND SB = 2
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);
        $this->vj02 = ADAPT_mysqli_result($r,0,0);
        $this->vj0201_key = ADAPT_mysqli_result($r,0,1);

        $q = "SELECT VJ, keyId FROM CCA_MixerParams
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 1
              AND SB = 1
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);
        $this->vj11 = ADAPT_mysqli_result($r,0,0);
        $this->vj11_key = ADAPT_mysqli_result($r,0,1);

        $q = "SELECT VJ, keyId FROM CCA_MixerParams
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 1
              AND SB = 2
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);
        $this->vj12 = ADAPT_mysqli_result($r,0,0);
        $this->vj12_key = ADAPT_mysqli_result($r,0,1);




        $q = "SELECT IJ, keyId FROM CCA_MixerParams
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 0
              AND SB = 1
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);
        $this->ij01 = ADAPT_mysqli_result($r,0,0);
        $this->ij01_key = ADAPT_mysqli_result($r,0,1);


        $q = "SELECT IJ, keyId FROM CCA_MixerParams
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 0
              AND SB = 2
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);
        $this->ij02 = ADAPT_mysqli_result($r,0,0);
        $this->ij02_key = ADAPT_mysqli_result($r,0,1);

        $q = "SELECT IJ, keyId FROM CCA_MixerParams
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 1
              AND SB = 1
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);
        $this->ij11 = ADAPT_mysqli_result($r,0,0);
        $this->ij11_key = ADAPT_mysqli_result($r,0,1);

        $q = "SELECT IJ, keyId FROM CCA_MixerParams
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 1
              AND SB = 2
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);
        $this->ij12 = ADAPT_mysqli_result($r,0,0);
        $this->ij12_key = ADAPT_mysqli_result($r,0,1);
    }


    public function Update_MixerParams($in_fkCCA, $in_fc){

        $q = "UPDATE CCA_MixerParams
          SET IMAG = $this->imag01
          WHERE fkComponent = $in_fkCCA
          AND FreqLO = $this->lo
          AND Pol = 0
          AND SB = 1
          AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);


        $q = "UPDATE CCA_MixerParams
          SET IMAG = $this->imag02
          WHERE fkComponent = $in_fkCCA
          AND FreqLO = $this->lo
          AND Pol = 0
          AND SB = 2
          AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);


        $q = "UPDATE CCA_MixerParams
          SET IMAG = $this->imag11
          WHERE fkComponent = $in_fkCCA
          AND FreqLO = $this->lo
          AND Pol = 1
          AND SB = 1
          AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);


        $q = "UPDATE CCA_MixerParams
          SET IMAG = $this->imag12
          WHERE fkComponent = $in_fkCCA
          AND FreqLO = $this->lo
          AND Pol = 1
          AND SB = 2
          AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);


        $q = "UPDATE CCA_MixerParams
              SET VJ = $this->vj01
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 0
              AND SB = 1
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);



        $q = "UPDATE CCA_MixerParams
              SET VJ = $this->vj02
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 0
              AND SB = 2
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);


        $q = "UPDATE CCA_MixerParams
              SET VJ = $this->vj11
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 1
              AND SB = 1
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);



        $q = "UPDATE CCA_MixerParams
              SET VJ = $this->vj12
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 1
              AND SB = 2
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);




        $q = "UPDATE CCA_MixerParams
              SET IJ = $this->ij01
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 0
              AND SB = 1
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);



        $q = "UPDATE CCA_MixerParams
              SET IJ = $this->ij02
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 0
              AND SB = 2
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);


        $q = "UPDATE CCA_MixerParams
              SET IJ = $this->ij11
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 1
              AND SB = 1
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);


        $q = "UPDATE CCA_MixerParams
              SET IJ = $this->ij12
              WHERE fkComponent = $in_fkCCA
              AND FreqLO = $this->lo
              AND Pol = 1
              AND SB = 2
              AND fkFacility = $in_fc;";
        $r = mysqli_query($this->dbconnection, $q);


    }
}

?>