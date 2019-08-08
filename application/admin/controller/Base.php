<?php
/**
 * Created by PhpStorm.
 * User: 刘彪
 * Date: 2019/7/22
 * Time: 14:29
 */

namespace app\admin\controller;



use app\http\middleware\LoginCheck;
use think\Controller;

class Base  extends Controller
{
    protected $middleware = [LoginCheck::class];
}