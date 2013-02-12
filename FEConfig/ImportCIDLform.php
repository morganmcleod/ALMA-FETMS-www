<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link rel="stylesheet" type="text/css" href="Cartstyle.css">
<link rel="stylesheet" type="text/css" href="tables.css">
<link rel="stylesheet" type="text/css" href="buttons.css">
<link href="images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
<title>Import CIDL</title>
</head>
<div id="wrap">
<body style="background-color: #495975">
<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once('functions.php');
require_once('jsFunctions.php');
require_once('dbGetQueries.php');
require_once('dbInsertQueries.php');
require_once($site_config_main);

$title="Import CIDL";
include('header.php');

$getqueries=new dbGetQueries;
$addqueries=new dbInsertQueries;

$status_query=$getqueries->getStatusLocation(StatusTypes);
while($stat_rs=mysql_fetch_array($status_query))
{
    $name=$stat_rs['Status'];
    $keystat=$stat_rs['keyStatusType'];
    $status_block .= "<option value=\"$keystat\">$name</option>";
}

$location_query=$getqueries->getStatusLocation(Locations);
while($loc_rs=mysql_fetch_array($location_query))
{
    $location=$loc_rs['Description'];
    $keyloc=$loc_rs['keyId'];
    $location_block .= "<option value=\"$keyloc\">$location</option>";
}

$facility=40;        /// TODO: fixme!

?>



<form enctype="multipart/form-data" action='ImportCIDL.php' method='post'">
            <div id="sidebar2" >


</div>

            <div id="maincontent">

            <input type=hidden name="facility" id="facility" value=<?php echo $facility;?>>
            <div style="width:600px">
            <table id = "table5">
            <tr class="alt"><th colspan = "2">CIDL Information</th></tr>
            <?php
            echo "
            <tr>
                    <th align = 'right'>
                        Front End SN:
                    </th>
                <td>";

                    echo"
                    <select name='FEkey' id='FEkey'>
                    ";
                        $q = "SELECT keyFrontEnds, SN FROM Front_Ends
                              ORDER BY SN ASC;";
                        $r = @mysql_query($q,$db);
                        while($row = @mysql_fetch_Array($r)){
                                echo "<option value='$row[0]'>$row[1]</option>";
                        }
                        echo "
                    </select>
                </td>
                </tr>";
                        ?>



            <tr class='alt3'>
                    <th align = 'right'>
                        Notes (for Front End):
                    </th>
                    <td align>
                        <textarea rows=3 cols=40 name='Notes' id='Notes'></textarea>
                    </td>
                </tr>


            <?php
            echo "
            <tr>
                    <th align = 'right'>
                        Updated By:
                    </th>
                <td>";

                    echo"
                    <select name='updatedby' id='updatedby'>
                        <option value='' selected = 'selected'></option>
                    ";
                        $q = "SELECT Initials FROM Users
                              ORDER BY Initials ASC;";
                        $r = @mysql_query($q,$db);
                        while($row = @mysql_fetch_Array($r)){
                                echo "<option value='$row[0]'>$row[0]</option>";
                        }
                        echo "
                    </select>
                </td>
                </tr>";

                ?>

            <tr >
            <th align = "right"><label for='status'>Status:</label></td><td><select name='status' id='status'>
            <option></option><?php echo "$status_block"; ?>
            </select></td></tr>
            <tr >
                <th align = "right"><label for='location'>Location:</label></th>
                <td>
                    <select name='location' id='location'>
                    <option></option>
                    <?php echo "$location_block"; ?>
                    </select>
                </td>
            </tr>
            <tr>
            <th>Spreadsheet</th>
            <td><input name="spreadsheet" type="file" />(*.xls)</td>
            </tr>
            <?php
            /*
            //Leave this feature out unless it is deemed necessary.

            <tr><td colspan='2'>
            If a component record already exists...
            <select name = "updateexisting">
                <option value = "0" selected = "selected">
                    Leave it as it is.
                </option>
                <option value = "1">
                    Update it's values with the information in this spreadsheet.
                </option>

            </select>

            </td></tr>
            */
            ?>


            <tr class = 'alt'><td colspan='2'>
            <a href='files/CIDLTemplate.xls'>Click here for CIDL Template Spreadsheet</a>
            </td></tr>

            </table>

            <div style='padding-left:20px;padding-top:20px'>
                <input type=submit name="submit" class="button blue2 biground" value = "Submit" style="width:120px">
            </div>


            </div>
            </div>

    </div>


</form>


</body>
</div>
<?
Include "footer.php";
?>
</html>