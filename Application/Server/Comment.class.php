<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/11/2
 * Time: 17:52
 */

namespace Server;


use Operation\Model\CommentModel;
use Operation\Model\MessageModel;
use Operation\Model\OrderModel;
use WeChat\Model\GoodsModel;
use WeChat\Model\GuanJiaModel;
use WeChat\Model\WeChatUserModel;

class Comment
{

    //评论子订单
    public function addChildOrderComment($commentlevel,$context,$noname,$cordersn,$jdaccount, $staffids = null, $staffnames = null, $stafflevels = null)
    {
        if ($commentlevel<1 || $commentlevel >3) response('请选择正确的满意度');
        $orderModel = new OrderModel();
        $corderinfo = $orderModel->getChildOrderInfo($cordersn);
        $iscomment = $corderinfo['iscomment'];
        $ishomeservice = $corderinfo['ishomeservice'];
        if (!$ishomeservice) response('对不起,不能评价该服务');
        if ($iscomment) response('该服务已评价,不能重复评价');
        if((mb_strlen($context)<5)||(mb_strlen($context)>200))
        {
            response('最多收入200字');
        }
        if (!$jdaccount) response('账号异常');
        if (!$cordersn) response('参数错误');
        if ($staffids) {
            $tempstaffids = trim($staffids,'-');
            $staffid_arr = explode('-', $tempstaffids);
        }
        if ($stafflevels) {
            $tempstafflevels = trim($stafflevels, '-');
            $staffstar_arr = explode('-', $tempstafflevels);
        }
        if ($staffnames) {
            $tempstaffnames = trim($staffnames, '-');
            $staffname_arr = explode('-', $tempstaffnames);
        }
        $supplierid = $corderinfo['supplierid'];
        $ordersn = substr($cordersn,0,count($cordersn)-4);
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $model = M();
        $model->startTrans();
        for ($i = 0; $i < count($staffid_arr); $i ++) {
            if ($staffid_arr[$i]) {
                $comment_id[] = $this->addOneStaffComment($staffid_arr[$i], $staffname_arr[$i], $staffstar_arr[$i], session('jdaccount'), $cordersn, $supplierid);
            }
        }
        if (count($comment_id)) {
            $commentids = implode(',', $comment_id);
        } else {
            $commentids = '';
        }
        $userModel = new WeChatUserModel();
        $userinfo = $userModel->getUserInfoByJdaccount($jdaccount);
        $param['productid'] = $orderinfo['productid'];
        $param['productname'] = $orderinfo['productname'];
        $param['goodsid'] = $orderinfo['goodid'];
        $param['context'] = $context;
        $param['jdaccount'] = $jdaccount;
        $param['avatar'] = $userinfo['avatarurl']?$userinfo['avatarurl']:'/guanjia/ui2/noname@3x.png';
        $param['name'] = $userinfo['nickname']?$userinfo['nickname']:'未知';
        $param['addressname'] = $orderinfo['addressname'];
        $param['userid'] = $userinfo['id'];
        $param['commentlevel'] = $commentlevel;
        $param['specname'] = $orderinfo['specname'];
        $param['specid'] = $orderinfo['specid'];
        $param['goodname'] = $orderinfo['goodname'];
        $param['trueuser'] = 1;
        $param['isdelete'] = 0;
        $param['noname'] = $noname;
        $param['creattime'] = time();
        $param['addtime'] = time();
        $param['orderid'] = $cordersn;
        $param['guanjiaid'] = $orderinfo['guanjiaid'];
        $param['phone'] = $orderinfo['userphone'];
        $param['isshow'] = 1;
        $param['categoryname'] = $orderinfo['producttype'];
        $param['ismainorder'] = 0;
        $param['commentids'] = $commentids;
        $param['staffids'] = $staffids;
        $param['staffnames'] = $staffnames;
        $param['stafflevels'] = $stafflevels;
        $param['supplierid'] = $supplierid;
        $Comment=M("comment");
        $res1 =$Comment->add($param);
        $data['iscomment'] = 1;
        $res2 =$orderModel->saveCordersn($cordersn, $data);
        if ($res1 && $res2) {
            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;
        }
    }


    //评论主订单
    public function addOneWeChatComment($commentlevel,$context,$noname,$ordersn,$jdaccount)
    {
        // 如果有服务人员评价，commentids 为staffcomment主键(格式1,2)，staffids为服务人员id(-1-2-)，stafflevels为服务人员评级(-3-5-)
        if ($commentlevel<1 || $commentlevel >3) response('请选择正确的满意度');
        if((mb_strlen($context)<5)||(mb_strlen($context)>200))
        {
            response('最多收入200字');
        }
        if (!$jdaccount) response('账号异常');
        if (!$ordersn) response('参数错误');

        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $userModel = new WeChatUserModel();
        $userinfo = $userModel->getUserInfoByJdaccount($jdaccount);
        $goodstype = M('goods')->where(['id'=>$orderinfo['goodid']])->limit(1)->getField('type');
        if ($goodstype != '3') { // 不是到家服务
            if (!$this->checkIfCommented($ordersn))
                response('该订单已评价');
        }
        $param['productid'] = $orderinfo['productid'];
        $param['productname'] = $orderinfo['productname'];
        $param['goodsid'] = $orderinfo['goodid'];
        $param['context'] = $context;
        $param['jdaccount'] = $jdaccount;
        $param['avatar'] = $userinfo['avatarurl']?$userinfo['avatarurl']:'https://file.rose52.com/guanjia/ui2/noname@3x.png';
        $param['name'] = $userinfo['nickname']?$userinfo['nickname']:'未知';
        $param['addressname'] = $orderinfo['addressname'];
        $param['userid'] = $userinfo['id'];
        $param['commentlevel'] = $commentlevel;
        $param['specname'] = $orderinfo['specname'];
        $param['specid'] = $orderinfo['specid'];
        $param['goodname'] = $orderinfo['goodname'];
        $param['trueuser'] = 1;
        $param['isdelete'] = 0;
        $param['noname'] = $noname;
        $param['creattime'] = time();
        $param['addtime'] = time();
        $param['orderid'] = $ordersn;
        $param['guanjiaid'] = $orderinfo['guanjiaid'];
        $param['phone'] = $orderinfo['userphone'];
        $param['isshow'] = 1;
        $param['categoryname'] = $orderinfo['producttype'];
        $param['supplierid'] = $orderinfo['supplierid'];
        $Comment=M("comment");
        $model = M();
        $model->startTrans();
        $res1=$Comment->add($param);
        $data['iscomment'] = 1;
        $res2 =$orderModel->saveOrder($ordersn,$data);
        if ($res1 && $res2) {
            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;
        }
    }

    public function addOneStaffComment($staffid, $staffname, $star, $jdaccount, $corders, $supplierid)
    {
        $row = [
            'staffid' => $staffid,
            'staffname' => $staffname,
            'star' => $star,
            'addtime' => time(),
            'jdaccount' => $jdaccount,
            'cordersn' => $corders,
            'supplierid' => $supplierid,
        ];
        return M('staffcomment')->add($row);
    }




    public function EjSendWeChatComment($comment,$commenttext,$ordersn)
    {
        $data['jdorder'] = $ordersn;
        $data['comment'] = $comment;
        $data['commentcontext'] = $commenttext;
        $data = json_encode($data);
        $gjAes = new GJAES();
        $data = $gjAes->aes_encrypt($data,'woshinibaba');
        // todo e家清后台
        $res = http_post_data('http://117.48.208.220/index.php/AjaxApi/Api/commentTogether',json_encode(['data'=>$data]));
        if (isset($res['state'])) {
            return $res['state'];
        } else {
            return 0;
        }
    }
    /*添加一条评论*/

    public function addOneCommentByUser($param)
    {
        if((!$param["goodsid"])||(!$param["context"])||(!$param["commentlevel"])||(!$param['productid'])||(!$param['addtime']))
        {
            response('参数错误');
        }
        if ($param['commentlevel']<1|| $param['commentlevel'] >3) response('参数错误');
        $context=$param["context"];
        if((mb_strlen($context)<5)||(mb_strlen($context)>200))
        {
            response('参数错误');
        }
        if ($param['isnewuser'] == 1) {
            if (!$param['name']) response('请填写昵称');
            if (!$param['avatar']) response('请上传头像');
            $data['name'] = $param['name'];
            $data['avatar'] = $param['avatar'];
            $data['createtime'] = time();
            $vuserid = M('vuser')->data($data)->add();
            $param['userid'] = -intval($vuserid);
        } else {
            if (!$param['vuserid']) response('参数错误');
            $param['userid'] = -intval($param['vuserid']);
            $where['id'] = $param['vuserid'];
            $vuserinfo = M('vuser')->where($where)->limit(1)->find();
            $param['avatar'] = C('UPLOADURL').$vuserinfo['avatar'];
            $param['name'] = $vuserinfo['name'];
            unset($param['vuserid']);
        }
        $param['addtime'] = strtotime($param['addtime']);
        $goodsModel = new GoodsModel();
        $productinfo = $goodsModel->getOneProduct($param['productid']);
        $param['productname'] = $productinfo['name'];
        $param['categoryname'] = $productinfo['categoryname'];
        $param['guanjiaid'] = $productinfo['guanjiaid'];
        $param['goodname'] = M('goods')->where(['id'=>$param['goodsid']])->limit(1)->getField('name');
        $param['creattime'] = time();
        if ($param['specid']) {
            $param['specname'] = M('spec')->where(['id'=>$param['specid']])->limit(1)->getField('specname');
        }
        $Comment=M("comment");
        $res=$Comment->add($param);
        return $res;
    }

    /**
     * @breif 获取最新最好的评价
     */
    public function getRecentComment($productid = 0)
    {
        $commentModel = new CommentModel();
        $allComment = $commentModel->getCommentByProductid($productid);
        $total = count($allComment);
        $res = [];
        if ($total) {
            $res = $allComment[0];
            if (!$res['noname'] && !$res['trueuser']) {
                $res['avatar'] = C('UPLOADURL').$res['avatar'];
            }
            $notmanyi = 0;
            foreach ($allComment as $key=>$row) {
                if ($row['commentlevel'] == 1) $notmanyi ++;
            }
            $manyipercent =sprintf('%.3f',($total-$notmanyi)/$total);
            $manyipercent = floatval($manyipercent*100);
            $res['total'] = $total;
            $res['manyipercent'] = $manyipercent;
        }
        return $res;

    }

    /**
     * @breif 微信端评价列表
     * @param $productid
     * @param $p
     * @return array
     */
    public function getWechatCommentList($productid, $p)
    {
        $commentModel = new CommentModel();
        $manyi = $this->getRecentComment($productid);
        if (count($manyi)) {
            $hasid = $manyi['id'];
            $where['productid'] = $productid;
            $where['id'] = ['neq', $hasid];
            $res = $commentModel->getWeChatCommentList($where, $p);
            if ($p ==1) {
                array_unshift($res,$manyi);
            }
            return $res;
        } else {
            return [];
        }

    }

    /*判断是否已经评论过*/

    public function checkIfCommented($orderid)
    {
        if(!$orderid)
        {
            return false;
        }

        $Comment=M("comment");

        $where=array();

        $where["orderid"]=$orderid;

        $where["isdelete"]=0;

        $res=$Comment->where($where)->limit(1)->find();


        if($res)
        {
            return false;
        }
        else
        {
            return true;
        }


    }

    /*获取推荐评论*/
    public function getRecommendComment($productid)
    {
        if(!$productid)
        {
            return false;
        }

        $Comment=M("comment");

        $where=array();

        $where["productid"]=$productid;

        $where["isdelete"]=0;

        $res=$Comment->where($where)->order('commentlevel asc,id desc')->select();

        if($res)
        {

            $data=array();

            $data["allCount"]=count($res);

            $data["info"]=$res[0];

            $data["level"]=$this->caculateLevel($res,$data["allCount"]);

            return $data;
        }
        else
        {
            return false;
        }



    }


    /*获取所有评论 分页api*/
    public function getAllComment($productid,$page,$commnetid)
    {

        if(!$productid)
        {
            return false;
        }

        if(!$page)
        {
            $page=0;
        }
        else
        {
            $page=intval($page);
        }

        $pageCondition=$page.",10";

        $Comment=M("comment");

        $where=array();

        $where["productid"]=$productid;

        $where["isdelete"]=0;

//        var_dump($where);
        $res=$Comment->where($where)->order('id desc')->page($pageCondition)->select();
//        var_dump($Comment->getLastSql());
//        var_dump($res);die;

        if($res)
        {

            $data=array();

            $data["allCount"]=count($res);

            if ($commnetid) {
                $data["info"]=$this->putOneCommentToTop($res,$commnetid);
            } else {
                $data['info'] = $res;
            }

            $data["level"]=$this->caculateLevel($res,$data["allCount"]);

            return $data;
        }
        else
        {
            return false;
        }
    }

    /*id 放第一个*/
    public function putOneCommentToTop($array,$commentid)
    {
        $count=count($array);

        $newArray=array();

        for($i=0;$i<$count;$i++)
        {
           if($array[$i]["id"]==$commentid)
           {
               $newArray[0]=$array[$i];
           }
           else
           {
               $newArray[$i+1]=$array[$i];
           }
        }

        return $newArray;

    }

    /*计算满意度*/
    public function caculateLevel($data,$count)
    {
       $good=0;

        for($i=0;$i<$count;$i++)
       {
           if($data[$i]>1)
           {
               $good=$good+1;
           }
       }

       return ($good/$count);
    }


    /*下面是西柚的接口*/
    /*添加一个虚拟用户*/
    public function addOneVUser($param)
    {
       if((!$param["name"])||(!$param["avatar"]))
       {
           return false;
       }
       else
       {
           $param["commetid"]="";

           $Vuser=M("vuser");

           $res=$Vuser->add($param);

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

    /***

     获取所有虚拟用户 搜索

     */

    public function getAllVUser($condition)
    {
        $Vuser=M("vuser");
        $userinfo=$Vuser->field('id,name')->where($condition)->select();
        return $userinfo;

//        $where=array();
//
//        if(!$condition)
//        {
//            $res=$Vuser->select();
//
//            return $res;
//        }
//        else
//        {
//            if($condition["id"])
//            {
//                $where["id"]=$condition["id"];
//
//                $rst=$Vuser->where($where)->limit(1)->find();
//
//                return $rst;
//
//            }
//            else if($condition["name"])
//            {
//                $name=$condition["name"];
//
//                $where["name"]=['like', "%$name%"];
//
//                $rst=$Vuser->where($where)->order('id desc')->select();
//
//                return $rst;
//
//            }
//            else
//            {
//                 return false;
//            }
//
//        }

    }

    /*添加一个虚拟评论*/
    public function addOneVUserComment($param)
    {
        if((!$param["productid"])||(!$param["guanjiaid"])||(!$param["addtime"])||(!$param["trueuser"])||(!$param["id"])||(!$param["avatar"])||(!$param["name"])||(!$param["goodid"])||(!$param["context"])||(!$param["jdaccount"])||(!$param["commentlevel"])||(!$param["specname"])||(!$param["goodname"])||(!$param["specid"])||(!$param["noname"]))
        {
            return false;
        }

        if($param["trueuser"]!==0)
        {
            return false;
        }

        $context=$param["context"];

        if((strlen($context)<5)||(strlen($context)>200))
        {
            return false;
        }


        $Vuser=M("vuser");

        $whereV=array();

        $whereV["id"]=$param["id"];

        $Comment=M("comment");

        $Comment->startTrans();

        $resC=$Comment->add($param);

        $resV=$Vuser->where($whereV)->limit(1)->find();

        $oldAsset=$resV["commnetid"];

        $newAsset="";

        if(strlen($oldAsset)>1)
        {
            $newAsset=";".$param["context"];
        }
        else
        {
            $newAsset=$param["context"];
        }

        $param["context"]=$newAsset;

        $resVA=$Vuser->where($whereV)->field('commentid')->save($param);

        if($resC&&$resV&&$resVA)
        {
            $Comment->commit();

            return true;
        }
        else
        {
            $Comment->rollback();

            return false;
        }


    }

    /*隐藏评价 上线评价*/
    public function hideOneComment($id,$isshow)
    {
        $Comment=M("comment");

        $where=array();

        $where["id"]=$id;

        $res=$Comment->where($where)->limit(1)->find();

        $data=array();

        if ($isshow ==1 && $isshow == $res['isshow']) {
            response('操作异常');
        } elseif ($isshow ==2 && $isshow == $res['isshow']) {
            response('操作异常');
        }
        $data['isshow'] = $isshow;
        $rst=$Comment->where($where)->save($data);
        return $rst;

    }

    /*所有评论展示

    2.筛选条件
    • 可通过评价时间（交互同订单列表的下单时间）进行筛选；
    • 可通过订单ID（精确匹配）、产品ID（精确匹配）、产品名称（模糊匹配）、填单手机号（精确匹配）、填单人（精确匹配）、用户ID（精确匹配）进行搜索；
    • 可通过所属管家、评价类型、状态进行筛选

    */
    public function operationGetAllComment($p,$condition)
    {
//        $page;

        if(!$p)
        {
            $page=0;
        }
        else
        {
            $page=$p;
        }

        $Comment=M("comment");

        $count=$Comment->count();

        $Page = new \Think\Page($count, 20);

        $data=array();

        $data["count"]=$count;

        if(!$condition)
        {

            $Page->nowPageage = $page;

            $res=$Comment->limit($Page->firstRow . ',' . $Page->listRows)->order('id desc')->select();

            $data['Page'] = $Page;

            $data['res'] = $res;

            return $data;

        }
        else
        {
            $where=array();

            if ((array_key_exists("startTime",$condition))&&(array_key_exists("endTime",$condition)))
            {
                $where["addtime"]=[['gt',strtotime($condition["startTime"])],['elt',strtotime($condition["endTime"])]];
            }
            else if((array_key_exists("startTime",$condition)))
            {

                $where["addtime"]=[['gt',strtotime($condition["startTime"])]];
            }
            elseif (array_key_exists("endTime",$condition))
            {
                $where['addtime']  = ['lt', strtotime($condition["endTime"])];
            }
            if(array_key_exists('orderId',$condition))
            {
                $where["orderid"]=$condition["orderId"];
            }
            if(array_key_exists('goodsId',$condition))
            {
                $where["goodsid"]=$condition["goodsId"];
            }
            if(array_key_exists('goodsName',$condition))
            {
                $goodname=$condition["goodsName"];

                $where["goodname"]=['like', "%$goodname%"];
            }
            if(array_key_exists("phone",$condition))
            {
                $where["phone"]=$condition["phone"];
            }
            if(array_key_exists("name",$condition))
            {
                $where["name"]=$condition["name"];
            }
            if(array_key_exists("jdaccount",$condition))
            {
                $where["jdaccount"]=$condition["jdaccount"];
            }
             if(array_key_exists("guanjiaid",$condition))
            {
                $where["guanjiaid"]=$condition["guanjiaid"];
            }
             if(array_key_exists("commentlevel",$condition))
            {
                $where["commentlevel"]=$condition["commentlevel"];
            }
             if(array_key_exists("isdelete",$condition))
            {
                $where["isdelete"]=$condition["isdelete"];
            }

            $Page->nowPageage = $page;

            $res=$Comment->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->order('id desc')->select();

            $data['Page'] = $Page;

            $data['res'] = $res;

            return $data;

        }







    }



}