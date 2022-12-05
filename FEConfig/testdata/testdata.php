<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <link href="../images/favicon.ico" rel="shortcut icon" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="../Cartstyle.css">
    <link rel="stylesheet" type="text/css" href="../tables.css">
    <link rel="stylesheet" type="text/css" href="../buttons.css">
    <link rel="stylesheet" type="text/css" href="../../ext4/resources/css/ext-all.css" />
    <script type="text/javascript" src="../../ext4/ext-all.js"></script>
    <script type='text/javascript' src='../../classes/pickComponent/popupMoveToOtherFE.js'></script>

    <?php
    require_once(dirname(__FILE__) . '/../../SiteConfig.php');
    require_once($site_dbConnect);
    require_once($site_classes . '/class.testdata_header.php');
    require_once($site_classes . '/class.cca_image_rejection.php');
    require_once($site_classes . '/class.finelosweep.php');
    require_once($site_classes . '/class.noisetemp.php');
    require_once($site_classes . '/class.wca.php');

    $fc = $_REQUEST['fc'] ?? '40';
    $drawplot = $_REQUEST['drawplot'] ?? false;
    $keyHeader = $_REQUEST['keyheader'] ?? false;
    $showrawdata = $_REQUEST['showrawdata'] ?? false;
    $fkDataStatus = $_REQUEST['fkDataStatus'] ?? NULL;
    $Notes = $_REQUEST['Notes'] ?? NULL;
    if (!$keyHeader) exit();

    $td = new TestData_header($keyHeader, $fc);

    // Some plot types should draw automatically if the PlotURL is blank:
    if (!$td->PlotURL && $td->AutoDrawThis())
        $drawplot = true;

    // Compute page title and header text, buttons...
    if ($td->Component->ComponentType == "Front End") {
        // Test data is associated with a front end...
        $feconfig = $td->frontEnd->feconfig->keyId;
        $fesn = $td->frontEnd->SN;
        // URL for header button to FE configuration:
        $header_main  = '<a href="../ShowFEConfig.php?key='
            . $td->frontEnd->feconfig->keyId . '&fc=' . $fc
            . '"><font color="#ffffff">'
            . $td->Component->ComponentType
            . ' SN ' . $td->Component->SN
            . '</font></a>';
    } else {
        // Test data is associated with a component...
        $feconfig = $td->Component->FEConfig;
        $fesn = $td->Component->FESN;
        $compdisplay = $td->Component->ComponentType;
        $band = $td->Component->Band;

        if ($band)
            $compdisplay .= " Band $band";

        $compdisplay .= " SN " . $td->Component->SN;

        // URL for for header button to component configuration:
        $refurl = "../ShowComponents.php?";
        $refurl .= "conf=" . $td->Component->keyId . "&fc=" . $td->keyFacility;
        $header_main = '<a href="' . $refurl . '"><font color="#ffffff">' . $compdisplay . '</font></a>';
    }

    $title = "";
    if ($fesn && !$FETMS_CCA_MODE) $title = "FE-$fesn - ";
    $band = $td->Band;
    if ($band) $title .= "Band $band - ";
    $title .= $td->testDataType;

    echo "<title>{$title}</title></head>";
    echo "<body style='background-color: #19475E'>";
    include('header_with_fe.php');

    // Display the buttons on the left:
    ?>
    <form enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
        <div id="wrap" style="height:6000px">
            <div id="sidebar2" style="height:6000px">
                <?php
                $td->displayTestDataButtons();
                ?>
            </div>
        </div>
    </form>

    <div id="maincontent" style="height:6000px">
        <?php
        echo "<div style='width:700px; text-align:right'>";

        $relativePath = preg_replace("/\/(\w+).php/", "", $_SERVER['PHP_SELF']);
        $buttonText = "Generate PDF";
        $buttonURL =  $relativePath . "/testdata_pdf.php?keyheader=$keyHeader&fc=$fc";

        echo "<a style='width:90px' href='$buttonURL' class='button blue2 bigrounded'>
         <span >$buttonText</span></a>";

        // style='width:130px'
        echo "</div>";
        ?>
        <div id="wrap">

            <?php
            // Update the TDH record with new values for fkDataStatus or Notes:
            $td->requestValuesHeader($fkDataStatus, $Notes);

            // Draw the plots, if needed or requested:
            if ($drawplot) {
                //Show a spinner while plots are being drawn.
                include($site_FEConfig . '/spin.php');
                $td->drawPlot();
                $refurl = "testdata.php?keyheader=$keyHeader";
                $refurl .= "&fc=$fc";
                echo '<meta http-equiv="Refresh" content="1;url=' . $refurl . '">';
            }

            $td->Display_TestDataMain();

            if ($td->AutoShowRawDataThis())
                $showrawdata = true;

            if ($showrawdata)
                $td->Display_RawTestData();

            unset($td);

            ?>

        </div>
    </div>
    </body>

</html>