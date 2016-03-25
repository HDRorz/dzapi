# -*- coding: utf-8 -*-

from IMApiClient import IMApiClient
from flask import Flask, make_response, request, session

app = Flask(__name__)


@app.route('/')
def hello_world():
    return 'Hello World!'


@app.route('/user_exists', methods=['GET'])
def user_exists():
    client = IMApiClient(request)
    result = client.user_exists()
    response = make_response(
            result[1],
            result[0],
            {
                'Content-type': 'text/html',
                'Content-Length': result[1].__len__()
            })
    return response


@app.route('/user_avatar', methods=['GET'])
def user_avatar():
    client = IMApiClient(request)
    result = client.user_avatar()
    response = make_response(
            result[1],
            result[0],
            {
                'Content-type': 'text/html',
                'Content-Length': result[1].__len__()
            })
    return response


@app.route('/user_login', methods=['POST'])
def user_login():
    client = IMApiClient(request)
    result = client.user_login()
    response = make_response(
            result[1],
            result[0],
            {
                'Content-type': 'text/html',
                'Content-Length': result[1].__len__()
            })
    return response


@app.route('/set_password', methods=['POST'])
def set_password():
    client = IMApiClient(request)
    result = client.set_password()
    response = make_response(
            result[1],
            result[0],
            {
                'Content-type': 'text/html',
                'Content-Length': result[1].__len__()
            })
    return response


@app.route('/remove_user', methods=['POST'])
def remove_user():
    client = IMApiClient(request)
    result = client.remove_user()
    response = make_response(
            result[1],
            result[0],
            {
                'Content-type': 'text/html',
                'Content-Length': result[1].__len__()
            })
    return response


@app.route('/remove_user_validate', methods=['POST'])
def remove_user_validate():
    client = IMApiClient(request)
    result = client.remove_user_validate()
    response = make_response(
            result[1],
            result[0],
            {
                'Content-type': 'text/html',
                'Content-Length': result[1].__len__()
            })
    return response


@app.route('/get_user', methods=['GET'])
def get_user():
    client = IMApiClient(request)
    result = client.get_user()
    response = make_response(
            result[1],
            result[0],
            {
                'Content-type': 'text/html',
                'Content-Length': result[1].__len__()
            })
    return response


@app.route('/get_userfield', methods=['GET'])
def get_userfield():
    client = IMApiClient(request)
    result = client.get_userfield()
    response = make_response(
            result[1],
            result[0],
            {
                'Content-type': 'text/html',
                'Content-Length': result[1].__len__()
            })
    return response


@app.route('/user_register', methods=['POST'])
def user_register():
    client = IMApiClient(request)
    result = client.user_register()
    response = make_response(
            result[1],
            result[0],
            {
                'Content-type': 'text/html',
                'Content-Length': result[1].__len__()
            })
    return response


@app.route('/get_threadslist', methods=['GET'])
def get_threadslist():
    client = IMApiClient(request)
    result = client.get_threadslist()
    response = make_response(
            result[1],
            result[0],
            {
                'Content-type': 'text/html',
                'Content-Length': result[1].__len__()
            })
    return response


@app.route('/get_postslist', methods=['GET'])
def get_postslist():
    client = IMApiClient(request)
    result = client.get_postslist()
    response = make_response(
            result[1],
            result[0],
            {
                'Content-type': 'text/html',
                'Content-Length': result[1].__len__()
            })
    return response


if __name__ == '__main__':
    # app.run(debug=True)
    app.run()
