<?php

class iniParser{

    private $IniFile;
    private $SafeFile;
    private $ParseClasses;

    public $KeysWithoutSections;
    public $KeysWithSections;


    public function __construct($FileName, $SafeFile = false){

        $this->IniFile = $FileName;
        $this->SafeFile = $SafeFile;

    }

    public function parseIni($SaveInClass = true){

        $FileHandle = file($this->IniFile);

        $CountLines = count($FileHandle);
        $Counter = 0;

        $NKeys = "";

        if ( $this->SafeFile ){

            $Counter += 2;
            $CountLines -= 2;
        }

        while ( $Counter < $CountLines ){

            $CurLine = $FileHandle[$Counter];

            $CurLineSplit = explode("=", $CurLine);

            $CurKey = $CurLineSplit[0];
            $CurValue = $CurLineSplit[1];
            if( $SaveInClass )
                $this->Keys[trim($CurKey)] = trim($CurValue);

            else
                $NKeys[trim($CurKey)] = trim($CurValue);

            $Counter++;
        }

        if( $SaveInClass )
            return $this->KeysWithoutSections;

        else
            return $NKeys;

    }

    public function parseIniWithSections($SaveInClass = true){

        $FileHandle = file($this->IniFile);

        $CountLines = count($FileHandle);
        $Counter = 0;

        $LastSection = "";

        $NKeys = "";

        if ( $this->SafeFile ){

            $CountLines -= 2;
            $Counter += 2;

        }

        while ( $Counter < $CountLines ){

            $CurLine = $FileHandle[$Counter];

            if ( strpos($CurLine, "[") == 1 ){

                $LastSection = $CurLine;
                continue;

            }

            $Explosion = explode("=", $CurLine);

            $CurKey = trim($Explosion[0]);
            $CurValue = trim($Explosion[1]);

            if ( $SaveInClass )
                $this->KeysWithSections[$LastSection][$CurKey] = $CurValue;

            else
                $NKeys[$LastSection][$CurKey] = $CurValue;


        }

        if ( $SaveInClass )
            return $this->KeysWithSections;
        else
            return $NKeys;

    }

};

?>