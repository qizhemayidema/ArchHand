<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Request;
use app\admin\Model\LibraryTag as LibraryTagModel;
use app\admin\Model\LibraryCategory as LibraryCategoryModel;
use app\admin\validate\LibraryTag as LibraryTagValidate;
use app\admin\Model\Library as LibraryModel;

class LibraryTag extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $tags = LibraryTagModel::paginate(15);
        if (!$tags) {
            $this->assign('is_exist', null);
        }
        $this->assign('tags', $tags);
        return $this->fetch();

    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function add()
    {
        try {
            $categories = LibraryCategoryModel::where('p_id', 0)->all();

            $this->assign('categories', $categories);
            return $this->fetch('library_tag/add_edit');
        } catch (\Exception $e) {
            return jsone(0, '系统错误');
        }
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $tag = $request->only('name,cate_id');
        $validate = new LibraryTagValidate();
        if (!$validate->check($tag)) {
            return jsone(0, $validate->getError());
        }

        $tag = LibraryTagModel::create($tag);
        if ($tag) {
            return jsone(1, '创建成功');
        }
        return jsone(0, '创建失败');
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
        try {
            $tag = LibraryTagModel::where('id', $id)->find();
            $categories = LibraryCategoryModel::where('p_id', 0)->all();

            if ($tag) {
                $this->assign('tag', $tag);
                $this->assign('categories', $categories);
                return $this->fetch('library_tag/add_edit');
            }
            return jsone(0, '未查找到数据');
        } catch (\Exception $e) {
            return jsone(0, '系统错误');
        }
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

        $tag = $request->only('id,cate_id,name');

        $validate = new LibraryTagValidate();
        if (!$validate->check($tag)) {
            return jsone(0, $validate->getError());
        }
        Db::startTrans();
        try {
            //更新标签表
            $tag = LibraryTagModel::update($tag);
            if ($tag) {
              if($this->updateLibraryTag($tag)){
                  Db::commit();
                  return jsone(1,'编辑成功');
              }
            }
            return jsone(0, '编辑失败');
        }catch(\Exception $e){
            Db::rollback();
            return jsone(0,'系统错误');
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

        Db::startTrans();
        try {
            $cate = LibraryTagModel::where('id', $id)->delete();

            if (!$cate) {
                return jsone(0, '未找到当前标签');
            }

            if($this->updateLibraryTag(['id'=>$id],true)){
                Db::name('library_tag_list')->where('tag_id', $id)->delete();
                Db::commit();
                return jsone(1,'删除成功');
            }else{
                Db::rollback();
                return jsone(0,'删除失败');
            }
//            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            return jsone(0, '系统错误');
        }
    }


    public function updateLibraryTag($tag,$is_delete=false){
        try {
            //查找当前标签对应的文库
            $list = Db::name('library_tag_list')->where('tag_id', $tag['id'])->column('library_id');
            if (!$list) {
                return true;
            }
            //查找文库中标签序列
            $library_serialize = Db::name('library')->where('id', 'in', $list)->field(['id', 'tag_serialize'])->select();

            $data = [];
            if ($library_serialize) {
                foreach ($library_serialize as $v) {
                    $tags = unserialize($v['tag_serialize']);
                    if($is_delete){
                        unset($tags[$tag['id']]);
                        $tags = serialize($tags);
                    }else {
                        $tags[$tag['id']] = $tag['name'];
                        $tags = serialize($tags);
                    }
                    $data[] = [
                        'id' => $v['id'],
                        'tag_serialize' => $tags,
                    ];
                }
                $library_serialize = (new LibraryModel())->saveAll($data);
                if ($library_serialize) {
                    return true;
                }
            }
        }catch(\Exception $e){
            return false;
        }
    }
}
