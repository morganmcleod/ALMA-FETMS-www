<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($files_root . '/vendor/autoload.php');
require_once($site_dbConnect);
require_once('pdf_tables.php');

$dbConnection = site_getDbConnection();

$defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];

$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

$band = $_REQUEST['band'];
$feconfig = $_REQUEST['FE_Config'];
$Data_Status = $_REQUEST['Data_Status'];
$filterChecked = isset($_REQUEST["filterChecked"]) ? $_REQUEST["filterChecked"] : false;

$q = "SELECT `Front_Ends`.`SN`
    FROM `Front_Ends` JOIN `FE_Config`
    ON `FE_Config`.fkFront_Ends = `Front_Ends`.keyFrontEnds
    WHERE `FE_Config`.keyFEConfig=$feconfig";

$r = mysqli_query($dbConnection, $q);
$fesn = ADAPT_mysqli_result($r, 0, 0);

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
$mpdf->SetTitle('Front End SN ' . $fesn . ' Band ' . $band . ' Health Check');
$mpdf->setAutoTopMargin = 'stretch';
$html = '<div class="header">';
$html .= '<div class="header-logo"><img src="' . $files_root . '/alma-logo.jpg" width="70.6px" height="100px"/></div>';
$html .= '<div class="header-inner">';
$html .= 'Front End SN ' . $fesn . '<br>';
$html .= 'Band ' . $band . ' Health Check';
$html .= '</div>';
$html .= '</div>';
$mpdf->SetHTMLHeader($html, '', true);
$mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
$html = '<link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Ubuntu:regular,bold&subset=Latin">';
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HEADER_CSS);

$html = band_results_table_html($feconfig, $band, $Data_Status, 1, $filterChecked);
$html .= band_results_table_html($feconfig, $band, $Data_Status, 3, $filterChecked);
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

$html = band_results_table_html($feconfig, $band, $Data_Status, 2, $filterChecked);
$html .= band_results_table_html($feconfig, $band, $Data_Status, 12, $filterChecked);
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

$html = band_results_table_html($feconfig, $band, $Data_Status, 13, $filterChecked);
$html .= band_results_table_html($feconfig, $band, $Data_Status, 14, $filterChecked);
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

$html = band_results_table_html($feconfig, $band, $Data_Status, 6, $filterChecked);
$html .= band_results_table_html($feconfig, $band, $Data_Status, 15, $filterChecked);
if (!is_null($html)) {
    $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
}


$html = band_results_table_html($feconfig, $band, $Data_Status, 39, $filterChecked);
if ($html[0] != ""){
    $mpdf->AddPage();
    $mpdf->WriteHTML($html[0], \Mpdf\HTMLParserMode::HTML_BODY);
}
if ($html[1] != ""){
    $mpdf->AddPage();
    $mpdf->WriteHTML($html[1], \Mpdf\HTMLParserMode::HTML_BODY);
}
$mpdf->Output('FE_SN_' . $fesn . '_Band_' . $band . '_Health_Check.pdf', 'I');
