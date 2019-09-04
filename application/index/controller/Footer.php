<?php

namespace app\index\controller;

use think\Controller;
use think\Request;

class Footer extends Base
{
    public function index(Request $request)
    {
        /**
         * "about": "<p><im
            "FAQ": "<p>\u53d
            "privacy_policy"
            "job": "<p><img
            "contact_us": "<
         */
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
