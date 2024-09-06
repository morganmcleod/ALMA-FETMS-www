<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <link rel="stylesheet" type="text/css" href="../Cartstyle.css">
    <link rel="stylesheet" type="text/css" href="../tables.css">
    <link rel="stylesheet" type="text/css" href="../buttons.css">
    <link rel="stylesheet" type="text/css" href="../headerbuttons.css">
    <link rel="stylesheet" type="text/css" href="../../ext4/resources/css/ext-all.css" />
    <script type="text/javascript" src="../../ext4/ext-all.js"></script>
    <script type='text/javascript' src='../../classes/pickComponent/popupMoveToOtherFE.js'></script>
    <script type="text/javascript" src="loadBP.js"></script>

    <?php
    require_once(dirname(__FILE__) . '/../../SiteConfig.php');
    require_once($site_classes . '/class.eff.php');
    require_once($site_classes . '/class.frontend.php');
    require_once($site_classes . '/class.scansetdetails.php');
    require_once($site_classes . '/class.testdata_header.php');
    require_once($site_dbConnect);
    $dbConnection = site_getDbConnection();

    // get the Facility Code and TestDataHeader id from the request:
    $fc = $_REQUEST['fc'];
    $headerKeyId = $_REQUEST['keyheader'];
    $drawplot = $_REQUEST['drawplot'] ?? 0;
    $fkDataStatus = $_REQUEST['fkDataStatus'] ?? NULL;
    $Notes = $_REQUEST['Notes'] ?? NULL;


    // Make a TestData_header record object for the FC and header id:
    $tdh = new TestData_header($headerKeyId, $fc);
    $datastatus = (int)$tdh->fkDataStatus;

    // if the HTML request includes a Notes parameter call the TestDataHeader method to write those notes back to the database:
    // The 'SAVE' button under the notes window reloads this page with the notes text included in the URL.
    $tdh->requestValuesHeader($fkDataStatus, $Notes);

    // Find the ScanSetDetails record id corresponding to the TestDataHeader id:
    $ssid = ScanSetDetails::getIdFromHeader($headerKeyId);

    // Create the main beam efficiency analysis class and set it up to work with the current scan set:
    $eff = new eff($ssid, $fc);

    // feconfig is the configuration number for the front end:
    $feconfig = $tdh->frontEnd->feconfig->keyId;

    // get the front end serial number and the current measurement cartridge band:
    $fesn = $tdh->frontEnd->SN;
    $band = $tdh->Band;

    if ($eff->scansets[0]->Scan_copol_pol0->BeamEfficencies->plot_copol_nfamp != '') {
        //All three scans are complete, and have already been processed.
        echo '<link rel="stylesheet" type="text/css" href="buttonblue.css">';
        $bpstatus = 2;
    } elseif ($eff->ReadyToProcess == 3) {
        //All three scans are complete, ready to process.
        echo '<link rel="stylesheet" type="text/css" href="buttongreen.css">';
        $bpstatus = 1;
    } elseif ($eff->ReadyToProcess == 2) {
        //Two scans are complete, can process without 180 scan.
        echo '<link rel="stylesheet" type="text/css" href="buttonyellow.css">';
        $bpstatus = 4;
    } else {
        //One or more scans still need to finish.
        echo '<link rel="stylesheet" type="text/css" href="buttonred.css">';
        $bpstatus = 3;
    }

    $title = "";
    if ($fesn && !$FETMS_CCA_MODE)
        $title = "FE-$fesn - ";

    $title .= "Band $band - Beam Patterns";

    echo "<title>" . $title . "</title></head>";
    echo "</head>";

    // start the HTML body.  Calls createBPTabs from loadBP.js to customize the button and create the data tabs:
    echo "<body BGCOLOR='#19475E'>";
    echo "<form action='" . $_SERVER["PHP_SELF"] . "' method='post' name='Submit' id='Submit'>";

    // show the standard header with the folllowing text plus Home, Front End NN, Bugs buttons:
    include "header_bp.php";

    // this section performs the analysis and draws the plots
    if ($drawplot == 1) {

        //Show a spinner while plots are being drawn.
        include($site_FEConfig . '/spin.php');

        $pointingOption = 'nominal';
        if (isset($_REQUEST['pointing'])) {
            $pointingOption = $_REQUEST['pointing'];
        }

        // GetEfficiencies calls out to the beameff_64 application to compute efficiencies and plots:
        $eff->GetEfficiencies($pointingOption);
        echo "done getting effs. <br>";

        echo "<meta http-equiv='Refresh' content='1;url=bp.php?keyheader={$tdh->keyId}&fc={$fc}'>";
    }

    echo "<script type='text/javascript'>
		Ext.onReady(function() {
		    function popupCallback() {
		        popupMoveToOtherFE('FE-$fesn', \"$url_root\", [$tdh->keyId]);
		    }
		    createBPTabs($fc, $headerKeyId, $band, $bpstatus, $datastatus, popupCallback)
        });</script>";

    // HTML code below are the targets for javaScript loaded from onload=createBPTabs() above.
    ?>
    <div style="padding-left: 2em; padding-top: 1em; width: 1100px; background-color: #19475E;">
        <div id="toolbar" style="margin-top:10px;"></div>
        <div id="tabs1"></div>
    </div>
    </form>

    <?php include "../footer.php"; ?>

    </body>

</html>