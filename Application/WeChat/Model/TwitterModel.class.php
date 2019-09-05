<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/11/2
 * Time: 17:56
 */
/*
 * 所有产品商品的数据库操作
 * */
namespace WeChat\Model;


use function GuzzleHttp\Psr7\rewind_body;
use Think\Page;

class TwitterModel
{

    public function getTwiiterList($page)
    {
        $Twitter = M("article");

        $orderBy=array();

        $orderBy["addtime"]='desc';

        $pageRow = 5;

        $startRow = ($page-1)*$pageRow;

        $endRow   = ($page)*$pageRow;

        $map=array();

        $map["status"]=1;

        $res=$Twitter->where($map)->order($orderBy)->limit($startRow.','.$endRow)->field("id,title,pic,guanjiaid,status,guanjianame,guanjiatype,avatarurl")->select();

        return $res;

    }

    public function getOneTwitter($id)
    {
        $Twitter = M("article");

        $map=array();

        $map["id"]=$id;

        $res=$Twitter->where($map)->limit(1)->find();

        return $res;

    }


}