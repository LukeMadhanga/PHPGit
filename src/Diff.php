<?php

namespace LukeMadhanga\Git\Diff;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * @author Luke Madhanga
 */
class Diff {
    
    /**
     *
     * @var array An array in the form<pre>
     *  [
     *      filename => LukeMadhanga\Git\Diff\FullDiff,
     *      ...
     *  ]
     */
    private $data;
    
    public function __construct($data, $full = false) {
        if (!$data) {
            throw new \Exception('No data passed to diff');
        }
        $byfile = explode('diff --git ', $data);
        foreach ($byfile as $filedata) {
            if ($filedata) {
                $file = new FullDiff($filedata);
                $key = $file->getFileName();
                $this->data[$key] = $file;
            }
        }
    }
    
    /**
     * Get the diff data
     * @return array An array in the form<pre>
     *  [
     *      filename => LukeMadhanga\Git\Diff\FullDiff,
     *      ...
     *  ]
     */
    function getDiff() {
        return $this->data;
    }
    
}
