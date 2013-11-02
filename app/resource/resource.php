<?php

namespace Resource;

abstract class Resource {

    protected
        $response;

    public function setRepresentation(\Representation $presenter) {
        $this->response = $presenter;
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