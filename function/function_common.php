<?php
/**
 * Created by PhpStorm.
 * User: HDRorz
 * Date: 2016-1-27
 * Time: 15:05
 */

define('COMMON_FUNCTION', true);

function system_error($message, $show = true, $save = true, $halt = true) {
    errors::system_error($message, $show, $save, $halt);
}

function getglobal($key, $group = null) {
    global $_G;
    $key = explode('/', $group === null ? $key : $group.'/'.$key);
    $v = &$_G;
    foreach ($key as $k) {
        if (!isset($v[$k])) {
            return null;
        }
        $v = &$v[$k];
    }
    return $v;
}

function setglobal($key , $value, $group = null) {
    global $_G;
    $key = explode('/', $group === null ? $key : $group.'/'.$key);
    $p = &$_G;
    foreach ($key as $k) {
        if(!isset($p[$k]) || !is_array($p[$k])) {
            $p[$k] = array();
        }
        $p = &$p[$k];
    }
    $p = $value;
    return true;
}

function getstatus($status, $position) {
    $t = $status & pow(2, $position - 1) ? 1 : 0;
    return $t;
}

function setstatus($position, $value, $baseon = null) {
    $t = pow(2, $position - 1);
    if($value) {
        $t = $baseon | $t;
    } elseif ($baseon !== null) {
        $t = $baseon & ~$t;
    } else {
        $t = ~$t;
    }
    return $t & 0xFFFF;
}

function isemail($email) {
    return strlen($email) > 6 && strlen($email) <= 32 && preg_match("/^([A-Za-z0-9\-_.+]+)@([A-Za-z0-9\-]+[.][A-Za-z0-9\-.]+)$/", $email);
}

function quescrypt($questionid, $answer) {
    return $questionid > 0 && $answer != '' ? substr(md5($answer.md5($questionid)), 16, 8) : '';
}

function getuserbyuid($uid, $fetch_archive = 0) {
    global $_G;
    static $users = array();
    $fp = fopen(SERVER_ROOT.'/log.txt', 'a+');
    fwrite($fp,var_export('getuserbyid',true)."\n");
    if(empty($users[$uid])) {
        $users[$uid] = S::t('common_member'.($fetch_archive === 2 ? '_archive' : ''))->fetch($uid);
        if($fetch_archive === 1 && empty($users[$uid])) {
            $users[$uid] = S::t('common_member_archive')->fetch($uid);
        }
    }

    if(!isset($users[$uid]['self']) && $uid == getglobal('uid') && getglobal('uid')) {
        //$users[$uid]['self'] = 1;
    }

    fwrite($fp,var_export($users,true)."\n");
    return $users[$uid];
}

function getuserfieldbyuid($uid) {
    static $usersfield = array();
    $fp = fopen(SERVER_ROOT.'/log.txt', 'a+');
    fwrite($fp,var_export('getuserfieldbyid',true)."\n");
    if(empty($usersfield[$uid])) {
        $member_status = S::t('common_member_status')->fetch($uid);
        $member_count = S::t('common_member_count')->fetch($uid);
        $field = array_merge($member_count, $member_status);
        $usersfield[$uid] = array(
            'uid' => $field['uid'],
            'extcredits1' => $field['extcredits1'],
            'extcredits2' => $field['extcredits2'],
            'extcredits3' => $field['extcredits3'],
            'extcredits4' => $field['extcredits4'],
            'extcredits5' => $field['extcredits5'],
            'extcredits6' => $field['extcredits6'],
            'extcredits7' => $field['extcredits7'],
            'extcredits8' => $field['extcredits8'],
            'friends' => $field['friends'],
            'posts' => $field['posts'],
            'threads' => $field['threads'],
            'digestposts' => $field['digestposts'],
            'oltime' => $field['oltime'],
            'regip' => $field['regip'],
            'lastip' => $field['lastip'],
            'lastvisit' => $field['lastvisit'],
            'lastactivity' => $field['lastactivity'],
            'lastpost' => $field['lastpost'],
            'invisible' => $field['invisible'],
        );
    }

    if(!isset($usersfield[$uid]['self']) && $uid == getglobal('uid') && getglobal('uid')) {
        $usersfield[$uid]['self'] = 1;
    }
    fwrite($fp,var_export($usersfield,true)."\n");
    return $usersfield[$uid];
}

function getuserprofile($field) {
    global $_G;
    if(isset($_G['member'][$field])) {
        return $_G['member'][$field];
    }
    static $tablefields = array(
        'count'		=> array('extcredits1','extcredits2','extcredits3','extcredits4','extcredits5','extcredits6','extcredits7','extcredits8','friends','posts','threads','digestposts','doings','blogs','albums','sharings','attachsize','views','oltime','todayattachs','todayattachsize', 'follower', 'following', 'newfollower', 'blacklist'),
        'status'	=> array('regip','lastip','lastvisit','lastactivity','lastpost','lastsendmail','invisible','buyercredit','sellercredit','favtimes','sharetimes','profileprogress'),
        'field_forum'	=> array('publishfeed','customshow','customstatus','medals','sightml','groupterms','authstr','groups','attentiongroup'),
        'field_home'	=> array('videophoto','spacename','spacedescription','domain','addsize','addfriend','menunum','theme','spacecss','blockposition','recentnote','spacenote','privacy','feedfriend','acceptemail','magicgift','stickblogs'),
        'profile'	=> array('realname','gender','birthyear','birthmonth','birthday','constellation','zodiac','telephone','mobile','idcardtype','idcard','address','zipcode','nationality','birthprovince','birthcity','resideprovince','residecity','residedist','residecommunity','residesuite','graduateschool','company','education','occupation','position','revenue','affectivestatus','lookingfor','bloodtype','height','weight','alipay','icq','qq','yahoo','msn','taobao','site','bio','interest','field1','field2','field3','field4','field5','field6','field7','field8'),
        'verify'	=> array('verify1', 'verify2', 'verify3', 'verify4', 'verify5', 'verify6', 'verify7'),
    );
    $profiletable = '';
    foreach($tablefields as $table => $fields) {
        if(in_array($field, $fields)) {
            $profiletable = $table;
            break;
        }
    }
    if($profiletable) {

        if(is_array($_G['member']) && $_G['member']['uid']) {
            space_merge($_G['member'], $profiletable);
        } else {
            foreach($tablefields[$profiletable] as $k) {
                $_G['member'][$k] = '';
            }
        }
        return $_G['member'][$field];
    }
    return null;
}

function avatar($uid, $size = 'middle', $returnsrc = true, $real = FALSE, $static = FALSE, $ucenterurl = '') {
    global $_G;
    static $staticavatar;
    if($staticavatar === null) {
        $staticavatar = $_G['setting']['avatarmethod'];
    }

    $ucenterurl = empty($ucenterurl) ? $_G['setting']['ucenterurl'] : $ucenterurl;
    $size = in_array($size, array('big', 'middle', 'small')) ? $size : 'middle';
    $uid = abs(intval($uid));
    if(!$staticavatar && !$static) {
        return $returnsrc ? $ucenterurl.'/avatar.php?uid='.$uid.'&size='.$size.($real ? '&type=real' : '') : '<img src="'.$ucenterurl.'/avatar.php?uid='.$uid.'&size='.$size.($real ? '&type=real' : '').'" />';
    } else {
        $uid = sprintf("%09d", $uid);
        $dir1 = substr($uid, 0, 3);
        $dir2 = substr($uid, 3, 2);
        $dir3 = substr($uid, 5, 2);
        $file = $ucenterurl.'/data/avatar/'.$dir1.'/'.$dir2.'/'.$dir3.'/'.substr($uid, -2).($real ? '_real' : '').'_avatar_'.$size.'.jpg';
        return $returnsrc ? $file : '<img src="'.$file.'" onerror="this.onerror=null;this.src=\''.$ucenterurl.'/images/noavatar_'.$size.'.gif\'" />';
    }
}

function space_merge(&$values, $tablename, $isarchive = false) {
	global $_G;

	$uid = empty($values['uid'])?$_G['uid']:$values['uid'];
	$var = "member_{$uid}_{$tablename}";
	if($uid) {
        if(!isset($_G[$var])) {
            $ext = $isarchive ? '_archive' : '';
            if(($_G[$var] = S::t('common_member_'.$tablename.$ext)->fetch($uid)) !== false) {
                if($tablename == 'field_home') {
                    $_G['setting']['privacy'] = empty($_G['setting']['privacy']) ? array() : (is_array($_G['setting']['privacy']) ? $_G['setting']['privacy'] : dunserialize($_G['setting']['privacy']));
                    $_G[$var]['privacy'] = empty($_G[$var]['privacy'])? array() : is_array($_G[$var]['privacy']) ? $_G[$var]['privacy'] : dunserialize($_G[$var]['privacy']);
                    foreach (array('feed','view','profile') as $pkey) {
                        if(empty($_G[$var]['privacy'][$pkey]) && !isset($_G[$var]['privacy'][$pkey])) {
                            $_G[$var]['privacy'][$pkey] = isset($_G['setting']['privacy'][$pkey]) ? $_G['setting']['privacy'][$pkey] : array();
                        }
                    }
                    $_G[$var]['acceptemail'] = empty($_G[$var]['acceptemail'])? array() : dunserialize($_G[$var]['acceptemail']);
                    if(empty($_G[$var]['acceptemail'])) {
                        $_G[$var]['acceptemail'] = empty($_G['setting']['acceptemail'])?array():dunserialize($_G['setting']['acceptemail']);
                    }
                }
            } else {
                S::t('common_member_'.$tablename.$ext)->insert(array('uid'=>$uid));
                $_G[$var] = array();
            }
        }
        $values = array_merge($values, $_G[$var]);
    }
}

function manyoulog($logtype, $uids, $action, $fid = '') {
    global $_G;

    if($_G['setting']['my_app_status'] && $logtype == 'user') {
        $action = daddslashes($action);
        $values = array();
        $uids = is_array($uids) ? $uids : array($uids);
        foreach($uids as $uid) {
            $uid = intval($uid);
            S::t('common_member_log')->insert(array('uid' => $uid, 'action' => $action, 'dateline' => TIMESTAMP), false, true);
        }
    }
}

function notification_add($touid, $type, $note, $notevars = array(), $system = 0, $category = -1) {
	global $_G;

    if (!($tospace = getuserbyuid($touid))) {
        return false;
    }
    space_merge($tospace, 'field_home');
    $filter = empty($tospace['privacy']['filter_note']) ? array() : array_keys($tospace['privacy']['filter_note']);

    if ($filter && (in_array($type . '|0', $filter) || in_array($type . '|' . $_G['uid'], $filter))) {
        return false;
    }
    if ($category == -1) {
        $category = 0;
        $categoryname = '';
        if ($type == 'follow' || $type == 'follower') {
            switch ($type) {
                case 'follow' :
                    $category = 5;
                    break;
                case 'follower' :
                    $category = 6;
                    break;
            }
            $categoryname = $type;
        } else {
            foreach ($_G['notice_structure'] as $key => $val) {
                if (in_array($type, $val)) {
                    switch ($key) {
                        case 'mypost' :
                            $category = 1;
                            break;
                        case 'interactive' :
                            $category = 2;
                            break;
                        case 'system' :
                            $category = 3;
                            break;
                        case 'manage' :
                            $category = 4;
                            break;
                        default :
                            $category = 0;
                    }
                    $categoryname = $key;
                    break;
                }
            }
        }
    } else {
        switch ($category) {
            case 1 :
                $categoryname = 'mypost';
                break;
            case 2 :
                $categoryname = 'interactive';
                break;
            case 3 :
                $categoryname = 'system';
                break;
            case 4 :
                $categoryname = 'manage';
                break;
            case 5 :
                $categoryname = 'follow';
                break;
            case 6 :
                $categoryname = 'follower';
                break;
            default :
                $categoryname = 'app';
        }
    }
    if ($category == 0) {
        $categoryname = 'app';
    } elseif ($category == 1 || $category == 2) {
        $categoryname = $type;
    }
    $notevars['actor'] = "<a href=\"home.php?mod=space&uid=$_G[uid]\">" . $_G['member']['username'] . "</a>";
    if (!is_numeric($type)) {
        $vars = explode(':', $note);
        if (count($vars) == 2) {
            $notestring = 'plugin/'.' '.$vars[0].' '.$vars[1];
        } else {
            $notestring = 'notification'.' '.$note;
        }
        $frommyapp = false;
    } else {
        $frommyapp = true;
        $notestring = $note;
    }

    $oldnote = array();
    if ($notevars['from_id'] && $notevars['from_idtype']) {
        $oldnote = S::t('home_notification')->fetch_by_fromid_uid($notevars['from_id'], $notevars['from_idtype'], $touid);
    }
    if (empty($oldnote['from_num'])) $oldnote['from_num'] = 0;
    $notevars['from_num'] = $notevars['from_num'] ? $notevars['from_num'] : 1;
    $setarr = array(
        'uid' => $touid,
        'type' => $type,
        'new' => 1,
        'authorid' => $_G['uid'],
        'author' => $_G['username'],
        'note' => $notestring,
        'dateline' => $_G['timestamp'],
        'from_id' => $notevars['from_id'],
        'from_idtype' => $notevars['from_idtype'],
        'from_num' => ($oldnote['from_num'] + $notevars['from_num']),
        'category' => $category
    );
    if ($system) {
        $setarr['authorid'] = 0;
        $setarr['author'] = '';
    }
    $pkId = 0;
    if ($oldnote['id']) {
        S::t('home_notification')->update($oldnote['id'], $setarr);
        $pkId = $oldnote['id'];
    } else {
        $oldnote['new'] = 0;
        $pkId = S::t('home_notification')->insert($setarr, true);
    }
    $banType = array('task');
    if ($_G['setting']['cloud_status'] && !in_array($type, $banType) && false) {
        /*
        $noticeService = Cloud::loadClass('Service_Client_Notification');
        if ($oldnote['id']) {
            $noticeService->update($touid, $pkId, $setarr['from_num'], $setarr['dateline'], $note);
        } else {
            $extra = $type == 'post' ? array('pId' => $notevars['pid']) : array();
            $extra['notekey'] = $note;
            $noticeService->add($touid, $pkId, $type, $setarr['authorid'], $setarr['author'], $setarr['from_id'], $setarr['from_idtype'], $setarr['note'], $setarr['from_num'], $setarr['dateline'], $extra);
        }
        */
    }

    if (empty($oldnote['new'])) {
        S::t('common_member')->increase($touid, array('newprompt' => 1));
        $newprompt = S::t('common_member_newprompt')->fetch($touid);
        if ($newprompt) {
            $newprompt['data'] = unserialize($newprompt['data']);
            if (!empty($newprompt['data'][$categoryname])) {
                $newprompt['data'][$categoryname] = intval($newprompt['data'][$categoryname]) + 1;
            } else {
                $newprompt['data'][$categoryname] = 1;
            }
            S::t('common_member_newprompt')->update($touid, array('data' => serialize($newprompt['data'])));
        } else {
            S::t('common_member_newprompt')->insert($touid, array($categoryname => 1));
        }
        //$mail_subject = 'notification'.' '.'mail_to_user';
        //sendmail_touser($touid, $mail_subject, $notestring, $frommyapp ? 'myapp' : $type);
    }

    if (!$system && $_G['uid'] && $touid != $_G['uid']) {
        S::t('home_friend')->update_num_by_uid_fuid(1, $_G['uid'], $touid);
    }
}


function memory($cmd, $key='', $value='', $ttl = 0, $prefix = '') {
    if($cmd == 'check') {
        return  S::memory()->enable ? S::memory()->type : '';
    } elseif(S::memory()->enable && in_array($cmd, array('set', 'get', 'rm', 'inc', 'dec'))) {
        if(defined('SERVER_DEBUG') && SERVER_DEBUG) {
            if(is_array($key)) {
                foreach($key as $k) {
                    S::memory()->debug[$cmd][] = ($cmd == 'get' || $cmd == 'rm' ? $value : '').$prefix.$k;
                }
            } else {
                S::memory()->debug[$cmd][] = ($cmd == 'get' || $cmd == 'rm' ? $value : '').$prefix.$key;
            }
        }
        switch ($cmd) {
            case 'set': return S::memory()->set($key, $value, $ttl, $prefix); break;
            case 'get': return S::memory()->get($key, $value); break;
            case 'rm': return S::memory()->rm($key, $value); break;
            case 'inc': return S::memory()->inc($key, $value ? $value : 1); break;
            case 'dec': return S::memory()->dec($key, $value ? $value : -1); break;
        }
    }
    return null;
}

function loadcache($cachenames, $force = false) {
    global $_G;
    static $loadedcache = array();
    $cachenames = is_array($cachenames) ? $cachenames : array($cachenames);
    $caches = array();
    foreach ($cachenames as $k) {
        if(!isset($loadedcache[$k]) || $force) {
            $caches[] = $k;
            $loadedcache[$k] = true;
        }
    }

    if(!empty($caches)) {
        $cachedata = S::t('common_syscache')->fetch_all($caches);
        foreach($cachedata as $cname => $data) {
            if($cname == 'setting') {
                $_G['setting'] = $data;
            } elseif($cname == 'usergroup_'.$_G['groupid']) {
                $_G['cache'][$cname] = $_G['group'] = $data;
            } elseif($cname == 'style_default') {
                $_G['cache'][$cname] = $_G['style'] = $data;
            } elseif($cname == 'grouplevels') {
                $_G['grouplevels'] = $data;
            } else {
                $_G['cache'][$cname] = $data;
            }
        }
    }
    return true;
}

function loaducenter() {
    require_once SERVER_ROOT.'/config/config_ucenter.php';
    require_once SERVER_ROOT.'/uc_client/client.php';
}

/*
 * common function in discuz version
 */

function dimplode($array) {
    if(!empty($array)) {
        $array = array_map('addslashes', $array);
        return "'".implode("','", is_array($array) ? $array : array($array))."'";
    } else {
        return 0;
    }
}

function dintval($int, $allowarray = false) {
    $ret = intval($int);
    if($int == $ret || !$allowarray && is_array($int)) return $ret;
    if($allowarray && is_array($int)) {
        foreach($int as &$v) {
            $v = dintval($v, true);
        }
        return $int;
    } elseif($int <= 0xffffffff) {
        $l = strlen($int);
        $m = substr($int, 0, 1) == '-' ? 1 : 0;
        if(($l - $m) === strspn($int,'0987654321', $m)) {
            return $int;
        }
    }
    return $ret;
}

function durlencode($url) {
    static $fix = array('%21', '%2A','%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
    static $replacements = array('!', '*', ';', ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
    return str_replace($fix, $replacements, urlencode($url));
}

function daddslashes($string, $force = 1) {
    if(is_array($string)) {
        $keys = array_keys($string);
        foreach($keys as $key) {
            $val = $string[$key];
            unset($string[$key]);
            $string[addslashes($key)] = daddslashes($val, $force);
        }
    } else {
        $string = addslashes($string);
    }
    return $string;
}

function dhtmlspecialchars($string, $flags = null) {
    if(is_array($string)) {
        foreach($string as $key => $val) {
            $string[$key] = dhtmlspecialchars($val, $flags);
        }
    } else {
        if($flags === null) {
            $string = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string);
            if(strpos($string, '&amp;#') !== false) {
                $string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', $string);
            }
        } else {
            if(PHP_VERSION < '5.4.0') {
                $string = htmlspecialchars($string, $flags);
            } else {
                if(strtolower(CHARSET) == 'utf-8') {
                    $charset = 'UTF-8';
                } else {
                    $charset = 'ISO-8859-1';
                }
                $string = htmlspecialchars($string, $flags, $charset);
            }
        }
    }
    return $string;
}

function dstrpos($string, $arr, $returnvalue = false) {
    if(empty($string)) return false;
    foreach((array)$arr as $v) {
        if(strpos($string, $v) !== false) {
            $return = $returnvalue ? $v : true;
            return $return;
        }
    }
    return false;
}

function dsign($str, $length = 16){
    return substr(md5($str.getglobal('config/security/authkey')), 0, ($length ? max(8, $length) : 16));
}

function dstrlen($str) {
    if(strtolower(CHARSET) != 'utf-8') {
        return strlen($str);
    }
    $count = 0;
    for($i = 0; $i < strlen($str); $i++){
        $value = ord($str[$i]);
        if($value > 127) {
            $count++;
            if($value >= 192 && $value <= 223) $i++;
            elseif($value >= 224 && $value <= 239) $i = $i + 2;
            elseif($value >= 240 && $value <= 247) $i = $i + 3;
        }
        $count++;
    }
    return $count;
}

function dstripslashes($string) {
    if(empty($string)) return $string;
    if(is_array($string)) {
        foreach($string as $key => $val) {
            $string[$key] = dstripslashes($val);
        }
    } else {
        $string = stripslashes($string);
    }
    return $string;
}

function dmkdir($dir, $mode = 0777, $makeindex = TRUE){
    if(!is_dir($dir)) {
        dmkdir(dirname($dir), $mode, $makeindex);
        @mkdir($dir, $mode);
        if(!empty($makeindex)) {
            @touch($dir.'/index.html'); @chmod($dir.'/index.html', 0777);
        }
    }
    return true;
}

function dunserialize($data) {
    if(($ret = unserialize($data)) === false) {
        $ret = unserialize(stripslashes($data));
    }
    return $ret;
}

function dgmdate($timestamp, $format = 'dt', $timeoffset = '9999', $uformat = '') {
    global $_G;
    $format == 'u' && !$_G['setting']['dateconvert'] && $format = 'dt';
    static $dformat, $tformat, $dtformat, $offset, $lang;
    if($dformat === null) {
        $dformat = getglobal('setting/dateformat');
        $tformat = getglobal('setting/timeformat');
        $dtformat = $dformat.' '.$tformat;
        $offset = getglobal('member/timeoffset');
        $sysoffset = getglobal('setting/timeoffset');
        $offset = $offset == 9999 ? ($sysoffset ? $sysoffset : 0) : $offset;
        $lang =  array(
            'before' => '前',
            'day' => '天',
            'yday' => '昨天',
            'byday' => '前天',
            'hour' => '小时',
            'half' => '半',
            'min' => '分钟',
            'sec' => '秒',
            'now' => '刚刚',
        );
    }
    $timeoffset = $timeoffset == 9999 ? $offset : $timeoffset;
    $timestamp += $timeoffset * 3600;
    $format = empty($format) || $format == 'dt' ? $dtformat : ($format == 'd' ? $dformat : ($format == 't' ? $tformat : $format));
    if($format == 'u') {
        $todaytimestamp = TIMESTAMP - (TIMESTAMP + $timeoffset * 3600) % 86400 + $timeoffset * 3600;
        $s = gmdate(!$uformat ? $dtformat : $uformat, $timestamp);
        $time = TIMESTAMP + $timeoffset * 3600 - $timestamp;
        if($timestamp >= $todaytimestamp) {
            if($time > 3600) {
                $return = intval($time / 3600).'&nbsp;'.$lang['hour'].$lang['before'];
            } elseif($time > 1800) {
                $return = $lang['half'].$lang['hour'].$lang['before'];
            } elseif($time > 60) {
                $return = intval($time / 60).'&nbsp;'.$lang['min'].$lang['before'];
            } elseif($time > 0) {
                $return = $time.'&nbsp;'.$lang['sec'].$lang['before'];
            } elseif($time == 0) {
                $return = $lang['now'];
            } else {
                $return = $s;
            }
            if($time >=0 && !defined('IN_MOBILE')) {
                $return = '<span title="'.$s.'">'.$return.'</span>';
            }
        } elseif(($days = intval(($todaytimestamp - $timestamp) / 86400)) >= 0 && $days < 7) {
            if($days == 0) {
                $return = $lang['yday'].'&nbsp;'.gmdate($tformat, $timestamp);
            } elseif($days == 1) {
                $return = $lang['byday'].'&nbsp;'.gmdate($tformat, $timestamp);
            } else {
                $return = ($days + 1).'&nbsp;'.$lang['day'].$lang['before'];
            }
            if(!defined('IN_MOBILE')) {
                $return = '<span title="'.$s.'">'.$return.'</span>';
            }
        } else {
            $return = $s;
        }
        return $return;
    } else {
        return gmdate($format, $timestamp);
    }
}

function dmktime($date) {
    if(strpos($date, '-')) {
        $time = explode('-', $date);
        return mktime(0, 0, 0, $time[1], $time[2], $time[0]);
    }
    return 0;
}

function dnumber($number) {
    return abs($number) > 10000 ? '<span title="'.$number.'">'.intval($number / 10000).'万'.'</span>' : $number;
}

function random($length, $numeric = 0) {
    $seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
    $seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
    if($numeric) {
        $hash = '';
    } else {
        $hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
        $length--;
    }
    $max = strlen($seed) - 1;
    for($i = 0; $i < $length; $i++) {
        $hash .= $seed{mt_rand(0, $max)};
    }
    return $hash;
}

/*
 * discuz funtion get ip location
 */
function convertip($ip) {

    $return = '';

    if(preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $ip)) {

        $iparray = explode('.', $ip);

        if($iparray[0] == 10 || $iparray[0] == 127 || ($iparray[0] == 192 && $iparray[1] == 168) || ($iparray[0] == 172 && ($iparray[1] >= 16 && $iparray[1] <= 31))) {
            $return = '- LAN';
        } elseif($iparray[0] > 255 || $iparray[1] > 255 || $iparray[2] > 255 || $iparray[3] > 255) {
            $return = '- Invalid IP Address';
        } else {
            $tinyipfile = SERVER_ROOT.'./data/ipdata/tinyipdata.dat';
            if(@file_exists($tinyipfile)) {
                $return = convertip_tiny($ip, $tinyipfile);
            }
        }
    }

    return $return;

}

function convertip_tiny($ip, $ipdatafile) {

    static $fp = NULL, $offset = array(), $index = NULL;

    $ipdot = explode('.', $ip);
    $ip    = pack('N', ip2long($ip));

    $ipdot[0] = (int)$ipdot[0];
    $ipdot[1] = (int)$ipdot[1];

    if($fp === NULL && $fp = @fopen($ipdatafile, 'rb')) {
        $offset = @unpack('Nlen', @fread($fp, 4));
        $index  = @fread($fp, $offset['len'] - 4);
    } elseif($fp == FALSE) {
        return  '- Invalid IP data file';
    }

    $length = $offset['len'] - 1028;
    $start  = @unpack('Vlen', $index[$ipdot[0] * 4] . $index[$ipdot[0] * 4 + 1] . $index[$ipdot[0] * 4 + 2] . $index[$ipdot[0] * 4 + 3]);

    for ($start = $start['len'] * 8 + 1024; $start < $length; $start += 8) {

        if ($index{$start} . $index{$start + 1} . $index{$start + 2} . $index{$start + 3} >= $ip) {
            $index_offset = @unpack('Vlen', $index{$start + 4} . $index{$start + 5} . $index{$start + 6} . "\x0");
            $index_length = @unpack('Clen', $index{$start + 7});
            break;
        }
    }

    @fseek($fp, $offset['len'] + $index_offset['len'] - 1024);
    if($index_length['len']) {
        return '- '.@fread($fp, $index_length['len']);
    } else {
        return '- Unknown';
    }

}