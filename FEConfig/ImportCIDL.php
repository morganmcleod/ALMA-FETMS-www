<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link rel="stylesheet" type="text/css" href="Cartstyle.css">

<link rel="stylesheet" type="text/css" href="buttons.css">
<link href="images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
<script type="text/javascript" src="spin.js"></script>
<title>Import CIDL</title>
</head>
<div id="wrap">
<body>

<?php

require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.dboperations.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_classes . '/class.logger.php');
require_once($site_classes . '/PHPExcel/PHPExcel.php');
require_once($site_config_main);
require_once($site_dbConnect);
require_once('HelperFunctions.php');

$objReader = PHPExcel_IOFactory::createReader('Excel5');
$objReader->setReadDataOnly(false);

$keyFE             = $_REQUEST['FEkey'];
$fkStatus         = $_REQUEST['status'];
$UpdatedBy         = $_REQUEST['updatedby'];
$UpdateExisting = $_REQUEST['updateexisting'];
$fkLocation     = $_REQUEST['location'];
$SLNNotes             = $_REQUEST['Notes'];

$CIDLSpreadsheetVersion = "1.0.7";
$errors = "";

$fe = new FrontEnd();
$fe->Initialize_FrontEnd($keyFE,$fc,-1);
$feconfig = $fe->feconfig->keyId;
$fe_sn = $fe->GetValue('SN');
$title="Front End-" . $fe->GetValue('SN');

include('header.php');

//Show a spinner while importing
echo "<div id='spinner' style='position:absolute;
left:400px;
top:25px;'>
<font color = '#00ff00'><b>
    &nbsp &nbsp &nbsp &nbsp
    &nbsp &nbsp &nbsp &nbsp
    &nbsp &nbsp &nbsp &nbsp
    &nbsp &nbsp &nbsp &nbsp
    &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp
    Importing CIDL...
    </font></b>

</div>";
echo "<script type = 'text/javascript'>
    var opts = {
      lines: 12, // The number of lines to draw
      length: 10, // The length of each line
      width: 3, // The line thickness
      radius: 10, // The radius of the inner circle
      color: '#00ff00', // #rgb or #rrggbb
      speed: 1, // Rounds per second
      trail: 60, // Afterglow percentage
      shadow: false, // Whether to render a shadow

    };
    var target = document.getElementById('spinner');
    var spinner = new Spinner(opts).spin(target);
</script>
";

if (isset($_FILES['spreadsheet'])){
    $spreadsheetname = $_FILES['spreadsheet']['name'];

    $extension = strtolower(substr($spreadsheetname,strlen($spreadsheetname)-3,3));

    $arr = explode(".",strtolower($spreadsheetname));
    $extension = $arr[count($arr)-1];

    if ($extension != "xls"){


        echo "<script type 'text/javascript'>";

        echo "alert('Submitted file: $spreadsheetname. Incorrect file format ($extension). Please use a .xls file.');";
        echo "</script>";

        echo "<meta http-equiv='refresh' content='0;url=ImportCIDLform.php'>";
    }

    if ($extension == "xls"){
    echo "submitted file: $spreadsheetname<br>";
    $objPHPExcel = $objReader->load($_FILES['spreadsheet']['tmp_name']);
    $objWorksheet = $objPHPExcel->getActiveSheet();



    $logger = new Logger();
    $logger->WriteLogFile(date('r'));
    $logger->WriteLogFile("Speadsheet uploaded: $spreadsheetname");
    $logger->WriteLogFile("Spreadsheet Version $CIDLSpreadsheetVersion");


    $ImportDocs = 0;
    for($row = 0; $row < 800; $row++) {
        $Import = 1;
        $keyId = '';
        if ($row == 1){
            $Version = $objPHPExcel->getActiveSheet()->getCell('B'.$row)->getCalculatedValue();
            if ($Version != $CIDLSpreadsheetVersion){
            $alertmsg = "WARNING: Spreadsheet version ($Version) is not the current version ($CIDLSpreadsheetVersion).";
            $alertmsg .= "This import operation is being aborted.";
            echo "<script language='javascript'>alert('$alertmsg');</script>";
            $refurl = "ImportCIDLform.php";
            $row = 800;
            $Import = 0;
            echo '<meta http-equiv="refresh" content="1;url=ImportCIDLform.php"> ';
        }

        }


        $Quantity      = $objPHPExcel->getActiveSheet()->getCell('C'.$row)->getCalculatedValue();


        if ($objPHPExcel->getActiveSheet()->getCell('A'.$row)->getCalculatedValue() != ''){
            $SectionHeader = $objPHPExcel->getActiveSheet()->getCell('A'.$row)->getCalculatedValue();
        }


        if ($SectionHeader == 'PAI and PAS Reports'){
            $ImportDocs = 1;
        }

        if ($Quantity > 0){
            $Bandstr = "";
            if ($ImportDocs == 0){
                $ComponentType   = $objPHPExcel->getActiveSheet()->getCell('B'.$row)->getCalculatedValue();
                $Quantity        = $objPHPExcel->getActiveSheet()->getCell('C'.$row)->getCalculatedValue();
                $Description     = $objPHPExcel->getActiveSheet()->getCell('I'.$row)->getCalculatedValue();
                $Band             = trim($objPHPExcel->getActiveSheet()->getCell('D'.$row)->getCalculatedValue());
                $SN                 = trim($objPHPExcel->getActiveSheet()->getCell('E'.$row)->getCalculatedValue());
                $ESN1            = $objPHPExcel->getActiveSheet()->getCell('F'.$row)->getCalculatedValue();
                $ESN2            = $objPHPExcel->getActiveSheet()->getCell('G'.$row)->getCalculatedValue();
                $Status             = $objPHPExcel->getActiveSheet()->getCell('H'.$row)->getCalculatedValue();
                $SICLLink         = $objPHPExcel->getActiveSheet()->getCell('J'.$row)->getCalculatedValue();
                $CIDLLink         = $objPHPExcel->getActiveSheet()->getCell('K'.$row)->getCalculatedValue();

                $SN = str_replace("#","",$SN);
                $SN = str_replace("NA","",$SN);
                $SN = str_replace("N.A.","",$SN);
                $SN = str_replace("N/A","",$SN);

                if ($Band != ''){
                    $Bandstr = "Band$Band";
                }
                if ($SN != ''){
                    $SNstr = "SN$SN";
                }

                //Find key for component type
                $q = "SELECT keyId FROM ComponentTypes WHERE Description LIKE '$ComponentType';";
                $r = @mysql_query($q,$db);
                $fkFE_ComponentType = @mysql_result($r,0,0);
                if ($fkFE_ComponentType == ''){
                    $Import = 0;
                    $errors = "Warning (row $row)- No component type found for $ComponentType. ";
                    $errors .= "Record not imported.";
                    $logger->WriteLogFile($errors);
                }


                /*
                 * Possible cases
                 *
                 * 1. Has band and SN
                 *
                 * Check to see if component of same type/Band/SN is in the database
                 *
                 * 2. No band, no SN
                 *
                 * Check to see if component of same type is associated
                 * with this front end.
                 *
                 * 3. Band, but no SN
                 *
                 * Check to see if component of same type/band is associated
                 * with this front end.
                 *
                 * 4. SN, but no band
                 *
                 * Check to see if component of same type/SN is in the database
                 */

                $BandCheck = $Band;
                if ($Band == ''){
                    $BandCheck = "%";
                }
                $SNCheck = $SN;
                if ($SN == ''){
                    $SNCheck = "%";
                }
                if (($SN != '') && ($Quantity > 1)){
                    $errors  = "Warning (row $row)- $ComponentType ($Bandstr $SNstr) has Quantity = $Quantity. ";
                    $errors .= "Quantity changed to 1.";
                    $logger->WriteLogFile($errors);
                    $Quantity = 1;
                }
                if (($BandCheck != "%") && ($SNCheck != "%")){
                    //Case 1: A Band and SN exists in the spreadsheet for this component.
                    //Check to see if component with same Band/SN is associated
                    //with this front end.
                    $q = "SELECT keyId FROM FE_Components
                          WHERE Band LIKE '$BandCheck'
                          AND SN LIKE '$SNCheck'
                          AND fkFE_ComponentType = $fkFE_ComponentType;";
                    $r = @mysql_query($q);
                    $keyId = @mysql_result($r,0,0);

                }

                if (($BandCheck == "%") && ($SNCheck == "%")){
                    //Case 2: No Band, no SN for this component.
                    //Check for a record with the same Component Type,
                    //already in this front end
                    $q = "SELECT FE_Components.keyId
                        FROM FE_Components,FE_Config, FE_ConfigLink
                        WHERE FE_Config.keyFEConfig = $feconfig
                        AND FE_ConfigLink.fkFE_Config = FE_Config.keyFEConfig
                        AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
                        AND FE_Components.fkFE_ComponentType = $fkFE_ComponentType;";
                    $r = @mysql_query($q,$db);
                    $keyId = @mysql_result($r,0,0);
                }

                if (($BandCheck != "%") && ($SNCheck == "%")){
                    //Case 3: Band, but no SN for this component
                    //Check to see if component of same type/band is associated
                     //with this front end.
                    $q = "SELECT FE_Components.keyId
                        FROM FE_Components,FE_Config, FE_ConfigLink
                        WHERE FE_Config.keyFEConfig = $feconfig
                        AND FE_ConfigLink.fkFE_Config = FE_Config.keyFEConfig
                        AND FE_Components.Band LIKE '$BandCheck'
                        AND FE_ConfigLink.fkFE_Components = FE_Components.keyId
                        AND FE_Components.fkFE_ComponentType = $fkFE_ComponentType;";
                    $r = @mysql_query($q,$db);
                    $keyId = @mysql_result($r,0,0);
                }

                if (($BandCheck == "%") && ($SNCheck != "%")){
                    //Case 4: SN, but no band for this component.
                    //Check to see if component of same type/SN is in the database
                    $q = "SELECT keyId FROM FE_Components
                          WHERE Band LIKE '%'
                          AND SN LIKE '$SNCheck'
                          AND fkFE_ComponentType = $fkFE_ComponentType;";
                    $r = @mysql_query($q,$db);
                    $keyId = @mysql_result($r,0,0);
                }

                $c = new FEComponent();




                if ($keyId != ""){
                    //A component already exists in the database.
                    $c->Initialize_FEComponent($keyId,$fc);
                    if ($c->FEid != $fe->keyId){
                        //IF the component is in the database, but is not in this front end,
                        //associate it with this front end.

                        $errors = "Warning (row $row)- $ComponentType ($Bandstr $SNstr) already in database. ";
                        $errors .= "Record will be associated with Front End ".$fe->GetValue('SN') . ".";
                        $logger->WriteLogFile($errors);
                    }
                    if (($c->FEid != $fe->keyId) && ($c->FEid != '')){
                        //If the component is already in another front end,
                        //don't import it.
                        $errors  = "Warning (row $row)- $ComponentType ($Bandstr $SNstr) is in another Front End (FE SN " . $c->FESN . "). ";
                        $errors .= "Record not imported.";
                        $logger->WriteLogFile($errors);
                        $Import = 0;
                    }
                    if ($c->FEid == $fe->keyId){
                        //If the component is already in this front end,
                        //don't import it.
                        $errors  = "Warning (row $row)- $ComponentType ($Bandstr $SNstr) is in already in this Front End (FE SN" . $c->FESN . "). ";
                        $errors .= "Record not imported.";
                        $logger->WriteLogFile($errors);
                        $Import = 0;
                    }
                }

                if ($keyId == ""){
                    //No preexisting record found. Import this record.
                    $c->NewRecord_FEComponent($fc);
                }

                //if (($keyId == "") || ($UpdateExisting == 1)){
                    //Apply the values from the spreadsheet if:
                    //1) No existing record was found, or
                    //2) An existing record was found, and the user
                    //   wants to update it.
                    if ($Import == 1){
                        $c->SetValue('fkFE_ComponentType',$fkFE_ComponentType);
                        $c->SetValue('Band'                 ,$Band);
                        $c->SetValue('SN'                 ,$SN);
                        $c->SetValue('ESN1'                ,$ESN1);
                        $c->SetValue('ESN2'                ,$ESN2);
                        $c->SetValue('Link1'                 ,$CIDLLink);
                        $c->SetValue('Link2'                 ,$SICLLink);
                        $c->SetValue('Production_Status' ,$Status);
                        $c->SetValue('Description'          ,$Description);
                        $c->Update();
                        $dbop = new DBOperations();
                        $dbop->AddComponentToFrontEnd($keyFE, $c->keyId, $fc, $fc, $fkStatus, '', $UpdatedBy);
                        unset($dbop);

                        $c->Initialize_FEComponent($c->keyId,$c->GetValue('keyFacility'));
                        $c->FE_ConfigLink->SetValue('Quantity',$Quantity);
                        $c->FE_ConfigLink->Update();

                    }

                //}
                unset($c);

            }//end if ImportDocs == 0, done importing components

            /*
             * IMPORT DOCUMENTS
             */
            if ($ImportDocs == 1){
                //Import documents
                $ComponentType = $SectionHeader;
                $DocumentTitle = $objPHPExcel->getActiveSheet()->getCell('B'.$row)->getCalculatedValue();
                $Status           = $objPHPExcel->getActiveSheet()->getCell('H'.$row)->getCalculatedValue();
                $Comments       = $objPHPExcel->getActiveSheet()->getCell('I'.$row)->getCalculatedValue();
                $Link1         = $objPHPExcel->getActiveSheet()->getCell('J'.$row)->getCalculatedValue();

                //Find key for component type
                $q = "SELECT keyId FROM ComponentTypes WHERE Description LIKE '$ComponentType';";
                $r = @mysql_query($q,$db);
                $fkFE_ComponentType = @mysql_result($r,0,0);
                if ($fkFE_ComponentType == ''){
                    $Import = 0;
                    $errors = "Warning (row $row)- No component type found for $ComponentType. ";
                    $logger->WriteLogFile($errors);
                    $errors .= "Action: Record not imported.";
                    $logger->WriteLogFile($errors);
                }

                //Check to see if document of same type and title is in the database
                $q = "SELECT keyId FROM FE_Components
                      WHERE DocumentTitle LIKE '$DocumentTitle'
                      AND fkFE_ComponentType = $fkFE_ComponentType;";
                $r = @mysql_query($q,$db);
                $keyId = @mysql_result($r,0,0);
                if ($keyId != ''){
                    //Don't import if the document is already in the database.
                    $Import = 0;
                    $errors  = "Warning (row $row)- '$DocumentTitle' is already imported. ";
                    $errors .= "Record not imported.";
                    $logger->WriteLogFile($errors);
                }

                $doc = new FEComponent();

                if ($keyId != ""){
                    //A component already exists in the database.
                    $doc->Initialize_FEComponent($keyId,$fc);
                    if (($doc->FEid != $fe->keyId) && ($doc->FEid != '')){
                        $errors  = "Warning (row $row)- '$DocumentTitle' is in another Front End (FE SN " . $doc->FESN . "). ";
                        $errors .= "Record not imported.";
                        $logger->WriteLogFile($errors);
                        $Import = 0;
                    }
                }

                if ($keyId == ""){
                    //No preexisting record found. Import this record.
                    $doc->NewRecord_FEComponent($fc);
                }
                //if (($keyId == "") || ($UpdateExisting == 1)){
                    //Apply the values from the spreadsheet if:
                    //1) No existing record was found, or
                    //2) An existing record was found, and the user
                    //   wants to update it.
                    if ($Import == 1){
                        $doc->SetValue('fkFE_ComponentType'           ,$fkFE_ComponentType);
                        $doc->SetValue('Link1'                          ,mysql_real_escape_string($Link1));
                        $doc->SetValue('DocumentTitle'                 ,$DocumentTitle);
                        $doc->SetValue('Production_Status'              ,$Status);
                        $doc->SetValue('Description'                   ,$Comments);
                        $doc->Update();
                        $dbop = new DBOperations();
                        $dbop->AddComponentToFrontEnd($keyFE, $doc->keyId, $fc, $fc, $fkStatus, '', $UpdatedBy);
                        unset($dbop);
                        $doc->Initialize_FEComponent($doc->keyId,$doc->GetValue('keyFacility'));
                        $doc->FE_ConfigLink->SetValue('Quantity',$Quantity);
                        $doc->FE_ConfigLink->Update();
                    }
                //}
                unset($doc);

                }
            }//end if Quantity = 0
        }
    }//end if extension == xls

}

if ($Import == 1){
    //Update Status Location and Notes for Front End
    $dbops = new DBOperations();
    $dbops->UpdateStatusLocationAndNotes_FE($fc, '', '',$SLNNotes,$fe->feconfig->keyId, $fe->feconfig->keyId, $UpdatedBy,' ');

    $oldfeconfig = $fe->feconfig->keyId;
    $fe->Initialize_FrontEnd($keyFE,$fc);
    $newfeconfig = $fe->feconfig->keyId;
}

$refurl = "ShowFEConfig.php?key=".$fe->feconfig->keyId."&fc=".$fe->GetValue('keyFacility');

if ($errors != ""){
    $refurl .= "&e=".$logger->logfilebasename;
}
unset($logger);

if ($Version != $CIDLSpreadsheetVersion){
    //echo '<meta http-equiv="refresh" content="1;url=ImportCIDLform.php"> ';
}
if ($Version == $CIDLSpreadsheetVersion){
    echo '<meta http-equiv="Refresh" content="0.2;url='.$refurl.'">';
}

?>
</body>
</div>
<?
include "footer.php";
?>
</html>
