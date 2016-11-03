<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');
require_once($site_classes . '/class.gitinfo.php');
$g = new GitInfo();
$br = $g->getCurrentBranch();

echo "<div id='footer'>";
echo    "<h3>FETMS Database by NRAO.  On branch: ";
echo    "<a href='https://github.com/morganmcleod/ALMA-FETMS-www' target='_blank'>";
echo    "<font color='#ffffff'>'$br' at " . $g->getCurrentHash();
if ($br != 'master')
    echo " (master at " . $g->getMasterHash() . ")";
echo "</font></a></h3></div>";
?>
