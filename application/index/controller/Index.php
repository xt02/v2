<?php
namespace app\index\controller;
use app\index\common\controller\Base;


class Index extends Base
{
    public function index()
    {

        return $this->fetch();
    }

    public function hello($name = 'ThinkPHP5')
    {
        return 'hello,' . $name;
    }
}
