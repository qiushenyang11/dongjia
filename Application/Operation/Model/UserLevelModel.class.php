<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/6/13
 * Time: 17:37
 */

namespace Operation\Model;


class UserLevelModel
{

    public function userList($where, $p){
        $count=$this->getTotal($where);
        $Page=new \Think\Page($count,20);
        $Page->nowPageage=$p;
        $userModel = M("userlevel");
        $res = $userModel->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
        $data['Page'] = $Page;
        $data['res'] = $res;
        $data['count']= $count;
        return $data;
    }

    public function getTotal($where)
    {
        $userModel = M("userlevel");
        return $userModel->where($where)->count();
    }

    public function getOneUser($id){
        if(!$id)return false;
        $userModel = M("userlevel");
        $where['id']=$id;
        $res=$userModel->field('name,phone,level')->where($where)->limit(1)->find();
        return $res;
    }

  public function saveUser($id,$data){
      if(!$id) return false;
      $where['id']=$id;
      $userModel = M("userlevel");
      $res=$userModel->where($where)->save($data);
      return $res;
  }

  public function addUserData($data){
      $userModel = M("userlevel");
      $res=$userModel->data($data)->add();
      return $res;
  }


}