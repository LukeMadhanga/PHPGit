# PHPGit #
*A PHP Library to run Git on your own server*


----------


Git is one of the world's most popular Version Control systems. I needed to be able to harness the power of Git for my web application and wrote a Library to do so


----------

##Prerequisites##

This library was designed for a 'Git on the Server' installation. It has not been tested with the github.com server. All the instructions assumes you understand this fact. The software has however been tested on a 'Git on the server' installation and works like a charm

####Connecting to the Server 

The trickiest part of the whole process is allowing PHP to perform `pull` and `push` commands. This is because the user that `Apache` runs as (`_www` on OS X, `www-data` on Ubuntu) doesn't have a home folder so cannot easily connect to your server. The way I went about it was to allow the Apache user to run `/path/to/git` as me, as I had already created password-less login to my server.

1. Edit the `sudoers` file. On Ubuntu, this will be in `/etc/sudoers`. At the end of the file, add `_www    ALL=(ALL) NOPASSWD: /path/to/only/script/i/can/run` to the end of the file. Change `_www` to the user that Apache runs as. **Note**, Adding `/path/to/only/script/i/can/run` only allows `_www` to run that ONE script and nothing else.
2. When setting the `binary` (we'll get on to this later), prepend `sudo -u name ` to the path to the git binary, e.g. `sudo -u name which git` (wrap `which git` in back-ticks). 
3. Ensure that you can log in to your server using password-less login. 
	- `ssh-keygen`
	- `ssh-copy-id name@host.com`

## Initialising

    require_once 'path/to/Git/Autoload.php';
    $git = new \LukeMadhanga\Git\Git('path/to/repo', 'sudo -u name `which git`', 'branch');

## Methods

#### add
*Stage a file to be committed*

Arguments

string | array `$list` An array or space-separated list of files to stage

**returns** `\LukeMadhanga\Git\Response`

---

#### branch
*Get a `\LukeMadhanga\Git\Branch` object describing the current branch*

No arguments

**returns** `\LukeMadhanga\Git\Branch`

---

#### commit
*Commit*

Arguments
string `$message` [optional] The message of the commit

array `$args` [optional] Arguments to pass to the `git commit` command as an associative array

array `$files` [optional] A list of files to commit

**Returns** `\LukeMadhanga\Git\Response`

---

#### diff
*Diff two branches*

Arguments

string `$brancha` The branch to compare \$branchb to

string `$branchb` [optional] The branch to compare \$brancha to. Defaults to `master`

boolean `$full` [optional] True (**Untested**) to perform a full diff. Defaults to `false`

string `$filename` [optional] The name of a file to diff. If omitted, all files will be diffed.

**returns** `\LukeMadhanga\Git\Diff\Diff`

---

#### getBinary
*Get the path to the binary in use*

No Arguments

**Returns** string The path to the binary in use

---

#### getConfiguration
*Get the configuration details for the current repository*

No Arguments

**Returns** `\LukeMadhanga\Git\Config`

---

#### pull
*Pull from the remote server*

Arguments

string `$remote` [optional] The name of the remote to use. Defaults to `origin`

**Returns** `\LukeMadhanga\Git\Response`

---

#### push
*Push commits to the server*

Arguments
string `$remote` [optional] The name of the remote to push to. Defaults to `origin`

string `$branch` [optional] The name of the branch to push to. Defaults to the current branch

**Returns** `\LukeMadhanga\Git\Response`

---

#### resetHead
*Unstage files for commit*

No Arguments

**Returns** `null`

---

#### run
*The main processing function that runs all `git xxx` commands*

Arguments
string `$command` The command to run, without the leading `git`. E.g. `pull origin master`. Use with caution.

array `$arguments` [optional] Arguments to pass to `STDIN`

\LukeMadhanga\Git\Response `$response` [optional] A response object if chaining responses

**Returns** `\LukeMadhanga\Git\Response`

---

#### status
*Get the status of the current repository*

Arguments
boolean `$remote` [optional] True to get the status of the remote branch

boolean `$incdirpath` [optional] True to include the directory path to the outputted files

**Returns** `\LukeMadhanga\Git\Status`
