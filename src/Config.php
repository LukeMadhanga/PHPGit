<?php

namespace LukeMadhanga\Git;

/**
 * @author Luke Madhanga
 */
class Config {
    
    /**
     * The data for this Configuration
     * @var \stdClass
     */
    private $data;
    
    /**
     * Get the configuration data for a given Git repo
     * @param \LukeMadhanga\Git\Git $git The Git object from which to get configuration data
     */
    public function __construct(Git $git) {
        $this->data = new \stdClass;
        $this->parse($git->run('config --list'));
    }
    
    /**
     * Parse the configuration text
     * @param string $config The configuration text to parse
     */
    private function parse($config) {
        $lines = explode("\n", trim($config));
        foreach ($lines as $line) {
            $keyvalue = explode('=', $line);
            if (count($keyvalue) === 1) {
                $value = null;
            } else {
                $value = $keyvalue[1];
            }
            if (preg_match("/[0-9]/", $value)) {
                $value = +$value;
            } else if ($value === 'false') {
                $value = false;
            } else if ($value === 'true') {
                $value = true;
            }
            $keybits = explode('.', $keyvalue[0]);
            // The software needs to point to the right most key to set a value, but the software does not know what values to expect
            // Taking adavantage of object behaviour, keep resetting $obj until the software is deep enough to set the value
            $obj = $this->data;
            while ($key = array_shift($keybits)) {
                $obj->$key = $keybits ? (empty($obj->$key) ? new \stdClass : $obj->$key) : $value;
                $obj = $obj->$key;
            }
        }
    }
    
    /**
     * Get configuration data
     * @param string $what [optional] The subset of configurations to get, e.g. core, remote or branch, or all configurations if not set
     * @return \stdClass 
     */
    function get($what = null) {
        return $what ? $this->data->$what : $this->data;
    }
    
    /**
     * Get the remote url to which pushes are made
     * @param string $remote [optional] The name of the remote (usually origin) for which to get the URL
     * @return string|false The remote origin url
     */
    function getRemoteUrl($remote = 'origin') {
        return empty($this->data->remote->$remote->url) ? false : $this->data->remote->$remote->url;
    }
    
    /**
     * Get the user name 
     * @return string|false The user name of false if one has not been set
     */
    function username() {
        return empty($this->data->user->name) ? false : $this->data->user->name;
    }
    
    /**
     * Get the user email for this associated with this project
     * @return string|false The email address or false if one has not been set
     */
    function emailaddress() {
        return empty($this->data->user->email) ? false : $this->data->user->email;
    }
    
}
