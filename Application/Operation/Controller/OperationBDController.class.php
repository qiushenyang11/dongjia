<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/10/27
 * Time: 11:25
 */
namespace Operation\Controller;
use Think\Controller;
use WeChat\Model\WeChatUserModel;

class OperationBDController extends OperationBaseController
{
    //BD列表
   public  function  index(){
       $p=(string)$_GET["p"]==0?1:$_GET["p"];
       $type=I("post.type",'');
       $condition=I("post.condition",'');
       $where = [];
       //echo $condition;
       if ($condition) {
           if($type==1){
               $where['id']=$condition;
           } elseif ($type == 2) {
               $where['name']=array('like',"%$condition%");
           } elseif ($type == 3) {
               $where['phone']=$condition;
           }
       }
       $BDModel = new WeChatUserModel();
       $data = $BDModel->BDList($where,$p);
       $result = $data['res'];
       $Page = $data['Page'];
       $count = $data['count'];
       $first=$Page->firstRow+1;
       $rest=($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
       $end = $first + $rest-1;
       $all=$first.'-'.$end;
       $this->assign("condition",$condition);
       $this->assign("type",$type);
       $this->assign("count",$count);
       $this->assign('page',$Page->show());
       $this->assign('nowPage',$p);
       $this->assign('totalPages',$Page->totalPages);
       $this->assign("result",$result);
       $this->assign("all", $all);
       $this->display();
   }

  //新建BD
    public  function  newBD(){
       $name=I("post.name",'');
        $phone=I("post.phone",'');
       $type=I("post.type",'');
        if(!($name && $phone && $type)) response("参数错误");
       $dataBD['name']=$name;
       $dataBD['phone']=$phone;
       $dataBD['type']=$type;
       $BDModel = new WeChatUserModel();
       if($BDModel->hasBDname($name,$type))response("BD姓名不能重复");
       if($BDModel->hasBDphone($phone,$type))response("BD手机号不能重复");
        $res=$BDModel->addBDinfo($dataBD);
        $res ? response("添加成功", 1) : response("添加失败");

    }

    public  function  editBD(){
        $id=I("get.id",'');
        $BDmodel = new WeChatUserModel();
        $res=$BDmodel->getOneBD($id);
        $this->assign("res",$res);
        $this->display();
    }


    //编辑BD信息
    public  function  saveBD(){
        $name=I("post.name",'');
        $phone=I("post.phone",'');
        $type=I("post.type",'');
        $id=I("post.id",'');
        if(!($name && $phone && $type  && $id ))response("参数错误");
        $data=array();
        $data['name']=$name;
        $data['phone']=$phone;
        $data['type']=$type;
        $BDModel = new WeChatUserModel();
        if($BDModel->hasBDname($name, $type, $id))response("BD姓名不能重复");
        if($BDModel->hasBDphone($phone, $type, $id))response("BD手机号不能重复");
        $res=$BDModel->saveBD($id,$data);
        response("修改成功",1);
    }




}