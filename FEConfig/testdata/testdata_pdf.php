<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/tcpdf/tcpdf.php');
require_once($files_root . '/vendor/autoload.php');
require_once($site_classes . '/class.testdata_header_html.php');
require_once($site_classes . '/class.noisetemp_html.php');
require_once($site_classes . '/class.finelosweep_html.php');
require_once($site_dbConnect);
require_once('pdf_tables.php');

$dbconnection = site_getDbConnection();

$defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];

$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

$fc = isset($_REQUEST['fc']) ? $_REQUEST['fc'] : '40';
$keyHeader = isset($_REQUEST['keyheader']) ? $_REQUEST['keyheader'] : false;

if (!$keyHeader)
    exit();

$td = new TestData_header_html();
$td->Initialize_TestData_header($keyHeader, $fc);
$fesn = $td->FrontEnd->GetValue('SN');
$band = $td->GetValue('Band');
$fkTestData_Type = $td->GetValue('fkTestData_Type');
$file_type = null;
switch ($fkTestData_Type) {
    case 56:
        $file_type = "Pol Angles";
        break;
    case 57:
        $file_type = "LO Lock Test";
        break;
    case 58:
        $file_type = "Noise Temp";
        break;
    case 59:
        $file_type = "Fine LO Sweep";
        break;
    case 29:
        $file_type = "Workmanship Amplitude";
        break;
}

$stylesheet = file_get_contents('pdf.css');
$mpdf = new \Mpdf\Mpdf(
    [
        'tempDir' => $files_root . '/mpdf',
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
$mpdf->SetTitle('Front End SN ' . $fesn . ' Band ' . $band . ' ' . $file_type);
$mpdf->setAutoTopMargin = 'stretch';
$html = '<div class="header">';
$html .= '<div class="header-logo"><img src="/nrao_logo.png" width="100px" height="100px"/></div>';
$html .= '<div class="header-inner">';
$html .= 'Front End SN ' . $fesn . '<br>';
$html .= 'Band ' . $band . ' ' . $file_type;
$html .= '</div>';
$html .= '</div>';
$mpdf->SetHTMLHeader($html, '', true);
$mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);

$html = $td->Display_TestDataMain();
$mpdf->WriteHTML($html[0], \Mpdf\HTMLParserMode::HTML_BODY);
if ($html[1] != "") {
    $mpdf->AddPage();
    $mpdf->WriteHTML($html[1], \Mpdf\HTMLParserMode::HTML_BODY);
}
$mpdf->Output('FE_SN_' . $fesn . '_Band_' . $band . '_' . str_replace(' ', '_', rtrim($file_type)), 'I');
