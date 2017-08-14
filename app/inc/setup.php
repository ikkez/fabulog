<?php
/**
 *	Setup Helper
 *
 *	The contents of this file are subject to the terms of the GNU General
 *	Public License Version 3.0. You may not use this file except in
 *	compliance with the license. Any of the license terms and conditions
 *	can be waived if you get permission from the copyright holder.
 *
 *	Copyright (c) 2014 ~ ikkez
 *	Christian Knuth <ikkez0n3@gmail.com>
 *
 *	@version 0.3.0
 **/

class Setup extends \Prefab {

	/**
	 * check environment for requirements
	 * @return array
	 */
	public function preflight() {
		/** @var \Base $f3 */
		$f3 = \Base::instance();

		$checkWriteableDirs = [
			$f3->TEMP,
//			$f3->LOGS,
			$f3->UPLOADS,
			'res/',
			'app/data/',
		];

		$preErr = [];

		foreach ($checkWriteableDirs as $dir)
			if (!is_dir($dir))
				$preErr[] = sprintf("Warning: '%s' does not exist!", $dir);
			elseif (!is_writable($dir))
				$preErr[] = sprintf("Warning:'%s' is not writable!", $dir);

		$checkWriteableFiles = [
			'app/data/config.json'
		];

		foreach ($checkWriteableFiles as $file)
			if (!file_exists($file))
				$preErr[] = sprintf("Warning: '%s' does not exist!", $file);
			elseif (!is_writable($file))
				$preErr[] = sprintf("Warning:'%s' is not writable!", $file);

		return $preErr;
	}

	/**
	 * install database
	 * @param $db_type
	 */
	public function install($db_type) {
		$f3 = \Base::instance();
		$db_type = strtoupper($db_type);
		if( $db = Storage::instance()->get($db_type))
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

	/**
	 * uninstall database
	 */
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