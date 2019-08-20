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
        //分类 ID
        $cate = $request->get('cate');
        if($cate){
            $cate_field = 'cate_id';
        }else{
            $cate_field = '';
        }
        //属性ID 以逗号分隔
        $attr = $request->get('attr');
        //筛选
        $filtrate = $request->get('filtrate');
        if($filtrate==1){
            //原创
            $filtrate = 'is_original';
        }else if($filtrate==2){
            //精华
            $filtrate = 'is_classics';
        }
        try {
            if (!$attr) {
                $library = LibraryModel::field('id,library_pic,name')->where('is_delete',0)
                    ->where('status',1)->where($cate_field,$cate)->where($filtrate,1)->order('create_time desc')->paginate(16);
            } else {
                $attr_value = explode(',', $attr);
                $length = count($attr_value);
                $library = LibraryModel::field('id,library_pic,name')->where('id', 'in', function ($query) use ($attr_value, $length) {
                    //查询出拥有特定属性的云库ID
                    $library_id = $query->name('library_have_attribute_value')->field('library_id')->where('attr_value', 'in', $attr_value)
                        ->group('library_id')->having('count(attr_value)=' . $length);
                })->where('is_delete',0)->where('status',1)->where($filtrate,1)->order('create_time desc')->paginate(16);
            }

            return json($library);
        }catch(\Exception $e){
            return json('出错啦');
        }

    }

    public function show(Request $request){
        if(!$id = $request->get('id')){
            return '参数错误';
        }
        //name user_id name_status create_time see_num integral source_url gehsi size desc
        //like_num collect_num comment_num is_classics is_official
        $library = LibraryModel::field('id,name,user_id,name_status,create_time,see_num,integral,source_url,suffix,data_size,desc,
        like_num,collect_num,comment_num,is_classics,is_official')->where('id',1)->find();

        

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
