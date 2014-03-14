<?php

namespace Controller;

abstract class Base {

	protected
		$response;

	/**
	 * set a new view
	 * @param \View\Base $view
	 */
	public function setView(\View\Base $view) {
		$this->response = $view;
	}

	/**
	 * init the View
	 */
	public function beforeroute() {
		$this->response = \View\Backend::instance();
	}

	/**
	 * kick start the View, which finally creates the response
	 * based on our previously set content data
	 */
	public function afterroute() {
		if (!$this->response)
			trigger_error('No View has been set.');
		echo $this->response->render();
	}
}