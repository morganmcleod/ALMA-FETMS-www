<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link rel="stylesheet" type="text/css" href="../Cartstyle.css">
<link rel="stylesheet" type="text/css" href="../tables.css">
<link rel="stylesheet" type="text/css" href="../buttons.css">
<link type="text/css" href="../../ext/resources/css/ext-all.css" media="screen" rel="Stylesheet" />
<script src="../../ext/adapter/ext/ext-base.js" type="text/javascript"></script>
<script src="../../ext/ext-all.js" type="text/javascript"></script>
<script src="../dbGrid.js" type="text/javascript"></script>
<script type="text/javascript" src="../spin.js"></script>

<body style="background-color: #19475E">

<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_dbConnect);
include('pas_tables.php');

$band = $_REQUEST['band'];
$FE_Config = $_REQUEST['FE_Config'];
$Data_Status = $_REQUEST['Data_Status'];

// get FE serial number
$q = "SELECT `Front_Ends`.`SN`
	FROM `Front_Ends` JOIN `FE_Config`
	ON `FE_Config`.fkFront_Ends = `Front_Ends`.keyFrontEnds
	WHERE `FE_Config`.keyFEConfig=$FE_Config";

$r = @mysql_query($q,$db);
$fe_sn = @mysql_result($r,0,0);

// get Data Status Description
$q = "SELECT `Description` FROM `DataStatus` WHERE `keyId` = $Data_Status ";

$r = @mysql_query($q,$db);
$Data_Status_Desc = @mysql_result($r,0,0);

If ($band != 0){
	$title = "Front End-$fe_sn - $Data_Status_Desc - Band $band ";
} else {
 	$title = "Front End-$fe_sn - $Data_Status_Desc - Other";
}
//$showrawurl = "testdata.php?showrawdata=1&keyheader=$td->keyId&fc=".$td->GetValue('keyFacility');
//$drawurl = "testdata.php?drawplot=1&keyheader=$td->keyId&fc=".$td->GetValue('keyFacility');
//$exportcsvurl = "export_to_csv.php?keyheader=$td->keyId&fc=".$td->GetValue('keyFacility');


include('header_with_fe.php');

echo "<title>$title </title></head>";

       ?>

<form enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
<div id="wrap" style="height:6000px">

<div id="sidebar2" style="height:6000px">
<table>

</table>
</div>

<div id="maincontent" style="height:6000px">
<div id = "wrap">




<form action=".htmlentities($_SERVER['PHP_SELF'])." method='post'>
<br>
<input type='submit' name='formSubmit' value='Save Changes' />
<br>

<?php
if (isset($_POST["formSubmit"])) {
	$state = 0;
	// For every check box there is a corresponding hidden control.
	// Therefore, iterating through the posted values gives two entries
	// per checked box (one for the box and one for hidden control, but
	// only one entry per unchecked box (just for the hidden control).
	// this loop identifies whether a box is checked and updates database
	// accordingly
    foreach($_POST['checkbox'] as $chkval){
    	switch ($state){
		// after checked box
		case 0;
			$state = 1;
        	break;

		// after unchecked box
         case 1;
			if ($prev_value == $chkval){
				update_dataset($chkval,1);
				$state = 0;
			} else {
				update_dataset($prev_value,0);
				$state = 1;
			}
            break;
		}
		$prev_value = $chkval;
	}
	// update last uncheck box
	if ($state == 1 ){
		update_dataset($prev_value,0);
	}
}


//Display results tables
If ($band != 0){

	// LNA - Actual Readings
	band_results_table($FE_Config,$band,$Data_Status, 1);

	// SIS – Actual Readings
	band_results_table($FE_Config,$band,$Data_Status, 3);

	// Temperature Sensors – Actual Readings
	band_results_table($FE_Config,$band,$Data_Status, 2);

	// WCA AMC Monitors
	band_results_table($FE_Config,$band,$Data_Status, 12);

	// WCA PA Monitors
	band_results_table($FE_Config,$band,$Data_Status, 13);

	// WCA PLL Monitors
	band_results_table($FE_Config,$band,$Data_Status, 14);

	// Nominal IF power levels
	band_results_table($FE_Config,$band,$Data_Status, 6);

	// Y-factor
	band_results_table($FE_Config,$band,$Data_Status, 15);

	// I-V Curve
	band_results_table($FE_Config,$band,$Data_Status, 39);

} else {

	// CPDS monitors
	results_table($FE_Config,$Data_Status, 24);

	// FLOOG Total Power
	results_table($FE_Config,$Data_Status, 5);

	// IF switch temperature sensors
	results_table($FE_Config,$Data_Status, 10);

	// LO Photonic Receiver Monitor Data
	results_table($FE_Config,$Data_Status, 8);

	// Photomixer Monitor Data
	results_table($FE_Config,$Data_Status, 9);

	// Cryo-cooler Temperatures
	results_table($FE_Config,$Data_Status, 4);

}

?>

</form>
</div></body>
</html>