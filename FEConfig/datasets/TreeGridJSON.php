<?php
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_dbConnect);

$action   = $_REQUEST['action'];
$FEid     = $_REQUEST['FEid'];
$Band     = $_REQUEST['band'];
$DataType = $_REQUEST['datatype'];

//**************************************
//  Read data to display in tree
//**************************************
if ($action == 'read'){
    $tdh = array();

    //Get all TestData_header.keyId values for this Front End, Band and test datatype
    $q = "SELECT DISTINCT(TestData_header.keyId), FE_Config.keyFEConfig
        FROM TestData_header, FE_Config, Front_Ends
        WHERE TestData_header.fkTestData_Type = $DataType
        AND TestData_header.Band = $Band
        AND TestData_header.fkFE_Config = FE_Config.keyFEConfig
        AND FE_Config.fkFront_Ends = $FEid";

    $r = @mysql_query($q,$db);
    $count = 0;
    // for each Testdata_header get all data specific subHeader records
    while ($row = @mysql_fetch_array($r)){

        //Empty out subheader records. This is an array to store each of the Test Data SubHeader child objects
        //for a TestData_header record.
        $sub_records = '';

        $tdheader = new GenericTable();
        $tdheader->Initialize('TestData_header',$row['keyId'],'keyId');

        switch ($DataType){

        case 7: // ifspectrum
            //Get IFSpectrum_SubHeader records where IFGain = 15
            $qif = "SELECT keyId FROM IFSpectrum_SubHeader
                    WHERE fkHeader = $tdheader->keyId
                    AND IFGain = 15
                    ORDER BY FreqLO ASC, IFChannel ASC";

            $rif = @mysql_query($qif,$db);
            $num_children = 0;

            $numrowsif = @mysql_num_rows($rif);

            while ($rowif = @mysql_fetch_Array($rif)){
                $ifsub = new GenericTable();
                $ifsub->Initialize('IFSpectrum_SubHeader',$rowif['keyId'],'keyId');
                $ifchannel = $ifsub->GetValue('IFChannel');
                $text = $ifsub->GetValue('FreqLO') . " GHz (IF" . $ifsub->GetValue('IFChannel') . ")";
                $checked = false;
                if ($ifsub->GetValue('IsIncluded') == '1'){
                    $checked = true;
                }
                $sub_records[$num_children] = array('text'=>$text,'leaf'=>true,'checked'=>$checked, 'id'=>$ifsub->keyId);

                $num_children += 1;
                unset($ifsub);
            }
            break;


        case 57: //lolocktest
            $qlosub = "SELECT keyId FROM TEST_LOLockTest_SubHeader
                        WHERE fkHeader = $tdheader->keyId;";
            $rlosub = @mysql_query($qlosub,$db);
            $losubId = @mysql_result($rlosub,0,0);


            $qlo = "SELECT LOFreq, IsIncluded FROM TEST_LOLockTest
                    WHERE fkHeader = $losubId
                    ORDER BY LOFreq ASC";

            $rlo = @mysql_query($qlo,$db);
            $num_children = @mysql_num_rows($rlo);

            $count_losub = 0;
            while ($rowlo = @mysql_fetch_Array($rlo)){
                $checked = false;
                if ($rowlo[IsIncluded] == '1'){
                    $checked = true;
                }
                $text = $rowlo[LOFreq] . " GHz";

                //RecordID is an id value for a specific LO frequency corresponding to an LOLockTest_SubHeader.keyId
                //An example RecordID would be 2314_221
                $RecordID = $losubId . "_" . $rowlo[LOFreq];
                $sub_records[$count_losub] = array('text'=>$text,'leaf'=>true,'checked'=>$checked, 'id'=>$RecordID);

                $count_losub++;
                unset($losub);
            }

            break;

        case 58: // noise temperature

            $qntsub = "SELECT keyId FROM Noise_Temp_SubHeader
                        WHERE fkHeader = $tdheader->keyId;";
            $rntsub = @mysql_query($qntsub,$db);
            $ntsubId = @mysql_result($rntsub,0,0);

            $qnt = "SELECT FreqLO, IsIncluded FROM Noise_Temp
                    WHERE fkSub_Header= $ntsubId
                    ORDER BY FreqLO ASC";

            $rnt = @mysql_query($qnt,$db);
            $num_children = @mysql_num_rows($rnt);

            $count_ntsub = 0;
            $prev_FreqLO = 0;
            while ($rownt = @mysql_fetch_Array($rnt)){
                // only display one entry per LO Frequency
                if ($prev_FreqLO != $rownt[FreqLO]){

                    $checked = false;
                    if ($rownt[IsIncluded] == '1'){
                        $checked = true;
                    }
                    $text = $rownt[FreqLO] . " GHz";

                    //RecordID is an id value for a specific LO frequency corresponding to an LOLockTest_SubHeader.keyId
                    //An example RecordID would be 2314_221
                    $RecordID = $ntsubId . "_" . $rownt[FreqLO];
                    $sub_records[$count_ntsub] = array('text'=>$text,'leaf'=>true,'checked'=>$checked, 'id'=>$RecordID);

                    $count_ntsub += 1;
                    unset($ntsub);
                }
                $prev_FreqLO = $rownt[FreqLO];
            }
            break;

        } // end switch

        // if there are no children, extjs will not execute correctly...
        // but as a work around, just substitue a dummy record.  This may be better anyway
        // because it gives you a solid indication that there is no subheader data for a test
        // data record
        if ($num_children == 0){
            $sub_records[0] = array('text'=>"No Data Found",'leaf'=>true, 'id'=>$count);
        }
        $tdh[$count] = '';
        $tdh[$count]['text'] = 'TestData_header ' . $tdheader->keyId;
        $tdh[$count]['feconfig'] = $row['keyFEConfig'];
        $tdh[$count]['ts'] = $tdheader->GetValue('TS');
        $tdh[$count]['notes'] = @mysql_real_escape_string($tdheader->GetValue('Notes'));
        $tdh[$count]['cls'] = 'folder';
        $tdh[$count]['expanded'] = false;
        $tdh[$count]['id'] = $tdheader->keyId;
        $tdh[$count]['groupnumber'] = $tdheader->GetValue('DataSetGroup');
        $tdh[$count]['children'] = $sub_records;

        $count += 1;
        unset($tdheader);

    } // end while loop

    echo json_encode($tdh);    // update table on client
}


//**************************************
//  Update the children in the tree
//**************************************
// This code needs to be improved.  It sends db queries for each record!
// Crazy inefficient and slow
if ($action == 'update_children'){
    $array = json_decode(file_get_contents("php://input"), true);

    switch ($DataType){
        case 7: // ifspectrum
            for ($i=0;$i<count($array);$i++){
                $FEid        = $array[$i][FEid];
                $checked     = $array[$i][checked];
                $datatype    = $array[$i][datatype];

                    $subid = $array[$i][subid];

                    //Update "IsIncluded" value in the table IFSpectrum_SubHeader
                    $ifsub = new GenericTable();
                    $ifsub->Initialize('IFSpectrum_SubHeader',$subid,'keyId');

                    $ifsubLO = $ifsub->GetValue('FreqLO');
                    $fkHeader = $ifsub->GetValue('fkHeader');

                    //Now apply the checked value to the record where IFGain=0 and IFGain=15 for the same LO frequency
                    $q0 = "UPDATE IFSpectrum_SubHeader SET IsIncluded = $checked WHERE FreqLO=$ifsubLO AND fkHeader = $fkHeader;";
                    $r0 = @mysql_query($q0,$ifsub->dbconnection);
                    unset($ifsub);
            }
            break;

        case 57: // LOlocktest
            for ($i=0;$i<count($array);$i++){
                $FEid        = $array[$i][FEid];
                $checked     = $array[$i][checked];
                $datatype    = $array[$i][datatype];

                $subid_array = explode("_",$array[$i][subid]);
                $subid = $subid_array[0];
                $lofreq = $subid_array[1];
                $q  = "UPDATE TEST_LOLockTest SET IsIncluded = $checked WHERE fkHeader = $subid AND LOFreq = $lofreq;";
                $r = @mysql_query($q,$db);
            }
            break;

        case 58: // Noise Tempertaure
            for ($i=0;$i<count($array);$i++){
                $FEid        = $array[$i][FEid];
                $checked     = $array[$i][checked];
                $datatype    = $array[$i][datatype];

                $subid_array = explode("_",$array[$i][subid]);
                $subid = $subid_array[0];
                $lofreq = $subid_array[1];
                $q  = "UPDATE Noise_Temp SET IsIncluded = $checked WHERE fkSub_Header = $subid AND FreqLO = $lofreq;";
                $r = @mysql_query($q,$db);
            }
            break;
    }
}


//**************************************
//  Update test data header record
//**************************************
if ($action == 'update'){
    //This function is the same for all data types, since it only
    //affects the TestDat_header records and not the subheader tables.

    //Decode JSON tree data
    $json = json_decode(file_get_contents("php://input"));

    $array = json_decode(file_get_contents("php://input"), true);


    if ($array[0]['id'] == ''){
        //Only one TestData_header record has been affected
        //Update TestData_header record
        $TestData_header = new GenericTable();
        $TestData_header->Initialize('TestData_header',$array['id'],'keyId');
        $TestData_header->SetValue('DataSetGroup',$array['groupnumber']);
        $TestData_header->SetValue('Notes',$array['notes']);
        $TestData_header->Update();


        unset($TestData_header);
    }

    $rows = count($array,0);
    $cols = (count($array,1)/count($array,0))-1;
    if ($array[0]['id'] != ''){

    //2d array (i.e., more than one TestData_header record is being affected)
        for ($i=0;$i<$rows;$i++){
            $TestData_header = new GenericTable();
            $TestData_header->Initialize('TestData_header',$array[$i]['id'],'keyId');
            $TestData_header->SetValue('DataSetGroup',$array[$i]['groupnumber']);
            $TestData_header->SetValue('Notes',$array[$i]['notes']);
            $TestData_header->Update();
        }
    }

    //Echo the json data back so the store will update and the "dirty record" markers
//    echo json_encode($json);
    // echo server call back
    echo "{'success':'1'}";
}



//**************************************
//  Get combo box data
//**************************************
if ($action == 'combobox'){

    // find all TDH records for any config for the given front end:
    $q="SELECT DISTINCT TestData_header.keyId, TestData_header.DataSetGroup
        FROM FE_Config LEFT JOIN TestData_header
        ON TestData_header.fkFE_Config = FE_Config.keyFEConfig
        WHERE TestData_header.Band = $Band
        AND TestData_header.fkTestData_Type= $DataType
        AND FE_Config.fkFront_Ends = $FEid
        ORDER BY TestData_header.DataSetGroup ASC";

    $r = @mysql_query($q,$db);

    $DataSetGroups = array();

    $lastG = "";
    while($row = @mysql_fetch_array($r)){
        // this code counts how many times a given data set has occured
        $tdh = $row['keyId'];
        $g = $row['DataSetGroup'];
        if ($g != $lastG) {
            $lastG = $g;
            $a = array('datasetgroup' => $g, 'TDHkeyId' => $tdh);
            array_push($DataSetGroups, $a);
        }
    }
    echo json_encode($DataSetGroups);
}

?>

