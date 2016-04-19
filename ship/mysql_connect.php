<?php
/*
 * 2016-04-19 mtm Removed secret credentials from this file.  Get them from /conf/ instead.
 * 2016-03-28 jee just fixed mysql_error - this was mysql_connect_error
 *                also added pw here
 */
 

include("/home/safe.nrao.edu/conf/mtm-dbConnect.conf");

$dbname     = 'shipping';

$dbc = mysql_connect($dbserver, $dbusername, $dbpassword)
or die ('Could not connect to MySQL: ' . mysql_error() );
mysql_select_db($dbname, $dbc);

?>
