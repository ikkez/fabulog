<?php

namespace Model;

class User extends Base {

    protected
        $fieldConf = array(
            'username' => array(
                'type' => \DB\SQL\Schema::DT_VARCHAR128,
                'nullable'=>false,
                'required'=>true,
            ),
            'name' => array(
                'type' => \DB\SQL\Schema::DT_VARCHAR128,
                'required' => true,
            ),
            'password' => array(
                'type' => \DB\SQL\Schema::DT_VARCHAR256,
                'nullable'=>false,
                'required'=>true,
            ),
            'mail' => array(
                'type' => \DB\SQL\Schema::DT_VARCHAR256
            ),
            'news' => array(
                'has-many' => array('\Model\Post','author'),
            ),
        ),
        $table = 'user',
        $db = 'DB';

    /**
     * check if username already exists
     * @param $val
     * @return null
     */
    public function set_username($val) {
        if($this->dry())
            // new
            $user = $this->findone(array('username = ?',$val));
        else // existing
            $user = $this->findone(array('username = ? and _id != ?',$val,$this->_id));
        if($user) {
            $val = NULL;
            \FlashMessage::instance()->addMessage('This username already exists. Please select a unique username.','warning');
            \FlashMessage::instance()->setKey('form.username','has-error');
        }
        return $val;
    }

    /**
     * crypt password
     * @param $val
     * @return string
     */
    public function set_password($val) {
        $f3 = \Base::instance();
        $hash_engine = $f3->get('password_hash_engine');
        switch($hash_engine) {
            case 'bcrypt':
                $crypt = \Bcrypt::instance();
                $val = $crypt->hash($val);
                break;
            case 'md5':
                // fall-through
            default:
                $val = md5($val.$f3->get('password_md5_salt'));
                break;
        }
        return $val;
    }

    /**
     * validate email address
     * @param $val
     * @return null
     */
    public function set_mail($val) {
        if(!empty($val) && !\Audit::instance()->email($val,false)) {
            $val = NULL;
            \FlashMessage::instance()->addMessage('The entered email address is not valid.', 'warning');
            \FlashMessage::instance()->setKey('form.mail', 'has-error');
        }
        return $val;
    }

}
