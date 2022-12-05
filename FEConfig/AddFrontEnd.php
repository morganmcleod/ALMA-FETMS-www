<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <link rel="stylesheet" type="text/css" href="Cartstyle.css">
    <link rel="stylesheet" type="text/css" href="tables.css">
    <link rel="stylesheet" type="text/css" href="buttons.css">
    <link href="images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
    <title>Add FrontEnd</title>
</head>
<div id="wrap">

    <body style="background-color: #495975">
        <?php

        require_once(dirname(__FILE__) . '/../SiteConfig.php');
        require_once($site_dbConnect);
        $dbconnection = site_getDbConnection();

        require_once('HelperFunctions.php');
        require_once('functions.php');
        require_once('jsFunctions.php');
        require_once('dbGetQueries.php');
        require_once('dbInsertQueries.php');


        $title = "Front Ends";
        include "header.php";


        $getqueries = new dbGetQueries;
        $addqueries = new dbInsertQueries;
        $status_block = "";
        $location_block = "";

        $status_query = $getqueries->getStatusLocation('StatusTypes');
        while ($stat_rs = mysqli_fetch_array($status_query)) {
            $name = $stat_rs['Status'];
            $keystat = $stat_rs['keyStatusType'];
            $status_block .= "<option value=\"$keystat\">$name</option>";
        }

        $location_query = $getqueries->getStatusLocation('Locations');
        while ($loc_rs = mysqli_fetch_array($location_query)) {
            $location = $loc_rs['Description'];
            $keyloc = $loc_rs['keyId'];
            $location_block .= "<option value=\"$keyloc\">$location</option>";
        }

        //Check first to see if Front End record already exists
        $q = "SELECT DefaultFacility FROM DatabaseDefaults";
        $r = mysqli_query($dbconnection, $q);
        $facility = ADAPT_mysqli_result($r, 0, 0);

        if (isset($_POST['submit'])) {
            $sn = $_POST['sn'];
            $esn = $_POST['esn0'];
            $datalink = $_POST['lnk1'];
            $notes = $_POST['notes'];
            $updatedby = $_POST['updatedby'];
            $status = $_POST['status'];
            $location = $_POST['location'];
            $facility = $_POST['facility'];
            $Description = $_POST['Description'];

            $frontend_array = array("sn" => $sn, "esn" => $esn, "link" => $datalink, "facility" => $facility, "description" => $Description);

            //Check first to see if Front End record already exists
            $q = "SELECT keyFrontEnds FROM Front_Ends
          WHERE keyFacility = $facility
          AND SN = $sn;";
            $r = mysqli_query($dbconnection, $q);
            $keyFE = ADAPT_mysqli_result($r, 0, 0);
            if ($keyFE != '') {
                Warn("Front End SN $sn already exists. Record not saved.");
            }

            if ($keyFE == '') {
                $addfrontend = $addqueries->InsertIntoFrontEnds($frontend_array);

                $maxkey = $getqueries->getMaxKey($sn, $facility);

                $insert_feconfig_query = $addqueries->insertIntoFEConfig($maxkey, $sn, $facility);

                if ($insert_feconfig_query) {
                    $MaxFEConfigKey = $getqueries->getMaxKeyFE_Config($maxkey);

                    $insertNotes = $addqueries->insertIntoStatLocAndNotes($notes, $updatedby, $status, $location, $datalink, $MaxFEConfigKey, $facility);

                    if ($insertNotes) {
                        echo "<script type='text/javascript' language='JavaScript'>location.href='ShowFEConfig.php?key=$MaxFEConfigKey&fc=$facility'</script>";
                    }
                }
            }
        }

        ?>



        <form action='<?php echo ($_SERVER["PHP_SELF"]) ?>' method='post' name="FEUpdate" id="FEUpdate" onsubmit="return FormValidate(1)">
            <div id="sidebar2">
            </div>



            <div id="maincontent">

                <input type=hidden name="facility" id="facility" value=<?php echo $facility; ?>>
                <div style="width:500px">
                    <table id="table5">
                        <tr class="alt">
                            <th colspan="2">Enter new Front End Record</th>
                        </tr>
                        <tr>
                            <th width="100px" align="right">FrontEnd SN:</td>
                            <td align="left"><input type=text name='sn' id='sn'>
                            </td>
                        </tr>
                        <tr>
                            <th align="right">CAN SN:</td>
                            <td><input type=text name='esn0' id='esn0'></input></td>
                        </tr>
                        <tr>
                            <th align="right">Docs:</td>
                            <td><textarea cols=40 rows=2 name="lnk1" id="lnk1"></textarea></td>
                        </tr>
                        <tr>
                            <th align="right">Description:</td>
                            <td><textarea cols=40 rows=2 name="Description" id="Description"></textarea></td>
                        </tr>
                        <tr>
                            <th align="right">Notes:</td>
                            <td><textarea rows=3 cols=40 name='notes' id='notes'></textarea></td>
                        </tr>

                        <?php
                        echo "
            <tr>
                    <th align = 'right'>
                        Updated By:
                    </th>
                <td>";

                        echo "
                    <select name='updatedby' id='updatedby'>
                        <option value='' selected = 'selected'></option>
                    ";
                        $q = "SELECT Initials FROM Users
                              ORDER BY Initials ASC;";
                        $r = mysqli_query($dbconnection, $q);
                        while ($row = mysqli_fetch_array($r)) {
                            echo "<option value='$row[0]'>$row[0]</option>";
                        }
                        echo "
                    </select>
                </td>
                </tr>";

                        ?>

                        <tr>
                            <th align="right"><label for='status'>Status:</label></td>
                            <td><select name='status' id='status'>
                                    <option></option><?php echo "$status_block"; ?>
                                </select></td>
                        </tr>
                        <tr>
                            <th align="right"><label for='location'>Location:</label></td>
                            <td><select name='location' id='location'>
                                    <option></option>
                                    <?php echo "$location_block"; ?>
                                </select></td>
                        </tr>
                    </table>

                    <div style='padding-left:20px;padding-top:20px'>
                        <input type=submit name="submit" class="button blue2 bigrounded value=" Submit" style="width:120px">
                        <input type=reset name="reset" value="Reset" class="button blue2 bigrounded style=" width:120px">
                    </div>




                </div>
            </div>

</div>


</form>


</body>
</div>
<?php
include "footer.php";
?>

</html>