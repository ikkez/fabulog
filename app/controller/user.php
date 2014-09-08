<?php

namespace Controller;

class User extends Resource {


	public function __construct()
	{
		$mapper = new \Model\User();
		parent::__construct($mapper);
	}

	public function getSingle($f3, $params)
	{
		if (isset($params['id']))
			$this->response->data['content'] = $this->resource->load(array('_id = ?', $params['id']));
		if ($this->resource->dry() && !$this->response instanceof \View\Backend)
			$f3->error(404, 'User not found');
	}

	public function getList($f3,$param) {
		$this->response->data = array(
			'content' => $this->resource->find(),
		);
	}
	public function post($f3, $params) {
		$msg = \FlashMessage::instance();
		if (isset($params['id'])) {
			// update existing
			$this->resource->load(array('_id = ?', $params['id']));
			if (!$this->resource->dry() && $this->resource->username == 'admin') {
				$msg->addMessage("You are not allowed to change the demo-admin",'danger');
				$f3->reroute('/admin/'.$params['module']);
				return;
			}
		}
		parent::post($f3,$params);
	}

	public function delete($f3, $params) {
		$this->resource->reset();
		$msg = \FlashMessage::instance();
		if (isset($params['id'])) {
			$this->resource->load(array('_id = ?', $params['id']));
			if (!$this->resource->dry() && $this->resource->username == 'admin') {
				$msg->addMessage("You are not allowed to delete the demo-admin",'danger');
				$f3->reroute('/admin/'.$params['module']);
				return;
			}
		}
		$f3->reroute($f3->get('SESSION.LastPageURL'));
	}

}
