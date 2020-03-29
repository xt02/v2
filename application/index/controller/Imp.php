<?php

namespace app\index\controller;
use app\index\common\controller\Base;
use think\facade\Request;
use app\index\common\model\AnaKeywords as AnaKeywordsModel;
use app\index\common\model\ProKeywords as ProKeywordsModel;

class Imp extends Base
{
    public function index()
    {
        $data = Request::param('do-imp-content02');
        $cat_date_id = Request::param('cat_date');
        $pro_cat_id = Request::param('cat_id');
        $arr = explode("\n",$data);
        foreach ( $arr as $k => $v){
            $arr[$k] = trim($v);
        }

        //判断数组是否为空,如果为空终止
        if(empty($arr[0])){
            halt('数据不能为空');
        }

        //获取搜索词中符合条件的语句
        $res01 = [];
        //求和display/point/consume
        $sum = [];
        foreach ($arr as $k => $v){
            $res01[$k] = AnaKeywordsModel::where('cat_date',$cat_date_id)->where('keyword',$v)->order('display','desc')->select();
            $sum[$k]['display'] = AnaKeywordsModel::where('cat_date',$cat_date_id)->where('keyword',$v)->sum('display');
            $sum[$k]['point'] = AnaKeywordsModel::where('cat_date',$cat_date_id)->where('keyword',$v)->sum('point');
            $sum[$k]['consume'] = round(AnaKeywordsModel::where('cat_date',$cat_date_id)->where('keyword',$v)->sum('consume'));
            $sum[$k]['num'] = count($res01[$k]);
        }
        $this->assign('sum',$sum);
        $this->assign('res01',$res01);

        //搜索订单词符合的语句
        $res02 = [];
        //总成交数/2019成交数
        $sum02 =[];
        foreach ($arr as $k => $v){
            $res02[$k] = ProKeywordsModel::where('cat_id',$pro_cat_id)->where('search','like','%'.$v.'%')->select();
            //halt($res02[$k]);
            $sum02[$k]['total'] = count($res02[$k]);
            $sum02[$k][2019] = count(ProKeywordsModel::where('cat_id',$pro_cat_id)->where('search','like','%'.$v.'%')->where('pro_day','like','2019'.'%')->order('pro_day','desc')->select());
        }
        $this->assign('sum02', $sum02);
        $this->assign('res02',$res02);
        $this->assign('keyword',$arr);

        return $this->fetch();
    }

}