<?php

namespace LukeMadhanga\Git;

/**
 * @author Luke Madhanga
 */
class Git {
    
    /**
     * The path to the git binary to use
     * @var string
     */
    private $binary;
    
    /**
     * The path to the git directory to work on
     * @var string
     */
    private $directory;
    
    /**
     * The git version of the given binary
     * @var string
     */
    private $gitversion;
    
    /**
     * The branch to use
     * @var \LukeMadhanga\Git\Branch
     */
    private $branch;
    
    /**
     * 
     * @param string $directory
     * @param string $binary
     * @param string $branch
     * @throws \Exception
     */
    function __construct($directory, $binary = null) {
        $this->directory = $directory;
        if (!is_readable($this->directory)) {
            // Could not read the directory specified
            $user = shell_exec('whoami');
            throw new \Exception("User {$user} cannot read the folder {$this->directory}");
        }
        $this->binary = $binary ? $binary : trim(shell_exec('which git'));
        // Test the binary to ensure that it is legit
        $version = $this->run('--version')->getLastMessage();
        if (!preg_match("/^git version [0-9\.]+/", $version)) {
            throw new \Exception("The git binary '{$this->binary}' returned a funny version. Make sure that the binary is correct");
        }
        $this->gitversion = preg_replace("/git version ([0-9\.]+)/", "$1", $version);
        $this->branch = new Branch($this);
    }
    
    /**
     * Stage a file for commit
     * @param array|string $list An array of files to stage for committing, or a space-separated list of files (e.g. 'file1 file2') or a
     *  single files
     * @return \LukeMadhanga\Git\Response
     * @throws \Exception
     */
    function add($list = '.') {
        $filelist = trim(implode(' ', (array) $list));
        if (!$filelist) {
            throw new \Exception('No files to add');
        }
        return $this->run("add $filelist");
    }
    
    /**
     * Reset the current git folder
     */
    function resetHead() {
        $this->run('reset');
    }
    
    /**
     * Diff this installation with the server
     * @param string $filename The name of the file to diff
     * @return \LukeMadhanga\Git\Diff\Diff Or <b>false</b> on failure
     */
    function diff($brancha, $branchb = 'master', $full = false, $filename = null) {
        $args = array('-w');
        if (!$full) {
            $args[] = '--name-status';
        }
        $args[] = $brancha;
        $args[] = $branchb;
        if ($filename) {
            $args[] = $filename;
        }
        $response = $this->run('diff ' . implode(' ', $args));
        $response->throwExceptionIfError();
        $data = trim();
        if ($data) {
            $diff = new Diff\Diff($data);
            $result = $diff;
        } else {
            $result = false;
        }
        return $result;
    }
    
    /**
     * Run a <code>git status</code> command
     * @param string $remote <b>True</b> to get the status of the remote master branch, or <b>false</b> to get the local status
     * @param string $incdirpath True to include the path specified that points to the current git folder
     * @return \LukeMadhanga\Git\Status
     */
    function status($remote = false, $incdirpath = false) {
        if ($remote) {
            $rawstatus = $this->run("diff --name-status -w {$this->branch()} master");
        } else {
            $rawstatus = $this->run("remote update && {$this->binary} status -s");
        }
        $lines = explode("\n", trim($rawstatus));
        if (!$lines && $rawstatus->hasErrors()) {
            $rawstatus->throwExceptionIfError();
        }
        if (!$remote) {
            array_shift($lines);
        }
        $status = new Status();
        $pathprefix = $incdirpath ? "{$this->directory}/" : '';
        foreach ($lines as $line) {
            if (!$line) {
                continue;
            }
            $action = $line[0];
            $line[0] = '';
            $line = trim($line);
            $type = null;
            switch ($action) {
                case 'A':
                    $type = 'new';
                    break;
                case 'M':
                    $type = 'modified';
                    break;
                case 'D':
                    $type = 'deleted';
                    break;
                case 'R':
                    $type = 'renamed';
                    break;
                case 'C':
                    $type = 'copied';
                    break;
                case 'U':
                    $type = 'unmerged';
                    break;
                default: 
                    continue;
            }
            if ($type) {
                $status->add($type, $pathprefix . $line);
            }
        }
        return $status;
    }

    /**
     * Run a <code>git status</code> command
     * @deprecated since version 0.0.1
     * @param string $incdirpath True to include the path specified that points to the current git folder
     * @return \LukeMadhanga\Git\Status
     */
    private function statusOld($incdirpath = false) {
        // Return array of updates/deletes/new or false
        // @todo cache
        $rawstatus = $this->run("remote update && {$this->binary} status");
        $diffobj = $this->diff();
        $diff = $diffobj ? $diffobj->getDiff() : false;
        $lines = explode("\n", $rawstatus);
        array_shift($lines);
        if (!$lines || $lines[0] === 'nothing to commit, working directory clean') {
            // There are no changes
            $error = $rawstatus->getLastError();
            if ($error) {
                // There was an error
                throw new \Exception("<pre>{$error->message}</pre>");
            }
            return false;
        }
        // @todo Staged and Unstaged
        $status = new Status();
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            } 
            $linebits = explode(':', $line);
            $pathprefix = $incdirpath ? "{$this->directory}/" : '';
            $type = null;
            switch ($linebits[0]) {
                case 'new file':
                    $type = 'new';
                    break;
                case 'modified':
                    $type = 'modified';
                    break;
                case 'deleted':
                    $type = 'deleted';
                    break;
                case 'renamed':
                    $type = 'renamed';
                    break;
                default: 
                    continue;
            }
            if ($type) {
                $filename = trim($linebits[1]);
                $linediff = isset($diff[$filename]) ? $diff[$filename] : null;
                $status->add($type, $pathprefix . $filename, $linediff);
            }
        }
        return $status;
    }
    
    /**
     * Run a <code>git commit</code> command
     * @param string $message The message to go with the commit. Can be null if valid arguments are passed. Will be overridden if there
     *  is an argument keyed by '-m' or '--message' in the $arg array
     * @param array $args A key-value pair of commit commands, e.g. ['-m' => 'message', ...]. See https://git-scm.com/docs/git-commit
     * @param array $files A space-separated list or array of files in the working directory to commit
     * @return \LukeMadhanga\Git\Response The response object. Run Response::successful() to determine whether the command executed
     *  successfully.
     * @throws \Exception
     */
    function commit($message = null, array $args = [], $files = null) {
        if (!$message && !$args) {
            throw new \Exception('A message or another type of argument is required for a commit');
        }
        if ($message && !isset($args['-m']) && !isset($args['--message'])) {
            $args['-m'] = $message;
        }
        // Build up an argument list, making sure to escapeshellargs
        $arglist = '';
        foreach ($args as $type => $value) {
            $separator = ' ';
            if (strpos('--', trim($type))) {
                $separator = '=';
            }
            $arglist .= " {$type}{$separator}" . escapeshellarg($value);
        }
        return $this->run("commit $arglist" . ($files ? ' ' . escapeshellarg(implode(' ', (array) $files)) : ''));
    }
    
    /**
     * Pull from a specified remote
     * @param string $branch [optional] The name of the branch to pull from. Defaults to the current branch
     * @param string $remote [optional] The name of the remote to set
     * @return \LukeMadhanga\Git\Response
     * @throws \Exception
     */
    function pull($branch = null, $remote = 'origin') {
        if (!$branch) {
            $branch = $this->branch();
        }
        $result = $this->run("pull {$remote} {$branch}");
        if (!preg_match("/^From/", trim($result->getErrorMessage()))) {
            // Data sometimes gets sent in the 
            $result->throwExceptionIfError();
        }
        return $result;
    }
    
    /**
     * Push to a specified remote branch
     * @param string $remote The remote to push to
     * @param string $branch The name of the branch to push to
     * @return \LukeMadhanga\Git\Response The response object
     */
    function push($remote = 'origin', $branch = null) {
        if (!$branch) {
            // Default the branch to this current branch
            $branch = $this->branch();
        }
        return $this->run("push {$remote} {$branch}");
    }
    
    /**
     * Get the path to the git binary
     * @return string
     */
    function getBinary() {
        return $this->binary;
    }
    
    /**
     * Get a Branch object describing the current branch
     * @return \LukeMadhanga\Git\Branch
     */
    function branch() {
        return $this->branch;
    }
    
    /**
     * Get the configuration data for this project
     * @return \LukeMadhanga\Git\Config
     */
    function getConfiguration() {
        return new Config($this);
    }
    
    /**
     * Run a command on the terminal. <b>USE WITH CAUTION</b>. Make sure arguments are properly escaped and verify any input as a simple 
     *  ';' or '&&' in a command can allow a hacker to break your server
     * @param string $command The command to run
     * @param array $arguments [optional] A list of arguments to supply to STDIN when prompted by the programme initiated by $command
     * @param \LukeMadhanga\Git\Response $response
     * @return \LukeMadhanga\Git\Response
     */
    function run($command, array $arguments = [], Response $response = null) {
        $pipes = [];
        $descriptorspec = array(
           array('pipe', 'r'),  // STDIN
           array('pipe', 'w'),  // STDOUT
           array('pipe', 'w'),  // STDERR
        );
        $process = proc_open("{$this->binary} " . trim($command), $descriptorspec, $pipes, $this->directory);
        foreach ($arguments as $arg) {
            // Write each of the supplied arguments to STDIN and ensure that it finishes with one trailing 
            fwrite($pipes[0], (preg_match("/\n(:?\s+)?$/", $arg) ? $arg : "{$arg}\n"));
        }
        if (!$response) {
            $response = new Response;
        }
        $response->addCompletedCommand(stream_get_contents($pipes[1]), $command, $arguments);
        $error = stream_get_contents($pipes[2]);
        if ($error) {
            $response->addError($error, $command, $arguments);
        }
        // Make sure that each pipe is closed to prevent a lockout
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }
        proc_close($process);
        return $response;
    }
}
