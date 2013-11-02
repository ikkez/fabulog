<?php

namespace Representation;

abstract class Representation extends \Prefab {

    public $data = array();

    /**
     * create response content
     * @return mixed
     */
    abstract public function render();

}