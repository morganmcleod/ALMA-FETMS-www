<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link rel="stylesheet" type="text/css" href="Cartstyle.css">
<link rel="stylesheet" type="text/css" href="buttons.css">
<link type="text/css" href="../ext/resources/css/ext-all.css" media="screen" rel="Stylesheet" />
<script src="../ext/adapter/ext/ext-base.js" type="text/javascript"></script>
<script src="../ext/ext-all.js" type="text/javascript"></script>
<script src="../ext/examples/simple-widgets/qtips.js" type="text/javascript"></script>
<script src="dbGridRecentTestList.js" type="text/javascript"></script>

<link href="images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
<title>FrontEnd Home</title>
</head>
<div id='wrap'>
<body onload=" var val=document.getElementById('search').value; creategrid(val);" style="background-color: #495975">
<?

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);

$title="Test Data";
include "header.php";

?>
<div id="maincontent">
    <div style= 'padding-left: 2em; '>
    <?php
echo "
<div style= 'padding-left: 0em; '>
<a class='fancy_button'><span style='background-color: #697891;'>";
echo "<font color='#ffffff'>Select Data Status</font><br>
<select name='search' id='search' onChange='creategrid(this.value);' >
<option value= 'All'>All</option>

";
$q = "SELECT keyId,Description FROM DataStatus ORDER BY Description ASC;";
$r = @mysql_query($q,$db) or die ('Could not execute query.');

echo "Numrows = " . @mysql_num_rows($r). "<br>";
while ($row=@mysql_fetch_array($r)){
    $selected = '';
    if ($row[0] == 3){
        $selected = "selected";
    }

    echo "<option value = '$row[0]' $selected>$row[1]</option>";
}

echo "
</select></span></a></div><br><br><br>";
?>

        <div style = 'width:800px'>

            <div id="db-grid">
        </div>
    </div>
    </div>
</div>
</body>
</div>
<?php
include('footer.php');
?>
</html>


