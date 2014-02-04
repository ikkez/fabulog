<?php

namespace Controller;

abstract class Base {

    protected
        $response;

    public function setView(\View\Base $view) {
        $this->response = $view;
    }

    public function getSingle($f3,$param) {
        $f3->error(403);
    }

    public function getList($f3,$param) {
        $f3->error(403);
    }

    public function post($f3,$param) {
        $f3->error(403);
    }

    public function delete($f3,$param) {
        $f3->error(403);
    }
}