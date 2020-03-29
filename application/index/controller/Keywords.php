<?php

namespace app\index\controller;
use app\index\common\controller\Base;
use think\facade\Request;
use app\index\common\model\AnaKeywords as AnaKeywordsModel;
use app\index\common\model\CatDate as CatDateModel;
use app\index\common\model\KeyDel as KeyDelModel;
use app\index\common\model\KeyImp as KeyImpModel;
use app\index\common\model\ProCat as ProCatModel;

class Keywords extends Base
{
    public function index()
    {
        return $this->fetch();
    }

    public function Save()
    {
        $data = Request::param();
        $date = $data['cat_date'];

        //判断目录是否存在,如果不存在就创建
        $result = CatDateModel::get(function ($query) use ($date){
            $query->where('date', $date)->select();
        });
        if(is_null($result)){
            $cat['date'] = $data['cat_date'];
            $cat['pro_cat_id'] = $data['cat_id'];
            $result = CatDateModel::create($cat);
        }

        $arr = explode('AAA' , $data['desc']);
        unset($arr[0]);
        $newArr['cat_date'] = $result['id'];
        foreach($arr as $k => $v){
            $arr[$k] = trim($v);
            switch($k % 8){
                case 0:
                    $newArr['consume'] = $arr[$k];
                    AnaKeywordsModel::create($newArr);
                    break;
                case 1:
                    $newArr['project'] = $arr[$k];
                    break;
                case 2:
                    $newArr['plan'] = $arr[$k];
                    break;
                case 3:
                    $newArr['keyword'] = $arr[$k];
                    break;
                case 4:
                    $newArr['search'] = $arr[$k];
                    break;
                case 5:
                    $newArr['status'] = $arr[$k];
                    break;
                case 6:
                    $newArr['display'] = $arr[$k];
                    break;
                case 7:
                    $newArr['point'] = $arr[$k];
                    break;
            }
        }
        return $this->redirect('keywords/index');


        //1-8分别对应键名赋值

        //写入数据库

        //$this->error('添加失败');

    }

    public function check()
    {
        //获取目录ID
        $data = Request::param('cat_date');
        //是否存在排序,不存在就默认
        $orderTitle = Request::param('title') ?: 'display';
        $orderSort = Request::param('sort') ?: 'desc';
        //手机端关键词提取并赋值
        $keysInfo = AnaKeywordsModel::where('cat_date',$data)->where('project','like','%'.'wap')->order($orderTitle,$orderSort)->paginate(2000);
        $this->assign('keysInfo',$keysInfo);
        //电脑关键词提取并赋值
        $keysInfo2 = AnaKeywordsModel::where('cat_date',$data)->where('project','like','%'.'pc')->order($orderTitle,$orderSort)->paginate(2000);
        $this->assign('keysInfo2',$keysInfo2);

        //已经删除关键词模板赋值dodo
        $dodo = KeyDelModel::where('pro_cat_id',getProCatID($data))->where('status',1)->order('id','asc')->select();
        //拼接所有keyword字符串并换行
        $newDodo[0] = '';
        $newDodo[1] = '';
        foreach ($dodo as $k => $v){
            $newDodo[0] = $newDodo[0].$v['keyword']."   [展现:".$v['display'].'_点击:'.$v['point'].'_消费:'.$v['consume']."]\n";
            $newDodo[1] = $newDodo[1].$v['keyword']."\n";
        }
        $newDodo[0] = trim($newDodo[0]);
        $newDodo[1] = trim($newDodo[1]);
        //赋值给模板
        $this->assign('newDodo',$newDodo);

        //保留的关键词查询/拼接/赋值
        $do02 = KeyDelModel::where('pro_cat_id',getProCatID($data))->where('status',2)->select();
        $newDo02[0] = '';
        $newDo02[1] = '';
        foreach ($do02 as $k => $v){
            $newDo02[0] = $newDo02[0].$v['keyword']."   [展现:".$v['display'].'_点击:' . $v['point'].'_消费:'.$v['consume']."]\n";
            $newDo02[1] = $newDo02[1].$v['keyword']."\n";
        }
        $newDo02[0] = trim($newDo02[0]);
        $newDo02[1] = trim($newDo02[1]);
        $this->assign('newDo02',$newDo02);

        //继续观察的关键词查询/拼接/赋值
        $do03 = KeyDelModel::where('pro_cat_id',getProCatID($data))->where('status',3)->select();
        $newDo03[0] = '';
        $newDo03[1] = '';
        foreach ($do03 as $k => $v){
            $newDo03[0] = $newDo03[0].$v['keyword']."   [展现:".$v['display'].'_点击:'.$v['point'].'_消费:'.$v['consume']."]\n";
            $newDo03[1] = $newDo03[1].$v['keyword']."\n";
        }
        $newDo03[0] = trim($newDo03[0]);
        $newDo03[1] = trim($newDo03[1]);
        $this->assign('newDo03',$newDo03);

        //重点关键词获取赋值给模板
        $proCatID = CatDateModel::where('id',$data)->value('pro_cat_id');
        $keyImpList= '';
        $keyImpListArr = KeyImpModel::where('pro_cat_id',$proCatID)->order('create_time','asc')->select();
        foreach ($keyImpListArr as $k => $v){
            $keyImpList = $keyImpList . $v['keyword'] . "\n";
        }
        $keyImpList = trim($keyImpList);
        $this->assign('keyImpList',$keyImpList);

        //目录名称获取赋值模板
        $catName = CatDateModel::where('id',$data)->value('date');
        $this->assign('catName',$catName);

        //渲染title
        $this->assign('title','关键词查看与批量管理');
        return $this->fetch();
    }
    //将对应文档目录时间里面的所有数据清空
    public function del()
    {
        $data = Request::param('cat_date');
        AnaKeywordsModel::where('cat_date',$data)->delete();
        return ['status' => 1,'mess'=>'一键清空成功','cat_date' => $data];
    }

    //将对应文档目录时间里面的所有数据清空cat_id
    public function changCatName()
    {
        $data = Request::param();
        CatDateModel::where('id',$data['cat_id'])->update(['date' => $data['cat_name']]);
        return ['status' => 1,'mess'=>'目录名称更新成功','cat_id' => $data['cat_id']];
    }

    //删除对应文档目录时间
    public function delCatDate()
    {
        $data = Request::param('cat_id');
        $name = CatDateModel::where('id',$data)->value('date');
        CatDateModel::where('id',$data)->delete();
        return ['status' => 1,'mess'=>"{$name}一键清空成功"];
    }

    //将对应目录的所有内容设置为未处理
    public function delCle()
    {
        $data = Request::param('cat_date');
        AnaKeywordsModel::where('cat_date',$data)->update(['bg' => 0]);
        return ['status' => 1,'mess'=>'一键处理成功','cat_date' => $data];
    }

    //订单系统里面的关键词添加
    public function pro()
    {
        return $this->fetch();
    }



}