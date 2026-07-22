<?php

namespace ishop;

class Db{

    use TSingletone;

    protected function __construct(){
        $configFile = (string)(getenv('DB_CONFIG_FILE') ?: (CONF . '/config_db.php'));
        if (!is_file($configFile)) {
            throw new \RuntimeException('Database configuration is missing', 500);
        }
        $db = require $configFile;
        if (!is_array($db) || empty($db['dsn']) || !array_key_exists('user', $db) || !array_key_exists('pass', $db)) {
            throw new \RuntimeException('Database configuration is invalid', 500);
        }
        class_alias('\RedBeanPHP\R','\R');
        \R::setup($db['dsn'], $db['user'], $db['pass']);
        if( !\R::testConnection() ){
            throw new \Exception("Нет соединения с БД", 500);
        }
        \R::freeze(true);
        if(DEBUG){
            \R::debug(true, 1);
        }

        \R::ext('xdispense', function($type){
            return \R::getRedBean()->dispense( $type );
        });
    }

}
