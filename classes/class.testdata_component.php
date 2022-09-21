<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');

class TestData_Component extends GenericTable{
    var $ComponentType;
    
    public function __construct() {
        parent::__construct();
    }
    
    public function Initialize_TestData_Component($in_keyId){
        parent::Initialize('FE_Components',$in_keyId,'keyId');

        $q = "SELECT Description FROM ComponentTypes
              WHERE keyId = " . $this->GetValue('fkFE_ComponentType');
        $r = mysqli_query($this->dbConnection, $q);
        $this->ComponentType = ADAPT_mysqli_result($r,0);
    }

    public function DisplayMainData(){
        echo "<div style = 'width: 300px'><br><br>";
        echo "<table id = 'table1'>";
        echo "<tr>";
        echo "<th>Band</th>";
        echo "<td>" . $this->Band . "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<th>SN</th>";
        echo "<td>".$this->SN."</td>";
        echo "</tr>";
        echo "</table></div>";
    }
}