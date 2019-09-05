<?php
/**
 * Created by PhpStorm.
 * User: xiyou
 * Date: 2017/10/12
 * Time: 15:07
 * 拍卖管理:商品信息，添加用户，商品上传
 *http://www.dservie.net/myWeb/index.php/Operation/OperationAuctionGoods/index
 *
 */
namespace Operation\Controller;
use  Think\Controller;

class OperationAuctionGoodsController  extends  OperationBaseController
{
    public function  index()
    {
     $auctiongoods = new \Operation\Model\AuctionGoodsModel();
     $result=$auctiongoods->select();
     $this->assign('result',$result);
     $this->display();
    }

    public  function  user(){
      $allowuser = new \Operation\Model\AuctionAllowUserModel();
      $result=$allowuser->select();
      $this->assign('result',$result);
      $this->display();
    }

        public  function  auctionUser()
    {

            $this->display();
    }

    public function addAuctionGoods()
    {
      $this->display();
    }
}