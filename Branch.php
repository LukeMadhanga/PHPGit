<?php

namespace LukeMadhanga\Git;
/**
 * @author Luke Madhanga
 */
class Branch {
    
    /**
     * The git instance that this branch object was created for
     * @var LukeMadhanga\Git\Git
     */
    private $git;
    
    public function __construct(Git $git) {
        $this->git = $git;
    }
    
    public function __toString() {
        return $this->getCurrent()->name;
    }
    
    /**
     * Get a list of branches
     * @param boolean $fetch <b>True</b> to recalculate the branches if they have already been fetched
     * @return array An array of stdClass objects with the properties <b>name</b> and <b>iscurrent</b>
     */
    function getAll($fetch = false) {
        static $result = null;
        if ($fetch || $result === null) {
            $branches = $this->git->run('branch')->getLastMessage();
            $list = explode("\n", trim($branches));
            $output = [];
            foreach ($list as $item) {
                $iscurrent = false;
                if (preg_match("/^\*/", $item)) {
                    $iscurrent = true;
                    $item = preg_replace("/^\*/", '', $item);
                }
                $item = trim($item);
                $output[$item] = (object) array('name' => $item, 'iscurrent' => $iscurrent);
            }
            $result = $output;
        }
        return $result;
    }
    
    /**
     * Create a new branch. No action is performed if the branch has already been created
     * @param string $name The name of the branch to create
     * @param boolean $switchtonewbranch True to switch to the newly created branch
     * @throws \Exception
     */
    function create($name, $switchtonewbranch = false) {
        self::validateBranchName($name);
        $response = null;
        if (!$this->exists($name)) {
            $response = $this->git->run("branch {$name}");
            if ($response->hasErrors() && trim($response->getLastError()->message) === "Switched to branch '$name'") {
                // Not an error, being emitted on wrong branch
                $response = null;
            }
        }
        if ($switchtonewbranch && $this->getCurrent()->name !== $name) {
            $response = $this->git->run("checkout {$name}", [], $response);
        }
        if ($response && $response->hasErrors()) {
            throw new \Exception($response->getErrorMessage());
        }
    }
    
    /**
     * Determine if a branch exists
     * @param string $name The name of the branch to test
     * @return boolean
     */
    function exists($name) {
        $branches = $this->getAll();
        foreach ($branches as $branch) {
            if ($branch->name === $name) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Set the current branch
     * @param type $branchname
     */
    function setCurrent($branchname) {
        if ($this->getCurrent() != $branchname) {
            $response = $this->git->run("checkout {$branchname}");
            if (trim($response->getLastError()->message) !== "Switched to branch '{$branchname}'") {
                $response->throwExceptionIfError();
            }
        }
    }
    
    /**
     * Get the current branch
     * @return \stdClass An object with the properties <b>name</b> and <b>iscurrent</b>
     */
    function getCurrent() {
        $branches = $this->getAll(true);
        foreach ($branches as $branch) {
            if ($branch->iscurrent) {
                return $branch;
            }
        }
        return;
    }
    
    /**
     * Rename a given branch
     * @param string $to The new name
     * @param string $from <b>[optional]</b> The branch to be renamed. Defaults to the current branch
     * @return \LukeMadhanga\Git\Response
     */
    function rename($to, $from = null) {
        if (!$from) {
            $from = $this->getCurrent()->name;
        }
        self::validateBranchName($to);
        return $this->git->run("branch -m {$from} {$to}");
    }
    
    /**
     * Validate that a branch name conforms
     * @param string $name The name of the branch to test
     * @throws \Exception
     */
    private static function validateBranchName($name) {
        if (preg_match("/[^a-z0-9\-\._]/", $name)) {
            throw new \Exception("Illegal characters in Branch name {$name}. Only a-z, 0-9, -, _ and . allowed");
        }
    }
    
}