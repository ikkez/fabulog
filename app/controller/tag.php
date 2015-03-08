<?php

namespace Controller;

class Tag extends Resource {

	public function __construct() {
		$mapper = new \Model\Tag();
		parent::__construct($mapper);
	}

	/**
	 * get a list of tags
	 * @param \Base $f3
	 * @param array $params
	 */
	public function getList(\Base $f3, $params) {
		$this->response = new \View\JSON();
		$tags = $this->resource->find();
		if (!$tags) {
			echo null;
			return;
		}
		$return = array();
		foreach ($tags as $tag) {
			$return[] = array(
				'value' => $tag->title,
				'tokens' => array($tag->title),
				'id' => $tag->_id,
			);
		}
		$this->response->data = $return;
	}

	public function tagCloud() {
		$tags = new \Model\Tag();
		$tags->filter('post',array('published = ? and publish_date <= ?', true, date('Y-m-d')));
		$tags->countRel('post');
		return $tags->find(null,array('order'=>'count_post desc'));
	}
}