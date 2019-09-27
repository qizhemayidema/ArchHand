<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use app\index\model\Help as HelpModel;

class Help extends Base
{
    public function index()
    {
        $help = (new HelpModel())->select();
        $this->assign('help',$help);
        $this->assign('banner',$this->getConfig('image.6.url'));
        return $this->fetch();
    }
}
