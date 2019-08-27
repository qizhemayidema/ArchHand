<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class FiltrateSqlLog extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $array = [];
        $file = '../runtime/log/201908/23_sql.log';
        try {
            $file = fopen($file, "r"); // 以只读的方式打开文件
            $i = 0;
            $itemStr = [];
            while (!feof($file)) {
                $itemStr[] = fgets($file); //fgets()函数从文件指针中读取一行
            }
            fclose($file);

            foreach ($itemStr as $v) {
                preg_match('/(?<=\[\sSQL\s\]\s)([\s\S^\[]*)(?=\s\[\s)/', $v, $array[]);
            }
            $array_preg = [];
            $show = [];
            //筛选数据
            foreach ($array as $v) {
                if (!empty($v)) {
                    if (!preg_match('/SHOW/', $v[0], $show[])) {
                        $array_preg[] = $v[0];
                    }
                }
            }
            //写入
            $file = fopen('../runtime/log/sql_preg.log', "w");
            foreach ($array_preg as $v) {
                fwrite($file, $v . "\n");
            }
            fclose($file);
            return json('写入成功');
        } catch (\Exception $e) {
            return json(['code' => $e->getCode(), 'line' => $e->getLine(), 'msg' => $e->getMessage()]);
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
