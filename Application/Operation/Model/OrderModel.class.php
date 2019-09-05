<?php
/**
 * Created by PhpStorm.
 * User: xiyou
 * Date: 2017/11/13
 * Time: 15:30
 * 订单相关的数据表操作
 *  *  */

namespace Operation\Model;


use Server\Card;
use Server\Order;
use Server\Coin;

class OrderModel
{
    public function getCount($where)
    {
        $orderModel = M("order_info as o");
        $oredrCount = $orderModel->join('__ORDER_GOOD__ as og ON o.id=og.orderid')->where($where)->count();
        return $oredrCount;
    }


    public function orderList($p, $where, $isexcel = false)
    {
        $count = $this->getCount($where);
        $Page = new \Think\Page($count, 20);
        $Page->nowPage = $p;
        $orderModel = M("order_info as o");
        if ($isexcel) {
            $res = $orderModel
                ->join('__ORDER_GOOD__ as og ON o.id=og.orderid')
                ->join('__PAYRECORD__ as p ON o.ordersn = p.ordersn and p.status=1','left')
                ->join('__COUPON__ as c ON o.couponid = c.id','left')
                ->field('o.id,o.ordersn,og.productname,og.productid,og.goodname,og.goodid,og.specname,og.specid,og.num,o.totalprice,o.status,og.type,o.username,o.addressname,o.mobile,o.guanjianame,o.guanjiaphone,og.producttype,o.bdname,o.bdphone,o.paystyle,o.paytime,p.payrecordsn,o.couponid,c.couponname,o.couponmoney,o.coin,o.coinprice,o.addtime,o.cardinfo,o.isexpress,o.orderinfo,o.jdkerperorder,o.settles,o.settletype,og.sku_remark')
                ->where($where)
                ->order('o.id desc')
                ->select();
        } else {
            $res = $orderModel->join('__ORDER_GOOD__ as og ON o.id=og.orderid')
                ->field('o.id,o.ordersn,og.productname,og.type,og.num,og.goodname,o.totalprice,og.type,o.userid,o.username,o.addressname,o.mobile,o.addtime,o.guanjianame,o.guanjiaphone,o.bdname,o.province,o.city,o.district,o.address,o.addressname,o.mobile,o.bdname,o.status,o.paystatus,o.bdphone,o.shippingstatus,og.specname,og.productid,og.paystyle,o.handletype,o.guanjiaid,o.addtime,o.settletype,o.cardinfo,o.isexpress,o.jdkerperorder,og.sku_remark')
                ->where($where)
                ->order('o.id desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        }
        $orderClass = new \Server\Order();
        $res = $orderClass->orderHandelStatus($res);
        $settletype = [
           0=>'',
           1=>'未结算',
           2=>'不结算',
           3=>'结算中',
            4=>'已结算'
        ];
        foreach ($res as $key =>$row) {
            $res[$key]['totalprice'] = floatval($row['totalprice']);
            if (!$isexcel) {
                $res[$key]['settlenum'] = $row['settletype'];
                $res[$key]['settletype'] = $settletype[$row['settletype']];
            }
        }
        $data['res'] = $res;
        $data['Page'] = $Page;
        $data['count'] = $count;
        return $data;

    }

    public  function  desc($orderid = '')
    {
        $where['orderid'] = $orderid;
       $descModel = M("orderdesc");
       $res = $descModel->where($where)->select();
       return $res;

    }

    /**
     * @breif 获取订单状态
     * @param string $ordersn
     * @param string $orderid
     * @return bool|mixed
     */
    public function getOrderInfo($ordersn = '', $orderid = '')
    {
        if (!$ordersn && !$orderid) return false;
        $where = [];
        if ($ordersn) {
            $where['o.ordersn'] = $ordersn;
        }
        if ($orderid) {
            $where['o.id'] = $orderid;
        }
        $orderModel = M('order_info as o');
        $result = $orderModel
            ->field('o.id,o.addressname,o.userid,o.username,o.userphone,o.province,o.city,o.district,o.bdid,o.bdphone,o.guanjiaid,o.guanjiaphone,o.guanjianame,o.openid,o.addtime,o.totalprice,o.mobile,o.address,o.paystatus,o.status,o.shippingstatus,o.jdaccount,og.type,og.num,og.productid,og.goodid,og.specid,og.productname,og.goodname,og.servicetype,og.specname,o.totalprice,og.code,o.payrecordsn,og.producttype,o.orderinfo,o.ordersn,o.servicetime,o.payrealprice,o.coin,o.coinprice,o.cardinfo,og.productname,og.productpic,o.supplierid,o.isexpress,o.jdkerperorder,og.skuid,o.kerperaddressid,o.cardtag')
            ->join("__ORDER_GOOD__ as og ON o.id = og.orderid")
            ->where($where)
            ->limit(1)
            ->find();
        return $result;
    }

    /**
     * @breif 添加订单记录
     * @param $orderid
     * @param $ordersn
     * @param $status
     * @param string $jdaccount
     * @param string $staffid
     * @param string $extra
     * @param string $type
     * @param int $isrefund
     * @return mixed
     */
    public function addOrderRecord($orderid, $ordersn, $status, $jdaccount = '', $staffid = '', $extra = '', $type = '', $isrefund = 0)
    {
        $data['orderid'] = $orderid;
        $data['ordersn'] = $ordersn;
        $data['status'] = $status;
        $data['jdaccount'] = $jdaccount;
        $data['staffid'] = $staffid;
        $data['extra'] = $extra;
        $data['type'] = $type;
        $data['isrefund'] = 0;
        $data['addtime'] = time();
        $data['isrefund'] =$isrefund;
        $model = M('orderrecord');
        return $model->data($data)->add();
    }


    /**
     * @breif 改变订单状态
     * @param $ordersn
     * @param string $status
     * @param string $paystatus
     * @param string $shippingstatus
     * @param string $expressno
     * @return bool
     */
    public function changeOrderStatus($ordersn, $status = '', $paystatus = '', $shippingstatus = '', $expressno = '', $isrefund = 0,$payno = '', $paystyle = 0,$bookingtype = 0,$refundreason = '', $refundinfo = '')
    {
        if (!$paystatus && !$status && !$shippingstatus) return false;
        $where['ordersn'] = $ordersn;
        if ($status) $data['status'] = $status;
        if ($paystatus) {
            $data['paystatus'] = $paystatus;
            $data['settletype'] = 1;          //添加结算状态
        }
        if ($shippingstatus) $data['shippingstatus'] = $shippingstatus;
        if ($expressno) $data['expressno'] = $expressno;
        if ($status == 1000 || $status == 2001) $data['paytime'] = time();
        if ($status == 1001 || $status == 1002) $data['confirmtime'] = time();
        if ($status == 1901) $data['settletype'] = 0;
        if ($status == 1902) $data['finishtime'] = time();
        if ($status == 1900 || $status == 1901) {
            $orderinfo = $this->getOrderInfo($ordersn);
            //判断礼品
            $cardinfo = $orderinfo['cardinfo'];
            if ($cardinfo) {
                $cardinfo = json_decode($cardinfo, true);
                $id = $cardinfo['id'];
                $cardClass = new Card();
                $cardinfos = M('card')->where(['id'=>$id])->limit(1)->find();
                $cardtype = $cardinfos['type'];
                if ($cardtype == 1) {
                    $name = $cardinfo['name']?$cardinfo['name']: '';
                    $cardprice = $cardinfo['cardprice'];
                    $cardClass = new Card();
                    $res = $cardClass->backCard($status==1900?2:1,$orderinfo['jdaccount'],$name,$id,$cardprice,$ordersn);
                    if (!$res) return false;
                } else {
                    $exchangelist = $cardinfos['exchangelist'];
                    $exchangelist = json_decode($exchangelist, true);
                    $cardtag = $orderinfo['cardtag'];
                    $productid = $orderinfo['productid'];
                    $goodid = $orderinfo['goodid'];
                    $specid = $orderinfo['specid'];
                    $num = $orderinfo['num'];
                    $selected = $goodid.'-'.$specid;
                    foreach ($exchangelist as $key =>$row) {
                        if ($row['id'] == $cardtag &&$row['productid'] == $productid && in_array($selected,$row['selects'])) {
                            $exchangelist[$key]['restnum'] = $row['restnum'] + $num;
                        }
                    }
                    $temp1 =$cardClass->addExchageLog($orderinfo['jdaccount'],$ordersn,$cardtag,$id,$productid,$goodid,$specid,$num,2);
                    $temp2 = $cardClass->saveExchage($id,$exchangelist,0);
                    if (!($temp1 && $temp2)) return false;
                }

            }
            // 判断东家银子
            if ($orderinfo['coin'] > 0) {
                // 服务类取消或者退款
                $coin = new Coin();
                $result = $coin->backcoin($orderinfo['jdaccount'], $orderinfo['coin'], $ordersn);
                if (!$result) return false;
            }
        }

        if ($bookingtype == 2 && $status == 1001) {
            $data['paytime'] = time();
            $data['confirmtime'] = time();
            $data['handletype'] = 1;
        }
        if ($status == 1000) {
            $data['handletype'] = 1;
        }
        if ($status == 1002 || $status == 1003) {
            $data['refundreason'] = $refundreason;
            $data['refundinfo'] = $refundinfo;
        }
        if ($status == 2002) $data['shippingtime'] = time();
        if ($isrefund > 0) $data['isrefund'] = $isrefund;
        if ($payno) $data['payno'] = $payno;
        if ($paystyle) $data['paystyle'] = $paystyle;
        $orderModel = M('order_info');
        return $orderModel->where($where)->save($data);
    }

    /**
     * @breif 用户已下单数量（单用户限购）
     * @param $jdaccount
     * @param $productid
     * @param $goodisid
     * @param $specid
     * @param $type
     */
    public function getOrderNum($jdaccount,$productid, $goodisid, $specid = '', $type =1)
    {
        $goodsModel = M("order_info as o");
        $where['o.jdaccount'] = $jdaccount;
        $where['og.productid'] = $productid;
        $where['og.goodid'] = $goodisid;
        $where['og.specid'] = $specid;
        if ($type == 1) {
            $where['o.status'] = ['in',[0,1000,1001,1902]];
        } else {
            $where['o.status'] = ['in',[2000,2001,2002,2003,2902]];
        }
        $res = $goodsModel
            ->field('sum(og.num) as totalnums')
            ->join("__ORDER_GOOD__ as og ON o.id = og.orderid")
            ->where($where)
            ->select();
        $count = $res[0]['totalnums'];
        if (!$count) $count=0;
        return $count;

    }

    public function getArrayOrderNum($ids = [], $jdaccount, $type)
    {
        if (!count($ids)) return false;
        $where['o.jdaccount'] = $jdaccount;
        $goodsModel = M("order_info as o");
        if ($type == 1) {
            $where['og.specid'] = ['in', $ids];
            $where['o.status'] = ['in',[0,1000,1001,1902]];
            $res = $goodsModel
                ->join("__ORDER_GOOD__ as og ON o.id = og.orderid")
                ->field('og.specid,count(og.specid) as total')
                ->where($where)
                ->group('og.specid')
                ->select();
        } elseif ($type == 2) {
            $where['og.goodid'] = ['in', $ids];
            $where['o.status'] = ['in',[2000,2001,2002,2003,2902]];
            $res = $goodsModel
                ->join("__ORDER_GOOD__ as og ON o.id = og.orderid")
                ->field('*')
                ->where($where)
                ->group('og.goodid')
                ->select();
        }
        return $res;


    }

    /**
     * @breif 生成服务码
     * @param $orderid
     * @param $code
     * @return bool
     */
    public function addServiceCode($orderid, $code)
    {
        $where['orderid'] = $orderid;
        $orderModel = M('order_good');
        $saveData['code'] = $code;
        return $orderModel->where($where)->save($saveData);
    }

    public function getWeChatOrderList($jdaccount, $page = 1)
    {
        $where['o.jdaccount'] = $jdaccount;
        $where['o.isdelete'] = 0;
        $orderModel = M('order_info as o');
        $res = $orderModel
            ->join("__ORDER_GOOD__ as og ON o.id = og.orderid")
            ->join('__GUANJIA__ as g ON o.guanjiaid = g.id')
            ->join('__SUPPLIER__ as s ON o.supplierid = s.id','left')
            ->field('o.id,o.ordersn,og.productpic,og.productname,og.goodname,specname, o.totalprice,og.paystyle,og.type,o.status,og.num,s.suppliershort,s.customertype,s.customerphone,o.ishomeservice,o.isexpress,og.specid,o.jdkerperorder')
            ->where($where)
            ->page($page)
            ->order('o.id desc')
            ->limit(15)
            ->select();
        return $res;

    }

    public function getWeChatOrderDetail($orderid,$ordersn, $jdaccount)
    {
        if ($orderid) {
            $where['o.id'] = $orderid;
        } else {
            $where['o.ordersn'] = $ordersn;
        }
        $where['o.jdaccount'] = $jdaccount;
        $orderModel = M('order_info as o');
        $res = $orderModel
            ->join('__ORDER_GOOD__ as og ON o.id = og.orderid')
            ->join('__GUANJIA__ as g ON o.guanjiaid = g.id')
            ->join('__SUPPLIER__ as s ON o.supplierid = s.id','left')
            ->field('o.ordersn,o.userid,og.productname,og.productpic,og.productid,og.code,og.goodname,og.specname,og.num,og.price,o.totalprice,o.paystyle,og.paystyle as paytype,og.type,o.addressname,o.mobile,o.ordersn,o.addtime,o.paytime,o.status,o.address,og.servicetype,s.suppliershort,s.customertype,s.customerphone,o.guanjiaid,o.orderinfo,o.servicetime,o.payrealprice,o.couponmoney,o.coin,o.payrealprice,o.couponid,o.coinprice,o.cardinfo,o.ishomeservice,o.isexpress,og.specid,o.jdkerperorder')
            ->where($where)
            ->limit(1)
            ->find();
        return $res;
    }

    public function getAllOrderCount($jdaccount)
    {
        $where['jdaccount'] = $jdaccount;
        $where['isdelete'] = 0;
        $orderModel = M('order_info');
        return $orderModel->where($where)->count();
    }

    public function  orderrecord($orderid = ''){
        $where['orderid'] = $orderid;
        $recordModel = M("orderrecord");
        $res = $recordModel->field('orderrecord.addtime,orderrecord.status,orderrecord.staffid,user.name')->join('user on orderrecord.userid=user.id')->where($where)->select();
        return $res;
    }

    public function userDelOrder($jdaccount, $ordersn)
    {
        $where['jdaccount'] = $jdaccount;
        $where['ordersn'] = $ordersn;
        $orderModel = M('order_info');
        return $orderModel->where($where)->save(['isdelete'=>1]);
    }

    public function delOrder($ordersn = '', $orderid = '')
    {
        $where = [];
        if (!$ordersn && !$orderid) return false;
        if ($ordersn) $where['ordersn'] = $ordersn;
        if ($orderid) $where['orderid'] = $orderid;
        $orderModel = M('order_info');
        $data['isdelete'] = 1;
        return $orderModel->where($where)->save($data);
    }

    public function orderDetails($orderid = '')
    {
        $data['descinfo']=$this->desc($orderid);
        $detail =  $this->getOrderDetail($orderid);
        $servicetime = $detail['servicetime'];
        if ($servicetime) {
            $servicetime = Date('Y m月d日 '.getWeekByDate($servicetime).' G:i',strtotime($servicetime));
            $detail['servicetime'] = $servicetime;
        }
        $settletype = [
            0=>'',
            1=>'未结算',
            2=>'不结算',
            3=>'结算中',
            4=>'已结算'
        ];
        $detail['settlenum'] = $detail['settletype'];
        $detail['settletype'] = $settletype[$detail['settletype']];
        $data['orderinfo'] = $detail;

        return $data;
    }

    public function getOrderRecord($orderid)
    {
        $where['orderid'] = $orderid;
        $recrodModel = M('orderrecord');
        $res = $recrodModel->where($where)->select();
        return $res;
    }

    public function getOrderDetail($orderid)
    {
        $orderModel = M('order_info as o');
        $where['o.id'] = $orderid;
        $res = $orderModel
            ->join("order_good as og ON o.id = og.orderid")
            ->join('payrecord as p ON o.ordersn = p.ordersn','left')
            ->join('__COUPON__ as c ON o.couponid = c.id','left')
            ->field('o.ordersn,o.status,og.productid,og.productname,og.producttype,og.goodname,og.specname,og.type,og.price,og.num,o.totalprice,o.addressname,o.mobile,o.province,o.city,o.district,o.address,o.userid,o.username,o.userphone,o.bdname,o.bdphone,o.guanjianame,o.guanjiaphone,o.paytime,o.paystyle,og.paystyle as paytype,o.payno,p.payrecordsn,o.orderinfo,o.servicetime,o.payrealprice,o.coin,o.coinprice,o.couponmoney,o.couponid,c.couponname,o.settletype,o.refundreason,o.refundinfo,o.cardinfo,o.ishomeservice,o.expressno,o.isexpress,o.settles,o.jdkerperorder,og.sku_remark')
            ->where($where)
            ->limit(1)
            ->find();
        return $res;
    }

    public  function addDesc($data=''){
        $descModel = M('orderdesc');
        $res=$descModel->data($data)->add();
        return $res;
    }

    //获取已经下单的数量
    public function getOrderCount($specid, $orderuserid)
    {
        $where['specid'] = $specid;
        $where['orderuserid'] = $orderuserid;
        $orderModel = M("order");
        $num = $orderModel->where($where)->count();
        return $num;
    }





    public function saveOrderRecord($ordersn, $savedata)
    {
        $where['ordersn'] = $ordersn;
        $model = M('orderrecord');
        return $model->where($where)->save($savedata);

    }

    public function addOrderGood($data)
    {
        $orderGoodModel = M('order_good');
        return $orderGoodModel->data($data)->add();
    }

    public function addOrder($data)
    {
        $order = M('order_info');
        return $order->data($data)->add();
    }

    public function getOneOrder($ordersn = '')
    {
        $where['ordersn'] = $ordersn;
        $model = M('order_info');
        return $model->where($where)->limit(1)->find();
    }

    public function saveOrder($ordersn, $param)
    {
        $where['ordersn'] = $ordersn;
        $model = M('order_info');
        return $model->where($where)->save($param);
    }

    /**
     * @brief 用于微信端列表
     * @param $userid
     * @param $page
     * @param $limit
     */
    public function getOrderList($userid, $page = 1, $limit, $status)
    {
        $where['orderuserid'] = $userid;
        $where['isdelete'] = 0;
        if ($status) $where['orderstatus'] = $status;
        $model = M('order');
        $model->where($where)->order('id desc')->page($page)->select();
    }

    //支付流水
    public function addPayRecord($paysn, $ordersn, $paynum, $status, $desc, $paystyle = 1)
    {
        $model = M('payrecord');
        $data['payrecordsn'] = $paysn;
        $data['ordersn'] = $ordersn;
        $data['paystyle'] = $paystyle;
        $data['paynum'] = $paynum;
        $data['paytime'] = time();
        $data['status'] = $status;
        $data['desc'] = $desc;
        return $model->data($data)->add();
    }

    /**
     * @breif 根据productid统计订单量
     * @param $where
     * @return mixed
     */
    public function staticsOrdersByProductids($where)
    {
        $orderModel = M('order_info as o');
        $staticsOrder = $orderModel
            ->join('order_good as g ON o.id=g.orderid')
            ->field('g.productid,count(g.productid) as total')
            ->where($where)
            ->group('g.productid')
            ->select();
        return $staticsOrder;
    }

    /**
     * @breif 根据productid统计
     * @param $where
     */
    public function staticsOrdersUsersByProductids($where)
    {
        $orderModel = M('order_info as o');
        $subQuery = $orderModel
            ->join('order_good as g ON o.id=g.orderid')
            ->field('g.productid')
            ->where($where)
            ->group('g.productid,o.jdaccount')
            ->buildSql();
        $staticsOrder = M()->table($subQuery.' s')
                ->field('s.productid,count(s.productid) as total')
                ->group('s.productid')
                ->select();
        return $staticsOrder;
    }

    public function getEcommerceTrackInfo($ordersn = [])
    {
        if (!count($ordersn)) return false;
        $where['o.ordersn'] = ['in', $ordersn];
        $where['o.paystatus'] = 1;
        $model = M('order_info as o');
        $res = $model->join('order_good as og ON o.id=og.orderid')
            ->field('o.ordersn as id,o.guanjianame as affiliation,o.totalprice as revenue,og.productname as name,og.productid as sku,og.producttype as category,og.price,og.num as quantity')
            ->where($where)
            ->select();
        return $res;
    }

    public function getAllGuanjiaOrders($guanjiaid = 0)
    {
        $model = M('order_info');
        $where['guanjiaid'] = $guanjiaid;
        $count = $model->where($where)->count();
        if ($count) return intval($count)*3;
        return 0;
    }

    /*
 * 获取订单推送
 * */
    public function getOrderPushInfo($ordersn)
    {
//        echo "getOrderPushInfo/n";
//
//        echo"<br/>";


        $model = M('order_info');

        $where["ordersn"]=$ordersn;

//        echo "ordersn is:".$ordersn;
//
//        echo"<br/>";

        $where["status"]=0;

        $where["isdelete"]=0;

        $res=$model->where($where)->limit(1)->find();

//        echo json_encode($res);
//
//        echo"<br/>";

        if($res)
        {
            $addtime=$res["addtime"];

            $map=array();

            $map["jdaccount"]=$res["jdaccount"];

            $map["addtime"]=array(array('gt',$addtime-30*60),array('lt',$addtime));

            $map["status"]=0;

            $map["isdelete"]=0;

            $rst=$model->where($map)->limit(1)->find();

//           echo json_encode($rst);
//
//           echo "<br/>";

            if($rst)
            {
                //有 判断具体产品
//               echo "check order detail";
//
//               echo"<br/>";


                $goodModle=M("order_good");

                $where["orderid"]=$rst["id"];

                $whereN["orderid"]=$res["id"];

                $rstG=$goodModle->where($where)->limit(1)->find();

                $rstN=$goodModle->where($whereN)->limit(1)->find();

                if(($rstG["productid"]===$rstN["productid"])&&($rstG["goodid"]===$rstN["goodid"])&&($rstG["specid"]===$rstN["specid"]))
                {
                    return false;
                }
                else
                {
                    return $res;
                }
            }
            else
            {
                //直接push

//               echo "directly push";
//
//               return $res;
            }

        }
        else
        {
            return false;
        }
    }


    public function handleOrder($ordersn)
    {
        $where['ordersn'] = $ordersn;
        $res = M('order_info')->where($where)->setField('handletype',2);
        return $res;
    }

    public function addChildOrder($alldata)
    {
        $nums = M('order_child')->addAll($alldata);
        return $nums;
    }

    public function getOrderChild($ordersn)
    {
        $where['ordersn'] = $ordersn;
        return M('order_child')->where($where)->select();
    }

    public function changeCordersn($cordersn, $status)
    {
        $where['cordersn'] = $cordersn;
        $data['status'] = $status;
        if ($status == 2000) {
            $data['finishtime'] = time();
        }
        return M('order_child')->where($where)->save($data);
    }

    public function getChildOrderInfo($cordersn)
    {
        $where['cordersn'] = $cordersn;
        return M('order_child')->where($where)->limit(1)->find();
    }

    public function getMainOrderNeedComment($jdaccount,$lasttime, $nowtime)
    {
        $where['finishtime'] = [['gt',$lasttime],['elt',$nowtime]];
        $where['iscomment'] = 0;
        $where['status'] = 1902;
        $where['jdaccount'] = $jdaccount;
        $res = M('order_info')->where($where)->order('finishtime desc')->limit(1)->find();
        return $res;
    }

    public function getChildOrderNeedComment($jdaccount,$lasttime, $nowtime)
    {
        $where['finishtime'] = [['gt',$lasttime],['elt',$nowtime]];
        $where['iscomment'] = 0;
        $where['status'] = 2000;
        $where['jdaccount'] = $jdaccount;
        $res = M('order_child')->where($where)->order('finishtime desc')->limit(1)->find();
        return $res;
    }

    /**
     * @param $ordersn
     */
    public function getChildOrderStaffData($ordersn)
    {
        $staffdata = '';
        $orderchildinfo = $this->getOrderChild($ordersn);
        $weekday=[
            '周日',
            '周一',
            '周二',
            '周三',
            '周四',
            '周五',
            '周六'
        ];
//        var_dump($orderchildinfo);exit;
        foreach ($orderchildinfo as $key => $row) {
            $servicetimetemp = $row['servicetime'];
            $staffdata[$key]['time'] = '';
            if ($servicetimetemp) {
                $week = $weekday[Date('w',$servicetimetemp)];
                $staffdata[$key]['time']['servicetime'] = Date("Y-m-d G:i", $servicetimetemp);
                $staffdata[$key]['time']['week'] = $week;
            }
            $staffdata[$key]['staff'] = '';
            $staffdata[$key]['cordersn'] = $row['cordersn'];
            $staffids = $row['staff'];
            if ($staffids) {
                $staffids = trim($staffids,'-');
                $staffids = explode('-', $staffids);
                unset($where);
                $where['id'] = ['in', $staffids];
                $staffs = M('svr_waiter')->field('id,name,img,sex')->where($where)->select();
                foreach ($staffs as $key1 =>$row1) {
                    $staffs[$key1]['img'] = C('UPLOADURL').$row1['img'];
                }
                $staffdata[$key]['staff'] = $staffs;
            }
            $status = '';
            if ($row['status'] == 2000) {
                $status='已服务';
            } else {
                $status = '未服务';
            }
//            var_dump($row['cordersn']);exit;
            if ($row['iscomment']) $status = '已评价';
            $staffdata[$key]['status'] = $status;
            $staffdata[$key]['iscomment'] = $row['iscomment'];
        }
        return $staffdata;
    }

    public function saveCordersn($cordersn, $param)
    {
        $where['cordersn'] = $cordersn;
        $res = M('order_child')->where($where)->save($param);
        return $res;
    }

}
