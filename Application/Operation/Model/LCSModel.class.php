<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/6/25
 * Time: 16:21
 */

namespace Operation\Model;
use Think\Model;


class LCSModel
{

    public function addLCSInfo($dataLCS=''){
       $LCSModel = M("licaishi");
       $res=$LCSModel->data($dataLCS)->add();
       return $res;
    }

    public function lcsList($where, $p){
        $count=$this->getTotal($where);
        $Page=new \Think\Page($count,20);
        $Page->nowPageage=$p;
        $lcsModel = M("licaishi");
        $res = $lcsModel->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
        $data['Page'] = $Page;
        $data['res'] = $res;
        $data['count']= $count;
        return $data;
    }

    public function getTotal($where)
    {
        $lcsModel = M("licaishi");
        return $lcsModel->where($where)->count();
    }


   public function getOneLCS($id){
        if(!$id) return false;
       $userModel = M("licaishi");
       $where['id']=$id;
       $res=$userModel->field('name,phone,jdaccount,status')->where($where)->limit(1)->find();
       return $res;
   }
   public function saveLCS($id,$data){
       if(!$id) return false;
       $where['id']=$id;
       $lcsModel = M("licaishi");
       $res=$lcsModel->where($where)->save($data);
       return $res;
   }

   public function lcsKeHuList($where, $p){
       $count=$this->getKeHuTotal($where);
       $Page=new \Think\Page($count,20);
       $Page->nowPageage=$p;
       $lcsModel = M("licaishikehu");
       $res = $lcsModel->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
       $data['Page'] = $Page;
       $data['res'] = $res;
       $data['count']= $count;
       return $data;

   }

   public function getKeHuTotal($where){
       $lcsModel = M("licaishikehu");
       return $lcsModel->where($where)->count();
   }

   public function addLCSKeHuInfo($dataLCS=''){
       $LCSModel = M("licaishikehu");
       $res=$LCSModel->data($dataLCS)->add();
       return $res;

   }

    public function getOneKeHu($id){
        if(!$id) return false;
        $userModel = M("licaishikehu");
        $where['id']=$id;
        $res=$userModel->field('name,jdaccount,licashiaccount')->where($where)->limit(1)->find();
        return $res;
    }

    public function saveKeHu($id,$data){
        if(!$id) return false;
        $where['id']=$id;
        $lcsModel = M("licaishikehu");
        $res=$lcsModel->where($where)->save($data);
        return $res;
    }


}