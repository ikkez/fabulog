<?php

namespace Model;

class Tag extends Base {

    protected
        $fieldConf = array(
            'title' => array(
                'type' => \DB\SQL\Schema::DT_VARCHAR128,
                'nullable' => false,
            ),
            'slug' => array(
                'type' => \DB\SQL\Schema::DT_VARCHAR128,
            ),
            'post' => array(
                'has-many' => array('\Model\Post','tags'),
            ),
        ),
        $table = 'tags',
        $db = 'DB';

    /**
     * magic setter for title
     */
    public function set_title($val)
    {
        // auto create slug when setting a tag title
        $this->set('slug', \Web::instance()->slug($val));
        return $val;
    }

}