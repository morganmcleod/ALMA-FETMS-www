<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">

<link rel="stylesheet" type="text/css" href="Cartstyle.css">
<link rel="stylesheet" type="text/css" href="buttons.css">

<link rel="stylesheet" type="text/css" href="recordform/css/Ext.ux.grid.RowActions.css">
<link rel="stylesheet" type="text/css" href="recordform/css/empty.css" id="theme">
<link rel="stylesheet" type="text/css" href="recordform/css/icons.css">
<link rel="stylesheet" type="text/css" href="recordform/css/recordform.css">

<link rel="stylesheet" type="text/css" href="../../ext/examples/ux/css/RowEditor.css">

<!-- <link rel="shortcut icon" href="../img/extjs.ico"> -->

<link type="text/css" href="../ext/resources/css/ext-all.css" media="screen" rel="Stylesheet" />

<script src="../ext/adapter/ext/ext-base.js" type="text/javascript"></script>
<script src="../ext/ext-all.js" type="text/javascript"></script>
<script src="../ext/examples/ux/fileuploadfield/FileUploadField.js" type="text/javascript"></script>

<script type="text/javascript" src="../ext/examples/ux/RowEditor.js"></script>

<script type="text/javascript" src="recordform/js/WebPage.js"></script>
<script type="text/javascript" src="recordform/js/Ext.ux.util.js"></script>
<script type="text/javascript" src="recordform/js/Ext.ux.form.ThemeCombo.js"></script>
<script type="text/javascript" src="recordform/js/Ext.ux.Toast.js"></script>
<script type="text/javascript" src="recordform/js/Ext.ux.grid.Search.js"></script>
<script type="text/javascript" src="recordform/js/Ext.ux.menu.IconMenu.js"></script>
<script type="text/javascript" src="recordform/js/Ext.ux.grid.RowActions.js"></script>
<script type="text/javascript" src="recordform/js/Ext.ux.grid.RecordForm.js"></script>
<script type="text/javascript" src="recordform/js/Ext.ux.form.DateTime.js"></script>
<script type="text/javascript" src="recordform/js/Example.Grid.js"></script>
<script type="text/javascript" src="recordform/recordform.js"></script>

<link rel="stylesheet" type="text/css" href="headerbuttons.css">

<title>Update Front End</title>
<style media="screen" type="text/css">
.x-grid3-hd.x-grid3-td-description {text-align:center;}
.x-grid3-td-description {text-align:left;}
</style>
</head>

<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.frontend.php');
require_once('dbGetQueries.php');

$fc = $_REQUEST['fc'];
$getqueries=new dbGetQueries;

$fe = new FrontEnd();
$fe->Initialize_FrontEnd_FromSN($_REQUEST['sn'], $fc);

//These are used by header.php
$feconfig = $fe->feconfig->keyId;
$fesn = $fe->GetValue('SN');
$title="Front End-$fesn";

if (strpos($_SERVER['HTTP_USER_AGENT'],'MSIE') > 0){
    //In IE, the header is pushed down from the top for some reqason.
    //Until I can figure out why, this code will push it back up.
    echo "<div style='margin-top:-60px;'>";
}

Include "header.php";

if (strpos($_SERVER['HTTP_USER_AGENT'],'MSIE') > 0){
    echo "</div>";
}
?>
<div id="wrap">
    <body onload="javascript:showsubform(<?php echo $feconfig . "," . $fc; ?>)">
        <div style="height:900px;">
            <div id="spe">
                <div id="west-content" class="x-hidden">
                    <div id="Description">
                    </div>
                </div>
            </div>
        </div>
        <div id="win_req_in">
        </div>
    </body>
</div>
<?php
Include "footer.php";
?>
</html>

