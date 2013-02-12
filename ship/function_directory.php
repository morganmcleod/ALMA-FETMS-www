<?php
//Function directory($directory,$filters)
/*
reads the content of $directory, takes the files that apply to
$filter
and returns an array of the filenames.
You can specify which files to read, for example
$files = directory(".","jpg,gif");
gets all jpg and gif files in this directory.
$files = directory(".","all");
gets all files.
*/
function directory($dir,$filters)
{
$handle=opendir($dir);
$files=array();
if ($filters == "all"){while(($file =
readdir($handle))!==false){$files[] = $file;}}
if ($filters != "all")
{
$filters=explode(",",$filters);
while (($file = readdir($handle))!==false)
{
for ($f=0;$f<sizeof($filters);$f++):
$system=explode(".",$file);
if ($system[1] == $filters[$f]){$files[] = $file;}
endfor;
}
}
closedir($handle);
return $files;
}
?>