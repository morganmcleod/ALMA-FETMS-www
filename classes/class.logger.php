<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');

class Logger{
    var $logfilename;
    var $logfilebasename;
    var $filehandle;

function __construct($in_logfilename = '', $fileaction = 'w') {
    $this->OpenLogFile($in_logfilename, $fileaction);
}

public function OpenLogFile($in_logfilename = '', $fileaction = 'w'){

    include(site_get_config_main());

    if (!file_exists($log_write_directory)){
        mkdir($log_write_directory);
    }
    $this->logfilename = $log_write_directory . $in_logfilename;
    if ($in_logfilename == ''){
        $this->logfilename = $log_write_directory . "log_" . date('Y_m_d_H_i_s') . ".txt";
    }
    $this->logfilebasename=basename(strtolower($this->logfilename),".txt");
    $this->filehandle = fopen($this->logfilename,$fileaction);
}

public function WriteLogFile($in_writestring){
    $writestring = $this->udate('Y-m-d H:i:s.u') . "\t" . $in_writestring . "\r\n";
    fwrite($this->filehandle, $writestring);
}

public function NewLine(){
    fwrite($this->filehandle, "\r\n");
}

function __destruct() {
    fclose($this->filehandle);
}

function udate($format, $utimestamp = null)
{
    if (is_null($utimestamp))
        $utimestamp = microtime(true);

    $timestamp = floor($utimestamp);
    $milliseconds = round(($utimestamp - $timestamp) * 1000000);

    return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
}

function delete(){
    if(file_exists($this->logfilename)){
        unlink($this->logfilename);
    }
}

}//end class Logger
