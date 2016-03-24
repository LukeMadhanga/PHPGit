<?php

namespace LukeMadhanga\Git\Diff;

class DiffFile {
    /**
     * The file name (a)
     * @var string
     */
    private $afilename;
    
    /**
     * The file name (b). This will usually be the same as (a) unless the file was renameds
     * @var string
     */
    private $bfilename;
    
    /**
     * The new file mode
     * @var int
     */
    private $newfilemode = false;
    
    /**
     * The index of the file
     * @var stirng
     */
    private $index;
    
    /**
     * <b>True</b> if there is no new line at the end of the file
     * @var boolean
     */
    private $nonewline = false;
    
    /**
     * An array in the form<pre>
     *  [
     *      '-0,0 +1,2' => 'Changes',
     *      ...
     *  ]
     * @var array
     */
    private $changes = [];
    
    /**
     * Generate an object describing the changes in a file
     * @param string $filedata The diff data for a file
     * @throws \Exception
     */
    function __construct($filedata) {
        if (!$filedata) {
            throw new \Exception('No file data to initialise LukeMadhanga\Git\Diff\FullDiff');
        }
        $this->parse($filedata);
    }
    
    /**
     * To string magic method
     * @return string
     */
    function __toString() {
        $output = '';
        foreach ($this->changes as $line => $change) {
            $output .= "$line\n$change\n\n";
        }
        return $output;
    }
    
    /**
     * Parse the file data
     * @param string $filedata The filedata parsed
     */
    private function parse($filedata) {
        $lines = explode("\n", $filedata);
        $doneheader = false;
        $changesbyline = [];
        $key = null;
        foreach ($lines as $line) {
            if (strpos($line, '+++') === 0) {
                $doneheader = true;
                continue;
            }
            if ($doneheader) {
                if ($line === '\ No newline at end of file') {
                    $this->nonewline = true;
                    continue;
                }
                if (strpos($line, '@@') === 0) {
                    $key = str_replace(array('@@ ', ' @@'), '', $line);
                    continue;
                }
                if ($key) {
                    $changesbyline[$key][] = $line;
                }
            } else {
                if (!$this->afilename) {
                    // Split on a space unless escaped
                    $fromto = preg_split("/(?<!\\\)\s/", $line);
                    if ($fromto) {
                        $this->afilename = trim(str_replace('a/', '', $fromto[0]));
                        $this->bfilename = trim(str_replace('b/', '', $fromto[1]));
                    }
                    continue;
                }
                if (strpos($line, 'new file mode') === 0) {
                    $this->newfilemode = +str_replace('new file mode ', '', $line);
                } else if (strpos($line, 'index') === 0) {
                    $this->index = str_replace('index ', '', $line);
                }
            }
        }
        foreach ($changesbyline as $lineno => $changes) {
            $this->changes[$lineno] = implode("\n", $changes);
        }
    }
    
    /**
     * Get the name of this file
     * @param boolean $a <b>True</b> to return the filename for file (a)
     * @return string
     */
    function getFileName($a = true) {
        return $a ? $this->afilename : $this->bfilename;
    }
    
    /**
     * Get changes by line
     * @return array An array in the form<pre>
     *  [
     *      '-0,0 +1,2' => 'Changes',
     *      ...
     *  ]
     */
    function getChangesByLine() {
        return $this->changes;
    }
    
    /**
     * Get the file mode of this file
     * @return int|false The new file mode or <b>false</b>
     */
    function getFileMode() {
        return $this->newfilemode;
    }
    
    /**
     * The index of this file
     * @return string
     */
    function index() {
        return $this->index;
    }
    
    /**
     * Determine if there is no new line at the end of this file
     * @return boolean
     */
    function noNewLineAtEnd() {
        return $this->nonewline;
    }
}
