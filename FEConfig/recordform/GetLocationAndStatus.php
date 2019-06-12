<?php

require_once(dirname(__FILE__) . '/../../SiteConfig.php');
require_once($site_dbConnect);

$statorloc=$_GET['which'];

if($statorloc=="1")
{
	$locations_query=mysqli_query($link, "SELECT keyId,Description FROM Locations");
	While($location= mysqli_fetch_object($locations_query))
	{
		$data[]=$location;
	}
}
else if($statorloc=="2")
{
	$status_query=mysqli_query($link, "SELECT keyStatusType, Status FROM StatusTypes");
	While($status=mysqli_fetch_object($status_query))
	{
		$data[]=$status;
	}
}
else if($statorloc=="3")
{
	$fe_query=mysqli_query($link, "SELECT MAX(keyFrontEnds) AS maxkey,SN FROM Front_Ends
						GROUP BY SN ORDER BY SN ASC");
	while($fe=mysqli_fetch_object($fe_query))
	{
		$data[]=$fe;
	}
}
else if($statorloc=="4")
{
	$user_query=mysqli_query($link, "SELECT Initials FROM Users ORDER BY Initials ASC;");
	While($users=mysqli_fetch_object($user_query))
	{
		$data[]=$users;
	}
}
echo json_encode($data);

?>