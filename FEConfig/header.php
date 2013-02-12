<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html401/loose.dtd">
<html>
<head>
<link rel="stylesheet" type="text/css" href="Cartstyle.css"/>
<link rel="stylesheet" type="text/css" href="buttons.css">
</head>
<body>
<div id="header">
    <div id="header_inside">
        <h1><span>
<?php
        echo $title;
?>
        </span></h1>
        <div class="menu_nav">
        <table><tr>
            <td><a href="FEHome.php" class="button gray biground"><span>Home</span>
            </a></td>
<?php
        if (isset($feconfig) && $feconfig != '') {
?>
            <td><a href="../FEConfig/ShowFEConfig.php?key=<?php echo $feconfig; ?>&fc=<?php echo $fc; ?>" class="button gray biground">
            <span>Front End <?php echo $fesn; ?></span></a></td>
<?php
        }
        if ($componentId != '') {
?>
            <td><a href="../FEConfig/ShowComponents.php?conf=<?php echo $componentId; ?>&fc=<?php echo $fc; ?>" class="button gray biground">
            <span>Component Record</span></a></td>
<?php
        }
?>
            <td><a href="https://safe.nrao.edu/php/ntc/bugs/AddNewBug.php?modulekey=61" target = "blank" class="button gray biground">
            <span>Bugs</span></a></td>
        </tr></table>
        </div>
    </div>
</div>
<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_dbConnect);
if ($_SERVER['SERVER_NAME'] == 'webtest.cv.nrao.edu') {
    if ($dbname == 'alma_feic') {
        echo "<font size = '+3' color = '#ff0000'><h><b>
        WARNING- Using production alma_feic database! Be careful!
        </b></h></font>";
    } else {
        echo "<font size = '+2' color = '#ff0000'><h><b>
        On webtest.cv.nrao.edu using database $dbname
        </b></h></font>";
    }
}
?>
</body>
</html>
