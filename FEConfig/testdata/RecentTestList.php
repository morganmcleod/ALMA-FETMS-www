<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link href="../images/favicon.ico" rel="shortcut icon" type="image/x-icon">
<link rel="stylesheet" type="text/css" href="../Cartstyle.css">
<link rel="stylesheet" type="text/css" href="../buttons.css">
<link rel="stylesheet" type="text/css" href="../../ext4/resources/css/ext-all.css">
<script src="../../ext4/ext-all.js" type="text/javascript"></script>
<script type="text/javascript" language="javscript" src="dbGridRecentTestList.js"></script>
<title>Recent Tests</title>
</head>

<body onload=" var val=document.getElementById('search').value; creategrid(val,1);" style="background-color: #285B75">

<?php
    $title="Test Data";
    include "../header.php";
?>

<div id='wrap'>
    <div id="maincontent4">

<?php //Data Status selector
    echo "
    <div style= 'padding-left: 2em; '>
    <a class='fancy_button'><span style='background-color: #697891;'>";
    echo "<font color='#ffffff'>Select Data Status</font><br>
    <select name='search' id='search' onChange='creategrid(this.value,1);' >
    <option value= 'All'>All</option>
    ";

    $q = "SELECT keyId,Description FROM DataStatus ORDER BY Description ASC;";
    $r = @mysql_query($q,$db) or die ('Could not execute query.');

    while ($row=@mysql_fetch_array($r)){
        $selected = '';
        if ($row[0] == 3){
            $selected = "selected";
        }
        echo "<option value = '$row[0]' $selected>$row[1]</option>";
    }

    echo "
    </select></span></a></div>";

    //Test type selector
    echo "
    <div style= 'padding-left: 0em; '>
    <a class='fancy_button'><span style='background-color: #697891;'>";
    echo "<font color='#ffffff'>Select Test Type</font><br>
    <select name='search2' id='search' onChange='creategrid(this.value,2);' >
    <option value= 'All' selected = 'selected'>All</option>

    ";
    $q = "SELECT keyId,Description FROM TestData_Types ORDER BY Description ASC;";
    $r = @mysql_query($q,$db) or die ('Could not execute query.');

    while ($row=@mysql_fetch_array($r)){
        echo "<option value = '$row[0]'>$row[1]</option>";
    }

    echo "</select></span></a></div><br><br><br>";
?>
    <br><br>
    <div id="db-grid"  style = "width:1100px;padding-left: 2em; "></div>';
    </div>
</div>
<?php
include('../footer.php');
?>
</body>
</html>

