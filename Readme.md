discuz apiserver 

thrift connect python front server with php back server

python front server: flask 0.10.1
thrift: 0.9.3
discuz: x3.2



检查用户是否存在
bool user_exists(username)
method = GET
username = 用户名
return {
    true: 存在
    false: 不存在
}


用户头像
string user_avatar(uid, size='middle')
method = GET
uid = 用户UID
size = 'big', 'middle', 'small'
return url


用户登录
i32 user_login(username, password, questionid='', answer='', fastloginfield='username')
method = POST
username = 用户名
password = 密码
questionid = 安全验证问题编号 ''
answer = 安全验证问题答案 ''
fastloginfield = 登录方式 'username', 'uid', 'email', 'auto'
return {
    uid: 登陆成功返回uid
    0: 登录失败
    -1: 用户不存在，或者被删除
    -2: 密码错
    -3: 安全提问错
}


获取用户基本信息
map<string, string> get_user(uid)
method = GET
uid = 用户uid
return array (
    'uid' => '2074729',
    'email' => 'ab250985201@163.com',
    'username' => 'HDRorz',
    'password' => 'cdb61336ac8aace9b8551eecd176ec01',
    'status' => '0',
    'emailstatus' => '0',
    'avatarstatus' => '1',
    'videophotostatus' => '0',
    'adminid' => '0',
    'groupid' => '15',
    'groupexpiry' => '0',
    'extgroupids' => '',
    'regdate' => '1450079776',
    'credits' => '6666',
    'notifysound' => '0',
    'timeoffset' => '9999',
    'newpm' => '0',
    'newprompt' => '2',
    'accessmasks' => '0',
    'allowadmincp' => '0',
    'onlyacceptfriendpm' => '0',
    'conisbind' => '0',
    'freeze' => '0',
)
/*
 * uid	会员id
 * email	邮箱
 * username	用户名
 * password	密码
 * status	判断用户是否已经删除	需要discuz程序加判断，并增加整体清理的功能。原home字段为flag
 * emailstatus	email是否经过验证	home字段为emailcheck
 * avatarstatus	是否有头像	home字段为avatar
 * videophotostatus	视频认证状态	home
 * adminid	管理员id
 * groupid	会员组id
 * groupexpiry	用户组有效期
 * extgroupids
 * regdate	注册时间
 * credits	总积分
 * notifysound	短信声音
 * timeoffset	时区校正
 * newpm	新短消息数量
 * newprompt	新提醒数目
 * accessmasks	标志
 * allowadmincp	标志
 * onlyacceptfriendpm	是否只接收好友短消息
 * conisbind	用户是否绑定QC
 * freeze   是否冻结
 */


获取用户积分信息
map<string, string> get_userfield(uid)
method = GET
uid = 用户uid
return array (
    'uid' => '2074729',
    'extcredits1' => '2056',
    'extcredits2' => '15',
    'extcredits3' => '0',
    'extcredits4' => '200',
    'extcredits5' => '0',
    'extcredits6' => '0',
    'extcredits7' => '0',
    'extcredits8' => '0',
    'friends' => '1',
    'posts' => '12',
    'threads' => '0',
    'digestposts' => '0',
    'oltime' => '15',
    'regip' => '127.0.0.1',
    'lastip' => '127.0.0.1',
    'lastvisit' => '1453803037',
    'lastactivity' => '1453802783',
    'lastpost' => '1452838048',
    'invisible' => '0',
)
/*
 * uid	会员id
 * extcredits1	声望
 * extcredits2	金钱
 * extcredits3	扩展
 * extcredits4	扩展
 * extcredits5	扩展
 * extcredits6	扩展
 * extcredits7	扩展
 * extcredits8	扩展
 * friends	好友个数
 * posts	帖子数
 * threads	主题数
 * digestposts	精华数
 * oltime	在线时间
 * regip	注册IP
 * lastip	最后登陆IP
 * lastvisit	最后访问
 * invisible	是否隐身登录
 */


用户注册
string user_register(username, password, password2, email, questionid='', answer='')
method = POST
username = 用户名
password = 密码
password2 = 重复密码
questionid = 安全验证问题编号 ''
answer = 安全验证问题答案 ''
return


获取帖子列表（获取第一页时包含置顶）
list<map<string, string>> get_threadslist(fid, page='1', filter='')
method = GET
fid = 论坛编号
page = 页号
filter = 筛选
return array (
  0 =>
  array (
    'tid' => '2229469',
    'fid' => '161',
    'posttableid' => '0',
    'typeid' => '63',
    'sortid' => '0',
    'readperm' => '0',
    'price' => '0',
    'author' => 'neotv.肉肉',
    'authorid' => '605672',
    'subject' => '电竞圈APP 限量VVIP用户招募（本条1块）',
    'dateline' => '1441192264',
    'lastpost' => '1445556753',
    'lastposter' => 'keyb',
    'views' => '2387',
    'replies' => '16',
    'displayorder' => '3',
    'highlight' => '0',
    'digest' => '0',
    'rate' => '0',
    'special' => '0',
    'attachment' => '1',
    'moderated' => '1',
    'closed' => '0',
    'stickreply' => '0',
    'recommends' => '0',
    'recommend_add' => '0',
    'recommend_sub' => '0',
    'heats' => '50',
    'status' => '32',
    'isgroup' => '0',
    'favtimes' => '0',
    'sharetimes' => '0',
    'stamp' => '4',
    'icon' => '-1',
    'pushedaid' => '0',
    'cover' => '0',
    'replycredit' => '0',
    'relatebytag' => '1451443859	2235721,2235050,2235034,2229471,2229470,2224040,2222832,2220226,2209514,2199229',
    'maxposition' => '59',
    'bgcolor' => '',
    'comments' => '0',
    'hidden' => '0',
  ),
  1 =>
  ...
)


获取回复列表（转义了内置bbcode和表情 ordertype=2正序 ordertype=1倒序）
list<map<string, string>> get_postslist(tid, page='1', ordertype='')
method = GET
tid = 帖子编号
page = 页号
ordertype = 排序方式 默认''正序｛
    2: 正序
    1: 倒序
｝
return array (size=4)
  10814474 =>
    array (size=26)
      'pid' => string '10814474' (length=8)
      'fid' => string '161' (length=3)
      'tid' => string '2239526' (length=7)
      'first' => string '1' (length=1)
      'author' => string 'neotv.kai' (length=9)
      'authorid' => string '410743' (length=6)
      'subject' => string 'afafa' (length=5)
      'dateline' => string '1453354197' (length=10)
      'message' => string 'Q 艰难地取得了胜利，你有什么感想A 再次感言要战胜神族是多么艰难。直到最后一刻我也没觉得自己能赢。我太紧张了，今天能赢真是万幸Q 最近大家都在批斗使徒A 其实我以为补丁会很快就出来呢（笑）。我在看别人的比赛，还想着轮到我上的时候补丁差不多就出来了吧，然而并没有，于是我现在已经是半放弃的状态了。Q 似乎你已经准备了对付使徒的办法A 前两局我都有所准备，第3�'... (length=2594)
      'useip' => string '127.0.0.1' (length=9)
      'port' => string '49484' (length=5)
      'invisible' => string '0' (length=1)
      'anonymous' => string '0' (length=1)
      'usesig' => string '0' (length=1)
      'htmlon' => string '0' (length=1)
      'bbcodeoff' => string '-1' (length=2)
      'smileyoff' => string '-1' (length=2)
      'parseurloff' => string '0' (length=1)
      'attachment' => string '0' (length=1)
      'rate' => string '0' (length=1)
      'ratetimes' => string '0' (length=1)
      'status' => string '0' (length=1)
      'tags' =>
        array (size=0)
          empty
      'comment' => string '0' (length=1)
      'replycredit' => string '0' (length=1)
      'position' => string '1' (length=1)
  10814475 =>
  ...
