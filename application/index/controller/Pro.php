<?php

namespace app\index\controller;
use app\index\common\controller\Base;
use think\facade\Request;
use app\index\common\model\ProKeywords as ProKeywordsModel;
use app\index\common\model\ProCat as ProCatModel;



class Pro extends Base
{
    public function index()
    {
        return $this->fetch();
    }

    public function Save()
    {
        $data = Request::param();
        $date = $data['cat_id'];
        //判断目录是否存在,如果不存在就创建
        $result = ProCatModel::get(function ($query) use ($date){
            $query->where('cat', $date)->select();
        });
        if(is_null($result)){
            $cat['cat'] = $data['cat_id'];
            $result = ProCatModel::create($cat);
        }
        $arr = explode('AAA' , $data['desc']);
        unset($arr[0]);
        $newArr['cat_id'] = $result['id'];
        foreach($arr as $k => $v){
            $arr[$k] = trim($v);
            switch($k % 5){
                case 0:
                    $newArr['suit'] = $arr[$k];
                    ProKeywordsModel::create($newArr);
                    break;
                case 1:
                    $newArr['search'] = $arr[$k];
                    break;
                case 2:
                    $newArr['pro_day'] = $arr[$k];
                    break;
                case 3:
                    $newArr['week'] = $arr[$k];
                    break;
                case 4:
                    $newArr['pro_time'] = $arr[$k];
                    break;
            }
        }
        return $this->redirect('pro/index');
    }

    public function check()
    {
        $data = Request::param('cat_id');
        $keysInfo = ProKeywordsModel::where('cat_id',$data)->order('id','asc')->paginate(2000);
        $this->assign('keysInfo',$keysInfo);
        return $this->fetch();
    }

    public function del()
    {
        $data = Request::param('cat_id');
        ProKeywordsModel::where('cat_id',$data)->delete();
        return $this->redirect('pro/check',['cat_id' => $data]);
    }


}