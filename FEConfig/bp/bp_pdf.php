<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($files_root . '/vendor/autoload.php');
require_once($site_classes . '/class.eff.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_classes . '/class.scansetdetails.php');
require_once($site_classes . '/class.testdata_header.php');
require_once($site_dbConnect);

$dbconnection = site_getDbConnection();

// get the Facility Code and TestDataHeader id from the request:
$fc = $_REQUEST['fc'];
$tdh_key = $_REQUEST['keyheader'];

// Make a TestData_header record object for the FC and header id:
$tdh = new TestData_header();
$tdh->Initialize_TestData_header($tdh_key, $fc);

// if the HTML request includes a Notes parameter call the TestDataHeader method to write those notes back to the database:
// The 'SAVE' button under the notes window reloads this page with the notes text included in the URL.
if (isset($_REQUEST['Notes'])) {
    $tdh->RequestValues_TDH();
}

// Find the ScanSetDetails record id corresponding to the TestDataHeader id:
$q = "SELECT keyId FROM ScanSetDetails WHERE fkHeader = " . $tdh->keyId . ";";
$r = mysqli_query($dbconnection, $q);
$ssid = ADAPT_mysqli_result($r, 0, 0);

// Make a ScanSetDetails record object;
$ssd = new ScanSetDetails();
$ssd->Initialize_ScanSetDetails($ssid, $fc);

// Create the main beam efficiency analysis class and set it up to work with the current scan set:
$eff = new eff();
$eff->Initialize_eff_SingleScanSet($ssid, $fc);

// Create the FrontEnd record object corresponding to the TestDataHeader configuration:
$fe = new FrontEnd();
$fe->Initialize_FrontEnd_FromConfig($tdh->GetValue('fkFE_Config'), $fc, FrontEnd::INIT_NONE);

// feconfig is the configuration number for the front end:
$feconfig = $fe->feconfig->keyId;

// get the front end serial number and the current measurement cartridge band:
$fesn = $fe->GetValue('SN');
$band = $tdh->GetValue('Band');

$fetms = $tdh->GetFetmsDescription("Measured at: ");

$defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];

$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

$stylesheet = file_get_contents('pdf.css');
$mpdf = new \Mpdf\Mpdf(
    [
        'tempDir' => $main_write_directory,
        'margin_left' => 5,
        'margin_right' => 5,
        'margin_header' => 0,
        'fontDir' => array_merge($fontDirs, [
            $files_root . '/fonts',
        ]),
        'fontdata' => $fontData + [
            'Ubuntu' => [
                'B' => 'Ubuntu-B.ttf',
                'BI' => 'Ubuntu-BI.ttf',
                'C' => 'Ubuntu-C.ttf',
                'L' => 'Ubuntu-L.ttf',
                'LI' => 'Ubuntu-LI.ttf',
                'M' => 'Ubuntu-M.ttf',
                'MI' => 'Ubuntu-MI.ttf',
                'R' => 'Ubuntu-R.ttf',
                'RI' => 'Ubuntu-RI.ttf',
                'Th' => 'Ubuntu-Th.ttf',
            ]
        ],
        'default_font' => 'Ubuntu'
    ]
);
$mpdf->SetTitle('Front End SN ' . $fesn . ' Band ' . $band . ' Beam Pattern');
$mpdf->setAutoTopMargin = 'stretch';
$html = '<div class="header">';
$html .= '<div class="header-logo"><img src="' . $files_root . '/alma-logo.jpg" width="70.6px" height="100px"/></div>';
$html .= '<div class="header-inner">';
$html .= 'Front End SN ' . $fesn . '<br>';
$html .= 'Band ' . $band . ' Beam Pattern';
$html .= '</div>';
$html .= '</div>';
$mpdf->SetHTMLHeader($html, '', true);
$mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);

$html = "<div class='scan-information'>";
$html .= "<table class='table-scan'>";
if ($fetms)
    $html .= "<tr><th class='table-name'>$fetms</th></tr>";
$html .= "<tr><th>Notes</th></tr>";
$html .= "<tr><td>";
$html .= "<textarea rows=8 width='100%' name='Notes'>" . stripslashes($tdh->GetValue('Notes')) . "</textarea>";
$html .= "<input type='hidden' name='fc' value='" . $tdh->GetValue('keyFacility') . "'>";
$html .= "<input type='hidden' name='keyheader' value='$tdh->keyId'>";
$html .= "</td></tr>";
$html .= "</table>";
$html .= "</div>";
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

$html = $eff->Display_ScanInformation_html();
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
$html = $eff->Display_SetupParameters_html();
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
$html = $eff->Display_ApertureEff_html();
$html .= $eff->Display_TaperEff_html();
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
$html = $eff->Display_PhaseEff_html();
$html .= $eff->Display_SpilloverEff_html();
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
$html = $eff->Display_PolEff_html();
$html .= $eff->Display_DefocusEff_html();
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
$html = $eff->Display_PhaseCenterOffset_html();
$html .= $eff->Display_Squint_html();
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
$html = $eff->Display_Equations_html();
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

$mpdf->AddPage();

$html = $eff->Display_PointingAngles_html();
$html .= $eff->Display_PointingAngleDiff_html();
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
$html = $eff->Display_PointingAnglesPlot_html();
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

$mpdf->AddPage();
$html = $eff->Display_AllAmpPhasePlots_html(0, 'nf');
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

$mpdf->AddPage();
$html = $eff->Display_AllAmpPhasePlots_html(0, 'ff');
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

$mpdf->AddPage();
$html = $eff->Display_AllAmpPhasePlots_html(1, 'nf');
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

$mpdf->AddPage();
$html = $eff->Display_AllAmpPhasePlots_html(1, 'ff');
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

$mpdf->Output('FE_SN_' . $fesn . '_Band_' . $band . '_Beam_Pattern', 'I');
