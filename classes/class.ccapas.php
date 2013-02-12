<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.cca.php');
require_once($site_classes . '/class.testdata_header.php');

class CCAPAS extends CCA{

    public function Display_IVCurve(){
        $q = "SELECT keyId FROM TestData_header
              WHERE fkFE_Components = $this->keyId
              AND keyFacility = " . $this->GetValue('keyFacility') . "
              AND fkTestData_Type = 39;";
        $r = @mysql_query($q, $this->dbconnection);

        while ($row = @mysql_fetch_array($r)){
            $t = new TestData_header();
            $t->Initialize_TestData_header($row[0], $this->GetValue('keyFacility'));
            echo "<img src='" . $t->GetValue('PlotURL') . "'><br>";
            unset($t);
        }
    }

    public function Display_AmplitudeStability(){
        $q = "SELECT keyId FROM TestData_header
              WHERE fkFE_Components = $this->keyId
              AND keyFacility = " . $this->GetValue('keyFacility') . "
              AND fkTestData_Type = 43;";

        $r = @mysql_query($q, $this->dbconnection);

        while ($row = @mysql_fetch_array($r)){
            $t = new TestData_header();
            $t->Initialize_TestData_header($row[0], $this->GetValue('keyFacility'));
            echo "<img src='" . $t->GetValue('PlotURL') . "'><br>";
            unset($t);
        }
    }

    public function Display_InBandPower(){
        $q = "SELECT keyId FROM TestData_header
              WHERE fkFE_Components = $this->keyId
              AND keyFacility = " . $this->GetValue('keyFacility') . "
              AND fkTestData_Type = 36;";

        $r = @mysql_query($q, $this->dbconnection);

        while ($row = @mysql_fetch_array($r)){
            $t = new TestData_header();
            $t->Initialize_TestData_header($row[0], $this->GetValue('keyFacility'));
            echo "<img src='" . $t->GetValue('PlotURL') . "'><br>";
            unset($t);
        }
    }

    public function Display_PolAccuracy(){
        $q = "SELECT keyId FROM TestData_header
              WHERE fkFE_Components = $this->keyId
              AND keyFacility = " . $this->GetValue('keyFacility') . "
              AND fkTestData_Type = 35;";

        $r = @mysql_query($q, $this->dbconnection);
        while ($row = @mysql_fetch_array($r)){
            $t = new TestData_header();
            $t->Initialize_TestData_header($row[0], $this->GetValue('keyFacility'));
            echo "<img src='" . $t->GetValue('PlotURL') . "'><br>";
            unset($t);
        }
    }

    public function Display_SidebandRatio(){
        $q = "SELECT keyId FROM TestData_header
              WHERE fkFE_Components = $this->keyId
              AND keyFacility = " . $this->GetValue('keyFacility') . "
              AND fkTestData_Type = 38;";

        $r = @mysql_query($q, $this->dbconnection);
        while ($row = @mysql_fetch_array($r)){
            $t = new TestData_header();
            $t->Initialize_TestData_header($row[0], $this->GetValue('keyFacility'));
            echo "<img src='" . $t->GetValue('PlotURL') . "'><br>";
            unset($t);
        }
    }

    public function Display_PhaseDrift(){
        $q = "SELECT keyId FROM TestData_header
              WHERE fkFE_Components = $this->keyId
              AND keyFacility = " . $this->GetValue('keyFacility') . "
              AND fkTestData_Type = 33;";

        $r = @mysql_query($q, $this->dbconnection);
        while ($row = @mysql_fetch_array($r)){
            $t = new TestData_header();
            $t->Initialize_TestData_header($row[0], $this->GetValue('keyFacility'));
            echo "<img src='" . $t->GetValue('PlotURL') . "'><br>";
            unset($t);
        }
    }
}

?>