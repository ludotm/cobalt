<?php 

namespace Core;

trait Singleton
{
	private function __clone(){}
    private function __wakeup(){}

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
       return $instance;
    }
}

?>