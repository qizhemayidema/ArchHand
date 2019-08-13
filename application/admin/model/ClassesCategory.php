<?php

namespace app\admin\model;

use think\facade\Env;
use think\Model;

class ClassesCategory extends Model
{

    protected $table = 'zhu_class_category';

    public function getCategoryList()
    {
        $data = $this->select()->toArray();
        return $data;
    }

    protected function getList($arr, $pid =0, $level = 0)
    {
        static $list = [];
        foreach ($arr as $key => $value){
            //第一次遍历,找到父节点为根节点的节点 也就是pid=0的节点
            if ($value['p_id'] == $pid){
                //父节点为根节点的节点,级别为0，也就是第一级
                $value['level'] = $level;
                //把数组放到list中
                $list[] = $value;
                //把这个节点从数组中移除,减少后续递归消耗
                unset($arr[$key]);
                //开始递归,查找父ID为该节点ID的节点,级别则为原级别+1
                $this->getList($arr, $value['id'], $level+1);
            }
        }
        return $list;

    }
}
