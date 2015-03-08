<?php

namespace Controller;


class Settings extends Base {


	public function beforeroute() {
		$this->response = new \View\Backend();
		$this->response->data['LAYOUT'] = 'settings_layout.html';
	}

	public function general( \Base $f3 ) {
		$this->response->data['SUBPART'] = 'settings_general.html';

		$cfg = \Config::instance();
		if($f3->get('VERB') == 'POST') {

			$error = false;
			if($f3->devoid('POST.blog_title')) {
				$error=true;
				\Flash::instance()->addMessage('Please enter a Blog Title','warning');
			} else {
				$cfg->set('blog_title',$f3->get('POST.blog_title'));
			}

			$cfg->set('ssl_backend',$f3->get('POST.ssl_backend')=='1');
			$cfg->set('auto_approve_comments',$f3->get('POST.auto_approve_comments')=='1');

			if(!$error) {
				\Flash::instance()->addMessage('Config saved','success');
				$cfg->save();
			}
		}
		$cfg->copyto('POST');

	}

	public function database( \Base $f3 ) {
		$this->response->data['SUBPART'] = 'settings_database.html';

		$cfg = \Config::instance();
		if ($f3->get('VERB') == 'POST' && $f3->exists('POST.active_db')) {

			$type = $f3->get('POST.active_db');
			$cfg->{'DB_'.$type} = $f3->get('POST.DB_'.$type);
			$cfg->ACTIVE_DB = $type;

			$cfg->save();
			\Flash::instance()->addMessage('Config saved','success');
			$setup = new \Setup();
			$setup->install($type);
			// logout
			$f3->clear('SESSION.user_id');
		}
		$cfg->copyto('POST');

		$f3->set('JIG_format', array('JSON','Serialized'));
	}

} 