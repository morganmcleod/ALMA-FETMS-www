<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link rel="stylesheet" type="text/css" href="Cartstyle.css">
<link rel="stylesheet" type="text/css" href="tables.css">
<link rel="stylesheet" type="text/css" href="buttons.css">
<link type="text/css" href="../ext/resources/css/ext-all.css" media="screen" rel="Stylesheet" />
<script src="../ext/adapter/ext/ext-base.js" type="text/javascript"></script>
<script src="../ext/ext-all.js" type="text/javascript"></script>
<script src="../ext/examples/simple-widgets/qtips.js" type="text/javascript"></script>
<script type="text/javascript" src="confighistory/dbGridConfigHistory.js"></script>
<script type="text/javascript" src="loadFEConfig.js"></script>
<script type="text/javascript" src="AddNotesEtc.js"></script>
<script type="text/javascript" src="EditFE.js"></script>
<script type="text/javascript" src="cidl.js"></script>
<script type="text/javascript" src="Ext.ux.plugins.HeaderButtons.js"></script>
<link rel="stylesheet" type="text/css" href="Ext.ux.plugins.HeaderButtons.css">
<link href="images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
<link rel="stylesheet" type="text/css" href="headerbuttons.css">

<title>Show Front Ends</title>
</head>

<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_FEConfig . '/HelperFunctions.php');
require_once($site_dbConnect);
// require_once(site_get_config_main());
require('dbGetQueries.php');
require('jsFunctions.php');

$keyFE=$_GET['key'];
$fc = $_REQUEST['fc'];
$fe = new FrontEnd();
$fe->Initialize_FrontEnd_FromConfig($keyFE, $fc, FrontEnd::INIT_CONFIGS);
$fe_sn = $fe->GetValue('SN');

$title="Front End-" . $fe->GetValue('SN');
include "header.php";

?>
<div id="wrap" >
    <body id = 'body2' style="background-color: #19475E" onload="javascript:createtabs(<?php echo "$keyFE,$fe_sn,$fc,$fe->keyId"; ?>);javascript:creategridConfigHistory(<?php echo "$keyFE,$fc,$fe->keyId"; ?>);" >
    <?php
        //Display a warning if the current page is not for the latest configuration

        if ($fe->feconfig->keyId != $fe->feconfig_id_latest) {
            echo "<font color='#ff0000'>
                  Warning: This configuration (".$fe->feconfig->keyId.") is not the most current configuration ($fe->feconfig_id_latest).
                  </font><br>";
        }

        if (isset($_REQUEST['e']) && $_REQUEST['e'] != '') {
            $errorfile = $log_url_directory . $_REQUEST['e'] . ".txt";
    ?>
            <a style="width:90px" class="button red biground">
            <span onClick="window.open('<?echo $errorfile;?>')">
            ERRORS!</span></a>
    <?php
        }
    ?>
    <div style='padding-left:20px;padding-top:20px'>
        <div id='toolbar'></div>
        <div id="tabs1"></div>
    </div>
    <br><br>
    </body>
</div>
<div id="db-grid-confighistory" style='padding-left:20px;padding-top:20px;width:1100px;'></div>
<?php
unset($fe);
include "footer.php";
?>
</html>
