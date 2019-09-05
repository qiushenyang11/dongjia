<?php
/**
 * Created by PhpStorm.
 * User: xiyou
 * Date: 2017/11/30
 * 小程序管理 : 活动列表
 * Time: 11:44
 */

namespace Operation\Controller;


use Operation\Model\procedureModel;
use Think\Controller;
use Think\Page;

class OperationProcedureController extends  OperationBaseController
{

   public  function  eventList(){
       $p=I("get.p",'1');
       $condition=I("get.condition",'');
       $type=I("get.type",'');
       $where=array();
       if($type==1){
         $where['id']=$condition;
       }
       if($type==2){
           $where['eventname']=array('like',"%$condition%");
       }

      $list=new procedureModel();
      $data=$list->eventInfo($p,$where);
      $Page=$data['Page'];
      $first=$Page->firstRow+1;
      $center=$Page->listRows-1;
      $end=$first+$center;
      $all=$first.'-'.$end;
      $count=$data['count'];
      $res=$data['res'];
      $this->assign("res",$res);
      $this->assign("count",$count);
      $this->assign("Page",$Page->show());
      $this->assign("nowPage",$p);
      $this->assign("total",$Page->totalPages);
       $this->assign("all",$all);
      $this->display();
   }



    
    
}
            
            
