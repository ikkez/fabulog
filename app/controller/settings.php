<?php

namespace Controller;


class Settings extends Base {


	public function beforeroute() {
		parent::beforeroute();
		$this->response->data['LAYOUT'] = 'settings_layout.html';
	}

	public function general( \Base $f3 ) {

		$this->response->data['SUBPART'] = 'settings_general.html';

		if($f3->get('VERB') == 'POST') {
			$cfg = \Config::instance();

			$error = false;
			if($f3->devoid('POST.title')) {
				$error=true;
				\FlashMessage::instance()->addMessage('Please enter a Blog Title','warning');
			} else {
				$cfg->set('blog_title',$f3->get('POST.title'));
			}

			$cfg->set('ssl_backend',$f3->get('POST.ssl_backend')==1);

			$cfg->set('auto_approve_comments',$f3->get('POST.auto_approve_comments')==1);

			if(!$error) {
				\FlashMessage::instance()->addMessage('Config saved','success');
				$cfg->save();
			}
		}

	}

	public function database( \Base $f3 ) {

		$this->response->data['SUBPART'] = 'settings_database.html';

		if ($f3->get('VERB') == 'POST' && $f3->exists('POST.active_db')) {

			$cfg = \Config::instance();
			$type = $f3->get('POST.active_db');
			$cfg->{'DB_'.$type} = $f3->get('POST.'.$type);

			if($cfg->ACTIVE_DB == $type) {
				$cfg->save();
				\FlashMessage::instance()->addMessage('Config saved','success');
			} else {
				$cfg->ACTIVE_DB = $type;
				$cfg->save();
				\FlashMessage::instance()->addMessage('Config saved','success');
				$setup = new \Setup();
				$setup->install($type);
			}

		}

	}

} 