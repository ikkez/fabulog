<?php


namespace Controller;

class Backend {

    protected
        $response;

    /**
     * fetch data for an overview page
     */
    public function home($f3) {
        $this->response->data['LAYOUT'] = 'overview.html';
    }

    /**
     * create a response that displays a list of module records
     */
    public function getList($f3,$params) {
        $class = '\Controller\\'.ucfirst($params['module']);
        if(!class_exists($class)) {
            trigger_error('unknown module');
            return false;
        }
        /** @var \Controller\Resource $module */
        $module = new $class();
        $module->setView($this->response);
        $module->getList($f3,$params);
        $this->response->data['SUBPART'] = $params['module'].'_list.html';
        $this->response->data['LAYOUT'] = $params['module'].'_layout.html';
    }

    /**
     * return an create/edit form for a given module
     */
    public function getSingle($f3,$params) {
        $class = '\Controller\\'.ucfirst($params['module']);
        if (!class_exists($class)) {
            trigger_error('unknown module');
            return false;
        }
        /** @var \Controller\Resource $module */
        $module = new $class();
        $module->setView($this->response);
        $module->getSingle($f3, $params);
        $this->response->data['SUBPART'] = $params['module'].'_edit.html';
        $this->response->data['LAYOUT'] = $params['module'].'_layout.html';
    }

    /**
     * check login state
     * @return bool
     */
    static public function isLoggedIn() {
        /** @var Base $f3 */
        $f3 = \Base::instance();
        if ($f3->exists('SESSION.user_id')) {
            $user = new \Model\User();
            $user->load(array('_id = ?',$f3->get('SESSION.user_id')));
            if(!$user->dry()) {
                $f3->set('BACKEND_USER',$user);
                return true;
            }
        }
        return false;
    }

    /**
     * login procedure
     */
    public function login($f3,$params) {
        if ($f3->exists('POST.username') && $f3->exists('POST.password')) {
            sleep(3); // login should take a while to kick-ass brute force attacks
            $user = new \Model\User();
            $user->load(array('username = ?',$f3->get('POST.username')));
            if (!$user->dry()) {
                // check hash engine
                $hash_engine = $f3->get('password_hash_engine');
                $valid = false;
                if($hash_engine == 'bcrypt') {
                    $valid = \Bcrypt::instance()->verify($f3->get('POST.password'),$user->password);
                } elseif($hash_engine == 'md5') {
                    $valid = (md5($f3->get('POST.password').$f3->get('password_md5_salt')) == $user->password);
                }
                if($valid) {
                    $f3->clear('SESSION'); //recreate session id
                    $f3->set('SESSION.user_id',$user->_id);
                    if($f3->get('ssl_backend'))
                        $f3->reroute('https://'.$f3->get('HOST').$f3->get('BASE').'/admin');
                    else $f3->reroute('/admin');
                }
            }
            \FlashMessage::instance()->addMessage('Wrong Username/Password', 'danger');
        }
        $this->response->setTemplate('templates/login.html');
    }

    public function logout($f3,$params) {
        $f3->clear('SESSION');
        $f3->reroute('http://'.$f3->get('HOST').$f3->get('BASE').'/');
    }

    /**
     * init the View
     */
    public function beforeroute() {
        $this->response = \View\Backend::instance();
    }

    /**
     * kick start the View, which finally creates the response
     * based on our previously set content data
     */
    public function afterroute() {
        $this->response->render();
    }

}