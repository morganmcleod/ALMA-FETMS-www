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
<script src="../dbGrid.js" type="text/javascript"></script>
<script type="text/javascript" src="../spin.js"></script>

<body style="background-color: #19475E">

<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_dbConnect);
require_once($site_classes . '/class.testdata_header.php');
require_once($site_classes . '/class.cca_image_rejection.php');
require_once($site_classes . '/class.finelosweep.php');
require_once($site_classes . '/class.noisetemp.php');
require_once($site_NT . '/noisetempcalc.php');
require_once($site_root . '/test/Library/plotter.php');
require_once($site_NT . '/noisetempcalc.php');
require_once($site_IF . '/IFCalc.php');
require_once($site_root . '/test/Library/plotter.php');


$fc = $_REQUEST['fc'];

if (isset($_REQUEST['drawplot']) && $_REQUEST['drawplot'] == 1 ) {
    //Show a spinner while plots are being drawn.
    include($site_FEConfig . '/spin.php');
}

$TestData_header_keyId = $_REQUEST['keyheader'];
$td = new TestData_header();
$td->Initialize_TestData_header($TestData_header_keyId, $fc);
$td->TestDataHeader = $TestData_header_keyId;

echo "<title>" . $td->TestDataType . "</title></head>";

if ($td->GetValue('fkTestData_Type') == 55){
    echo '<meta http-equiv="Refresh" content="0.1;url=bp.php?fc='.$td->GetValue('keyFacility').'&id='.$td->keyId.'&band='.$td->GetValue('Band') . '&keyconfig=' .$td->GetValue('fkFE_Config') . '">';
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

if (isset($_REQUEST['drawplot']) && ($_REQUEST['drawplot'] == 1 )){
    $td->DrawPlot();
    $refurl = "testdata.php?keyheader=$TestData_header_keyId";
    $refurl .= "&fc=$fc";
    echo '<meta http-equiv="Refresh" content="1;url='.$refurl.'">';
}

if (($td->GetValue('PlotURL') == '')) {
    switch($td->GetValue('fkTestData_Type')) {
        case 1:
        case 2:
        case 3:
        case 4:
        case 5:
        case 8:
        case 9:
        case 10:
        case 12:
        case 13:
        case 14:
        case 15:
            // don't draw plots for health check tabular data
            break;

        case 57:
            //Don't automatically draw LO Lock test
            break;
        case 58:
            //Don't automatically draw noise temperature
            break;
        case 59:
            //Don't automatically draw fine LO sweep
            break;
        case 44:
        case 45:
        case 46:
        case 47:
        case 48:
            //Don't automatically draw WCA cartridge PAI plots
            break;

        case 42:
            //Don't automatically draw CCA cartridge PAI plots
            break;

        default:
            //Show a spinner while plots are being drawn.
            include($site_FEConfig . '/spin.php');
            $td->DrawPlot();
            $refurl = "testdata.php?keyheader=$TestData_header_keyId";
            $refurl .= "&fc=$fc";
            echo '<meta http-equiv="Refresh" content="1;url='. $refurl .'">';
            break;
    }
}

require_once(site_get_classes() . '/class.wca.php');

function Display_TestDataMain($td) {

    switch ($td->GetValue('fkTestData_Type')) {
        case 27:
            $td->Display_DataForm();
            echo "<br>";
            $td->Display_PhaseStabilitySubHeader();
            break;

        case 7:
            //IF Spectrum
            break;

        case 56:
            //Pol Angles
            $td->Display_DataForm();
            echo "<br>";
            $td->Display_Data_PolAngles();
            break;

        case 57:
            //LO Lock Test
            $td->Display_DataSetNotes();
            echo "<br>";
            break;

        case 58:
            //Noise Temperature
            $td->Display_DataSetNotes();
            echo "<br>";
            break;

        case 50:
            $td->Display_DataForm();
            echo "<br>";
            $td->Display_Data_Cryostat(1);
            break;
        case 52:
            $td->Display_DataForm();
            echo "<br>";
            $td->Display_Data_Cryostat(3);
            break;
        case 53:
            $td->Display_DataForm();
            echo "<br>";
            $td->Display_Data_Cryostat(2);
            break;
        case 54:
            $td->Display_DataForm();
            echo "<br>";
            $td->Display_Data_Cryostat(4);
            break;
        case 25:
            $td->Display_DataForm();
            echo "<br>";
            $td->Display_Data_Cryostat(5);
            break;
        case 45:
            $td->Display_DataForm();
            echo "<br>";
            $wca = new WCA();
            $wca->Initialize_WCA($td->GetValue('fkFE_Components'),$td->GetValue('keyFacility'));
            $wca->Display_AmplitudeStability();
            break;
        case 44:
            $td->Display_DataForm();
            echo "<br>";
            $wca = new WCA();
            $wca->Initialize_WCA($td->GetValue('fkFE_Components'),$td->GetValue('keyFacility'));
            $wca->Display_AMNoise();
            break;
        case 46:
            $td->Display_DataForm();
            echo "<br>";
            $wca = new WCA();
            $wca->Initialize_WCA($td->GetValue('fkFE_Components'),$td->GetValue('keyFacility'));
            $wca->Display_OutputPower();
            break;
        case 47:
            $td->Display_DataForm();
            echo "<br>";
            $wca = new WCA();
            $wca->Initialize_WCA($td->GetValue('fkFE_Components'),$td->GetValue('keyFacility'));
            $wca->Display_PhaseNoise();
            break;
        case 48:
            $td->Display_DataForm();
            echo "<br>";
            $wca = new WCA();
            $wca->Initialize_WCA($td->GetValue('fkFE_Components'),$td->GetValue('keyFacility'));
            $wca->Display_PhaseNoise();
            break;
        default:
            $td->Display_DataForm();
            break;
    }
}


require_once(site_get_classes() . '/class.finelosweep.php');
require_once(site_get_classes() . '/class.noisetemp.php');
require_once(site_get_classes() . '/class.cca_image_rejection.php');

function Display_Plot($td){
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
            require(site_get_config_main());
            $band = $td->GetValue('Band');
            for ($sb=1; $sb <= 2; $sb++) {
            	for ($pol=0; $pol <= 1; $pol++) {
            		echo "<img src= 'http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/NoiseTempLibrary/Band$band Pol$pol Sb$sb RF.png'><br><br>";
            	}
            }
            echo "<img src = 'http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/NoiseTempLibrary/Band$band IF.png'><br><br>";
            if ($band != 3) {
				echo "<img src = 'http://webtest.cv.nrao.edu/php/ntc/ws-atb/test_datafiles/NoiseTempLibrary/Band$band Avg RF.png'><br><br>";
			}
            /*$nztemp = new NoiseTemperature();
            $nztemp->Initialize_NoiseTemperature($td->keyId,$td->GetValue('keyFacility'));
            $nztemp->DisplayPlots();
            unset($nztemp);//*/
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
}

Display_TestDataMain($td);
Display_Plot($td);

$showrawdata = isset($_REQUEST['showrawdata']) ? $_REQUEST['showrawdata'] : 0;

switch($td->GetValue('fkTestData_Type')){
    case 1:
        $showrawdata = 1;
        break;
    case 2:
        $showrawdata = 1;
        break;
    case 3:
        $showrawdata = 1;
        break;
    case 24:
        $showrawdata = 1;
        break;
    case 49:
        $showrawdata = 1;
        break;
}

if ($showrawdata == 1){
    $td->Display_RawTestData();
}
unset($td);

?>

</div>
</div>
</body>
</html>

