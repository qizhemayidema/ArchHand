<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\facade\Log;
use think\Request;
use app\admin\validate\LibraryCategory as LibraryCategoryValidate;
use app\admin\model\LibraryCategory as LibraryCategoryModel;

class LibraryCategory extends Base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $categories = LibraryCategoryModel::all();

//        return json(getCategoryTree($categories));
        dump(getCategoryTree($categories));die;

        $categories = getCategories(getCategoryTree());
        $page = \think\facade\Request::param('page') ?: 1;
        $pageCount = count($categories);
        $pageSize = 10;
        $start = ($page - 1) * $pageSize;
        $newArr = array_slice($categories, $start, $pageSize);
        Log::error($page);
        $html = sprintf('<ul class="pagination">
                <li class="disabled"><span>«</span></li>
                <li class="active"><span>%s</span></li>
                <li><a href="/admin/library_category/index.html?page=%s">%s</a></li>
                <li><a href="/admin/library_category/index.html?page=%s">»</a></li>
            </ul>', ($start == 0) ? $page : ($start > 1 ? $page - 1 : $page + 1),
            ($start == 0) ? $page+1 : ($start > 1 ? $page - 1 : $page + 1),
            ($start == 0) ? $page+1 : ($start > 1 ? $page - 1 : $page + 1),
            ($start == 0) ? $page+1 : ($start > 1 ? $page - 1 : $page + 1));
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
        $this->assign('categories', getCategories(getCategoryTree()));
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
        $cate = $request->only('id,name');

        $validate = new LibraryCategoryValidate();
        if (!$validate->check($cate)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }
        $category = Db::name('library_category')->where('id', $cate['id'])->find();
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
