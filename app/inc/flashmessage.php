<?php
/**
    FlashMessage.php
    
    The contents of this file are subject to the terms of the GNU General
    Public License Version 3.0. You may not use this file except in
    compliance with the license. Any of the license terms and conditions
    can be waived if you get permission from the copyright holder.
    
    Copyright (c) 2013 ~ ikkez
    Christian Knuth <ikkez0n3@gmail.com>
 
        @version 0.1.0
        @date: 11.10.13 
 **/

class FlashMessage extends Prefab {

    /** @var Base */
    protected $f3;

    public function __construct() {
        $this->f3 = \Base::instance();
        if(!$this->f3->exists('SESSION.flash.msg'))
            $this->f3->set('SESSION.flash.msg',array());
    }

    public function addMessage($text,$status = 'info') {
        $msg = array('text'=>$text,'status'=>$status);
        $this->f3->push('SESSION.flash.msg', $msg);
    }

    public function getMessages() {
        $out = $this->f3->get('SESSION.flash.msg');
        $this->clearMessages();
        return $out;
    }

    public function clearMessages() {
        $this->f3->clear('SESSION.flash.msg');
    }

    public function hasMessages() {
        $val = $this->f3->get('SESSION.flash.msg');
        return !empty($val);
    }

    public function setKey($key,$val=null) {
        $this->f3->set('SESSION.flash.key.'.$key,$val);
    }

    public function getKey($key) {
        if(!$this->f3->exists('SESSION.flash.key.'.$key))
            return '';
        $out = $this->f3->get('SESSION.flash.key.'.$key);
        $this->f3->clear('SESSION.flash.key.'.$key);
        return $out;
    }

}