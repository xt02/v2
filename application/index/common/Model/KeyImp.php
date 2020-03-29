<?php

namespace app\index\common\model;
use think\Model;

class KeyImp extends Model
{
    protected $pk = 'id';//默认主键
    protected $table = 'key_imp';//默认数据表
    protected $autoWriteTimestamp = true;//自动时间戳
    protected $createtime = 'create_time';
    protected $updatetime = 'update_time';
}
