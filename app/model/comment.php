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
     * save new comment
     * @param $postID
     * @return bool|mixed
     */
    public function addToPost($postID)
    {
        $f3 = \Base::instance();
        foreach($this->fieldConf as $key => $conf) {
            // check requirements
            if (isset($this->fieldConf[$key]['required'])
                && $this->fieldConf[$key]['required'] == TRUE
                && (!$f3->exists('POST.'.$key) || empty($_POST[$key]) )
            ) {
                \FlashMessage::instance()->addMessage('Please fill out all required fields',
                    'warning');
                return false;
            }
        }
        $this->set('author_name',$f3->get('POST.author_name'));
        $this->set('author_email',$f3->get('POST.author_email'));
        $this->set('message',$f3->get('POST.message'));
        $this->set('post', $postID);
        $this->set('approved', \Config::instance()->get('auto_approve_comments') ? 1 : 0 );
        return $this->save();
    }

    static public function countNew() {
        $comments = new self;
        return $comments->count(array('approved = ?',0));
    }

}