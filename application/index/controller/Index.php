<?php

namespace app\index\controller;

use think\Controller;
use twelvet\utils\HTTP;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    public function http()
    {
        $demo = HTTP::get("https://www.12tla.com");
        return $demo;
    }
}
