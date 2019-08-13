<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Classes extends Base
{
    public function index()
    {
        return $this->fetch();
    }

    public function add()
    {

    }
}
