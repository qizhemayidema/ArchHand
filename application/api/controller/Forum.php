<?php

namespace app\api\controller;

use think\Controller;
use think\Request;
use app\api\model\ForumPlate as PlateModel;

class Forum extends Base
{
    /**
     * 获取二级分类
     * @return \think\response\Json
     */
    public function getAllPlate()
    {
        return json(['code'=>1,'data'=>(new PlateModel())->getList()]);
    }


}
