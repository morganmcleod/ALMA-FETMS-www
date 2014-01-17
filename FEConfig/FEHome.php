<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link href="images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
<link rel="stylesheet" type="text/css" href="Cartstyle.css">
<link rel="stylesheet" type="text/css" href="buttons.css">
<link type="text/css" href="../ext/resources/css/ext-all.css" media="screen" rel="Stylesheet" />
<script src="../ext/adapter/ext/ext-base.js" type="text/javascript"></script>
<script src="../ext/ext-all.js" type="text/javascript"></script>
<script type="text/javascript" src="Ext.ux.plugins.HeaderButtons.js"></script>
<script src="dbGrid.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="headerbuttons.css">

<title>FrontEnd Home</title>
</head>

<div id = "wrap">
<body onload="javascript:creategrid(100,1);" style="background-color: #19475E; ">

<?php
    $title="Front Ends";
    include "header.php";
    $where = $_SERVER["PHP_SELF"];
    //$where = '';
?>

<form action='<?php echo $where ?>' method='post' name='fehome' id='fehome'>
<div style= 'padding-left: 2em; padding-top: 1em; width:1100px; background-color: #19475E;'>

<div id="toolbar" style="margin-top:10px;"></div>
<div id="db-grid"></div>

</form>

</body>
</div>

<?php
    include "footer.php";
?>

</html>
