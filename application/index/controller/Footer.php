<?php

namespace app\index\controller;

use think\Controller;
use think\Request;

class Footer extends Base
{
    public function index(Request $request)
    {
        $page = $request->get('page')?? 'about';

        $footer = $this->getConfig('bottom_navigation');
        if (! isset($footer[$page]) ){
            $page = 'about';
        }
        $this->assign('footer',$footer);
        $this->assign('page',$page);

        return $this->fetch();
    }
}
