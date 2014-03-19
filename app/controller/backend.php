<?php


namespace Controller;

class Backend extends Base {

	protected function loadModule($name) {
		$class = '\Controller\\'.ucfirst($name);
		if(!class_exists($class)) {
			trigger_error('unknown module');
			return false;
		}
		/** @var \Controller\Resource $module */
		return new $class();
	}

	/**
	 * create a response that displays a list of module records
	 */
	public function getList($f3,$params) {
		$module = $this->loadModule($params['module']);
		$module->setView($this->response);
		$module->getList($f3,$params);
		$this->response->data['SUBPART'] = $params['module'].'_list.html';
		$this->response->data['LAYOUT'] = $params['module'].'_layout.html';
	}

	/**
	 * return an create/edit form for a given module
	 */
	public function getSingle($f3,$params) {
		$module = $this->loadModule($params['module']);
		$module->setView($this->response);
		$module->getSingle($f3, $params);
		$this->response->data['SUBPART'] = $params['module'].'_edit.html';
		$this->response->data['LAYOUT'] = $params['module'].'_layout.html';
	}


}