namespace php IMApiServer

const string Nochar = ''

service DzApi{
	string hello(),
	bool user_exists(1:string user),
	string user_avatar(1:string uid, 2:string size = 'middle'),
	map<string, string> get_user(1:string uid)
	map<string, string> get_userfield(1:string uid)
	i32 user_login(1:string ip, 2:string user, 3:string password, 4:i32 qid = 0, 5:string ans = Nochar, 6:string loginfield = 'username'),
	string user_register(1:string ip, 2:string username, 3:string password, 4:string password2, 5:string email, 6:string questionid = Nochar, 7:string answer = Nochar),
	list<map<string, string>> get_threadslist(1:string fid, 2:string page = '1', 3:string filter = Nochar),
	list<map<string, string>> get_postslist(1:string tid, 2:string page = '1', 3:string ordertype = Nochar),
}
