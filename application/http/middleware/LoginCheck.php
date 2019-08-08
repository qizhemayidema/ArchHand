<?php

namespace app\http\middleware;

use think\facade\Session;
use think\Request;

class LoginCheck
{
    public function handle($request, \Closure $next)
    {
        if (!Session::get('admin')){
            return redirect('/admin/login');
        }
        return $next($request);
    }
}
