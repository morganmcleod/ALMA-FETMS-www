<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <link rel="stylesheet" type="text/css" href="Cartstyle.css">
    <link rel="stylesheet" type="text/css" href="tables.css">
    <link rel="stylesheet" type="text/css" href="buttons.css">
    <link href="images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
    <title>Edit Front End</title>
</head>

<body>
    <div id='wrap'>
        <?php
        require_once(dirname(__FILE__) . '/../SiteConfig.php');
        require_once($site_classes . '/class.dboperations.php');
        require_once($site_classes . '/class.frontend.php');
        require_once($site_dbConnect);
        $dbconnection = site_getDbConnection();

        $keyId = $_REQUEST['id'];  //keyId of FE_Components table
        $fc = $_REQUEST['fc'];

        $fe = new FrontEnd(NULL, $fc, FrontEnd::INIT_NONE, $keyId);
        //This is used by the header to display a link back to the FE page
        $feconfig = $fe->feconfig->keyId;
        $fesn = $fe->SN;

        $title = "Front End SN $fesn";

        include "header.php";

        if (isset($_REQUEST['Updated_By'])) {
            $ESN_old =  $fe->ESN;
            $desc_old = $fe->Description;
            $Docs_old = $fe->Docs;

            $fe->ESN = $_REQUEST['ESN'];
            $fe->Docs = $_REQUEST['Docs'];
            $fe->Description = $_REQUEST['Description'];


            $Notes = $_REQUEST['Updated_By'] . ' changed ';
            $changed = 0;
            if ($ESN_old != $fe->ESN) {
                $Notes .= ' ESN,';
                $changed = 1;
            }
            if ($Docs_old != $fe->Docs) {
                $Notes .= ' Docs,';
                $changed = 1;
            }
            if ($desc_old != $fe->Description) {
                $Notes .= ' Description';
                $changed = 1;
            }
            if (substr($Notes, -1, 1) == ",") {
                $Notes = substr($Notes, 0, strlen($Notes) - 1);
            }
            $Notes .= ".";

            if ($changed == 1) {
                $fe->Update();
                $dbops = new DBOperations();
                $NewFEConfig = $dbops->UpdateStatusLocationAndNotes_FE($fc, '', '', $Notes, $feconfig, $feconfig, $_REQUEST['Updated_By'], '');

                echo "<meta http-equiv='Refresh' content='0.1;url=ShowFEConfig.php?key=$feconfig&fc=$fc'>";
            }
            if ($changed == 0) {
                echo "<script type='text/javascript'>alert('Nothing was changed in this record.');</script>";
            }
        }



        echo "

<form action='" . $_SERVER["PHP_SELF"] . "' method='post' name='Submit' id='Submit'>
    <div id='sidebar2' >

    </div>
<div id='maincontent'>
        <input type='hidden' name='fc' id='facility' value='$fc'>
        <input type='hidden' name='id' id='facility' value='$fe->keyId'>
        <div style='width:500px'>
        <font size='+2'>
            <table id = 'table5'>
                <tr class='alt'>
                    <th colspan = '2'>
                        Front End $fesn Information
                    </th>
                </tr>

                <tr>
                    <th>
                        CAN SN
                    </th>
                    <td>
                        <input type='text' size='20' name='ESN' value={$fe->ESN}>
                    </td>
                </tr>
                <tr>


                <tr class='alt3'>
                    <th align = 'right'>
                        Docs:
                    </th>
                    <td align>
                    <textarea rows='3' cols='40' name='Docs' id='Docs'>{$fe->Docs}</textarea>
                    </td>
                </tr>
                <tr class='alt3'>
                    <th align = 'right'>
                        Description:
                    </th>
                    <td align>
                    <textarea rows='3' cols='40' name='Description' id='Description'>{$fe->Description}</textarea>
                    </td>
                </tr>
                <tr>
                    <th align = 'right'>
                        Updated By:
                    </th>
                <td>";

        echo "
                    <select name='Updated_By' id='Updated_By'>
                    <option value='' selected='selected'></option>";
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


        echo "
            </table></font>

            <div style='padding-left:20px;padding-top:20px'>
                <input type='submit' name='submit' class='button blue2 bigrounded value = 'Submit' style='width:120px'>
                <input type='hidden' name='id' value='$feconfig'>
                    <a style='width:90px' href='ShowFEConfig.php?key=" . $feconfig . "&fc=$fc' class='button blue2 bigrounded'
                    <span style='width:130px'>Cancel</span></a>
            </div>
        </div>
    </div>
</div>

</form>";

        include "footer.php";

        ?>
    </div>
</body>

</html>