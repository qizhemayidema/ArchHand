<?php

namespace app\admin\controller;

use think\Controller;
use think\Exception;
use think\Request;
use think\Validate;
use app\admin\model\LibraryAttribute as AttrModel;
use app\admin\model\LibraryAttributeValue as AttrValueModel;
use app\admin\model\LibraryHaveAttributeValue as LHAVModel;

class LibraryAttribute extends Base
{
    public function index()
    {
        $attrList = (new AttrModel())->alias('attr')
                        ->join('library_category cate','cate.id = attr.cate_id')
                        ->field('cate.cate_name,attr.attr_name,attr.id')
                        ->order('id','desc')
                        ->paginate(20);
        foreach ($attrList as $key => $value){
            $attrList[$key]['attr_values'] = (new AttrValueModel())->where(['attr_id'=>$value['id']])->select();
        }

        $this->assign('attr_list',$attrList);
        return $this->fetch();
    }

    public function add()
    {
        $cateList = (new \app\admin\model\LibraryCategory())->select();

        $this->assign('cate_list',$cateList);
        return $this->fetch();
    }

    public function save(Request $request)
    {
        $data = $request->post();

        $rules = [
            'cate_id'       => 'require',
            'attr_name'     => 'require|max:40',
            'attr_values'   => 'require',
        ];

        $message = [
            'cate_id.require'       =>  'error',
            'attr_name.require'     =>  '属性名称必须填写',
            'attr_name.max'         =>  '属性名称长度不能超过40',
            'attr_values.require'   =>  '属性值必须填写',
        ];

        $validate = new Validate($rules,$message);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $attrModel = new AttrModel();

        if ($attrModel->where(['cate_id'=>$data['cate_id'],'attr_name'=>$data['attr_name']])->find()){
            return json(['code'=>0,'msg'=>'此属性名称已经存在']);
        }

        $attrModel->startTrans();

        try{
            $attrModel->insert([
                'cate_id'       => $data['cate_id'],
                'attr_name'     => $data['attr_name'],
            ]);
            $attrId = $attrModel->getLastInsID();
            $attrValues = explode('，',$data['attr_values']);
            $attrValuesSet = [];
            foreach ($attrValues as $key => $value){
                $attrValuesSet[] = [
                    'attr_id'       => $attrId,
                    'value'         => $value,
                ];
            }

            (new AttrValueModel())->insertAll($attrValuesSet);

            $attrModel->commit();

        }catch (Exception $e){
            $attrModel->rollback();
            return json(['code'=>0,'msg'=>'操作失败,请重新尝试']);
        }

        return json(['code'=>1,'msg'=>'success']);
    }

    public function read(Request $request)
    {
        $attr_id = $request->param('id');

        $cateList = (new \app\admin\model\LibraryCategory())->select();

        $attrInfo = (new AttrModel())->find($attr_id);

        $attrValueInfo = (new AttrValueModel())->where(['attr_id'=>$attr_id])->select();

        $this->assign('cate_list',$cateList);

        $this->assign('attr_info',$attrInfo);

        $this->assign('attr_vals',$attrValueInfo);

        return $this->fetch();
    }

    public function update(Request $request)
    {
        $data = $request->post();

        $rules = [
            'id'            => 'require',
            'cate_id'       => 'require',
            'attr_name'     => 'require|max:40',
        ];

        $message = [
            'id.require'            =>  'error',
            'cate_id.require'       =>  'error',
            'attr_name.require'     =>  '属性名称必须填写',
            'attr_name.max'         =>  '属性名称长度不能超过40',
        ];

        $validate = new Validate($rules,$message);
        if (!$validate->check($data)){
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $attrModel = new AttrModel();

        if ($attrModel->where(['cate_id'=>$data['cate_id'],'attr_name'=>$data['attr_name']])
            ->where('id','<>',$data['id'])->find()){
            return json(['code'=>0,'msg'=>'此属性名称已经存在']);
        }

        $attrModel->where(['id'=>$data['id']])->update([
            'cate_id'       => $data['cate_id'],
            'attr_name'     => $data['attr_name'],
        ]);

        if ($data['new_attr_value']){
            $attrValues = explode('，',$data['new_attr_value']);
            $attrValuesSet = [];
            foreach ($attrValues as $key => $value){
                $attrValuesSet[] = [
                    'attr_id'       => $data['id'],
                    'value'         => $value,
                ];
            }

            (new AttrValueModel())->insertAll($attrValuesSet);
        }

        return json(['code'=>1,'msg'=>'success']);
    }

    //删除一个属性
    public function delete(Request $request)
    {
        $id = $request->param('id');
        //判断是否有属性值 如果有 则可以删除
        if ((new AttrValueModel())->where(['attr_id'=>$id])->find()){
            return json(['code'=>0,'msg'=>'有属性值情况下无法删除']);
        }
        (new AttrModel())->where(['id'=>$id])->delete();
        return json(['code'=>1,'msg'=>'success']);
    }

    //删除某个属性值
    public function deleteWithAttrValue(Request $request)
    {
        $id = $request->attr_value_id;

        //删除 value表
        (new AttrValueModel())->where(['id'=>$id])->delete();

        //删除 zhu_library_have_attribute_value 表
        (new LHAVModel())->where(['attr_value_id'=>$id])->delete();

        return json(['code'=>1,'msg'=>'success']);
    }
}
