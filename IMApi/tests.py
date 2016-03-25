# -*- coding: utf-8 -*-

import os
import IMApi
import unittest
import tempfile


class FlaskrTestCase(unittest.TestCase):
    def setUp(self):
        IMApi.app.config['TESTING'] = True
        self.app = IMApi.app.test_client()

    def tearDown(self):
        pass

    def test_hello(self):
        rv = self.app.get('/')
        assert 'Hello' in rv.data

    def test_user_exists(self):
        rv = self.app.get('/user_exists',
                          data={
                              'user': 'iksama1',
                              'server': 'domain'
                          }
                          )
        assert 'true' in rv.data

    def test_user_avatar(self):
        rv = self.app.get('/user_avatar',
                          data={
                              'uid': '2074729',
                              'server': 'domain'
                          }
                          )
        assert 'true' in 'true'

    def test_user_login(self):
        rv = self.app.post('/user_login',
                          data={
                              'user': 'iksama1',
                              'server': 'domain',
                              'pass': '123qwe'
                          }
                          )
        assert 'success' in rv.data

    def test_set_password(self):
        rv = self.app.post('/set_password',
                           data={
                               'user': '123',
                               'server': 'domain',
                               'pass': '123'
                           }
                           )
        assert 'success' in rv.data

    def test_remove_user(self):
        rv = self.app.post('/remove_user',
                           data={
                               'user': '123',
                               'server': 'domain'
                           }
                           )
        assert 'success' in rv.data

    def test_remove_user_validate(self):
        rv = self.app.post('/remove_user_validate',
                           data={
                               'user': 'iksama1',
                               'server': 'domain',
                               'pass': '123qwe'
                           }
                           )
        assert 'success' in rv.data

    def test_get_user(self):
        rv = self.app.get('/get_user',
                           data={
                               'user': 'iksama1',
                               'uid': '2074745',
                               'server': 'domain'
                           }
                           )
        assert 'HDRorz' in rv.data

    def test_get_userfield(self):
        rv = self.app.get('/get_userfield',
                           data={
                               'user': 'iksama1',
                               'uid': '2074745',
                               'server': 'domain'
                           }
                           )
        assert '2074729' in rv.data

    def test_get_threadslist(self):
        rv = self.app.get('/get_threadslist',
                           data={
                               'fid': '161',
                               'page': '1',
                               'server': 'domain'
                           }
                           )
        assert '161' in rv.data

    def test_get_postslist(self):
        rv = self.app.get('/get_postslist',
                           data={
                               'tid': '2239526',
                               'page': '1',
                               'server': 'domain'
                           }
                           )
        assert '161' in rv.data

    def test_user_register(self):
        rv = self.app.post('/user_register',
                           data={
                               'user': 'iksama2',
                               'password': '123qwe',
                               'password2': '123qwe',
                               'email': 'n21a@nadl1k.com'
                           }
                           )
        assert 'succeed' in rv.data

    def test_user_zlogin2(self):
        rv = self.app.post('/user_login',
                           data={
                               'user': 'iksama2',
                               'pass': '123qwe'
                           }
                           )
        assert 'success' in rv.data

if __name__ == '__main__':
    unittest.main()
