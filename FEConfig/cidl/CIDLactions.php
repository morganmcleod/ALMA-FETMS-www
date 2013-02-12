<?php
/*
 * This is called from cidlREV.js, to perform CRUD operations.
 *
 * Arguments:
 *
 * keyfe- key value of the Front_Ends table record.
 * action- action to perform (create, read, update, destroy)
 *
 * Actions:
 *
 * read- Read RevHistory table and echo json encoded string.
 * update- Parse JSON string and update a record in the RevHistory table.
 * create- Parse JSON string and insert new record into the RevHistory table.
 * destroy- Delete a record from the RevHistory table.
 *
 */

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.logger.php');
require_once($site_dbConnect);

$keyfe = $_REQUEST['keyfe'];
$action = $_REQUEST['action'];

//READ
$l = new Logger("CIDLactions.txt");
$l->WriteLogFile("Action=$action");
$l->WriteLogFile("keyfe=$keyfe");

if ($action == 'read'){
    $q = "SELECT * FROM RevHistory WHERE fkFront_Ends = $keyfe ORDER BY Date ASC, keyRevHistory ASC;";
    $r = @mysql_query($q,$db);

    //Insert a new record if none exist
    if (@mysql_num_rows($r) < 1){
        $RevHistory = new GenericTable();
        $RevHistory->NewRecord('RevHistory'  ,'keyRevHistory');
        $RevHistory->SetValue('Revision'     ,'A');
        $RevHistory->SetValue('AffectedPages','All');
        $RevHistory->SetValue('Date'         ,date('Y-m-d'));
        $RevHistory->SetValue('Remarks'      ,'Draft');
        $RevHistory->SetValue('fkFront_Ends' ,$keyfe);
        $RevHistory->Update();
        unset($RevHistory);
    }
    $r = @mysql_query($q,$db);
    $ct = 0;
    while($row=@mysql_fetch_array($r)){
        $records[$ct] = array('id'=>$row['keyRevHistory'],'email'=>'me@me.com','revision'=>$row['Revision'], 'affectedpages'=>$row['AffectedPages'],'date'=>substr($row['Date'],0,10), 'remarks'=>$row['Remarks']);
        $ct += 1;
    }
    echo json_encode($records);
}

//UPDATE
if (strlen(strstr($action,'update')) > 0){
    $json = json_decode(file_get_contents("php://input"));
    $id            = $json->{'id'};
    $revision        = $json->{'revision'};
    $date          = $json->{'date'};
    $affectedpages = $json->{'affectedpages'};
    $remarks       = $json->{'remarks'};
    $email         = $json->{'email'};

    $RevHistory = new GenericTable();
    $RevHistory->Initialize('RevHistory',$id,'keyRevHistory');
    $RevHistory->SetValue('Revision'     ,$revision);
    $RevHistory->SetValue('Date'         ,$date );
    $RevHistory->SetValue('AffectedPages',$affectedpages);
    $RevHistory->SetValue('Remarks'      ,$remarks);
    $RevHistory->Update();
    unset($RevHistory);

    //Return the new record as a json object so the grid will no longer show "dirty" records with red triangles.
    $records[0] = array('id'=>$id,'email'=>$email,'revision'=>$revision, 'affectedpages'=>$affectedpages,'date'=>substr($date,0,10) , 'remarks'=>$remarks);
    echo json_encode($records);
}

//DESTROY
if (strlen(strstr($action,'destroy')) > 0){
    $json = json_decode(file_get_contents("php://input"));
    $id            = $json->{'id'};

    //Delete the record
    $q = "DELETE FROM RevHistory WHERE fkFront_Ends = $keyfe AND keyRevHistory = $id;";
    $r = @mysql_query($q,$db);

    //Update the JSON object and send it back
    $q = "SELECT * FROM RevHistory WHERE fkFront_Ends = $keyfe ORDER BY Date ASC;";
    $r = @mysql_query($q,$db);
    $ct = 0;
    while($row=@mysql_fetch_array($r)){

        $records[$ct] = array('id'=>$row['keyRevHistory'],'email'=>'me@me.com','revision'=>$row['Revision'], 'affectedpages'=>$row['AffectedPages'],'date'=>substr($row['Date'],0,10) , 'remarks'=>$row['Remarks']);
        $ct += 1;
    }
    echo json_encode($records);
}

//CREATE
if (strlen(strstr($action,'create')) > 0){
    $json = json_decode(file_get_contents("php://input"));
    $id            = $json->{'id'};
    $revision        = $json->{'revision'};
    $date          = $json->{'date'};
    $affectedpages = $json->{'affectedpages'};
    $remarks       = $json->{'remarks'};

    $RevHistory = new GenericTable();
    $RevHistory->NewRecord('RevHistory','keyRevHistory');
    $RevHistory->SetValue('Revision'     ,$revision);
    $RevHistory->SetValue('Date'         ,$date );
    $RevHistory->SetValue('AffectedPages',$affectedpages);
    $RevHistory->SetValue('Remarks'      ,$remarks);
    $RevHistory->SetValue('fkFront_Ends'      ,$keyfe);
    $RevHistory->Update();

    //Return the new record as a json object so the grid will no longer show "dirty" records with red triangles.
    $records[0] = array('id'=>$RevHistory->keyId,'email'=>'me@me.com','revision'=>$revision, 'affectedpages'=>$affectedpages,'date'=>substr($date,0,10) , 'remarks'=>$remarks);
    echo json_encode($records);
    unset($RevHistory);
}


?>