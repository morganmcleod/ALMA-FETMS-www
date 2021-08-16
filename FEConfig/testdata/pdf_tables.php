<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.spec_functions.php');
require_once($site_dbConnect);

function results_section_header_html($desc) {
    return "<br><font size='+1' color='#f0fff0' face='sans-serif'><h><b>
        $desc
        </b></h></font>";
}

function table_header_html($width, &$tdh, $cols = 2, $filterChecked = false, $checkBox = "Select", $plot = null, $plot_name = null) {
    $table_ver = "1.2.3";
    /*
     * 1.2.3 Sort each section with newest at top, large font section headers
     * 1.2.2 Fix coloring of Y-factor results.
     * 1.2.1 Include FETMS_Description in table headers.
     * 1.2.0 added CCA SIS Warm Resistance table.
     * 1.1.5 using PAIcheckBox to select TDHs for filtering.
     * 1.1.4 Fix display bugs when using 2 GHz (or other) steps in Band3_NT_results()
     * 1.1.3 Removed Notes from Band3_NT_results()
     * 1.1.2 Don't show query error for CCA NT table when no data is available.
     *       "Include in PAS Report" -> "for PAI"
     * 1.1.1 Fix SIS buggy alignment of PAS monitor data with control data.
     * 1.1.0 Now pulls specifications from new class that pulls from files instead of database.
     * 1.0.6 MM fixed query fetching LNA config data for band 4.
     * 1.0.5 MM fixed error in calculating average attenuation in IF_Power_results()
     */

    $testpage = 'testdata.php';

    $q = "SELECT `Description` FROM `TestData_Types`
        WHERE `keyId` = " . $tdh->GetValue('fkTestData_Type') . "";
    $r = mysqli_query($tdh->dbconnection, $q);
    $test_name = ADAPT_mysqli_result($r, 0, 0);

    $html = '';
    // decide if the box is checked:


    // First title block line with check box
    $class_test_name = str_replace(' ', '-', rtrim($test_name));
    if (is_null($plot)) {
        $html .= "<div class='$class_test_name'>";
    } else {
        $html .= "<div class='$plot'>";
    }
    $html .= "<table class='table-health'><tr>";
    if (is_null($plot_name)) {
        $html .= "<th class='table-name' colspan=$cols>$test_name</th>";
    } else {
        $html .= "<th class='table-name' colspan=$cols>$plot_name</th>";
    }
    $html .= "</tr>";

    // second title block line
    $fetms = $tdh->GetFetmsDescription(" at: ");
    $html .= "<tr><th colspan=$cols>Measured" . $fetms . " " . $tdh->GetValue('TS') .
        ", TDH: <a href='$testpage?keyheader=" . $tdh->GetValue('keyId') . "&fc=40' target = 'blank'>" . $tdh->GetValue('keyId') . "</a>
            </th></tr>";

    //third title block line
    // check to see if it was a FE component test or a FE config test
    if ($tdh->GetValue('fkFE_Config') != 0) {
        $html .= "<tr><th colspan=$cols> FE Config: " . $tdh->GetValue('fkFE_Config') .
            ", Table SWVer: $table_ver, Meas SWVer: " . $tdh->GetValue('Meas_SWVer') . "
                </th></tr>";
    } else {
        $html .= "<tr><th colspan=$cols> FE Component: " . $tdh->GetValue('fkFE_Components') .
            ", Table SWVer: $table_ver, Meas SWVer: " . $tdh->GetValue('Meas_SWVer') . "
                </th></tr>";
    }
    return $html;
}

function band_results_table_html($FE_Config, $band, $Data_Status, $TestData_Type, $filterChecked) {

    $dbconnection = site_getDbConnection();
    $q = "SELECT `keyId` FROM `TestData_header`
        WHERE `fkFE_Config` = $FE_Config
        AND `fkTestData_Type` = $TestData_Type
        AND BAND = $band AND fkDataStatus = $Data_Status
        ORDER BY `keyId` DESC";
    $r = mysqli_query($dbconnection, $q) or die("QUERY FAILED: $q");

    $html = null;
    if ($TestData_Type == 39) {
        $html = ["", ""];
    } else {
        $html = "";
    }

    $count_iv = 0;
    while ($row = mysqli_fetch_array($r)) {
        switch ($TestData_Type) {
            case 1:
                $html = LNA_results_html($row[0], $filterChecked);
                return $html;
                break;
            case 2:
                $html = Temp_Sensor_results_html($row[0], $filterChecked);
                return $html;
                break;
            case 3:
                $html = SIS_results_html($row[0], $filterChecked);
                return $html;
                break;
            case 60:
                $html = SIS_Resistance_results_html($row[0], $filterChecked);
                return $html;
                break;
            case 6:
                $html = IF_Power_results_html($row[0], $filterChecked);
                return $html;
                break;
            case 12:
                $html = WCA_AMC_results_html($row[0], $filterChecked);
                return $html;
                break;
            case 13:
                $html = WCA_PA_results_html($row[0], $filterChecked);
                return $html;
                break;
            case 14:
                $html = WCA_MISC_results_html($row[0], $filterChecked);
                return $html;
                break;
            case 15:
                $html = Y_factor_results_html($row[0], $filterChecked);
                return $html;
                break;
            case 39:
                ++$count_iv;
                if ($count_iv <= 4) {
                    $html[0] .= I_V_Curve_results_html($row[0], $filterChecked, $count_iv);
                } else {
                    $html[1] .= I_V_Curve_results_html($row[0], $filterChecked, $count_iv);
                }
                break;
        }
        if ($band > 8) {
            if ($count_iv >= 4) {
                break;
            }
        } else {
            if ($count_iv >= 8) {
                break;
            }
        }
    }
    return $html;
}

function results_table_html($FE_Config, $Data_Status, $TestData_Type, $filterChecked) {
    $dbconnection = site_getDbConnection();
    $q = "SELECT keyId FROM `TestData_header`
        WHERE `fkFE_Config` = $FE_Config
        AND `fkTestData_Type` = $TestData_Type
        AND fkDataStatus = $Data_Status
        ORDER BY `keyId` DESC";
    $r = mysqli_query($dbconnection, $q) or die("QUERY FAILED: $q");
    $html = "";
    while ($row = mysqli_fetch_array($r)) {
        switch ($TestData_Type) {
            case 4:
                $html .= Cryo_Temp_results_html($row[0], $filterChecked);
                break;
            case 5:
                $html .= FLOOG_results_html($row[0], $filterChecked);
                break;
            case 8:
                $html .= LPR_results_html($row[0], $filterChecked);
                break;
            case 9:
                $html .= Photomixer_results_html($row[0], $filterChecked);
                break;
            case 10:
                $html .= IF_Switch_Temp_results_html($row[0], $filterChecked);
                break;
            case 24:
                $html .= CPDS_results_html($row[0], $filterChecked);
                break;
        }
    }
    return $html;
}


/**
 * returns a number formated to display only two decimal places
 *
 * @param $number (float) - number to format
 *
 */
function mon_data_html($number) {
    return number_format((float)$number, 2, '.', '');
}

function update_dataset_html($td_keyID, $data_set_group) {
    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID, "40");
    $tdh->SetValue('DataSetGroup', $data_set_group);
    $tdh->Update();
}


// CPDS monitors
/**
 * echos a HTML table that contains CPDS monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function CPDS_results_html($td_keyID, $filterChecked) {
    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID, "40");

    $col_name = array("Band", "+6V Voltage", "-6V Voltage", "+15V Voltage", "-15V Voltage", "+24V Voltage", "+8V Voltage", "+6V Current", "-6V Current", "+15V Current", "-15V Current", "+24V Current", "+8V Current");

    $html = table_header_html(900, $tdh, count($col_name), $filterChecked);

    if ($html) {

        $q = "SELECT `Band`, `P6V_V`,`N6V_V`,`P15V_V`,`N15V_V`,`P24V_V`,`P8V_V`,`P6V_I`,`N6V_I`,`P15V_I`,`N15V_I`,`P24V_I`,`P8V_I`
                FROM `CPDS_monitor`
                WHERE `fkHeader` = $td_keyID
                ORDER BY BAND ASC";
        $r = mysqli_query($tdh->dbconnection, $q) or die("QUERY FAILED: $q");

        // write table subheader
        $html .= "</tr>";
        foreach ($col_name  as $Col) {
            $html .= "<th>" . $Col . "</th>";
        }
        $html .= "</tr>";

        // Write data to table
        while ($row = mysqli_fetch_array($r)) {
            $html .= "<tr>";
            for ($i = 0; $i < 13; $i++) {
                $html .= "<td>$row[$i]</td>    ";
            }
            $html .= "<tr>";
        }
        $html .= "</table></div>";
    }
    return $html;
}


// LNA - Actual Readings
/**
 * echos a HTML table that contains CCA LNA monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function LNA_results_html($td_keyID, $filterChecked) {

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID, "40");

    $html = table_header_html(700, $tdh, 8, $filterChecked);

    if ($html) {

        // get specifications array
        $new_spec = new Specifications();
        $spec = $new_spec->getSpecs('CCA_LNA_bias', 0);

        //get and save Monitor Data
        $q = "SELECT Pol, SB, Stage, FreqLO, VdRead, IdRead, VgRead
            FROM CCA_LNA_bias
            WHERE fkHeader = $td_keyID ORDER BY `Pol`ASC, `SB` ASC, Stage ASC";
        $r = mysqli_query($tdh->dbconnection, $q) or die("QUERY FAILED: $q");

        $FreqLO = 0;
        $Cntrl_FreqLO = 0;
        $output = array();
        while ($row = mysqli_fetch_array($r)) {
            // cache the LO frequency:
            if (!$FreqLO)
                $FreqLO = $row[3];
            // insert the results keyed by Pol, LNA, and Stage:
            $key = 'Pol' . $row[0] . " LNA" . $row[1];
            $stageKey = 'Stage ' . $row[2];
            $stageData = array(
                'VdRead' => mon_data_html($row[4]),
                'IdRead' => mon_data_html($row[5]),
                'VgRead' => mon_data_html($row[6])
            );
            if (!isset($output[$key]))
                $output[$key] = array();
            $output[$key][$stageKey] = $stageData;
        }

        // If any rows found, so FreqLO got assigned:
        if ($FreqLO) {
            //get and save Control Data
            $q_CompID = "SELECT MAX(FE_Components.keyId)
                FROM `FE_Components` JOIN `FE_ConfigLink`
                ON FE_Components.keyId = FE_ConfigLink.fkFE_Components
                WHERE  FE_ConfigLink.fkFE_Config =" . $tdh->GetValue('fkFE_Config') . "
                AND `fkFE_ComponentType`= 20 AND Band =" . $tdh->GetValue('Band') . "";

            // data queries
            $q = "SELECT `Pol`,`SB`,`FreqLO`,`VD1`,`VD2`,`VD3`,`ID1`,`ID2`,`ID3`,`VG1`,`VG2`,`VG3`
                    FROM `CCA_PreampParams`
                    WHERE `fkComponent`=($q_CompID)";

            $ord = " ORDER BY `Pol` ASC, `SB` ASC;";

            // default query looks for exact LO match
            $q_default = $q . " AND `FreqLO`= $FreqLO" . $ord;

            // alternate query matches any LO
            $q_any_lo = $q . $ord;

            // try the exact LO match query:
            $r = mysqli_query($tdh->dbconnection, $q_default) or die("QUERY FAILED: $q_default");

            // if no result, try the any LO query:
            $numRows = mysqli_num_rows($r);
            if (!$numRows)
                $r = mysqli_query($tdh->dbconnection, $q_any_lo) or die("QUERY FAILED: $q_any_lo");

            // Match up control data with monitor data:
            while ($row = mysqli_fetch_array($r)) {
                // cache the LO frequency:
                if (!$Cntrl_FreqLO)
                    $Cntrl_FreqLO = $row[2];
                // insert the results keyed by Pol, LNA, and Stage:
                $key = 'Pol' . $row[0] . " LNA" . $row[1];
                if (isset($output[$key])) {
                    for ($stage = 0; $stage < 3; $stage++) {
                        $stageKey = 'Stage ' . ($stage + 1);
                        if (isset($output[$key][$stageKey])) {
                            $output[$key][$stageKey]['VD'] = $row[3 + $stage];
                            $output[$key][$stageKey]['ID'] = $row[6 + $stage];
                            $output[$key][$stageKey]['VG'] = $row[9 + $stage];
                        }
                    }
                }
            }
        }
        $html .= "<tr><th colspan='2' rowspan='2'>Device</th>
            <th colspan='3'>Control Values: (LO $Cntrl_FreqLO Ghz)</th>
            <th colspan='3'>Monitor Values: (LO $FreqLO Ghz)</th></tr>
            <tr>
            <th>Vd(V)</th>
            <th>Id(mA)</th>
            <th>Vg(V)</th>
            <th>Vd(V)</th>
            <th>Id(mA)</th>
            <th>Vg(V)</th></tr>";

        if (!count($output)) {
            $html .= "<tr><td colspan='8'>NO DATA</td></tr>";
        } else {
            foreach ($output as $key => $device) {
                $first = true;
                foreach ($device as $stageKey => $row) {
                    $html .= "<tr><td>";
                    if ($first) {
                        $html .= $key;
                        $first = false;
                    }
                    $html .= "</td>";
                    $html .= "<td>" . $stageKey . "</td>";
                    $html .= "<td>" . (isset($row['VD']) ? $row['VD'] : "") . "</td>";
                    $html .= "<td>" . (isset($row['ID']) ? $row['ID'] : "") . "</td>";
                    $html .= "<td>" . (isset($row['VG']) ? $row['VG'] : "") . "</td>";

                    // check to see if Vd is in spec
                    $mon_Vd = "";
                    if (isset($row['VdRead'])) {
                        $mon_Vd = $row['VdRead'];
                        if (isset($row['VD'])) {
                            $mon_Vd = $new_spec->numWithinPercent($mon_Vd, $row['VD'], $spec['Vd_diff']);
                        }
                    }
                    $html .= "<td>$mon_Vd</td>";

                    // check to see if Id is in spec
                    $mon_Id = "";
                    if (isset($row['IdRead'])) {
                        $mon_Id = $row['IdRead'];
                        if (isset($row['ID'])) {
                            $mon_Id = $new_spec->numWithinPercent($mon_Id, $row['ID'], $spec['Id_diff']);
                        }
                    }
                    $html .= "<td>$mon_Id</td>";

                    // display Vg:
                    $mon_Vg = "";
                    if (isset($row['VgRead']))
                        $mon_Vg = $row['VgRead'];
                    $html .= "<td>" . $mon_Vg . "</td></tr>";
                }
            }
        }
        $html .= "</table></div>";
    }
    return $html;
}


// SIS � Actual Readings
/**
 * echos a HTML table that contains SIS monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function SIS_results_html($td_keyID, $filterChecked) {

    //get and save Monitor Data
    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID, "40");

    $html = table_header_html(700, $tdh, 8, $filterChecked);

    if ($html) {

        // get specs object for SIS bias:
        $new_spec = new Specifications();
        $spec = $new_spec->getSpecs('CCA_SIS_bias', 0);

        $q = "SELECT `Pol`,`SB`,`FreqLO`,`VjRead`,`IjRead`,`VmagRead`,`ImagRead`
            FROM `CCA_SIS_bias`
            WHERE `fkHeader` = $td_keyID ORDER BY `Pol`ASC, `SB` ASC";
        $r = mysqli_query($tdh->dbconnection, $q) or die("QUERY FAILED: $q");

        $FreqLO = 0;
        $output = array();
        while ($row = mysqli_fetch_array($r)) {
            // cache the LO frequency:
            if (!$FreqLO)
                $FreqLO = $row[2];
            // insert the results keyed by Pol and SIS description:
            $key = 'Pol' . $row[0] . " SIS" . $row[1];
            $output[$key] = array(
                'VjRead' => mon_data_html($row[3]),
                'IjRead' => mon_data_html($row[4]),
                'VmagRead' => mon_data_html($row[5]),
                'ImagRead' => mon_data_html($row[6])
            );
        }

        // If any rows found, so FreqLO got assigned:
        if ($FreqLO) {

            //get and save Control Data
            $q_CompID = "SELECT DISTINCT MAX(FE_Components.keyId)
                FROM `FE_Components` JOIN `FE_ConfigLink`
                ON FE_Components.keyId = FE_ConfigLink.fkFE_Components
                WHERE  FE_ConfigLink.fkFE_Config =" . $tdh->GetValue('fkFE_Config') . "
                AND `fkFE_ComponentType`= 20 AND Band =" . $tdh->GetValue('Band') . "";

            $q = "SELECT `Pol`,`SB`,`VJ`,`IJ`,`IMAG` FROM `CCA_MixerParams` WHERE `fkComponent` = ($q_CompID)";

            $ord = " ORDER BY `Pol`ASC, `SB` ASC;";

            $q_default = $q . " AND `FreqLO` = $FreqLO" . $ord;
            $q_any_lo = $q . $ord;

            // try the exact LO match query:
            $r = mysqli_query($tdh->dbconnection, $q_default) or die("QUERY FAILED: $q_default");

            // if no result, try the any LO query:
            $numRows = mysqli_num_rows($r);
            if (!$numRows)
                $r = mysqli_query($tdh->dbconnection, $q_any_lo) or die("QUERY FAILED: $q_any_lo");

            // Match up control data with monitor data:
            while ($row = mysqli_fetch_array($r)) {
                $key = 'Pol' . $row[0] . " SIS" . $row[1];
                if (isset($output[$key])) {
                    $output[$key]['VJ'] = mon_data_html($row[2]);
                    $output[$key]['IJ'] = mon_data_html($row[3]);
                    $output[$key]['IMAG'] = mon_data_html($row[4]);
                }
            }
        }

        $html .= "<tr><th rowspan='2'>Device</th>
            <th colspan='3'>Control Values</th>
            <th colspan='4'>Monitor Values</th></tr>
            <tr>
            <th>Bias Voltage (mV)</th>
            <th>Bias Current (uA)</th>
            <th>Magnet Current (mA)</th>
            <th>Bias Voltage (mV)</th>
            <th>Bias Current (uA)</th>
            <th>Magnet Voltage (V)</th>
            <th>Magnet Current (mA)</th></tr></tr>";

        if (!count($output)) {
            $html .= "<tr><td colspan='8'>NO DATA</td></tr>";
        } else {
            foreach ($output as $key => $row) {
                $VJ = "";
                if (isset($row['VJ']))
                    $VJ = $row['VJ'];
                $IJ = "";
                if (isset($row['IJ']))
                    $IJ = $row['IJ'];
                $IMAG = "";
                if (isset($row['IMAG']))
                    $IMAG = $row['IMAG'];

                $html .= "<tr>
                      <td>$key</td>
                      <td>$VJ</td>
                      <td>$IJ</td>
                      <td>$IMAG</td>";

                // check to see if Bias voltage is in spec
                $mon_Bias_V = "";
                if (isset($row['VjRead'])) {
                    $mon_Bias_V = $row['VjRead'];
                    if (isset($row['VJ'])) {
                        $mon_Bias_V = $new_spec->numWithinPercent($mon_Bias_V, $row['VJ'], $spec['bias_V_diff']);
                    }
                }

                // check to see if Bias currrent is in spec
                $mon_Bias_I = "";
                if (isset($row['IjRead'])) {
                    $mon_Bias_I = $row['IjRead'];
                    if (isset($row['IJ'])) {
                        $mon_Bias_I = $new_spec->numWithinPercent($mon_Bias_I, $row['IJ'], $spec['bias_I_diff']);
                    }
                }

                // display magnet voltage:
                $mon_Mag_V = "";
                if (isset($row['VmagRead']))
                    $mon_Mag_V = $row['VmagRead'];

                // check to see if Magnet currrent is in spec
                $mon_Mag_I = "";
                if (isset($row['ImagRead'])) {
                    $mon_Mag_I = $row['ImagRead'];
                    if (isset($row['IMAG'])) {
                        $mon_Mag_I = $new_spec->numWithinPercent($mon_Mag_I, $row['IMAG'], $spec['magI_diff']);
                    }
                }

                $html .= "<td>$mon_Bias_V</td>
                      <td>$mon_Bias_I</td>
                      <td>$mon_Mag_V</td>
                      <td>$mon_Mag_I</td>
                      </tr>";
            }
        }
        $html .= "</table></div>";
    }
    return $html;
}

// SIS Warm Resistance
/**
 * echos a HTML table that contains warm SIS resistence
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 * Requires the following database changes:
 *

INSERT INTO `TestData_Types` (`keyId`, `TestData_TableName`, `Description`) VALUES (60, 'CCA_TEST_SISResistance', 'CCA SIS Warm Resistance');

CREATE TABLE `CCA_TEST_SISResistance` (
	`keyId` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`fkHeader` INT(10) UNSIGNED NULL DEFAULT NULL,
	`TS` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`Pol` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
	`SB` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
	`ROhms` DOUBLE NULL DEFAULT NULL,
	PRIMARY KEY (`keyId`),
	INDEX `Index 1` (`fkHeader`)
)
COLLATE='latin1_swedish_ci'
ENGINE=MyISAM;
 *
 */
function SIS_Resistance_results_html($td_keyID, $filterChecked) {
    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID, "40");

    $html = table_header_html(475, $tdh, 2, $filterChecked);

    if ($html) {

        $new_spec = new Specifications();

        $q = "SELECT `Pol`,`SB`,`ROhms`
        FROM `CCA_TEST_SISResistance`
        WHERE `fkHeader` = $td_keyID ORDER BY `Pol`ASC, `SB` ASC";
        $r = mysqli_query($tdh->dbconnection, $q) or die("QUERY FAILED: $q");

        $html .= "<tr><th>Device</th><th colspan='2'>Resistance (Ohms)</th></tr>";

        while ($row = mysqli_fetch_array($r)) {
            $key = 'Pol' . $row[0] . " SIS" . $row[1];
            $html .= "<tr><td>$key</td><td>" . $row[2] . "</td></tr>";
        }
        $html .= "</table></div>";
    }
    return $html;
}


// Temperature Sensors � Actual Readings
/**
 * echos a HTML table that contains Temperature Sensor monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function Temp_Sensor_results_html($td_keyID, $filterChecked) {
    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID, "40");

    $html = table_header_html(475, $tdh, 2, $filterChecked);

    if ($html) {

        $new_spec = new Specifications();

        $html .= "<tr><th colspan='1'>Monitor Point</th><th colspan='1'>Monitor Values (K)</th>";

        $col_name = array("4k", "110k", "Pol0_mixer", "Spare", "15k", "Pol1_mixer");
        $col_strg = implode(",", $col_name);
        $q = "SELECT $col_strg
            FROM CCA_TempSensors
            WHERE fkHeader= $td_keyID";

        $r = mysqli_query($tdh->dbconnection, $q) or die("QUERY FAILED: $q");
        $i = 0;
        foreach ($col_name  as $Col) {
            $html .= "<tr>";
            $html .= "<td colspan='1'>" . $Col . "</td>";
            // check to see if data Status is: Cold PAS, Cold PAI or Health check
            $test_type_array = array("1", "3", "4");
            if (in_array($tdh->GetValue('fkDataStatus'), $test_type_array)) {
                // check to see if line is a 4k stage
                $cold_array = array("4k", "Pol0_mixer", "Pol1_mixer");
                if (in_array($Col, $cold_array)) {
                    $num = ADAPT_mysqli_result($r, 0, $i);
                    // check to see if 4k stange meets spec
                    $num = $new_spec->chkNumAgnstSpec($num, "<", 4);
                } else {
                    $num = ADAPT_mysqli_result($r, 0, $i);
                }
            } else {
                $num = ADAPT_mysqli_result($r, 0, $i);
            }
            $html .= "<td colspan='1'>$num</td></tr>";
            $i++;
        }
        $html .= "</table></div>";
    }
    return $html;
}


// WCA AMC Monitors
/**
 * echos a HTML table that contains WCA AMC monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function WCA_AMC_results_html($td_keyID, $filterChecked) {

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID, "40");

    $html = table_header_html(475, $tdh, 2, $filterChecked);

    if ($html) {

        // get WCA_AMC_bias values
        $col_name = array("VDA", "VDB", "VDE", "IDA", "IDB", "IDE", "VGA", "VGB", "VGE", "MultD", "MultD_Current", "5Vsupply");
        $col_strg = implode(",", $col_name);

        $q = "SELECT $col_strg
            FROM WCA_AMC_bias
            WHERE fkHeader= $td_keyID";

        $r = mysqli_query($tdh->dbconnection, $q) or die("QUERY FAILED: $q");;

        $html .= "<tr><th colspan='1'>Monitor Point</th><th colspan='1'>Monitor Values</th>";

        $i = 0;
        foreach ($col_name  as $Col) {
            $html .= "<tr>
            <td colspan='1'>" . $Col . "</td>
            <td colspan='1'>" . ADAPT_mysqli_result($r, 0, $i) . "</td></tr>";
            $i++;
        }
        $html .= "</table></div>";
    }
    return $html;
}


// WCA PA Monitors
/**
 * echos a HTML table that contains WCA PA monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function WCA_PA_results_html($td_keyID, $filterChecked) {

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID, "40");

    $html = table_header_html(475, $tdh, 2, $filterChecked);

    if ($html) {

        // get PA_bias values
        $col_name = array("VDp0", "VDp1", "IDp0", "IDp1", "VGp0", "VGp1", "3Vsupply", "5Vsupply");
        $col_strg = implode(",", $col_name);

        $q = "SELECT $col_strg
            FROM WCA_PA_bias
            WHERE fkHeader= $td_keyID";

        $r = mysqli_query($tdh->dbconnection, $q) or die("QUERY FAILED: $q");

        $html .= "<tr><th colspan='1'>Monitor Point</th><th colspan='1'>Monitor Values</th>";

        $i = 0;
        foreach ($col_name  as $Col) {
            $html .= "<tr>
            <td colspan='1'>" . $Col . "</td>
            <td colspan='1'>" . ADAPT_mysqli_result($r, 0, $i) . "</td></tr>";
            $i++;
        }
        $html .= "</table></div>";
    }
    return $html;
}


// WCA Misc Monitors
/**
 * echos a HTML table that contains WCA Misc monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function WCA_MISC_results_html($td_keyID, $filterChecked) {

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID, "40");

    $html = table_header_html(475, $tdh, 2, $filterChecked);

    if ($html) {

        // get Misc_bias values
        $col_name = array("PLLtemp", "YTO_heatercurrent");
        $col_strg = implode(",", $col_name);

        $q = "SELECT $col_strg
            FROM WCA_Misc_bias
            WHERE fkHeader= $td_keyID";

        $r = mysqli_query($tdh->dbconnection, $q) or die("QUERY FAILED: $q");

        $html .= "<tr><th colspan='1'>Monitor Point</th><th colspan='1'>Monitor Values</th>";

        $i = 0;
        foreach ($col_name  as $Col) {
            $html .=  "<tr>
            <td colspan='1'>" . $Col . "</td>
            <td colspan='1'>" . ADAPT_mysqli_result($r, 0, $i) . "</td></tr>";
            $i++;
        }
        $html .= "</table></div>";
    }
    return $html;
}

// FLOOG Total Power
/**
 * echos a HTML table that contains FLOOG monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function FLOOG_results_html($td_keyID, $filterChecked) {

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID, "40");

    $html = table_header_html(475, $tdh, 2, $filterChecked);

    if ($html) {

        $q = "SELECT `Band`, `RefTotalPower` FROM `FLOOGdist`
                WHERE `fkHeader` = $td_keyID";
        $r = mysqli_query($tdh->dbconnection, $q) or die("QUERY FAILED: $q");

        $html .= "<tr><th></th><th colspan='2'>Reference Total Power (dBm)</th><tr>";

        while ($row = mysqli_fetch_array($r)) {
            $html .= "<tr>
            <td width = '100px'>Band $row[0] WCA</td>
            <td width = '300px'>$row[1]</td></tr>";
        }
        $html .= "</table></div>";
    }
    return $html;
}


// Nominal IF power levels
/**
 * echos a HTML table that contains Nominal IF power level monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function IF_Power_results_html($td_keyID, $filterChecked) {

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID, "40");

    $html = table_header_html(475, $tdh, 3, $filterChecked);

    if ($html) {

        $q = "SELECT `IFChannel`,`Power_0dB_gain`,`Power_15dB_gain`
            FROM `IFTotalPower`
            WHERE `fkHeader` = $td_keyID";

        $r = mysqli_query($tdh->dbconnection, $q) or die("QUERY FAILED: $q");

        $html .= "<tr><th>IFChannel</th>
            <th>Power 0dB gain (dBm)</th>
            <th>Power 15dB gain (dBm)</th>";

        $atten_cnt = 0;
        $att_sum = 0;

        $new_spec = new Specifications();

        while ($row = mysqli_fetch_array($r)) {
            $html .= "<tr>";
            $att_sum = $att_sum + abs($row[2] - $row[1]);
            $atten_cnt++;
            // check to see if the numbers meet spec
            $check1 = $new_spec->numInRange(($row[2] - 14), mon_data_html($row[1]), ($row[2] - 16));
            $check2 = $new_spec->numInRange(($row[1] + 16), mon_data_html($row[2]), ($row[1] + 14));
            switch ($row[0]) {
                case 0:
                    $html .= "<td>IF0 Pol 0 USB</td>";
                    $html .= "<td>$check1</td>";
                    $html .= "<td>$check2</td></tr>";
                    break;
                case 1:
                    $html .= "<td>IF1 Pol 1 USB</td>";
                    $html .= "<td>$check1</td>";
                    $html .= "<td>$check2</td></tr>";
                    break;
                case 2:
                    $html .= "<td>IF2 Pol 0 LSB</td>";
                    $html .= "<td>$check1</td>";
                    $html .= "<td>$check2</td></tr>";
                    break;
                case 3:
                    $html .= "<td>IF3 Pol 1 LSB</td>";
                    $html .= "<td>$check1</td>";
                    $html .= "<td>$check2</td></tr>";
                    break;
            }
        }
        $avg_atten = ($atten_cnt) ? mon_data_html($att_sum / $atten_cnt) : 0;
        //check to see if avgerage attunuation is in range
        $check3 = $new_spec->numInRange(16, $avg_atten, 14);
        $html .= "<tr><th>Average Attenutation (dB) </th>
            <th colspan='2' align='right'>" . $check3 . "</th>";
        $html .= "</table></div>";
    }
    return $html;
}


// IF switch temperature sensors
/**
 * echos a HTML table that contains IF switch Temperature sensor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function IF_Switch_Temp_results_html($td_keyID, $filterChecked) {

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID, "40");

    $html = table_header_html(475, $tdh, 2, $filterChecked);

    if ($html) {

        $col_name = array("pol0sb1", "pol0sb2", "pol1sb1", "pol1sb2");
        $col_strg = implode(",", $col_name);
        $q = "SELECT $col_strg
            FROM IFSwitchTemps
            WHERE fkHeader= $td_keyID";

        $html .= "<tr><th></th><th colspan='2'>Monitor Values (K)</th>";

        $r = mysqli_query($tdh->dbconnection, $q) or die("QUERY FAILED: $q");
        $i = 0;
        foreach ($col_name  as $Col) {
            $html .= "<tr>";
            $html .= "<td width = '100px'>" . $Col . "</td>";
            $html .= "<td width = '300px'>" . ADAPT_mysqli_result($r, 0, $i) . "</td></tr>";
            $i++;
        }
        $html .= "</table></div>";
    }
    return $html;
}


// WCA PA Monitors
/**
 * echos a HTML table that contains WCA PA monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function LPR_results_html($td_keyID, $filterChecked) {

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID, "40");

    $html = table_header_html(475, $tdh, 2, $filterChecked);

    if ($html) {

        $col_name = array("Laser Pump Temperature (K)", "Laser Drive Current (mA)", "Laser Photodetector Current (mA)", "Photodetector Current (mA)", "Photodetector Power (mW)", "Modulation Input (V)", "TempSensor0 (K)", "TempSensor1 (K)");
        $q = "SELECT `LaserPumpTemp`, `LaserDrive`, `LaserPhotodetector`, `Photodetector_mA`, `Photodetector_mW`, `ModInput`, `TempSensor0`, `TempSensor1`
            FROM `LPR_WarmHealth`
            WHERE `fkHeader`= $td_keyID";

        $r = mysqli_query($tdh->dbconnection, $q) or die("QUERY FAILED: $q");

        $html .= "<tr><th>Monitor Point</th><th colspan='2'>Monitor Values </th>";

        $i = 0;
        foreach ($col_name  as $Col) {
            $html .= "<tr>";
            $html .= "<td width = '250px'>" . $Col . "</td>";
            $html .= "<td width = '150px'>" . ADAPT_mysqli_result($r, 0, $i) . "</td></tr>";
            $i++;
        }
        $html .= "</table></div>";
    }
    return $html;
}


// Photomixer Monitor Data
/**
 * echos a HTML table that contains Photomixer monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function Photomixer_results_html($td_keyID, $filterChecked) {

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID, "40");

    $html = table_header_html(475, $tdh, 2, $filterChecked);

    if ($html) {

        $q = "SELECT `Band`,`Vpmx`, `Ipmx`
            FROM `Photomixer_WarmHealth`
            WHERE `fkHeader` = $td_keyID";

        $r = mysqli_query($tdh->dbconnection, $q) or die("QUERY FAILED: $q");

        $col_name = array("Photomixer Voltage (V)", "Photomixer Current (mA)");

        $html .= "<tr><th>Monitor Point</th><th colspan='2'>Monitor Values </th>";

        while ($row = mysqli_fetch_array($r)) {
            $html .= "<tr>
            <td width = '250px' ALIGN='LEFT'> Band $row[0]</td>
            <td width = '150px'></td></tr>
            <td width = '250px'>Photomixer Voltage (V)</td>
            <td width = '150px'>$row[1]</td></tr>
            <td width = '250px'>Photomixer Current (mA)</td>
            <td width = '150px'>$row[2]</td></tr>";
        }
        $html .= "</table></div>";
    }
    return $html;
}


// Cryo-cooler Temperatures
/**
 * echos a HTML table that contains Cryo-cooler monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function Cryo_Temp_results_html($td_keyID, $filterChecked) {

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID, "40");

    $html = table_header_html(475, $tdh, 2, $filterChecked);

    if ($html) {

        $col_name = array("4k_CryoCooler", "4k_PlateLink1", "4k_PlateLink2", "4k_PlateFarSide1", "4k_PlateFarSide2", "15k_CryoCooler", "15k_PlateLink", "15k_PlateFarSide", "15k_Shield", "110k_CryoCooler", "110k_PlateLink", "110k_PlateFarSide", "110k_Shield");
        $col_strg = implode(",", $col_name);
        $q = "SELECT $col_strg
            FROM `CryostatTemps`
            WHERE `fkHeader` = $td_keyID";

        $html .= "<tr><th>Monitor Point</th><th colspan='2'>Monitor Values (K)</th>";

        $r = mysqli_query($tdh->dbconnection, $q) or die("QUERY FAILED: $q");
        $i = 0;
        foreach ($col_name  as $Col) {
            $html .= "<tr>
            <td width = '250px'>" . $Col . "</td>
            <td width = '150px'>" . ADAPT_mysqli_result($r, 0, $i) . "</td></tr>";
            $i++;
        }
        $html .= "</table></div>";
    }
    return $html;
}


// Y-factor
/**
 * echos a HTML table that contains Y-factor monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function Y_factor_results_html($td_keyID, $filterChecked) {

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID, "40");

    $html = table_header_html(475, $tdh, 4, $filterChecked);

    if ($html) {

        // get specifications array
        //$spec=get_specs ( 15 , $tdh->GetValue('Band') );
        $new_spec = new Specifications();
        $spec = $new_spec->getSpecs('Yfactor', $tdh->GetValue('Band'));

        $col_name = array("IFchannel", "Phot_dBm", "Pcold_dBm", "Y", "FreqLO");
        $col_strg = implode(",", $col_name);
        $q = "SELECT $col_strg
            FROM Yfactor
            WHERE fkHeader= $td_keyID";
        $r = mysqli_query($tdh->dbconnection, $q) or die("QUERY FAILED: $q");

        $FreqLO = ADAPT_mysqli_result($r, 0, 4);
        mysqli_data_seek($r, 0);

        $html .= "<tr><th colspan='1'>LO= $FreqLO GHz</th>
            <th colspan='1'>Phot (dBm)</th>
            <th colspan='1'>Pcold (dBm)</th>
            <th colspan='1'>Y-Factor</th></tr>";

        $Ycnt = 0;
        $Ysum = 0;
        $Ymin = $spec['Ymin'];
        $Ymax = $spec['Ymax'];

        while ($row = mysqli_fetch_array($r)) {
            $Ysum += $row[3];
            $Ycnt++;

            switch ($row[0]) {
                case 0:
                    $html .= "<tr><td colspan='1'>IF0 Pol 0 USB</td>";
                    break;

                case 1:
                    $html .= "<tr><td colspan='1'>IF1 Pol 1 USB</td>";
                    break;

                case 2:
                    $html .= "<tr><td colspan='1'>IF2 Pol 0 LSB</td>";
                    break;

                case 3:
                    $html .= "<tr><td colspan='1'>IF3 Pol 1 LSB</td>";
                    break;
            }
            $html .= "<td colspan='1'>" . mon_data_html($row[1]) . "</td>
                <td colspan='1'>" . mon_data_html($row[2]) . "</td>";

            // check to see if Y factor is in spec
            $Y_factor = $new_spec->chkNumAgnstSpec(mon_data_html($row[3]), "range", $Ymin, $Ymax);
            $html .= "<td>$Y_factor</tr> ";
        }
        $Yavg = mon_data_html($Ysum / $Ycnt);
        $YavgText = $new_spec->chkNumAgnstSpec($Yavg, "range", $Ymin, $Ymax);
        $html .= "<tr><th colspan='3'>Average Y factor </th><th colspan='1'>$YavgText</th></tr>";
        $html .= "</table></div>";
    }
    return $html;
}

// I-V_Curve
/**
 * echos a HTML table that contains I-V Curve monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function I_V_Curve_results_html($td_keyID, $filterChecked, $count_iv) {
    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID, "40");
    $plot = 'iv_curve_' . $count_iv;
    $plot_name = null;
    $band = $tdh->GetValue('Band');
    if ($band <= 8) {
        switch ($count_iv) {
            case 1:
                $plot_name = "I-V Curve Unpumped Pol 1 SIS 2";
                break;
            case 2:
                $plot_name = "I-V Curve Unpumped Pol 1 SIS 1";
                break;
            case 3:
                $plot_name = "I-V Curve Unpumped Pol 0 SIS 2";
                break;
            case 4:
                $plot_name = "I-V Curve Unpumped Pol 0 SIS 1";
                break;
            case 5:
                $plot_name = "I-V Curve Pumped Pol 1 SIS 2";
                break;
            case 6:
                $plot_name = "I-V Curve Pumped Pol 1 SIS 1";
                break;
            case 7:
                $plot_name = "I-V Curve Pumped Pol 0 SIS 2";
                break;
            case 8:
                $plot_name = "I-V Curve Pumped Pol 0 SIS 1";
                break;
        }
    } else {
        switch ($count_iv) {
            case 1:
                $plot_name = "I-V Curve Unpumped Pol 1 SIS 1";
                break;
            case 2:
                $plot_name = "I-V Curve Unpumped Pol 0 SIS 1";
                break;
            case 3:
                $plot_name = "I-V Curve Pumped Pol 1 SIS 1";
                break;
            case 4:
                $plot_name = "I-V Curve Pumped Pol 0 SIS 1";
                break;
        }
    }
    $html = table_header_html(800, $tdh, 1, $filterChecked, null,  $plot, $plot_name);

    if ($html) {
        $html .= "<tr><td colspan='1'><img width='100%' src='" . $tdh->GetValue('PlotURL') . "'/></td></tr>";
        $html .= "</table></div>";
    }
    return $html;
}

// Band 3 Noise Temperature Table
/**
 * echos a HTML table that contains Band 3 Noise Temperature monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function Band3_NT_results_html($td_keyID) {

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID, "40");

    //get specs
    $spec_names = array();
    for ($i = 1; $i < 6; $i++) {
        $spec_names[] = 'Bspec_bbTSSB' . $i . 'f';
        $spec_names[] = 'Bspec_bbTSSB' . $i . 's';
    }
    $new_spec = new Specifications();
    $spec = $new_spec->getSpecs('FEIC_NoiseTemperature', $tdh->GetValue('Band'), $spec_names);
    $specs = array();
    for ($i = 1; $i < 6; $i++) {
        $specs[$spec['Bspec_bbTSSB' . (string)$i . 'f']] = $spec['Bspec_bbTSSB' . (string)$i . 's'];
    }


    $col_name = array("FreqLO", "Pol0USB", "Pol0LSB", "Pol1USB", "Pol1LSB", "AvgNT");
    $col_strg = implode(",", $col_name);
    $q = "SELECT $col_strg
        FROM `Noise_Temp_Band3_Results`
        WHERE fkHeader= $td_keyID
        ORDER BY FreqLO;";
    $r = mysqli_query($tdh->dbconnection, $q) or die("QUERY FAILED: $q");

    $html = table_header_html(800, $tdh, 7);

    // display data column header row
    $html .= "<tr>";
    for ($i = 0; $i < 7; $i++) {
        switch ($i) {
            case 0;
                //diplay "Ghz" for Frequency
                $html .= "<th>$col_name[$i] (Ghz)</th>";
                break;
            case 6;
                //diplay "Ghz" for Frequency
                $html .= "<th>Spec (K)</th>";
                break;
            default;
                //Display "K" for noise temps
                $html .= "<th>$col_name[$i] (K)</th>";
                break;
        }
    }
    $html .= "</tr>";

    // display data rows
    $cnt = 0;
    $spec = $specs[92];

    while ($row = mysqli_fetch_array($r)) {
        $i = 0;
        $html .= "<tr>";
        for ($i = 0; $i < 7; $i++) {
            switch ($i) {
                case 0;
                    //Frequency column
                    $freq = ADAPT_mysqli_result($r, $cnt, $i);
                    $html .= "<td>$freq</td>";
                    break;
                case 5;
                    //average NT column
                    if (isset($specs[$freq]))
                        $spec = $specs[$freq];
                    $num = mon_data_html(ADAPT_mysqli_result($r, $cnt, $i));
                    $text = $new_spec->chkNumAgnstSpec($num, "<", $spec);
                    $html .= "<td>$text</td>";
                    break;
                case 6;
                    //spec column
                    $html .= "<td> less than $spec</td>";
                    break;
                default;
                    //only display 2 decimals on a float number
                    $html .= "<td>" . mon_data_html(ADAPT_mysqli_result($r, $cnt, $i)) . "</td>";
                    break;
            }
        }
        $html .= "</tr>";
        $cnt++;
    }
    $html .= "</table></div>";
    return $html;
}

// Band 3 CCA Noise Temperature Table
/**
 * echos a HTML table that contains Band 3 CCA Noise Temperature monitor data
 *
 * @param $td_keyID (float) - testdata header keyID
 *
 */
function Band3_CCA_NT_results_html($td_keyID) {

    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($td_keyID, "40");

    $html = "";

    //get specs
    $spec_names = array();
    for ($i = 1; $i < 6; $i++) {
        $spec_names[] = 'Bspec_bbTSSB' . (string)$i . 'f';
        $spec_names[] = 'Bspec_bbTSSB' . (string)$i . 's';
    }
    //$specs=get_specs_by_spec_type ( 10 , $tdh->GetValue('Band') );
    $new_spec = new Specifications();
    $spec = $new_spec->getSpecs('FEIC_NoiseTemperature', $tdh->GetValue('Band'), $spec_names);
    $specs = array();
    for ($i = 1; $i < 6; $i++) {
        $specs[$spec['Bspec_bbTSSB' . (string)$i . 'f']] = $spec['Bspec_bbTSSB' . (string)$i . 's'];
    }

    //Query to get CCA Serial Number
    $q = "SELECT MAX(FE_Components.SN) FROM FE_Components, FE_ConfigLink, FE_Config
         WHERE FE_ConfigLink.fkFE_Config = " . $tdh->GetValue('fkFE_Config') . "
         AND FE_Components.fkFE_ComponentType = 20
         AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
         AND FE_Components.Band = " . $tdh->GetValue('Band') . "
         AND FE_Components.keyFacility =" . $tdh->GetValue('keyFacility') . "
         AND FE_ConfigLink.fkFE_ConfigFacility = FE_Config.keyFacility
         ORDER BY Band ASC";

    //Get CCA FE_Component keyid
    $q = "SELECT keyId FROM FE_Components
         WHERE SN = ($q) AND fkFE_ComponentType = 20
         AND band = " . $tdh->GetValue('Band') . "
         AND keyFacility =" . $tdh->GetValue('keyFacility') . "
         GROUP BY keyId DESC";

    $r = mysqli_query($tdh->dbconnection, $q) or die("QUERY FAILED: $q");;
    while ($row = mysqli_fetch_array($r)) {
        $CCA_key[] = $row[0];
    }

    $cnt = 0;
    do {
        // check all CCA configurations for Noise Temperature data
        //get CCA Test Data key
        $q = "SELECT keyID FROM TestData_header WHERE fkTestData_Type = 42
            AND fkDataStatus = 7 AND fkFE_Components = $CCA_key[$cnt]
            AND keyFacility =" . $tdh->GetValue('keyFacility') . "";
        $r = mysqli_query($tdh->dbconnection, $q);

        $CCA_TD_key = ADAPT_mysqli_result($r, 0, 0);
        $cnt++;
    } while ($CCA_TD_key === FALSE && $cnt < count($CCA_key));

    $cca_tdh = new TestData_header();
    $cca_tdh->Initialize_TestData_header($CCA_TD_key, "40");

    if ($CCA_TD_key) {
        // get and display table
        $col_name = array("Pol", "SB", "FreqLO", "CenterIF", "Treceiver");
        $col_strg = implode(",", $col_name);
        $q = "SELECT $col_strg
            FROM `CCA_TEST_NoiseTemperature`
            WHERE fkHeader= $CCA_TD_key AND `CenterIF` != 0
            ORDER BY `Pol` ASC, `SB` ASC, `FreqLO` ASC, `CenterIF` ASC";

        $r = mysqli_query($tdh->dbconnection, $q) or die("QUERY FAILED: $q");

        // read sort and average Noise Temperature Data
        $last_FREQ_LO = 0;

        $AVG_NT_FREQ_LO = array();

        while ($row = mysqli_fetch_array($r)) {
            if ($last_FREQ_LO != $row[2] && $last_FREQ_LO != 0) {
                $index = array_search($last_FREQ_LO, $AVG_NT_FREQ_LO);

                if ($index === FALSE  || $index === NULL) {
                    $AVG_NT_FREQ_LO[] = $last_FREQ_LO;
                }
                // calculate NT averages for a polarizaion and SB per a given Freq_LO
                // pol 0 SB1
                if ($last_pol == 0 && $last_sb == 1) {
                    $AVG_NT_Pol0_Sb1[] = array_sum($NT_Pol0_Sb1) / count($NT_Pol0_Sb1);
                    unset($NT_Pol0_Sb1);

                    // pol 0 SB2
                } else if ($last_pol == 0 && $last_sb == 2) {
                    $AVG_NT_Pol0_Sb2[] = array_sum($NT_Pol0_Sb2) / count($NT_Pol0_Sb2);
                    unset($NT_Pol0_Sb2);

                    // pol 1 SB1
                } else if ($last_pol == 1 && $last_sb == 1) {
                    $AVG_NT_Pol1_Sb1[] = array_sum($NT_Pol1_Sb1) / count($NT_Pol1_Sb1);
                    unset($NT_Pol1_Sb1);

                    // pol 1 SB2
                } else if ($last_pol == 1 && $last_sb == 2) {
                    $AVG_NT_Pol1_Sb2[] = array_sum($NT_Pol1_Sb2) / count($NT_Pol1_Sb2);
                    unset($NT_Pol1_Sb2);
                }
            }

            //save polarization and sidebands NT into an array
            // pol 0 SB1
            if ($row[0] == 0 && $row[1] == 1) {
                $NT_Pol0_Sb1[] = $row[4];

                // pol 0 SB2
            } else if ($row[0] == 0 && $row[1] == 2) {
                $NT_Pol0_Sb2[] = $row[4];

                // pol 1 SB1
            } else if ($row[0] == 1 && $row[1] == 1) {
                $NT_Pol1_Sb1[] = $row[4];

                // pol 1 SB2
            } else if ($row[0] == 1 && $row[1] == 2) {
                $NT_Pol1_Sb2[] = $row[4];
            }

            $last_FREQ_LO = $row[2];
            $last_pol = $row[0];
            $last_sb = $row[1];
            $last_NT = $row[4];
        }

        // calculate last average point
        $AVG_NT_Pol1_Sb2[] = array_sum($NT_Pol1_Sb2) / count($NT_Pol1_Sb2);

        // get TFETMS Average Data
        $q = "SELECT `AvgNT`, `FreqLO`
            FROM `Noise_Temp_Band3_Results`
            WHERE fkHeader= $td_keyID";
        $r = mysqli_query($tdh->dbconnection, $q);
        $TFETMS = array();
        while ($row = mysqli_fetch_array($r)) {
            $TFETMS[$row[1]] = $row[0];
        }

        $html = table_header_html(800, $cca_tdh, 8, false, false);
        $col_name = array("FreqLO (Ghz)", "Pol0USB (K)", "Pol0LSB (K)", "Pol1USB (K)", "Pol1LSB (K)", "AvgNT (K)", "Spec (K)", "T(FETMS)-\nT(HIA)-\n3Kmirrors (K)");
        // display data column header row
        foreach ($col_name  as $Col) {
            $html .= "<th'>$Col</th>";
        }

        // display data rows
        $cnt = 0;
        $i = 0;

        foreach ($AVG_NT_FREQ_LO as $FREQ_LO) {
            $html .= "<tr>";
            //don't format frequency
            $html .= "<td>$FREQ_LO</td>";

            //only display 2 decimals on a float number
            $html .= "<td>" . mon_data_html($AVG_NT_Pol0_Sb1[$cnt]) . "</td>";
            $html .= "<td>" . mon_data_html($AVG_NT_Pol0_Sb2[$cnt]) . "</td>";
            $html .= "<td>" . mon_data_html($AVG_NT_Pol1_Sb1[$cnt]) . "</td>";
            $html .= "<td>" . mon_data_html($AVG_NT_Pol1_Sb2[$cnt]) . "</td>";
            $AVG =     mon_data_html(($AVG_NT_Pol0_Sb1[$cnt] + $AVG_NT_Pol0_Sb2[$cnt] + $AVG_NT_Pol1_Sb1[$cnt] + $AVG_NT_Pol1_Sb2[$cnt]) / 4);
            $temp_spec = $specs[$FREQ_LO] - 3;
            $text = $new_spec->chkNumAgnstSpec($AVG, "<", $temp_spec);
            $html .= "<td>$text</td>";
            $html .= "<td>less than $temp_spec</td>";
            if (isset($TFETMS[$FREQ_LO]))
                $result = mon_data_html($TFETMS[$FREQ_LO] - $AVG - 3);
            else
                $result = "";
            $html .= "<td>$result</td>";
            $cnt++;
            $html .= "</tr>";
        }
        $html .= "</table></div>";
    }
    return $html;
}
