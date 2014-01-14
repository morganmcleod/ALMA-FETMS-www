
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link rel="stylesheet" type="text/css" href="../Cartstyle.css">
<link rel="stylesheet" type="text/css" href="../buttons.css">
<link type="text/css" href="../../ext/resources/css/ext-all.css" media="screen" rel="Stylesheet" />
<script src="../../ext/adapter/ext/ext-base.js" type="text/javascript"></script>
<script src="../../ext/ext-all.js" type="text/javascript"></script>
<script src="../dbGrid.js" type="text/javascript"></script>

<title>Test Data</title>
</head>
<body style="background-color: #19475E">

<?php

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.cryostat.php');
require_once($site_classes . '/class.testdata_header.php');

$fc = $_REQUEST['fc'];


$TestData_header_keyId = $_REQUEST['keyheader'];
$td = new TestData_header();
$td->Initialize_TestData_header($TestData_header_keyId, $fc);
$td->TestDataHeader = $TestData_header_keyId;


$cryostat = new Cryostat;
$cryostat->Initialize_Cryostat($td->Component->keyId, $fc);
$cryostat->RequestValues_Cryostat();


if (isset($_REQUEST['submitted'])){


    if ($cryostat->keyId == ''){
        $cryostat->NewRecord_Cryostat($fc);
        $cryostat->RequestValues_Cryostat();
    }
    $cryostat->Update_Cryostat();
    echo "Record Updated<br><br>";
    //echo '<meta http-equiv="Refresh" content="1;url=cryostat.php?keyId='.$cryostat->keyId.'&fc='.$cryostat->GetValue('keyFacility').'">';
}

$title = "<a href='../ShowComponents.php?conf=$cryostat->keyId&fc=$fc'>
          <font color='#ffffff'>Cryostat " . $cryostat->GetValue('SN') .
          "</font></a>";

$feconfig=$cryostat->FEConfig;
$fesn=$cryostat->FESN;
include('header_with_fe.php');

?>
<form enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
<div id="wrap" style="height:6000px">

<div id="sidebar2" style="height:6000px">
<table>


<?php
  $exportcsvurl = "export_to_csv.php?keyheader=$td->keyId&fc=".$td->GetValue('keyFacility');

    switch ($td->GetValue('fkTestData_Type')) {
        case '28':
             //cryo pas
             echo "
            <tr><td>
                <a style='width:90px' href='$exportcsvurl' class='button blue2 biground'>
                <span style='width:130px'>Export CSV</span></a>
            </tr></td>";
            break;
         case '52':
             //cryo first cooldown
             echo "
            <tr><td>
                <a style='width:90px' href='$exportcsvurl' class='button blue2 biground'>
                <span style='width:130px'>Export CSV</span></a>
            </tr></td>";
            break;



          case '53':
             //cryo first warmup
             echo "
            <tr><td>
                <a style='width:90px' href='$exportcsvurl' class='button blue2 biground'>
                <span style='width:130px'>Export CSV</span></a>
            </tr></td>";
            break;
        default:
            echo "
            <tr><td>
                <a style='width:90px' href='$exportcsvurl' class='button blue2 biground'>
                <span style='width:130px'>Export CSV</span></a>
            </tr></td>
            <tr><td>
                <a style='width:90px' href='$drawurl' class='button blue2 biground'>
                <span style='width:130px'>Generate Plot</span></a>
            </tr></td>";
    }


?>





</table>
</div>

<div id="maincontent" style="height:6000px">

<div id = "wrap">
<?php
echo "<table<tr><td>";
$td->Display_DataForm();
echo "</td></tr><tr><td>";
$cryostat->DisplayData_Cryostat($td->GetValue('fkTestData_Type'));
echo "</td></tr>";

unset($td);
?>

</div></div></div></form></body>
</html>