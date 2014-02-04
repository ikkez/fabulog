<?php

namespace View;

abstract class Base extends \Prefab {

    public $data = array();

    /**
     * create response content
     * @return mixed
     */
    abstract public function render();

}