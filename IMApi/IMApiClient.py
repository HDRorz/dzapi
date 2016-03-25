# -*- coding: utf-8 -*-

import sys

sys.path.append('../gen-py')
from IMApiServer import DzApi
from IMApiServer.ttypes import *

from thrift import Thrift
from thrift.transport import TSocket
from thrift.transport.THttpClient import THttpClient
from thrift.transport import TTransport
from thrift.protocol import TBinaryProtocol

from BaseResponse import BaseResponse



class IMApiClient(BaseResponse):
    def __init__(self, request):
        BaseResponse.__init__(self)
        self.params = request.values
        self.username = self.params.get('user')
        self.domain = self.params.get('server')
        self.password = self.params.get('pass')
        if isinstance(request.remote_addr, str):
            self.ip = request.remote_addr
        else:
            self.ip = '127.0.0.1'
        # self.transport = TSocket.TSocket('localhost', 8080)
        # self.transport = THttpClient('http://localhost:8080/apiserver.php')
        self.transport = THttpClient('http://localhost/dzapi/apiserver.php')
        self.transport = TTransport.TBufferedTransport(self.transport)
        self.protocol = TBinaryProtocol.TBinaryProtocol(self.transport)
        self.client = DzApi.Client(self.protocol)
        self.transport.open()

    def user_exists(self):
        if self.client.user_exists(self.username):
            return self.reTrue
        else:
            return self.reNotExist

    def user_avatar(self):
        avatar = self.client.user_avatar(self.params.get('uid'), self.params.get('size'))
        return avatar

    def user_login(self):
        if self.client.user_exists(self.username):
            uid = self.client.user_login(self.ip, self.username, self.password, self.params.get('questionid'), self.params.get('answer'), self.params.get('fastloginfield'),)
            if uid > 0:
                # self.uid = uid
                return self.reSuccess
            else:
                return self.reInvalidPass
        else:
            return self.reError

    def set_password(self):
        if any(self.password):
            if True:
                return self.reSuccess
            else:
                return self.reError

    def remove_user(self):
        if self.user_exists()[1] == 'true':
            if True:
                return self.reSuccess
            else:
                return self.reNotAllow
        else:
            return self.reNotExist

    def remove_user_validate(self):
        if self.user_exists()[1] == 'true':
            if self.user_login()[1] == 'success' and True:
                return self.reSuccess
            else:
                return self.reInvalidPass
        else:
            return self.reNotExist

    def get_user(self):
        if self.user_exists()[1] == 'true':
            return [200, str(self.client.get_user(self.params.get('uid')))]
        else:
            return self.reError

    def get_userfield(self):
        if self.user_exists()[1] == 'true':
            return [200, str(self.client.get_userfield(self.params.get('uid')))]
        else:
            return self.reError

    def user_register(self):
        if self.user_exists()[1] != 'true':
            result = self.client.user_register(self.ip, self.username, self.params.get('password'), self.params.get('password2'), self.params.get('email'), self.params.get('questionid'), self.params.get('answer'))
            return [200, str(result)]
        else:
            return self.reExistUser

    def get_threadslist(self):
        threadslist = self.client.get_threadslist(self.params.get('fid'), self.params.get('page'), self.params.get('filter'))
        for thread in threadslist:
            thread['subject'] = thread['subject'].decode('utf-8')
        return [200, str(threadslist)]

    def get_postslist(self):
        postslist = self.client.get_postslist(self.params.get('tid'), self.params.get('page'), self.params.get('ordertype'))
        for post in postslist:
            post['message'] = post['message'].decode('utf-8')
        return [200, str(postslist)]


if __name__ == '__main__':
    api = IMApiClient(
            {
                'values':
                    {
                        'user': 'HDRorz',
                        'server': 'domain',
                        'pass': 'ab250985201',
                    },
                'remote_addr': '127.0.0.1',
            }
    )
    print api.user_login()
