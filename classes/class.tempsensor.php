<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_dbConnect);

class Cryostat_tempsensor extends GenericTable{
    
    public function __construct() {
        parent::__construct();
    }
    
    public function Initialize_tempsensor($fkCryostat,$sensor_number,$in_fc){
        $this->dbconnection = site_getDbConnection();
        $q= "SELECT keyId FROM Cryostat_tempsensors
            WHERE fkCryostat = $fkCryostat
            AND sensor_number = $sensor_number
            AND fkFacility = $in_fc;";

        $r=mysqli_query($this->dbconnection, $q);
        $tempsensor_keyId=ADAPT_mysqli_result($r,0);
        parent::Initialize("Cryostat_tempsensors",$tempsensor_keyId,"keyId",$in_fc,'fkFacility');
    }

    public function DisplayData_TempSensor(){
        echo "<br><font size='+2'><b><u>Temperature Sensor Information</u></b></font><br>";
        echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
            echo "<br>Sensor Number:<input type='text' name='sensor_number' size='3' maxlength='3'
                      value = '".$this->GetValue('sensor_number')."'>";
            echo "<br><br>Location:<input type='text' name='location' size='30' maxlength='60'
                      value = '".$this->GetValue('location')."'>";
            echo "<br><br>Sensor Type:<input type='text' name='sensor_type' size='30' maxlength='60'
                      value = '".$this->GetValue('sensor_type')."'>";
            echo "<br><br>K1:<input type='text' name='k1' size='10' maxlength='30'
                      value = '".$this->GetValue('k1')."'>";
            echo "<br>K2:<input type='text' name='k2' size='10' maxlength='30'
                      value = '".$this->GetValue('k2')."'>";
            echo "<br>K3:<input type='text' name='k3' size='10' maxlength='30'
                      value = '".$this->GetValue('k3')."'>";
            echo "<br>K4:<input type='text' name='k4' size='10' maxlength='30'
                      value = '".$this->GetValue('k4')."'>";
            echo "<br>K5:<input type='text' name='k5' size='10' maxlength='30'
                      value = '".$this->GetValue('k5')."'>";
            echo "<br>K6:<input type='text' name='k6' size='10' maxlength='30'
                      value = '".$this->GetValue('k6')."'>";
            echo "<br>K7:<input type='text' name='k7' size='10' maxlength='30'
                      value = '".$this->GetValue('k7')."'>";

            echo "<div style ='width:100%;height:30%'>";
            echo "<div align='left' style ='width:50%;height:30%'>";
            echo "<input type='hidden' name='keyId' value='$this->keyId'>";
            echo "<br><br><input type='submit' name = 'submitted' value='SAVE CHANGES'>";
            //echo "<input type='submit' name = 'deleterecord' value='DELETE RECORD'>";
            echo "</div></div>";
        echo "</form>";

        if ($this->GetValue('SN') != ""){
            $this->Display_uploadform();
        }
    }
}
?>
