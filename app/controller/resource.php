<?php

namespace Controller;

abstract class Resource extends Base {

	// mapper
	protected $resource;

	/** @var \View\Base */
	protected $response;

	/**
	 * @param \Model\Base $model
	 */
	public function __construct(\Model\Base $model) {
		$this->resource = $model;
	}

	/**
	 * get single record
	 * @param \Base $f3
	 * @param array $params
	 */
	public function getSingle(\Base $f3,$params) {
		$f3->error(403);
	}

	public function edit(\Base $f3,$params) {
		if ($f3->get('VERB') == 'POST')
			$this->post($f3,$params);
		elseif ($f3->get('VERB') == 'GET')
			$this->getSingle($f3,$params);
	}

	/**
	 * get collection of records
	 * @param \Base $f3
	 * @param array $params
	 */
	public function getList(\Base $f3,$params) {
		$f3->error(403);
	}

	/**
	 * create / update a record
	 * @param \Base $f3
	 * @param array $params
	 */
	public function post(\Base $f3, $params) {
		$flash = \Flash::instance();
		$this->resource->reset();
		if (isset($params['id'])) {
			// update existing
			$this->resource->load(array('_id = ?', $params['id']));
			if ($this->resource->dry()) {
				$flash->addMessage('No record found with this ID.','danger');
				$f3->reroute('/admin/'.$params['module']);
				return;
			}
		}
		$fields = $this->resource->getFieldConfiguration();
		$this->resource->copyfrom('POST', array_keys($fields));
		$this->resource->save();
		if (!$f3->get('ERROR')) {
			// display the list again after saving
			$flash->addMessage('Successfully saved.', 'success');
			$f3->reroute('/admin/'.$params['module']);
		}
	}

	/**
	 * delete a record
	 * @param \Base $f3
	 * @param array $params
	 */
	public function delete(\Base $f3, $params) {
		$this->resource->reset();
		$flash = \Flash::instance();
		if (isset($params['id'])) {
			$this->resource->load(array('_id = ?', $params['id']));
			if ($this->resource->dry()) {
				$flash->addMessage('No record found with this ID.', 'danger');
			} else {
				$this->resource->erase();
				$flash->addMessage("Record deleted.", 'success');
			}
		}
		$f3->reroute($f3->get('SESSION.LastPageURL'));
	}

}