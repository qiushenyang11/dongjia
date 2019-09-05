<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/11/2
 * Time: 17:52
 */

namespace Server;
use WeChat\Model\GoodsModel;


class FoucusService
{

    /**

     获取用户所有收藏
     jdaccount
     *
     * p 分页
     **/
    public function getAllCollect($jdaccount,$p)
    {
        if(!$jdaccount)
        {
            return false;
        }

        $page=0;

        if($p)
        {
            $page=$p;
        }

        $Collect=M("collect");

        $where=array();

        $where["jdaccount"]=$jdaccount;

        $where["isdelete"] = 0;

        $pageCondition=$page.",10";

        $res=$Collect
            ->join('__PRODUCT__ as p ON p.id = __COLLECT__.productid')
            ->field('p.guanjiaid,collect.id,collect.jdaccount,collect.productid,collect.timestamp,collect.isdelete')
            ->where($where)->order('collect.timestamp desc')->page($pageCondition)->select();

        $goodsModel = new GoodsModel();

        foreach ($res as &$item) {
            $item['product_info'] = $goodsModel->getOneServiceProductInfo($item['productid']);
            $item['info_status'] = $goodsModel->checkServieProduct($item['productid']);
            $item['facepic'] =  getshowImgUrl($item['product_info']['facepic']);
        }
        return $res;
    }



    /**
    添加一条关注
     * jdaccount
     * productid
     * productpic
     * fromprice
     * productname
     */

    public function foucusOneService($param)
   {

       if((!$param["jdaccount"])||(!$param["productid"]))
       {
           return false;
       }

       $rst=$this->checkIfCollect($param["jdaccount"],$param["productid"]);

       if($rst)
       {
          return $this->changeCollectStatus($rst["id"],$rst["productid"],$rst["jdaccount"]);
       }
       else
       {
           $Collect=M("collect");
           $param['timestamp'] = time();
           $res=$Collect->add($param);

           if($res)
           {
               return $res;
           }
           else
           {

               return false;
           }
       }
   }

    /**
    判断是否已经关注过


     **/
   public function checkIfCollect($jdaccount,$productid)
   {
       $Collect=M("collect");

       $where=array();

       $where["jdaccount"]=$jdaccount;

       $where["productid"]=$productid;

       $res=$Collect->where($where)->limit(1)->find();

       return $res;

   }


   /***

    * 改变关注状态


    **/
   public function changeCollectStatus($id,$productid,$jdaccount)
   {
       $Collect=M("collect");

       $where=array();

       $where["id"]=$id;

       $where["productid"]=$productid;

       $where["jdaccount"]=$jdaccount;

       $res=$Collect->where($where)->limit(1)->find();

       $isdelete=$res["isdelete"];

       $data=array();

       if($isdelete==0)
       {
           $data["isdelete"]=1;
       }
       else
       {
           $data["isdelete"]=0;
       }

       $resU=$Collect->where($where)->field("isdelete")->save($data);

       return $resU;

   }

    /**
     * 收藏列表数量
     * @return mixed
     */
    public function getCollectCount($jdaccount) {
        $where["jdaccount"]=$jdaccount;
        $where["isdelete"] = 0;
        return M("collect")
            ->join('__PRODUCT__ as p ON p.id = __COLLECT__.productid')
            ->where($where)->count();
    }






}