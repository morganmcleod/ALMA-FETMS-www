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
    require_once($site_classes . '/class.logger.php');
    require_once($site_dbConnect);
    $dbconnection = site_getDbConnection();

    // $l = new Logger('SLNBROKEN.txt');
    // $l->WritELogFile('test');

    $keyId = $_REQUEST['id'];  //keyId of FE
    $fc = $_REQUEST['fc'];

    $fe = new FrontEnd($keyId, $fc, FrontEnd::INIT_SLN);
    //This is used by the header to display a link back to the FE page
    $feconfig = $fe->feconfig->keyId;
    $fesn = $fe->SN;

    $title = "Front End SN $fesn";
    include "header.php";

    if (isset($_REQUEST['Updated_By'])) {

        if (strlen($_REQUEST['Updated_By']) < 1) {
            echo "<script type='text/javascript'>alert('Please identify yourself in the Updated By selector');</script>";
        }

        if (strlen($_REQUEST['Updated_By']) > 0) {
            $dbops = new DBOperations();

            $newlink = $_REQUEST['lnk_Data'];
            if ($_REQUEST['lnk_Data'] == '') {
                $newlink = " ";
            }

            $Updater = $_REQUEST['Updated_By'];

            $HasChanged = 0;
            $ChangedNotes = "";

            if (strlen($_REQUEST['Notes']) > 1) {
                $HasChanged = 1;
            }
            if ($_REQUEST['fkLocationNames'] != $fe->fesln->fkLocationNames) {
                $HasChanged = 2;
                $ChangedNotes .= " Location,";
            }
            if ($_REQUEST['fkStatusType'] != $fe->fesln->fkStatusType) {
                $HasChanged = 2;
                $ChangedNotes .= " Status,";
            }
            if ($_REQUEST['lnk_Data'] != $fe->fesln->lnk_Data) {
                $len1 = strlen($fe->fesln->lnk_Data);
                $len2 = strlen($_REQUEST['lnk_Data']);

                if (($len1 + $len2) >= 4) {
                    $HasChanged = 2;
                    $ChangedNotes .= " Link.";
                }
            }

            if ($HasChanged > 1) {
                $ChangedNotes = "$Updater changed " . $ChangedNotes;
                $ChangedNotes = substr($ChangedNotes, 0, strlen($ChangedNotes) - 1) . ".";
            }

            if ($_REQUEST['Notes'] != '') {
                $ChangedNotes = mysqli_real_escape_string($dbconnection, $_REQUEST['Notes']) . "\r\n" . $ChangedNotes;
            }



            if ($HasChanged == 0) {
                echo "<script type='text/javascript'>alert('Nothing was changed. Record not updated.');</script>";
            }
            if ($HasChanged > 0) {
                $NewFEConfig = $dbops->UpdateStatusLocationAndNotes_FE($fc, $_REQUEST['fkStatusType'], $_REQUEST['fkLocationNames'], $ChangedNotes, $fe->feconfig->keyId, $fe->feconfig->keyId, $_REQUEST['Updated_By'], $newlink);
            }

            echo "<meta http-equiv='Refresh' content='0.1;url=ShowFEConfig.php?key=$NewFEConfig&fc=$fc'>";
        }
    }



    echo "
<div id='wrap2' >";

    echo "
<form action='" . $_SERVER["PHP_SELF"] . "' method='post' name='Submit' id='Submit'>
    <div id='sidebar2' >";




    echo "
    </div>
<div id='maincontent'>
        <input type='hidden' name='fc' id='facility' value='$fc'>
        <input type='hidden' name='id' id='facility' value='$fe->keyId'>
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
                        <textarea cols='40' rows='2' name='lnk_Data' id='lnk_Data'></textarea>
                    </td>
                </tr>



                <tr>
                    <th align = 'right'>
                        Updated By:
                    </th>
                <td>";

    echo "
                    <select name='Updated_By' id='Updated_By'>";
    echo "<option value='' selected = 'selected'></option>";
    $q = "SELECT Initials FROM Users
                              ORDER BY Initials ASC;";
    $r = mysqli_query($dbconnection, $q);
    while ($row = mysqli_fetch_array($r)) {

        echo "<option value='$row[0]'>$row[0]</option>";
    }
    echo "
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

    echo "
                    <select name='fkStatusType' id='fkStatusType'>";
    $q = "SELECT keyStatusType,Status FROM StatusTypes
                              ORDER BY keyStatusType ASC;";
    $r = mysqli_query($dbconnection, $q);
    while ($row = mysqli_fetch_array($r)) {

        //                             $l->WriteLogFile("Status Option: $row[0]");
        //                             $l->WriteLogFile("Current status= " . $fe->fesln->keyId);

        if ($row[0] == $fe->fesln->fkStatusType) {
            echo "<option value='$row[0]' selected = 'selected'>$row[1]</option>";
        } else {
            echo "<option value='$row[0]'>$row[1]</option>";
        }
    }
    echo "
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
    echo "
                    <td>
                    <select name='fkLocationNames' id='fkLocationNames'>";
    $q = "SELECT keyId,Description FROM Locations
                              ORDER BY Description ASC;";
    $r = mysqli_query($dbconnection, $q);
    while ($row = mysqli_fetch_array($r)) {
        if ($row[0] == $fe->fesln->fkLocationNames) {
            echo "<option value='$row[0]' selected = 'selected'>$row[1]</option>";
        } else {
            echo "<option value='$row[0]'>$row[1]</option>";
        }
    }
    echo "
                    </select>";


    echo "</td>
                </tr>";

    echo "
            </table>

            <div style='padding-left:20px;padding-top:20px'>
                <input type='submit' name='submit' class='button blue2 bigrounded value = 'Submit' style='width:120px'>
                <a style='width:90px' href='ShowFEConfig.php?key=$feconfig&fc=$fc' class='button blue2 bigrounded'
                <span style='width:130px'>Cancel</span></a>
            </div>


        </div>
    </div>
</div>
</div></form>";

    //}

    echo "</div>";


    include "footer.php";


    ?>
</body>

</html>