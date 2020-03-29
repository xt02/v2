<?php
namespace app\index\controller;
use app\index\common\controller\Base;
use think\facade\Request;
use app\index\common\model\KeyDel as KeyDelModel;
use app\index\common\model\AnaKeywords as AnaKeywordsModel;
use app\index\common\model\ProKeywords as ProKeywordsModel;

class Sol extends Base
{
    //删除错误写入数据库中的单词,将bg变为0
    public function do00()
    {
        $data = Request::param();
        $arr = explode("\n",$data['content']);
        $num = 0;
        foreach ($arr as $k => $v){
            //检查关键词是否存在,如果存在,就删除
            $res =KeyDelModel::where('pro_cat_id',getProCatID($data['cat_date']))->where('keyword',$v)->find();
            if($res){
                KeyDelModel::where('pro_cat_id',getProCatID($data['cat_date']))->where('keyword',$v)->delete();
            }
            $num += AnaKeywordsModel::where('cat_date',$data['cat_date'])->where('search','like','%'.$v.'%')->update(['bg' => 0]);
        }
        return ['status'=>1,'mess'=>'更新成功','cat_date' => $data['cat_date'],'num'=>$num];
    }

    //关键词批量剔除/保留/继续观察
    public function do01()
    {
        $data = Request::param();
        $arr = explode("\n",$data['content']);
        $newArr['pro_cat_id'] = getProCatID($data['cat_date']);
        $newArr['status'] = 1;
        $num = 0;
        foreach ($arr as $k => $v){
            $newArr['keyword'] = $v;
            //检测数值是不是包含在数据库中,如果是有数据包含,则设置bg 1,$num替换个数
            $num += AnaKeywordsModel::where('cat_date',$data['cat_date'])->where('search','like','%'.$v.'%')->update(['bg' => 1]);
            $newArr['display'] = AnaKeywordsModel::where('cat_date',$data['cat_date'])->where('search','like','%'.$v.'%')->sum('display');
            $newArr['point'] = AnaKeywordsModel::where('cat_date',$data['cat_date'])->where('search','like','%'.$v.'%')->sum('point');
            $newArr['consume'] = round(AnaKeywordsModel::where('cat_date',$data['cat_date'])->where('search','like','%'.$v.'%')->sum('consume'));
            //判断是否已经删除过,如果没有就写入,如果存在就全部更新
            $res =KeyDelModel::where('pro_cat_id',$newArr['pro_cat_id'])->where('status',1)->where('keyword',$v)->find();
            if(is_null($res)){
                KeyDelModel::create($newArr);
            }else{
                KeyDelModel::where('id',$res['id'])->data($newArr)->Update();
            }
        }
        return ['status'=>1,'mess'=>'剔除成功','cat_date' => $data['cat_date'],'num'=>$num];
    }

    public function do02()
    {
        $data = Request::param();
        $arr = explode("\n",$data['content']);
        $newArr['pro_cat_id'] = getProCatID($data['cat_date']);
        $newArr['status'] = 2;
        $num = 0;
        foreach ($arr as $k => $v){
            $newArr['keyword'] = $v;
            $newArr['display'] = AnaKeywordsModel::where('cat_date',$data['cat_date'])->where('bg','<>',1)->where('search',$v)->sum('display');
            $newArr['point'] = AnaKeywordsModel::where('cat_date',$data['cat_date'])->where('bg','<>',1)->where('search',$v)->sum('point');
            $newArr['consume'] = round(AnaKeywordsModel::where('cat_date',$data['cat_date'])->where('bg','<>',1)->where('search',$v)->sum('consume'));
            //判断是否(保留关键词)已经删除过,如果没有就写入
            $res =KeyDelModel::where('pro_cat_id',$newArr['pro_cat_id'])->where('status',2)->where('keyword',$v)->find();
            if(is_null($res)){
                KeyDelModel::create($newArr);
            }else{
                KeyDelModel::where('id',$res['id'])->data($newArr)->Update();
            }
            //检测数值是不是包含在数据库中,如果是有数据包含,则设置bg 1
            $num += AnaKeywordsModel::where('cat_date',$data['cat_date'])->where('bg','<>',1)->where('search',$v)->update(['bg' => 2]);
        }
        return ['status'=>1,'mess'=>'保留成功(字体绿色显示)','cat_date' => $data['cat_date'],'num'=>$num];
    }


    //关键词提取搜索
    public function search()
    {
        $data = Request::param();
        if($data['searchKey'] == 0){
            $res = AnaKeywordsModel::where('cat_date',$data['cat_date'])->where('search','like','%'.$data['keyword'].'%')->select();
        }else{
            $res = AnaKeywordsModel::where('cat_date',$data['cat_date'])->where('bg',0)->where('search','like','%'.$data['keyword'].'%')->select();
        }

        $res02 ='';
        foreach ($res as $k => $v){
            $res02 = $res02 . $v['search'] ."\n";
        }
        $count = count($res);
        return ['status'=>1,'mess'=>$res02,'cat_date' => $data['cat_date'],'num'=>$count];
    }

    //关键词查询在数据库和订单库同步查询
    public function check()
    {
        $data = Request::param();
        //数组分割时,是以换行为分割,换行也是需要去掉才可以查询
        $arr = explode("\n",$data['keyword02']);
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
            $res01[$k] = AnaKeywordsModel::where('cat_date',$data['cat_date'])->where('search','like','%'.$v.'%')->order('display','desc')->select();
            $sum[$k]['display'] = AnaKeywordsModel::where('cat_date',$data['cat_date'])->where('search','like','%'.$v.'%')->sum('display');
            $sum[$k]['point'] = AnaKeywordsModel::where('cat_date',$data['cat_date'])->where('search','like','%'.$v.'%')->sum('point');
            $sum[$k]['consume'] = round(AnaKeywordsModel::where('cat_date',$data['cat_date'])->where('search','like','%'.$v.'%')->sum('consume'));
            $sum[$k]['num'] = count($res01[$k]);
        }
        $this->assign('sum',$sum);
        $this->assign('res01',$res01);

        //搜索订单词符合的语句
        $res02 = [];
        //总成交数/2019成交数
        $sum02 =[];
        foreach ($arr as $k => $v){
            $res02[$k] = ProKeywordsModel::where('cat_id',$data['cat_id'])->where('search','like','%'.$v.'%')->order('pro_day','desc')->select();
            $sum02[$k]['total'] = count($res02[$k]);
            $sum02[$k][2019] = count(ProKeywordsModel::where('cat_id',$data['cat_id'])->where('search','like','%'.$v.'%')->where('pro_day','like','2019'.'%')->order('pro_day','desc')->select());
        }
        $this->assign('sum02', $sum02);
        $this->assign('res02',$res02);
        $num = count($res02);
        $this->assign('num',$num);
        //halt($arr);

        $this->assign('keyword',$arr);
        return $this->fetch();
    }

    //单个词写入到数据库删除
    public function keyDo01()
    {
        $data = Request::param();
        $data['status'] = 1;
        //判断关键词是否有写入,如果为空就写入
        $res = KeyDelModel::where('cat_date',$data['cat_date'])->where('keyword',$data['keyword'])->find();
        if (is_null($res)){
            KeyDelModel::create($data);
            $num = AnaKeywordsModel::where('cat_date',$data['cat_date'])->where('search','like','%'.$data['keyword'].'%')->update(['bg' => 1]);
        }else{
            return ['status' => 0,'mess'=>'失败,当前数据库中已经存在'];
        }
        return ['status' => 1,'mess'=>'剔除成功','num' => $num];
    }

    //保留的词写入到数据库
    public function keyDo02()
    {
        $data = Request::param();
        $data['status'] = 2;
        //判断关键词是否有写入,如果为空就写入
        $res = KeyDelModel::where('keyword',$data['keyword'])->find();
        if (is_null($res)){
            KeyDelModel::create($data);
            $num = AnaKeywordsModel::where('cat_date',$data['cat_date'])->where('bg','<>',1)->where('search','like','%'.$data['keyword'].'%')->update(['bg' => 2]);
        }else{
            return ['status' => 0,'mess'=>'失败,当前数据库中已经存在'];
        }
        return ['status' => 1,'mess'=>'保留成功','num' => $num];
    }

    //继续的词写入到数据库
    public function keyDo03()
    {
        $data = Request::param();
        $data['status'] = 3;
        //判断关键词是否有写入,如果为空就写入
        $res = KeyDelModel::where('keyword',$data['keyword'])->find();
        if (is_null($res)){
            KeyDelModel::create($data);
            $num = AnaKeywordsModel::where('cat_date',$data['cat_date'])->where('bg','=',0)->where('search','like','%'.$data['keyword'].'%')->update(['bg' => 3]);
        }else{
            return ['status' => 0,'mess'=>'失败,当前数据库中已经存在'];
        }
        return ['status' => 1,'mess'=>'继续观察关键词成功','num' => $num];
    }


}