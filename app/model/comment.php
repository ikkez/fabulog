<?php

namespace Model;

class Comment extends Base {

    protected
        $fieldConf = array(
            'author_name' => array(
                'type' => \DB\SQL\Schema::DT_VARCHAR128,
                'nullable' => false,
                'required' => true,
            ),
            'author_email' => array(
                'type' => \DB\SQL\Schema::DT_VARCHAR128,
                'nullable' => false,
                'required' => true,
            ),
            'message' => array(
                'type' => \DB\SQL\Schema::DT_TEXT,
                'required'=>true,
            ),
            'datetime' => array(
                'type' => \DB\SQL\Schema::DT_TIMESTAMP,
                'default' => \DB\SQL\Schema::DF_CURRENT_TIMESTAMP,
            ),
            'post' => array(
                'belongs-to-one' => '\Model\Post',
            ),
            'approved' => array(
                'type' => \DB\SQL\Schema::DT_TINYINT,
            ),
        ),
        $table = 'comments',
        $db = 'DB';

    /**
     * validate email address
     * @param $val
     * @return null
     */
    public function set_author_email($val) {
        return \Validation::instance()->email($val,'error.model.comment.author_email')
            ? $val : null;
    }

    static public function countNew() {
        $comments = new self;
        return $comments->count(array('approved = ?',0),null,0);
    }

}