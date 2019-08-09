<?php

namespace app\admin\model;

use think\Model;

class Permission extends Model
{
    //获取所有权限  返回一维数组
    public function getPermissionList()
    {
        $permissionList = $this->select()->toArray();

        return $this->getList($permissionList);
    }
    //获取所有权限 返回多维数组
    public function getPermissionArray()
    {
        $permissionList = $this->select()->toArray();

        return $this->getArray($permissionList);
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

    protected function getArray($arr,$parent_id = 0){
        $tree = [];
        foreach ($arr as $k=>$v){
            if ($v['p_id'] == $parent_id){
                $v['child'] = $this->getArray($arr,$v['id']);
                if ($v['child'] == null){
                    unset($v['child']);
                }
                $tree[] = $v;
            }
        }
        return $tree;
    }

}
