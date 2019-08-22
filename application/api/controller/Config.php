<?php

namespace app\api\controller;

use think\Controller;
use think\Exception;
use think\Request;

class Config extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        try {
            $config = json_decode(file_get_contents(CONFIG_PATH . 'web_site.json'), true);
            $data = [
                'issue_integral',
                'issue_integral_count',
                'comment_integral',
                'comment_integral_count',
                'service_charge_integral',
                'sign_in_integral'];
            if (!$config) {
                throw new Exception();
            }
            unset($config['issue_integral'], $config['issue_integral_count'], $config['comment_integral'],
                $config['comment_integral_count'], $config['service_charge_integral'], $config['sign_in_integral']);

            return json(['code' => 1, 'msg' => '查询成功', 'data' => $config]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取配置失败'], 500);
        }
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
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
        //
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
