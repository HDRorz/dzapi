# -*- coding: utf-8 -*-


class BaseResponse:
    def __init__(self):
        self.reTrue = [200, 'true']
        self.reFalse = [200, 'false']
        self.reSuccess = [201, 'success']
        self.reNotAllow = [403, 'not allowed for some reason']
        self.reInvalidPass = [403, 'invalid user password or not allowed for other reason']
        self.reNotExist = [404, 'user does not exist']
        self.reExistUser = [409, 'user already exists']
        self.reError = [500, 'unknow error']
