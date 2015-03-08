<?php

namespace Controller;

class User extends Resource {


	public function __construct() {
		$mapper = new \Model\User();
		parent::__construct($mapper);
	}

	public function getSingle(\Base $f3, $params) {
		$this->response->data['SUBPART'] = 'user_edit.html';
		if (isset($params['id'])) {
			$this->resource->load(array('_id = ?', $params['id']));
			if ($this->resource->dry())
				$f3->error(404, 'User not found');
			$this->response->data['POST'] = $this->resource;
		}
	}

	public function getList(\Base $f3,$param) {
		$this->response->data['SUBPART'] = 'user_list.html';
		$this->response->data['content'] = $this->resource->find();
	}

	public function post(\Base $f3, $params) {
		$this->response->data['SUBPART'] = 'user_edit.html';
		$msg = \Flash::instance();
		if (isset($params['id'])) {
			// update existing
			$this->resource->load(array('_id = ?', $params['id']));
			if ($f3->get('HOST') == 'ikkez.de'
				&& !$this->resource->dry() && $this->resource->username == 'admin') {
				$msg->addMessage("You are not allowed to change the demo-admin",'danger');
				$f3->reroute('/admin/'.$params['module']);
				return;
			}
		}
		parent::post($f3,$params);
	}

	public function delete(\Base $f3, $params) {
		$this->resource->reset();
		$msg = \Flash::instance();
		if (isset($params['id'])) {
			$this->resource->load(array('_id = ?', $params['id']));
			if ($f3->get('HOST') == 'ikkez.de'
				&& !$this->resource->dry() && $this->resource->username == 'admin') {
				$msg->addMessage("You are not allowed to delete the demo-admin",'danger');
				$f3->reroute('/admin/'.$params['module']);
				return;
			}
			parent::delete($f3,$params);
		}
		$f3->reroute($f3->get('SESSION.LastPageURL'));
	}

}
