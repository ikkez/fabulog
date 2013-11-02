<?php

namespace Resource;

class Post extends DB_Resource {

    // data configuration
    protected
        $fieldConf = array(
            'title' => array(
                'type' => \DB\SQL\Schema::DT_VARCHAR256,
                'nullable'=>false,
                'required'=>true,
            ),
            'slug' => array(
                'type' => \DB\SQL\Schema::DT_VARCHAR256,
                'nullable'=>false,
                'required' => true,
            ),
            'image' => array(
                'type' => \DB\SQL\Schema::DT_VARCHAR256,
            ),
            'teaser' => array(
                'type' => \DB\SQL\Schema::DT_TEXT,
            ),
            'text' => array(
                'type' => \DB\SQL\Schema::DT_TEXT,
                'required' => true,
            ),
            'publish_date' => array(
                'type' => \DB\SQL\Schema::DT_TIMESTAMP
            ),
            'published' => array(
                'type' => \DB\SQL\Schema::DT_BOOLEAN,
                'default'=>0,
            ),
            'author' => array(
                'belongs-to-one' => '\Resource\User',
            ),
            'tags' => array(
                'has-many' => array('\Resource\Tag','post'),
            ),
            'comments' => array(
                'has-many' => array('\Resource\Comment','post'),
            ),
        ),
        $table = 'posts',
        $db = 'DB';

    /** @var \Representation */
    protected $response;

    /**
     * magic setter for publish_date
     */
    public function set_publish_date($val) {
        // make input date compatible with DB datatype format
        return date('Y-m-d',strtotime($val));
    }

    /**
     * magic setter for title
     */
    public function set_title($val) {
        // auto create slug when setting a blog title
        $this->set('slug',\Web::instance()->slug($val));
        return $val;
    }

    /**
     * set and add new tags to the post entity
     * @param $val
     * @return array
     */
    public function set_tags($val) {
        if (!empty($val)) {
            $tagsArr = \Base::instance()->split($val);
            $tag_res = new Tag();
            // find IDs of known Tags
            $known_tags = $tag_res->find(array('title IN ?', $tagsArr));
            if ($known_tags) {
                foreach ($known_tags as $tag)
                    $tags[$tag->_id] = $tag->title;
                $newTags = array_diff($tagsArr, array_values($tags));
            } else
                $newTags = $tagsArr;
            // create remaining new Tags
            foreach ($newTags as $tag) {
                $tag_res->reset();
                $tag_res->title = $tag;
                $out = $tag_res->save();
                $tags[$out->_id] = $out->title;
            }
            // set array of IDs to current Post
            $val = array_keys($tags);
        }
        return $val;
    }

    /**
     * display a list of post entries
     */
    public function getList($f3, $params) {
        $this->response->data['SUBPART'] = 'post_list.html';
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        if ($this->response instanceof \Representation\Backend) {
            // backend view
            $this->response->data['content'] = $this->paginate($page-1,25,null, array('order' => 'publish_date desc'));
        } else {
            // frontend view
            $this->response->data['content'] = $this->addRelFilter('comments',array('approved = 1'))->
                paginate($page - 1, 10,array('publish_date <= ? and published = 1',date('Y-m-d')),array('order'=>'publish_date desc'));
        }
    }

    /**
     * display a single post
     */
    public function getSingle($f3,$params)
    {
        $this->response->data = array('SUBPART'=>'post_single.html');
        $addQuery = '';
        // only show published posts, except in backend
        if (!$this->response instanceof \Representation\Backend)
            $addQuery = ' and publish_date <= ? and published = 1';
        else {
            $ui = $f3->get('BACKEND_UI');
            if($f3->get('text_editor') == 'sommernote') {
                $f3->set('ASSETS.JS.summernote',$ui.'js/summernote.js');
                $f3->set('ASSETS.CSS.fontawesome','http://netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.min.css');
                $f3->set('ASSETS.CSS.summernote', $ui.'css/summernote.css');
                $f3->set('ASSETS.CSS.summernote-bs3', $ui.'css/summernote-bs3.css');
            }

            $f3->set('ASSETS.JS.jqueryui', $ui.'js/vendor/jquery.ui.widget.js');
            $f3->set('ASSETS.JS.jq-iframe-transport', $ui.'js/jquery.iframe-transport.js');
            $f3->set('ASSETS.JS.fileupload', $ui.'js/jquery.fileupload.js');
            $f3->set('ASSETS.CSS.fileupload', $ui.'css/jquery.fileupload.css');
        }
        // select a post by its ID
        if(isset($params['id'])) {
            $this->response->data['content'] = $this->load(
                array('_id = ?'.$addQuery, $params['id'], date('Y-m-d')));
        }
        // select a post by its slugged title
        elseif(isset($params['slug'])) {
            $this->response->data['content'] = $this->addRelFilter('comments',array('approved = 1'))->
                load(array('slug = ?'.$addQuery, $params['slug'], date('Y-m-d')));
        }
        if ($this->dry() && !$this->response instanceof \Representation\Backend)
            $f3->error(404,'Post not found');
    }

    public function save()
    {
        /** @var Base $f3 */
        $f3 = \Base::instance();
        if(!$this->author)
            $this->author = $f3->get('BACKEND_USER')->_id;
        return parent::save();
    }

    /**
     * remove a post entry
     */
    public function delete($f3,$params) {
        $this->response = \Representation\Backend::instance();
        $this->reset();

        if (isset($params['id'])) {
            $this->load(array('_id = ?', $params['id']));
            if ($this->dry()) {
                \FlashMessage::instance()->addMessage('No Blog Post found with this ID.', 'danger');
            } else {
                $this->erase();
                \FlashMessage::instance()->addMessage('Blog Post deleted.', 'success');
            }
        }
        // TODO: erase comments and tag references
        $f3->reroute($f3->get('SESSION.LastPageURL'));
    }

    /**
     * add a comment from POST data to current blog post
     */
    public function addComment( \Base $f3,$params)
    {
        if(isset($params['slug'])) {
            // you may only comment published posts
            $this->load(array('slug = ? and publish_date <= ? and published = 1',
                              $params['slug'], date('Y-m-d')));
            if($this->dry()) {
                // invalid post ID
                $f3->error(404, 'Post not found.');
                return false;
            }
            $comment = new Comment();
            if($comment->addToPost($this->_id)) {
                // if posting was successful, reroute to the post view
                if ($f3->get('auto_approve_comments'))
                    \FlashMessage::instance()->addMessage('Your comment has been added.','success');
                else
                    \FlashMessage::instance()->addMessage('Your comment has been added, but must be approved first before it becomes public.','success');
                $f3->reroute('/'.$params['slug']);
            } else
                // if posting failed, return to comment form
                $this->getSingle($f3,$params);
        } else {
            // invalid URL, no post id given
            \FlashMessage::instance()->addMessage('No Post ID specified.', 'danger');
            $f3->reroute('/');
        }
    }

    public function publish($f3,$params)
    {
        if ($this->updateProperty(array('_id = ?', $params['id']), 'published', 1)) {
            \FlashMessage::instance()->addMessage('Your post was published. Hurray!', 'success');
        } else {
            \FlashMessage::instance()->addMessage('This Post ID was not found', 'danger');
        }
        $f3->reroute('/admin/post');
    }

    public function hide($f3,$params)
    {
        if ($this->updateProperty(array('_id = ?', $params['id']), 'published', 0)) {
            \FlashMessage::instance()->addMessage('Your post is now hidden.', 'success');
        } else {
            \FlashMessage::instance()->addMessage('This Post ID was not found', 'danger');
        }
        $f3->reroute('/admin/post');
    }

    public function beforeroute() {
        $this->response = \Representation\Frontend::instance();
    }

    public function afterroute() {
        echo $this->response->render();
    }


}