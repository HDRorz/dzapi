<?php
/**
 * Created by PhpStorm.
 * User: HDRorz
 * Date: 2016-1-29
 * Time: 16:21
 */

function userexists($username) {
    $fp = fopen(SERVER_ROOT.'/log.txt', 'a+');
    fwrite($fp,var_export('userexists',true)."\n");

    if(!function_exists('uc_user_checkname')) {
        loaducenter();
    }

    return uc_user_checkname($username);
}

function userlogin($username, $password, $questionid, $answer, $loginfield = 'username', $ip = '') {
    $return = array();

    if($loginfield == 'uid' && false) {
        $isuid = 1;
    } elseif($loginfield == 'email') {
        $isuid = 2;
    } elseif($loginfield == 'auto') {
        $isuid = 3;
    } else {
        $isuid = 0;
    }

    if(!function_exists('uc_user_login')) {
        loaducenter();
    }
    if($isuid == 3) {
        if(!strcmp(dintval($username), $username) && getglobal('setting/uidlogin')) {
            $return['ucresult'] = uc_user_login($username, $password, 1, 1, $questionid, $answer, $ip);
        } elseif(isemail($username)) {
            $return['ucresult'] = uc_user_login($username, $password, 2, 1, $questionid, $answer, $ip);
        }
        if($return['ucresult'][0] <= 0 && $return['ucresult'][0] != -3) {
            $return['ucresult'] = uc_user_login(addslashes($username), $password, 0, 1, $questionid, $answer, $ip);
        }
    } else {
        $return['ucresult'] = uc_user_login(addslashes($username), $password, $isuid, 1, $questionid, $answer, $ip);
    }
    $tmp = array();
    $duplicate = '';
    list($tmp['uid'], $tmp['username'], $tmp['password'], $tmp['email'], $duplicate) = $return['ucresult'];
    $return['ucresult'] = $tmp;
    if($duplicate && $return['ucresult']['uid'] > 0 || $return['ucresult']['uid'] <= 0) {
        $return['status'] = 0;
        return $return;
    }

    $member = getuserbyuid($return['ucresult']['uid'], 1);
    if(!$member || empty($member['uid'])) {
        $return['status'] = -1;
        return $return;
    }
    $return['member'] = $member;
    $return['status'] = 1;
    if($member['_inarchive']) {
        S::t('common_member_archive')->move_to_master($member['uid']);
    }
    if($member['email'] != $return['ucresult']['email']) {
        S::t('common_member')->update($return['ucresult']['uid'], array('email' => $return['ucresult']['email']));
    }

    return $return;
}

function setloginstatus($member, $time) {
    global $_G;

    //TODO:
}

function logincheck($username) {
    global $_G;

    $return = 0;
    $username = trim($username);
    loaducenter();
    if(function_exists('uc_user_logincheck')) {
        $return = uc_user_logincheck(addslashes($username), $_G['clientip']);
    } else {
        $login = S::t('common_failedlogin')->fetch_ip($_G['clientip']);
        $return = (!$login || (TIMESTAMP - $login['lastupdate'] > 900)) ? 5 : max(0, 5 - $login['count']);

        if(!$login) {
            S::t('common_failedlogin')->insert(array(
                'ip' => $_G['clientip'],
                'count' => 0,
                'lastupdate' => TIMESTAMP
            ), false, true);
        } elseif(TIMESTAMP - $login['lastupdate'] > 900) {
            S::t('common_failedlogin')->insert(array(
                'ip' => $_G['clientip'],
                'count' => 0,
                'lastupdate' => TIMESTAMP
            ), false, true);
            S::t('common_failedlogin')->delete_old(901);
        }
    }
    return $return;
}

function loginfailed($username) {
    global $_G;

    loaducenter();
    if(function_exists('uc_user_logincheck')) {
        return;
    }
    S::t('common_failedlogin')->update_failed($_G['clientip']);
}

function failedipcheck($numiptry, $timeiptry) {
    global $_G;
    if(!$numiptry) {
        return false;
    }
    list($ip1, $ip2) = explode('.', $_G['clientip']);
    $ip = $ip1.'.'.$ip2;
    return $numiptry <= S::t('common_failedip')->get_ip_count($ip, TIMESTAMP - $timeiptry);
}

function failedip() {
    global $_G;
    list($ip1, $ip2) = explode('.', $_G['clientip']);
    $ip = $ip1.'.'.$ip2;
    S::t('common_failedip')->insert_ip($ip);
}

function checkemail($email) {
    global $_G;

    $email = strtolower(trim($email));
    if(strlen($email) > 32) {
        showmessage('profile_email_illegal', '', array(), array('handle' => false));
    }
    if($_G['setting']['regmaildomain']) {
        $maildomainexp = '/('.str_replace("\r\n", '|', preg_quote(trim($_G['setting']['maildomainlist']), '/')).')$/i';
        if($_G['setting']['regmaildomain'] == 1 && !preg_match($maildomainexp, $email)) {
            showmessage('profile_email_domain_illegal', '', array(), array('handle' => false));
        } elseif($_G['setting']['regmaildomain'] == 2 && preg_match($maildomainexp, $email)) {
            showmessage('profile_email_domain_illegal', '', array(), array('handle' => false));
        }
    }

    loaducenter();
    $ucresult = uc_user_checkemail($email);

    if($ucresult == -4) {
        showmessage('profile_email_illegal', '', array(), array('handle' => false));
    } elseif($ucresult == -5) {
        showmessage('profile_email_domain_illegal', '', array(), array('handle' => false));
    } elseif($ucresult == -6) {
        showmessage('profile_email_duplicate', '', array(), array('handle' => false));
    }
}

function userregister($username, $password, $password2, $email, $questionid, $answer) {
    $fp = fopen(SERVER_ROOT.'/log.txt', 'a+');
    fwrite($fp, "userregister \n");

    fwrite($fp, "$username \n");

    fwrite($fp, "$email \n");

    global $_G;

    if($_G['uid']) {
        //TODO yi deng lu
    }

    if(!function_exists('uc_user_register')) {
        loaducenter();
    }

    $bbrules = & $_G['setting']['bbrules'];
    $bbrulesforce = & $_G['setting']['bbrulesforce'];
    $bbrulestxt = & $_G['setting']['bbrulestxt'];
    $welcomemsg = & $_G['setting']['welcomemsg'];
    $welcomemsgtitle = & $_G['setting']['welcomemsgtitle'];
    $welcomemsgtxt = & $_G['setting']['welcomemsgtxt'];
    $regname = $_G['setting']['regname'];

    if($_G['setting']['regverify']) {
        if($_G['setting']['areaverifywhite']) {
            $location = $whitearea = '';
            $location = trim(convertip($_G['clientip'], "./"));
            if($location) {
                $whitearea = preg_quote(trim($_G['setting']['areaverifywhite']), '/');
                $whitearea = str_replace(array("\\*"), array('.*'), $whitearea);
                $whitearea = '.*'.$whitearea.'.*';
                $whitearea = '/^('.str_replace(array("\r\n", ' '), array('.*|.*', ''), $whitearea).')$/i';
                if(@preg_match($whitearea, $location)) {
                    $_G['setting']['regverify'] = 0;
                }
            }
        }

        if($_G['cache']['ipctrl']['ipverifywhite']) {
            foreach(explode("\n", $_G['cache']['ipctrl']['ipverifywhite']) as $ctrlip) {
                if(preg_match("/^(".preg_quote(($ctrlip = trim($ctrlip)), '/').")/", $_G['clientip'])) {
                    $_G['setting']['regverify'] = 0;
                    break;
                }
            }
        }
    }

    $invitestatus = false;
    if($_G['setting']['regstatus'] == 2) {
        if($_G['setting']['inviteconfig']['inviteareawhite']) {
            $location = $whitearea = '';
            $location = trim(convertip($_G['clientip'], "./"));
            if($location) {
                $whitearea = preg_quote(trim($_G['setting']['inviteconfig']['inviteareawhite']), '/');
                $whitearea = str_replace(array("\\*"), array('.*'), $whitearea);
                $whitearea = '.*'.$whitearea.'.*';
                $whitearea = '/^('.str_replace(array("\r\n", ' '), array('.*|.*', ''), $whitearea).')$/i';
                if(@preg_match($whitearea, $location)) {
                    $invitestatus = true;
                }
            }
        }

        if($_G['setting']['inviteconfig']['inviteipwhite']) {
            foreach(explode("\n", $_G['setting']['inviteconfig']['inviteipwhite']) as $ctrlip) {
                if(preg_match("/^(".preg_quote(($ctrlip = trim($ctrlip)), '/').")/", $_G['clientip'])) {
                    $invitestatus = true;
                    break;
                }
            }
        }
    }

    $groupinfo = array();
    if($_G['setting']['regverify']) {
        $groupinfo['groupid'] = 8;
    } else {
        $groupinfo['groupid'] = $_G['setting']['newusergroupid'];
    }

    //TODO send short message


    if ($_G['setting']['regstatus'] == 2 && empty($invite) && !$invitestatus) {
        return 'not open registration invite';
    }

    $activation = array();
    if (!$activation) {
        $usernamelen = strlen($username);
        if ($usernamelen < 3) {
            return 'username tooshort';
        } elseif ($usernamelen > 15) {
            return 'username toolong';
        }
        if (uc_get_user(addslashes($username)) && !S::t('common_member')->fetch_uid_by_username($username) && !S::t('common_member_archive')->fetch_uid_by_username($username)) {
            return 'username exist';
        }
        if ($_G['setting']['pwlength']) {
            if (strlen($password) < $_G['setting']['pwlength']) {
                return 'password tooshort at least'.$_G['setting']['pwlength'];
            }
        }
        if ($_G['setting']['strongpw']) {
            $strongpw_str = '';
            if (in_array(1, $_G['setting']['strongpw']) && !preg_match("/\d+/", $password)) {
                $strongpw_str .= ' number';
            }
            if (in_array(2, $_G['setting']['strongpw']) && !preg_match("/[a-z]+/", $password)) {
                $strongpw_str .= ' lowercase';
            }
            if (in_array(3, $_G['setting']['strongpw']) && !preg_match("/[A-Z]+/", $password)) {
                $strongpw_str .= ' uppercase';
            }
            if (in_array(4, $_G['setting']['strongpw']) && !preg_match("/[^a-zA-z0-9]+/", $password)) {
                $strongpw_str .= ' symbol';
            }
            if ($strongpw_str) {
                return 'password weak needs'.$strongpw_str;
            }
        }
        $email = strtolower(trim($email));
        if (empty($_G['setting']['ignorepassword'])) {
            if ($password !== $password2) {
                return 'passwd notmatch';
            }

            if (!$password || $password != addslashes($password)) {
                return 'passwd illegal';
            }
        } else {
            $password = md5(random(10));
        }
    }

    $censorexp = '/^(' . str_replace(array('\\*', "\r\n", ' '), array('.*', '|', ''), preg_quote(($_G['setting']['censoruser'] = trim($_G['setting']['censoruser'])), '/')) . ')$/i';

    if ($_G['setting']['censoruser'] && @preg_match($censorexp, $username)) {
        return 'username protect';
    }

    if ($_G['setting']['regverify'] == 2 && !trim($_GET['regmessage'])) {
        return 'required info invalid';
    }

    if ($_G['cache']['ipctrl']['ipregctrl']) {
        foreach (explode("\n", $_G['cache']['ipctrl']['ipregctrl']) as $ctrlip) {
            if (preg_match("/^(" . preg_quote(($ctrlip = trim($ctrlip)), '/') . ")/", $_G['clientip'])) {
                $ctrlip = $ctrlip . '%';
                $_G['setting']['regctrl'] = $_G['setting']['ipregctrltime'];
                break;
            } else {
                $ctrlip = $_G['clientip'];
            }
        }
    } else {
        $ctrlip = $_G['clientip'];
    }

    if ($_G['setting']['regctrl']) {
        if (S::t('common_regip')->count_by_ip_dateline($ctrlip, $_G['timestamp'] - $_G['setting']['regctrl'] * 3600)) {
            return 'register ctrl this ip cant register in '.$_G['setting']['regctrl'].' hours';
        }
    }

    $setregip = null;
    if ($_G['setting']['regfloodctrl']) {
        $regip = S::t('common_regip')->fetch_by_ip_dateline($_G['clientip'], $_G['timestamp'] - 86400);
        if ($regip) {
            if ($regip['count'] >= $_G['setting']['regfloodctrl']) {
                return 'register flood ctrl this ip can only register '.$_G['setting']['regfloodctrl'].'th';
            } else {
                $setregip = 1;
            }
        } else {
            $setregip = 2;
        }
    }

    $profile = $verifyarr = array();

    if (!$activation) {
        $uid = uc_user_register(addslashes($username), $password, $email, $questionid, $answer, $_G['clientip']);
        if ($uid <= 0) {
            if ($uid == -1) {
                return ('profile_username_illegal');
            } elseif ($uid == -2) {
                return ('profile_username_protect');
            } elseif ($uid == -3) {
                return ('profile_username_duplicate');
            } elseif ($uid == -4) {
                return ('profile_email_illegal');
            } elseif ($uid == -5) {
                return ('profile_email_domain_illegal');
            } elseif ($uid == -6) {
                return ('profile_email_duplicate');
            } else {
                return ('undefined_action');
            }
        }
    } else {
        list($uid, $username, $email) = $activation;
    }
    $_G['username'] = $username;
    if (getuserbyuid($uid, 1)) {
        if (!$activation) {
            uc_user_delete($uid);
        }
        return 'uid '.$uid. 'duplicate';
    }

    $password = md5(random(10));
    $secques = $questionid > 0 ? random(8) : '';

    if ($_FILES) {
        $upload = new discuz_upload();

        foreach ($_FILES as $key => $file) {
            $field_key = 'field_' . $key;
            if (!empty($_G['cache']['fields_register'][$field_key]) && $_G['cache']['fields_register'][$field_key]['formtype'] == 'file') {

                $upload->init($file, 'profile');
                $attach = $upload->attach;

                if (!$upload->error()) {
                    $upload->save();

                    if (!$upload->get_image_info($attach['target'])) {
                        @unlink($attach['target']);
                        continue;
                    }

                    $attach['attachment'] = dhtmlspecialchars(trim($attach['attachment']));
                    if ($_G['cache']['fields_register'][$field_key]['needverify']) {
                        $verifyarr[$key] = $attach['attachment'];
                    } else {
                        $profile[$key] = $attach['attachment'];
                    }
                }
            }
        }
    }

    if ($setregip !== null) {
        if ($setregip == 1) {
            S::t('common_regip')->update_count_by_ip($_G['clientip']);
        } else {
            S::t('common_regip')->insert(array('ip' => $_G['clientip'], 'count' => 1, 'dateline' => $_G['timestamp']));
        }
    }

    if ($invite && $_G['setting']['inviteconfig']['invitegroupid']) {
        $groupinfo['groupid'] = $_G['setting']['inviteconfig']['invitegroupid'];
    }

    $init_arr = array('credits' => explode(',', $_G['setting']['initcredits']), 'profile' => $profile, 'emailstatus' => $emailstatus);

    S::t('common_member')->insert($uid, $username, $password, $email, $_G['clientip'], $groupinfo['groupid'], $init_arr);

    if ($_G['setting']['regctrl'] || $_G['setting']['regfloodctrl']) {
        S::t('common_regip')->delete_by_dateline($_G['timestamp'] - ($_G['setting']['regctrl'] > 72 ? $_G['setting']['regctrl'] : 72) * 3600);
        if ($_G['setting']['regctrl']) {
            S::t('common_regip')->insert(array('ip' => $_G['clientip'], 'count' => -1, 'dateline' => $_G['timestamp']));
        }
    }

    setloginstatus(array(
        'uid' => $uid,
        'username' => $_G['username'],
        'password' => $password,
        'groupid' => $groupinfo['groupid'],
    ), 0);
    updatestat('register');


    if ($welcomemsg && !empty($welcomemsgtxt)) {
        $welcomemsgtitle = replacesitevar($welcomemsgtitle);
        $welcomemsgtxt = replacesitevar($welcomemsgtxt);
        if ($welcomemsg == 1) {
            $welcomemsgtxt = nl2br(str_replace(':', '&#58;', $welcomemsgtxt));
            notification_add($uid, 'system', $welcomemsgtxt, array('from_id' => 0, 'from_idtype' => 'welcomemsg'), 1);
        } elseif ($welcomemsg == 2) {
            //sendmail_cron($email, $welcomemsgtitle, $welcomemsgtxt);
        } elseif ($welcomemsg == 3) {
            //sendmail_cron($email, $welcomemsgtitle, $welcomemsgtxt);
            $welcomemsgtxt = nl2br(str_replace(':', '&#58;', $welcomemsgtxt));
            notification_add($uid, 'system', $welcomemsgtxt, array('from_id' => 0, 'from_idtype' => 'welcomemsg'), 1);
        }
    }

    switch ($_G['setting']['regverify']) {
        case 1:
            $idstring = random(6);
            $authstr = $_G['setting']['regverify'] == 1 ? "$_G[timestamp]\t2\t$idstring" : '';
            S::t('common_member_field_forum')->update($_G['uid'], array('authstr' => $authstr));
            $verifyurl = "{$_G[siteurl]}member.php?mod=activate&amp;uid={$_G[uid]}&amp;id=$idstring";
            /*
             $email_verify_message = lang('email', 'email_verify_message', array(
                'username' => $_G['member']['username'],
                'bbname' => $_G['setting']['bbname'],
                'siteurl' => $_G['siteurl'],
                'url' => $verifyurl
            ));
            if (!sendmail("$username <$email>", lang('email', 'email_verify_subject'), $email_verify_message)) {
                runlog('sendmail', "$email sendmail failed.");
            }
            */
            $message = 'register_email_verify';
            $locationmessage = 'register_email_verify_location';
            $refreshtime = 10000;
            break;
        case 2:
            $message = 'register_manual_verify';
            $locationmessage = 'register_manual_verify_location';
            break;
        default:
            $message = 'register_succeed';
            $locationmessage = 'register_succeed_location';
            break;
    }
    $param = array('bbname' => $_G['setting']['bbname'], 'username' => $_G['username'], 'usergroup' => $_G['group']['grouptitle'], 'uid' => $_G['uid']);
    return $message;
}

function updatestat($type, $primary=0, $num=1) {
    $uid = getglobal('uid');
    $updatestat = getglobal('setting/updatestat');
    if(empty($uid) || empty($updatestat)) {
        return false;
    }
    S::t('common_stat')->updatestat($uid, $type, $primary, $num);
}

function replacesitevar($string, $replaces = array()) {
    global $_G;
    $sitevars = array(
        '{sitename}' => $_G['setting']['sitename'],
        '{bbname}' => $_G['setting']['bbname'],
        '{time}' => dgmdate(TIMESTAMP, 'Y-n-j H:i'),
        '{adminemail}' => $_G['setting']['adminemail'],
        '{username}' => $_G['member']['username'],
        '{myname}' => $_G['member']['username']
    );
    $replaces = array_merge($sitevars, $replaces);
    return str_replace(array_keys($replaces), array_values($replaces), $string);
}