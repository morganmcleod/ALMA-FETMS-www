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

    $fc = isset($_REQUEST['fc']) ? $_REQUEST['fc'] : '40';
    $drawplot = isset($_REQUEST['drawplot']) ? $_REQUEST['drawplot'] : false;
    $keyHeader = isset($_REQUEST['keyheader']) ? $_REQUEST['keyheader'] : false;
    $showrawdata = isset($_REQUEST['showrawdata']) ? $_REQUEST['showrawdata'] : false;

    if (!$keyHeader)
        exit();        // nothing to do.

    $td = new TestData_header();
    $td->Initialize_TestData_header($keyHeader, $fc);

    // Some plot types should draw automatically if the PlotURL is blank:
    if (!$td->GetValue('PlotURL') && $td->AutoDrawThis())
        $drawplot = true;

    // Compute page title and header text, buttons...
    if ($td->Component->ComponentType == "Front End") {
        // Test data is associated with a front end...
        $feconfig = $td->FrontEnd->feconfig->keyId;
        $fesn = $td->FrontEnd->GetValue('SN');
        // URL for header button to FE configuration:
        $header_main  = '<a href="../ShowFEConfig.php?key='
            . $td->FrontEnd->feconfig->keyId . '&fc=' . $fc
            . '"><font color="#ffffff">'
            . $td->Component->ComponentType
            . ' SN ' . $td->Component->GetValue('SN')
            . '</font></a>';
    } else {
        // Test data is associated with a component...
        $feconfig = $td->Component->FEConfig;
        $fesn = $td->Component->FESN;
        $compdisplay = $td->Component->ComponentType;
        $band = $td->Component->GetValue('Band');

        if ($band)
            $compdisplay .= " Band $band";

        $compdisplay .= " SN " . $td->Component->GetValue('SN');

        // URL for for header button to component configuration:
        $refurl = "../ShowComponents.php?";
        $refurl .= "conf=" . $td->Component->keyId . "&fc=" . $td->GetValue('keyFacility');
        $header_main = '<a href="' . $refurl . '"><font color="#ffffff">' . $compdisplay . '</font></a>';
    }

    $title = "";
    if ($fesn && !$FETMS_CCA_MODE)
        $title = "FE-$fesn - ";

    $band = $td->GetValue('Band');
    if ($band)
        $title .= "Band $band - ";

    $title .= $td->TestDataType;

    echo "<title>" . $title . "</title></head>";
    echo "<body style='background-color: #19475E'>";
    include('header_with_fe.php');

    // Display the buttons on the left:
    ?>
    <form enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
        <div id="wrap" style="height:6000px">
            <div id="sidebar2" style="height:6000px">
                <?php
                $td->Display_TestDataButtons();
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
            $td->RequestValues_TDH();

            // Draw the plots, if needed or requested:
            if ($drawplot) {
                //Show a spinner while plots are being drawn.
                include($site_FEConfig . '/spin.php');
                $td->DrawPlot();
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