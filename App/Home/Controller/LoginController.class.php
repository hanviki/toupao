<?php

/**
 * 版    本：1.0.0
 * 功能说明：后台登录控制器。
 *
 * */

namespace Home\Controller;

use Home\Controller\ComController;

class LoginController extends ComController {

    public function index() {

        $flag = $this->check_login();
        if ($flag) {
            $this->error('您已经登录,正在跳转到主页', U("index/index"));
        }
        $this->display();
    }

    public function login() {
        $username = isset($_POST['user_name']) ? trim($_POST['user_name']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        $remember = isset($_POST['remember']) ? $_POST['remember'] : 0;
        if ($username == '') {
            $this->error('用户名不能为空！', U("login/index"));
        } elseif ($password == '') {
            $this->error('密码必须！', U("login/index"));
        }
        $model = M("judges");
        $user = $model->field('user_id,user_name,user_type,user_status')->where(array('user_name' => $username, 'password' => $password))->find();

        if ($user['user_status'] == 1) {
            if ($user) {
                $salt = C("COOKIE_SALT");
                $ip = get_client_ip();
                $ua = $_SERVER['HTTP_USER_AGENT'];
                session_start();
                session('user_id', $user['user_id']);
                //加密cookie信息
                $auth = password($user['user_id'] . $user['user_name'] . $ip . $ua . $salt);
                if ($remember) {
                    cookie('auth', $auth, 3600 * 24 * 365); //记住我
                } else {
                    cookie('auth', $auth);
                }
                if ($user['user_type'] == '1') {
                    $url = U('Index/index');
                } else {
                    $url = U('Tpiao/index');
                }
                header("Location: $url");
                exit(0);
            } else {
                $this->error('登录失败，请重试！', U("login/index"));
            }
        } else {
            $this->error('未激活，暂无权限投票！', U("login/index"));
            die();
        }
    }

}
