<?php

namespace app\admin\controller;

use think\Controller;

class Index extends Base
{
    public function index(){
$data  = getCategoryTree();
//return json($data);
        dump($data);die;

        return $this->fetch();
    }
}
