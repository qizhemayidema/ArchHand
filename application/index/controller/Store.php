<?php

namespace app\index\controller;

use app\common\controller\UploadPic;
use app\index\model\LibraryCategory as LibraryCategoryModel;
use think\Controller;
use think\Exception;
use think\exception\HttpException;
use think\Request;
use app\index\model\LibraryHaveAttributeValue as LibraryHaveAttributeValueModel;
use app\index\model\Library as LibraryModel;
use app\index\model\LibraryComment as LibraryCommentModel;
use app\index\model\Store as StoreModel;
use app\index\validate\Library as LibraryValidate;
use app\index\validate\LibraryComment as LibraryCommentValidate;
use think\Validate;

class Store extends Base
{
    public $pageLength = 16;

    public function index(Request $request)
    {
        $store_id = $request->param('store_id');
        //查询店铺信息
        $storeInfo = (new StoreModel())->where(['id'=>$store_id,'status'=>0])->find();
        if (!$storeInfo){
            throw new HttpException(404);
        }

        $cate = (new LibraryCategoryModel())->getCate();


        if (count($cate) > 0) {
            $cate_id = $cate[0]['id'];
        } else {
            $cate_id = 0;
        }
        //分类 ID
        $library = LibraryModel::field('id,library_pic,name')->where('is_delete', 0)
            ->where(['cate_id' => $cate_id,'store_id'=>$store_id])
            ->where('status', 1)
            ->order('is_official desc,create_time desc')
            ->field('is_official,name,library_pic,id');
        $count = $library->count();
        $library = $library->limit(0, $this->listPageLength)->select()->toArray();

        $this->assign('library', $library);
        $this->assign('cate', $cate);
        $this->assign('library_count', $count);
        $this->assign('page_length', $this->pageLength);
        $this->assign('store',$storeInfo);


        return $this->fetch();
    }


    /**
     * 获取筛选后的列表
     * @param Request $request
     * @return \think\response\Json
     */
    public function getList(Request $request)
    {
        //SELECT library_id FROM zhu_library_have_attribute_value
        //WHERE attr_value IN (1,2) GROUP BY library_id HAVING COUNT(attr_value) = 2 ORDER BY library_id DESC LIMIT 0,1
        //分类 ID
        $pageSize = $this->listPageLength;

        $cate = $request->post('cate_id');

        $store_id = $request->post('store_id');

        $search = $request->post('search');
        //属性ID 以逗号分隔
        $attr = $request->post('attr_ids');
        //筛选
        $filtrate = $request->post('filtrate');

        $page = $request->post('page');

        $start = $page * $pageSize - $pageSize;

        $count = 0;
        $length = 0;
        if ($attr) {
            //筛选属性
            $array_attr = explode(',', $attr);
            $attr_value = [];

            foreach ($array_attr as $value) {
                if ($value > 0) {
                    $attr_value[] = $value;
                }
            }
            $length = count($attr_value);
        }

        if ($filtrate == 1) {
            //原创
            $filtrate = 'is_original';
        } else if ($filtrate == 2) {
            //精华
            $filtrate = 'is_classics';
        } else {
            $filtrate = 0;
        }
        try {
            $library = LibraryModel::field('id,library_pic,name,is_official')->where('is_delete', 0)
                ->where('status', 1)->where(['store_id'=>$store_id]);
            if ($search) {
                $library = $library->where('name', 'like', '%' . $search . '%');
            }
            if ($length) {
                $library = $library->where('id', 'in', function ($query) use ($attr_value, $length) {
                    //查询出拥有特定属性的云库ID
                    $query->name('library_have_attribute_value')->field('library_id')
                        ->where('attr_value_id', 'in', $attr_value)->group('library_id')->having('count(attr_value_id)=' . $length);
                });
            } else {
                $library = $library->where(['cate_id' => $cate]);
            }
            if ($filtrate) {
                $library = $library->where($filtrate, 1);
            }
            $count = $library->count();
            $library = $library->order('is_official desc,create_time desc')->limit($start, $pageSize)->select();
            $this->assign('library', $library);

            return json(['code' => 1, 'msg' => '查询成功', 'data' => $this->fetch('library/index_list'), 'count' => $count, 'page_length' => $pageSize], 200);

        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => $e->getMessage()], 500);
        }

    }

    public function updateInfo(Request $request)
    {
        $user_info = $this->userInfo;
        $data = $request->post();
        $rules = [
            'store_name'    => 'require|max:10',
        ];

        $messages = [
            'store_name.require'    => '店铺名称必须填写',
            'store_name.max'        => '店铺名称最长十个字符',
        ];

        $validate = new Validate($rules,$messages);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $result = [
            'store_name'    => $data['store_name']
        ];
        try{    //如果上传出现意外 抛异常
            if (isset($data['store_logo']) && $data['store_logo']){
               $logoRes = (new UploadPic())->uploadBase64Pic($data['store_logo'],'store/'.$user_info['id'].'/');
               if ($logoRes['code'] == 0){
                   return json(['code'=>0,'msg'=>'上传logo失败,请刷新后重新尝试']);
               }
               $result['store_logo'] = $logoRes['msg'];
            }
            if (isset($data['store_background']) && $data['store_background']){
                $backRes = (new UploadPic())->uploadBase64Pic($data['store_background'],'store/'.$user_info['id'].'/');
                if ($backRes['code'] == 0){
                    return json(['code'=>0,'msg'=>'上传背景图失败,请刷新后重新尝试']);
                }
                $result['store_background'] = $backRes['msg'];
            }
        }catch (\Exception $e){
            return json(['code'=>0,'msg'=>'上传图片失败']);
        }
        (new StoreModel())->where(['id'=>$user_info['store_id']])->update($result);

        return json(['code'=>1,'msg'=>'修改成功']);
    }
}
