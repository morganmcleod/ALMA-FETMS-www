<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
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

    public function getConfigKey() {
        return ($this->keyFrontEnd) ? "keyFEConfig" : "keyId";
    }

    public function getConfigKeyLabel() {
        return ($this->keyFrontEnd) ? "FE Config" : "Config";
    }

    public function DisplayAllMatching() {
        /*
         * 2018-09-07 MM Can now fetch test data for band "0" for display on the Components tab.
         * 2017-08-30 MM separated grouping, link, and text generation into helper groupHeaders()
         * 2017-01-18 MM combined methods from classes FEComponent and FrontEnd
         * 2015-04-28 jee for pattern data, added test number and day of week to date
         */

        // Fetch TDH records matching the FE_Config or FE_Component for this band:
        $r = $this -> fetchTestDataHeaders();
        $headers = $this -> groupHeaders($r);

        // config column label:
        $configLabel = $this->getConfigKeyLabel();

        // Config column is either keyFEConfig or component keyId
        $configKey = $this->getConfigKey();

        echo "<div style= 'width:900px'>";
        echo "<table id='table1'>";
        echo "<tr class = 'alt'><th colspan='7'>TEST DATA</th></tr>";
        echo "<tr>
              <th width='10px' align='center'>$configLabel</th>
              <th>Data Status</th>
              <th>Description</th>
              <th>Notes</th>
              <th>TS</th>
              <th width='10px'>Select</th>
              </tr>";

        $trclass = '';
        foreach ($headers as $row) {
            $configId = $row['configId'];
            $dataStatusDesc = $row['dataStatusDesc'];
            $link = $row['link'];
            $description = $row['description'];
            $notes = $row['notes'];
            $testTS = $row['TS'];

            $trclass = ($trclass=="" ? 'class="alt"' : "");
            echo "<tr $trclass><td width='10px' align='center'>$configId</td>";
            echo "<td width='10px' align='center'>$dataStatusDesc</td>";
            echo "<td width='70px'><a href='$link' target = 'blank'>$description</a></td>";
            echo "<td width='200px'>$notes</td>";
            echo "<td width='70px'>$testTS</td>";

            // In the "for PAI" column show a checkbox corresponding to the value of UseForPAI:
            echo "<td width='10px'>";

            $checked = "";
            if ($row['selected'])
                $checked = "checked='checked'";

            $keyId = $row['tdhId'];
            $cboxId = "PAI_" . $keyId;

            // Call the PAIcheckBox JS function when the checkbox is clicked:
            echo "<input type='checkbox' name='$cboxId' id='$cboxId' $checked
                onchange=\"PAIcheckBox($keyId, document.getElementById('$cboxId').checked, 'testdata/');\" />";
            echo "</td></tr>";
        }
        echo "</table></div>";
    }

    public function groupHeaders($resultSet) {
        // Reformat the results from fetchTestDataHeaders() into a 2d array of strings for output or display.
        // Reduces identical dataSetGroups down to a single row and creates a link to testdata.php or equiv.

        // Config column is either keyFEConfig or component keyId
        $configKey = $this->getConfigKey();

        $outputArray = array();

        $groupDetectArray = array();

        while ($row = @mysql_fetch_array($resultSet)) {

            $fc = $row['keyFacility'];
            $keyId = $row['tdhID'];
            $configId = $row[$configKey];
            $dataDesc = $row['Description'];
            $dataSetGroup = $row['DataSetGroup'];

            // add the day of the week to the date:
            $testTS = DateTime::createFromFormat('Y-m-d H:i:s', $row['TS'])->format('D Y-m-d H:i:s');

            // save newdata header record data set in a two dimentional array
            // first dimension is test data type, second is the dataset group
            $groupDetectArray[$dataDesc][] = $dataSetGroup;
            // count how many times each dataset group occurs
            $dataset_cnt = array_count_values($groupDetectArray[$dataDesc]);

            // output a row if there is only one entry for the dataset or the dataset is 0
            if ($dataSetGroup == 0 || $dataset_cnt[$dataSetGroup] <= 1) {

                switch ($row['fkTestData_Type']) {
                    case 55:
                        //Beam patterns
                        $link = "bp/bp.php?keyheader=$keyId&fc=$fc";
                        $description = "$dataDesc $keyId";
                        break;
                    case 7:
                        //IFSpectrum
                        $link = "ifspectrum/ifspectrumplots.php?fc=$fc"
                                  . "&fe=" . $this->keyFrontEnd . "&b=" . $row['Band']
                                  . "&id=$keyId";
                        $description = "$dataDesc Group $dataSetGroup";
                        break;

                    case 57:
                    case 58:
                        //LO Lock Test or noise temp
                        $g = ($dataSetGroup) ? "&g=$dataSetGroup" : "";
                        $link = "testdata/testdata.php?keyheader=$keyId$g&fc=$fc";
                        $description = ($dataSetGroup) ? "$dataDesc Group $dataSetGroup" : $dataDesc;
                        break;

                    default:
                        $link = "testdata/testdata.php?keyheader=$keyId&fc=$fc";
                        $description = $dataDesc;
                        break;
                }

                $outputRow = array(
                    "configId" => $configId,
                    "tdhId" => $keyId,
                    "dataStatusDesc" => $row['DStatus'],
                    "testDataType" => $row['fkTestData_Type'],
                    "description" => $description,
                    "group" => $dataSetGroup,
                    "link" => $link,
                    "notes" => $row['Notes'],
                    "TS" => $testTS,
                    "selected" => $row['UseForPAI']
                );

                $outputArray []= $outputRow;
            }
        }
        return $outputArray;
    }

    public function fetchTestDataHeaders($selectedOnly = false) {
        // Filter on data status depending on whether this is FE data or component data, and FETMS_CCA_MODE:
        //"1"   "Cold PAS"
        //"2"   "Warm PAS"
        //"3"	"Cold PAI"        = data which is taken on the FETMS
        //"4"   "Health Check"    = warm and cold health check data taken on FETMS
        //"7"	"Cartridge PAI"   = data which is delivered with a CCA or WCA

        $dataStatus = '()';
        if ($this->keyFrontEnd)
            $dataStatus = '(3)';
        else if ($this->band == 0)
            $dataStatus = '(1, 7)';
        else
            $dataStatus = ($this->FETMS_CCA_MODE) ? '(1, 2, 3, 4, 7)' : '(7)';

        return $this -> fetchData($dataStatus, $selectedOnly);
    }

    private function fetchData($dataStatus, $selectedOnly) {
        // Left-hand (LH) table for join is either FE_Config or FE_Components
        $lhTable = ($this->keyFrontEnd) ? "FE_Config" : "FE_Components";

        // Left hand field for join is either LH.keyFEConfig or LH.keyId
        $lhKeyId = ($this->keyFrontEnd) ? "keyFEConfig" : "keyId";

        // Right hand field for join is either TDH.fkFE_Config or TDH.fkFE_Components
        $rhKeyId = ($this->keyFrontEnd) ? "fkFE_Config" : "fkFE_Components";

        // Filter for band, including band "0" for Components tab:
        $likeBand = $this->band;

        // Filter for component SN
        $likeCompSN = ($this->compSN) ? $this->compSN : '%';

        // Select TDH records matching the FE_Config or FE_Component for this band...
        $q = "SELECT TDH.keyId as tdhID, TestData_Types.Description,
             TDH.fkTestData_Type, LH.$lhKeyId,
             TDH.Band, TDH.Notes, TDH.fkDataStatus,
             TDH.TS, TDH.keyFacility, DataStatus.Description AS DStatus,
             TDH.DataSetGroup, TDH.UseForPAI
             FROM $lhTable as LH, TestData_header as TDH, TestData_Types, DataStatus
             WHERE TDH.Band like '$likeBand'
             AND TDH.fkDataStatus IN $dataStatus";

        // Filtered for UseForPAI, aka 'selected'.
        if ($selectedOnly)
            $q .= " AND TDH.UseForPAI <> 0";

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

        // If viewing a component, we want to see heath checks at the top and configurations in reverse order...
        if ($this->compSN)
            $q .= "TDH.fkDataStatus DESC, LH.keyId DESC, ";

        // Sorted by test description, and TS in reverse order...
        $q .= "TestData_Types.Description ASC, TDH.TS DESC;";

        $r = @mysql_query($q, $this->dbConnection);
        return $r;
    }
}

?>
