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
    <script type="text/javascript" src="loadIFSpectrum.js"></script>
    <script type="text/javascript" src="../spin.js"></script>

    <?php
    require_once(dirname(__FILE__) . '/../../SiteConfig.php');
    require(site_get_config_main());
    require_once($site_classes . '/IFSpectrum/IFSpectrum_impl.php');
    require_once($site_dbConnect);

    $fc = $_REQUEST['fc'] ?? '';
    $FEid = $_REQUEST['fe'] ?? '';
    $band = $_REQUEST['b'] ?? '';
    $dataSetGroup = $_REQUEST['g'] ?? '';
    $TDHid = $_REQUEST['id'] ?? '0';
    $drawPlots = $_REQUEST['d'] ?? 0;

    // Make a new IF Spectrum object
    $ifspec = new IFSpectrum_impl($TDHid, $fc);
    $ifspec->Initialize_IFSpectrum($FEid, $band, $dataSetGroup, $TDHid);

    if ($TDHid) {
        $tdh = new TestData_header($TDHid, $fc);
        $feconfig = $tdh->fkFE_Config;
        $datastatus = (int)$tdh->fkDataStatus;
    }

    $fesn = $ifspec->frontEnd->SN;
    $tdhIdArray = $ifspec->GetTDHkeyString();

    // Use $dataSetGroup from the IFSpectrum_impl if we don't have it yet.
    if (!$dataSetGroup)
        $dataSetGroup = $ifspec->getDataSetGroup();

    if ($drawPlots) {
        // If drawing plots, create a file for the progress page to use:
        $ifspec->CreateNewProgressFile();

        // Force a redirect to the progress page
        header('Connection: close');
        ob_start();

        /* close out the server process, release to the client */
        phpinfo();
        $size = ob_get_length();
        header("Content-Length: $size");
        header("location: $url_root/FEConfig/pbar/status.php?lf=" . $ifspec->getProgressFile());
        ob_end_flush();
        flush();

        /* end the forced redirect and continue with this script process */
        ignore_user_abort(true);

        // Do the work of generating plots:
        $ifspec->GeneratePlots();

        // wait a bit before deleting the progress file:
        sleep(20);

        $ifspec->DeleteProgressFile();
        exit();
    }

    $title = "Band $band - IF Spectrum - DataSet $dataSetGroup ";

    echo "<title>$title</title>";
    echo "</head>";
    echo "<body BGCOLOR='#19475E'>";

    include "header_ifspectrum.php";

    echo "<script type='text/javascript'>
        Ext.onReady(function() {
            function popupCallback() {
                popupMoveToOtherFE('FE-$fesn', \"$url_root\", [$tdhIdArray]);
            }
            createIFSpectrumTabs($fc, $TDHid, $FEid, $dataSetGroup, $band, $datastatus, popupCallback);
        });</script>";

    ?>

    <div id="content_inside_main2">
        <div id="toolbar" style="margin-top:10px"></div>
        <div id="tabs1"></div>
    </div>
    </body>

</html>