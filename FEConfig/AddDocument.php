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
<div id='wrap' >

<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_classes . '/class.dboperations.php');
require_once($site_dbConnect);

$feconfig = $_REQUEST['conf'];  //keyId of FE_Components table
$fc = $_REQUEST['fc'];

$fe = new FrontEnd();
$fe->Initialize_FrontEnd_FromConfig($feconfig, $fc, FrontEnd::INIT_NONE);

$fesn=$fe->GetValue('SN');

$title = "Add Document To FE $fesn";

include('header.php');

if (isset($_REQUEST['Updated_By'])){
    $c = new FEComponent();
    $c->NewRecord_FEComponent($fc);

    // Turn off E_NOTICE error reporting for this section because of all tha unguarded $_REQUEST[]s:
    error_reporting($errorReportSettingsNo_E_NOTICE);

    $c->SetValue('fkFE_ComponentType',$_REQUEST['fkFE_ComponentType']);
    $c->SetValue('Link1',$_REQUEST['Link1']);
    $c->SetValue('Link2',$_REQUEST['Link2']);
    $c->SetValue('Description',$_REQUEST['Description']);
    $c->SetValue('DocumentTitle',$_REQUEST['DocumentTitle']);
    $c->SetValue('Production_Status',$_REQUEST['StatusType']);

    // Turn back on E_NOTICE error reporting:
    error_reporting($errorReportSettingsNormal);

    $c->Update();
    $dbops = new DBOperations();
    $dbops->AddComponentToFrontEnd($fe->keyId, $c->keyId, $fe->GetValue('keyFacility'), $fc, '','', $_REQUEST['Updated_By']);
    echo "<meta http-equiv='Refresh' content='0.1;url=ShowComponents.php?conf=$c->keyId&fc=$fc'>";
}



echo "

<form action='".$_SERVER["PHP_SELF"]."' method='post' name='Submit' id='Submit'>
    <div id='sidebar2' >
    </div>
<div id='maincontent'>
        <input type='hidden' name='fc' id='facility' value='$fc'>
        <input type='hidden' name='conf' id='facility' value='$feconfig'>
        <div style='width:500px'>
            <table id = 'table5'>
                <tr class='alt'>
                    <th colspan = '2'>
                        New Document Information
                    </th>
                </tr>

                <tr>
                    <th>
                        Document Type
                    </th>
                    <td>";

                    echo "<select name='fkFE_ComponentType'>";

                    $DocType = array(217,218,219,220,222);

                    for($idoc=0;$idoc<count($DocType);$idoc++){
                        $q = "SELECT Description FROM ComponentTypes WHERE keyId = $DocType[$idoc];";
                        $r = @mysql_query($q,$db);
                        $DocDesc = @mysql_result($r,0,0);
                        echo "<option value='$DocType[$idoc]'>$DocDesc</option>";
                    }

                    echo "</select>";

                    echo"
                    <tr><th>Status</th>
                    <td>
                    <select name='StatusType'>
                        <option value='Draft'>Draft</option>
                        <option value='Released'>Released</option>
                        <option value='Open'>Open</option>
                        <option value='Closed'>Closed</option>
                        <option value='Resolved'>Resolved</option>
                    </select>
                </td>
                </tr>";

echo "
                    </td>
                </tr>


                    <tr class='alt3'>
                        <th align = 'right'>
                            Document Title:
                        </th>
                        <td align>
                        <textarea rows='3' cols='40' name='DocumentTitle' id='DocumentTitle'></textarea>
                        </td>
                    </tr>







                <tr class='alt3'>
                    <th align = 'right'>
                        Link:
                    </th>
                    <td align>
                    <textarea rows='3' cols='40' name='Link2'></textarea>
                    </td>
                </tr>
                <tr class='alt3'>
                    <th align = 'right'>
                        Description:
                    </th>
                    <td align>
                    <textarea rows='3' cols='40' name='Description' id='Description'></textarea>
                    </td>
                </tr>";

                    echo"
                    <tr><th>Updated By</th>
                    <td>
                    <select name='Updated_By' id='Updated_By'>
                        <option value=''></option>";
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


        echo "
            </table>

            <div style='padding-left:20px;padding-top:20px'>
            <input type='submit' name='submit' class='button blue2 biground' value = 'Submit' style='width:120px'>

            <a style='width:90px' href='ShowFEConfig.php?key=$feconfig&fc=$fc' class='button blue2 biground'>
                    <span style='width:130px'>Cancel</span></a>
            </div>

        </div>

    </div>
</div>

</form>";

include('footer.php');

?>
</div>
</body>
</html>