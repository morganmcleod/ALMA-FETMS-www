<?php
/*
 * This script is called from loadBP.js.
 * It populates the tabbed enclosure with plots or data tables,
 * depending on which tab was selected.
 *
 * 2015-02-22 jee added Display_PhaseEff();
 *
 */

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.eff.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_dbConnect);
$dbConnection = site_getDbConnection();

//Facility code
$fc = $_REQUEST['fc'];
//TestData_header.keyId value
$keyId = $_REQUEST['id'];
//Which tab was selected
$tabtype = $_REQUEST['tabtype'];

//Instantiate a new TestData_header object
$tdh = new TestData_header($keyId, $fc);

//Instantiate a new eff object
$q = "SELECT keyId FROM ScanSetDetails WHERE fkHeader = " . $keyId . ";";
$r = mysqli_query($dbConnection, $q);
$ssid = ADAPT_mysqli_result($r, 0, 0);
$eff = new eff($ssid, $fc);

echo "<div style='background-color:#6C7070;width:1000px;'>";

//Get FETMS description:
$fetms = $tdh->GetFetmsDescription("Measured at: ");

//Take an action based on which tab was selected.
switch ($tabtype) {
    case 1:
        //Scan Info tab
        $posturl = "bp.php?keyheader=$tdh->keyId&fc=" . $tdh->keyFacility;

        //If not MISE, we will wrap the test data notes in a <form>.
        //TODO:  apparently this means you can't save notes in MSIE!
        $browserNotMSIE = (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'msie') === FALSE);

        if ($browserNotMSIE)
            echo "<form action='$posturl' method='post'>";

        echo "<div style='width:100px'>";
        echo "<table id = 'table1'>";
        if ($fetms)
            echo "<tr><th>$fetms</th></tr>";
        echo "<tr><th>Notes</th></tr>";
        echo "<tr><td>";
        echo "<textarea rows='20' cols='85' name = 'Notes'>" . stripslashes($tdh->Notes) . "</textarea>";
        echo "<input type='hidden' name='fc' value='" . $tdh->keyFacility . "'>";
        echo "<input type='hidden' name='keyheader' value='$tdh->keyId'>";
        echo "<br><input type='submit' name='submitted' value='SAVE'>";
        echo "</td></tr>";
        echo "</table>";
        echo "</div>";

        if ($browserNotMSIE)
            echo "</form>";

        $eff->Display_ScanInformation();
        $eff->Display_SetupParameters();
        break;

    case 2:
        //Data Tables tab
        if ($fetms) {
            echo "<div style='width:300px'>";
            echo "<table id = 'table1'>";
            echo "<tr><th>$fetms</th></tr>";
            echo "</table></div>";
        }
        $eff->Display_ApertureEff();
        $eff->Display_TaperEff();
        $eff->Display_PhaseEff();
        $eff->Display_SpilloverEff();
        $eff->Display_PolEff();
        $eff->Display_DefocusEff();
        $eff->Display_PhaseCenterOffset();
        $eff->Display_Squint();
        $eff->Display_Equations();
        break;

    case 3:
        //Pointing Angles tab
        if ($fetms) {
            echo "<div style='width:300px'>";
            echo "<table id = 'table1'>";
            echo "<tr><th>$fetms</th></tr>";
            echo "</table></div>";
        }
        echo $eff->Display_PointingAngles();
        echo $eff->Display_PointingAngleDiff();
        echo "<br><br>";
        $eff->Display_PointingAnglesPlot();
        break;

    case 4:
        //Pol 0 Nearfield Plots tab
        echo "<div style='background-color:#6C7070;width:900px;' id = 'maincontent6'>";
        $eff->Display_AllAmpPhasePlots(0, 'nf');
        echo "</div>";
        break;

    case 5:
        //Pol 0 Farfield Plots tab
        echo "<div style='background-color:#6C7070;width:900px;' id = 'maincontent6'>";
        $eff->Display_AllAmpPhasePlots(0, 'ff');
        echo "</div>";
        break;

    case 6:
        //Pol 1 Nearfield Plots tab
        echo "<div style='background-color:#6C7070;width:900px;' id = 'maincontent6'>";
        $eff->Display_AllAmpPhasePlots(1, 'nf');
        echo "</div>";
        break;

    case 7:
        //Pol 1 Farfield Plots tab
        echo "<div style='background-color:#6C7070;width:900px;' id = 'maincontent6'>";
        $eff->Display_AllAmpPhasePlots(1, 'ff');
        echo "</div>";
        break;

    default:
        //Undefined tab type
        break;
}

unset($eff);
echo "</div>";
