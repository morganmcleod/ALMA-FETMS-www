<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <link href="../images/favicon.ico" rel="shortcut icon" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="../Cartstyle.css">
    <link rel="stylesheet" type="text/css" href="../tables.css">
    <link rel="stylesheet" type="text/css" href="../buttons.css">
    <link type="text/css" href="../../ext/resources/css/ext-all.css" media="screen" rel="Stylesheet" />
    <script src="../../ext/adapter/ext/ext-base.js" type="text/javascript"></script>
    <script src="../../ext/ext-all.js" type="text/javascript"></script>
    <script type="text/javascript" src="./PAICheckBox.js"></script>
    <script type="text/javascript" src="../spin.js"></script>

    <?php
    require_once(dirname(__FILE__) . '/../../SiteConfig.php');
    require_once($site_dbConnect);

    $dbconnection = site_getDbConnection();

    include('pas_tables.php');

    $band = $_REQUEST['band'];
    $feconfig = $_REQUEST['FE_Config'];
    $Data_Status = $_REQUEST['Data_Status'];
    $filterChecked = isset($_REQUEST["filterChecked"]) ? $_REQUEST["filterChecked"] : false;

    // get FE serial number
    $q = "SELECT `Front_Ends`.`SN`
	FROM `Front_Ends` JOIN `FE_Config`
	ON `FE_Config`.fkFront_Ends = `Front_Ends`.keyFrontEnds
	WHERE `FE_Config`.keyFEConfig=$feconfig";

    $r = mysqli_query($dbconnection, $q);
    $fesn = ADAPT_mysqli_result($r, 0, 0);

    // get Data Status Description
    $q = "SELECT `Description` FROM `DataStatus` WHERE `keyId` = $Data_Status ";

    $r = mysqli_query($dbconnection, $q);
    $Data_Status_Desc = ADAPT_mysqli_result($r, 0, 0);

    $title = "";
    if ($fesn && !$FETMS_CCA_MODE)
        $title = "FE-$fesn - ";

    if ($band)
        $title .= "Band $band - ";

    $title .= "$Data_Status_Desc";

    echo "<title>$title </title></head>";
    echo "<body style='background-color: #19475E'>";
    include('header_with_fe.php');

    echo "<div id='maincontent' style='height:6000px'>";

    echo "<div style='width:700px; text-align:right'>";

    $buttonText = $filterChecked ? "Show All" : "Show Selected";
    $buttonURL = $_SERVER['PHP_SELF'] . "?FE_Config=$feconfig&band=$band&Data_Status=$Data_Status";

    if (!$filterChecked)
        $buttonURL .= "&filterChecked=1";

    echo "<a style='width:90px' href='$buttonURL' class='button blue2 bigrounded'>
        <span >$buttonText</span></a>";

    $relativePath = preg_replace("/\/(\w+).php/", "", $_SERVER['PHP_SELF']);
    $buttonText = "Generate PDF";
    $buttonURL =  $relativePath . "/health_check_pdf.php?FE_Config=$feconfig&band=$band&Data_Status=$Data_Status";

    echo "<a style='width:90px' href='$buttonURL' class='button blue2 bigrounded'>
        <span >$buttonText</span></a>";

    // style='width:130px'
    echo "</div>";

    //Display results tables
    if ($band) {

        // LNA - Actual Readings
        results_section_header("LNA");
        band_results_table($feconfig, $band, $Data_Status, 1, $filterChecked);

        // SIS � Actual Readings
        results_section_header("SIS");
        band_results_table($feconfig, $band, $Data_Status, 3, $filterChecked);

        // Temperature Sensors � Actual Readings
        results_section_header("Temperature Sensors");
        band_results_table($feconfig, $band, $Data_Status, 2, $filterChecked);

        // WCA AMC Monitors
        results_section_header("WCA AMC");
        band_results_table($feconfig, $band, $Data_Status, 12, $filterChecked);

        // WCA PA Monitors
        results_section_header(" WCA PA");
        band_results_table($feconfig, $band, $Data_Status, 13, $filterChecked);

        // WCA PLL Monitors
        results_section_header("WCA PLL");
        band_results_table($feconfig, $band, $Data_Status, 14, $filterChecked);

        // Nominal IF power levels
        results_section_header("IF Power");
        band_results_table($feconfig, $band, $Data_Status, 6, $filterChecked);

        // Y-factor
        results_section_header("Y-factor");
        band_results_table($feconfig, $band, $Data_Status, 15, $filterChecked);

        // I-V Curve
        results_section_header("I-V Curve");
        band_results_table($feconfig, $band, $Data_Status, 39, $filterChecked);
    } else {

        // CPDS monitors
        results_section_header("CPDS");
        results_table($feconfig, $Data_Status, 24, $filterChecked);

        // FLOOG Total Power
        results_section_header("FLOOG Total Power");
        results_table($feconfig, $Data_Status, 5, $filterChecked);

        // IF switch temperature sensors
        results_section_header("IF Switch Temperatures");
        results_table($feconfig, $Data_Status, 10, $filterChecked);

        // LO Photonic Receiver Monitor Data
        results_section_header("LPR");
        results_table($feconfig, $Data_Status, 8, $filterChecked);

        // Photomixer Monitor Data
        results_section_header("Photomixers");
        results_table($feconfig, $Data_Status, 9, $filterChecked);

        // Cryo-cooler Temperatures
        results_section_header("Cryostat Temperatures");
        results_table($feconfig, $Data_Status, 4, $filterChecked);
    }

    ?>
    </div>
    </body>

</html>
