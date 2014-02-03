<?php

namespace Resource;

class Comment extends DB_Resource {

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
                'belongs-to-one' => '\Resource\Post',
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
        $this->set('approved', $f3->get('auto_approve_comments') ? 1 : 0 );
        return $this->save();
    }

    public function approve($f3,$params) {
        if($this->updateProperty(array('_id = ?', $params['id']),'approved',1)) {
            \FlashMessage::instance()->addMessage('Comment approved', 'success');
        } else {
            \FlashMessage::instance()->addMessage('Unknown Comment ID', 'danger');
        }
        $f3->reroute($f3->get('SESSION.LastPageURL'));
    }

    public function reject($f3,$params) {
        if ($this->updateProperty(array('_id = ?', $params['id']), 'approved', 2)) {
            \FlashMessage::instance()->addMessage('Comment rejected', 'success');
        } else {
            \FlashMessage::instance()->addMessage('Unknown Comment ID', 'danger');
        }
        $f3->reroute($f3->get('SESSION.LastPageURL'));
    }

    public function getSingle($f3,$params) {
        if (isset($params['id'])) {
            $this->response->data['content'] = $this->load(array('_id = ?',$params['id']));
            if(!$this->dry())
                return true;
        }
        \FlashMessage::instance()->addMessage('Unknown Comment ID', 'danger');
        $f3->reroute($f3->get('SESSION.LastPageURL'));
    }

    /**
     * display list of comments
     */
    public function getList($f3, $params)
    {
        $this->response->data['SUBPART'] = 'comment_list.html';
        $this->response->data['LAYOUT'] = 'comment_layout.html';
        $filter = array('approved = 0'); // new
        if (isset($params['viewtype'])) {
            if ($params['viewtype'] == 'published')
                $filter = array('approved = 1');
            elseif ($params['viewtype'] == 'rejected')
                $filter = array('approved = 2');
            elseif(!empty($params['viewtype'])) {
                // display all comments for a specified post
                $filter = array('post = ?',$params['viewtype']);
            }
        }

        $page = \Pagination::findCurrentPage();
        $limit = 3;
        $this->response->data['content'] = $this->paginate($page-1,$limit,$filter, array('order' => 'datetime asc'));
    }

    static public function countNew() {
        $comments = new self;
        return $comments->count(array('approved = 0'));
    }

    public function beforeroute() {
        $this->response = \Representation\Backend::instance();
    }

    public function afterroute() {
        echo $this->response->render();
    }

}