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
<script type="text/javascript" src="loadCCAEdit.js"></script>

<title>EDIT CCA Configuration</title>
</head>

<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.cca.php');
require_once('HelperFunctions.php');
require_once('jsFunctions.php');

if (isset($_REQUEST['fc'])){
    $fc = $_REQUEST['fc'];
}

$comp_key=$_REQUEST['conf'];
$cca = new CCA();
$cca->Initialize_CCA($comp_key, $fc, CCA::INIT_SLN);

$title="Edit CCA " . $cca->GetValue('Band') . "-" . $cca->GetValue('SN') . " Configuration";

$feconfig = $cca->FEConfig;
$fesn = $cca->FESN;
$band = $cca->GetValue('Band');

echo "<body id = 'body2' onload='createCompTabs(" . $band . ", 20, " . $comp_key . ")' BGCOLOR='#19475E'>";

include "header.php";

if (isset($_REQUEST['submit'])) {
    //CCA, update configuration

    echo "UPDATED<br>";
    echo "cca id= $cca->keyId<br>";

    //Preserve these values in the new SLN record

    $oldStatus   = $cca->sln->GetValue('fkStatusType');
    $oldLocation = $cca->sln->GetValue('fkLocationNames');
    $updatestring = "Updated mixer and preamp params for CCA ". $cca->GetValue('Band') . "-" . $cca->GetValue('SN');
    $feconfig = $cca->FEConfig;


    $cca->Update_Configuration();
    echo "<meta http-equiv='Refresh' content='0.1;url=ShowComponents.php?conf=$cca->keyId&fc=$fc'>";


    //Update sln info for component and front end
    $dbops->UpdateStatusLocationAndNotes_FE($cca->FEfc, '', '',$updatestring,$feconfig, $feconfig, ' ','');
}
echo "<form action='".$_SERVER["PHP_SELF"]."' method='post' name='Submit' id='Submit'>";
echo "
    <div id='sidebar2' >
        <table>
            <tr><td>
            <input type='submit' name='submit' class='button blue2 biground' value = 'Submit' style='width:120px'>
            <br>
            </td></tr>

            <tr>
                <td>
                    <a style='width:90px' href='ShowComponents.php?conf=".$cca->keyId."&fc=$fc' class='button blue2 biground'>
                    <span style='width:130px'>Cancel</span></a>
                </td>
            </tr>

        </table>
    </div>";
?>

<div id="maincontent2" >
    <div id="tabs1"  ></div>
</div>

<?php
    echo "<input type = 'hidden' name ='conf' value='$cca->keyId'>";
    echo "<input type = 'hidden' name ='fc' value='".$cca->GetValue('keyFacility')."'>";
?>

</form>
<br><br>
<?php
// include "footer.php";
?>
</body>
</html>

