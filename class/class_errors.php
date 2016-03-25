<?php

/**
 * Created by PhpStorm.
 * User: HDRorz
 * Date: 2016-1-27
 * Time: 14:05
 */
class errors
{
    public static function system_error($message, $show = true, $save = true, $halt = true) {

        $fp = fopen(SERVER_ROOT.'/log.txt', 'a+');
        fwrite($fp,'error '.var_export($message,true)."\n");

        if(!empty($message)) {
            $message .= 'error';
        } else {
            $message .= 'error_unknow';
        }

        list($showtrace, $logtrace) = self::debug_backtrace();

        if($save) {
            $messagesave = '<b>'.$message.'</b><br><b>PHP:</b>'.$logtrace;
            self::write_error_log($messagesave);
        }

        if($show) {
            self::show_error('system', "<li>$message</li>", $showtrace, 0);
        }

        if($halt) {
            exit();
        } else {
            return $message;
        }
    }

    public static function debug_backtrace() {
        $skipfunc[] = 'errors->debug_backtrace';
        $skipfunc[] = 'errors->db_error';
        $skipfunc[] = 'errors->template_error';
        $skipfunc[] = 'errors->system_error';
        $skipfunc[] = 'db_mysql->halt';
        $skipfunc[] = 'db_mysql->query';
        $skipfunc[] = 'DB::_execute';

        $show = $log = '';
        $debug_backtrace = debug_backtrace();
        krsort($debug_backtrace);
        foreach ($debug_backtrace as $k => $error) {
            $file = str_replace(SERVER_ROOT, '', $error['file']);
            $func = isset($error['class']) ? $error['class'] : '';
            $func .= isset($error['type']) ? $error['type'] : '';
            $func .= isset($error['function']) ? $error['function'] : '';
            if(in_array($func, $skipfunc)) {
                break;
            }
            $error['line'] = sprintf('%04d', $error['line']);

            $show .= "<li>[Line: $error[line]]".$file."($func)</li>";
            $log .= !empty($log) ? ' -> ' : '';
            $log .= $file.':'.$error['line'];
        }
        return array($show, $log);
    }

    public static function db_error($message, $sql) {
        global $_G;

        list($showtrace, $logtrace) = self::debug_backtrace();

        $title = 'error'.'db_'.$message;
        $title_msg = 'error'.'db_error_message';
        $title_sql = 'error'.'db_query_sql';
        $title_backtrace = 'error'.'backtrace';
        $title_help = 'error'.'db_help_link';

        $db = &DB::object();
        $dberrno = $db->errno();
        $dberror = str_replace($db->tablepre,  '', $db->error());
        $sql = dhtmlspecialchars(str_replace($db->tablepre,  '', $sql));

        $msg = '<li>[Type] '.$title.'</li>';
        $msg .= $dberrno ? '<li>['.$dberrno.'] '.$dberror.'</li>' : '';
        $msg .= $sql ? '<li>[Query] '.$sql.'</li>' : '';

        self::show_error('db', $msg, $showtrace, false);
        unset($msg, $phperror);

        $errormsg = '<b>'.$title.'</b>';
        $errormsg .= "[$dberrno]<br /><b>ERR:</b> $dberror<br />";
        if($sql) {
            $errormsg .= '<b>SQL:</b> '.$sql;
        }
        $errormsg .= "<br />";
        $errormsg .= '<b>PHP:</b> '.$logtrace;

        self::write_error_log($errormsg);
        exit();

    }

    public static function exception_error($exception) {

        $fp = fopen(SERVER_ROOT.'/log.txt', 'a+');
        fwrite($fp,'exception '.var_export($exception,true)."\n");

        if($exception instanceof DbException) {
            $type = 'db';
        } else {
            $type = 'system';
        }

        if($type == 'db') {
            $errormsg = '('.$exception->getCode().') ';
            $errormsg .= self::sql_clear($exception->getMessage());
            if($exception->getSql()) {
                $errormsg .= '<div class="sql">';
                $errormsg .= self::sql_clear($exception->getSql());
                $errormsg .= '</div>';
            }
        } else {
            $errormsg = $exception->getMessage();
        }

        $trace = $exception->getTrace();
        krsort($trace);

        $trace[] = array('file'=>$exception->getFile(), 'line'=>$exception->getLine(), 'function'=> 'break');
        $phpmsg = array();
        foreach ($trace as $error) {
            if(!empty($error['function'])) {
                $fun = '';
                if(!empty($error['class'])) {
                    $fun .= $error['class'].$error['type'];
                }
                $fun .= $error['function'].'(';
                if(!empty($error['args'])) {
                    $mark = '';
                    foreach($error['args'] as $arg) {
                        $fun .= $mark;
                        if(is_array($arg)) {
                            $fun .= 'Array';
                        } elseif(is_bool($arg)) {
                            $fun .= $arg ? 'true' : 'false';
                        } elseif(is_int($arg)) {
                            $fun .= (defined('DISCUZ_DEBUG') && DISCUZ_DEBUG) ? $arg : '%d';
                        } elseif(is_float($arg)) {
                            $fun .= (defined('DISCUZ_DEBUG') && DISCUZ_DEBUG) ? $arg : '%f';
                        } else {
                            $fun .= (defined('DISCUZ_DEBUG') && DISCUZ_DEBUG) ? '\''.dhtmlspecialchars(substr(self::clear($arg), 0, 10)).(strlen($arg) > 10 ? ' ...' : '').'\'' : '%s';
                        }
                        $mark = ', ';
                    }
                }

                $fun .= ')';
                $error['function'] = $fun;
            }
            $phpmsg[] = array(
                'file' => str_replace(array(SERVER_ROOT, '\\'), array('', '/'), $error['file']),
                'line' => $error['line'],
                'function' => $error['function'],
            );
        }

        self::show_error($type, $errormsg, $phpmsg);
        exit();

    }

    public static function clear($message) {
        return str_replace(array("\t", "\r", "\n"), " ", $message);
    }

    public static function sql_clear($message) {
        $message = self::clear($message);
        $message = str_replace(DB::object()->tablepre, '', $message);
        $message = dhtmlspecialchars($message);
        return $message;
    }

    public static function write_error_log($message) {
        global $_G;

        $message = self::clear($message);
        $time = time();
        $file =  SERVER_ROOT.'/log/'.date("Ym").'_errorlog.php';
        $hash = md5($message);

        $uid = $_G['uid'];
        $ip = $_G['clientip'];

        $user = '<b>User:</b> uid='.intval($uid).'; IP='.$ip.'; RIP:'.$_SERVER['REMOTE_ADDR'];
        $uri = 'Request: '.dhtmlspecialchars(self::clear($_SERVER['REQUEST_URI']));
        $message = "<?PHP exit;?>\t{$time}\t$message\t$hash\t$user $uri\n";
        if($fp = @fopen($file, 'rb')) {
            $lastlen = 50000;
            $maxtime = 60 * 10;
            $offset = filesize($file) - $lastlen;
            if($offset > 0) {
                fseek($fp, $offset);
            }
            if($data = fread($fp, $lastlen)) {
                $array = explode("\n", $data);
                if(is_array($array)) foreach($array as $key => $val) {
                    $row = explode("\t", $val);
                    if($row[0] != '<?PHP exit;?>') continue;
                    if($row[3] == $hash && ($row[1] > $time - $maxtime)) {
                        return;
                    }
                }
            }
        }
        error_log($message, 3, $file);
    }

    public static function show_error($type, $errormsg, $phpmsg = '', $typemsg = '') {
        global $_G;
    }

}