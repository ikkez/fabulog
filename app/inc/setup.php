<?php
/**
    setup.php
    
    The contents of this file are subject to the terms of the GNU General
    Public License Version 3.0. You may not use this file except in
    compliance with the license. Any of the license terms and conditions
    can be waived if you get permission from the copyright holder.
    
    Copyright (c) 2013 ~ ikkez
    Christian Knuth <ikkez0n3@gmail.com>
 
        @version 0.1.0
        @date: 31.10.13 
 **/

class setup {

    public function install($f3,$params) {
        $db_type = strtoupper($params['type']);
        if( $db = storage::instance()->get($db_type))
            $f3->set('DB', $db);
        else {
            $f3->error(256,'no valid DB specified');
        }
        // setup the models
        \Resource\Post::setup();
        \Resource\Tag::setup();
        \Resource\Comment::setup();
        \Resource\User::setup();

        // create demo admin user
        $user = new \Resource\User();
        $user->load(array('username = ?', 'admin'));
        if ($user->dry()) {
            $user->username = 'admin';
            $user->password = 'fabulog';
            $user->save();
            echo "Admin User created:<br>Username: admin<br>Password: fabulog<br/><br/>";
        }
        $cfg = new Config();
        $cfg->set('ACTIVE_DB', $db_type);
        $cfg->save();
        echo "Setup completed.";
    }

    public function uninstall()
    {
        die('serious?');
        // clears all tables !!!
        \Resource\Post::setdown();
        \Resource\Tag::setdown();
        \Resource\Comment::setdown();
        \Resource\User::setdown();
        $cfg = new Config();
        $cfg->clear('ACTIVE_DB');
        $cfg->save();
        \Base::instance()->clear('SESSION');
        echo "goodbye!";
    }

} 