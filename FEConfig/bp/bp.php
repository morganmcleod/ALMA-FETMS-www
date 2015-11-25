<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html401/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link href="../images/favicon.ico" rel="shortcut icon" type="image/x-icon" />

<link rel="stylesheet" type="text/css" href="../Cartstyle.css">
<link rel="stylesheet" type="text/css" href="../tables.css">
<link rel="stylesheet" type="text/css" href="../buttons.css">
<link rel="Stylesheet" type="text/css" href="../headerbuttons.css">
<link rel="Stylesheet" type="text/css" href="../../ext/resources/css/ext-all.css" media="screen"/>

<script type="text/javascript" src="../../ext/adapter/ext/ext-base.js"></script>
<script type="text/javascript" src="../../ext/ext-all.js"></script>
<script type="text/javascript" src="../spin.js"></script>
<script type="text/javascript" src="loadBP.js"></script>

<title>Beam Patterns</title>
</head>

<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.eff.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_classes . '/class.scansetdetails.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_dbConnect);

// get the Facility Code and TestDataHeader id from the request:
$fc = $_REQUEST['fc'];
$tdh_key=$_REQUEST['keyheader'];

// Make a TestData_header record object for the FC and header id:
$tdh = new TestData_header();
$tdh->Initialize_TestData_header($tdh_key,$fc);

// if the HTML request includes a Notes parameter call the TestDataHeader method to write those notes back to the database:
// The 'SAVE' button under the notes window reloads this page with the notes text included in the URL.
if (isset($_REQUEST['Notes'])) {
    $tdh->RequestValues_TDH();
}

// Find the ScanSetDetails record id corresponding to the TestDataHeader id:
$q = "SELECT keyId FROM ScanSetDetails WHERE fkHeader = " . $tdh->keyId . ";";
$r = @mysql_query($q,$db);
$ssid = @mysql_result($r,0,0);

// Make a ScanSetDetails record object;
$ssd = new ScanSetDetails();
$ssd->Initialize_ScanSetDetails($ssid,$fc);

// Create the main beam efficiency analysis class and set it up to work with the current scan set:
$eff = new eff();
$eff->Initialize_eff_SingleScanSet($ssid,$fc);

// Create the FrontEnd recorod object corresponding to the TestDataHeader configuration:
$fe = new FrontEnd();
$fe->Initialize_FrontEnd_FromConfig($tdh->GetValue('fkFE_Config'),$fc);

// feconfig is the configuration number for the front end:
$feconfig = $fe->feconfig->keyId;

// get the front end serial number and the current measurement cartridge band:
$fesn = $fe->GetValue('SN');
$band = $tdh->GetValue('Band');

// Display the 'Get Efficiencies' button variously based on the ReadyToProcess attribute of the beam efficiency analysis class:
$geteffurl = "bp.php?drawplot=1&keyheader=".$tdh->keyId."&keyconfig=" . $tdh->GetValue('fkFE_Config') . "&band=$band&fc=$fc";

$buttonclass = 'button blue2 biground';

if ($eff->scansets[0]->Scan_copol_pol0->BeamEfficencies->GetValue('plot_copol_nfamp') != '') {
    //All three scans are complete, and have already been processed.
    echo '<link rel="stylesheet" type="text/css" href="buttonblue.css">';
    $bpstatus = 2;

} elseif ($eff->ReadyToProcess == 1) {
    //All three scans are complete, ready to process.
    echo '<link rel="stylesheet" type="text/css" href="buttongreen.css">';
    $bpstatus = 1;

} elseif ($eff->ReadyToProcess != 1) {
    //One or more scans still need to finish.
    echo '<link rel="stylesheet" type="text/css" href="buttonred.css">';
    $bpstatus = 3;
}

// start the HTML body.  Calls createBPTabs from loadBP.js to customize the button and create the data tabs:
echo "<body id = 'body3' onload='createBPTabs($fc,$tdh_key,$band,$bpstatus)' BGCOLOR='#19475E'>";
echo "<form action='".$_SERVER["PHP_SELF"]."' method='post' name='Submit' id='Submit'>";

// show the standard header with the folllowing text plus Home, Front End NN, Bugs buttons:
$title = "Band $band Beam Patterns";
include "header_bp.php";

// this section performs the analysis and draws the plots
if (isset($_REQUEST['drawplot'])) {
    if ($_REQUEST['drawplot'] == 1) {

        //Show a spinner while plots are being drawn.
        include($site_FEConfig . '/spin.php');

        $pointingOption = 'nominal';
        if (isset($_REQUEST['pointing'])) {
            $pointingOption = $_REQUEST['pointing'];
        }

        // GetEfficiencies calls out to the beameff_64 application to compute efficiencies and plots:
        $eff->GetEfficiencies($pointingOption);
        echo "done getting effs. <br>";

        // ReplacePlotURLs fixes URLs in the database so that they can be loaded from the Plots tabs:
        $eff->ReplacePlotURLs();
        echo '<meta http-equiv="Refresh" content="1;url=bp.php?keyheader='.$tdh->keyId.'&fc='.$fc.'">';
    }
}

// HTML code below are the targets for javaScript loaded from onload=createBPTabs() above.
?>
<div style="padding-left: 2em; padding-top: 1em; width: 1100px; background-color: #19475E;">
    <div id="toolbar" style="margin-top:10px;"></div>
    <div id="tabs1"  ></div>
</div>
</form>

<?php include "../footer.php"; ?>

</body>
</html>



