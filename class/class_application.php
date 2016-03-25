<?php

/**
 * Created by PhpStorm.
 * User: HDRorz
 * Date: 2016-1-27
 * Time: 11:25
 */
class application extends svbase
{
    var $config = array();

    var $cachelist = array();

    var $var = array();

    var $init_db = true;
    var $init_session = true;
    var $init_setting = true;

    var $initated = false;

    var $superglobal = array(
        'GLOBALS' => 1,
        '_GET' => 1,
        '_POST' => 1,
        '_REQUEST' => 1,
        '_COOKIE' => 1,
        '_SERVER' => 1,
        '_ENV' => 1,
        '_FILES' => 1,
    );

    static function &instance() {
        static $object;
        if(empty($object)) {
            $object = new self();
        }
        return $object;
    }

    public function __construct() {

        $this->_init_env();
        $this->_init_config();
    }

    public function init() {
        if(!$this->initated) {
            $this->_init_db();
            $this->_init_setting();
            //$this->_init_user();
            //$this->_init_session();
        }
        $this->initated = true;
    }

    private function _init_env() {

        error_reporting(E_ERROR | E_WARNING | E_PARSE);
        if(PHP_VERSION < '5.3.0') {
            set_magic_quotes_runtime(0);
        }

        define('MAGIC_QUOTES_GPC', function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc());
        define('ICONV_ENABLE', function_exists('iconv'));
        define('MB_ENABLE', function_exists('mb_convert_encoding'));
        define('EXT_OBGZIP', function_exists('ob_gzhandler'));

        define('TIMESTAMP', time());
        $this->timezone_set();

        if(!defined('COMMON_FUNCTION') && !@include(SERVER_ROOT.'/function/function_common.php')) {
            exit('function_common.php is missing');
        }

        foreach ($GLOBALS as $key => $value) {
            if (!isset($this->superglobal[$key])) {
                $GLOBALS[$key] = null; unset($GLOBALS[$key]);
            }
        }

        global $_G;
        $_G = array(
            'uid' => 0,
            'username' => '',
            'adminid' => 0,
            'groupid' => 1,
            'sid' => '',
            'formhash' => '',
            'connectguest' => 0,
            'timestamp' => TIMESTAMP,
            'starttime' => microtime(true),
            'clientip' => $this->_get_client_ip(),
            'remoteport' => $_SERVER['REMOTE_PORT'],
            'referer' => '',
            'charset' => '',
            'gzipcompress' => '',
            'authkey' => '',
            'timenow' => array(),

            'PHP_SELF' => '',
            'siteurl' => '',
            'siteroot' => '',
            'siteport' => '',

            'config' => array(),
            'member' => array(),
            'group' => array(),
            'cookie' => array(),
            'cache' => array(),
            'session' => array(),

            'tpp' => 20,

        );
        $_G['PHP_SELF'] = dhtmlspecialchars($this->_get_script_url());
        $_G['basefilename'] = basename($_G['PHP_SELF']);
        $sitepath = substr($_G['PHP_SELF'], 0, strrpos($_G['PHP_SELF'], '/'));
        if(defined('IN_API')) {
            $sitepath = preg_replace("/\/api\/?.*?$/i", '', $sitepath);
        }
        $_G['isHTTPS'] = ($_SERVER['HTTPS'] && strtolower($_SERVER['HTTPS']) != 'off') ? true : false;
        $_G['siteurl'] = dhtmlspecialchars('http'.($_G['isHTTPS'] ? 's' : '').'://'.$_SERVER['HTTP_HOST'].$sitepath.'/');

        $url = parse_url($_G['siteurl']);
        $_G['siteroot'] = isset($url['path']) ? $url['path'] : '';
        $_G['siteport'] = empty($_SERVER['SERVER_PORT']) || $_SERVER['SERVER_PORT'] == '80' || $_SERVER['SERVER_PORT'] == '443' ? '' : ':'.$_SERVER['SERVER_PORT'];

        if(defined('SUB_DIR')) {
            $_G['siteurl'] = str_replace(SUB_DIR, '/', $_G['siteurl']);
            $_G['siteroot'] = str_replace(SUB_DIR, '/', $_G['siteroot']);
        }

        $this->var = & $_G;
    }

    private function _get_script_url() {
        if(!isset($this->var['PHP_SELF'])){
            $scriptName = basename($_SERVER['SCRIPT_FILENAME']);
            if(basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
                $this->var['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
            } else if(basename($_SERVER['PHP_SELF']) === $scriptName) {
                $this->var['PHP_SELF'] = $_SERVER['PHP_SELF'];
            } else if(isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
                $this->var['PHP_SELF'] = $_SERVER['ORIG_SCRIPT_NAME'];
            } else if(($pos = strpos($_SERVER['PHP_SELF'],'/'.$scriptName)) !== false) {
                $this->var['PHP_SELF'] = substr($_SERVER['SCRIPT_NAME'],0,$pos).'/'.$scriptName;
            } else if(isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'],$_SERVER['DOCUMENT_ROOT']) === 0) {
                $this->var['PHP_SELF'] = str_replace('\\','/',str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['SCRIPT_FILENAME']));
                $this->var['PHP_SELF'][0] != '/' && $this->var['PHP_SELF'] = '/'.$this->var['PHP_SELF'];
            } else {
                system_error('request_tainting');
            }
        }
        return $this->var['PHP_SELF'];
    }

    private function _init_config() {

        $_config = array();
        @include SERVER_ROOT.'/config/config_global.php';
        if(empty($_config)) {
            system_error('config_notfound');
        }

        $this->config = & $_config;
        $this->var['config'] = & $_config;

    }

    private function _get_client_ip() {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
            foreach ($matches[0] AS $xip) {
                if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                    $ip = $xip;
                    break;
                }
            }
        }
        return $ip;
    }

    private function _init_db() {
        if($this->init_db) {
            $driver = function_exists('mysql_connect') ? 'driver' : system_error('no mysql driver');
            DB::init($driver, $this->config['db']);
        }
    }

    private function _init_setting() {
        if($this->init_setting) {
            if(empty($this->var['setting'])) {
                $this->cachelist[] = 'setting';
            }
            if(!isset($this->var['cache']['cronnextrun'])) {
                $this->cachelist[] = 'cronnextrun';
            }
        }

        !empty($this->cachelist) && loadcache($this->cachelist);

        if(!is_array($this->var['setting'])) {
            $this->var['setting'] = array();
        }

    }

    private function _init_session() {

        $sessionclose = !empty($this->var['setting']['sessionclose']);
        $this->session = $sessionclose ? new discuz_session_close() : new discuz_session();

        if($this->init_session)	{
            $this->session->init($this->var['cookie']['sid'], $this->var['clientip'], $this->var['uid']);
            $this->var['sid'] = $this->session->sid;
            $this->var['session'] = $this->session->var;

            if(!empty($this->var['sid']) && $this->var['sid'] != $this->var['cookie']['sid']) {
                dsetcookie('sid', $this->var['sid'], 86400);
            }

            if($this->session->isnew) {
                if(ipbanned($this->var['clientip'])) {
                    $this->session->set('groupid', 6);
                }
            }

            if($this->session->get('groupid') == 6) {
                $this->var['member']['groupid'] = 6;
                if(!defined('IN_MOBILE_API')) {
                    sysmessage('user_banned');
                } else {
                    mobile_core::result(array('error' => 'user_banned'));
                }
            }

            if($this->var['uid'] && !$sessionclose && ($this->session->isnew || ($this->session->get('lastactivity') + 600) < TIMESTAMP)) {
                $this->session->set('lastactivity', TIMESTAMP);
                if($this->session->isnew) {
                    if($this->var['member']['lastip'] && $this->var['member']['lastvisit']) {
                        dsetcookie('lip', $this->var['member']['lastip'].','.$this->var['member']['lastvisit']);
                    }
                    S::t('common_member_status')->update($this->var['uid'], array('lastip' => $this->var['clientip'], 'port' => $this->var['remoteport'], 'lastvisit' => TIMESTAMP));
                }
            }

        }
    }

    private function _init_user() {
        if($this->init_user) {
            if($auth = getglobal('auth', 'cookie')) {
                $auth = daddslashes(explode("\t", authcode($auth, 'DECODE')));
            }
            list($discuz_pw, $discuz_uid) = empty($auth) || count($auth) < 2 ? array('', '') : $auth;

            if($discuz_uid) {
                $user = getuserbyuid($discuz_uid, 1);
            }

            if(!empty($user) && $user['password'] == $discuz_pw) {
                if(isset($user['_inarchive'])) {
                    S::t('common_member_archive')->move_to_master($discuz_uid);
                }
                $this->var['member'] = $user;
            } else {
                $user = array();
                $this->_init_guest();
            }

            if($user && $user['groupexpiry'] > 0 && $user['groupexpiry'] < TIMESTAMP) {
                $memberfieldforum = S::t('common_member_field_forum')->fetch($discuz_uid);
                $groupterms = dunserialize($memberfieldforum['groupterms']);
                if(!empty($groupterms['main'])) {
                    S::t("common_member")->update($user['uid'], array('groupexpiry'=> 0, 'groupid' => $groupterms['main']['groupid'], 'adminid' => $groupterms['main']['adminid']));
                    $user['groupid'] = $groupterms['main']['groupid'];
                    $user['adminid'] = $groupterms['main']['adminid'];
                    unset($groupterms['main'], $groupterms['ext'][$this->var['member']['groupid']]);
                    $this->var['member'] = $user;
                    S::t('common_member_field_forum')->update($discuz_uid, array('groupterms' => serialize($groupterms)));
                } elseif((getgpc('mod') != 'spacecp' || CURSCRIPT != 'home') && CURSCRIPT != 'member') {
                    dheader('location: home.php?mod=spacecp&ac=usergroup&do=expiry');
                }
            }

            if($user && $user['freeze'] && (getgpc('mod') != 'spacecp' && getgpc('mod') != 'misc'  || CURSCRIPT != 'home') && CURSCRIPT != 'member' && CURSCRIPT != 'misc') {
                dheader('location: home.php?mod=spacecp&ac=profile&op=password');
            }

            $this->cachelist[] = 'usergroup_'.$this->var['member']['groupid'];
            if($user && $user['adminid'] > 0 && $user['groupid'] != $user['adminid']) {
                $this->cachelist[] = 'admingroup_'.$this->var['member']['adminid'];
            }

        } else {
            $this->_init_guest();
        }
        setglobal('groupid', getglobal('groupid', 'member'));
        !empty($this->cachelist) && loadcache($this->cachelist);

        if($this->var['member'] && $this->var['group']['radminid'] == 0 && $this->var['member']['adminid'] > 0 && $this->var['member']['groupid'] != $this->var['member']['adminid'] && !empty($this->var['cache']['admingroup_'.$this->var['member']['adminid']])) {
            $this->var['group'] = array_merge($this->var['group'], $this->var['cache']['admingroup_'.$this->var['member']['adminid']]);
        }

        if($this->var['group']['allowmakehtml'] && isset($_GET['_makehtml'])) {
            $this->var['makehtml'] = 1;
            $this->_init_guest();
            loadcache(array('usergroup_7'));
            $this->var['group'] = $this->var['cache']['usergroup_7'];
            unset($this->var['inajax']);
        }

        if(empty($this->var['cookie']['lastvisit'])) {
            $this->var['member']['lastvisit'] = TIMESTAMP - 3600;
            dsetcookie('lastvisit', TIMESTAMP - 3600, 86400 * 30);
        } else {
            $this->var['member']['lastvisit'] = $this->var['cookie']['lastvisit'];
        }

        setglobal('uid', getglobal('uid', 'member'));
        setglobal('username', getglobal('username', 'member'));
        setglobal('adminid', getglobal('adminid', 'member'));
        setglobal('groupid', getglobal('groupid', 'member'));

        if($this->var['member']['newprompt']) {
            $this->var['member']['newprompt_num'] = S::t('common_member_newprompt')->fetch($this->var['member']['uid']);
            $this->var['member']['newprompt_num'] = unserialize($this->var['member']['newprompt_num']['data']);
            $this->var['member']['category_num'] = helper_notification::get_categorynum($this->var['member']['newprompt_num']);
        }

    }

    private function _init_guest() {
        $username = '';
        $groupid = 7;
        if(!empty($this->var['cookie']['con_auth_hash']) && ($openid = authcode($this->var['cookie']['con_auth_hash']))) {
            $this->var['connectguest'] = 1;
            $username = 'QQ_'.substr($openid, -6);
            $this->var['setting']['cacheindexlife'] = 0;
            $this->var['setting']['cachethreadlife'] = 0;
            $groupid = $this->var['setting']['connect']['guest_groupid'] ? $this->var['setting']['connect']['guest_groupid'] : $this->var['setting']['newusergroupid'];
        }
        setglobal('member', array( 'uid' => 0, 'username' => $username, 'adminid' => 0, 'groupid' => $groupid, 'credits' => 0, 'timeoffset' => 9999));
    }

    public function timezone_set($timeoffset = 0) {
        if(function_exists('date_default_timezone_set')) {
            @date_default_timezone_set('Etc/GMT'.($timeoffset > 0 ? '-' : '+').(abs($timeoffset)));
        }
    }

    public function mobile_iconv_recurrence($value) {
        if(is_array($value)) {
            foreach($value AS $key => $val) {
                $value[$key] = $this->mobile_iconv_recurrence($val);
            }
        } else {
            $value = diconv($value, 'utf-8', CHARSET);
        }
        return $value;
    }
}