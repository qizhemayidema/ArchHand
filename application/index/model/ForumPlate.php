<?php
namespace app\index\model;

use think\Model;
use app\api\model\ForumCategory as CateModel;
use think\Cache;

class ForumPlate extends Model
{
    public function getList()
    {
        //先从缓存 获取 如果没有 则生成
        $cache = new Cache(['type'=>config('cache.type')]);
        $forumPlateList = $cache->get('forum_plate_list');
        if (!$forumPlateList){
            $forumPlateList = $this->makeList();
             $cache->set('forum_plate_list',$forumPlateList);
        }
        return $forumPlateList;
    }

    private function makeList()
    {
        $list =  (new CateModel())->field('id,cate_name')->select()->toArray();
        foreach ($list as $key => $value){
            $list[$key]['plate'] = $this->where(['cate_id'=>$value['id'],'is_delete'=>0])->select()->toArray();
        }

        return $list;
    }
}
