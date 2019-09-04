<?php

namespace app\http\middleware;

use think\Cache;
use think\exception\HttpException;
use think\Request;

class IndexCheckIp
{
    public function handle(Request $request, \Closure $next)
    {
        $ip=FALSE;

        //客户端IP 或 NONE

        if(!empty($_SERVER["HTTP_CLIENT_IP"])){

            $ip = $_SERVER["HTTP_CLIENT_IP"];

        }

        //多重代理服务器下的客户端真实IP地址（可能伪造）,如果没有使用代理，此字段为空

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

            $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);

            if ($ip) { array_unshift($ips, $ip); $ip = FALSE; }

            for ($i = 0; $i < count($ips); $i++) {

                if (! preg_match   ("^(10│172.16│192.168).", $ips[$i])) {

                    $ip = $ips[$i];

                    break;

                }

            }

        }

        //客户端IP 或 (最后一个)代理服务器 IP

        $real_ip = $ip ? $ip : $_SERVER['REMOTE_ADDR'];

        $cache = new Cache(['type'=>config('cache.type'),'expire'=>30]);

        $key_name = $real_ip . $request->path();

        if ($cache->has($key_name)){
            if ($cache->get($key_name) >= 50){
                if ($request->isAjax()){
                    return json(['code'=>0,'msg'=>'请求次数频繁,请稍后再试']);
                }else{
                    throw new HttpException(403);
                }
            }
            $cache->inc($key_name);
        }else{
            $cache->set($key_name,1);
        }

        return $next($request);
    }
}
