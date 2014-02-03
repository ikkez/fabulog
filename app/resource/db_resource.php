<?php

namespace Resource;

use Representation\Representation;

abstract class DB_Resource extends \DB\Cortex {

    // persistence settings
    protected $table, $db, $fieldConf;

    /** @var \Representation */
    protected $response;

    public function __construct()
    {
        $f3 = \Base::instance();
        $this->table = $f3->get('db_table_prefix').$this->table;
        $this->db = 'DB';
        parent::__construct();
    }

    public function setRepresentation($presenter) {
        $this->response = $presenter;
    }

    public function getSingle($f3,$param) {
        $f3->error(403);
    }

    public function getList($f3,$param) {
        $f3->error(403);
    }

    public function post($f3, $params)
    {
        $msg = \FlashMessage::instance();
        $this->reset();
        if (isset($params['id'])) {
            // update existing
            $this->load(array('_id = ?', $params['id']));
            if ($this->dry()) {
                $msg->addMessage("No record found with this ID.",'danger');
                $f3->reroute('/admin/'.$params['module']);
                return;
            }
        }
        $this->copyfrom('POST',array_keys($this->fieldConf));
        $requiredError = false;
        foreach ($this->fieldConf as $name => $conf) {
            if (isset($conf['required']) && $conf['required'] == TRUE
                && empty($this->{$name})
            ) {
                $requiredError = true;
            }
        }
        if($requiredError || $msg->hasMessages()) {
            if($requiredError)
                $msg->addMessage("Please fill out all required fields");
            $f3->copy('POST', 'form');
            $backend = new Backend();
            $backend->setRepresentation($this->response);
            $backend->getSingle($f3, $params);
            return;
        }

        $out = $this->save();
        if ($out) {
            // display the list again after saving
            $msg->addMessage("Successfully saved.", 'success');
            $f3->reroute('/admin/'.$params['module']);
        } else {
            $msg->addMessage("Operation failed.", 'danger');
        }
    }

    public function delete($f3, $params)
    {
        $this->reset();
        $msg = \FlashMessage::instance();
        if (isset($params['id'])) {
            $this->load(array('_id = ?', $params['id']));
            if ($this->dry()) {
                $msg->addMessage("No record found with this ID.", 'danger');
            } else {
                $this->erase();
                $msg->addMessage("Record deleted.", 'success');
            }
        }
        $f3->reroute($f3->get('SESSION.LastPageURL'));
    }

    public function updateProperty($filter,$key,$value)
    {
        $this->load($filter);
        if ($this->dry()) {
            return false;
        } else {
            while(!$this->dry()) {
                $this->set($key, $value);
                $this->save();
                $this->next();
            }
            return true;
        }
    }

}