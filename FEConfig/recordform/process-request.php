<?php

/*
    *  2011-04-14 jee updated to use comboSN
    *                 TODO needs proper error return
    *  2011-04-18 jee added elog
    */

//define("isDEBUG", true);

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.dboperations.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_config_main);
require_once($site_dbConnect);
$dbConnection = site_getDbConnection();

$key = isset($_POST['key']) ? $_POST['key'] : '';
$command = isset($_POST['cmd']) ? $_POST['cmd'] : '';
$compTypeName = isset($_POST['ctype']) ? $_POST['ctype'] : '';
$band = isset($_POST['band']) ? $_POST['band'] : '';

$UserCode = ' ';
if (isset($_REQUEST['UserCode'])) {
    $UserCode = $_REQUEST['UserCode'];
}


//Check first to see if Front End record already exists
$q = "SELECT DefaultFacility FROM DatabaseDefaults";
$r = mysqli_query($dbConnection, $q);
$facility = ADAPT_mysqli_result($r, 0, 0);
$fc = $facility;



//if (isDEBUG) elog("process-request enter with command = ' $command '");
if ($command == "getComboDesc") {
    //get all component types
    $combo_data = mysqli_query($dbConnection, "SELECT keyId,Description FROM ComponentTypes
                ORDER BY Description ASC")
        or die("Could not get description combo box data" . mysqli_error($dbConnection));

    while ($combolist = mysqli_fetch_object($combo_data)) {
        $combo[] = $combolist;
    }
    echo json_encode($combo);
} else if ($command == "getComboSN") {
    // get all serial numbers for a given component type
    $getCompkey = mysqli_query($dbConnection, "SELECT keyId FROM ComponentTypes WHERE Description='$compTypeName'")
        or die("Could not get Component key" . mysqli_error($dbConnection));

    $compType = ADAPT_mysqli_result($getCompkey, 0, "keyId");
    if ($band != "No band" && $band != "") {
        $combo_data = mysqli_query($dbConnection, "SELECT DISTINCT SN FROM FE_Components
        WHERE SN IS NOT NULL AND fkFE_ComponentType='$compType' AND Band='$band' ORDER BY (SN + 0) ASC")
            or die("Could not get SN combo box data" . mysqli_error($dbConnection));
    } else {
        $combo_data = mysqli_query($dbConnection, "SELECT DISTINCT SN FROM FE_Components
        WHERE SN IS NOT NULL AND fkFE_ComponentType='$compType' AND (Band IS NULL OR Band='0')
        ORDER BY BINARY SN ASC")
            or die("Could not get SN combo box data" . mysqli_error($dbConnection));
    }
    while ($combolist = mysqli_fetch_object($combo_data)) {
        $combo[] = $combolist;
    }
    echo json_encode($combo);
} else if ($command == "getData") {
    //get all components integrated in a front end
    $comp_data = mysqli_query($dbConnection, "Select FE_Components.keyId,FE_Components.SN,FE_Components.Band,
    ComponentTypes.Description
    FROM FE_Components
    LEFT JOIN ComponentTypes ON FE_Components.fkFE_ComponentType=ComponentTypes.keyId
    WHERE FE_Components.keyId=ANY(SELECT fkFE_Components FROM
    FE_ConfigLink WHERE fkFE_Config='$key' AND fkFE_ComponentFacility='$facility'
    ORDER BY FE_Components.fkFE_ComponentType DESC) AND keyFacility='$facility'
    ORDER BY ComponentTypes.Description ASC")
        or die("Could not get data" . mysqli_error($dbConnection));

    while ($comp = mysqli_fetch_object($comp_data)) {
        $data[] = $comp;
    }

    echo json_encode($data);
} else if ($command == "saveData") {
    //integrate new but existing components to front end
    $data = $_POST['data'];
    $FEkey = $_POST['fekey'];

    $fe = new FrontEnd(NULL, $fc, FrontEnd::INIT_NONE, $FEkey);

    $FE_sn = $fe->SN;


    if (get_magic_quotes_gpc()) {
        $data = stripslashes($data);
    }
    $obj = json_decode($data);


    //Get string for new SLN record. "Added WCA7-34, CCA6-12, etc"
    $newstring = "Added ";
    $commacount = 0;
    $newcomponents = 0;
    foreach ($obj as $array) {
        $comps = array();
        foreach ($array as $id => $value) {
            $comps[$id] = $value;
        }
        if ($comps['newRecord'] == 1 || $comps['newRecord'] == true) {
            $newcomponents = 1;
            if ($commacount > 0) {
                $newstring .= ", ";
            }
            $sn = $comps['SN'];
            if (strtolower($sn) == 'na') {
                $sn = '';
            }
            if (strtolower($sn) == 'n/a') {
                $sn = '';
            }
            if ($comps['Band'] > 0) {
                $newstring .= $comps['Description'] . " " . $comps['Band'] . "-$sn";
            }
            if ($comps['Band'] < 1) {
                $newstring .= $comps['Description'] . " " . "$sn";
            }
            $commacount += 1;
        }
    } //end for each obj

    //Update FE SLN record to show which components (if any) were added.
    if ($newcomponents == 1) {
        $feconfig = $fe->feconfig->keyId;
        $dbopnewcomps = new DBOperations();
        $dbopnewcomps->UpdateStatusLocationAndNotes_FE($fe->keyFacility, '', '', $newstring, $feconfig, $feconfig, $UserCode, ' ');
        unset($dbopnewcomps);
    }


    foreach ($obj as $array) {
        $comps = array();
        foreach ($array as $id => $value) {
            $comps[$id] = $value;
        }
        if ($comps['newRecord'] == 1 || $comps['newRecord'] == true) {
            //get Component id for given component description.
            $desc = $comps['Description'];

            $compType = mysqli_query($dbConnection, "SELECT keyId FROM ComponentTypes WHERE Description='$desc'");
            if (mysqli_num_rows($compType) > 0) {
                $type_id = ADAPT_mysqli_result($compType, 0, 'keyId');
                $dbop = new DBOperations();
                $sn = $comps['SN'];
                $band = $comps['Band'];

                //get the latest configuration number of a given frontend
                if ($band != "No band") //band is not 0 or null
                {
                    $checkduplicates = mysqli_query($dbConnection, "SELECT MAX(keyId) AS MaxKey FROM
                    FE_Components WHERE fkFE_ComponentType='$type_id'
                    AND SN='$sn' AND Band='$band' AND keyFacility='$facility'");
                } else {
                    $checkduplicates = mysqli_query($dbConnection, "SELECT MAX(keyId) AS MaxKey
                    FROM FE_Components WHERE fkFE_ComponentType='$type_id'
                    AND SN='$sn' AND (Band is NULL OR Band='0')
                    AND keyFacility='$facility'");
                }

                $CompKey = ADAPT_mysqli_result($checkduplicates, 0, "MaxKey");

                //Possible new method
                $dbop->AddComponentToFrontEnd($fe->keyId, $CompKey, $fc, $fc, '', '', $UserCode);
            }
        }
    } //end for each obj

    $success = "{success:true}";
    echo $success;
} else if ($command == "deleteData") {
    $keyComponent = $_POST['compkey'];
    $FEConfig = $_POST['fekey'];

    $dbop = new DBOperations();
    $dbop->RemoveComponentFromFrontEnd($fc, $keyComponent, $UserCode);


    $success = "{success:true,newconfig:$dbop->latest_feconfig,deleterec:true}";
    unset($dbop);
    echo $success;
}
