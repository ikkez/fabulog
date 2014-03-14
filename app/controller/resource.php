<?php

namespace Controller;

abstract class Resource extends Base {

	// mapper
	protected $resource;

	/** @var \View\Base */
	protected $response;

	public function __construct(\Model\Base $model)
	{
		$this->resource = $model;
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
		$this->resource->reset();
		if (isset($params['id'])) {
			// update existing
			$this->resource->load(array('_id = ?', $params['id']));
			if ($this->resource->dry()) {
				$msg->addMessage("No record found with this ID.",'danger');
				$f3->reroute('/admin/'.$params['module']);
				return;
			}
		}
		$fields = $this->resource->getFieldConfiguration();
		$this->resource->copyfrom('POST', array_keys($fields));
		$requiredError = false;
		foreach ($fields as $name => $conf) {
			if (isset($conf['required']) && $conf['required'] == TRUE
				&& empty($this->resource->{$name})
			) {
				$requiredError = true;
			}
		}
		if($requiredError || $msg->hasMessages()) {
			if($requiredError)
				$msg->addMessage("Please fill in all required fields");
			$f3->copy('POST', 'form');
			$backend = new Backend();
			$backend->setView($this->response);
			$backend->getSingle($f3, $params);
			return;
		}

		$out = $this->resource->save();
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
		$this->resource->reset();
		$msg = \FlashMessage::instance();
		if (isset($params['id'])) {
			$this->resource->load(array('_id = ?', $params['id']));
			if ($this->resource->dry()) {
				$msg->addMessage("No record found with this ID.", 'danger');
			} else {
				$this->resource->erase();
				$msg->addMessage("Record deleted.", 'success');
			}
		}
		$f3->reroute($f3->get('SESSION.LastPageURL'));
	}

}