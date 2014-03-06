<?php

namespace Controller;

class Tag extends Resource {

	public function __construct()
	{
		$mapper = new \Model\Tag();
		parent::__construct($mapper);
	}

	/**
	 * get a list of tags
	 */
	public function getList($f3, $params)
	{
		if (isset($params['slug'])) {
			$this->response = new \View\Frontend();
			//$this->load(array('slug = ?',$params['slug']));
			$post = new \Model\Post();
			//$posts = $post->find('')
			$post->filter('comments',array('approved = 1'));
			$post->has('tags',array('slug = ?',$params['slug']));
			$posts = $post->find(array('publish_date <= ? and published = 1',date('Y-m-d')),
				array('order'=>'publish_date desc'));
			//TODO: paginate
			//paginate($page - 1, 10,array('publish_date <= ? and published = 1',date('Y-m-d')),array('order'=>'publish_date desc'));
			$this->response->data = array(
				'content' => $posts,
				'SUBPART' => 'post_tag_list.html',
			);
			$this->response->render();
		}
		elseif ($f3->get('AJAX')) {
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
			header('Content-Type: application/json');
			echo json_encode($tags->getAll('title'));
		}

	}
}