<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <link rel="stylesheet" type="text/css" href="Cartstyle.css">
    <link rel="stylesheet" type="text/css" href="tables.css">
    <link rel="stylesheet" type="text/css" href="buttons.css">
    <link href="images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
    <title>Edit Component</title>
</head>

<body>
    <div id='wrap'>

        <?php
        require_once(dirname(__FILE__) . '/../SiteConfig.php');
        require_once($site_classes . '/class.cca.php');
        require_once($site_classes . '/class.dboperations.php');
        require_once($site_classes . '/class.fecomponent.php');
        require_once($site_classes . '/class.generictable.php');
        require_once($site_classes . '/class.wca.php');
        require_once($site_dbConnect);
        $dbConnection = site_getDbConnection();

        $keyId = $_REQUEST['id'];  //keyId of FE_Components table
        $fc = $_REQUEST['fc'];

        $c = new FEComponent(NULL, $keyId, NULL, $fc);
        //This is used by the header to display a link back to the FE page
        $feconfig = $c->FEConfig;
        $fesn = $c->FESN;

        $title = $c->ComponentType;
        if ($c->Band > 0) {
            $title .= " Band" . $c->Band;
        }
        $title .= " SN" . $c->SN;

        include "header.php";

        $IsDoc = 0;
        $IsWCA = 0;

        switch ($c->fkFE_ComponentType) {
            case 11:
                $IsWCA = 1;
                break;
        }

        if (isset($_REQUEST['Updated_By'])) {
            if ($c->FE_ConfigLink->keyId != '') {
                $Qty_old  = $c->FE_ConfigLink->GetValue('Quantity');
            }
            $SN_old       = $c->SN;
            $ESN1_old     = $c->ESN1;
            $ESN2_old     = $c->ESN2;
            $desc_old     = $c->Description;
            $Link1_old    = $c->Link1;
            $Link2_old    = $c->Link2;
            $DocTitle_old = $c->DocumentTitle;
            $Ctype_old    = $c->fkFE_ComponentType;
            $Ctype_new    = $Ctype_old;

            // Turn off E_NOTICE error reporting for this section because of all tha unguarded $_REQUEST[]s:
            error_reporting($errorReportSettingsNo_E_NOTICE);

            $Link1_new = str_replace("\\\\", "\\", $_REQUEST['Link1']);
            $Link2_new = str_replace("\\\\", "\\", $_REQUEST['Link2']);

            if ($c->FE_ConfigLink->keyId != '') {
                echo $c->FE_ConfigLink->SetValue('Quantity', $_REQUEST['Quantity']);
                $c->FE_ConfigLink->Update();
            }

            $c->SN = $_REQUEST['SN'];
            $c->ESN1 = $_REQUEST['ESN1'];
            $c->ESN2 = $_REQUEST['ESN2'];
            $c->Link1 = $_REQUEST['Link1'];
            $c->Link2 = $_REQUEST['Link2'];
            $c->Description = $_REQUEST['Description'];

            if (isset($_REQUEST['ctype'])) {
                $Ctype_new = $_REQUEST['ctype'];
                $c->fkFE_ComponentType = $_REQUEST['ctype'];
            }

            $Notes = $_REQUEST['Updated_By'] . ' changed ';

            // Turn back on E_NOTICE error reporting:
            error_reporting($errorReportSettingsNormal);

            $changed = 0;
            if ($c->FE_ConfigLink->keyId != '') {
                if ($Qty_old != $c->FE_ConfigLink->GetValue('Quantity')) {
                    $Notes .= ' Quantity,';
                    $changed = 1;
                }
            }
            if ($SN_old != $c->SN) {
                $Notes .= ' SN,';
                $changed = 1;
            }
            if ($ESN1_old != $c->ESN1) {
                $Notes .= ' ESN1,';
                $changed = 1;
            }
            if ($ESN2_old != $c->ESN2) {
                $Notes .= ' ESN2,';
                $changed = 1;
            }
            if ($Link1_old != $Link1_new) {
                $Notes .= ' Link1,';
                $changed = 1;
            }
            if ($Link2_old != $Link2_new) {
                $Notes .= ' Link2,';
                $changed = 1;
            }
            if ($desc_old != $c->Description) {
                $Notes .= ' Description,';
                $changed = 1;
            }
            if ($DocTitle_old != $c->DocumentTitle) {
                $Notes .= ' Doc. Title,';
                $changed = 1;
            }
            if ($Ctype_old != $Ctype_new) {
                $Notes .= ' Doc. type,';
                $changed = 1;
            }

            if ($IsWCA) {
                $wca = new WCA($c->keyId, $c->keyFacility, WCA::INIT_ALL);
                //Create records in WCAs if one doesn't exist.
                if ($wca->_WCAs->keyId == '') {
                    $wca->_WCAs = GenericTable::NewRecord('WCAs', 'keyId', $wca->keyFacility, 'fkFacility');
                    $wca->_WCAs->fkFE_Component = $wca->keyId;
                }


                $FloYIG_old = $wca->_WCAs->FloYIG;
                $FhiYIG_old = $wca->_WCAs->FhiYIG;
                $VG0_old     = $wca->_WCAs->VG0;
                $VG1_old     = $wca->_WCAs->VG1;

                $wca->_WCAs->FloYIG = $_REQUEST['FloYIG'];
                $wca->_WCAs->FhiYIG = $_REQUEST['FhiYIG'];
                $wca->_WCAs->VG0 =   $_REQUEST['VG0'];
                $wca->_WCAs->VG1 =   $_REQUEST['VG1'];
                $wca->_WCAs->Update();

                if ($FloYIG_old != $wca->_WCAs->FloYIG) {
                    $Notes .= ' FloYIG,';
                    $changed = 1;
                }
                if ($FhiYIG_old != $wca->_WCAs->FhiYIG) {
                    $Notes .= ' FhiYIG,';
                    $changed = 1;
                }
                if ($VG0_old != $wca->_WCAs->VG0) {
                    $Notes .= ' VG0,';
                    $changed = 1;
                }
                if ($VG1_old != $wca->_WCAs->VG1) {
                    $Notes .= ' VG1,';
                    $changed = 1;
                }
            }

            if (substr($Notes, -1, 1) == ",") {
                $Notes = substr($Notes, 0, strlen($Notes) - 1);
            }
            $Notes .= ".";

            if ($changed == 1) {
                $c->Update();
                $dbops = new DBOperations();
                $dbops->UpdateStatusLocationAndNotes_Component($fc, '', '', $Notes, $c->keyId, $_REQUEST['Updated_By'], '');
                echo "<meta http-equiv='Refresh' content='0.1;url=ShowComponents.php?conf=$c->keyId&fc=$fc'>";
            }
            if ($changed == 0) {
                echo "<script type='text/javascript'>alert('Nothing was changed in this record.');</script>";
            }
        }

        echo "<form action='" . $_SERVER["PHP_SELF"] . "' method='post' name='Submit' id='Submit'>
    <div id='sidebar2'></div>
    <div id='maincontent'>
        <input type='hidden' name='fc' id='facility' value='$fc'>
        <input type='hidden' name='id' id='facility' value='$c->keyId'>
        <div style='width:500px'>
            <table id = 'table5'>
                <tr class='alt'>
                    <th colspan = '2'>
                        Component Information
                    </th>
                </tr>";

        echo "          <tr>
                    <th>
                        Serial Number
                    </th>
                    <td>
                        <input type='text' size='20' name='SN' value = '" . $c->SN . "'>
                    </td>
                </tr>";

        echo "          <th>
                    Component Type
                </th>
            <td>";

        echo $c->ComponentType;

        echo "      </td></tr>";

        echo "      <tr>
                <th>
                    ESN1
                </th>
                <td>
                    <input type='text' size='20' name='ESN1' value = '" . $c->ESN1 . "'>
                </td>
            </tr>
            <tr>
                <th>
                    ESN2
                </th>
                <td>
                    <input type='text' size='20' name='ESN2' value='" . $c->ESN2 . "'>
                </td>
            </tr>
            <tr>
                <th>
                    Quantity
                </th>
                <td>";

        if (isset($c->FE_ConfigLink) && $c->FE_ConfigLink->keyId != '') {
            echo "<input type='text' size='3' name='Quantity' value='" . $c->FE_ConfigLink->GetValue('Quantity') . "'>";
        } else {
            echo 'Must be in front end to edit quantity';
        }

        echo "          </td></tr>";

        echo "          <tr class='alt3'>
                    <th align = 'right'>
                        Link1 (CIDL):
                    </th>
                    <td align>
                        <textarea rows='3' cols='40' name='Link2' id='Link2'>" . $c->Link2 . "</textarea>
                    </td>
                </tr>

                <tr class='alt3'>
                    <th align = 'right'>
                        Link2 (SICL):
                    </th>
                    <td align>
                        <textarea rows='3' cols='40' name='Link1' id='Link1'>" . $c->Link1 . "</textarea>
                    </td>
                </tr>";

        echo "          <tr class='alt3'>
                    <th align = 'right'>
                        Description:
                    </th>
                    <td align>
                        <textarea rows='3' cols='40' name='Description' id='Description'>" . $c->Description . "</textarea>
                    </td>
                </tr>";

        if ($IsWCA == 1) {
            $wca = new WCA($c->keyId, $c->keyFacility, WCA::INIT_ALL);
            echo "      <tr>
                    <th>
                        YIG LOW (GHz)
                    </th>
                    <td>
                        <input type='text' size='6' name='FloYIG' value=" . $wca->_WCAs->FloYIG . ">
                    </td>
                </tr>
                <tr>
                    <th>
                        YIG HIGH (GHz)
                    </th>
                    <td>
                        <input type='text' size='6' name='FhiYIG' value=" . $wca->_WCAs->FhiYIG . ">
                    </td>
                </tr>
                <tr>
                    <th>
                        VG0
                    </th>
                    <td>
                        <input type='text' size='6' name='VG0' value=" . $wca->_WCAs->VG0 . ">
                    </td>
                </tr>
                <tr>
                    <th>
                        VG1
                    </th>
                    <td>
                        <input type='text' size='6' name='VG1' value=" . $wca->_WCAs->VG1 . ">
                    </td>
                </tr>";
        }


        echo "          <tr><th>Updated By</th>
                    <td>
                    <select name='Updated_By' id='Updated_By'>
                        <option value=''></option>";
        $q = "SELECT Initials FROM Users
                              ORDER BY Initials ASC;";
        $r = mysqli_query($dbConnection, $q);
        while ($row = mysqli_fetch_array($r)) {
            echo "<option value='$row[0]'>$row[0]</option>";
        }
        echo "</select></td></tr>";

        echo "      </table>";

        echo "      <div style='padding-left:20px;padding-top:20px'>
                <input type='submit' name='submit' class='button blue2 bigrounded value = 'Submit' style='width:120px'>
                <a style='width:90px' href='ShowComponents.php?conf=$c->keyId&fc=" . $c->keyFacility . "' class='button blue2 bigrounded'
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