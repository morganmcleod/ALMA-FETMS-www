<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<link href="../images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
<link type="text/css" href="../../ext4/resources/css/ext-all.css" rel="Stylesheet" />
<link rel="stylesheet" type="text/css" href="../Cartstyle.css">
<link rel="stylesheet" type="text/css" href="../buttons.css">
<script type="text/javascript" src="../../ext4/bootstrap.js"></script>
<script type="text/javascript" src="../../ext4/examples/shared/examples.js"></script>
<script type="text/javascript" src="cidlREV.js"></script>
<script type="text/javascript" src="../Ext.ux.plugins.HeaderButtons.js"></script>
<script type="text/javascript" src="cidl.js"></script>
<title>CIDL Change Record</title>
</head>

<?php

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.frontend.php');

//feconfig- Key value for a record in the FEConfig table
$feconfig = $_REQUEST['feconfig'];
//fc- Facility code
$fc = $_REQUEST['fc'];

//Instantiate a FrontEnd object from the feconfig value.
$fe = new FrontEnd();
$fe->Initialize_FrontEnd_FromConfig($feconfig, $fc, FrontEnd::INIT_NONE);
$fesn = $fe->GetValue('SN');

$title="CIDL Front End " . $fe->GetValue('SN');
//title is used in header
include('header.php');

?>

<body style="background-color: #19475E;" onload="javascript:creategrid(<?php echo "$fe->keyId,$feconfig"; ?>);">
    <div style="padding-left: 12em; padding-top: 1em;">
        <div id='editor-grid'></div>
        <div id='toolbar'></div>
    </div>
</body>
</html>

