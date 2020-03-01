<?php
require_once(dirname(__FILE__) . '/../SiteConfig.php');

// adapted from http://stackoverflow.com/questions/7447472/how-could-i-display-the-current-git-branch-name-at-the-top-of-the-page-of-my-de

class GitInfo {
    private $head;          // the head string, like "ref: refs/heads/master"
    private $branch;        // the name of the current branch
    private $hash;          // the hash of the current branch
    private $masterHash;    // the hash of the master branch

    public function __construct() {
        global $files_root;
        // get the first line in the .git/HEAD file, typically like "ref: refs/heads/master":
        $this->head = file($files_root . "/.git/HEAD", FILE_USE_INCLUDE_PATH);
        $this->head = trim($this->head[0]);
        // get the current branch, the third part of the head:
        $this->branch = explode("/", $this->head, 3);
        $this->branch = trim($this->branch[2]);
        // get the hash of the current branch:
        $this->hash = file_get_contents(sprintf("$files_root/.git/refs/heads/%s", $this->branch));
        // get the hash of the master branch:
        $this->masterHash = file_get_contents("$files_root/.git/refs/heads/master");
    }
    public function getHeadString() {
        return $this->head;
    }
    public function getCurrentBranch() {
        return $this->branch;
    }
    public function getCurrentHash($numChars = 7) {
        $len = strlen($this->hash);
        return substr($this->hash, 0, ($numChars < $len) ? $numChars : $len);
    }
    public function getMasterHash($numChars = 7) {
        $len = strlen($this->masterHash);
        return substr($this->masterHash, 0, ($numChars < $len) ? $numChars : $len);
    }
}

?>