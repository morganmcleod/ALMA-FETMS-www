<?php

//called from AddStatLoc.js
require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_classes . '/class.dboperations.php');
require_once($site_classes . '/class.fecomponent.php');
require_once($site_dbConnect);
$dbconnection = site_getDbConnection();

//Since new components are being created, the facility code ($fc)
//is not being passed in. It is defined in config_main.php.

$notes=$_POST['notes'];
$locval=$_POST['locval'];
$statval=$_POST['statval'];
$updatedby=$_POST['updatedby'];
$fekey=$_POST['fe'];

//Get facility code to use for lookup:
$q = "SELECT DefaultFacility FROM DatabaseDefaults";
$r = mysqli_query($dbconnection, $q);
$fc = ADAPT_mysqli_result($r,0,0);

//get front end config value
$get_feConfig_query=mysqli_query($dbconnection, "SELECT max(keyFEConfig) as MaxFEConfig FROM FE_Config
								 WHERE fkFront_Ends='$fekey' AND keyFacility='$fc'");
$feconfig=ADAPT_mysqli_result($get_feConfig_query,0,0);


//if config does not exist create one.
if (($feconfig=="") && ($fekey != ''))
{
	$fesn_query=mysqli_query($dbconnection, "SELECT SN FROM Front_Ends WHERE keyFrontEnds='$fekey'
	                          AND keyFacility='$fc'");
	$fesn=ADAPT_mysqli_result($fesn_query,0,"SN");

	mysqli_query($dbconnection, "INSERT INTO FE_Config(fkFront_Ends,Description,keyFacility)
							 VALUES('$fekey','Cold PAS Config for SN $fesn','$fc'")

	or die("Could not create frontend config" .mysqli_query($dbconnection, ));

	$get_feConfig_query=mysqli_query($dbconnection, "SELECT max(keyFEConfig) as MaxFEConfig FROM FE_Config
							WHERE fkFront_Ends='$fekey' AND keyFacility='$fc'");
	$feconfig=ADAPT_mysqli_result($get_feConfig_query,0,"MaxFEConfig");

}

//insert record into StatusLocationAndNotes table and FE_Config link.
if(isset($_COOKIE['compcookie']))
{

	//Get string for new SLN record. "Added WCA7-34, CCA6-12, etc"
	$newstring = "Added ";
	$commacount = 0;
	$newcomponents = 0;
	/***********************************************************/

    foreach ($_COOKIE['compcookie'] as $name => $value)
    {
		mysqli_query($dbconnection, "INSERT INTO FE_StatusLocationAndNotes
				(fkFEComponents,fkLocationNames,fkStatusType,Notes,Updated_By,keyFacility)
				Values('$value','$locval','$statval','$notes','$updatedby','$fc')")
		or die("Could not insert into StatusLocationAndNotes" . mysqli_error($dbconnection));


		if (($fekey != '')){
			//Only insert new FE_ConfigLink record if the user has specified a front end
			$feconfig_facility_query=mysqli_query($dbconnection, "SELECT keyFacility FROM FE_Config
									WHERE keyFEConfig='$feconfig'");
			$config_facility=ADAPT_mysqli_result($feconfig_facility_query,0,"keyFacility");

			mysqli_query($dbconnection, "INSERT INTO FE_ConfigLink(fkFE_Components,fkFE_Config,fkFE_ComponentFacility,
						fkFE_ConfigFacility)
						VALUES('$value','$feconfig','$fc','$config_facility')")
			or die("Could not insert into FE_ConfigLink" . mysqli_error($dbconnection));

			$component = new FEComponent();
			$component->Initialize_FEComponent($value,$fc);


			/*Compose string for FE SLN record************************/
			$newcomponents = 1;
			if ($commacount > 0){
				$newstring .= ", ";
			}
			$sn   = $component->GetValue('SN');
			$band = $component->GetValue('Band');

			if (strtolower($sn) == 'na'){
				$sn = '';
			}
			if (strtolower($sn) == 'n/a'){
				$sn = '';
			}
			if ($band > 0){
				$newstring .= $component->ComponentType->GetValue('Description') . " $band-$sn";
			}
			if ($band < 1){
				$newstring .= $component->ComponentType->GetValue('Description') . " $sn";
			}
			$commacount += 1;
			/**********************************************************/




		}

	}//end for each

	/*Update FE SLN record to show which components (if any) were added.****************************************************************/
		if ($newcomponents == 1){
			//$feconfig = $fe->feconfig->keyId;
			$dbopnewcomps = new DBOperations();
			$dbopnewcomps->UpdateStatusLocationAndNotes_FE($config_facility, '', '', $newstring, $feconfig, $feconfig, $updatedby, '');
			unset($dbopnewcomps);
		}
		/***********************************************************************************************************************************/
}


?>