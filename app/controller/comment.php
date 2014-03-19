<?php

namespace Controller;

class Comment extends Resource {

	public function __construct()
	{
		$mapper = new \Model\Comment();
		parent::__construct($mapper);
	}

	public function approve($f3,$params)
	{
		if($this->resource->updateProperty(array('_id = ?', $params['id']),'approved',1)) {
			\FlashMessage::instance()->addMessage('Comment approved', 'success');
		} else {
			\FlashMessage::instance()->addMessage('Unknown Comment ID', 'danger');
		}
		$f3->reroute($f3->get('SESSION.LastPageURL'));
	}

	public function reject($f3,$params)
	{
		if ($this->resource->updateProperty(array('_id = ?', $params['id']), 'approved', 2)) {
			\FlashMessage::instance()->addMessage('Comment rejected', 'success');
		} else {
			\FlashMessage::instance()->addMessage('Unknown Comment ID', 'danger');
		}
		$f3->reroute($f3->get('SESSION.LastPageURL'));
	}

	public function getSingle($f3,$params)
	{
		if (isset($params['id'])) {
			$this->response->data['content'] = $this->resource->load(array('_id = ?',$params['id']));
			if(!$this->resource->dry())
				return true;
		}
		\FlashMessage::instance()->addMessage('Unknown Comment ID', 'danger');
		$f3->reroute($f3->get('SESSION.LastPageURL'));
	}

	/**
	 * display list of comments
	 */
	public function getList($f3, $params)
	{
		$this->response->data['SUBPART'] = 'comment_list.html';
		$this->response->data['LAYOUT'] = 'comment_layout.html';
		$filter = array('approved = ?',0); // new
		if (isset($params['viewtype'])) {
			if ($params['viewtype'] == 'published')
				$filter = array('approved = ?',1);
			elseif ($params['viewtype'] == 'rejected')
				$filter = array('approved = ?',2);
			elseif(!empty($params['viewtype'])) {
				// display all comments for a specified post
				$filter = array('post = ?',$params['viewtype']);
			}
		}

		$page = \Pagination::findCurrentPage();
		$limit = 3;
		$this->response->data['content'] =
			$this->resource->paginate($page-1,$limit,$filter, array('order' => 'datetime desc'));
	}

}