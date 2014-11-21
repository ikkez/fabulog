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

			$f3->set('tag_cloud',$this->tagCloud());

			$post = new \Model\Post();
			$post->filter('comments',array('approved = ?',1));
			$post->has('tags',array('slug = ?',$params['slug']));
			$post->countRel('comments');
			$page = \Pagination::findCurrentPage();
			$posts = $post->paginate($page - 1, 10,
				array('publish_date <= ? and published = ?', date('Y-m-d'), true),
				array('order'=>'publish_date desc'));
			$this->response->data = array(
				'content' => $posts,
				'headline' => 'All Post by Tag: '.$params['slug'],
				'SUBPART' => 'post_list.html',
			);
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
			die(json_encode($return));
		}
	}

	public function tagCloud() {
		$tags = new \Model\Tag();
		$tags->filter('post',array('published = ? and publish_date <= ?', true, date('Y-m-d')));
		$tags->countRel('post');
		return $tags->find(null,array('order'=>'count_post desc'));
	}
}