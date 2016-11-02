<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">

<link rel="stylesheet" type="text/css" href="Cartstyle.css">
<link rel="stylesheet" type="text/css" href="tables.css">
<link rel="stylesheet" type="text/css" href="buttons.css">

<link href="images/favicon.ico" rel="shortcut icon" type="image/x-icon" />

<link type="text/css" href="../ext/resources/css/ext-all.css" media="screen" rel="Stylesheet" />

<script src="../ext/adapter/ext/ext-base.js" type="text/javascript"></script>
<script src="../ext/ext-all.js" type="text/javascript"></script>
<script src="../ext/examples/simple-widgets/qtips.js" type="text/javascript"></script>

<script type="text/javascript" src="ToolTips.js"></script>
<script type="text/javascript" src="confighistory/dbGridConfigHistoryComp.js"></script>
<script type="text/javascript" src="Ext.ux.plugins.HeaderButtons.js"></script>

<link rel="stylesheet" type="text/css" href="Ext.ux.plugins.HeaderButtons.css">
<link rel="stylesheet" type="text/css" href="headerbuttons.css">
<link rel="stylesheet" type="text/css" href="../ext/examples/ux/fileuploadfield/css/fileuploadfield.css">

<script src="../ext/examples/ux/fileuploadfield/FileUploadField.js" type="text/javascript"></script>

<script type="text/javascript" src="wca/UploadWCAconfig.js"></script>
<script type="text/javascript" src="cca/UploadCCAconfig.js"></script>
<script type="text/javascript" src="loadComponents.js"></script>
<script type="text/javascript" src="testdata/PAICheckBox.js"></script>

<title>Show Component</title>
</head>

<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.cca.php');
require_once($site_classes . '/class.cryostat.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_classes . '/xlreader/reader.php');
require_once('dbGetQueries.php');
require_once('HelperFunctions.php');
require_once('jsFunctions.php');

$fc = $_REQUEST['fc'];

$comp_key=$_GET['conf'];
$component = new FEComponent();
$component->Initialize_FEComponent($comp_key, $fc);
$band=$component->GetValue('Band');
$comp_type=$component->GetValue('fkFE_ComponentType');

$title="FE-$component->FESN Component";

$feconfig = $component->FEConfig;
$fesn = $component->FESN;

include "header.php";

$getQuery=new dbGetQueries;

if (isset($_REQUEST['submitted_ccafile'])){
    $cca = new CCA();
    $cca->Initialize_CCA($comp_key,$fc);
    $cca->RequestValues_CCA();
}

if (isset($_REQUEST['submit_datafile_cryostat'])){
    //Cryostat
    $cryo = new Cryostat();
    $cryo->Initialize_Cryostat($comp_key,$fc);
    $cryo->RequestValues_Cryostat();
    $cryo->Update_Cryostat();
    $url = "ShowComponents.php?conf=$cryo->keyId";
    $url .= "&fc=" . $cryo->GetValue('keyFacility');
    unset($cryo);
    //echo "<meta http-equiv='Refresh' content='1;url=$url'>";
}

if ($band < 1){
    $band = 0;
}

if ($component->IsDocument == 1){
    $CompDescription = 'Document';
}
else{
    $CompDescription = 'Component';
}
if ($comp_type == 20){
    $CompDescription = 'CCA';
}
if ($comp_type == 11){
    $CompDescription = 'WCA';
}
if ($comp_type == 6){
    $CompDescription = 'Cryostat';
}
?>

<body id = 'body3' onload="createCompTabs(<?php echo "$band,$comp_type,$comp_key,$fc,'$CompDescription'"; ?>); creategridConfigHistoryComp(<?php echo "$comp_key,$fc"?>);" BGCOLOR="#19475E">
<div id="wrap">
<?php
    //Display a warning if the current page is not for the latest configuration
    $component->GetMaxConfig();
    if ($component->MaxConfig != $component->keyId){
        if ($component->MaxConfig != 0){
            echo "<font color='#ff0000'>
                  Warning: This configuration ($component->keyId) is not the most current configuration ($component->MaxConfig).
                  </font><br>";
        }
    }
?>

    <div style='padding-left:20px;padding-top:20px'>
        <div id='toolbar'></div>
        <div id="tabs1">
            <div id="parent1" class="x-hide-display"></div>
            <div id="parent2" class="x-hide-display">
                <div id="subtab" class="x-hide-display"></div>
            </div>
        </div>
        <br>
    </div>
</div>

<div id="maincontent4" >
    <br>
    <div id="db-grid-confighistorycomp" style='padding-left:20px;padding-top:25px;width:1100px;'></div>
</div>
<?php
unset ($component);
include "footer.php" ;?>
</body>
</html>