<?php


namespace Controller;

class Backend extends Base {

	/** @var \Controller\Base */
	protected $module;

	/**
	 * init the backend view, so the module controller can care about it
	 */
	public function beforeroute() {
		$module_name = \Base::instance()->get('PARAMS.module');
		$this->response = new \View\Backend();
		$this->response->data['LAYOUT'] = $module_name.'_layout.html';
		$this->module = $this->loadModule($module_name);
		$this->module->setView($this->response);
	}

	/**
	 * load module controller class
	 * @param $name
	 * @return bool
	 */
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
	 * pass method calls to module
	 * @param $name
	 * @param $args
	 * @return mixed
	 */
	public function __call($name,$args) {
		return call_user_func_array(array($this->module,$name),$args);
	}

	/**
	 * give the module control about the view
	 */
	public function afterroute() {
		$this->module->afterroute();
	}

}