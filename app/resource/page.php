<?php

namespace Resource;

class Page {

    public function get() {
        echo \Template::instance()->render('templates/index.html');
    }

}