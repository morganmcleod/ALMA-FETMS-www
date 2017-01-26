<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link rel="stylesheet" type="text/css" href="Cartstyle.css">
<link rel="stylesheet" type="text/css" href="tables.css">
<link rel="stylesheet" type="text/css" href="buttons.css">
<link href="images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
<title>Add Notes</title>
</head>
<body>
<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.dboperations.php');
require_once($site_classes . '/class.fecomponent.php');

$keyId = $_REQUEST['id'];  //keyId of FE_Components table
$fc = $_REQUEST['fc'];

$c = new FEComponent();
$c->Initialize_FEComponent($keyId,$fc);
//This is used by the header to display a link back to the FE page
$feconfig = $c->FEConfig;
$fesn=$c->FESN;

$title = $c->ComponentType->GetValue('Description');
if ($c->GetValue('Band') > 0){
	$title.= " Band" . $c->GetValue('Band');
}
$title.= " SN" . $c->GetValue('SN');

include "header.php";

if (isset($_REQUEST['Updated_By'])){
	$newlink = $_REQUEST['lnk_Data'];
	if ($newlink == ''){
		$newlink = ' ';
	}
	$newnotes = $_REQUEST['Notes'];
	if ($newnotes == ''){
		$newnotes = ' ';
	}

	$dbops = new DBOperations();
	$dbops->UpdateStatusLocationAndNotes_Component($fc, $_REQUEST['fkStatusType'], $_REQUEST['fkLocationNames'],$newnotes,$c->keyId, $_REQUEST['Updated_By'],$newlink);
	echo "<meta http-equiv='Refresh' content='0.1;url=ShowComponents.php?conf=$c->keyId&fc=$fc'>";
}


if (!isset($_REQUEST['Updated_By'])){
echo "
<div id='wrap2' >
<form action='".$_SERVER["PHP_SELF"]."' method='post' name='Submit' id='Submit'>
	<div id='sidebar2' >

			";





echo "
	</div>
<div id='maincontent'>
		<input type='hidden' name='fc' id='facility' value='$fc'>
		<input type='hidden' name='id' id='facility' value='$c->keyId'>
		<div style='width:500px'>
			<table id = 'table5'>
				<tr class='alt'>
					<th colspan = '2'>
						Status, Location And Notes
					</th>
				</tr>
				<tr class='alt3'>
					<th align = 'right'>
						Notes:
					</th>
					<td align>
						<textarea rows=3 cols=40 name='Notes' id='Notes'></textarea>
					</td>
				</tr>
				<tr>
					<th>
						Link:
					</th>
					<td>
						<textarea cols='40' rows='2' name='lnk_Data' id='lnk_Data'>".$c->sln->GetValue('lnk_Data')."</textarea>
					</td>
				</tr>



				<tr>
					<th align = 'right'>
						Updated By:
					</th>
				<td>";

					echo"
					<select name='Updated_By' id='Updated_By'>
						<option value='' selected = 'selected'></option>";
						$q = "SELECT Initials FROM Users
							  ORDER BY Initials ASC;";
						$r = @mysql_query($q,$db);
						while($row = @mysql_fetch_Array($r)){
							if ($row[0] == $c->sln->GetValue('Updated_By')){
								echo "<option value='$row[0]' selected = 'selected'>$row[0]</option>";
							}
							else{
								echo "<option value='$row[0]'>$row[0]</option>";
							}
						}echo "
					</select>
				</td>
				</tr>
				<tr>
					<th align = 'right'>
						<label for='status'>
							Status:
						</label>
					</th>
					<td>";

					echo"
					<select name='fkStatusType' id='fkStatusType'>";
						$q = "SELECT keyStatusType,Status FROM StatusTypes
							  ORDER BY keyStatusType ASC;";
						$r = @mysql_query($q,$db);
						while($row = @mysql_fetch_Array($r)){
							if ($row[0] == $c->sln->GetValue('fkStatusType')){
								echo "<option value='$row[0]' selected = 'selected'>$row[1]</option>";
							}
							else{
								echo "<option value='$row[0]'>$row[1]</option>";
							}
						}echo "
					</select>";


					echo "
					</td>

					</tr>
				<tr >
					<th align = 'right'>
						<label for='location'>
							Location:
						</label>
					</td>";
					echo"
					<td>
					<select name='fkLocationNames' id='fkLocationNames'>";
						$q = "SELECT keyId,Description FROM Locations
							  ORDER BY Description ASC;";
						$r = @mysql_query($q,$db);
						while($row = @mysql_fetch_Array($r)){
							if ($row[0] == $c->sln->GetValue('fkLocationNames')){
								echo "<option value='$row[0]' selected = 'selected'>$row[1]</option>";
							}
							else{
								echo "<option value='$row[0]'>$row[1]</option>";
							}
						}echo "
					</select>";


			  echo "</td>
				</tr>";

		echo "
			</table>
			<div style='padding-left:20px;padding-top:20px'>
				<input type='submit' name='submit' class='button blue2 biground' value = 'Submit' style='width:120px'>
				<a style='width:90px' href='ShowComponents.php?conf=$c->keyId&fc=".$c->GetValue('keyFacility') . "' class='button blue2 biground'>
				<span style='width:130px'>Cancel</span></a>
			</div>
	</div>
</div>
</div>
</form>";

}


include "footer.php";


?>
</body>
</html>
