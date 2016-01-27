<?php

namespace Model;

class Base extends \DB\Cortex {

	// persistence settings
	protected $table, $db, $fieldConf;

	/**
	 * init the model
	 */
	public function __construct() {
		$f3 = \Base::instance();
		$this->table = $f3->get('db_table_prefix').$this->table;
		$this->db = 'DB';
		parent::__construct();
		// validation & error handler
		$class = get_called_class(); // PHP 5.3 bug
		$saveHandler = function(\DB\Cortex $self) use($class) {
			$valid = true;
			foreach($self->getFieldConfiguration() as $field=>$conf) {
				if (isset($conf['type']) && !isset($conf['relType'])) {
					$val = $self->get($field);
					$model = strtolower(str_replace('\\','.',$class));
					// check required fields
					if ($valid && isset($conf['required']))
						$valid = \Validation::instance()->required($val,$field,'error.'.$model.'.'.$field);
					// check unique
					if ($valid && isset($conf['unique']))
						$valid = \Validation::instance()->unique($self,$val,$field,'error.'.$model.'.'.$field);
					if (!$valid)
						break;
				}
			}
			return $valid;
		};
		$this->beforesave($saveHandler);
	}

	/**
	 * just a little mass update shortcut
	 * @param $filter
	 * @param $key
	 * @param $value
	 * @return bool
	 */
	public function updateProperty($filter, $key, $value) {
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