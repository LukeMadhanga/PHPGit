<?php

namespace LukeMadhanga\Git;
/**
 * @author Luke Madhanga
 */
class Response {
    
    /**
     * An array of stdClass objects describing errors
     * @var array
     */
    private $errors;
    
    /**
     * An array of stdClass objects describing completed commands
     * @var array
     */
    private $completedcommands = [];
    
    /**
     * __toString magic method
     * @return string
     */
    function __toString() {
        return $this->getLastMessage();
    }
    
    /**
     * 
     * @param type $response
     * @param type $command
     * @param type $arguments
     * @return \LukeMadhanga\Git\Response
     */
    function addCompletedCommand($response, $command = null, $arguments = null) {
        $this->completedcommands[] = (object) array(
            'response' => $response,
            'command' => $command,
            'arguments' => $arguments,
        );
        return $this;
    }
    
    /**
     * 
     * @param type $msg
     * @param type $command
     * @param type $arguments
     * @return \LukeMadhanga\Git\Response
     */
    function addError($msg, $command = null, $arguments = null) {
        $this->errors[] = (object) array(
            'message' => $msg,
            'command' => $command,
            'arguments' => $arguments,
        );
        return $this;
    }
    
    /**
     * Get the last message
     * @return string The message or <b>false</b> on failure
     */
    function getLastMessage() {
        $msg = end($this->completedcommands);
        return $msg ? $msg->response : false;
    }
    
    /**
     * <b>True</b> if there were no errors in processing
     * @return boolean
     */
    function successful() {
        return empty($this->errors);
    }
    
    /**
     * Get the last error
     * @return stdClass|false
     */
    function getLastError() {
        return end($this->errors);
    }
    
    /**
     * Determine whether errors were encountered
     * @return boolean
     */
    function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Get all error message
     * @return string The error messages concatenated by two new lines
     */
    function getErrorMessage() {
        $output = '';
        foreach ($this->errors as $error) {
            $output .= "{$error->message}\n\n";
        }
        return $output;
    }

    /**
     * Get all errors encountered
     * @return array An array of stdClass objects with the properties message, command and arguments
     */
    function getErrors() {
        return $this->errors;
    }
    
    /**
     * Throw an exception if there an error processing the request. <b>Exceptions are not thrown by default because the git binary
     *  sometimes sends information in STDERR. In these instances, higher level error checking is required
     * @throws \Exception
     */
    function throwExceptionIfError() {
        if ($this->hasErrors()) {
            throw new \Exception($this->getErrorMessage());
        }
    }
}
