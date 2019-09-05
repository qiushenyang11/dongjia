<?php
/**
 * Created by PhpStorm.
 * User: xiyou
 * Date: 2017/11/30
 *
 * 小程序管理数据库操作
 * Time: 12:05
 */

namespace Operation\Model;


class procedureModel
{
    public function  getCount($where){
        $eventModel=M('event');
        $count=$eventModel->where($where)->count();
        return $count;
    }



  public  function  eventInfo($p,$where){
      $count=$this->getCount($where);
      $Page = new \Think\Page($count, 5);
      $Page->nowPageage = $p;
      $eventModel=M('event');
      $res=$eventModel->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->order('id desc')->select();
      $data['res']=$res;
      $data['Page']=$Page;
      $data['count']=$count;
      return $data;


  }



}