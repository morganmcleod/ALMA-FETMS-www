<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <link href="../images/favicon.ico" rel="shortcut icon" type="image/x-icon">
    <title>Edit Data Sets</title>

    <script type="text/javascript" src="../../ext4/ext-all.js"></script>
    <link type="text/css" href="../../ext4/resources/css/ext-all.css" rel="Stylesheet" />

    <link rel="stylesheet" type="text/css" href="../Cartstyle.css">
    <link rel="stylesheet" type="text/css" href="../buttons.css">

    <script type="text/javascript" src="TreeTable.js"></script>

    <style>
         .x-tree-checked .x-grid-cell-inner {
             font-weight: bold;
         }
         .x-grid-row-selected .x-grid-cell {
             background-color: #efefef !important;
         }
    </style>
</head>

<?php

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_dbConnect);

$TDHid	  = $_REQUEST['id'];
$FEid     = $_REQUEST['fe'];
$fc       = $_REQUEST['fc'];
$Band     = $_REQUEST['b'];
$datatype = $_REQUEST['d'];
$compId   = 0;

// get test description from the DB
$q = "SELECT `Description`
	FROM `TestData_Types`
	WHERE `keyId` = $datatype";

$r = @mysql_query($q, $db);
$test_desc = @mysql_result($r,0,0);

$title = $test_desc;

if ($FEid) {
    $fe = new FrontEnd();
    $fe->Initialize_FrontEnd($FEid, $fc, FrontEnd::INIT_NONE);
    
    //The following two variables are used in datasets_header.php for the Front End button in top right corner.
    $feconfig = $fe->feconfig->keyId;
    $fesn = $fe->GetValue('SN');
    
    $title .= " FE-$fesn";
    unset($fe);

} else {
    $tdh = new TestData_header();
    $tdh->Initialize_TestData_header($TDHid, $fc);
    $compId = $tdh->GetValue('fkFE_Components');
    
    $title .= " Band $Band";
    unset($tdh);
}

switch ($datatype){
	case 7:
		// IF spectrum
		$link = "../ifspectrum/ifspectrumplots.php?fc=$fc&fe=$FEid&b=$Band&id=";
		break;
	case 57:
		// LO Lock test
		$link = "../testdata/testdata.php?fc=40&keyheader=";
		break;
	case 58:
		// Noise temperature
		$link = "../testdata/testdata.php?fc=40&keyheader=";
		break;
}

include('datasets_header.php');
?>

<body onload="javascript:CreateTree(<?php echo "$FEid, $Band, '$datatype', '$test_desc', '$link', '$TDHid', '$compId'"; ?>)" style="background-color: #19475E; ">
	<div style= 'padding-left: 2em; padding-top: 1em; width:1100px; background-color: #19475E;'>
        <div id="tree-div"></div>
    </div>
</body>
</html>