<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/27
 * Time: 17:12
 * url: https://www.dservie.cn/myWeb/Operation/OperationComment/
 */

namespace Operation\Controller;
use Operation\Model\CommentModel;
use Server\Comment;
use WeChat\Model\GoodsModel;
use WeChat\Model\GuanJiaModel;


class OperationCommentController extends OperationBaseController
{
   public function index(){
       $starttime = I('get.starttime','');
       $endtime = I('get.endtime', '');
       $value = I('get.value', '');
       $type = I('get.type', 0);
       $guanjiaid = I('get.guanjiaid', 0);
       $commentlevel = I('get.commentlevel',0);
       $isshow = I('get.isshow', 0);
       $trueuser = I('get.trueuser', '');
       $p = I('get.p',1);
       $guanjiaModel = new GuanJiaModel();
       $guanjiaInfo = $guanjiaModel->getAllGuanjia();
       if ($value) {
           if ($type == 1) {
               $where['orderid'] = $value;
           } elseif ($type == 2) {
               $where['productid'] = $value;
           } elseif ($type == 3) {
               $where['productname'] = ['like','%'.$value.'%'];
           } elseif ($type == 4) {
               $where['phone'] = $value;
           } elseif ($type == 5) {
               $where['addressname'] = $value;
           } elseif ($type == 6) {
               $where['userid'] = $value;
           }
       }
       if ($guanjiaid) {
           $where['guanjiaid'] = $guanjiaid;
       }
       if ($commentlevel) {
           $where['commentlevel'] = $commentlevel;
       }
       if ($isshow) {
           $where['isshow'] = $isshow;
       }
       if ($starttime && $endtime) {
           $where['addtime'] = [['gt',strtotime($starttime)],['elt',strtotime($endtime)]];
       } elseif($starttime) {
           $where['addtime'] = ['gt',strtotime($starttime)];
       } elseif ($endtime) {
           $where['addtime'] = ['elt',strtotime($endtime)];
       }
       if ($trueuser != '') {
           $where['trueuser'] = $trueuser;
       }
       $commentModel = new CommentModel();
       $data = $commentModel->comeentList($where, $p);
       $result = $data['res'];
       foreach ($result as $key =>$row) {
           $result[$key]['addtime'] = Date("Y-m-d G:i:s",$row['addtime']);
       }
       $Page = $data['Page'];
       $count = $data['count'];
       $first=$Page->firstRow+1;
       $rest=($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
       $end = $first + $rest-1;
       $all=$first.'-'.$end;
       $this->assign("count",$count);
       $this->assign('page',$Page->show());
       $this->assign('nowPage',$p);
       $this->assign('totalPages',$Page->totalPages);
//       dump($result);
       $this->assign("result",$result);
       $this->assign("all", $all);
       $this->assign('starttime', $starttime);
       $this->assign('endtime', $endtime);
       $this->assign('isshow', $isshow);
       $this->assign('commentlevel', $commentlevel);
       $this->assign('guanjiaid', $guanjiaid);
       $this->assign('value', $value);
       $this->assign('trueuser', $trueuser);
       $this->assign('type', $type);
       $this->assign('guanjiainfo', $guanjiaInfo);
       $this->display();
   }

 /*获取选择产品*/
   public function getSerachProduct()
   {
       $value = I('get.key',0);
       if (preg_match("/^\d*$/",$value)) {
           $where['id'] = $value;
       } else {
           $where['name'] = ['like','%'.$value.'%'];
       }
       $where['type'] = 1;
       $Product=new GoodsModel();
       $res=$Product->getProductIdAndName($where);
       if(count($res))
       {
           $data = [];
           foreach ($res as $key => $row) {
               $data[$key]['id'] = $row['id'];
               $data[$key]['text'] = $row['name'].'('.$row['id'].')';
           }
           echo json_encode($data);
       } else {
           echo json_encode([]);
       }
   }

     /*获取已有用户*/
     public function getSearchUser(){
         $value = I('get.key',0);
         if (preg_match("/^\d*$/",$value)) {
             $condition['id'] = $value;
         } else {
             $condition['name'] = ['like','%'.$value.'%'];
         }
         $user=new Comment();
         $res=$user->getAllVUser($condition);
         if(count($res))
         {
             $data = [];
             foreach ($res as $key => $row) {
                 $data[$key]['id'] = $row['id'];
                 $data[$key]['text'] = $row['name'].'('.$row['id'].')';
             }
             echo json_encode($data);
         } else {
             echo json_encode([]);
         }
     }



   public function getGoods()
   {
       $id = I('post.productid', 0);
       if (!$id) response('id异常');
       $goodsModel = new GoodsModel();
       $where['productid'] = $id;
       $res = $goodsModel->getServiceGoods($where);
       response('获取成功', 1, $res);
   }

   public function getSpec()
   {
        $goodid = I('post.goodid', 0);
       if (!$goodid) response('id异常');
       $where['goodsid'] = $goodid;
       $goodsModel = new GoodsModel();
       $res = $goodsModel->getSpec($where);
       response('获取成功', 1, $res);
   }

    public function addOneComment() {
        $productid = I('post.productid', 0);
        $goodsid = I('post.goodsid', 0);
        $specid = I('post.specid', 0);
        $commentlevel = I('post.commentlevel',3);
        $context = I('post.context','');
        $addtime = I('post.addtime', '');           //评价时间
        $isnewuser = I('post.isnewuser', 0);            //0 选择已有用户  //1新建用户
        $vuserid = I('post.vuserid', 0);
        $name = '';
        $avatar = '';
        if ($isnewuser==1) {
            $avatar = I('post.avatar', '');
            $name = I('post.name', '');
        }
        $params = [];
        $params['productid'] = $productid;
        $params["phone"]=0;
        $params["orderid"]=0;
        $params["jdaccount"]='';
        $params["avatar"]=$avatar;
        $params["name"]=$name;
        $params["isnewuser"]=$isnewuser;
        $params["goodsid"]=$goodsid;
        $params["context"]=$context;
        $params["commentlevel"]=$commentlevel;
        $params["specid"] = $specid;
        $params["noname"]=0;
        $params['addtime'] = $addtime;
        $params['vuserid'] = $vuserid;
        $params['trueuser'] = 0;
        $comment = new Comment();
        $result = $comment->addOneCommentByUser($params);
        $result ? response('添加成功',1) : response('添加失败');

    }

    public function hideOneComment()
    {
        $id = I('post.commentid',0);
        $isshow = I('post.isshow',1);
        $commnet = new Comment();
        $res = $commnet->hideOneComment($id,$isshow);
        $res ? response('修改成功',1):response('修改失败');
    }
}