<?php

require_once('../SiteConfig.php');
require_once($site_config_main);

$dirPath = $main_write_directory;

if (!is_dir($dirPath)) {
    throw new InvalidArgumentException("$dirPath must be a directory");
}
if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
    $dirPath .= '/';
}
$files = glob($dirPath . 'Progress_*.txt', GLOB_MARK);
foreach ($files as $file) {
    echo $file . "<br>";
    unlink($file);
}

?>