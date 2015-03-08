<?php
/**
	Model Setup Helper

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2014 ~ ikkez
	Christian Knuth <ikkez0n3@gmail.com>

		@version 0.2.0
 **/

class setup {

	public function install($db_type) {
		$f3 = \Base::instance();
		$db_type = strtoupper($db_type);
		if( $db = storage::instance()->get($db_type))
			$f3->set('DB', $db);
		else {
			$f3->error(256,'no valid DB specified');
		}
		// setup the models
		\Model\Post::setup();
		\Model\Tag::setup();
		\Model\Comment::setup();
		\Model\User::setup();

		// create demo admin user
		$user = new \Model\User();
		$user->load(array('username = ?', 'admin'));
		if ($user->dry()) {
			$user->username = 'admin';
			$user->name = 'Administrator';
			$user->password = 'fabulog';
			$user->save();
			\Flash::instance()->addMessage('Admin User created,'
				.' username: admin, password: fabulog','success');
		}
		\Flash::instance()->addMessage('Setup complete','success');
	}

	public function uninstall()
	{
		die('serious?');
		// clears all tables !!!
		\Model\Post::setdown();
		\Model\Tag::setdown();
		\Model\Comment::setdown();
		\Model\User::setdown();
		$cfg = new Config();
		$cfg->clear('ACTIVE_DB');
		$cfg->save();
		\Base::instance()->clear('SESSION');
		echo "goodbye!";
	}

}