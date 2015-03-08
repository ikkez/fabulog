<?php

namespace View;

abstract class Base {

    public $data = array();

    /**
     * create and return response content
     * @return mixed
     */
    abstract public function render();

}