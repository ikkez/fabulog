<?php

namespace View;

class JSON extends Base {

	public function render() {
		header('Content-Type: application/json');
		return json_encode($this->data);
	}

}