<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link href="images/favicon.ico" rel="shortcut icon" type="image/x-icon">
<link rel="stylesheet" type="text/css" href="Cartstyle.css">
<link rel="stylesheet" type="text/css" href="buttons.css">
<link type="text/css" href="../ext/resources/css/ext-all.css" media="screen" rel="Stylesheet">
<script src="../ext/adapter/ext/ext-base.js" type="text/javascript"></script>
<script src="../ext/ext-all.js" type="text/javascript"></script>
<script type="text/javascript" src="Ext.ux.plugins.HeaderButtons.js"></script>
<script src="dbGrid.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="headerbuttons.css">

<?php
    require_once(dirname(__FILE__) . '/../SiteConfig.php');
    require_once(site_get_config_main());
    $pageTitle = "FrontEnd Home";
    $pageHeader = "Front Ends";
    $pageComponent = "100";
    if ($FETMS_CCA_MODE) {
        $pageTitle = "CCAs Home";
        $pageHeader = "CCAs";
        $pageComponent = "20";
    }
    echo "<title>$pageTitle</title>";

    echo "</head>";

    echo "<body onload='javascript:creategrid(" . $pageComponent . ", 1);' style='background-color: #19475E; '>";
    echo "<div id = 'wrap'>";

    $title=$pageHeader;
    include "header.php";
    $where = $_SERVER["PHP_SELF"];
?>

<form action='<?php echo $where ?>' method='post' name='fehome' id='fehome'>
<div style= 'padding-left: 2em; padding-top: 1em; width:900px; background-color: #19475E;'>

<div id="toolbar" style="margin-top:10px;"></div>
<div id="db-grid"></div>

</div>
</form>
<?php
    echo "</div>";
    include "footer.php";
?>
</body>
</html>
