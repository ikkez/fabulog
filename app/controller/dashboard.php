<?php


namespace Controller;

class Dashboard extends Base {

    protected
        $response;

    /**
     * init the View
     */
    public function beforeroute() {
        $this->response = new \View\Backend();
    }

    /**
     * fetch data for an overview page
     */
    public function main($f3) {
        $this->response->data['LAYOUT'] = 'overview.html';
    }

}