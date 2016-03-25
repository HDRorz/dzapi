<?php

/**
 * Created by PhpStorm.
 * User: HDRorz
 * Date: 2016-1-27
 * Time: 13:38
 */

error_reporting(E_ALL);

//define('SERVER_ROOT', substr(dirname(__FILE__), 0, -6));
define('IN_DISCUZ', true);
define('SERVER_DEBUG', false);

set_exception_handler(array('server', 'handleException'));

if(function_exists('spl_autoload_register')) {
    spl_autoload_register(array('server', 'autoload'));
} else {
    function __autoload($class) {
        return server::autoload($class);
    }
}

S::creatapp();

class server
{
    private static $_tables;
    private static $_imports;
    private static $_app;
    private static $_memory = null;

    public static function app() {
        return self::$_app;
    }

    public static function creatapp() {
        if(!is_object(self::$_app)) {
            self::$_app = application::instance();
        }
        return self::$_app;
    }

    public static function t($name) {
        return self::_make_obj($name, 'table');
    }

    protected static function _make_obj($name, $type, $extendable = false, $p = array()) {
        $pluginid = null;
        if($name[0] === '#') {
            list(, $pluginid, $name) = explode('#', $name);
        }
        $cname = $type.'_'.$name;
        if(!isset(self::$_tables[$cname])) {
            if(!class_exists($cname, false)) {
                self::import(($pluginid ? 'plugin/'.$pluginid : 'class').'/'.$type.'/'.$name);
            }
            if($extendable) {
                self::$_tables[$cname] = new container();
                switch (count($p)) {
                    case 0:	self::$_tables[$cname]->obj = new $cname();break;
                    case 1:	self::$_tables[$cname]->obj = new $cname($p[1]);break;
                    case 2:	self::$_tables[$cname]->obj = new $cname($p[1], $p[2]);break;
                    case 3:	self::$_tables[$cname]->obj = new $cname($p[1], $p[2], $p[3]);break;
                    case 4:	self::$_tables[$cname]->obj = new $cname($p[1], $p[2], $p[3], $p[4]);break;
                    case 5:	self::$_tables[$cname]->obj = new $cname($p[1], $p[2], $p[3], $p[4], $p[5]);break;
                    default: $ref = new ReflectionClass($cname);self::$_tables[$cname]->obj = $ref->newInstanceArgs($p);unset($ref);break;
                }
            } else {
                self::$_tables[$cname] = new $cname();
            }
        }
        return self::$_tables[$cname];
    }

    public static function memory() {
        if(!self::$_memory) {
            self::$_memory = new memory();
            self::$_memory->init(self::app()->config['memory']);
        }
        return self::$_memory;
    }

    public static function import($name, $folder = '', $force = true) {
        //关闭strictc错误
        error_reporting(error_reporting()&(~E_STRICT));
        $key = $folder.$name;
        if(!isset(self::$_imports[$key])) {
            $path = SERVER_ROOT.'/'.$folder;
            if(strpos($name, '/') !== false) {
                $pre = basename(dirname($name));
                $filename = dirname($name).'/'.$pre.'_'.basename($name).'.php';
            } else {
                $filename = $name.'.php';
            }

            if(is_file($path.'/'.$filename)) {
                include $path.'/'.$filename;
                self::$_imports[$key] = true;

                return true;
            } elseif(!$force) {
                return false;
            } else {
                throw new Exception('Oops! System file lost: '.$filename);
            }
        }
        return true;
    }

    public static function handleException($exception) {
        errors::exception_error($exception);
    }


    public static function handleError($errno, $errstr, $errfile, $errline) {
        if($errno & SERVER_DEBUG) {
            errors::system_error($errstr, false, true, false);
        }
    }

    public static function handleShutdown() {
        if(($error = error_get_last()) && $error['type'] & SERVER_DEBUG) {
            errors::system_error($error['message'], false, true, false);
        }
    }

    public static function autoload($class) {

        $fp = fopen(SERVER_ROOT.'/log.txt', 'a+');
        //fwrite($fp,var_export($class,true)."\n");
        $class = strtolower($class);
        if(strpos($class, '_') !== false) {
            list($folder) = explode('_', $class);
            if($folder != 'class') {
                $file = 'class/'.$folder.'/'.substr($class, strlen($folder) + 1);
            } else {
                $file = 'class/'.substr($class, strlen($folder) + 1);
            }
        } else {
            $file = 'class/'.$class;
        }

        try {

            self::import($file);
            return true;

        } catch (Exception $exc) {

            $trace = $exc->getTrace();
            foreach ($trace as $log) {
                if(empty($log['class']) && $log['function'] == 'class_exists') {
                    return false;
                }
            }
            errors::exception_error($exc);
        }
    }

    public static function analysisStart($name){
        $key = 'other';
        if($name[0] === '#') {
            list(, $key, $name) = explode('#', $name);
        }
        if(!isset($_ENV['analysis'])) {
            $_ENV['analysis'] = array();
        }
        if(!isset($_ENV['analysis'][$key])) {
            $_ENV['analysis'][$key] = array();
            $_ENV['analysis'][$key]['sum'] = 0;
        }
        $_ENV['analysis'][$key][$name]['start'] = microtime(TRUE);
        $_ENV['analysis'][$key][$name]['start_memory_get_usage'] = memory_get_usage();
        $_ENV['analysis'][$key][$name]['start_memory_get_real_usage'] = memory_get_usage(true);
        $_ENV['analysis'][$key][$name]['start_memory_get_peak_usage'] = memory_get_peak_usage();
        $_ENV['analysis'][$key][$name]['start_memory_get_peak_real_usage'] = memory_get_peak_usage(true);
    }

    public static function analysisStop($name) {
        $key = 'other';
        if($name[0] === '#') {
            list(, $key, $name) = explode('#', $name);
        }
        if(isset($_ENV['analysis'][$key][$name]['start'])) {
            $diff = round((microtime(TRUE) - $_ENV['analysis'][$key][$name]['start']) * 1000, 5);
            $_ENV['analysis'][$key][$name]['time'] = $diff;
            $_ENV['analysis'][$key]['sum'] = $_ENV['analysis'][$key]['sum'] + $diff;
            unset($_ENV['analysis'][$key][$name]['start']);
            $_ENV['analysis'][$key][$name]['stop_memory_get_usage'] = memory_get_usage();
            $_ENV['analysis'][$key][$name]['stop_memory_get_real_usage'] = memory_get_usage(true);
            $_ENV['analysis'][$key][$name]['stop_memory_get_peak_usage'] = memory_get_peak_usage();
            $_ENV['analysis'][$key][$name]['stop_memory_get_peak_real_usage'] = memory_get_peak_usage(true);
        }
        return $_ENV['analysis'][$key][$name];
    }
}

class S extends server {}
class DB extends database {}

S::app()->init();