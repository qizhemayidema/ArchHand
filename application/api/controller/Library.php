<?php

namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Request;
use app\api\model\LibraryHaveAttributeValue as LibraryHaveAttributeValueModel;
use app\api\model\Library as LibraryModel;

class Library extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index(Request $request)
    {
        //SELECT library_id FROM zhu_library_have_attribute_value
        //WHERE attr_value IN (1,2) GROUP BY library_id HAVING COUNT(attr_value) = 2 ORDER BY library_id DESC LIMIT 0,1
        $cate = $request->get('cate');
        $attr = $request->get('attr');
        try {
            if (!$attr) {
                $library = LibraryModel::field('id,library_pic,name')->where('cate_id', $cate)->paginate(16);
            } else {
                $cate = explode(',', $attr);
                $length = count($cate);
                $library = LibraryModel::field('id,library_pic,name')->where('id', 'in', function ($query) use ($cate, $length) {
                    $library_id = $query->name('library_have_attribute_value')->field('library_id')->where('attr_value', 'in', $cate)
                        ->group('library_id')->having('count(attr_value)=' . $length);
                })->paginate(16);
            }

            return json($library);
        }catch(\Exception $e){
            
        }

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
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
