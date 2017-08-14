<?php
/**
    post.php
    
    The contents of this file are subject to the terms of the GNU General
    Public License Version 3.0. You may not use this file except in
    compliance with the license. Any of the license terms and conditions
    can be waived if you get permission from the copyright holder.
    
    Copyright (c) 2015 ~ ikkez
    Christian Knuth <ikkez0n3@gmail.com>
 
        @version 0.2.0
 **/

namespace Controller;


class Post extends Resource {

	public function __construct() {
		parent::__construct(new \Model\Post());
		// setup delete cascade
		$this->resource->onerase(function($self){
			// erase comment references
			$comments = new \Model\Comment();
			$comments->erase(array('post = ?',$self->_id));
			return true;
		});
	}

	/**
	 * display a list of post entries
	 * @param \Base $f3
	 * @param array $params
	 */
	public function getList(\Base $f3, $params) {
		$this->response->data['SUBPART'] = 'post_list.html';
		$page = \Pagination::findCurrentPage();
		if ($this->response instanceof \View\Backend) {
			// backend view
			$records = $this->resource->paginate($page-1,5,null,
				array('order'=>'publish_date desc'));
		} else {
			// frontend view
			$tags = new Tag();
			$f3->set('tag_cloud',$tags->tagCloud());

			$this->resource->filter('comments', array('approved = ?',1));
			$this->resource->countRel('comments');
			$records = $this->resource->paginate($page-1,5,
				array('publish_date <= ? and published = ?', date('Y-m-d'), true),
				array('order' => 'publish_date desc'));
		}
		$this->response->data['content'] = $records;
	}

	/**
	 * display a list of post entries by a specific tag they are attached to
	 * @param \Base $f3
	 * @param $params
	 */
	public function getListByTag(\Base $f3, $params) {
		$this->response->data['headline'] = 'All Post by Tag: '.$params['slug'];
		// set the tag condition
		$this->resource->has('tags',array('slug = ?',$params['slug']));
		$this->getList($f3,$params);
	}

	/**
	 * display a single post
	 * @param \Base $f3
	 * @param array $params
	 */
	public function getSingle(\Base $f3, $params) {
		$this->response->data['SUBPART'] = 'post_single.html';
		$addQuery = '';

		if ($this->response instanceof \View\Backend)
			$this->initBackend();
		else {
			// only show published posts on the frontend
			$addQuery = ' and publish_date <= ? and published = ?';
		}

		// show only approved comments in the next query
		$this->resource->filter('comments', array('approved = ?', 1));

		// select a post by its ID
		if (isset($params['id'])) {
			$this->resource->load(array('_id = ?'.$addQuery, $params['id'], date('Y-m-d'), true));
		}
		// select a post by its slugged title
		elseif (isset($params['slug'])) {
			$this->resource->load(array('slug = ?'.$addQuery, $params['slug'], date('Y-m-d'), true));
		}

		if ($this->response instanceof \View\Backend) {
			$data=$this->resource->cast(null, 0);
			if (!$this->resource->dry()) {
				$tags=$this->resource->tags;
				if ($tags)
					$tags=implode(',', $tags->getAll('title'));
				$data['tags']=$tags;
			}
			$data['publish_date'] = $f3->format('{0,date}', !empty($data['publish_date'])
				? strtotime($data['publish_date']) : time());
			// the model object itself, for getting relations etc.
			$this->response->data['post']=$this->resource;
			// the prepared form data, processed by FooForms
			$this->response->data['POST']=$data;

		} else {
			if ($this->resource->dry())
				$f3->error(404, 'Post not found');
			// copy whole post model, to be able to fetch relations
			// on the fly from within the template, if we need them
			$this->response->data['post']=$this->resource;
			$f3->set('page.title',$this->resource->title.' - '.\Config::instance()->blog_title);
		}
	}


	public function initBackend() {
		$f3 = \Base::instance();
		$this->response->data['SUBPART'] = 'post_edit.html';

		$f3->set('DP_FORMAT', $f3->get('LANGUAGE') == 'de-DE' ? 'dd.mm.yyyy' : 'mm/dd/yy');
	}

	/**
	 * update/create post
	 * @param \Base $f3
	 * @param array $params
	 */
	public function post(\Base $f3,$params) {
		parent::post($f3,$params);
		if ($this->response instanceof \View\Backend)
			$this->initBackend();
	}

	/**
	 * add a comment from POST data to current blog post
	 */
	public function addComment(\Base $f3, $params) {
		if (isset($params['slug'])) {
			// you may only comment published posts
			$this->resource->load(array('slug = ? and publish_date <= ? and published = ?',
							  $params['slug'], date('Y-m-d'), true));
			if ($this->resource->dry()) {
				// invalid post ID
				$f3->error(404, 'Post not found.');
				return false;
			}
			if (!$this->resource->enable_comments && !$this->resource->enable_comments === NULL) {
				$f3->error(403, 'Comments are not allowed for this Post');
				return false;
			}
			$comment = new \Model\Comment();
			$comment->copyfrom('POST','author_name, author_email, message');
			$comment->post = $this->resource->_id;
			$comment->approved = \Config::instance()->get('auto_approve_comments') ? 1 : 0;
			$comment->save();

			if ($f3->get('ERROR')) {
				// if posting failed, return to comment form
				$this->getSingle($f3, $params);
			} else {
				// if posting was successful, reroute to the post view
				if (\Config::instance()->get('auto_approve_comments'))
					\Flash::instance()->addMessage('Your comment has been added.', 'success');
				else
					\Flash::instance()->addMessage('Your comment has been added, but must be approved first before it becomes public.', 'success');
				$f3->reroute('/'.$params['slug']);
			}
		} else {
			// invalid URL, no post id given
			\Flash::instance()->addMessage('No Post ID specified.', 'danger');
			$f3->reroute('/');
		}
	}


	public function publish($f3, $params) {
		if ($this->resource->updateProperty(array('_id = ?', $params['id']), 'published', true)) {
			\Flash::instance()->addMessage('Your post was published. Hurray!', 'success');
		} else {
			\Flash::instance()->addMessage('This Post ID was not found', 'danger');
		}
		$f3->reroute('/admin/post');
	}

	public function hide($f3, $params) {
		if ($this->resource->updateProperty(array('_id = ?', $params['id']), 'published', false)) {
			\Flash::instance()->addMessage('Your post is now hidden.', 'success');
		} else {
			\Flash::instance()->addMessage('This Post ID was not found', 'danger');
		}
		$f3->reroute('/admin/post');
	}

} 