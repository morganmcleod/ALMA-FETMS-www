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

$keyId = $_REQUEST['id'];  //keyId of FE_Components table
$fc = $_REQUEST['fc'];

$c = new FEComponent();
$c->Initialize_FEComponent($keyId,$fc);
//This is used by the header to display a link back to the FE page
$feconfig = $c->FEConfig;
$fesn=$c->FESN;

$title=$c->ComponentType->GetValue('Description');
if ($c->GetValue('Band') > 0){
    $title.= " Band" . $c->GetValue('Band');
}
$title.= " SN" . $c->GetValue('SN');

include "header.php";

$IsDoc = 0;
$IsWCA = 0;

switch($c->GetValue('fkFE_ComponentType')){
    case 11:
        $IsWCA = 1;
        break;
}

if (isset($_REQUEST['Updated_By'])){
    if ($c->FE_ConfigLink->keyId != ''){
        $Qty_old  = $c->FE_ConfigLink->GetValue('Quantity');
    }
    $SN_old       = $c->GetValue('SN');
    $ESN1_old     = $c->GetValue('ESN1');
    $ESN2_old     = $c->GetValue('ESN2');
    $desc_old       = $c->GetValue('Description');
    $Link1_old       = $c->GetValue('Link1');
    $Link2_old       = $c->GetValue('Link2');
    $DocTitle_old = $c->GetValue('DocumentTitle');
    $Ctype_old    = $c->GetValue('fkFE_ComponentType');
    $Ctype_new    = $Ctype_old;

    // Turn off E_NOTICE error reporting for this section because of all tha unguarded $_REQUEST[]s:
    error_reporting($errorReportSettingsNo_E_NOTICE);

    $Link1_new = str_replace("\\\\","\\",$_REQUEST['Link1']);
    $Link2_new = str_replace("\\\\","\\",$_REQUEST['Link2']);

    if ($c->FE_ConfigLink->keyId != ''){
        echo $c->FE_ConfigLink->SetValue('Quantity',$_REQUEST['Quantity']);
        $c->FE_ConfigLink->Update();
    }

    $c->SetValue('SN',$_REQUEST['SN']);
    $c->SetValue('ESN1',$_REQUEST['ESN1']);
    $c->SetValue('ESN2',$_REQUEST['ESN2']);
    $c->SetValue('Link1',$_REQUEST['Link1']);
    $c->SetValue('Link2',$_REQUEST['Link2']);
    $c->SetValue('Description',$_REQUEST['Description']);
    $c->SetValue('DocumentTitle',$_REQUEST['DocumentTitle']);

    if (isset($_REQUEST['ctype'])){
        $Ctype_new = $_REQUEST['ctype'];
        $c->SetValue('fkFE_ComponentType',$_REQUEST['ctype']);
    }

    $Notes = $_REQUEST['Updated_By'] . ' changed ';

    // Turn back on E_NOTICE error reporting:
    error_reporting($errorReportSettingsNormal);

    $changed = 0;
    if ($c->FE_ConfigLink->keyId != ''){
        if ($Qty_old != $c->FE_ConfigLink->GetValue('Quantity')){
            $Notes .= ' Quantity,';
            $changed = 1;
        }
    }
    if ($SN_old != $c->GetValue('SN')){
        $Notes .= ' SN,';
        $changed = 1;
    }
    if ($ESN1_old != $c->GetValue('ESN1')){
        $Notes .= ' ESN1,';
        $changed = 1;
    }
    if ($ESN2_old != $c->GetValue('ESN2')){
        $Notes .= ' ESN2,';
        $changed = 1;
    }
    if ($Link1_old != $Link1_new){
        $Notes .= ' Link1,';
        $changed = 1;
    }
    if ($Link2_old != $Link2_new){
        $Notes .= ' Link2,';
        $changed = 1;
    }
    if ($desc_old != $c->GetValue('Description')){
        $Notes .= ' Description,';
        $changed = 1;
    }
    if ($DocTitle_old != $c->GetValue('DocumentTitle')){
        $Notes .= ' Doc. Title,';
        $changed = 1;
    }
    if ($Ctype_old != $Ctype_new){
        $Notes .= ' Doc. type,';
        $changed = 1;
    }


    if ($IsWCA == 1){

        $wca = new WCA();
        $wca->Initialize_WCA($c->keyId,$c->GetValue('keyFacility'));
        //Create records in WCAs if one doesn't exist.
        if ($wca->_WCAs->keyId == ''){
            $wca->_WCAs = New GenericTable();
            $wca->_WCAs->NewRecord('WCAs','keyId',$wca->GetValue('keyFacility'),'fkFacility');
            $wca->_WCAs->SetValue('fkFE_Component',$wca->keyId);
        }


        $FloYIG_old = $wca->_WCAs->GetValue('FloYIG');
        $FhiYIG_old = $wca->_WCAs->GetValue('FhiYIG');
        $VG0_old     = $wca->_WCAs->GetValue('VG0');
        $VG1_old     = $wca->_WCAs->GetValue('VG1');

        $wca->_WCAs->SetValue('FloYIG',$_REQUEST['FloYIG']);
        $wca->_WCAs->SetValue('FhiYIG',$_REQUEST['FhiYIG']);
        $wca->_WCAs->SetValue('VG0',   $_REQUEST['VG0']);
        $wca->_WCAs->SetValue('VG1',   $_REQUEST['VG1']);
        $wca->_WCAs->Update();

        if ($FloYIG_old != $wca->_WCAs->GetValue('FloYIG')){
            $Notes .= ' FloYIG,';
            $changed = 1;
        }
        if ($FhiYIG_old != $wca->_WCAs->GetValue('FhiYIG')){
            $Notes .= ' FhiYIG,';
            $changed = 1;
        }
        if ($VG0_old != $wca->_WCAs->GetValue('VG0')){
            $Notes .= ' VG0,';
            $changed = 1;
        }
        if ($VG1_old != $wca->_WCAs->GetValue('VG1')){
            $Notes .= ' VG1,';
            $changed = 1;
        }



    }

    if (substr($Notes, -1, 1) == "," ){
        $Notes = substr($Notes,0,strlen($Notes)-1);
    }
    $Notes .= ".";

    if ($changed == 1){
    $c->Update();
    $dbops = new DBOperations();
    $dbops->UpdateStatusLocationAndNotes_Component($fc, '', '',$Notes,$c->keyId, $_REQUEST['Updated_By'],'');
    echo "<meta http-equiv='Refresh' content='0.1;url=ShowComponents.php?conf=$c->keyId&fc=$fc'>";
    }
    if ($changed == 0){
        echo "<script type='text/javascript'>alert('Nothing was changed in this record.');</script>";
    }
}


echo "

<form action='".$_SERVER["PHP_SELF"]."' method='post' name='Submit' id='Submit'>
    <div id='sidebar2' >
    </div>
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

                if ($c->IsDocument != 1){
                    echo "
                    <tr>
                        <th>
                            Serial Number
                        </th>
                        <td>
                            <input type='text' size='20' name='SN' value = '".$c->GetValue('SN')."'>
                        </td>
                    </tr>";
                }

                echo "
                    <th>
                        Component Type
                    </th>
                    <td>";



                        //If not a document
                        if ($c->IsDocument != 1){
                            echo $c->ComponentType->GetValue('Description');
                        }


                        if ($c->IsDocument == 1){
                            //If it is a document
                            $DocTypes = array(217,218,219,220,222);
                            $ctype = $c->ComponentType->keyId;
                            echo "<select name='ctype'>";


                            for ($i_ctype=0;$i_ctype<count($DocTypes);$i_ctype++){
                                $ComponentType = new GenericTable();
                                $ComponentType->Initialize('ComponentTypes', $DocTypes[$i_ctype],'keyId');

                                if( $DocTypes[$i_ctype] == $c->ComponentType->keyId){
                                    echo "<option selected = 'selected' value='$DocTypes[$i_ctype]'>" . $ComponentType->GetValue('Description') . "</option>";
                                }
                                else{
                                    echo "<option value='$DocTypes[$i_ctype]'>" . $ComponentType->GetValue('Description') . "</option>";
                                }
                                unset($ComponentType);
                            }
                            echo "</select>";
                        }



echo "
                    </td>
                </tr>";

                if ($c->IsDocument != 1){
                    echo
                    "<tr>
                        <th>
                            ESN1
                        </th>
                        <td>
                            <input type='text' size='20' name='ESN1' value = '".$c->GetValue('ESN1')."'>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            ESN2
                        </th>
                        <td>
                            <input type='text' size='20' name='ESN2' value='".$c->GetValue('ESN2')."'>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            Quantity
                        </th>
                        <td>
        ";

                            if (isset($c->FE_ConfigLink) && $c->FE_ConfigLink->keyId != ''){
                                echo "<input type='text' size='3' name='Quantity' value='" . $c->FE_ConfigLink->GetValue('Quantity') . "'>";
                            }
                            else {
                                echo 'Must be in front end to edit quantity';
                            }

                             echo "
                        </td>
                    </tr>


                    ";
                }

                if ($c->IsDocument != 1){
                    echo "
                    <tr class='alt3'>
                        <th align = 'right'>
                            Link1 (CIDL):
                        </th>
                        <td align>
                        <textarea rows='3' cols='40' name='Link2' id='Link2'>".$c->GetValue('Link2')."</textarea>
                        </td>
                    </tr>

                    <tr class='alt3'>
                        <th align = 'right'>
                            Link2 (SICL):
                        </th>
                        <td align>
                        <textarea rows='3' cols='40' name='Link1' id='Link1'>".$c->GetValue('Link1')."</textarea>
                        </td>
                    </tr>";
                }

                if ($c->IsDocument == 1){
                    echo "
                    <tr class='alt3'>
                        <th align = 'right'>
                            Link:
                        </th>
                        <td align>
                        <textarea rows='3' cols='40' name='Link2' id='Link2'>".$c->GetValue('Link2')."</textarea>
                        </td>
                    </tr>";
                }


                echo"
                <tr class='alt3'>
                    <th align = 'right'>
                        Description:
                    </th>
                    <td align>
                    <textarea rows='3' cols='40' name='Description' id='Description'>".$c->GetValue('Description')."</textarea>
                    </td>
                </tr>";



                if ($c->IsDocument == 1){
                    echo "
                    <tr class='alt3'>
                        <th align = 'right'>
                            Document Title:
                        </th>
                        <td align>
                        <textarea rows='3' cols='40' name='DocumentTitle' id='DocumentTitle'>".$c->GetValue('DocumentTitle')."</textarea>
                        </td>
                    </tr>
        ";
                }
                if ($IsWCA == 1){
                    $wca = new WCA();
                    $wca->Initialize_WCA($c->keyId,$c->GetValue('keyFacility'));

                    echo

                    "
                    <tr>
                        <th>
                            YIG LOW (GHz)
                        </th>
                        <td>
                            <input type='text' size='6' name='FloYIG' value=".$wca->_WCAs->GetValue('FloYIG').">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            YIG HIGH (GHz)
                        </th>
                        <td>
                            <input type='text' size='6' name='FhiYIG' value=".$wca->_WCAs->GetValue('FhiYIG').">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            VG0
                        </th>
                        <td>
                            <input type='text' size='6' name='VG0' value=".$wca->_WCAs->GetValue('VG0').">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            VG1
                        </th>
                        <td>
                            <input type='text' size='6' name='VG1' value=".$wca->_WCAs->GetValue('VG1').">
                        </td>
                    </tr>



                ";
                }


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
                <a style='width:90px' href='ShowComponents.php?conf=$c->keyId&fc=".$c->GetValue('keyFacility') . "' class='button blue2 biground'>
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