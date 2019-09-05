<?php
/**
 * Created by PhpStorm.
 * User: xiyou
 * Date: 2017/9/29
 * Time: 17:33
 * 用户管理
 *http://www.dservie.net/myWeb/index.php/Operation/OperationUser/index
 */
namespace Operation\Controller;
use Think\Controller;


class OperationUserController extends  OperationBaseController
{
    // 用户展示
    public  function index(){
        $p=(String)$_GET["p"]==0?1:$_GET["p"];
         $phone=I("post.phone",'');
         $user=new \WeChat\Model\WeChatUserModel();
         $count=$user->count();
         $Page=new \Think\Page($count,3);
         $map=array();
         if ($phone) {
             $map['phone'] = $phone;
             $show = 1;
             $Page->nowPageage=$p;
             $result=$user->where($map)->limit(1)->select();
         }else{
             $show = $Page->show();
             $Page->nowPageage=$p;
             $result=$user->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();
         }
        $this->assign('page',$show);
        $this->assign('totalPages',$Page->totalPages);
        $this->assign('nowPage',$Page->nowPage);
        $this->assign('result',$result);
        $this->display();
    }

    //删除用户
    public function  delUser(){
        $phone=I('post.phone','');
        if(!phone){
            $this->ajaxReturn(['state'=>0,'msg'=>'异常用户']);
        }
        $map['phone']=$phone;
        $user=new \WeChat\Model\WeChatUserModel();
        $result=$user->where($map)->limit(1)->find();
        if($result){
          $res=$user->where($map)->delete();
        }
        if($res){
            $this->ajaxReturn(['state'=>1,'msg'=>'删除成功']);
        }else{
            $this->ajaxReturn(['state'=>0,'msg'=>'删除失败']);
        }
    }


}