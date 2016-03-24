<?php

namespace LukeMadhanga\Git;

/**
 * @author Luke Madhanga
 */
class Status {
    
    /**
     *
     * @var array
     */
    private $data = [];
    
    /**
     * 
     * @return array
     */
    static function getTypes() {
        return ['new', 'modified', 'deleted', 'renamed', 'copied', 'unmerged'];
    }
    
    /**
     * 
     * @param type $type
     * @return array One of the 
     * @throws \Exception
     */
    function get($type) {
        if (in_array($type, self::getTypes())) {
            return isset($this->data[$type]) ? $this->data[$type] : [];
        }
        throw new \Exception("Unknown type $type");
    }
    
    /**
     * 
     * @return array
     */
    function getByType() {
        return $this->data;
    }
    
    /**
     * 
     * @param type $type
     * @param type $filename
     * @param type $diff Description
     * @throws \Exception
     */
    function add($type, $filename, $diff = null) {
        if (in_array($type, $this->getTypes())) {
            $this->data[$type][] = (object) ['filename' => $filename, 'diff' => $diff];
            return;
        }
        throw new \Exception("Unknown type $type");
    }
    
}
