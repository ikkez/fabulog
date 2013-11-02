<?php
/**
    storage.php - just a little DB Object creator

    The contents of this file are subject to the terms of the GNU General
    Public License Version 3.0. You may not use this file except in
    compliance with the license. Any of the license terms and conditions
    can be waived if you get permission from the copyright holder.

    Copyright (c) 2013 ~ ikkez
    Christian Knuth <ikkez0n3@gmail.com>

        @version 0.1.0
        @date: 31.10.13
 **/

class storage extends Prefab {
    
    public function get($type) {
        /** @var \Base $f3 */
        $f3 = \Base::instance();
        $cfg = Config::instance();
        $type = strtoupper($type);
        switch ($type) {
            case 'JIG':
                $db = new \DB\Jig($cfg->DB_JIG['dir']);
                break;
            case 'MYSQL': // fall-through
            case 'PGSQL':
                $conf = $cfg['DB_'.$type];
                $db = new \DB\SQL($conf['dsn'], $conf['user'], $conf['password']);
                break;
            case 'SQLITE':
                $db = new \DB\SQL($cfg->DB_SQLITE['dsn']);
                break;
            case 'MONGO':
                $db = new \DB\SQL($cfg->DB_MONGO['dsn']);
                break;
        }
        return isset($db) ? $db : false;
    }

    public function update($type,$conf) {
        $cfg = Config::instance();
        $cfg->set('DB_'.strtoupper($type), $conf);
        $cfg->save();
    }
    
} 