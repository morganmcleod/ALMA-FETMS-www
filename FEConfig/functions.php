<?php
class setArray {
	public $users;

    function setUsers() {
        $this->users = array(
            1 => "CL", 2 => "DK", 3 => "DS", 4 => "DN", 5 => "FJ", 6 => "JE", 7 => "ML", 8 => "MJL",
            9 => "MS", 10 => "MR", 11 => "NH", 12 => "PW", 13 => "RG"
        );

        foreach ($this->users as $key => $value) {
			$user_block .= "<option value=$value>$value";
		}
		return $user_block;
	}
}

function getCellNum($whole) {
//Returns the Number part of the Cell to populate the dropdown box. ex: returns 10 from A10
//where $whole=A10 and $part2=10
    $part1 = $whole[1] . "" . $whole[2];
    return ($part1);
}
function getCellChar($whole) {
//Returns the character part of the Cell to populate the dropdown box. ex: returns A from A10
//where $whole=A10 and $char=A
    $char = $whole[0];
	return $char;
}
function modifyLink($link) {
//2008-10-24 dn: removed explode and implode functions and put preg_replace instead.
//Replaces the back slashes in $link with frontslashes and replaces the drives z: or y:(if any) with $replace
//Returns the modified link.
    $path = preg_replace('~[\\\\/]+~', '/', $link);
    $find = "/^Z:|Y:|S:|E:|F:/";
    $replace = "/cvfiler.nrao.edu/cv-cdl-sis";
    $path1 = preg_replace($find, $replace, $path);
    $ModifiedLink = "/" . $path1;
    return ($ModifiedLink);
}
function escapechars($notes) {
//Escapes the special characters in $notes and returns the escaped string.
    $notes1 = addslashes($notes);
	return $notes1;
}
?>
