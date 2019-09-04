<?php

namespace app\http\middleware;

use think\exception\HttpException;
use think\facade\Response;
use think\facade\Session;

class IndexCheckLoginStatus
{
    public function handle($request, \Closure $next)
    {
        if (!Session::has(config('app.index_user_session_path'))){
            throw new HttpException(404);
        }
        return $next($request);
    }
}
