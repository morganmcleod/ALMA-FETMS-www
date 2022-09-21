<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.generictable.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_classes . '/class.frontend.php');
require_once($site_classes . '/class.sln.php');
require_once($site_classes . '/class.logger.php');
require_once($site_FEConfig . '/HelperFunctions.php');
require_once($site_dbConnect);

class DBOperations {
    var $dbconnection;
    var $FEid;              //key value of front end
    var $COMPid;          //key value of component
    var $fc_fe;          //facility code of front end
    var $fc_comp;         //facility code of component
    var $frontEnd;       //Front End object                 (class.frontend.php)
    var $Component;         //Component object                 (class.fecomponent.php
    var $FE_ConfigLink;  //FE_ConfigLink object             (class.generictable.php)
    var $FE_Config;       //FE_Config object                 (class.generictable.php)
    var $FE_SLN;           //FE_StatusLocationAndNotes object (class.generictable.php)
    var $latest_feconfig; //Latest FE Configuration number


    public function AddComponentToFrontEnd($in_FEid, $in_COMPid, $in_fc_fe, $in_fc_comp, $statustype, $notes = '', $UpdatedBy = '', $UpdateSLNcomponent = 1) {
        /*
        $in_FEic     = key of Front End
        $in_COMP_id = key of component
        $in_fc_fe    = facility code for front end
        $in_fc_comp = facility code for component
        $statustype = status type for status location and notes
        $notes         = notes for status location and notes
        $UpdatedBy  = initials of person who updated the record
        */
        $this->dbConnection = site_getDbConnection();
        $this->FEid         = $in_FEid;
        $this->COMPid        = $in_COMPid;
        $this->fc_fe         = $in_fc_fe;
        $this->fc_comp         = $in_fc_comp;

        $this->frontEnd = new FrontEnd($in_FEid, $in_fc_fe, FrontEnd::INIT_NONE);

        $this->Component = new FEComponent(NULL, $in_COMPid, NULL, $in_fc_comp);

        $FE_Config_Original = $this->frontEnd->feconfig->keyId;

        //Check to see if this component is already in the front end
        $q = "SELECT keyId FROM FE_ConfigLink WHERE
        fkFE_ComponentFacility = $in_fc_comp AND
        fkFE_Components = $in_COMPid AND
        fkFE_ConfigFacility = $in_fc_fe AND
        fkFE_Config = " . $this->frontEnd->feconfig->keyId . ";";
        $r = mysqli_query($this->dbConnection, $q);
        $numrows = mysqli_num_rows($r);

        if ($numrows < 1) {


            /*
             * Temporarily disabled by request. May be reenabled later if deemed necessary.
             *
             * //Remove the component from front end, if necessary
             * $this->RemoveComponentFromFrontEnd($in_fc_comp, $in_COMPid, $UpdatedBy);
             */

            /* This is commented out unless we decide to start updating configuration when components are added.
            //Update FEConfig
            $this->FE_Config = new GenericTable();
            $this->FE_Config->NewRecord('FE_Config','keyFEConfig',$in_fc_fe,'keyFacility');
            $this->FE_Config->SetValue('fkFront_Ends'        ,$in_FEid);
            $this->FE_Config->SetValue('Description'        ,"Config for FE SN-" . $this->frontEnd->SN);
            $this->FE_Config->Update();

            //Reinitialize front end now that new config record exists for it
            $this->frontEnd->Initialize_FrontEnd($in_FEid, $in_fc_fe);
            */

            //Add to FE_ConfigLink table
            $this->FE_ConfigLink = GenericTable::NewRecord('FE_ConfigLink', 'keyId', $in_fc_fe, 'fkFE_ComponentFacility');
            $this->FE_ConfigLink->SetValue('fkFE_Components', $in_COMPid);
            $this->FE_ConfigLink->SetValue('fkFE_ConfigFacility', $in_fc_fe);
            $this->FE_ConfigLink->SetValue('fkFE_ComponentFacility', $in_fc_comp);
            $this->FE_ConfigLink->SetValue('fkFE_Config', $this->frontEnd->feconfig->keyId);
            $this->FE_ConfigLink->Update();



            //Update SLN for front end
            $NotesFE = "Added " . $this->Component->ComponentType;
            if ($this->Component->Band != '') {
                $NotesFE .= " Band" . $this->Component->Band . " ";
            }
            $NotesFE .= " SN" . $this->Component->SN;

            //Update SLN for component
            $NotesComp = "Added to FE SN-" . $this->frontEnd->SN;

            if ($notes == '') {
                $notes = $NotesComp;
            }

            if ($UpdateSLNcomponent == 1) {
                $this->UpdateStatusLocationAndNotes_Component($in_fc_comp, '', '', $notes, $this->Component->keyId, $UpdatedBy);
            }
        }
    }


    public function RemoveComponentFromFrontEnd($in_fc_comp, $in_COMPid = '', $UpdatedBy = '', $UpdateSLNfe = 1, $UpdateSLNcomponent = 1) {
        /*
        $in_COMP_id = key of component
        $in_fc_comp = facility code for component
        $UpdatedBy  = person who made the update
        */

        $this->dbConnection = site_getDbConnection();

        if (!isset($this->Component) || !($this->Component->keyId)) {
            $this->Component = new FEComponent(NULL, $in_COMPid, NULL, $in_fc_comp);
        }

        $l = new Logger('REMOVETEST.txt', 'a');
        $l->WriteLogFile("$in_fc_comp, $in_COMPid, $UpdatedBy, $UpdateSLNfe, $UpdateSLNcomponent");
        $l->WriteLogFile("fecfg= " . $this->Component->FEConfig);

        if ($this->Component->FEConfig != '') {
            //Update FEConfig to show that component was removed.
            $NotesFE = "Removed " . $this->Component->ComponentType;
            if ($this->Component->Band != '') {
                $NotesFE .= " Band" . $this->Component->Band . " ";
            }
            $NotesFE .= " SN" . $this->Component->SN;

            //New FEConfig
            $FE_Config = GenericTable::NewRecord('FE_Config', 'keyFEConfig', $in_fc_comp, 'keyFacility');
            $FE_Config->SetValue('fkFront_Ends', $this->Component->FEid);
            //$FE_Config->SetValue('Description'        ,"Config for FE SN-" . $this->Component->FESN);
            $FE_Config->SetValue('Description', $NotesFE);
            $FE_Config->Update();

            if ($UpdateSLNfe == 1) {
                $this->UpdateStatusLocationAndNotes_FE($in_fc_comp, '', '', $NotesFE, $FE_Config->keyId, $this->Component->FEConfig, $UpdatedBy);
            }

            //Update component
            $NotesComp = "Removed from FE SN-" . $this->Component->FESN;

            //UpdateStatusLocationAndNotes_Component(
            //$in_fc_comp,
            //$fkStatusType = '',
            //$fkLocationNames = '',
            //$Notes = '',
            //$fkFEComponents= '',
            //$UpdatedBy = '',
            //$lnk_Data='')
            if ($UpdateSLNcomponent == 1) {
                $this->UpdateStatusLocationAndNotes_Component($in_fc_comp, '', '', $NotesComp, $this->Component->keyId, $UpdatedBy);
            }
            //Wait so that the timestamps won't be identical for the next update.
            sleep(1);
            $this->Update_FEConfigLinksRemove($this->Component->FEConfig, $FE_Config->keyId, $in_fc_comp, $this->Component->keyId);
            $this->latest_feconfig = $FE_Config->keyId;
            unset($FE_Config_old);
            unset($FE_Config);
        }
    }

    public function UpdateStatusLocationAndNotes_FE($in_fc_fe, $fkStatusType = '', $fkLocationNames = '', $Notes = '', $fkFEConfig = '', $FE_Config_Original, $UpdatedBy = '', $lnk_Data = '') {
        /*
        $in_fc            = facility code
        $fkStatusType      = key of StatusStypes
        $fkLocationNames = key of Locations
        $notes              = notes for status location and notes
        $fkFEConfig      = key of FE_Config
        $UpdatedBy       = person who made the update
        */

        $this->dbConnection = site_getDbConnection();

        //         if ($fkFEComponents == ''){
        //             $fkFEComponents = '%';
        //         }
        //See if a previous SLN record exists. If so, make a new one, and copy the old record,
        //but change any values that are passed as arguments to this function.
        $q = "SELECT max(keyId) FROM FE_StatusLocationAndNotes WHERE
        fkFEConfig LIKE '$FE_Config_Original' AND
        keyFacility = $in_fc_fe;";
        $r = mysqli_query($this->dbConnection, $q);
        $keyId = ADAPT_mysqli_result($r, 0, 0);

        $sln = new GenericTable('FE_StatusLocationAndNotes', 'keyId', $in_fc_fe, 'keyFacility');
        $sln->NewRecord('FE_StatusLocationAndNotes', 'keyId', $in_fc_fe, 'keyFacility');

        if ($keyId != '') {
            $sln_old = new GenericTable('FE_StatusLocationAndNotes', $keyId, 'keyId', $in_fc_fe, 'keyFacility');
            $sln->SetValue('fkFEComponents', $sln_old->GetValue('fkFEComponents'));
            $sln->SetValue('fkConfigType', $sln_old->GetValue('fkConfigType'));
            $sln->SetValue('fkLocationNames', $sln_old->fkLocationNames);
            $sln->SetValue('fkStatusType', $sln_old->fkStatusType);
            //$sln->SetValue('lnk_Data' , FixHyperlinkForMySQL($sln_old->GetValue('lnk_Data')));
            $sln->SetValue('Updated_By', $sln_old->GetValue('Updated_By'));
        }

        if ($fkFEConfig == '') {
            //If a new FE Config record hasn't been created yet, then make one here.
            $fe = new FrontEnd(NULL, $in_fc_fe, $FE_Config_Original);
            $NewFEConfig = GenericTable::NewRecord('FE_Config', 'keyFEConfig', $in_fc_fe, 'keyFacility');
            $NewFEConfig->SetValue('fkFront_Ends', $fe->keyId);
            $NewFEConfig->Update();
            $fkFEConfig = $NewFEConfig->keyId;
        }
        $sln->SetValue('fkFEConfig', $fkFEConfig);


        if ($lnk_Data != '') {
            $sln->SetValue('lnk_Data', $lnk_Data);
        }
        if ($fkLocationNames != '') {
            $sln->SetValue('fkLocationNames', $fkLocationNames);
        }
        if ($fkStatusType != '') {
            $sln->SetValue('fkStatusType', $fkStatusType);
        }
        if ($UpdatedBy != '') {
            $sln->SetValue('Updated_By', $UpdatedBy);
        }
        $sln->SetValue('Notes', $Notes);
        $sln->SetValue('TS', Date('Y-m-d H:i:s'));
        $this->latest_feconfig = $sln->GetValue('fkFEConfig');
        $sln->Update();
        unset($sln);
        unset($sln_old);

        return $this->latest_feconfig;
    }

    public function UpdateStatusLocationAndNotes_Component($in_fc_comp, $fkStatusType = '', $fkLocationNames = '', $Notes = '', $fkFEComponents = '', $UpdatedBy = '', $lnk_Data = '') {
        /*
        $in_fc_comp            = facility code
        $fkStatusType      = key of StatusStypes
        $fkLocationNames = key of Locations
        $notes              = notes for status location and notes
        $fkFEConfig      = key of FE_Config
        */

        $this->dbConnection = site_getDbConnection();

        //See if a previous SLN record exists. If so, make a new one, and copy the old record,
        //but change any values that are passed as arguments to this function.
        $q = "SELECT max(keyId) FROM FE_StatusLocationAndNotes WHERE
        fkFEComponents LIKE '$fkFEComponents' AND
        keyFacility = $in_fc_comp;";
        $r = mysqli_query($this->dbConnection, $q);
        $keyId = ADAPT_mysqli_result($r, 0, 0);

        $sln = GenericTable::NewRecord('FE_StatusLocationAndNotes', 'keyId', $in_fc_comp, 'keyFacility');

        //Get location of front end, and use it for the component as well.
        $component_old = new FEComponent(NULL, $fkFEComponents, NULL, $in_fc_comp);

        $qloc = "SELECT fkLocationNames FROM FE_StatusLocationAndNotes
                 WHERE fkFEConfig = $component_old->FEConfig
                 ORDER BY keyId DESC;";
        $rloc = mysqli_query($this->dbConnection, $qloc);
        $locid = ADAPT_mysqli_result($rloc, 0, 0);
        if ($fkLocationNames == '') {
            $sln->SetValue('fkLocationNames', $locid);
        }

        if ($keyId != '') {
            $sln_old = new GenericTable('FE_StatusLocationAndNotes', $keyId, 'keyId', $in_fc_comp, 'keyFacility');
            $sln->SetValue('fkFEConfig', $sln_old->GetValue('fkFEConfig'));
            $sln->SetValue('fkLocationNames', $sln_old->fkLocationNames);
            $sln->SetValue('fkStatusType', $sln_old->fkStatusType);
            $sln->SetValue('lnk_Data', FixHyperlinkForMySQL($sln_old->GetValue('lnk_Data')));
            $sln->SetValue('Updated_By', $sln_old->GetValue('Updated_By'));
        }

        $sln->SetValue('fkFEComponents', $fkFEComponents);

        if ($fkLocationNames != '') {
            $sln->SetValue('fkLocationNames', $fkLocationNames);
        }


        if ($lnk_Data != '') {
            $sln->SetValue('lnk_Data', $lnk_Data);
        }

        if ($fkStatusType != '') {
            $sln->SetValue('fkStatusType', $fkStatusType);
        }
        if ($UpdatedBy != '') {
            $sln->SetValue('Updated_By', $UpdatedBy);
        }

        $sln->SetValue('Notes', $Notes);
        $sln->SetValue('TS', Date('Y-m-d H:i:s'));
        $sln->Update();
        $this->latest_feconfig = $sln->GetValue('fkFEConfig');

        unset($sln);
        unset($sln_old);
    }

    public function Update_FEConfigLinksAdd($in_feconfig_old, $in_feconfig_new, $in_fc_fe) {
        /*
         * $in_feconfig_old= Previous configuration (FEConfig.keyFEConfig)
         * $in_feconfig_new= New configuration (FEConfig.keyFEConfig)
         * $in_fc_fe = facility code of front end
         * $in_fc_comp = facility code of component
         * $in_component = key of component (FE_Components.keyId)
         */
        $this->dbConnection = site_getDbConnection();

        //Make new config link records for all components in this front end
        $q = "SELECT keyId FROM FE_ConfigLink
             WHERE
             fkFE_ConfigFacility = $in_fc_fe AND
             fkFE_Config = $in_feconfig_old;";
        $r = mysqli_query($this->dbConnection, $q);
        while ($row = mysqli_fetch_array($r)) {
            $fecl_old = new GenericTable('FE_ConfigLink', $row[0], 'keyId', $in_fc_fe, 'fkFE_ConfigFacility');

            $fecl_new = GenericTable::NewRecord('FE_ConfigLink', 'keyId');
            $fecl_new->SetValue('fkFEComponentFacility', $fecl_old->GetValue('fkFE_ComponentFacility'));
            $fecl_new->SetValue('fkFE_Components', $fecl_old->GetValue('fkFE_Components'));
            $fecl_new->SetValue('fkFEConfigFacility', $fecl_old->GetValue('fkFE_ConfigFacility'));
            $fecl_new->SetValue('fkFE_Config', $in_feconfig_new);
            $fecl_new->Update();
            unset($fecl_new);
            unset($fecl_old);
        }
    }

    public function Update_FEConfigLinksRemove($in_feconfig_old, $in_feconfig_new, $in_fc_fe, $comp_id) {
        /*
         * $in_feconfig_old= Previous configuration (FEConfig.keyFEConfig)
         * $in_feconfig_new= New configuration (FEConfig.keyFEConfig)
         * $in_fc_fe = facility code of front end
         * $in_fc_comp = facility code of component
         * $in_component = key of component (FE_Components.keyId)
         * $comp_id = id of component that has been removed
         */
        $this->dbConnection = site_getDbConnection();

        //Make new config link records for all components in this front end
        $q = "SELECT keyId FROM FE_ConfigLink
             WHERE
             fkFE_ConfigFacility = $in_fc_fe AND
             fkFE_Config = $in_feconfig_old;";
        $r = mysqli_query($this->dbConnection, $q);
        while ($row = mysqli_fetch_array($r)) {
            $fecl_old = new GenericTable('FE_ConfigLink', $row[0], 'keyId', $in_fc_fe, 'fkFE_ConfigFacility');

            if ($fecl_old->GetValue('fkFE_Components') != $comp_id) {
                $fecl_new = GenericTable::NewRecord('FE_ConfigLink', 'keyId');
                $fecl_new->SetValue('fkFEComponentFacility', $fecl_old->GetValue('fkFE_ComponentFacility'));
                $fecl_new->SetValue('fkFE_Components', $fecl_old->GetValue('fkFE_Components'));
                $fecl_new->SetValue('fkConfigType', $fecl_old->GetValue('fkConfigType'));
                $fecl_new->SetValue('fkFEConfigFacility', $fecl_old->GetValue('fkFE_ConfigFacility'));
                $fecl_new->SetValue('fkFE_Config', $in_feconfig_new);
                $fecl_new->Update();
                unset($fecl_new);
            }
            unset($fecl_old);
        }
    }
}
