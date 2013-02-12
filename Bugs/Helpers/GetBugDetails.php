<?php
include "../dbConnect.php";

$bugkey=$_GET['bugkey'];
$nodeDepth=$_GET['nodeDepth'];

if($nodeDepth==2)
{
	$notes_query=mysql_query("SELECT Notes FROM Tasks WHERE keyTasks='$bugkey'")
	or die("error" .mysql_error());
}
else if($nodeDepth==3)
{
	$notes_query=mysql_query("SELECT Notes FROM TaskEvents WHERE keyTaskEvents='$bugkey'")
	or die("error" .mysql_error());
}
	
while($note=mysql_fetch_array($notes_query))
{
	$bugnote=$note['Notes'];
	$bugnote=utf8_encode($bugnote);
	$notes[]=$bugnote;
}

echo json_encode($notes);
	
?>