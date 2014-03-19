<?php
/**
    post.php
    
    The contents of this file are subject to the terms of the GNU General
    Public License Version 3.0. You may not use this file except in
    compliance with the license. Any of the license terms and conditions
    can be waived if you get permission from the copyright holder.
    
    Copyright (c) 2013 ~ ikkez
    Christian Knuth <ikkez0n3@gmail.com>
 
        @version 0.1.0
        @date: 03.02.14 
 **/

namespace Controller;


class Post extends Resource {

	public function __construct()
	{
		$mapper = new \Model\Post();
		parent::__construct($mapper);
	}

	/**
	 * display a list of post entries
	 */
	public function getList($f3, $params)
	{
		$this->response->data['SUBPART'] = 'post_list.html';
		$page = \Pagination::findCurrentPage();
		if ($this->response instanceof \View\Backend) {
			// backend view
			$records = $this->resource->paginate($page-1,25,null,
				array('order'=>'publish_date desc'));
		} else {
			// frontend view
			$this->resource->filter('comments', array('approved = ?',1));
			$records = $this->resource->paginate($page-1,10,
				array('publish_date <= ? and published = ?', date('Y-m-d'), 1),
				array('order' => 'publish_date desc'));
		}
		$this->response->data['content'] = $records;
	}

	/**
	 * display a single post
	 */
	public function getSingle($f3, $params)
	{
		$this->response->data = array('SUBPART' => 'post_single.html');
		$addQuery = '';
		// only show published posts, except in backend
		if (!$this->response instanceof \View\Backend)
			$addQuery = ' and publish_date <= ? and published = ?';
		else {
			$ui = $f3->get('BACKEND_UI');
			if ($f3->get('text_editor') == 'sommernote') {
				$f3->set('ASSETS.JS.summernote', $ui.'js/summernote.js');
				$f3->set('ASSETS.CSS.fontawesome',
					'http://netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.min.css');
				$f3->set('ASSETS.CSS.summernote', $ui.'css/summernote.css');
				$f3->set('ASSETS.CSS.summernote-bs3', $ui.'css/summernote-bs3.css');
			}

			$f3->set('ASSETS.JS.jqueryui', $ui.'js/vendor/jquery.ui.widget.js');
			$f3->set('ASSETS.JS.jq-iframe-transport', $ui.'js/jquery.iframe-transport.js');
			$f3->set('ASSETS.JS.fileupload', $ui.'js/jquery.fileupload.js');
			$f3->set('ASSETS.CSS.fileupload', $ui.'css/jquery.fileupload.css');
		}

		// show only approved comments in the next query
		$this->resource->filter('comments', array('approved = ?', 1));

		// select a post by its ID
		if (isset($params['id'])) {
			$this->resource->load(array('_id = ?'.$addQuery, $params['id'], date('Y-m-d'), 1));
		}
		// select a post by its slugged title
		elseif (isset($params['slug'])) {
			$this->resource->load(array('slug = ?'.$addQuery, $params['slug'], date('Y-m-d'), 1));
		}
		$this->response->data['content'] = $this->resource;

		if ($this->resource->dry() && !$this->response instanceof \View\Backend)
			$f3->error(404, 'Post not found');
	}


	/**
	 * remove a post entry
	 */
	public function delete($f3, $params)
	{
		// TODO: erase comments and tag references
		parent::delete($f3,$params);
	}


	/**
	 * add a comment from POST data to current blog post
	 */
	public function addComment(\Base $f3, $params)
	{
		if (isset($params['slug'])) {
			// you may only comment published posts
			$this->resource->load(array('slug = ? and publish_date <= ? and published = ?',
							  $params['slug'], date('Y-m-d'), 1));
			if ($this->resource->dry()) {
				// invalid post ID
				$f3->error(404, 'Post not found.');
				return false;
			}
			$comment = new \Model\Comment();
			if ($comment->addToPost($this->resource->_id)) {
				// if posting was successful, reroute to the post view
				if (\Config::instance()->get('auto_approve_comments'))
					\FlashMessage::instance()->addMessage('Your comment has been added.',
						'success');
				else
					\FlashMessage::instance()->addMessage('Your comment has been added, but must be approved first before it becomes public.',
						'success');
				$f3->reroute('/'.$params['slug']);
			} else
				// if posting failed, return to comment form
				$this->getSingle($f3, $params);
		} else {
			// invalid URL, no post id given
			\FlashMessage::instance()->addMessage('No Post ID specified.', 'danger');
			$f3->reroute('/');
		}
	}

	public function publish($f3, $params)
	{
		if ($this->resource->updateProperty(array('_id = ?', $params['id']), 'published', 1)) {
			\FlashMessage::instance()->addMessage('Your post was published. Hurray!', 'success');
		} else {
			\FlashMessage::instance()->addMessage('This Post ID was not found', 'danger');
		}
		$f3->reroute('/admin/post');
	}

	public function hide($f3, $params)
	{
		if ($this->resource->updateProperty(array('_id = ?', $params['id']), 'published', 0)) {
			\FlashMessage::instance()->addMessage('Your post is now hidden.', 'success');
		} else {
			\FlashMessage::instance()->addMessage('This Post ID was not found', 'danger');
		}
		$f3->reroute('/admin/post');
	}

	public function beforeroute()
	{
		$this->response = \View\Frontend::instance();
	}
} 