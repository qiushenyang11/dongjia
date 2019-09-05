<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/10/12
 * Time: 15:14
 */

namespace Operation\Model;
use Think\Model;


class BannerModel  extends  Model
{


    public function  addOneBanner($data)
    {
        $banner=M("banner");

        $res=$banner->add($data);

        return $res;
    }

    public  function  getCount()
    {
        $banner = M("banner");

        $where['isdelete']=0;

        $count=$banner->where($where)->count();

        return $count;
    }

    public function updateBanner($id,$array)
    {
        $banner = M("banner");

        $where['id']=$id;

        $rst=$banner->where($where)->save($array);

        return $rst;
    }



    public function downLine($data)
    {
        $id=$data["id"];

        $map["id"]=$id;

        $banner=M("banner");

        $res=$banner->where($map)->setField("isdelete",1);

        return $res;
    }

    public function searchBanner($whereArray)
    {

        $banner=M("banner");

        $res=$banner->where($whereArray)->select();

        return $res;

    }

    public function findBannerOne($id)
    {
        $banner=M("banner");

        $where=array();

        $where["id"]=$id;

        $res=$banner->where($where)->limit(1)->find();

        return $res;
    }

    public function getShowBanner()
    {
        $address = session('address');
        $banner=M("banner");
        $time = time();
        $where['showstarttime']=['lt',$time];
        $where['showendtime'] = ['egt', $time];
        $where['ishow'] = 1;
        $where['isdelete'] = 0;
        if ($address) {
            $where['_string'] = "`servicecity` like '%全国%' or `servicecity` like '%".$address."%'";
        }
        $res = $banner->field('pic,urltype,urltvalue')->where($where)->order('frame asc')->select();
        return $res;
    }

}
