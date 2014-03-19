<?php
/**
	Jig-based Config Wrapper

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2013 ~ ikkez
	Christian Knuth <ikkez0n3@gmail.com>

		@version 0.2.0
		@date: 29.10.13
 **/

class Config extends \DB\Jig\Mapper {

	public function __construct() {
		$db = new \DB\Jig('app/data/');
		parent::__construct($db,'config.json');
		$this->load();
	}

	public function expose() {
		$arr = $this->cast();
		\Base::instance()->mset($arr);
	}

	static public function instance() {
		if (\Registry::exists('CONFIG'))
			$cfg = \Registry::get('CONFIG');
		else {
			$cfg = new self;
			\Registry::set('CONFIG',$cfg);
		}
		return $cfg;
	}

}