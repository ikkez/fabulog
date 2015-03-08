<?php

namespace Controller;

class Comment extends Resource {

	public function __construct() {
		$mapper = new \Model\Comment();
		parent::__construct($mapper);
	}

	public function beforeroute() {
		$this->response = new \View\Backend();
		$this->response->data['LAYOUT'] = 'comment_layout.html';
	}

	/**
	 * @param \Base $f3
	 * @param $params
	 */
	public function approve(\Base $f3, $params) {
		if($this->resource->updateProperty(array('_id = ?', $params['id']),'approved',1)) {
			\Flash::instance()->addMessage('Comment approved', 'success');
		} else {
			\Flash::instance()->addMessage('Unknown Comment ID', 'danger');
		}
		$f3->reroute($f3->get('SESSION.LastPageURL'));
	}

	/**
	 * @param \Base $f3
	 * @param $params
	 */
	public function reject(\Base $f3, $params) {
		if ($this->resource->updateProperty(array('_id = ?', $params['id']), 'approved', 2)) {
			\Flash::instance()->addMessage('Comment rejected', 'success');
		} else {
			\Flash::instance()->addMessage('Unknown Comment ID', 'danger');
		}
		$f3->reroute($f3->get('SESSION.LastPageURL'));
	}

	/**
	 * @param \Base $f3
	 * @param array $params
	 * @return bool
	 */
	public function getSingle(\Base $f3,$params) {
		$this->response->data['SUBPART'] = 'comment_edit.html';

		if (isset($params['id'])) {
			$this->response->data['comment'] = $this->resource->load(array('_id = ?',$params['id']));
			if(!$this->resource->dry())
				return true;
		}
		\Flash::instance()->addMessage('Unknown Comment ID', 'danger');
		$f3->reroute($f3->get('SESSION.LastPageURL'));
	}

	/**
	 * display list of comments
	 * @param \Base $f3
	 * @param array $params
	 */
	public function getList(\Base $f3, $params) {
		$this->response->data['SUBPART'] = 'comment_list.html';
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
		$limit = 10;
		$this->response->data['comments'] =
			$this->resource->paginate($page-1,$limit,$filter, array('order' => 'datetime desc'));
	}

}