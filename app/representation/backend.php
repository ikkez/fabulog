<?php

namespace Representation;

class Backend extends Representation {

    protected
        $template = 'templates/layout.html';

    public function __construct() {
        /** @var \Base $f3 */
        $f3 = \Base::instance();
        // save last visited URL
        if ($f3->exists('SESSION.CurrentPageURL')) {
            if ($f3->get('SESSION.CurrentPageURL') != $f3->get('PARAMS.0'))
                $f3->copy('SESSION.CurrentPageURL', 'SESSION.LastPageURL');
        } else
            $f3->set('SESSION.LastPageURL', '');
        $f3->set('SESSION.CurrentPageURL', $f3->get('PARAMS.0'));
    }

    public function setTemplate($filepath) {
        $this->template = $filepath;
    }

    public function render() {
        /** @var \Base $f3 */
        $f3 = \Base::instance();
        if ($f3->get('AJAX')) {
            // if this is an ajax request, respond a JSON string
            echo json_encode($this->$data);
        } else {
            // add template data to F3 hive
            if($this->data)
                $f3->mset($this->data);
            // change UI path to backend layout dir
            $f3->copy('BACKEND_UI','UI');
            // render base layout, the rest happens in template
            echo \Template::instance()->render($this->template);
        }
    }

}