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

	public function beforeroute() {
		$this->response = \View\Backend::instance();
	}

	public function afterroute()
	{
		if (!$this->response)
			trigger_error('No View has been set.');
		echo $this->response->render();
	}

}
