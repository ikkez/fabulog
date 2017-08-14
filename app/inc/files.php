<?php

class Files extends \Prefab {

	function upload($f3,$params) {
		$result = \Web::instance()->receive(function ($file) {
			$allowed_types = array('image/png', 'image/jpeg', 'image/gif', 'image/bmp');
			return in_array($file['type'], $allowed_types);
		},
			true, // overwrite
			true // rename to UTF-8 save filename
		);
		echo json_encode($result);
	}
}