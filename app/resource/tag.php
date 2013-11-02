<?php

namespace Resource;

class Tag extends DB_Resource {

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
                'has-many' => array('\Resource\Post','tags'),
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

    /**
     * get a list of tags
     */
    public function getList($f3, $params)
    {
        if (isset($params['slug'])) {
            $this->response = new \Representation\Frontend();
            $this->load(array('slug = ?',$params['slug']));
            $this->response->data = array(
                'content' => $this->post,
                'SUBPART' => 'post_tag_list.html',
            );
            $this->response->render();
        }
        elseif ($f3->get('AJAX')) {
            $tags = $this->find();
            if (!$tags) {
                echo null;
                return;
            }
            $return = array();
            foreach ($tags as $tag) {
                $return[] = array(
                    'value' => $tag->title,
                    'tokens' => array($tag->title),
                    'id' => $tag->_id,
                );
            }
            header('Content-Type: application/json');
            echo json_encode($tags->getAll('title'));
        }

    }
}