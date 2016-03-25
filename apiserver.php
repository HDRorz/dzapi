<?php
/**
 * Created by PhpStorm.
 * User: HDRorz
 * Date: 2016-1-27
 * Time: 15:51
 */
namespace IMApiServer\php;

define('SERVER_ROOT', dirname(__FILE__));

//error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
error_reporting(E_ALL);

require_once __DIR__.'/lib/php/lib/Thrift/ClassLoader/ThriftClassLoader.php';

use Thrift\ClassLoader\ThriftClassLoader;

$GEN_DIR = realpath(dirname(__FILE__)).'/gen-php';

$loader = new ThriftClassLoader();
$loader->registerNamespace('Thrift', __DIR__ . '/lib/php/lib');
$loader->registerDefinition('IMApiServer', $GEN_DIR);
$loader->register();

/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements. See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership. The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */

/*
 * This is not a stand-alone server.  It should be run as a normal
 * php web script (like through Apache's mod_php) or as a cgi script
 * (like with the included runserver.py).  You can connect to it with
 * THttpClient in any language that supports it.  The PHP tutorial client
 * will work if you pass it the argument "--http".
 */

if (php_sapi_name() == 'cli') {
    ini_set("display_errors", "stderr");
}

use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TPhpStream;
use Thrift\Transport\TBufferedTransport;

class IMApiHandler implements \IMApiServer\DzApiIf {
    protected $log = array();

    public function hello() {
        // TODO: Implement hello() method.
        require_once './class/class_server.php';
        return 'Hello';
    }

    public function user_exists($user) {
        // TODO: Implement user_exists() method.
        error_reporting(E_ALL);
        $fp = fopen(SERVER_ROOT.'/log.txt','a+');
        fwrite($fp,var_export($user."\n",true));

        require_once SERVER_ROOT.'/class/class_server.php';
        require_once SERVER_ROOT.'/function/function_member.php';

        $return = userexists($user);

        if ($return == -3) {
            return true;
        }

        return false;
    }

    public function user_avatar($uid, $size) {
        // TODO: Implement user_exists() method.
        error_reporting(E_ALL);
        require_once SERVER_ROOT.'/class/class_server.php';
        require_once SERVER_ROOT.'/function/function_member.php';

        return avatar($uid, $size);
    }

    public function get_user($uid)
    {
        // TODO: Implement get_user() method.
        require_once SERVER_ROOT.'/class/class_server.php';
        require_once SERVER_ROOT.'/function/function_member.php';

        return getuserbyuid($uid);
    }

    public function get_userfield($uid)
    {
        // TODO: Implement get_userfield() method.
        error_reporting(E_ALL);
        require_once SERVER_ROOT.'/class/class_server.php';
        require_once SERVER_ROOT.'/function/function_member.php';

        return getuserfieldbyuid($uid);
    }

    public function user_login($ip, $user, $password, $qid, $ans, $loginfield) {
        // TODO: Implement user_login() method.
        error_reporting(E_ALL);
        require_once SERVER_ROOT.'/class/class_server.php';
        require_once SERVER_ROOT.'/function/function_member.php';

        global $_G;
        $_G['clientip'] = $ip;

        $fp = fopen(SERVER_ROOT.'/log.txt', 'a+');
        fwrite($fp,var_export('user_login',true)."\n");
        $return = userlogin($user, $password, $qid, $ans, $loginfield, $ip);

        fwrite($fp,'return'.var_export($return,true)."\n");
        //fwrite($fp,'_G'.var_export($_G,true)."\n");

        if($return['status'] >= 0) {
            return $return['ucresult']['uid'];
        }
        return 0;
    }

    public function user_register($ip, $username, $password, $password2, $email, $questionid = '', $answer = '')
    {
        // TODO: Implement user_register() method.
        error_reporting(E_ALL);
        require_once SERVER_ROOT.'/class/class_server.php';
        require_once SERVER_ROOT.'/function/function_member.php';

        global $_G;
        $_G['clientip'] = $ip;

        $fp = fopen(SERVER_ROOT.'/log.txt', 'a+');
        fwrite($fp,'user_register'."\n");

        return userregister($username, $password, $password2, $email, $questionid, $answer);
    }

    public function get_threadslist($fid, $page = 1, $filter = '')
    {
        // TODO: Implement get_userfield() method.
        error_reporting(E_ALL);
        require_once SERVER_ROOT.'/class/class_server.php';
        require_once SERVER_ROOT.'/function/function_forum.php';
        return getthreadslist($fid, $page, $filter);
    }

    public function get_postslist($tid, $page = 1, $ordertype = '')
    {
        // TODO: Implement get_postslist() method.
        error_reporting(E_ALL);
        require_once SERVER_ROOT.'/class/class_server.php';
        require_once SERVER_ROOT.'/function/function_forum.php';

        loadforum(null,$tid);
        return getpostslist($tid, $page, $ordertype);
    }


};

header('Content-Type', 'application/x-thrift');
if (php_sapi_name() == 'cli') {
    echo "\r\n";
}

$handler = new IMApiHandler();
$processor = new \IMApiServer\DzApiProcessor($handler);

$transport = new TBufferedTransport(new TPhpStream(TPhpStream::MODE_R | TPhpStream::MODE_W));
$protocol = new TBinaryProtocol($transport, true, true);

$transport->open();
$processor->process($protocol, $protocol);
$transport->close();
