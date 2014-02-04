<?php

namespace Model;

class Base extends \DB\Cortex {

	// persistence settings
	protected $table, $db, $fieldConf;

	public function __construct()
	{
		$f3 = \Base::instance();
		$this->table = $f3->get('db_table_prefix').$this->table;
		$this->db = 'DB';
		parent::__construct();
	}

	public function updateProperty($filter, $key, $value)
	{
		$this->load($filter);
		if ($this->dry()) {
			return false;
		} else {
			while (!$this->dry()) {
				$this->set($key, $value);
				$this->save();
				$this->next();
			}
			return true;
		}
	}

}