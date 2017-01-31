<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);
require_once($site_dbConnect);
require_once(site_get_config_main());

class TestDataTable {
    private $dbConnection;
    private $fc;
    private $band;
    private $keyFrontEnd;
    private $compType;
    private $compSN;
    private $FETMS_CCA_MODE;

    public function __construct($band = 0) {
        $this->dbConnection = site_getDbConnection();
        $this->fc = 40;
        $this->band = $band;
        $this->keyFrontEnd = 0;
        $this->compType = 0;
        $this->compSN = 0;
        global $FETMS_CCA_MODE;
        $this->FETMS_CCA_MODE = $FETMS_CCA_MODE;
    }

    public function setFrontEnd($keyFrontEnd) {
        $this->keyFrontEnd = $keyFrontEnd;
    }

    public function setComponent($compType, $compSN) {
        $this->compType = $compType;
        $this->compSN = $compSN;
    }

    public function DisplayAllMatching() {
        /*
         * 2017-01-18 MM combined methods from classes FEComponent and FrontEnd
         * 2015-04-28 jee for pattern data, added test number and day of week to date
         */

        // config column label:
        $configLabel = ($this->keyFrontEnd) ? "FE Config" : "Config";

        echo "<div style= 'width:950px'>";
        echo "<table id='table1'>";
        echo "<tr class = 'alt'><th colspan='7'>TEST DATA</th></tr>";
        echo "<tr>
              <th width='10px'>$configLabel</th>
              <th>Data Status</th>
              <th>Description</th>
              <th>Notes</th>
              <th>TS</th>
              <th width='10px'>for PAI</th>
              </tr>";

        // Filter on data status depending on whether this is FE data or component data, and FETMS_CCA_MODE:
        //"3"	"Cold PAI"        = data which is taken on the FETMS
        //"4"   "Health Check"    = warm and cold health check data taken on FETMS
        //"7"	"Cartridge PAI"   = data which is delivered with a CCA or WCA

        $dataStatus = '()';
        if ($this->keyFrontEnd)
            $dataStatus = '(3)';
        else
            $dataStatus = ($this->FETMS_CCA_MODE) ? '(3, 4, 7)' : '(7)';

        // Left-hand (LH) table for join is either FE_Config or FE_Components
        $lhTable = ($this->keyFrontEnd) ? "FE_Config" : "FE_Components";

        // Left hand field for join is either LH.keyFEConfig or LH.keyId
        $lhKeyId = ($this->keyFrontEnd) ? "keyFEConfig" : "keyId";

        // Right hand field for join is either TDH.fkFE_Config or TDH.fkFE_Components
        $rhKeyId = ($this->keyFrontEnd) ? "fkFE_Config" : "fkFE_Components";

        // Filter for band
        $likeBand = ($this->band) ? $this->band : '%';

        // Filter for component SN
        $likeCompSN = ($this->compSN) ? $this->compSN : '%';

        // Select TDH records matching the FE_Config or FE_Component, filtered for this band, excluding certain data...
        $q = "SELECT TDH.keyId as tdhID, TestData_Types.Description,
             TDH.fkTestData_Type, LH.$lhKeyId,
             TDH.Band, TDH.Notes, TDH.fkDataStatus,
             TDH.TS, TDH.keyFacility, DataStatus.Description AS DStatus,
             TDH.DataSetGroup, TDH.UseForPAI
             FROM $lhTable as LH, TestData_header as TDH, TestData_Types, DataStatus
             WHERE TDH.Band like '$likeBand'
             AND TDH.fkDataStatus IN $dataStatus";

        // Either matching keyFrontEnd or a particular Component serial number...
        if ($this->keyFrontEnd)
            $q .= " AND LH.fkFront_Ends = $this->keyFrontEnd";
        else
            $q .= " AND LH.SN LIKE '$likeCompSN'";

        // Optionally matching a particular component type...
        if ($this->compType)
            $q .= " AND LH.fkFE_ComponentType = $this->compType";

        // Join the LH table to TestData_header, join the auxiliary tables...
        $q .=  " AND LH.$lhKeyId = TDH.$rhKeyId
                 AND TestData_Types.keyId = TDH.fkTestData_Type
                 AND DataStatus.keyId = TDH.fkDataStatus";

        $q .= " ORDER BY ";

        // If viewing a component, we want to see configurations in reverse order...
        if ($this->compSN)
            $q .= "LH.keyId DESC, ";

        // Sorted by test description, and TS in reverse order...
        $q .= "TestData_Types.Description ASC, TDH.TS DESC;";

        $r = @mysql_query($q, $this->dbConnection);

        $record_list = array();
        $trclass = '';
        while ($row = @mysql_fetch_array($r)) {

            $fc = $row['keyFacility'];
            $keyId = $row['tdhID'];
            $configId = $row[$lhKeyId];
            $dataDesc = $row['Description'];
            $dataSetGroup = $row['DataSetGroup'];
            $dataStatusDesc = $row['DStatus'];
            $testNotes = $row['Notes'];

            // add the day of the week to the date:
            $testTS = DateTime::createFromFormat('Y-m-d H:i:s', $row['TS'])->format('D Y-m-d H:i:s');

            // save newdata header record data set in a two dimentional array
            // first dimension is test data type, second is the dataset group
            $record_list[$dataDesc][] = $dataSetGroup;
            // count how many times each dataset group occurs
            $dataset_cnt = array_count_values($record_list[$dataDesc]);

            // display row if there is only one entry for the dataset or the dataset is 0
            if ($dataSetGroup == 0 || $dataset_cnt[$dataSetGroup] <= 1) {

                $trclass = ($trclass=="" ? 'class="alt"' : "");
                echo "<tr $trclass><td width = '10px' align = 'center'>$configId</td>";
                echo "<td width = '70px'>$dataStatusDesc</td>";

                $testpage = 'testdata/testdata.php';

                switch($row['fkTestData_Type']) {
                    case 55:
                        //Beam patterns
                        $testpage = 'bp/bp.php';
                        // hyperlink with test URL and key ID
                        echo "<td width='180px'><a href='$testpage?keyheader=$keyId&fc=$fc' target = 'blank'>$dataDesc $keyId</a></td>";
                        break;
                    case 7:
                        //IFSpectrum
                        $testpage = 'ifspectrum/ifspectrumplots.php';

                        $url  = $testpage . "?fc=$fc";
                        $url .= "&fe=" . $this->keyFrontEnd . "&b=" . $row['Band'];
                        $url .= "&id=$keyId";

                        $Description = "$dataDesc Group $dataSetGroup";
                        echo "<td width='180px'><a href='$url' target = 'blank'>$Description</a></td>";
                        break;

                    case 57:
                    case 58:
                        //LO Lock Test or noise temp
                        $g = ($dataSetGroup) ? "&g=$dataSetGroup" : "";
                        $Description = ($dataSetGroup) ? "$dataDesc Group $dataSetGroup" : $dataDesc;
                        echo "<td width='180px'><a href='$testpage?keyheader=$keyId$g&fc=$fc' target = 'blank'>$Description</a></td>";
                        break;

                    default:
                        echo "<td width='180px'><a href='$testpage?keyheader=$keyId&fc=$fc' target = 'blank'>$dataDesc</a></td>";
                        break;
                }

                echo "<td width = '150px'>$testNotes</td>";
                echo "<td width = '120px'>$testTS</td>";

                // In the "for PAI" column show a checkbox corresponding to the value of UseForPAI:
                echo "<td width = '10px'>";
                if ($row[11])
                    $checked = "checked='checked'";
                    else
                        $checked = "";
                        $cboxId = "PAI_" . $row[0];
                        // Call the PAIcheckBox JS function when the checkbox is clicked:
                        echo "<input type='checkbox'
                        name='$cboxId'
                        id='$cboxId'
                        $checked
                        onchange=\"PAIcheckBox($row[0], document.getElementById('$cboxId').checked);\" />";
                        echo "</td></tr>";
            }
        }
        echo "</table></div>";
    }
}

?>
