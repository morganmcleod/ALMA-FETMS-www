<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link rel="stylesheet" type="text/css" href="../Cartstyle.css">
<link rel="stylesheet" type="text/css" href="../tables.css">
<link rel="stylesheet" type="text/css" href="../buttons.css">
<link type="text/css" href="../../ext/resources/css/ext-all.css" media="screen" rel="Stylesheet" />
<script src="../../ext/adapter/ext/ext-base.js" type="text/javascript"></script>
<script src="../../ext/ext-all.js" type="text/javascript"></script>
<!-- <script src="../dbGrid.js" type="text/javascript"></script> -->

<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_dbConnect);
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.cca_image_rejection.php');
require_once($site_classes . '/class.finelosweep.php');
require_once($site_classes . '/class.noisetemp.php');
require_once($site_classes . '/class.wca.php');

$fc = isset($_REQUEST['fc']) ? $_REQUEST['fc'] : '40';
$drawplot = isset($_REQUEST['drawplot']) ? $_REQUEST['drawplot'] : false;
$keyHeader = isset($_REQUEST['keyheader']) ? $_REQUEST['keyheader'] : false;
$showrawdata = isset($_REQUEST['showrawdata']) ? $_REQUEST['showrawdata'] : false;

if (!$keyHeader)
	exit();		// nothing to do.
	
$td = new TestData_header();
$td->Initialize_TestData_header($keyHeader, $fc);

if (!$td->GetValue('PlotURL') && $td->AutoDrawThis())
	$drawplot = true;

echo "<title>" . $td->TestDataType . "</title></head>";
echo "<body style='background-color: #19475E'>";

if ($td->GetValue('fkTestData_Type') == 55) {
    $url = $rootdir_url . "FEConfig/bp/bp.php";
    echo '<meta http-equiv="Refresh" content="0.1;' . $url .
         '?fc=' . $td->GetValue('keyFacility') .
         '&id=' . $td->keyId .
         '&band=' . $td->GetValue('Band') .
         '&keyconfig=' . $td->GetValue('fkFE_Config') .
         '&keyheader=' . $keyHeader . '">';
    exit();  // don't load the rest of this page.
}

if ($td->Component->ComponentType != "Front End") {
    $compdisplay = $td->Component->ComponentType;
    $band=0;

    if ($td->Component->GetValue('Band') != ''){
        $compdisplay .= " Band " . $td->Component->GetValue('Band');
        $band=$td->Component->GetValue('Band');
    }

    $compdisplay .= " SN " . $td->Component->GetValue('SN');

    $refurl = "../ShowComponents.php?";
    $refurl .= "conf=" .$td->Component->keyId . "&fc=". $td->GetValue('keyFacility');
    $header_main  = '<a href="'. $refurl . '"><font color="#ffffff">' . $compdisplay . '</font></a>';
}

$feconfig = $td->Component->FEConfig;
$fesn = $td->Component->FESN;

if ($td->Component->ComponentType == "Front End"){
    $feconfig = $td->FrontEnd->feconfig->keyId;
    $fesn = $td->FrontEnd->GetValue('SN');
    $header_main  = '<a href="../ShowFEConfig.php?key='
                 . $td->FrontEnd->feconfig->keyId . '&fc=' . $fc
                 . '"><font color="#ffffff">'
                 . $td->Component->ComponentType
                 . ' SN ' . $td->Component->GetValue('SN')
                 . '</font></a>';
}

$title = $td->TestDataType;
$showrawurl = "testdata.php?showrawdata=1&keyheader=$td->keyId&fc=".$td->GetValue('keyFacility');
$drawurl = "testdata.php?drawplot=1&keyheader=$td->keyId&fc=".$td->GetValue('keyFacility');
$exportcsvurl = "export_to_csv.php?keyheader=$td->keyId&fc=".$td->GetValue('keyFacility');

include('header_with_fe.php');

?>

<form enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
<div id="wrap" style="height:6000px">

<div id="sidebar2" style="height:6000px">
<table>

<?php

switch ($td->GetValue('fkTestData_Type')) {
    case '7':
        //if spectrum
        $drawurl = "testdata.php?keyheader=$td->keyId&drawplot=1&fc=". $td->GetValue('keyFacility');
        $datasetsurl = "testdata.php?keyheader=$td->keyId&sd=1&fc=". $td->GetValue('keyFacility');
        echo "
            <tr><td>
                <a style='width:90px' href='$showrawurl' class='button blue2 biground'>
                <span style='width:130px'>Show Links To Raw Data</span></a>
            </tr></td>
            <tr><td>
                <a style='width:90px' href='$datasetsurl' class='button blue2 biground'>
                <span style='width:130px'>Show Data Sets</span></a>
            </tr></td>
            <tr><td>
                <a style='width:90px' href='$drawurl' class='button blue2 biground'>
                <span style='width:130px'>Generate Plots And Data</span></a>
            </tr></td>";
        break;

    case '57':
    case '58':
        //LO Lock test or noise temperature
        $drawurl = "testdata.php?keyheader=$td->keyId&drawplot=1&fc=". $td->GetValue('keyFacility');
        $datasetsurl = "testdata.php?keyheader=$td->keyId&sd=1&fc=". $td->GetValue('keyFacility');
        $gridurl = "../datasets/datasets.php?fc=". $td->GetValue('keyFacility') . "&id=" . $td->keyId;
        $gridurl .= "&fe=". $td->FrontEnd->keyId . "&b=". $td->GetValue('Band') . "&d=".$td->GetValue('fkTestData_Type');
        echo "
            <tr><td>
                <a style='width:90px' href='$showrawurl' class='button blue2 biground'>
                <span style='width:130px'>Show Raw Data</span></a>
            </tr></td>

            <tr><td>
                <a style='width:90px' href='$drawurl' class='button blue2 biground'>
                <span style='width:130px'>Generate Plots And Data</span></a>
            </tr></td>
            <tr><td>
                <a style='width:90px' href='$exportcsvurl' class='button blue2 biground'>
                <span style='width:130px'>Export CSV</span></a>
            </tr></td>
            <tr><td>
                <a style='width:90px' href='$gridurl' class='button blue2 biground'>
                <span style='width:130px'>Edit Data Sets</span></a>
            </tr></td>";
        break;

    case '28':
         //cryo pas
         echo "
            <tr><td>
                <a style='width:90px' href='$showrawurl' class='button blue2 biground'>
                <span style='width:130px'>Show Raw Data</span></a>
            </tr></td>
            <tr><td>
                <a style='width:90px' href='$exportcsvurl' class='button blue2 biground'>
                <span style='width:130px'>Export CSV</span></a>
            </tr></td>";
         break;

     case '52':
         //cryo first cooldown
         echo "
            <tr><td>
                <a style='width:90px' href='$showrawurl' class='button blue2 biground'>
                <span style='width:130px'>Show Raw Data</span></a>
            </tr></td>
            <tr><td>
                <a style='width:90px' href='$exportcsvurl' class='button blue2 biground'>
                <span style='width:130px'>Export CSV</span></a>
            </tr></td>";
         break;

      case '53':
         //cryo first warmup
         echo "
            <tr><td>
                <a style='width:90px' href='$showrawurl' class='button blue2 biground'>
                <span style='width:130px'>Show Raw Data</span></a>
            </tr></td>
            <tr><td>
                <a style='width:90px' href='$exportcsvurl' class='button blue2 biground'>
                <span style='width:130px'>Export CSV</span></a>
            </tr></td>";
         break;

    default:
        echo "
            <tr><td>
                <a style='width:90px' href='$showrawurl' class='button blue2 biground'>
                <span style='width:130px'>Show Raw Data</span></a>
            </tr></td>
            <tr><td>
                <a style='width:90px' href='$exportcsvurl' class='button blue2 biground'>
                <span style='width:130px'>Export CSV</span></a>
            </tr></td>
            <tr><td>
                <a style='width:90px' href='$drawurl' class='button blue2 biground'>
                <span style='width:130px'>Generate Plot</span></a>
            </tr></td>";
         break;
}

?>

</table>
</div>
</div>
</form>

<div id="maincontent" style="height:6000px">
<div id = "wrap">

<?php

$td->RequestValues_TDH();

if ($drawplot){
	//Show a spinner while plots are being drawn.
	include($site_FEConfig . '/spin.php');
	$td->DrawPlot();
    $refurl = "testdata.php?keyheader=$keyHeader";
    $refurl .= "&fc=$fc";
    echo '<meta http-equiv="Refresh" content="1;url='.$refurl.'">';
}

$td->Display_TestDataMain();

switch($td->GetValue('fkTestData_Type')){
	case 59:
		//Fine LO Sweep
		$finelosweep = new FineLOSweep();
		$finelosweep->Initialize_FineLOSweep($td->keyId,$td->GetValue('keyFacility'));
		$finelosweep->DisplayPlots();
		unset($finelosweep);
		break;
	case 58:
		//Noise Temperature
		$nztemp = new NoiseTemperature();
		$nztemp->Initialize_NoiseTemperature($td->keyId,$td->GetValue('keyFacility'));
		$nztemp->DisplayPlots();
		unset($nztemp);
		break;
	case 38:
		//CCA Image Rejection
		$ccair = new cca_image_rejection();
		$ccair->Initialize_cca_image_rejection($td->keyId,$td->GetValue('keyFacility'));
		$ccair->DisplayPlots();
		unset($ccair);
		break;

	default:
		$urlarray = explode(",",$td->GetValue('PlotURL'));
		for ($i=0;$i<count($urlarray);$i++){
			echo "<img src='" . $urlarray[$i] . "'><br>";
		}
		break;
}

switch($td->GetValue('fkTestData_Type')) {
    case 1:
        $showrawdata = true;
        break;
    case 2:
        $showrawdata = true;
        break;
    case 3:
        $showrawdata = true;
        break;
    case 24:
        $showrawdata = true;
        break;
    case 49:
        $showrawdata = true;
        break;
}

if ($showrawdata)
    $td->Display_RawTestData();

unset($td);

?>

</div>
</div>
</body>
</html>
