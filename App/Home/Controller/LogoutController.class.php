<?php

namespace Home\Controller;

class LogoutController extends ComController
{
    public function index()
    {
        cookie('auth', null);
        session('user_id',null);
        $url = U("login/index");
        header("Location: {$url}");
        exit(0);
    }
}