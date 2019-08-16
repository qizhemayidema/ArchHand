<?php

namespace app\admin\controller;

use think\Controller;
use think\Env;
use think\facade\Cache;
use think\Request;

class Config extends Base
{
    const WEB_SITE_PATH = CONFIG_PATH . 'web_site.json';

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {

        $data = json_decode(file_get_contents(self::WEB_SITE_PATH));

        $this->assign('site', $data);

        return $this->fetch('config/index');
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function add()
    {
        //
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $form = $request->post();




        $data = [
            'title' => trim($form['title']),
            'keyword' => trim($form['keyword']),
            'description' => trim($form['description']),
            'icp' => trim($form['icp']),
            'announcement' => trim($form['announcement']),
            'copyright' => trim($form['copyright']),
            'vip' => trim($form['vip']),
            'qq' => trim($form['qq']),
            'phone' => trim($form['phone']),
            'qr_code' => trim($form['qr_code']),
            'email' => trim($form['email']),
            'app_key' => trim($form['app_key']),
            'app_secret' => trim($form['app_secret']),
            'sign' => trim($form['sign']),
        ];
        $data = json_encode($data);
        $status = file_put_contents(self::WEB_SITE_PATH, $data);
        if ($status) {
            Cache::set('web_site', $data);
            return jsone(1, '设置成功');
        } else {
            return jsone(0, '设置失败');
        }

    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
