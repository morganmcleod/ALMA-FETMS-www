<?php
class SLN extends GenericTable{
    //FE_StatusLocationsAndNotes
    var $fc;
    var $location;
    var $status;

    public function Initialize_SLN($in_keyId, $in_fc){
        $this->fc= $in_fc;
        parent::Initialize("FE_StatusLocationAndNotes",$in_keyId,"keyId",$in_fc,'keyFacility');

        $q = "SELECT Description, Notes FROM Locations
              WHERE keyId = " . $this->GetValue('fkLocationNames') . ";";
        $r = @mysql_query($q,$this->dbconnection);
        $locid = @mysql_result($r,0,0);
        $this->location = @mysql_result($r,0,0) . " (" . @mysql_result($r,0,1) . ")";

        $q = "SELECT Status FROM StatusTypes
              WHERE keyStatusType = " . $this->GetValue('fkStatusType') . ";";
        $r = @mysql_query($q,$this->dbconnection);
        $this->status = @mysql_result($r,0,0);
    }
}
?>