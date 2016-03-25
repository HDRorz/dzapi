<?php
/**
 * Created by PhpStorm.
 * User: hdrorz
 * Date: 2/15/16
 * Time: 1:40 AM
 */

define('SERVER_ROOT', dirname(__FILE__));

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

ini_set('display_error', 'on');

require_once SERVER_ROOT.'/class/class_server.php';
require_once SERVER_ROOT.'/function/function_member.php';
require_once SERVER_ROOT.'/function/function_forum.php';

switch($_GET["mod"]) {
    case 'viewthread':
        loadforum(null,2239526);
        var_dump(getpostslist(2239526));
        break;
    case 'login':
        var_dump(userlogin($_GET["user"],$_GET["pass"]),''.'');
        break;

}