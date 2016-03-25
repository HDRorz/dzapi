<?php
/**
 * Created by PhpStorm.
 * User: hdrorz
 * Date: 2/4/16
 * Time: 12:51 AM
 */

function loadforum($fid = null, $tid = null) {
    global $_G;
    $tid = isset($tid) ? intval($tid) : null;
    $fid = isset($fid) ? intval($fid) : null;

    if(isset($_G['forum']['fid']) && $_G['forum']['fid'] == $fid || isset($_G['thread']['tid']) && $_G['thread']['tid'] == $tid){
        return null;
    }

    if($fid) {
        $fid = is_numeric($fid) ? intval($fid) : (!empty($_G['setting']['forumfids'][$fid]) ? $_G['setting']['forumfids'][$fid] : 0);
    }

    $modthreadkey = '';
    $_G['forum_auditstatuson'] = $modthreadkey ? true : false;

    $adminid = $_G['adminid'];

    if(!empty($tid) || !empty($fid)) {

        if(!empty ($tid)) {
            $archiveid = null;
            $_G['thread'] = get_thread_by_tid($tid, $archiveid);
            $_G['thread']['allreplies'] = $_G['thread']['replies'] + $_G['thread']['comments'];
            if(!$_G['forum_auditstatuson'] && !empty($_G['thread'])
                && !($_G['thread']['displayorder'] >= 0 || (in_array($_G['thread']['displayorder'], array(-4,-3,-2)) && $_G['uid'] && $_G['thread']['authorid'] == $_G['uid']))) {
                $_G['thread'] = null;
            }

            $_G['forum_thread'] = & $_G['thread'];

            if(empty($_G['thread'])) {
                $fid = $tid = 0;
            } else {
                $fid = $_G['thread']['fid'];
                $tid = $_G['thread']['tid'];
            }
        }

        if($fid) {
            $forum = S::t('forum_forum')->fetch_info_by_fid($fid);
        }

        if($forum) {
            $forum['allowview'] = 1;
            if($_G['uid']) {
                $forum['allowpost'] = 1;
                $forum['allowreply'] = 1;
                $forum['allowgetattach'] = 1;
                $forum['allowgetimage'] = 1;
                $forum['allowpostattach'] = 0;
                $forum['allowpostimage'] = 0;
                if($adminid == 3) {
                    $forum['ismoderator'] = S::t('forum_moderator')->fetch_uid_by_fid_uid($fid, $_G['uid']);
                }
            }
            $forum['ismoderator'] = !empty($forum['ismoderator']) || $adminid == 1 || $adminid == 2 ? 1 : 0;
            $fid = $forum['fid'];
            $gorup_admingroupids = $_G['setting']['group_admingroupids'] ? dunserialize($_G['setting']['group_admingroupids']) : array('1' => '1');

            foreach(array('threadtypes', 'threadsorts', 'creditspolicy', 'modrecommend') as $key) {
                $forum[$key] = !empty($forum[$key]) ? dunserialize($forum[$key]) : array();
                if(!is_array($forum[$key])) {
                    $forum[$key] = array();
                }
            }
        } else {
            $fid = 0;
        }
    }

    $_G['fid'] = $fid;
    $_G['tid'] = $tid;
    $_G['forum'] = &$forum;
    $_G['current_grouplevel'] = &$grouplevel;

    if(empty($_G['uid'])) {
        $_G['group']['allowpostactivity'] = $_G['group']['allowpostpoll'] = $_G['group']['allowvote'] = $_G['group']['allowpostreward'] = $_G['group']['allowposttrade'] = $_G['group']['allowpostdebate'] = $_G['group']['allowpostrushreply'] = 0;
    }
}

function get_thread_by_tid($tid, $forcetableid = null) {
    global $_G;

    $ret = array();
    if(!is_numeric($tid)) {
        return $ret;
    }
    loadcache('threadtableids');
    $threadtableids = array(0);
    if(!empty($_G['cache']['threadtableids'])) {
        if($forcetableid === null || ($forcetableid > 0 && !in_array($forcetableid, $_G['cache']['threadtableids']))) {
            $threadtableids = array_merge($threadtableids, $_G['cache']['threadtableids']);
        } else {
            $threadtableids = array(intval($forcetableid));
        }
    }
    $threadtableids = array_unique($threadtableids);
    foreach($threadtableids as $tableid) {
        $tableid = $tableid > 0 ? $tableid : 0;
        $ret = S::t('forum_thread')->fetch($tid, $tableid);
        if($ret) {
            $ret['threadtable'] = S::t('forum_thread')->get_table_name($tableid);
            $ret['threadtableid'] = $tableid;
            $ret['posttable'] = 'forum_post'.($ret['posttableid'] ? '_'.$ret['posttableid'] : '');
            break;
        }
    }

    if(!is_array($ret)) {
        $ret = array();
    } elseif($_G['setting']['optimizeviews']) {
        if(($row = S::t('forum_threadaddviews')->fetch($tid))) {
            $ret['addviews'] = intval($row['addviews']);
            $ret['views'] += $ret['addviews'];
        }
    }

    return $ret;
}

function getforumslist() {

}

function getthreadslist($fid, $page, $filter) {
    global $_G;

    $fp = fopen(SERVER_ROOT.'/log.txt', 'a+');
    fwrite($fp, "getthreadslist \n");

    if($page == 1) {
        // TODO
        if ($_G['cache']['announcements_forum'] && (!$_G['cache']['announcements_forum']['endtime'] || $_G['cache']['announcements_forum']['endtime'] > TIMESTAMP)) {
            $announcement = $_G['cache']['announcements_forum'];
            $announcement['starttime'] = dgmdate($announcement['starttime'], 'd');
        } else {
            $announcement = NULL;
        }
    }

    if($filter != 'hot') {
        $page = $_G['setting']['threadmaxpages'] && $page > $_G['setting']['threadmaxpages'] ? 1 : $page;
    }

    $start_limit = ($page - 1) * $_G['tpp'];
    $realpages = @ceil($_G['forum_threadcount']/$_G['tpp']);
    $maxpage = ($_G['setting']['threadmaxpages'] && $_G['setting']['threadmaxpages'] < $realpages) ? $_G['setting']['threadmaxpages'] : $realpages;
    $nextpage = ($page + 1) > $maxpage ? 1 : ($page + 1);

    $specialtype = array('poll' => 1, 'trade' => 2, 'reward' => 3, 'activity' => 4, 'debate' => 5);
    $filterfield = array('digest', 'recommend', 'sortall', 'typeid', 'sortid', 'dateline', 'page', 'orderby', 'specialtype', 'author', 'view', 'reply', 'lastpost', 'hot');

    $forumdisplayadd = array('orderby' => '');
    foreach($filterfield as $v) {
        $forumdisplayadd[$v] = '';
    }

    $filter = in_array($filter, $filterfield) ? $filter : '';
    $filterbool = !empty($filter);
    $filterarr = $multiadd = array();
    $threadclasscount = array();

    if($_G['forum']['relatedgroup']) {
        $relatedgroup = explode(',', $_G['forum']['relatedgroup']);
        $relatedgroup[] = $fid;
        $filterarr['inforum'] = $relatedgroup;
    } else {
        $filterarr['inforum'] = $fid;
    }

    $thisgid = $_G['forum']['type'] == 'forum' ? $_G['forum']['fup'] : (!empty($_G['cache']['forums'][$_G['forum']['fup']]['fup']) ? $_G['cache']['forums'][$_G['forum']['fup']]['fup'] : 0);
    $forumstickycount = $stickycount = 0;
    $stickytids = '';
    $showsticky = !defined('MOBILE_HIDE_STICKY') || !MOBILE_HIDE_STICKY;


    $orderby = isset($_G['cache']['forums'][$_G['fid']]['orderby']) ? $_G['cache']['forums'][$_G['fid']]['orderby'] : 'lastpost';
    $ascdesc = isset($_G['cache']['forums'][$_G['fid']]['ascdesc']) ? $_G['cache']['forums'][$_G['fid']]['ascdesc'] : 'DESC';


    if($showsticky) {
        $forumstickytids = array();
        if($_G['page'] !== 1 || $filterbool === false) {
            if($_G['setting']['globalstick'] && $_G['forum']['allowglobalstick']) {
                $stickytids = explode(',', str_replace("'", '', $_G['cache']['globalstick']['global']['tids']));
                if(!empty($_G['cache']['globalstick']['categories'][$thisgid]['count'])) {
                    $stickytids = array_merge($stickytids, explode(',', str_replace("'", '', $_G['cache']['globalstick']['categories'][$thisgid]['tids'])));
                }

                if($_G['forum']['status'] != 3) {
                    $stickycount = $_G['cache']['globalstick']['global']['count'];
                    if(!empty($_G['cache']['globalstick']['categories'][$thisgid])) {
                        $stickycount += $_G['cache']['globalstick']['categories'][$thisgid]['count'];
                    }
                }
            }

            if($_G['forum']['allowglobalstick']) {
                $forumstickycount = 0;
                $forumstickfid = $_G['forum']['status'] != 3 ? $_G['fid'] : $_G['forum']['fup'];
                if(isset($_G['cache']['forumstick'][$forumstickfid])) {
                    $forumstickycount = count($_G['cache']['forumstick'][$forumstickfid]);
                    $forumstickytids = $_G['cache']['forumstick'][$forumstickfid];
                }
                if(!empty($forumstickytids)) {
                    $stickytids = array_merge($stickytids, $forumstickytids);
                }
                $stickycount += $forumstickycount;
            }
        }
    }

    $threadids = array();

    $filterarr['sticky'] = 4;
    $filterarr['displayorder'] = !$filterbool && $stickycount ? array(0, 1) : array(0, 1, 2, 3, 4);
    if($filter !== 'hot') {
        $threadlist = array();
        $indexadd = '';
        $_order = "displayorder DESC, $orderby $ascdesc";
        fwrite($fp, "stickytids\n".var_export($stickytids,true)."\n");
        if($filterbool) {
            if($filterarr['digest']) {
                $indexadd = " FORCE INDEX (digest) ";
            }
        } elseif($showsticky && is_array($stickytids) && $stickytids[0]) {
            $filterarr1 = $filterarr;
            $filterarr1['inforum'] = '';
            $filterarr1['intids'] = $stickytids;
            $filterarr1['displayorder'] = array(2, 3, 4);
            $threadlist = S::t('forum_thread')->fetch_all_search($filterarr1, 0, $start_limit, $_G['tpp'], $_order, '');
            unset($filterarr1);
        }
        $threadlist = array_merge($threadlist, S::t('forum_thread')->fetch_all_search($filterarr, 0, $start_limit, $_G['tpp'], $_order, '', $indexadd));
        unset($_order);
    } else {
        $hottime = intval(str_replace('-', '', $_GET['time']));
        $multipage = '';
        if($hottime && checkdate(substr($hottime, 4, 2), substr($hottime, 6, 2), substr($hottime, 0, 4))) {
            $calendartime = abs($hottime);
            $ctime = sprintf('%04d', substr($hottime, 0, 4)).'-'.sprintf('%02d', substr($hottime, 4, 2)).'-'.sprintf('%02d', substr($hottime, 6, 2));
        } else {
            $calendartime = dgmdate(strtotime(dgmdate(TIMESTAMP, 'Y-m-d')) - 86400, 'Ymd');
            $ctime = dgmdate(strtotime(dgmdate(TIMESTAMP, 'Y-m-d')) - 86400, 'Y-m-d');
        }
        $caldata = S::t('forum_threadcalendar')->fetch_by_fid_dateline($_G['fid'], $calendartime);
        $_G['forum_threadcount'] = 0;
        if($caldata) {
            $hottids = S::t('forum_threadhot')->fetch_all_tid_by_cid($caldata['cid']);
            $threadlist = S::t('forum_thread')->fetch_all_by_tid($hottids);
            $_G['forum_threadcount'] = count($threadlist);
        }

    }

    fwrite($fp, "threadlist\n".var_export($threadlist,true)."\n");

    return $threadlist;

}

function getpostslist($tid, $page = 1, $ordertype = '') {

    global $_G;

    $thread = & $_G['forum_thread'];
    $forum = & $_G['forum'];

    if(!$_G['forum_thread'] || !$_G['forum']) {
        return array(0 => array('error' => 'thread_nonexistence'));
    }

    $page = max(1, $page);

    $threadtableids = !empty($_G['cache']['threadtableids']) ? $_G['cache']['threadtableids'] : array();
    $threadtable_info = !empty($_G['cache']['threadtable_info']) ? $_G['cache']['threadtable_info'] : array();

    $archiveid = $thread['threadtableid'];
    $thread['is_archived'] = $archiveid ? true : false;
    $thread['archiveid'] = $archiveid;
    $forum['threadtableid'] = $archiveid;
    $threadtable = $thread['threadtable'];
    $posttableid = $thread['posttableid'];
    $posttable = $thread['posttable'];


    $_G['action']['fid'] = $_G['fid'];
    $_G['action']['tid'] = $_G['tid'];

    $fromuid = $_G['setting']['creditspolicy']['promotion_visit'] && $_G['uid'] ? '&amp;fromuid='.$_G['uid'] : '';
    $feeduid = $_G['forum_thread']['authorid'] ? $_G['forum_thread']['authorid'] : 0;
    $feedpostnum = $_G['forum_thread']['replies'] > $_G['ppp'] ? $_G['ppp'] : ($_G['forum_thread']['replies'] ? $_G['forum_thread']['replies'] : 1);

    $navigation = '';

	if($_G['forum']['type'] == 'sub') {
        $fup = $_G['cache']['forums'][$_G['forum']['fup']]['fup'];
        $navigation .= $_G['cache']['forums'][$fup]['name'];
    }

	if($_G['forum']['fup']) {
        $fup = $_G['forum']['fup'];
        $navigation .= $_G['cache']['forums'][$fup]['name'];
    }
	$navigation .= $_G['forum']['name'];

	unset($t_link, $t_name);

    if(empty($_G['forum']['allowview'])) {

        if(!$_G['forum']['viewperm']) {
            return array(0 => array('error' => 'group_nopermission'));
        } else {
            return array(0 => array('error' => 'viewperm'));
        }

    } elseif($_G['forum']['allowview'] == -1) {
        return array(0 => array('error' => 'forum_access_view_disallow'));
    }

    $threadtag = array();
    viewthread_updateviews($archiveid);

    $_G['setting']['infosidestatus']['posts'] = $_G['setting']['infosidestatus'][1] && isset($_G['setting']['infosidestatus']['f'.$_G['fid']]['posts']) ? $_G['setting']['infosidestatus']['f'.$_G['fid']]['posts'] : $_G['setting']['infosidestatus']['posts'];


    $postfieldsadd = $specialadd1 = $specialadd2 = $specialextra = '';
    $tpids = array();

    $onlyauthoradd = $threadplughtml = '';

    $maxposition = 0;
    if(empty($viewpid)) {
        $disablepos = !$rushreply && S::t('forum_threaddisablepos')->fetch($_G['tid']) ? 1 : 0;
        if(!$disablepos && !in_array($_G['forum_thread']['special'], array(2,3,5))) {
            if($_G['forum_thread']['maxposition']) {
                $maxposition = $_G['forum_thread']['maxposition'];
            } else {
                $maxposition = S::t('forum_post')->fetch_maxposition_by_tid($posttableid, $_G['tid']);
            }
        }

        $ordertype = empty($ordertype) && getstatus($_G['forum_thread']['status'], 4) ? 1 : $ordertype;
        if($_GET['from'] == 'album') {
            $ordertype = 1;
        }
        $sticklist = array();
        if($_G['page'] === 1 && $_G['forum_thread']['stickreply'] && empty($_GET['authorid'])) {
            $poststick = S::t('forum_poststick')->fetch_all_by_tid($_G['tid']);
            foreach (S::t('forum_post')->fetch_all($posttableid, array_keys($poststick)) as $post) {
                $post['position'] = $poststick[$post['pid']]['position'];
                $post['avatar'] = avatar($post['authorid'], 'small');
                $post['isstick'] = true;
                $sticklist[$post['pid']] = $post;
            }
            $stickcount = count($sticklist);
        }

        if($maxposition) {
            $_G['forum_thread']['replies'] = $maxposition - 1;
        }
        $_G['ppp'] = $_G['forum']['threadcaches'] && !$_G['uid'] ? $_G['setting']['postperpage'] : $_G['ppp'];
        $totalpage = ceil(($_G['forum_thread']['replies'] + 1) / $_G['ppp']);
        $page > $totalpage && $page = $totalpage;
        $_G['forum_pagebydesc'] = !$maxposition && $page > 2 && $page > ($totalpage / 2) ? TRUE : FALSE;

        if($_G['forum_pagebydesc']) {
            $firstpagesize = ($_G['forum_thread']['replies'] + 1) % $_G['ppp'];
            $_G['forum_ppp3'] = $_G['forum_ppp2'] = $page == $totalpage && $firstpagesize ? $firstpagesize : $_G['ppp'];
            $realpage = $totalpage - $page + 1;
            if($firstpagesize == 0) {
                $firstpagesize = $_G['ppp'];
            }
            $start_limit = max(0, ($realpage - 2) * $_G['ppp'] + $firstpagesize);
            $_G['forum_numpost'] = ($page - 1) * $_G['ppp'];
            if($ordertype != 1) {
            } else {
                $_G['forum_numpost'] = $_G['forum_thread']['replies'] + 2 - $_G['forum_numpost'] + ($page > 1 ? 1 : 0);
            }
        } else {
            $start_limit = $_G['forum_numpost'] = max(0, ($page - 1) * $_G['ppp']);
            if($start_limit > $_G['forum_thread']['replies']) {
                $start_limit = $_G['forum_numpost'] = 0;
                $page = 1;
            }
            if($ordertype != 1) {
            } else {
                $_G['forum_numpost'] = $_G['forum_thread']['replies'] + 2 - $_G['forum_numpost'] + ($page > 1 ? 1 : 0);
            }
        }
    }

    $_G['forum_newpostanchor'] = $_G['forum_postcount'] = 0;

    $_G['forum_onlineauthors'] = $_G['forum_cachepid'] = $_G['blockedpids'] = array();

    $isdel_post = $cachepids = $postusers = $skipaids = array();

    if($_G['forum_auditstatuson'] || in_array($_G['forum_thread']['displayorder'], array(-2, -3, -4)) && $_G['forum_thread']['authorid'] == $_G['uid']) {
        $visibleallflag = 1;
    }

    if($maxposition) {
        $start = ($page - 1) * $_G['ppp'] + 1;
        $end = $start + $_G['ppp'];
        if($ordertype == 1) {
            $end = $maxposition - ($page - 1) * $_G['ppp'] + ($page > 1 ? 2 : 1);
            $start = $end - $_G['ppp'] + ($page > 1 ? 0 : 1);
            $start = max(array(1,$start));
        }
        $have_badpost = $realpost = $lastposition = 0;
        foreach(S::t('forum_post')->fetch_all_by_tid_range_position($posttableid, $_G['tid'], $start, $end, $maxposition, $ordertype) as $post) {
            if($post['invisible'] != 0) {
                $have_badpost = 1;
            }
            $cachepids[$post[position]] = $post['pid'];
            $postarr[$post[position]] = $post;
            $lastposition = $post['position'];
        }
        $realpost = count($postarr);
        if($realpost != $_G['ppp'] || $have_badpost) {
            $k = 0;
            for($i = $start; $i < $end; $i ++) {
                if(!empty($cachepids[$i])) {
                    $k = $cachepids[$i];
                    $isdel_post[$i] = array('deleted' => 1, 'pid' => $k, 'message' => '', 'position' => $i);
                } elseif($i < $maxposition || ($lastposition && $i < $lastposition)) {
                    $isdel_post[$i] = array('deleted' => 1, 'pid' => $k, 'message' => '', 'position' => $i);
                }
                $k ++;
            }
        }
        $pagebydesc = false;
    }

    if(!empty($isdel_post)) {
        $updatedisablepos = false;
        foreach($isdel_post as $id => $post) {
            if(isset($postarr[$id]['invisible']) && ($postarr[$id]['invisible'] == 0 || $postarr[$id]['invisible'] == -3 || $visibleallflag)) {
                continue;
            }
            $postarr[$id] = $post;
            $updatedisablepos = true;
        }
        if($updatedisablepos && !$rushreply) {
            S::t('forum_threaddisablepos')->insert(array('tid' => $_G['tid']), false, true);
        }
        $ordertype != 1 ? ksort($postarr) : krsort($postarr);
    }
    $summary = '';
    if($page == 1 && $ordertype == 1) {
        $firstpost = S::t('forum_post')->fetch_threadpost_by_tid_invisible($_G['tid']);
        if($firstpost['invisible'] == 0 || $visibleallflag == 1) {
            $postarr = array_merge(array($firstpost), $postarr);
            unset($firstpost);
        }
    }
    $tagnames = $locationpids = $hotpostarr = $hotpids = $member_blackList = array();

    foreach($postarr as $post) {
        if(($onlyauthoradd && empty($post['anonymous']) || !$onlyauthoradd) && !isset($postlist[$post['pid']])) {

            if(isset($hotpostarr[$post['pid']])) {
                $post['existinfirstpage'] = true;
            }

            $postusers[$post['authorid']] = array();
            if($post['first']) {
                if($ordertype == 1 && $page != 1) {
                    continue;
                }
                $_G['forum_firstpid'] = $post['pid'];
                //if(!$_G['forum_thread']['price'] && (IS_ROBOT || $_G['adminid'] == 1)) $summary = str_replace(array("\r", "\n"), '', messagecutstr(strip_tags($post['message']), 160));
                $tagarray_all = $posttag_array = array();
                $tagarray_all = explode("\t", $post['tags']);
                if($tagarray_all) {
                    foreach($tagarray_all as $var) {
                        if($var) {
                            $tag = explode(',', $var);
                            $posttag_array[] = $tag;
                            $tagnames[] = $tag[1];
                        }
                    }
                }
                $post['tags'] = $posttag_array;
            }
            require_once SERVER_ROOT.'/function/function_discuzcode.php';
            loadcache('smilies');
            $post['message'] = discuzcode($post['message'], $post['smileyoff'], $post['bbcodeoff'], $post['htmlon'] & 1, $_G['forum']['allowsmilies'], $_G['forum']['allowbbcode'], ($_G['forum']['allowimgcode'] && $_G['setting']['showimages'] ? 1 : 0), $_G['forum']['allowhtml'], ($_G['forum']['jammer'] && $post['authorid'] != $_G['uid'] ? 1 : 0), 0, $post['authorid'], $_G['cache']['usergroups'][$post['groupid']]['allowmediacode'] && $_G['forum']['allowmediacode'], $post['pid'], $_G['setting']['lazyload'], $post['dbdateline'], $post['first']);
            $postlist[$post['pid']] = $post;
        }
    }
    unset($hotpostarr);


    if(empty($postlist)) {
        return array(0 => array('error' => 'post_not_found'));
    } elseif(!defined('IN_MOBILE_API')) {
        foreach($postlist as $pid => $post) {
            $postlist[$pid]['message'] = preg_replace("/\[attach\]\d+\[\/attach\]/i", '', $postlist[$pid]['message']);
        }
    }

    $_G['forum_thread']['heatlevel'] = $_G['forum_thread']['recommendlevel'] = 0;
    if($_G['setting']['heatthread']['iconlevels']) {
        foreach($_G['setting']['heatthread']['iconlevels'] as $k => $i) {
            if($_G['forum_thread']['heats'] > $i) {
                $_G['forum_thread']['heatlevel'] = $k + 1;
                break;
            }
        }
    }

    $allowblockrecommend = $_G['group']['allowdiy'] || getstatus($_G['member']['allowadmincp'], 4) || getstatus($_G['member']['allowadmincp'], 5) || getstatus($_G['member']['allowadmincp'], 6);
    if($_G['setting']['portalstatus']) {
        $allowpostarticle = $_G['group']['allowmanagearticle'] || $_G['group']['allowpostarticle'] || getstatus($_G['member']['allowadmincp'], 2) || getstatus($_G['member']['allowadmincp'], 3);
        $allowpusharticle = empty($_G['forum_thread']['special']) && empty($_G['forum_thread']['sortid']) && !$_G['forum_thread']['pushedaid'];
    } else {
        $allowpostarticle = $allowpusharticle = false;
    }
    if($_G['forum_thread']['displayorder'] != -4) {
        $modmenu = array(
            'thread' => $_G['forum']['ismoderator'] || $allowblockrecommend || $allowpusharticle && $allowpostarticle,
            'post' => $_G['forum']['ismoderator'] && ($_G['group']['allowwarnpost'] || $_G['group']['allowbanpost'] || $_G['group']['allowdelpost'] || $_G['group']['allowstickreply']) || $_G['forum_thread']['pushedaid'] && $allowpostarticle || $_G['forum_thread']['authorid'] == $_G['uid']
        );
    } else {
        $modmenu = array();
    }

    return $postlist;

}

function viewthread_updateviews($tableid) {
    global $_G;

    if(!$_G['setting']['preventrefresh'] || $_G['cookie']['viewid'] != 'tid_'.$_G['tid']) {
        if(!$tableid && $_G['setting']['optimizeviews']) {
            if($_G['forum_thread']['addviews']) {
                if($_G['forum_thread']['addviews'] < 100) {
                    S::t('forum_threadaddviews')->update_by_tid($_G['tid']);
                } else {
//                    if(!discuz_process::islocked('update_thread_view')) {
//                        $row = S::t('forum_threadaddviews')->fetch($_G['tid']);
//                        S::t('forum_threadaddviews')->update($_G['tid'], array('addviews' => 0));
//                        S::t('forum_thread')->increase($_G['tid'], array('views' => $row['addviews']+1), true);
//                        discuz_process::unlock('update_thread_view');
//                    }
                }
            } else {
                S::t('forum_threadaddviews')->insert(array('tid' => $_G['tid'], 'addviews' => 1), false, true);
            }
        } else {
            S::t('forum_thread')->increase($_G['tid'], array('views' => 1), true, $tableid);
        }
    }
}