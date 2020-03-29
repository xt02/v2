<?php

namespace app\index\common\controller;
use think\Controller;
use app\index\common\model\CatDate as CatDateModel;
use app\index\common\model\ProCat as ProCatModel;


class Base extends Controller
{
    protected function initialize()
    {
        //文档目录全局模板
        $catDateInfo = CatDateModel::all()->order('create_time','desc');
        $this->assign('catDateInfo',$catDateInfo);
        //产品关键词目录全局模板
        $proCatInfo = ProCatModel::all();
        $this->assign('proCatInfo',$proCatInfo);
    }

}