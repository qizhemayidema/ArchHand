<?php

namespace app\admin\controller;

use think\Db;
use think\facade\Cache;
use think\Request;
use app\admin\validate\LibraryCategory as LibraryCategoryValidate;
use app\admin\model\LibraryCategory as LibraryCategoryModel;
use app\admin\model\LibraryTag as LibraryTagModel;
use app\admin\model\Library;
use page\Page;

class LibraryCategory extends Base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //检测当前是否有文库分类缓存，没有设置
        if(!Cache::has('libraryCategory')){
            $this->setCache();
        }

        //获取分类数组
        $categories = getCategories();
        //当权页码
        $current_page = \think\facade\Request::param('page') ?: 1;
        //总数
        $total = count($categories);
        //每页条数
        $per_page = 15;
        //总页码
        $last_page = ($total + $per_page - 1) / $per_page;
        //开始坐标
        $start = ($current_page - 1) * $per_page;
        //截取数组内特定数量数据
        $newArr = array_slice($categories, $start, $per_page);

        intval($last_page) == 1 ? $html = null : $html = (new Page($total, $per_page, 'paging', $current_page))->render;


        $this->assign('categories', $newArr);
        $this->assign('html', $html);
        return $this->fetch();

    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function add()
    {

        $categories = LibraryCategoryModel::all();

        $this->assign('categories', $categories);
        return $this->fetch('add_edit');

    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $cate = $request->only('p_id,name');

        $validate = new LibraryCategoryValidate();
        if (!$validate->check($cate)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        $category = Db::name('library_category')->where('id', $cate['p_id'])->find();
        $data['cate_name'] = $cate['name'];
        if ($category) {
            $data['p_id'] = $category['id'];
            $data['ids_string'] = $category['ids_string'] . $category['id'] . '-';
        } else {
            $data['p_id'] = 0;
            $data['ids_string'] = '-';
        }

        $insert = Db::name('library_category')->insertGetId($data);
        if ($insert) {
            //新增分类后，重新创建分类缓存
            $this->setCache();
            return json(['code' => 1, 'msg' => '添加成功']);
        }
        return json(['code' => 0, 'msg' > 'error']);


    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {
        $categories = (new LibraryCategoryModel())->select();
        if (!$categories) {
            $this->assign('is_exist', false);
            return $this->fetch('library_category/add_edit');
        }
        $cate = $categories->where('id', $id);
        if (count($cate) == 0) {
            $this->assign('is_exist', false);
            return $this->fetch('library_category/add_edit');
        }
        foreach ($cate as $v) {
            $cate = $v;
        }
        $parent = $cate['p_id'] == 0 ? true : false;
        $this->assign('categories', $categories);

        $this->assign('cate', $cate);

        $this->assign('parent', $parent);
        return $this->fetch('library_category/add_edit');
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

        $cate = $request->post();
        $validate = new LibraryCategoryValidate();

        if (!$validate->check($cate)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        //判断当前分类是否可以编辑
        if ($msg = $this->check($cate)) {
            return $msg;
        }
        //默认没有更改父级分类是修改的数据
        $data = [
            'id' => $cate['id'],
            'cate_name' => $cate['name'],
            'p_id' => $cate['p_id'],
        ];

        if ($cate['p_id'] == 0) {
            $data['ids_string'] = '-';
        } else {
            $is_string = LibraryCategoryModel::where('id', $cate['p_id'])->value('ids_string');
            $data['ids_string'] = $is_string . $cate['p_id'] . '-';
//            }
        }

        $category = LibraryCategoryModel::update($data);
        if ($category) {
            //编辑分类后，重新创建分类缓存
            $this->setCache();
            return jsone(1, '修改成功');
        } else {
            return jsone(0, '修改失败');
        }
    }

    /**
     * 删除指定资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        try {
            $cate = LibraryCategoryModel::where('id', $id)->find();
            if ($cate) {
                $cate['cp_id'] = $cate['p_id'];
                if ($msg = $this->check($cate, true)) return $msg;
            }
           if( $cate->delete())
               //删除分类后，重新创建分类缓存
               $this->setCache();
               return jsone(1,'删除成功');
        }catch(\Exception $e){
            return jsone(0,'系统错误');
        }
    }

    /**
     * 检测修改或删除的分类是否有子分类、标签、文库，有则不能删除
     * @param $cate
     * @param null $is_delete
     * @return string|\think\response\Json
     */
    public function check($cate,$is_delete=null)
    {
        $msg = '';
        //判断当前分类p_id是否有修改
        if ($cate['cp_id'] != $cate['p_id']||$is_delete) {

            //判断当前修改分类是否是一级分类，是：判断是否有标签 否：判断是否有关联文本
            if ($cate['cp_id'] == 0||$is_delete) {
                $tag = LibraryTagModel::where('cate_id', $cate['id'])->count('id');
                if ($tag) {
                    $msg = jsone(0, '当前分类下有标签，不能修改父级分类或删除当前分类');
                }
                $categories = LibraryCategoryModel::all();
                foreach ($categories as $v) {
                    if ($cate['id'] == $v['p_id']) {
                        $msg = jsone(0, '当前分类下有子分类，不能修改父级分类或删除当前分类');
                    }
                }
            }
            //判断二级分类下是否有文库
            $library = Library::where('cate_id', $cate['id'])->count('id');
            if ($library) {
                $msg = jsone(0, '当前分类下有文库，不能修改父级分类或删除当前分类');
            }
        }
        return $msg;
    }

    public function setCache(){
        $options = [
            'type'=>'File',
            'expire'=>0,
            'path'=>'../runtime/cache',
        ];
        Cache::init($options);
        Cache::set('libraryCategory',getCategoryTree());
    }

}
