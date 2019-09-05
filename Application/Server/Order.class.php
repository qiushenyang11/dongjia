<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/11/15
 * Time: 11:00
 */

namespace Server;


use NewApi\Model\AgencyModel;
use Operation\Model\AccountModel;
use Operation\Model\CategoryModel;
use Operation\Model\MessageModel;
use Operation\Model\OrderModel;
use WeChat\Model\GoodsModel;
use WeChat\Model\SupplierModel;
use WeChat\Model\WeChatUserModel;

class Order
{
    public function submitOrder($param = [], $userid)
    {

        $wechatRedis = new WeChatRedis();
        $userModel = new WeChatUserModel();
        $userinfo  =$userModel->getUserInfo($userid);
        $supplierModel = new SupplierModel();
        $cardinfo = '';
        //todo 考虑一个京东账号绑定过多个微信公众号
        $info = $this->verifySubmitOrder($param, $userinfo['openid'], $userinfo['jdaccount']);
        $supplierinfo = $supplierModel->getOneSupplier($info['supplierid']);
        if (!$wechatRedis->checkSubmitToken($param['token'])) response('该订单已提交,请刷新重试');
        $bdinfo = $userModel->getOneBD($info['bdid']);
        $guanjiainfo = $userModel->getOneGuanJia($info['guanjiaid']);
        $ordersn = createOrderSn();
        $productModel = new GoodsModel();
        $bookingtype = $productModel->getProductBooking($info['productid']);
        if ($info['cardid']) {
            $cardinfo = M('card')->where(['id'=>$info['cardid']])->limit(1)->find();
        }
        $staffid = $info['staffid'];
        $staffname = isset($param['staffname'])?$param['staffname']:'';
        $servicetime = $param['servicetime']?$param['servicetime']:'';
        $num = $param['nums'];
        $servicetype = $info['servicetype'];
        $childData = [];
        $j = 0;
        for ($i = 1; $i <= $num; $i++) {
            $childData[$j] = [
                'ordersn' =>$ordersn,
                'cordersn'=>$ordersn.str_pad($i,3,'0',STR_PAD_LEFT),
                'supplierid'=>$info['supplierid'],
                'ishomeservice'=>$servicetype == 3 ? 1:0,
                'addtime'=>time(),
            ];
            if ($j == 0) {                                      //如果选择时间和人员就存入第一个子订单中
                $childData[$j]['staff'] = $staffid ? '-'.$staffid.'-':'';
                $childData[$j]['staffname'] ='-'. $staffname.'-';
                $childData[$j]['servicetime']=strtotime($servicetime);
            } else {
                $childData[$j]['staff'] = '';
                $childData[$j]['staffname'] = '';
                $childData[$j]['servicetime']='';
            }
            $j++;
        }
        /***************添加order_info********************/
        $data['ordersn'] = $ordersn;
        $data['userid'] = $userid;
        $data['jdaccount'] = $userinfo['jdaccount'];
        $data['userphone'] = $userinfo['phone'];
        $data['username'] = $userinfo['nickname'];
        $data['openid'] = $userinfo['openid'];
        $data['shippingstatus'] = 0;
        $data['bdid'] = $bdinfo['id'];
        $data['bdname'] = $bdinfo['name'];
        $data['bdphone'] = $bdinfo['phone'];
        $data['guanjiaid'] = $guanjiainfo['guanjiaid'];
        $data['guanjianame'] = $guanjiainfo['guanjianame'];
        $data['guanjiaphone'] = $guanjiainfo['guanjiaphone'];
        $data['addtime'] = time();
        $data['addressname'] = $param['addressname'];
        $data['mobile'] = $param['mobile'];
        $data['totalprice'] = floatval($info['price']*$param['nums']);
        $data['address'] = $param['type'] == 1 ? '':$param['address'];
        $data['orderinfo'] = $param['orderinfo'];
        $data['servicetime'] = $param['servicetime']?$param['servicetime']:'';
        $data['couponid'] = $info['couponid'];
        $data['couponmoney'] = $info['couponprice'];
        $data['payrealprice'] = floatval($param['totalprice']);
        $data['coin'] = $info['coin'];
        $data['coinprice'] = $info['coinprice'];
        $data['supplierid'] = $info['supplierid'];
        $data['isselecttime'] = ($num == 1 && $servicetime) ? 1 : 0;
        $data['isselectstaff'] = ($num == 1 && $staffname) ? 1 : 0;
        $data['staffid'] = $staffid? '-'.$staffid.'-':'';
        $data['staffname'] = $staffname;
        $data['ishomeservice'] = $servicetype == 3 ? 1:0;
        $data['address'] = (isset($param['city']) && $param['city']) ? $param['city'] : '';
        $data['isselecttime'] = ($num == 1 && $servicetime) ? 1 : 0;
        $data['isselectstaff'] = ($num == 1 && $staffname) ? 1 : 0;
        $data['isexpress'] = $servicetype == 5 ? 1 : 0;
        $data['agencyid'] = $info['agencyid'];
        $data['settles'] = $info['settles'];
        $data['cardtag'] = $info['cardtag'];
        if ($info['cardid']) {
            $cardtemp['id'] = $cardinfo['id'];
            $cardtemp['cardid'] = $cardinfo['cardid'];
            $cardtemp['loopid'] = $cardinfo['loopid'];
            $cardtemp['cardprice'] = $info['cardprice'];
            $cardtemp['name'] = $cardinfo['name'];
            $data['cardinfo'] = json_encode($cardtemp);
        } else {
            $data['cardinfo'] = '';
        }
        if (isset($param['addresscode']) && $param['addresscode']) {
            $temp = explode(',',$param['addresscode']);
            $data['province'] = $temp[0];
            $data['city'] = $temp[1];
            $data['district'] = $temp[2];
            unset($temp);
        }
        //是否需要支付
        if ($param['totalprice'] >0) {
            $data['paystatus'] = 0;
            $data['status'] = $param['type'] == 1 ?0 :2000;
        } else {                         //不需要支付
            $data['paystatus'] = 1;
            $data['status'] = $param['type'] == 1 ?1000 :2001;
            $data['handletype'] = 1;
            if ($bookingtype == 2 && $param['type'] == 1) {
                $data['status'] = 1001;
            }
            if (intval($data['totalprice']*100) > 0) {     //不需要支付且收费
                $data['settletype'] = 1;
            }
        }

        $model = M();
        $model->startTrans();
        $orderModel = new OrderModel();
        $orderid = $orderModel->addOrder($data);
        /****************添加order_good*****************/
        $ordergoodData['orderid'] = $orderid;
        $ordergoodData['productid'] = $info['productid'];
        $ordergoodData['goodid'] = $info['goodid'];
        $ordergoodData['specid'] = $info['specid'];
        $ordergoodData['productname'] = $info['productname'];
        $ordergoodData['producttype'] = $info['producttype'];
        $ordergoodData['productpic'] = $info['productpic'];
        $ordergoodData['goodname'] = $info['type'] == 1 ?$info['goodname'] :'';
        $ordergoodData['goodpic'] = $info['goodpic'];
        $ordergoodData['specname'] =$info['type'] == 1 ? $info['specname']: $info['goodname'];
        $ordergoodData['servicetype'] = $info['type'] == 1 ? $info['servicetype'] : 0;    //0:快递类无该状态 1:服务商品类型(服务) 2:快递商品类型（咨询）
        $ordergoodData['paystyle'] = $info['type'] == 1?$info['paystyle']:1;
        $ordergoodData['price'] = $info['price'];
        $ordergoodData['num'] = $param['nums'];
        $ordergoodData['addtime'] = time();
        $ordergoodData['type'] = $param['type'];
        $ordergoodData['code'] = ($param['payrealprice'] == 0 && $param['type'] == 1 && $servicetype != 5) ? createServiceCode():'';
        $ordergoodData['skuid'] = $info['skuid'];

        $goodsModel = new GoodsModel();
        $specinfo   = $goodsModel->getOneSpec($info['specid']);
        $ordergoodData['sku_remark'] = $specinfo['remark'];   //sku备注
        $res1 = $orderModel->addOrderGood($ordergoodData);
        /********************添加记录*****************************/
        $res2 = $orderModel->addOrderRecord($orderid,$ordersn,$data['status'],$userinfo['jdaccount'],'','用户('.$userinfo['nickname'].'):订单创建',$param['type']);
        /***********************添加子订单*********************************/
        $res7 = $orderModel->addChildOrder($childData);
        /*********************安排服务人员************************/
        $res8 = true;

        /*********************减少库存**********************************/
        if ($info['type'] == 1) {
            $id = $info['specid'];
            $wechatRedis = new WeChatRedis();
            $wechatRedis->setUserOrderHistory($userid, $param['addressname'],$param['mobile']);
        } elseif ($info['type'] == 2) {
            $id = $info['specid'];
        } else {
            $id = 0;               //异常
        }
        $goodsModel = new GoodsModel();
        $res3 = $goodsModel->delSpecNum($id,$param['nums'],$info['type']);
        /*********************添加15分钟取消订单**********************************/
        $addstring =  json_encode(['id'=>$ordersn,'type'=>'cancelorder']);
        $crotabClass = new Crontab($addstring);
        $message = "订单:".$ordersn.",添加到15分钟取消队列";
        $time = time() + 15*60;
        $res4 = $crotabClass->addCancelOrder($time, $message);
        $res5 = true;
        $res6 = true;
        $res9 = true;
        $res10 = true;
        if ($info['couponid']) {
            $res5 = A("CouponApi/Coupon")->lockCoupon($info['couponid'],$info['ordertotalprice']);
        }
        $cardClass = new Card();
        if ($info['isexchange']) {      //兑换卡
            $newexchagelist = $info['newexchagelist'];
            $isuseall = 1;
            foreach ($newexchagelist as $row) {
                if ($row['restnum'] > 0){
                    $isuseall = 0;
                    break;
                }
            }
            $res9 = $cardClass->saveExchage($info['cardid'],$newexchagelist,$isuseall);
            $res10 = $cardClass->addExchageLog($userinfo['jdaccount'],$ordersn,$info['cardtag'],$info['cardid'],$info['productid'],$info['goodid'],$info['specid'],$param['nums']);

        } else {
            if ($info['cardid']) {
                $res6 = $cardClass->lockCard($userinfo['jdaccount'],$cardinfo['name'],$info['cardid'],$info['cardprice'],$ordersn);
            }
        }

        if ($info['coin']) {
            $coinClass = new Coin();
            $res5 = $coinClass->lockcoin($userinfo['jdaccount'],$info['coin'],$ordersn);
//            var_dump($res5);exit;
        }
        if ($orderid && $res1 && $res2 && $res3 && $res4 && $res5 && $res6 && $res7 && $res8 && $res9 && $res10) {
            if ($info['iskerper'] && C("ISONLINE")) {
                $kerperdata['thirdOrder'] = $ordersn;
                $kerperdata['sku'][] =[
                    'skuId'=>$info['kpl_sku'],
                    'num' => $num
                ];
                $orderinfos = $param['orderinfo'];
                $orderinfos = htmlspecialchars_decode($orderinfos);
                $orderinfos = json_decode($orderinfos, true);
                $kerperaddresdetail = '';
                $orderinfos = $orderinfos[0];
                foreach ($orderinfos as $row) {
                    if ($row['name'] == '地址' || $row['name'] == '') {
                        $kerperaddresdetail = trim($row['value']);
                        break;
                    }
                }
                $kerperdata['name'] = $data['addressname'];
                $kerperdata['province'] = $info['provinceid'];
                $kerperdata['city'] = $info['cityid'];
                $kerperdata['county'] = $info['countyid'];
                $kerperdata['town'] = $info['townid'];
                $kerperdata['address'] =$kerperaddresdetail;
                $kerperdata['mobile'] = $data['mobile'];
                $kerperdata['email'] = 'jie.li@dongrich.com';
                $kerperdata['invoiceState'] = 2;
                $kerperdata['invoiceType'] = 2;
                $kerperdata['selectedInvoiceTitle'] = 5;
                $kerperdata['companyName'] = '上海东戊信息科技有限公司';
                $kerperdata['invoiceContent'] = 1;
                $kerperdata['paymentType'] = 4;
                $kerperdata['isUseBalance'] = 1;
                $kerperdata['submitState'] = $param['totalprice'] == 0 ? 1 : 0;
                $kerperdata['invoiceName'] = '李杰';
                $kerperdata['invoicePhone'] = '18612450636';
                $kerperdata['invoiceProvice'] = 2;
                $kerperdata['invoiceCity'] = 78;
                $kerperdata['invoiceCounty'] = 51978;
                $kerperdata['invoiceAddress'] = '上海市黄浦区南京西路明天广场9楼';
                $kerperClass =new KerperApi();
                $submitinfo = $kerperClass->submitKerperOrder($kerperdata);
                if ($submitinfo === 0) {            //如果超时再试一次
                    $submitinfo = $kerperClass->submitKerperOrder($kerperdata);
                }
                if (!$submitinfo) {
                    $model->rollback();
                    response('系统繁忙，请稍后重试');
                }
                $model->commit();
                $jdorder = $submitinfo['jdOrderId'];
                $kerperaddressid = $info['kerperaddressid'];
                $orderModel->saveOrder($ordersn,['jdkerperorder'=>$jdorder,'kerperaddressid'=>$kerperaddressid]);
            } else {
                $model->commit();
            }
            if ($info['staffid']) {
                $servicetimetemp = explode(' ', $servicetime);
                $paibanresult = $this->paipan('add',$childData[0]['cordersn'] ,$info['staffid'],$servicetimetemp[0], $servicetimetemp[1], 'lock');
                $paipanresult = $paibanresult['res'];
                if ($paipanresult != 200) {
                    $res8 = false;
                }
            }
            //添加订单数
            $wechatRedis->addGuanJiaOrders($guanjiainfo['guanjiaid'],1);
            $wechatRedis->addcronOrdernum($guanjiainfo['guanjiaid'],time()+9, 2);
            if ($info['cardid']) {
                $wechatRedis->delLock('card'.$info['cardid']);
            }
            if ($param['totalprice' ] == 0) {

                $orderinfo = $orderModel->getOrderInfo($ordersn);
                //优惠券抵消
                if ($info['couponid']) {
                    $couponresult =  A("CouponApi/Coupon")->verificationOfCoupon($info['couponid']);
                }
                if ($info['cardid'] &&!$info['isexchange']) {
                    $carredulst = $cardClass->addOneCardHistory($userinfo['jdaccount'],$cardinfo['name'],$info['cardid'],$info['cardprice'],$ordersn,2);
                }
                //排班成功
                if ($info['staffid']) {
                    $servicetimetemp = explode(' ', $servicetime);
                    $paibanresult = $this->paipan('add', $childData[0]['cordersn'],$info['staffid'],$servicetimetemp[0], $servicetimetemp[1], 'work');
                    $paipanresult = $paibanresult['res'];
                }

                //发送邮件
                if ($info['coin']) {
                    $orderClass = new Coin();
                    $res3 = $orderClass->delcoin($userinfo['jdaccount'],$info['coin'],$ordersn);
                }
                $mailPhp = new MailPhp();
                $title = '供应商('.$supplierinfo['suppliershort'].')有新订单了，订单号'.$ordersn;
                $specstr = ($ordergoodData['goodname'] && $ordergoodData['specname'])?'-':'';
                $specstr = $ordergoodData['goodname'].$specstr.$ordergoodData['specname'];
                $info = $param['orderinfo'];
                $info = htmlspecialchars_decode($info);
                $info = json_decode($info, true);
                $str = '';
                foreach ($info as $key => $row) {
                    foreach ($row as $key1 => $vo) {
                        if ($vo['type'] != 3) {
                            $str.='<div>'.$vo['name'].':'.$vo['value'].'</div>';
                        }
                    }
                }
                if ($data['servicetime']) $str.='<div>服务时间:'.$data['servicetime'].'</div>';
                $content =<<<EOF
<h4>产品信息</h4>
<div>产品id:{$ordergoodData['productid']}</div>
<div>产品名称:{$ordergoodData['productname']}</div>
<div>规格名称:{$specstr}</div>
<div>购买份数:{$ordergoodData['num']}</div>
<h4>用户信息</h4>
{$str}
EOF;
                $mailPhp->sendMail($title,$content);
                if ($bookingtype == 2) {
                    $this->confirmSuccessOperation(['productid'=>$info['productid'],'mobile'=>$param['mobile'],'productname'=>$info['productname'],'code'=>$ordergoodData['code'],'servicetime'=>$data['servicetime']]);
                }
                $this->infoSupplier($orderinfo);
                return ['state'=>2,'param'=>['ordersn'=>$ordersn,'code'=>'']];         //v1
            } else {

                //如果是开普勒订单 下单成功
                return ['state'=>1,'param'=>$ordersn];
            }
        } else {
            $model->rollback();
            return false;
        }

    }


    public function verifyOrderCanPay($ordersn)
    {
        if (!$ordersn) response('订单异常');
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $status = $orderinfo['status'];
        if ($status !=0 && $status != 2000) {
            return false;

        } else {
            return true;
        }
    }

    private function verifySubmitOrder($param = [], $openid, $jdaccount)
    {

        $type = $param['type'];
        $agenceyid = isset($param['agenceyid']) ? $param['agenceyid']:0;
        if (!$jdaccount) response('用户异常');
        if (session('jdaccount')!= $jdaccount) response('账号异常');
        $productid = $param['productid'];
        $goodsid = $param['goodsid'];
        $specid = $param['specid'];
        $nums = $param['nums'];
        $addressname = $param['addressname'];
        $mobile = $param['mobile'];
        $totalprice = $param['totalprice'];
        $staffid = isset($param['staffid'])?$param['staffid']:'';
        $couponid = (isset($param['couponid']) && $param['couponid']) ? $param['couponid'] : 0;
        $couponprice = (isset($param['couponprice']) && $param['couponprice']) ? floatval($param['couponprice']) : 0;
        $coin = (isset($param['coin']) && $param['coin']) ? $param['coin'] : 0;
        $cardid = (isset($param['cardid']) && $param['cardid']) ? $param['cardid'] : 0;
        $cardprice = (isset($param['cardprice']) && $param['cardprice']) ? $param['cardprice'] : 0;
        $cardtag = (isset($param['cardtag']) && $param['cardtag']) ? $param['cardtag'] : 0;     //兑换李换卡时需要用到
        if (!$productid) response('数据异常');
        if (!$goodsid) response('数据异常');
        if (!$nums) response('购买数量至少一个');
        if (!$mobile) response('请输入下单人手机号');
        if (!preg_match("/^1\d{10}$/", $mobile)) response('请输入正确的手机号');
        if ($type != 1 && $type != 2) response('产品类型异常');
        //如果选择服务人员验证是否可以选择
        if ($staffid) {
            $city = $param['city'];
            $servicetime = $param['servicetime'];
            list($day, $time) = explode(' ', $servicetime);
            $stafflist = $this->getSupplierStaffList($goodsid, $city, $day, $time);
            if ($stafflist['res'] != 0) {
                response('该时间段服务人员已被占用');
            }
            if (!in_array($staffid, $stafflist['data'])) response('该时间段服务人员已被占用');
        }

        $goodsModel = new GoodsModel();
        if ($type == 1) {
            if (!$addressname) response('请填写正确的姓名');
            $specid = $param['specid'];
            $info = $goodsModel->getSubmitProductInfo($productid,$goodsid,$specid, 1);
            if ($agenceyid && $info) {
                $agencyModel = new AgencyModel();
                $agencyInfo = $agencyModel->getProduct($agenceyid);
                $info['productname'] = $agencyInfo['productname'];
                $info['producttype'] = $agencyInfo['categoryname'];
                $info['categoryid'] = $agencyInfo['leveltwoid'];
                $info['productpic'] = C("AGENTIMG");
                $info['goodname'] = $agencyInfo['goodname'];
                $info['paystyle'] = $agencyInfo['type'];
                $info['price'] = floatval($agencyInfo['price']);
                $info['nums'] = 1;
            }
            if (!$info) response('该商品已下线');            //当购买时删除要买的服务类规格
            if ($info['productstatus'] == 2) response('该产品已下线');
            if ($info['goodsstatus'] == 2) response('该商品已下线');
            if ($info['specstatus'] == 2) response('该规格已下线');
            $info['iskerper'] = $info['kpl_sku'] ? 1 : 0;
        }
        $info['staffid'] = $staffid;
        $restnum = $info['nums'];
        $price = $info['price'];
        $limittype = $info['limittype'];
        $limitnum = $info['limitnum'];
        if (!$restnum || $nums > $restnum) response('该规格已售罄,请重新选择');
        if ($limittype == 3) {              //按单限量
            if ($nums > $limitnum) response('每订单限购'.$limitnum.'份');
        } elseif ($limittype == 2) {
            $orderModel = new OrderModel();
            if ($type == 1) {
                $ordernum = $orderModel->getOrderNum($jdaccount,$productid,$goodsid,$specid,1);
            } else {
                $ordernum = $orderModel->getOrderNum($jdaccount,$productid,0,$goodsid,2);
            }
            if ($ordernum + $nums > $limitnum) response('每用户限购'.$limitnum.'份');
        }
        $orderTotalprice = $price*$nums;
        $noworderprice = $orderTotalprice;
        $info['ordertotalprice'] = $orderTotalprice;
        $coinprice = 0;
        //查看是否是兑换卡
        $isexchange = false;
        $newexchagelist = '';
        $wechatRedis = new WeChatRedis();
        if ($cardid) {
            $cardinfo = M('card')->where(['id'=>$cardid])->limit(1)->find();
            $type = $cardinfo['type'];
            if ($type ==2) {        //兑换卡直接兑换
                if ($wechatRedis->setLock('card'.$cardid,2,1)) {
                    $cardClass = new Card();
                    $newexchagelist = $cardClass->verifyExchange($cardid,$cardtag, $productid, $goodsid, $specid, $nums);
                    $noworderprice = 0;
                    $isexchange = true;
                } else {
                    response('系统繁忙，请刷新重试');
                }
            }
        }

        if (!$isexchange) {                     //非礼品卡才处理这些
            if ($couponid) {
                $dikouprice = A('CouponApi/Coupon')->checkCoupon($couponid,$orderTotalprice);  //抵完后剩多少
                if ($dikouprice === false) response('优惠券异常');
                if ((int)($orderTotalprice*100) - (int)($couponprice*100) < 0) {
                    $couponprice = $orderTotalprice; //实际抵扣金额
                    if ($dikouprice*100 != 0) {
                        response('该优惠不能为免费');
                    }
                }
                else {
                    if ((int)($orderTotalprice*100 - $couponprice*100) != (int)($dikouprice*100)) response('优惠券抵扣异常: ' . $dikouprice*100);
                }
                $noworderprice = $dikouprice;
            }
            //礼品卡

            $cardprice = floatval($cardprice);
            $info['cardprice'] = $cardprice;
            if ($cardid) {
                if ($wechatRedis->setLock('card'.$cardid,2,1)) {
                    $leveltwo = $info['categoryid'];
                    $guanjiaid = $info['guanjiaid'];
                    $supplierid = $info['supplierid'];
                    $cateModel = new CategoryModel();
                    $categoryinfo = $cateModel->getParentCategory(2,$leveltwo);
                    $levelone = $categoryinfo['id'];
                    $cardClass = new Card();
                    $cardre = $cardClass->verifyCard($cardid,$jdaccount,$cardprice,$levelone,$leveltwo,$guanjiaid,$productid,$supplierid);
                    if (!$cardre) response('礼品卡异常，请联系客服');
                } else {
                    response('系统繁忙，请刷新重试');
                }
                $noworderprice = (intval($noworderprice*100) - intval($cardprice*100))/100;

            }

            if ($coin) {
                // 计算补全后价格
                $coinlilv = 10;             //东家币兑换比例  10 指1角
                $total_fixed =  intval(strval($noworderprice * 100));
                $total_coinprice = $coin*$coinlilv;
                if ($total_fixed >=$total_coinprice) {
                    $coinprice = floatval($coin*$coinlilv/100);
                    $noworderprice = floatval(($total_fixed-$total_coinprice)/100);
                } else {
                    if ($total_coinprice - $total_fixed >=$coinlilv) response('价格异常');
                    $coinprice = $noworderprice;
                    $noworderprice = 0;
                    $info['truecoinprice'] = $coinprice;
                }
            }
            if ($coin) {
                $coinClass = new Coin();
                $nowcoin = $coinClass -> getUserCoin($jdaccount);
                if ($coin > $nowcoin) response('东家银子不足');
            }
        }
        $info['cardid'] = $cardid;
        $info['cardtag'] = $cardtag;
        $info['couponid'] = $couponid;
        $info['couponprice'] = $couponprice;
        $info['coinprice'] = $coinprice;
        $info['coin'] = $coin;
        $info['isexchange'] = $isexchange;
        $info['newexchagelist'] = $newexchagelist;
        $noworderprice = (int)(round($noworderprice*100));
        $totalprice = (int)(round($totalprice*100));

        if ($noworderprice != $totalprice) response('价格异常');
        $provinceid = 0;
        $cityid = 0;
        $countyid = 0;
        $townid = 0;
        if ($info['iskerper']) {
            $provinceid = isset($param['provinceid'])? $param['provinceid'] : 0;
            $cityid = isset($param['cityid'])? $param['cityid'] : 0;
            $countyid = isset($param['countyid'])? $param['countyid'] : 0;
            $townid = isset($param['townid'])? $param['townid'] : 0;
            $skuid = $this->verifyKerperOrder($specid,$nums,$price,$provinceid,$cityid,$countyid,$townid);

        }
        $info['provinceid'] = $provinceid;
        $info['cityid'] = $cityid;
        $info['countyid'] = $countyid;
        $info['townid'] = $townid;
        $info['kerperaddressid'] = $provinceid.'_'.$cityid.'_'.$countyid.'_'.$townid;
        $info['skuid'] = $skuid;
        $info['agenceyid'] = $agenceyid;
        //计算结算价格
        $info['settles'] = $this->calsettle($info['settletype'],$info['settlevalue'],$info['price'],$param['nums']);
        return $info;
    }

    private function calsettle($settletype,$settlevalue,$price,$num)
    {
        $settles = '';
        if (!$settlevalue) return '';
        if ($settletype == 1) {
            $settles['settletype'] = $settletype;
            $settles['settlename'] = '固定结算金额';
            $settles['settlevalue'] = $settlevalue;
            $settles['onevalue'] = $settlevalue;
            $settles['allvalue'] = $settles['onevalue']*$num;
            $settles['num'] = $num;
            $settles['settlevalue'] = $settlevalue;
        } elseif ($settletype == 2) {
            $settles['settletype'] = $settletype;
            $settles['settlename'] = '固定佣金比例';
            $settles['settlevalue'] = $settlevalue;
            $settles['onevalue'] = floatval(sprintf('%.2f',($price*(100-$settlevalue)/100)));
            $settles['allvalue'] = $settles['onevalue']*$num;
            $settles['num'] = $num;
            $settles['settlevalue'] = $settlevalue;
        } elseif ($settletype == 3) {
            $settles['settletype'] = $settletype;
            $settles['settlename'] = '固定佣金金额';
            $settles['settlevalue'] = $settlevalue;
            $settles['onevalue'] = floatval($price-$settlevalue);
            $settles['allvalue'] = $settles['onevalue']*$num;
            $settles['num'] = $num;
            $settles['settlevalue'] = $settlevalue;
        }
        if ($settles) $settles = json_encode($settles);
        return $settles;
    }

    public function payOrder($ordersn, $userid, $openid)
    {
        if (!$ordersn) response('订单异常');
        if (!$userid) response('用户异常');
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $status = $orderinfo['status'];
        if (is_weixin()) {
            if ($orderinfo['openid'] && ($openid != $orderinfo['openid'])) response('支付订单异常');
        }
        if (!($status == 0 || $status == 2000)) {
            response('订单已支付，不能重复支付');
        }
        if ($orderinfo['addtime'] + 24 * 60*60 < time()) {
            response('订单已超时,请重新下单');
        }
        //$param['openid'] = $orderinfo['openid'];
        $param['openid'] = $openid;
        $param['ordersn'] = $ordersn;
        $param['totalprice'] = $orderinfo['payrealprice'];
        $param['userid'] = $userid;
        $param['productname'] = $orderinfo['productname'];
        $param['type'] = $orderinfo['type'];
        $JsApiParameters = $this->getJsapiParam($param, $userid);
        return $JsApiParameters;
    }

    /**
     * 小程序取得订单信息获取支付信息
     * @param $ordersn
     * @param $userid
     * @param $openid
     * @return array|bool|\json数据，可直接填入js函数作为参数|mixed
     */
    public function payOrderXCX($ordersn, $userid, $openid, $appid, $mchid)
    {
        if (!$ordersn) response('订单异常');
        if (!$userid) response('用户异常');
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $status = $orderinfo['status'];
        if (is_weixin()) {
//            if ($orderinfo['openid'] && ($openid != $orderinfo['openid'])) response('支付订单异常');
        }
        // todo 测试删除订单状态检查
        if (!($status == 0 || $status == 2000)) {
            response('订单已支付，不能重复支付');
        }
//        if ($orderinfo['addtime'] + 24 * 60*60 < time()) {
//            response('订单已超时,请重新下单');
//        }
        //$param['openid'] = $orderinfo['openid'];
        $param['openid'] = $openid;
        $param['ordersn'] = $ordersn;
        $param['totalprice'] = $orderinfo['payrealprice'];
        $param['userid'] = $userid;
        $param['productname'] = $orderinfo['productname'];
        $param['type'] = $orderinfo['type'];
        $JsApiParameters = $this->getJsapiParamXCX($param, $userid, $appid, $mchid);
        return $JsApiParameters;
    }

    /**
     * 小程序支付getJsapiParam
     * @param $param
     * @param $userid
     * @return array|bool|\json数据，可直接填入js函数作为参数|mixed
     */
    private function getJsapiParamXCX($param, $userid, $appid, $mchid)
    {
        if (!isset($param['productname']) || !isset($param['ordersn']) || !isset($param['totalprice']) || !isset($param['userid'])) return false;
        if ($param['userid'] != $userid) return false;
        $orderModel = new OrderModel();
        $info = $orderModel->getOneOrder($param['ordersn']);
        if ($info['payrealprice'] != $param['totalprice']) return false;
        $trade_no = $param['ordersn'].'_'.time();
        vendor("WeChatPayXCX.JsApiPay");
        $JsApiPay = new \JsApiPay();
        $input=new \WxPayUnifiedOrder();
        $input->SetBody($param['productname']);
        $input->SetOut_trade_no($trade_no);
        $input->SetTotal_fee($param['totalprice']*100);
        $input->SetNotify_url(getUrl()."/myWeb/index.php/WeChat/WeChatPublic/notifyWeChatXCX");

        if (!$param['openid']) return false;
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($param['openid']);

        $input->SetAttach($param['type']);
        $input->SetMch_id($mchid);
        $input->SetAppid($appid);
        // api key 同WxPayConfig中的配置
        $order = \WxPayApi::unifiedOrder($input);
        $timeStamp =time();
        $order['timeStamp']="$timeStamp";

        //TODO 订单加入redis
        $orderkey = C('ORDERKEY');
        $key = md5($trade_no.$orderkey);
        $wechatRedis = new WeChatRedis();
        $res = $wechatRedis->setOrderKey($key, $param['totalprice']);
        $res1 = $orderModel->saveOrder($info['ordersn'],['payrecordsn'=>$trade_no]);
        if ($res && $res1) {
            return $JsApiPay->GetJsApiParameters($order);
        } else {
            return false;
        }

    }

    public function checkH5PayOrder($ordersn = '')
    {
        if (!$ordersn) response('订单异常');
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);

        $totalprice = $orderinfo['payrealprice'];
        $totalprice = floatval($totalprice);
        $trade_no = $orderinfo['payrecordsn'];
        $status = $orderinfo['status'];
        if ($status == 1000 || $status == 2001) {
            return ['msg'=>'支付成功','type'=>$orderinfo['type'],'code'=>$orderinfo['code'],'status'=>1];
        } else{
            vendor("WeChatPay.JsApiPay");
            $input=new \WxPayUnifiedOrder();
            $input->SetOut_trade_no($trade_no);
            $result = \WxPayApi::orderQuery($input);
            if ($result['result_code'] === 'SUCCESS' && $result['return_code'] === 'SUCCESS' && $result['trade_state'] === 'SUCCESS' && ($orderinfo['payrealprice']*100 == $result['total_fee'])) {
                $delstring = json_encode(['id'=>$ordersn,'type'=>'cancelorder']);
                $crontabClass = new Crontab();
                $crontabClass->delOneTimeTick($delstring, false);
                $orderinfo = $orderModel->getOrderInfo($ordersn);
                return ['msg'=>'支付成功','type'=>$orderinfo['type'],'code'=>$orderinfo['code'],'status'=>1];
            }
            return ['msg'=>'还未支付','status'=>0];
        }
    }

    public function checkPayOrder($ordersn = '')
    {
        if (!$ordersn) response('订单异常');
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $totalprice = $orderinfo['totalprice'];
        $totalprice = floatval($totalprice);
        $trade_no = $orderinfo['payrecordsn'];
        if ($orderinfo['status'] == 1000 || $orderinfo['status'] == 2001) {
            response('支付成功', 1,$orderinfo['code']);
        } else {
            vendor("WeChatPay.JsApiPay");
            $input=new \WxPayUnifiedOrder();
            $input->SetOut_trade_no($trade_no);
            $result = \WxPayApi::orderQuery($input);
            if ($result['result_code'] === 'SUCCESS' && $result['return_code'] === 'SUCCESS' && $result['trade_state'] === 'SUCCESS' && ($orderinfo['payrealprice']*100 == $result['total_fee'])) {
                $delstring = json_encode(['id'=>$ordersn,'type'=>'cancelorder']);
                $crontabClass = new Crontab();
                $crontabClass->delOneTimeTick($delstring, false);
                response('支付成功', 1,$orderinfo['code']);
            } else {
                response('还未支付');
            }
        }


    }

    /**
     * 小程序检查支付状态
     * @param string $ordersn
     */
    public function checkXCXPayOrder($ordersn = '', $appid, $mchid)
    {
        if (!$ordersn) response('订单异常');
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $totalprice = $orderinfo['totalprice'];
        $totalprice = floatval($totalprice);
        $trade_no = $orderinfo['payrecordsn'];
        if ($orderinfo['status'] == 1000 || $orderinfo['status'] == 2001) {
            response('支付成功', 1,$orderinfo['code']);
        } else {
            vendor("WeChatPayXCX.JsApiPay");
            $input=new \WxPayUnifiedOrder();
            $input->SetOut_trade_no($trade_no);
            $input->SetMch_id($mchid);
            $input->SetAppid($appid);
            $result = \WxPayApi::orderQuery($input);
            if ($result['result_code'] === 'SUCCESS' && $result['return_code'] === 'SUCCESS' && $result['trade_state'] === 'SUCCESS' && ($orderinfo['payrealprice']*100 == $result['total_fee'])) {
                $delstring = json_encode(['id'=>$ordersn,'type'=>'cancelorder']);
                $crontabClass = new Crontab();
                $crontabClass->delOneTimeTick($delstring, false);
                response('支付成功', 1,$orderinfo['code']);
            } else {
                response('还未支付');
            }
        }


    }

    private function getJsapiParam($param, $userid)
    {
        if (!isset($param['productname']) || !isset($param['ordersn']) || !isset($param['totalprice']) || !isset($param['userid'])) return false;
        if ($param['userid'] != $userid) return false;
        $orderModel = new OrderModel();
        $info = $orderModel->getOneOrder($param['ordersn']);
        if ($info['payrealprice'] != $param['totalprice']) return false;
        $trade_no = $param['ordersn'].'_'.time();
        vendor("WeChatPay.JsApiPay");
        $JsApiPay = new \JsApiPay();
        $input=new \WxPayUnifiedOrder();
        $input->SetBody($param['productname']);
        $input->SetOut_trade_no($trade_no);
        $input->SetTotal_fee($param['totalprice']*100);
        $input->SetNotify_url(getUrl()."/myWeb/index.php/WeChat/WeChatPublic/notifyWeChat");
        if (is_weixin()) {
            // H5/小程序环境设置为JSAPI
            if (!$param['openid']) return false;
            $input->SetTrade_type("JSAPI");
            $input->SetOpenid($param['openid']);
        } else {
            $input->SetTrade_type("MWEB");
            $senceinfo['h5_info'] = [
                'type'=>'WAP',
                'wap_url'=>getUrl(),
                'wap_name'=>'东家管家'
            ];
            $senceinfo = json_encode($senceinfo);
            $input->SetScene_info($senceinfo);
        }
        $input->SetAttach($param['type']);
        $order = \WxPayApi::unifiedOrder($input);
        $timeStamp =time();
        $order['timeStamp']="$timeStamp";
        //TODO 订单加入redis
        $orderkey = C('ORDERKEY');
        $key = md5($trade_no.$orderkey);
        $wechatRedis = new WeChatRedis();
        $res = $wechatRedis->setOrderKey($key, $param['totalprice']);
        $res1 = $orderModel->saveOrder($info['ordersn'],['payrecordsn'=>$trade_no]);
        if ($res && $res1) {
            if (is_weixin()) {
                return $JsApiPay->GetJsApiParameters($order);
            } else {
                $wechatRedis->addPayAlert($param['ordersn']);
                $info = $JsApiPay->GetH5JsApiParameters($order);
                $redirect_url = getUrl().'/myWeb/WeChat/WeChatGuanJia/index?#/pay/'.$param['ordersn'];
                $info['mweb_url'] = $info['mweb_url'].'&redirect_url='.urlencode($redirect_url);
                return $info;
            }
        } else {
            return false;
        }

    }

    public function orderNotifyWeChat($msg, $isXCX = false)
    {

        $result_code=$msg['result_code'];
//支付成功
        $return_code=$msg['return_code'];
        if ($result_code === 'FAIL') {
            return;
        } else {
            //验证sign
            $payOrdersn = $msg['out_trade_no'];
            $totalPrice = $msg['total_fee'];
            $paynum = $msg['transaction_id'];
            $wechatRedis = new WeChatRedis();

            if ($wechatRedis->orderLock($payOrdersn) != 1 or true) {          //获得锁可以操作
                $ordertemp = explode('_',$payOrdersn);
                $ordersn = $ordertemp[0];
                $key = $payOrdersn.C('ORDERKEY');
                $key = md5($key);
                $price = $wechatRedis->getOrderkey($key);
                $type = $msg['attach'];
                $orderModel = new OrderModel();

                if ($price === 'FALSE') return;
                if ($return_code === 'FAIL') {                      //支付失败处理
                    $errmsg = $msg['err_code_des'];
                    $orderModel->addPayRecord($payOrdersn, $ordersn, $paynum, 2, $errmsg);
                    return;
                } else {                                            //支付成功
                    //验证sign
                    if ($isXCX) $result = $this->checkPaySignXCX($msg);
                    else $result = $this->checkPaySign($msg);
                    if (!$result) {
                        $orderModel->addPayRecord($payOrdersn, $ordersn, $paynum, 2, '签名不一致');
                        return;
                    }
                    //验证订单
                    $orderinfo = $orderModel->getOneOrder($ordersn);
                    $orderprice = $orderinfo['payrealprice'];
                    $orderstatus = $orderinfo['status'];
                    $paystatus = $orderinfo['paystatus'];
                    $orderid = $orderinfo['id'];
                    $couponid = $orderinfo['couponid'];
                    $couponprice = $orderinfo['couponmoney'];
                    if (!($totalPrice == intval($orderprice*100) && $totalPrice == intval($price*100))) {                         //订单价格异常
                        $orderModel->addPayRecord($payOrdersn, $ordersn, $paynum, 2, '价格异常');
                        return;
                    }
                    if ($paystatus == 1) {
                        $orderModel->addPayRecord($payOrdersn, $ordersn, $paynum, 2, '订单已被支付');
                        return;
                    }
                    if ($type ==1 && $orderstatus !=0) {
                        $orderModel->addPayRecord($payOrdersn, $ordersn, $paynum, 2, '服务类订单状态异常');
                        return;
                    }
                    if ($type == 2 && $orderstatus != 2000) {
                        $orderModel->addPayRecord($payOrdersn, $ordersn, $paynum, 2, '快递类订单状态异常');
                        return;
                    }
                    $model = M();
                    $model->startTrans();
                    //改变订单状态
                    $orderinfo1 = $orderModel->getOrderInfo($ordersn);
                    $coin = intval($orderinfo1['coin']);
                    $productid = $orderinfo1['productid'];
                    $cardinfo = $orderinfo1['cardinfo'];
                    $produtModel = new GoodsModel();
                    $bookingtype = $produtModel->getProductBooking($productid);                      //booking=2 预订后直接成功
                    $status = $type == 1 ? 1000:2001;
                    if ($bookingtype == 2 && $type == 1) {
                        $status =1001;
                    }
                    $res1= $orderModel->changeOrderStatus($ordersn,$status,1,'','','',$paynum, 1,$bookingtype);
                    //添加订单操作记录
                    $msg = '';
                    if ($bookingtype == 2) {
                        $msg = '(直接预订成功)';
                    }
                    $res2 = $orderModel->addOrderRecord($orderid,$ordersn,$status,0,0,'用户('.$orderinfo['username'].'):支付成功'.$msg,$type);
                    //添加支付流水
                    $res3 = $orderModel->addPayRecord($payOrdersn, $ordersn, $paynum, 1, '支付成功');
                    //添加服务码
                    $res4 = true;

                    //优惠券
                    $res5 = true;

                    if ($couponid) {
                        $res5 =  A("CouponApi/Coupon")->verificationOfCoupon($couponid);
                    }
                    //礼品卡
                    $res6 = true;
                    if ($cardinfo) {
                        $cardinfo = json_decode($cardinfo, true);
                        $cardClass = new Card();
                        $res6 = $cardClass->addOneCardHistory($orderinfo1['jdaccount'],$cardinfo['name'],$cardinfo['id'],$cardinfo['cardprice'],$ordersn,2);
                    }

                    $code = '';
                    if ($type == 1 && !$orderinfo['isexpress']) {
                        $code = createServiceCode();
                        $res4 = $orderModel->addServiceCode($orderid,$code);
                    }
                    if ($res1 && $res2 && $res3 && $res4 && $res5 && $res6) {
                        $model->commit();
                        $jdkerperorder = $orderinfo['jdkerperorder'];
                        $kerperClass = new KerperApi();
                        if ($jdkerperorder) {
                            $kerperClass->confirmKerperOrder($jdkerperorder);       //开普勒确认
                        }
                        if ($coin) {
                            $coinClass = new Coin();
                            $coinClass->delcoin($orderinfo1['jdaccount'],$coin,$ordersn);
                        }
                    } else {
                        $model->rollback();
                    }
                }
                //删除支付锁
                $wechatRedis->orderRelase($payOrdersn);
                //删除orderkey
                $wechatRedis->delOrderkey($key);

                if ($bookingtype == 2 && $type == 1) {
                    $orderinfo1['code'] = $code;
                    $this->confirmSuccessOperation($orderinfo1);
                }
                $this->infoSupplier($orderinfo1);
                sendMail($orderinfo);
                return;
            } else {
                return;
            }
        }
    }

    public function orderNotifyJd($msg)
    {
        $falg = \Jdpay\XMLUtil::decryptResXml($msg, $resdata);
        if (!$falg) return false;
        unset($msg);
        $msg = $resdata;
        if (is_array($msg) && isset($msg['result']['desc']) && $msg['result']['desc'] == 'success') {

            $payList = $msg['payList'];
            $pay = $payList['pay'];
            $payOrdersn = $msg['tradeNum'];
            $totalPrice = $pay['amount'];

            $wechatRedis = new WeChatRedis();

            if ($wechatRedis->orderLock($payOrdersn) != 1) {          //获得锁可以操作
                $ordertemp = explode('_', $payOrdersn);
                $ordersn = $ordertemp[0];
                $paynum = $ordertemp[1];
                $type = $msg['note'];
                $orderModel = new OrderModel();
                //验证订单
                $orderinfo = $orderModel->getOneOrder($ordersn);
                $orderprice = $orderinfo['payrealprice'];
                $orderstatus = $orderinfo['status'];
                $paystatus = $orderinfo['paystatus'];
                $orderid = $orderinfo['id'];
                $coin = intval($orderinfo['coin']);
                $couponid = $orderinfo['couponid'];
                $couponprice = $orderinfo['couponmoney'];
                if ($totalPrice != intval($orderprice * 100) ) {                         //订单价格异常
                    $orderModel->addPayRecord($payOrdersn, $ordersn, $paynum, 2, '价格异常',2);
                    return;
                }
                if ($paystatus == 1) {
                    $orderModel->addPayRecord($payOrdersn, $ordersn, $paynum, 2, '订单已被支付',2);
                    return;
                }
                if ($type == 1 && $orderstatus != 0) {
                    $orderModel->addPayRecord($payOrdersn, $ordersn, $paynum, 2, '服务类订单状态异常',2);
                    return;
                }
                if ($type == 2 && $orderstatus != 2000) {
                    $orderModel->addPayRecord($payOrdersn, $ordersn, $paynum, 2, '快递类订单状态异常', 2);
                    return;
                }
                $model = M();
                $model->startTrans();
                //改变订单状态
                $status = $type == 1 ? 1000 : 2001;
                $orderinfo1 = $orderModel->getOrderInfo($ordersn);
                $cardinfo = $orderinfo1['cardinfo'];
                $productid = $orderinfo1['productid'];
                $produtModel = new GoodsModel();
                $bookingtype = $produtModel->getProductBooking($productid);                      //booking=2 预订后直接成功
                $status = $type == 1 ? 1000:2001;
                if ($bookingtype == 2 && $type == 1) {
                    $status =1001;
                }
                $res1= $orderModel->changeOrderStatus($ordersn,$status,1,'','','',$paynum, 2,$bookingtype);
                //添加订单操作记录
                $msg = '';
                if ($bookingtype == 2) {
                    $msg = '(直接预订成功)';
                }
                //添加订单操作记录
                $res2 = $orderModel->addOrderRecord($orderid, $ordersn, $status, 0, 0, '用户(' . $orderinfo['username'] . '):支付成功'.$msg, $type);
                //添加支付流水
                $res3 = $orderModel->addPayRecord($payOrdersn, $ordersn, $paynum, 1, '支付成功', 2);
                //添加服务码
                $res4 = true;
                //优惠券
                $res5 = true;
                if ($couponid) {
                    $res5 =  A("CouponApi/Coupon")->verificationOfCoupon($couponid);
                }
                //礼品卡
                $res6 = true;
                if ($cardinfo) {
                    $cardinfo = json_decode($cardinfo, true);
                    $cardClass = new Card();
                    $res6 = $cardClass->addOneCardHistory($orderinfo1['jdaccount'],$cardinfo['name'],$cardinfo['id'],$cardinfo['cardprice'],$ordersn,2);
                }
                $code ='';
                if ($type == 1 && !$orderinfo['isexpress']) {
                    $code = createServiceCode();
                    $res4 = $orderModel->addServiceCode($orderid, $code);
                }
                if ($res1 && $res2 && $res3 && $res4 && $res5 && $res6) {
                    $model->commit();
                    $jdkerperorder = $orderinfo['jdkerperorder'];
                    $kerperClass = new KerperApi();
                    if ($jdkerperorder) {
                        $kerperClass->confirmKerperOrder($jdkerperorder);       //开普勒确认
                    }
                    if ($coin) {
                        $coinClass = new Coin();
                        $res = $coinClass->delcoin($orderinfo1['jdaccount'],$coin,$ordersn);

                    }
                } else {
                    $model->rollback();
                }
                //删除支付锁
                $wechatRedis->orderRelase($payOrdersn);
                if ($bookingtype == 2 && $type == 1) {
                    $orderinfo1['code'] = $code;
                    $this->confirmSuccessOperation($orderinfo1);
                }
                $this->infoSupplier($orderinfo1);
                sendMail($orderinfo);
                return;
            } else {
                return;
            }
        } else {
            return false;
        }
    }

    public function getSearchOrder()
    {
        $data[1] = '待支付';
        $data[2] = '待确认';
        $data[3] = '预订成功';
        $data[4] = '预订失败';
        $data[5] = '待发货';
        $data[6] = '已发货';
        $data[7] = '已签收';
        $data[8] = '申请退款';
        $data[9] = '已退款';
        $data[10] = '已取消';
        $data[11] = '已完成';
        return $data;
    }

    /**
     * @breif 处理后台工作可执行状态 orderstatus
     * @param $orders
     * @return array
     */
    public function orderHandelStatus($orders)
    {
        if (!(count($orders) && is_array($orders)))  return false;
        foreach ($orders as $key =>$order)
        {
            $status = $order['status'];
            $paystatus = $order['paystatus'];
            $shippingstatus = $order['shippingstatus'];
            $totalprice = $order['totalprice'];
            $type = $order['type'];
            //TODO 还未对之前的E家清订单处理
            $ejenditme = strtotime('2018-5-13 23:59');
            $guanjiaid = $order['guanjiaid'];                               //e家清产品对后台没有操作权限
            $cancelbutton = 0;                                              //取消按钮   2时 需要跳转到客服申请退款
            $confirmbutton = 0;                                             //确认订单按钮
            $confirmfinishbutton = 0;                                       //确认已完成按钮
            $confirmshippingbuttion = 0;                                     //确认已发货按钮
            $confirmsignbutton = 0;                                         //确认已签收按钮
            $applyrefundbutton = 0;                                         //申请退款按钮
            $confirmrefundbutton = 0;                                       //确认已退款按钮
            $addtime = $order['addtime'];
            if ($type == 1) {                           //服务类
                /******************判断是否有取消按钮*******************************/
                if ($status == 0) {                      //待支付订单可取消
                    $cancelbutton = 1;
                } elseif ($status == 1001 && $totalprice == 0) {     //预约成功且订单免费
                    if ($guanjiaid == C("EGUANJIAID") && $addtime>$ejenditme){
                        $cancelbutton = 0;
                    } else {
                        $cancelbutton = 2;
                    }

                }
                /******************判断是否有确认订单按钮*******************************/
                if ($status == 1000 ) {
                    if ($guanjiaid == C("EGUANJIAID") && $addtime>$ejenditme){
                        $confirmbutton = 0;
                    } else {
                        $confirmbutton = 1;
                    }

                }
                /******************判断是否有确认已完成按钮*******************************/
                if ($status == 1001 ) {
                    if ($guanjiaid == C("EGUANJIAID") && $addtime>$ejenditme){
                        $confirmfinishbutton = 0;
                    } else {
                        $confirmfinishbutton = 1;
                    }
                }
                /******************判断是否有确认已发货按钮*******************************/
                /******************判断是否有确认已签收按钮*******************************/
                /******************判断是否有申请退款按钮*******************************/
                if ($status == 1001 && $totalprice > 0) {        //预约成功且不免费
                    $applyrefundbutton = 1;
                }
                /******************判断是否有确认已退款按钮*******************************/
                if ($status == 1002 ) {
                    $confirmrefundbutton = 1;
                } elseif ($status == 1003) {
                    $confirmrefundbutton = 2;
                } elseif ($status == 1004) {
                    $confirmrefundbutton =3;
                }
            }
            if ($type == 2) {
                /******************判断是否有取消按钮*******************************/
                if ($status == 2000) {                      //待支付订单可取消
                    $cancelbutton = 3;
                } elseif ($status == 2001 && $totalprice == 0) {     //取消已支付免费订单
                    $cancelbutton = 4;
                } elseif ($status == 2003 && $totalprice == 0) {       //取消已签收免费订单
                    $cancelbutton =5;
                }
                /******************判断是否有确认订单按钮*******************************/

                /******************判断是否有确认已完成按钮*******************************/

                /******************判断是否有确认已发货按钮*******************************/
                if ($status == 2001) {
                    $confirmshippingbuttion = 1;
                }
                /******************判断是否有确认已签收按钮*******************************/
                if ($status == 2002) {
                    $confirmsignbutton = 1;
                }
                /******************判断是否有申请退款按钮*******************************/
                if ($status == 2001 && $totalprice >0) {              //退款已支付订单
                    $applyrefundbutton = 2;
                } elseif ($status == 2003 & $totalprice >0) {          //退款已签收订单
                    $applyrefundbutton = 3;
                }
                /******************判断是否有确认已退款按钮*******************************/
                if ($status == 2004) {
                    $confirmrefundbutton = 4 ;
                } elseif ($status == 2005) {
                    $confirmrefundbutton = 5;
                } elseif ($status == 2006) {
                    $confirmrefundbutton = 6;
                }
            }
            if ($order['jdkerperorder']) {
                $orders[$key]['cancelbutton'] = 0;
                $orders[$key]['confirmbutton'] = 0;
                $orders[$key]['confirmfinishbutton'] = 0;
                $orders[$key]['confirmshippingbuttion'] = 0;
                $orders[$key]['confirmsignbutton'] = 0;
                $orders[$key]['applyrefundbutton'] = 0;
                $orders[$key]['confirmrefundbutton'] = 0;
            } else {
                $orders[$key]['cancelbutton'] = $cancelbutton;
                $orders[$key]['confirmbutton'] = $confirmbutton;
                $orders[$key]['confirmfinishbutton'] = $confirmfinishbutton;
                $orders[$key]['confirmshippingbuttion'] = $confirmshippingbuttion;
                $orders[$key]['confirmsignbutton'] = $confirmsignbutton;
                $orders[$key]['applyrefundbutton'] = $applyrefundbutton;
                $orders[$key]['confirmrefundbutton'] = $confirmrefundbutton;
            }

            $orders[$key]['orderstatus'] = $order['jdkerperorder'] ? $this->getJdKerperOrdetStatus($order['jdkerperorder']): $this->showOrderStatus($type,$status, $order['isexpress']);
        }
        return $orders;
    }

    /**
     * @breif 处理微信端可执行状态
     * @param $type
     * @param $status
     * @return array
     */
    public function orderHandelWeChatStatus($orders)
    {
        if (!(count($orders) && is_array($orders))) return false;
        foreach ($orders as $key => $order) {
            $status = $order['status'];
            $totalprice = $order['totalprice'];
            $totalprice = floatval($totalprice);
            $paybutton = 0;
            $cancelbutton = 0;
            $delbutton = 0;
            if ($status == 0 || $status == 2000) {
                $paybutton = 1;
                if ($status == 0) {
                    $cancelbutton = 1;
                } else {
                    $cancelbutton = 3;
                }

            }
            if ($status == 1001 && $totalprice == 0 && $orders['guanjiaid'] != C("EGUANJIAID")) {
                $cancelbutton = 2;
            } elseif ($status == 2001 && $totalprice == 0) {
                $cancelbutton = 4;
            }
            if ($status == 1902 || $status == 1901 || $status == 1900 || $status == 2900 || $status == 2901 || $status == 2902) {
                $delbutton = 1;
            }
            $orders[$key]['paybutton'] = $paybutton;
            $orders[$key]['cancelbutton'] = $cancelbutton;
            $orders[$key]['delbutton'] = $delbutton;
            $orders[$key]['ishomeservice'] = $order['ishomeservice'];
        }
        return $orders;
    }

    /**
     * @breif 显示服务码形式 1、正常显示 2、下划线显示 3、不显示
     * @param int $status
     * @return bool|int
     */
    public function showServerGoodsCode($status = 0)
    {
        if (!$status) return 0;
        if (1000 == $status || 1001 == $status) return 1;
        if (1902 == $status) return 2;
        return 0;
    }

    /**
     * @breif 显示后台订单状态 1未支付 2待确认 3预约成功 4预约失败 5待发货 6已发货 7已签收 8已完成 9申请退款中 9已完成 10已退款 11已取消
     * @param $status
     * @param $paystatus
     * @param $shippingstatus
     * @return int|string
     */
    public function showOrderStatus($type,$status,$isexpress = '')
    {
        $result = 0;
        if ($type == 1) {                                               //服务类
            if ($status == 0) {
                $result = '待支付';
            } elseif ($status == 1000) {
                if ($isexpress) {
                    $result = '待发货';
                } else {
                    $result = '待确认';
                }
            } elseif ($status == 1001) {
                if ($isexpress) {
                    $result = '已发货';
                } else {
                    $result = '预订成功';
                }
            } elseif ($status == 1002 ) {
                $result = '申请退款';    //预约失败,申请退款
            } elseif ($status == 1003) {
                $result = '申请退款';  //取消已预约，申请退款
            } elseif ($status == 1004) {
                $result = '申请退款'; //用户申请退款
            } elseif ($status == 1900) {
                $result = '已退款';
            } elseif ($status == 1901) {
                $result = '已取消';
            } elseif ($status == 1902) {
                $result = '已完成';
            } else {
                $result ='异常';
            }
        } elseif ($type == 2) {
            if ($status == 2000) {
                $result = '待支付';
            } elseif ($status == 2001) {
                $result = '未发货';
            } elseif ($status == 2002) {
                $result = '已发货';
            } elseif ($status == 2003) {
                $result = '已签收';
            } elseif ($status == 2004) {
                $result = '申请退款';  //取消待发货订单,申请退款
            } elseif ($status == 2005) {
                $result = '申请退款'; //取消已签收订单，申请退款
            } elseif ($status == 2006) {
                $result = '申请退款'; //用户取消已订单，申请退款
            } elseif ($status == 2900) {
                $result = '已退款';
            } elseif ($status == 2901) {
                $result = '已取消';
            } elseif ($status == 2902) {
                $result = '已完成';
            } else {
                $result = '异常';
            }
        }
        return $result;

    }

    /**
     * @brief  E家清预约成功后取消订单
     * @param $ordersn
     * @param $operationname
     * @param $cancelType
     */
    public function EjiaCancelOrder($ordersn, $operationname)
    {
        if (!$ordersn) return false;
        $orderModel = new OrderModel;
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $status = $orderinfo['status'];
        $totalprice = floatval($orderinfo['totalprice']);
        $type = $orderinfo['type'];
        $orderid = $orderinfo['id'];
        $model = M();
        $model->startTrans();
        if ($type != 1) response('异常订单');
        if ($totalprice == 0) {                      //E家清取消免费服务订单
            if ($status != 1001) response('订单状态已更新',0,['errcode'=>10000]);
            $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1003,'', 0,'E家清员工('.$operationname.'):取消订单,取消服务类免费订单,修改订单状态为"申请退款"',$type);
            $res2 = $orderModel->changeOrderStatus($ordersn, 1003);
        } else {
            if ($status != 1001) response('订单状态已更新',0,['errcode'=>10000]);
            $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1003,'', 0,'E家清员工('.$operationname.'):取消订单,申请已预约退款,修改订单状态为"申请退款中"',$type);
            $res2 = $orderModel->changeOrderStatus($ordersn, 1003);
        }
        if ($res1 && $res2) {
            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;
        }
    }

    /**
     * @breif 取消订单
     * @param $ordersn
     * @param $userid  用户取消(可能存在供应商取消)时 为用户userid ，客服取消时为客服userid,当自动取消时为空
     * @param $cancelType   1、客服取消未付款订单（服务类） 2、客服取消预订成功免费订单     (服务类)
     *                       3、客服取消未付款订单（快递类） 4、客服取消待发货免费订单（快递类） 5、客服取消已签收免费订单（快递类）
     *                       6、系统自动取消超时未付订单
     * @return int
     */
    public function cancelOrder($ordersn, $userid,$msg, $cancelType)
    {
        if (!$ordersn) return false;
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        if ($cancelType == 6 ) {
            if (!count($orderinfo)) return false;
        } else {
            if (!count($orderinfo)) response('订单号异常',0,'');
        }

        $orderid = $orderinfo['id'];
        $status = $orderinfo['status'];
        $totalprice = $orderinfo['totalprice'];
        $type = $orderinfo['type'];
        $num = $orderinfo['num'];
        $goodid = $orderinfo['goodid'];
        $specid = $orderinfo['specid'];
        $productid = $orderinfo['productid'];
        $cardinfo = $orderinfo['cardinfo'];

        /**************判断订单状态********************/
        $model = M();
        $res1 = true;
        $res2 = true;
        $res3 = true;
        $model->startTrans();
        if ($cancelType == 1) {
            if ($type != 1) return false;
            if ($status != 0) response('订单状态已更新',0,['errcode'=>10000]);
            $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1901,'', $userid,$msg.':取消服务类未支付订单,修改订单状态为"已取消"',$type);
            $res2 = $orderModel->changeOrderStatus($ordersn, 1901);
            $res3 = true;
            if($num) {
                $goodModel = new \Operation\Model\GoodsModel();
                $res3 = $goodModel->addGoodNum($goodid, $num, $specid);
            }
            if ($orderinfo['jdkerperorder']) {
                $kerperClass = new KerperApi();
                $kerperClass->cancelKerperOrder($orderinfo['jdkerperorder']);
            }
            $childinfo = $orderModel->getOrderChild($ordersn);
            foreach ($childinfo as $row) {
                if ($row['staff']) {
                    $this->paipan('del',$row['cordersn'],'','','','');
                }
            }
        } elseif ($cancelType == 2) {
            if ($type != 1) return false;
            if ($status != 1001) response('订单状态已更新',0,['errcode'=>10000]);
            if ($totalprice != 0) response('不能取消该订单');
            $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1003,'', $userid,$msg.':取消服务类免费订单,修改订单状态为"申请退款"',$type);
            $res2 = $orderModel->changeOrderStatus($ordersn, 1003);
            $childinfo = $orderModel->getOrderChild($ordersn);
            foreach ($childinfo as $row) {
                if ($row['staff']) {
                    $this->paipan('del',$row['cordersn'],'','','','');
                }
            }
        } elseif ($cancelType == 3) {
            if ($type != 2) return false;
            if ($status != 2000) response('订单状态已更新',0,['errcode'=>10000]);
            $res1 = $orderModel->addOrderRecord($orderid, $ordersn,2901,'', $userid,$msg.':取消快递类未支付订单,修改订单状态为"已取消"',$type);
            $res2 = $orderModel->changeOrderStatus($ordersn, 2901);
            if($num) {
                $goodModel = new \Operation\Model\GoodsModel();
                $res3 = $goodModel->addGoodNum($goodid, $num, $specid);
            }
        }  elseif ($cancelType == 4) {
            if ($type != 2) return false;
            if ($status != 2001) response('订单状态已更新',0,['errcode'=>10000]);
            if ($totalprice != 0) response('不能取消该订单');
            $res1 = $orderModel->addOrderRecord($orderid, $ordersn,2004,'', $userid,$msg.':取消待发货免费订单,修改订单状态为"申请退款"',$type);
            $res2 = $orderModel->changeOrderStatus($ordersn, 2004);
        } elseif ($cancelType == 5) {
            if ($type != 2) return false;
            if ($status != 2003) response('订单状态已更新',0,['errcode'=>10000]);
            if ($totalprice != 0) response('不能取消该订单');
            $res1 = $orderModel->addOrderRecord($orderid, $ordersn,2004,'', $userid,$msg.':取消已签收订单,修改订单状态为"申请退款"',$type);
            $res2 = $orderModel->changeOrderStatus($ordersn, 2004);
        } elseif ($cancelType == 6) {
            if ($status != 0 && $status != 2000) return false;
            if ($type == 1) {
                $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1901,'', $userid,'系统:取消15分钟未支付订单',$type);
                $res2 = $orderModel->changeOrderStatus($ordersn, 1901);
            } else {
                $res1 = $orderModel->addOrderRecord($orderid, $ordersn,2901,'', $userid,'系统:取消15分钟未支付订单',$type);
                $res2 = $orderModel->changeOrderStatus($ordersn, 2901);
            }
            $goodModel = new \Operation\Model\GoodsModel();
            $res3 = $goodModel->addGoodNum($goodid, $num, $specid);
            if ($orderinfo['jdkerperorder']) {
                $kerperClass = new KerperApi();
                $kerperClass->cancelKerperOrder($orderinfo['jdkerperorder']);
            }
            $childinfo = $orderModel->getOrderChild($ordersn);
            foreach ($childinfo as $row) {
                if ($row['staff']) {
                    $this->paipan('del',$row['cordersn'],'','','','');
                }
            }

        } else {
            return false;
        }
        if ($res1 && $res2 && $res3) {
            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;
        }

    }

    /**
     * @breif e家清确认订单
     * @param $ordersn
     * @param $operationname
     * @param $issuccess
     * @return bool
     */
    public function EjiaConfirmOrder($ordersn, $operationname, $issuccess)
    {
        if (!$ordersn) return false;
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $type = $orderinfo['type'];
        $orderid = $orderinfo['id'];
        $status = $orderinfo['status'];
        $adminModel = new AccountModel();
        $model = M();
        $model->startTrans();
        if ($type != 1) {
            return false;
        }
        if ($status != 1000 && $status != 1001) response('订单状态已更新',0,['errcode'=>10000]);
        if ($issuccess) {
            //预订成功发送短信
            $this->confirmSuccessOperation($orderinfo);
            $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1001,'', 0,'E家清员工('.$operationname.'):确认订单通过,修改订单状态为"预订成功"',$type);
            $res2 = $orderModel->changeOrderStatus($ordersn, 1001);
            $orderModel->handleOrder($ordersn);
        } else {
            $this->confirmFailOperation($orderinfo);
            $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1002,'', 0,'员工('.$operationname.'):确认订单不通过,修改订单状态为"申请退款"',$type);
            $res2 = $orderModel->changeOrderStatus($ordersn, 1002);
            $orderModel->handleOrder($ordersn);
        }
        if ($res1 && $res2) {
            $model->commit();
            if ($issuccess) {
                $data['id'] =$ordersn;
                $data['type']= 'sendmarketing';
                $timeTickClass = new TimeTickToDo(json_encode($data));
                $timeTickClass->addOneTimeTick(time()+3*60);
            }
            return true;
        } else {
            $model->rollback();
            return false;
        }
    }

    /**
     * @breif 客服确认服务类订单
     * @param $ordersn
     * @param $userid
     * @return bool
     */

    public function EjiaModifyAfterAppointTime($ordersn,$appointime,$operationname)
    {
        if (!$ordersn) response('参数错误');
        if (!$appointime) response('参数错误');
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOneOrder($ordersn);
        $status = $orderinfo['status'];
        if ($status !=1001) response('订单状态异常');
        $res = $this->EjiaModiyAppointTime($ordersn,$orderinfo['orderinfo'],$appointime);
        $orderModel = new OrderModel();
        if ($res) {
            $res1 = $orderModel->addOrderRecord($orderinfo['id'], $ordersn,1001,'', 0,'E家清员工('.$operationname.'):修改预约时间到'.Date("Y-m-d G:i:s",$appointime),1);
            if ($res1) return true;
            return false;
        }
        return false;
    }

    public function EjiaModiyAppointTime($ordersn,$orderinfo, $appointtime)
    {
        $orderinfo = htmlspecialchars_decode($orderinfo);
        $orderinfo = json_decode($orderinfo, true);
        $orderinfo = $orderinfo[0];
        foreach ($orderinfo as $key => $row)
        {
            if ($row['type'] == 9) {
                $orderinfo[$key]['value'] = $appointtime;
                break;
            }
        }
        $data[0] = $orderinfo;
        $data = json_encode($data);
        $data = htmlspecialchars($data);
        $orderModel = new OrderModel();
        $res = $orderModel->saveOrder($ordersn,['orderinfo'=>$data]);
        return $res;
    }

    public function confirmOrder($ordersn, $userid,$msg,$issuccess = 0,$refundreason = '', $refundinfo = '',$expressno = '')
    {
        if (!$ordersn) return false;
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $type = $orderinfo['type'];
        $orderid = $orderinfo['id'];
        $status = $orderinfo['status'];
        $productid = $orderinfo['productid'];
        $messageModel = new \Operation\Model\GoodsModel();
        $messagetype = $messageModel->getProductMessagetype($productid);
        $model = M();
        $model->startTrans();
        if ($type != 1) {
            return false;
        }
        if ($status != 1000) response('订单状态已更新',0,['errcode'=>10000]);
        if ($issuccess) {
            //预订成功发送短信
            if ($messagetype !=2) {
                $this->confirmSuccessOperation($orderinfo);
            }
            $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1001,'', $userid,$msg.':确认订单通过,修改订单状态为"预订成功"',$type);
            $res2 = $orderModel->changeOrderStatus($ordersn, 1001,'','',$expressno);
            $orderModel->handleOrder($ordersn);
        } else {
            if ($messagetype !=2) {
                $this->confirmFailOperation($orderinfo);
            }
            $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1002,'', $userid,$msg.':确认订单不通过,修改订单状态为"申请退款"',$type);
            $res2 = $orderModel->changeOrderStatus($ordersn, 1002, '','','',0,'',0,0, $refundreason, $refundinfo);
            $orderModel->handleOrder($ordersn);
        }
        if ($res1 && $res2) {
            $model->commit();
            if ($issuccess && !$orderinfo['isexpress']) {
                $data['id'] =$ordersn;
                $data['type']= 'sendmarketing';
                $timeTickClass = new TimeTickToDo(json_encode($data));
                $timeTickClass->addOneTimeTick(time()+3*60);
            }
            return true;
        } else {
            $model->rollback();
            return false;
        }


    }

    public function EjiaConfirmSuccessOperation($ordersn,$operationname,$code)
    {
        if (!$ordersn) return false;
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        if ($code != $orderinfo['code']) response('服务码不一致');
        $type = $orderinfo['type'];
        $orderid = $orderinfo['id'];
        $status = $orderinfo['status'];
        if ($type !=1 ) return false;
        if ($status != 1001) response('订单状态已更新',0,['errcode'=>10000]);
        $model = M();
        $model->startTrans();
        $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1902,'', 0,'e家清洁阿姨('.$operationname.'):确认完成,修改订单状态为"已完成"',$type);
        $res2 = $orderModel->changeOrderStatus($ordersn, 1902);
        if ($res1 && $res2) {
            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;
        }
    }

    /**
     * @breif 服务类订单确认成功后操作
     * @param $orderinfo
     */
    public function confirmSuccessOperation($orderinfo)
    {
        $sms = new SmsMessage();
        $messageModel = new MessageModel();
        $message = $messageModel->getMessageByProductid($orderinfo['productid']);
        if ($message) {
            $res = $sms->sendcustom($orderinfo['mobile'], $message, $orderinfo);
        } else {
            $servicetime = $orderinfo['servicetime'];
            $param['product'] = $orderinfo['productname'];
            $param['code'] = $orderinfo['code'];
            $param['servicetime'] = $servicetime?Date("Y-m-d日 G:i",strtotime($servicetime)):'';
            $res =  $sms->sendsubscribeSuccess($orderinfo['mobile'], $param);
        }
        return $res;
    }

    public function infoSupplier($orderinfo)
    {
        $addressname = $orderinfo['addressname'];
        $mobile = $orderinfo['mobile'];
        $productname = $orderinfo['productname'];
        $goodname = $orderinfo['goodname'];
        $specname = $orderinfo['specname'];
        $num = $orderinfo['num'];
        $supplierid = $orderinfo['supplierid'];
        $phone = M('supplier')->where(['id'=>$supplierid])->getField('phone');
        if ($phone && C("ISONLINE")) {
            $str = $productname.'-'.$goodname.'-'.$specname.$num.'份';
            $smsClass = new SmsMessage();
            $res= $smsClass->sendSupplierInfo($phone,$addressname,$mobile,$str);
        }

    }

    public function confirmFailOperation($orderinfo)
    {
        $sms = new SmsMessage();
        $param['product'] = $orderinfo['productname'];
        $param['money'] = $orderinfo['totalprice'];
        $param['coin'] = $orderinfo['coin'];
        $param['payrealprice'] = $orderinfo['payrealprice'];
        //确认不通过发送短息（付费）
        if (empty(floatval($orderinfo['totalprice']))) {
            $res =  $sms->sendrefundFree($orderinfo['mobile'],$param);
        } else {
            $res = $sms->sendsubscribeFail($orderinfo['mobile'], $param);
        }
        return $res;
    }


    /**
     * @breif 客服确认已完成服务类订单
     * @param $ordersn
     * @param $userid
     * @return bool
     */
    public function confirmFinishOrder($ordersn, $userid, $msg)
    {
        if (!$ordersn) return false;

        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $type = $orderinfo['type'];
        $orderid = $orderinfo['id'];
        $status = $orderinfo['status'];
        if ($type !=1 ) return false;
        if ($status != 1001) response('订单状态已更新',0,['errcode'=>10000]);
        $model = M();
        $model->startTrans();
        $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1902,'', $userid,$msg.':确认完成,修改订单状态为"已完成"',$type);
        $res2 = $orderModel->changeOrderStatus($ordersn, 1902);
        if ($res1 && $res2) {
            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;
        }
    }

    /**
     * @breif 客服确认订单已发货
     * @param $ordersn
     * @param $userid
     * @param string $expressNo
     * @return bool
     */
    public function confirmShippingOrder($ordersn, $userid, $expressNo = '')
    {
        if (!$ordersn) return false;
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $type = $orderinfo['type'];
        $orderid = $orderinfo['id'];
        $status = $orderinfo['status'];
        $adminModel = new AccountModel();
        $admininfo = $adminModel->getOneAccountInfo($userid);
        if ($type != 2) return false;
        if ($status != 2001) response('订单状态已更新',0,['errcode'=>10000]);
        $model = M();
        $model->startTrans();
        $res1 = $orderModel->addOrderRecord($orderid, $ordersn,2002,'', $userid,'员工('.$admininfo['name'].'):确认发货,修改订单状态为"已发货"',$type);
        $res2 = $orderModel->changeOrderStatus($ordersn, 2002,'', 1, $expressNo);
        if ($res1 && $res2) {
            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;
        }
    }

    /**
     * @breif 确认订单已签收
     * @param $ordersn
     * @param $userid
     * @return bool
     */
    public function confirmSignOrder($ordersn, $userid)
    {
        if (!$ordersn) return false;
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $type = $orderinfo['type'];
        $orderid = $orderinfo['id'];
        $status = $orderinfo['status'];
        $adminModel = new AccountModel();
        $admininfo = $adminModel->getOneAccountInfo($userid);
        if ($type != 2) return false;
        if ($status != 2002) response('订单状态已更新',0,['errcode'=>10000]);
        $model = M();
        $model->startTrans();
        $res1 = $orderModel->addOrderRecord($orderid, $ordersn,2003,'', $userid,'员工('.$admininfo['name'].'):确认签收,修改订单状态为"已签收"',$type);
        $res2 = $orderModel->changeOrderStatus($ordersn, 2003);
        //TODO 扔队列七天订单完成
        $addstring =  json_encode(['id'=>$ordersn,'type'=>'finishorder']);
        $crotabClass = new Crontab($addstring);
        $message = "订单号:$ordersn,添加到7天自动完成队列";
        $time = time() + 7*24*3600;
        $res3 = $crotabClass -> addFinishOrder($time, $message);
        if ($res1 && $res2 && $res3) {
            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;
        }
    }

    /**
     * @breif 快递类订单完成订单,$userid =0 系统7天自动完成
     * @param $ordersn
     * @param $userid
     * @return bool
     */
    public function finishOrder($ordersn)
    {
        if (!$ordersn) return false;
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $type = $orderinfo['type'];
        $orderid = $orderinfo['id'];
        $status = $orderinfo['status'];
        if ($type != 2) return false;
        if ($status != 2003) {
            return false;
        }
        $model = M();
        $model->startTrans();
        $res1 = $orderModel->addOrderRecord($orderid, $ordersn,2902,'', 0,'系统:7天自动完成',$type);
        $res2 = $orderModel->changeOrderStatus($ordersn, 2902);
        if ($res1 && $res2) {
            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;
        }

    }

    /**
     * @breif 申请退款
     * @param $ordersn
     * @param $userid
     * @param $applyType 1、客服申请退款预订成功订单（服务类） 2、客服申请待发货订单（快递类） 3、客服申请退款以签收订单（快递类）
     * @return bool
     */
    public function applyRefunOrder($ordersn, $userid,$msg, $applyType, $refundreason = '', $refundinfo = '')
    {
        if (!$ordersn) return false;
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $type = $orderinfo['type'];
        $orderid = $orderinfo['id'];
        $status = $orderinfo['status'];
        $model = M();
        $model->startTrans();
        $res1 = true;
        $res2 = true;
        if ($applyType == 1) {
            if ($type != 1) return false;
            if ($status != 1001) response('订单状态已更新',0,['errcode'=>10000]);
            $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1003,'', $userid,$msg.':申请已预约退款,修改订单状态为"申请退款中"',$type);
            $res2 = $orderModel->changeOrderStatus($ordersn, 1003,'','','',0,'',0,0,$refundreason, $refundinfo);
        } elseif ($applyType == 2) {
            if ($type != 2) return false;
            if ($status != 2001) response('订单状态已更新',0,['errcode'=>10000]);
            $res1 = $orderModel->addOrderRecord($orderid, $ordersn,2004,'', $userid,$msg.':申请待发货退款,修改订单状态为"申请退款中"',$type);
            $res2 = $orderModel->changeOrderStatus($ordersn, 2004);
        } elseif ($applyType == 3) {
            if ($type != 2) return false;
            if ($status != 2003) response('订单状态已更新',0,['errcode'=>10000]);
            $res1 = $orderModel->addOrderRecord($orderid, $ordersn,2005,'', $userid,$msg.':申请已签收退款,修改订单状态为"申请退款中"',$type);
            $res2 = $orderModel->changeOrderStatus($ordersn, 2005);
        }
        if ($res1 && $res2) {
            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;
        }
    }


    /**
     * @breif 确认退款
     * @param $ordersn
     * @param $userid
     * @param $confirmType 1、确认预约失败退款（服务类） 2、确认预约成功后的退款（服务类）3、用户取消订单申请退款
     *                      4、确认待发货退款（快递） 5、确认已签收退款（快递） 6、用于取消未支付订单
     * @return bool
     */
    public function confirmRefundOrder($ordersn, $userid, $msg,$confirmType)
    {
        if (!$ordersn) return false;
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $type = $orderinfo['type'];
        $orderid = $orderinfo['id'];
        $status = $orderinfo['status'];
        $totalprice = $orderinfo['totalprice'];
        $totalprice = floatval($totalprice);
        $goodid = $orderinfo['goodid'];
        $specid = $orderinfo['specid'];
        $num = $orderinfo['num'];
        $productid = $orderinfo['productid'];
        $messageModel = new \Operation\Model\GoodsModel();
        $messagetype = $messageModel->getProductMessagetype($productid);
        $model = M();
        $model->startTrans();
        $res1 = true;
        $res2 = true;
        $res3 = true;
        $sms = new SmsMessage();
        if ($confirmType == 1) {
            if ($type != 1) return false;
            if ($status != 1002) response('订单状态已更新',0,['errcode'=>10000]);
            if ($totalprice > 0) {
                $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1900,'', $userid,$msg.':确认退款(预约失败退款),退款金额为'.$totalprice.'元,修改订单状态为"已退款"',$type);
                $res2 = $orderModel->changeOrderStatus($ordersn, 1900,'','','',1);
            } else {
                $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1901,'', $userid,$msg.':确认退款(预约失败退款),退款金额为'.$totalprice.'元,修改订单状态为"已取消"',$type);
                $res2 = $orderModel->changeOrderStatus($ordersn, 1901);
            }
        } elseif ($confirmType == 2) {
            if ($type != 1) return false;
            if ($status != 1003) response('订单状态已更新',0,['errcode'=>10000]);
            if ($totalprice > 0) {
                $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1900,'', $userid,$msg.':确认退款(预约成功退款),退款金额为'.$totalprice.'元,修改订单状态为"已退款"',$type);
                $res2 = $orderModel->changeOrderStatus($ordersn, 1900,'','','',1);
            } else {
                $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1901,'', $userid,$msg.':确认退款(预约成功退款),退款金额为'.$totalprice.'元,修改订单状态为"已取消"',$type);
                $res2 = $orderModel->changeOrderStatus($ordersn, 1901);
            }
        } elseif ($confirmType == 3) {
            if ($type != 1) return false;
            if ($status != 1004) response('订单状态已更新',0,['errcode'=>10000]);
            if ($totalprice > 0) {
                $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1900,'', $userid,$msg.':确认退款(用户取消退款),退款金额为'.$totalprice.'元,修改订单状态为"已退款"',$type);
                $res2 = $orderModel->changeOrderStatus($ordersn, 1900,'','','',1);
            } else {
                $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1901,'', $userid,$msg.':确认退款(用户取消退款),退款金额为'.$totalprice.'元,修改订单状态为"已取消"',$type);
                $res2 = $orderModel->changeOrderStatus($ordersn, 1901);
            }
        } elseif ($confirmType == 4) {
            if ($type != 2) return false;
            if ($status != 2004) response('订单状态已更新',0,['errcode'=>10000]);
            if ($totalprice > 0) {
                $res1 = $orderModel->addOrderRecord($orderid, $ordersn,2900,'', $userid,$msg.':确认退款(待发货退款),退款金额为'.$totalprice.'元,修改订单状态为"已退款"',$type);
                $res2 = $orderModel->changeOrderStatus($ordersn, 2900,'','','',1);
            } else {
                $res1 = $orderModel->addOrderRecord($orderid, $ordersn,2901,'', $userid,$msg.':确认退款(待发货退款),退款金额为'.$totalprice.'元,修改订单状态为"已取消"',$type);
                $res2 = $orderModel->changeOrderStatus($ordersn, 2901);
            }
        } elseif ($confirmType == 5) {
            if ($type != 2) return false;
            if ($status != 2005) response('订单状态已更新',0,['errcode'=>10000]);
            if ($totalprice > 0) {
                $res1 = $orderModel->addOrderRecord($orderid, $ordersn,2900,'', $userid,$msg.':确认退款(已签收退款),退款金额为'.$totalprice.'元,修改订单状态为"已退款"',$type);
                $res2 = $orderModel->changeOrderStatus($ordersn, 2900,'','', '',1);
            } else {
                $res1 = $orderModel->addOrderRecord($orderid, $ordersn,2901,'', $userid,$msg.':确认退款(已签收退款),退款金额为'.$totalprice.'元,修改订单状态为"已取消"',$type);
                $res2 = $orderModel->changeOrderStatus($ordersn, 2901);
            }
        } elseif ($confirmType == 6) {
            if ($type != 2) return false;
            if ($status != 2006) response('订单状态已更新',0,['errcode'=>10000]);
            if ($totalprice > 0) {
                $res1 = $orderModel->addOrderRecord($orderid, $ordersn,2900,'', $userid,$msg.':确认退款(用户取消已支付退款),退款金额为'.$totalprice.'元,修改订单状态为"已退款"',$type);
                $res2 = $orderModel->changeOrderStatus($ordersn, 2900,'','', '',1);
            } else {
                $res1 = $orderModel->addOrderRecord($orderid, $ordersn,2901,'', $userid,$msg.':确认退款(用户取消已支付退款),退款金额为'.$totalprice.'元,修改订单状态为"已取消"',$type);
                $res2 = $orderModel->changeOrderStatus($ordersn, 2901);
            }
        } else {
            return false;
        }
        if ($num >0 ) {
            $goodsModel = new \Operation\Model\GoodsModel();
            $res3 = $goodsModel->addGoodNum($goodid, $num, $specid);
        }

        if ($res1 && $res2 && $res3) {
            $model->commit();
            if ($totalprice >0 ) {
                $param['product'] = $orderinfo['productname'];
                $param['money'] = $totalprice;
                $param['payrealprice'] = $orderinfo['payrealprice'];
                $param['coin'] = $orderinfo['coin'];
                if ($messagetype != 2) {
                    if ($orderinfo['type'] == 1) {
                        $sms->sendrefundNoFree($orderinfo['mobile'], $param);
                    } else {
                        $sms->sendrefund($orderinfo['mobile'], $param);
                    }
                }
            } else {
                if ($messagetype != 2) {
                    $param['product'] = $orderinfo['productname'];
                    $sms->sendrefundFree($orderinfo['mobile'], $param);
                }
            }
            return true;
        } else {
            return false;
        }
    }


    public function finishChildOrdersn($cordersn, $userid, $msg)
    {
        $ordersn = substr($cordersn,0, strlen($cordersn)-3);
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $info = $orderModel->getChildOrderInfo($cordersn);
        if ($info['status'] == 2000) response('该服务已设置为完成');
        $model = M();
        $model->startTrans();
        $res1 = $orderModel->changeCordersn($cordersn,2000);
        $res2 = $orderModel->addOrderRecord($orderinfo['id'], $ordersn,-2000,'', $userid,$msg.':修改子订单('.$cordersn.')为已完成',1);
        if ($res1 && $res2) {
            $model->commit();
            return  true;
        } else {
            $model->rollback();
            return false;
        }
    }


    /**
     * @breif 客服删除订单
     * @param $ordersn
     * @return bool
     */
    public function delOrder($ordersn)
    {
        if (!$ordersn) return false;
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $orderid = $orderinfo['id'];
        $status = $orderinfo['status'];
        if ($status == 1900 || $status == 1901 || $status == 1902 || $status == 2900 || $status == 2901 || $status == 2902) {
            $orderModel = new OrderModel();
            $res = $orderModel->delOrder($ordersn, $orderid);
            return $res;
        } else {
            return false;
        }
    }

    /**
     * @breif 微信端删除订单
     * @param $userid
     * @param $ordersn
     * @return bool
     */
    public function userDelOrder($jdaccount, $ordersn)
    {
        if (!$jdaccount) return false;
        if (!$ordersn) return false;
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $status = $orderinfo['status'];
        if ($status == 1900 || $status == 1901 || $status == 1902 || $status == 2900 || $status == 2901 || $status == 2902) {
            $orderModel = new OrderModel();
            $res = $orderModel->userDelOrder($jdaccount, $ordersn);
            return $res;
        } else {
            response('订单状态已更新',0,['errcode'=>10000]);
        }
    }

    /**
     * @breif 微信端取消订单
     * @param $userid
     * @param $ordersn
     * @param $canceltype
     * @return bool
     */
    public function userCancelOrder($jdaccount, $ordersn, $canceltype)
    {
        if (!$jdaccount) return false;
        if (!$ordersn) return false;
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $orderid = $orderinfo['id'];
        $goodid = $orderinfo['goodid'];
        $specid = $orderinfo['specid'];
        $status = $orderinfo['status'];
        $num = $orderinfo['num'];
        $model = M();
        $model->startTrans();
        $res1 = true;
        $res2 = true;
        $res3 = true;
        if ($canceltype == 1) {      //服务类待支付取消
            if ($status != 0) response('订单状态已更新',0,['errcode'=>10000]);
            if ($orderinfo['type'] != 1) response('订单异常');
            $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1901,$jdaccount, '','用户('.$orderinfo['username'].'):取消服务类未支付,修改订单状态为"已取消"');
            $res2 = $orderModel->changeOrderStatus($ordersn, 1901);
            if($num) {
                $goodModel = new \Operation\Model\GoodsModel();
                $res3 = $goodModel->addGoodNum($goodid, $num, $specid);
            }
            if ($orderinfo['jdkerperorder']) {
                $kerperClass = new KerperApi();
                $kerperClass->cancelKerperOrder($orderinfo['jdkerperorder']);
            }
            $childinfo = $orderModel->getOrderChild($ordersn);
            foreach ($childinfo as $row) {
                if ($row['staff']) {
                    $this->paipan('del',$row['cordersn'],'','','','');
                }
            }
        } elseif ($canceltype == 2) {  //服务类预约成功 且价格为0
            if ($status != 1001) response('订单状态已更新',0,['errcode'=>10000]);
            if ($orderinfo['totalprice'] != 0) response('订单异常');
            if ($orderinfo['type'] != 1) response('订单异常');
            $res1 = $orderModel->addOrderRecord($orderid, $ordersn,1004,$jdaccount, '','用户('.$orderinfo['username'].'):取消预约成功免费订单,修改订单状态为"客服申请退款"');
            $res2 = $orderModel->changeOrderStatus($ordersn, 1004);
        } elseif ($canceltype == 3) {    //待支付订单
            if ($status != 2000) response('订单状态已更新',0,['errcode'=>10000]);
            if ($orderinfo['type'] != 2) response('订单异常');
            $res1 = $orderModel->addOrderRecord($orderid, $ordersn,2901,$jdaccount, '','用户('.$orderinfo['username'].'):取消快递类未支付,修改订单状态为"已取消"');
            $res2 = $orderModel->changeOrderStatus($ordersn, 2901);
            if($num) {
                $goodModel = new \Operation\Model\GoodsModel();
                $res3 = $goodModel->addGoodNum($goodid, $num, $specid);
            }
        } elseif($canceltype == 4) {     //已支付免费订单 快递类
            if ($status != 2001) response('订单状态已更新',0,['errcode'=>10000]);
            if ($orderinfo['totalprice'] != 0) response('订单异常');
            if ($orderinfo['type'] != 2) response('订单异常');
            $res1 = $orderModel->addOrderRecord($orderid, $ordersn,2006,$jdaccount, '','用户('.$orderinfo['username'].'):取消已支付快递类免费订单,修改订单状态为"客服申请退款"');
            $res2 = $orderModel->changeOrderStatus($ordersn, 2006);
        } else{
            return false;
        }
        if ($res1 && $res2 && $res3) {
            $model->commit();
            return true;
        } else {
            return false;
        }
    }

    /*   public function noPayOrder($userid, $ordersn)
       {
           if (!$userid) return false;
           if (!$ordersn) return false;
           $where['ordersn'] = $ordersn;
           $where['userid'] = $userid;
           $orderModel = new OrderModel();
           $orderinfo = $orderModel->getOrderInfo($ordersn);
           $status = $orderinfo['status'];
           $addtime = $orderinfo['addtime'];
           $openid = $orderinfo['openid'];
           if ($status != 0 && $status != 2000) response('订单状态已更新');
           if ($addtime + 15*60 < time()) response('订单已超时请重新下单');
           if ($openid != session('openid')) response('订单异常,请重新下单');
           $payparam['openid'] = $openid;
           $payparam['ordersn'] = $ordersn;
           $payparam['totalprice'] = $orderinfo['totalprice'];
           $payparam['userid'] = $userid;
           $payparam['type'] = $orderinfo['type'];
           if($orderinfo['type'] == 1) {
               $payparam['goodsname'] = $orderinfo['productname'].'-'.$orderinfo['goodname'].'('.$orderinfo['specname'].')';
           } else {
               $payparam['goodsname'] = $orderinfo['productname'].'-'.$orderinfo['goodname'];
           }
           $payparam = json_encode($payparam);
           $aesClass = new GJAES();
           $payparams = $aesClass -> aes_encrypt($payparam, 'dongrichorder');
           return $payparams;
       }*/
    private function checkPaySign($retrunParam)
    {
        if (C('ISONLINE')) {
            require_once LIB_PATH.'Vendor/WeChatPay/WxPay.Data.php';
        } else {
            require_once LIB_PATH.'Vendor/WeChatPayTest/WxPay.Data.php';
        }

        $inputObj = new \WxPayUnifiedOrder();
        $data =$retrunParam;
        foreach ($data as $key => $val) {
            if ($key != 'sign' && $val !== '') {
                $inputObj->setParam($key, $val);
            }

        }
        $inputObj->setSign();
        $result = $inputObj->getSign();
        return $result == $retrunParam['sign'];
    }

    /**
     * 小程序验证签名
     * @param $retrunParam
     * @return bool
     */
    private function checkPaySignXCX($retrunParam)
    {
        require_once LIB_PATH.'Vendor/WeChatPayXCX/WxPay.Data.php';
        $inputObj = new \WxPayUnifiedOrder();
        $data =$retrunParam;
        foreach ($data as $key => $val) {
            if ($key != 'sign' && $val !== '') {
                $inputObj->setParam($key, $val);
            }

        }
        $inputObj->setSign();
        $result = $inputObj->getSign();
        return $result == $retrunParam['sign'];
    }

    /**
     * @breif 根据产品id统计该产品的"订单数量","订单用户数","成单数量","成单用户数"
     * @param array $ids
     * @return bool
     */
    public function staticsOrdersByProductids($ids = [],$startTime = '', $endTime = '',$data)
    {
        if (!is_array($ids)|| !count($ids)) return false;
        $where = [];
        if ($startTime && $endTime) {
            $where['o.addtime'] = [['gt',$startTime],['elt',$endTime]];
        } elseif ($startTime) {
            $where['o.addtime'] = ['gt', $startTime];
        } elseif ($endTime) {
            $where['o.addtime'] = ['lt',$endTime];
        }
        $where['o.isdelete'] = 0;
        $where['g.productid'] = ['in',$ids];
        $orderClass = new OrderModel();
        //订单总数量
        $allOrders = $orderClass->staticsOrdersByProductids($where);
        //订单用户数
        $allUserOrders = $orderClass->staticsOrdersUsersByProductids($where);
        /*       $where['o.status'] = ['not in', [0,2000]];
               //成单量
               $payAllOrders = $orderClass->staticsOrdersByProductids($where);
               //成单用户数
               $payAllUserOrders = $orderClass->staticsOrdersUsersByProductids($where);*/
        foreach ($data as $key1 => $row) {
            $productid = $row[8];
            foreach ($allOrders as $vo1) {
                if ($vo1['productid'] == $productid) {
                    $data[$key1][4] = $vo1['total'];
                    break;
                }
            }
            foreach ($allUserOrders as $vo2) {
                if ($vo2['productid'] == $productid) {
                    $data[$key1][5] = $vo2['total'];
                    break;
                }
            }
            /*         foreach ($payAllOrders as $vo3) {
                         if ($vo3['productid'] == $productid) {
                             $data[$key1][6] = $vo3['total'];
                             break;
                         }
                     }
                     foreach ($payAllUserOrders as $vo4) {
                         if ($vo4['productid'] == $productid) {
                             $data[$key1][7] = $vo4['total'];
                             break;
                         }
                     }*/
            if (!isset($data[$key1][4])) $data[$key1][4] = 0;
            if (!isset($data[$key1][5])) $data[$key1][5] = 0;
            /*            if (!isset($data[$key1][6])) $data[$key1][6] = 0;
                        if (!isset($data[$key1][7])) $data[$key1][7] = 0;*/
            unset($data[$key1][8]);
        }
        return $data;

    }

    public function getEcommerceTrackInfo($ordersns = [])
    {
        $orderModel = new OrderModel();
        $res = $orderModel->getEcommerceTrackInfo($ordersns);
        foreach ($res as $key => $row) {
            $res[$key]['category'] = str_replace('-','|',$row['category']);
            $res[$key]['shipping'] = 0;
            $res[$key]['tax'] = 0;
        }
        return $res;
    }

    //自定义下单
    //format 姓名,1,1,1,,1|手机号,1,1,1,,2|城市,2,0,2,上海;北京,3|身份证,3,1,2,2;正面;反面,4|地址,3,1,1,,5|
    public function getOrderFormat($str = '',$goodid = 0)
    {
        if (!$str) return false;
        if (!$goodid) return false;
        $all = explode('|', $str);
        $data = [];
        foreach ($all as $row) {
            $temp = explode(',', $row);
            if (count($temp)<6) return false;
            $type = $temp[1];                       //1、2、3、4
            $name = $temp[0];                       //名字
            $require = $temp[2];                     //0或1
            $numtype = $temp[3];                    //1或2
            $extra = $temp[4];
            $sort = $temp[5];                       //顺序
            if (!$name) return false;
            if ($type == 1 || $type == 4) {
                $data[]=[
                    'name' =>$name,
                    'type'=>$type,
                    'require'=>$require,
                    'numtype'=>$numtype,
                    'extra'=>'',
                    'goodid'=>$goodid,
                    'sort'=>$sort
                ];
            } elseif ($type == 2) {                 //筛选框
                if (!$extra) return false;
                $options = explode(';',$extra);
                if (count($options) <2) return false;
                $data[]=[
                    'name' =>$name,
                    'type'=>$type,
                    'require'=>$require,
                    'numtype'=>$numtype,
                    'extra'=>$extra,
                    'goodid'=>$goodid,
                    'sort'=>$sort
                ];
            } elseif ($type == 3) {
                if (!$extra) return false;
                $data[]=[
                    'name' =>$name,
                    'type'=>$type,
                    'require'=>$require,
                    'numtype'=>$numtype,
                    'extra'=>$extra,
                    'goodid'=>$goodid,
                    'sort'=>$sort
                ];
            }
        }
        return $data;
    }

    public function setOrderFormat($data = [])
    {
        if (!count($data)) return false;
        $str = '';
        foreach ($data as $row) {
            $str .=$row['name'].','.$row['type'].','.$row['require'].','.$row['numtype'].','.$row['extra'].','.$row['sort'].','.$row['id']."|";
        }
        $str = substr($str,0,strlen($str)-1);
        return $str;
    }

    public function editOrderFormat($str, $goodid = 0)
    {
        $str = explode('|',$str);
        $goodModel = new \Operation\Model\GoodsModel();
        $orginids = $goodModel->getAllFormatIds($goodid);
        $addData = [];
        $delData = [];
        $editData = [];
        $hasids = [];

        foreach ($str as $row) {
            $temp = explode(',',$row);
            $name = $temp[0];
            $type = $temp[1];
            $require = $temp[2];
            $numtype = $temp[3];
            $extra = $temp[4];
            $sort = $temp[5];
            $id = $temp[6];
            if ($id == 0) {

                if (!$name) return false;
                if ($type == 1 || $type == 4) {
                    $addData[]=[
                        'name' =>$name,
                        'type'=>$type,
                        'require'=>$require,
                        'numtype'=>$numtype,
                        'extra'=>'',
                        'goodid'=>$goodid,
                        'sort'=>$sort
                    ];
                } elseif ($type == 2) {                 //筛选框
                    if (!$extra) return false;
                    $options = explode(';',$extra);
                    if (count($options) <2) return false;
                    $addData[]=[
                        'name' =>$name,
                        'type'=>$type,
                        'require'=>$require,
                        'numtype'=>$numtype,
                        'extra'=>$extra,
                        'goodid'=>$goodid,
                        'sort'=>$sort
                    ];
                } elseif ($type == 3) {

                    if (!$extra) return false;
                    $addData[]=[
                        'name' =>$name,
                        'type'=>$type,
                        'require'=>$require,
                        'numtype'=>$numtype,
                        'extra'=>$extra,
                        'goodid'=>$goodid,
                        'sort'=>$sort
                    ];
                }
            } else {
                $hasids[]=$id;
                if (!$name) return false;
                if ($type == 1 || $type == 4) {
                    $editData[$id]=[
                        'name' =>$name,
                        'type'=>$type,
                        'require'=>$require,
                        'numtype'=>$numtype,
                        'extra'=>'',
                        'goodid'=>$goodid,
                        'sort'=>$sort
                    ];
                } elseif ($type == 2) {                 //筛选框
                    if (!$extra) return false;
                    $options = explode(';',$extra);
                    if (count($options) <2) return false;
                    $editData[$id]=[
                        'name' =>$name,
                        'type'=>$type,
                        'require'=>$require,
                        'numtype'=>$numtype,
                        'extra'=>$extra,
                        'goodid'=>$goodid,
                        'sort'=>$sort
                    ];
                } elseif ($type == 3) {
                    if (!$extra) return false;
                    $editData[$id]=[
                        'name' =>$name,
                        'type'=>$type,
                        'require'=>$require,
                        'numtype'=>$numtype,
                        'extra'=>$extra,
                        'goodid'=>$goodid,
                        'sort'=>$sort
                    ];
                }
            }
        }
        $delData = array_diff($orginids,$hasids);
        return ['delData'=>$delData,'editData'=>$editData,'addData'=>$addData];
    }

    public function getWeChatOrderFormat($goodid = 0)
    {
        if (!$goodid) return false;
        $goodModel = new \Operation\Model\GoodsModel();
        $orderformat = $goodModel->getOrderFormat($goodid);
        $data = [];
        if (!$orderformat) {
            $data[]=[
                'type'=>1,
                'name'=>'姓名',
                'require'=>1,
                'numtype'=>1,
                'extra'=>''
            ];
            $data[]=[
                'type'=>1,
                'name'=>'手机号',
                'require'=>1,
                'numtype'=>1,
                'extra'=>''
            ];
        } else {
            foreach ($orderformat as $row) {
                $type = $row['type'];
                $name = $row['name'];
                $require = $row['require'];
                $numtype = $row['numtype'];
                $extra = $row['extra'];
                if ($type == 2 || $type == 3) {
                    $extra = explode(';',$extra);
                }
                $data[]=[
                    'type'=>$type,
                    'name'=>$name,
                    'require'=>$require,
                    'numtype'=>$numtype,
                    'extra'=>$extra
                ];
            }
        }


        return $data;
    }

    public function getOrderInfoFormat($str)
    {
        if (!$str) return false;
        $str = json_decode($str, true);
        $data = [];
        foreach ($str as $key => $row) {
            foreach ($row as $key1 => $vo) {
                $data[$key][$key1]['name'] = $vo['name'];
                $data[$key][$key1]['type'] = $vo['type'];
                $data[$key][$key1]['extra'] = $vo['extra'];

                if ($vo['type'] == 3) {
                    $data[$key][$key1]['value'] = explode(';',$vo['value']);
                } else {
                    $data[$key][$key1]['value'] = $vo['value'];
                }
            }
        }
        return $data;
    }

    public function getOperationOrderInfoFormat($str)
    {
        if (!$str) return false;
        $str = json_decode($str, true);
        $txtdata = [];
        $imagedata = [];
        foreach ($str as $key => $row) {
            foreach ($row as $key1 => $vo) {
                if ($vo['type'] == 3) {
                    $imagedata[$key][] =[
                        'name'=>$vo['name'],
                        'type'=>$vo['type'],
                        'value'=>explode(';',$vo['value']),
                    ];
                } else {
                    $txtdata[$key][] =[
                        'name'=>$vo['name'],
                        'type'=>$vo['type'],
                        'value'=>$vo['value'],
                    ];
                }


            }
        }
        return ['txtdata'=>$txtdata,'imagedata'=>$imagedata];
    }

    /*寻找可以push的订单*/

    public function findCanPushOrder($ordersn)
    {
        $orderModel = new OrderModel();

        $pushInfo = $orderModel->getOrderPushInfo($ordersn);

        return $pushInfo;

    }

    /**
     * @breif 向e家清
     */
    public function sendEjiaOrder($orderinfo)
    {
        $jdcode=$orderinfo['code'];
        $name = '';
        $phone = '';
        $jdprice = $orderinfo['totalprice'];
        $jdorder = $orderinfo['ordersn'];
        $ordertime = '';
        $address = '';
        $orderinfos = htmlspecialchars_decode($orderinfo['orderinfo']);
        $orderinfos = json_decode($orderinfos,true);
        $orderinfos = $orderinfos[0];
        foreach ($orderinfos as $row) {
            if ($row['type'] == 1 && $row['name'] == '姓名') {
                $name = $row['value'];
            } elseif ($row['type'] == 1 && $row['name'] == '手机号') {
                $phone = $row['value'];
            } elseif ($row['type'] == 1 && $row['name'] == '详细地址') {
                $address = $row['value'];
            }
        }
        $servietime  = (isset($orderinfo['servietime'])&&$orderinfo['servietime'])?strtotime($orderinfo['servietime']):time();
        if (!$name || !$phone) return 0;
        $data['jdorder'] = $jdorder;
        $data['name'] = $name;
        $data['phone'] = $phone;
        $data['address'] = $address;
        $data['ordertime'] = $servietime;
        $data['jdprice'] = floatval($jdprice);
        $data['jdcode'] = $jdcode;
        $data['productid'] = $orderinfo['productid'];
        $data['productname'] = $orderinfo['productname'];
        $data['goodname'] = $orderinfo['goodname'];
        $data['goodid'] = $orderinfo['goodid'];
        $data['specname'] = $orderinfo['specname'];
        $data['specid'] = $orderinfo['specid'];
        $gjAes = new GJAES();
        $data =json_encode($data);
        $data = $gjAes->aes_encrypt($data,'woshinibaba');
        // todo e家清后台
        $res = http_post_data('http://117.48.208.220/index.php/AjaxApi/Api/addOneJdOrder',json_encode(['data'=>$data]));
        $res = json_decode($res,true);
        if (isset($res['state'])) {
            return $res['state'];
        } else {
            return 0;
        }

    }

    public function setSettleType($ordersn, $settletype = 1)
    {
        $param['settletype'] = $settletype;
        $orderModel = new OrderModel();
        return $orderModel->saveOrder($ordersn,$param);
    }

    public function setHandleType($ordersn, $settletype = 1)
    {
        $param['handletype'] = $settletype;
        $orderModel = new OrderModel();
        return $orderModel->saveOrder($ordersn,$param);
    }

    public function getSupplierStaffList($goodsid, $city, $day, $time) {
        $client = new \GuzzleHttp\Client([
            // Base URI is used with relative requests
            'base_uri' => getUrl(),
            // You can set any number of default request options.
            'timeout'  => 30,
        ]);

        // 测试
//                    'goodsid' => '328', 超长钓鱼竿啦啦啦0
//                    'city' => '上海|杨浦',
//                    'day' => '2018-08-14',
//                    'time' => '10:30'
        try {
            $res = $client->request('GET', '/Supplier/Service/Scheduling/getWaiterList', [
                'query' => [
                    'goodsid' => $goodsid,
                    'city' => $city,
                    'day' => $day,
                    'time' => $time,
                ]
            ]);


            $json = (string)$res->getBody();
            $arr = json_decode($json, true);
            return $arr['data'];
        } catch (GuzzleException $e) {
            // 日志
            $log_model = new LogModel();
            $log_model->addLog($e->getMessage(), time());
            return false;
        }
    }

    public function paipan($opt, $order_sub_sn, $idlist, $svr_date, $start, $status)
    {
        $client = new \GuzzleHttp\Client([
            // Base URI is used with relative requests
            'base_uri' => getUrl(),
            // You can set any number of default request options.
            'timeout'  => 30,
        ]);
        $response = $client->request('POST', '/Supplier/Service/Scheduling/paiban_save', [
            'multipart' => [
                [
                    'name'     => 'opt',
                    'contents' => $opt
                ],
                [
                    'name'     => 'order_sub_sn',
                    'contents' => $order_sub_sn
                ],
                [
                    'name'     => 'idlist',
                    'contents' => $idlist
                ],
                [
                    'name'     => 'svr_date',
                    'contents' => $svr_date
                ],
                [
                    'name'     => 'start',
                    'contents' => $start
                ],
                [
                    'name'     => 'status',
                    'contents' => $status
                ]
            ]
        ]);
        $json = (string)$response->getBody();
        $arr = json_decode($json, true);
        return $arr['data'];
    }

    public function getOperationInfo($adminuserid, $supplierid, $key)
    {
        $msgtitle = '';
        $adminModel = new AccountModel();
        if ($adminuserid) {
            $admininfo = $adminModel->getOneAccountInfo($adminuserid);
            $msgtitle = '员工('.$admininfo['name'].')';
        } elseif ($key) {
            $aes = new GJAES();                     //format:supplierid-timestamp
            $desryptData = $aes->aes_decrypt($key, 'dongjia');
            $desryptData = explode('-', $desryptData);
            if (count($desryptData)!=2) return false;
            if ($desryptData[0] === '0') {
                $msgtitle = '超管(李杰)';
            } else{
                if ($desryptData[0] != (-$supplierid)) return false;
                $admininfo = $adminModel->getOneAccountInfo($supplierid);
                $msgtitle = '供应商('.$admininfo['name'].')';
            }

        } else {
            $msgtitle = '系统';
        }
        return $msgtitle;
    }

    public function getRecentNeedComment($jdaccount,$lasttime, $nowtime)
    {
        if (!$lasttime) $lasttime = 0;
        $orderModel = new OrderModel();
        $mainOrder = $orderModel->getMainOrderNeedComment($jdaccount,$lasttime, $nowtime);
        $chidOrder = $orderModel->getChildOrderNeedComment($jdaccount,$lasttime, $nowtime);
        if (isset($chidOrder['ishomeservice']) && $chidOrder['ishomeservice']) {          //是到家服务就选择子订单
            $temp['needCommet'] = $chidOrder;
            $temp['ischild'] = 1;
        } else {
            $temp['needCommet'] = $mainOrder;
            $temp['ischild'] = 0;
        }
        $staffs = [];
        $ordersn = '';
        $cordersn = '';
        if ($temp['ischild']) {
            $data = $temp['needCommet'];
            $ishomeservice = $data['ishomeservice'];
            $staff = $data['staff'];
            if ($ishomeservice && $staff) {
                $staff = trim($staff,'-');
                $staffs = explode('-',$staff);

            }
            $ordersn = $data['ordersn'];
            $cordersn = $data['cordersn'];
        } else {
            $data = $temp['needCommet'];
            $ordersn = $data['ordersn'];
        }
        $retrundata = [];
        $orderinfo = $orderModel->getOrderInfo($ordersn);
//        var_dump($orderinfo);exit;
//        $orderchildinfo = $this->getOrderChild($ordersn);
        $retrundata['ordersn'] = $ordersn;
        $retrundata['productname'] = $orderinfo['productname'];
        $retrundata['productpic'] = C('UPLOADURL').$orderinfo['productpic'];
        $retrundata['cordersn'] = $cordersn;
        $retrundata['staff'] = '';
        if (count($staffs)) {
            $tempwaiter = M('svr_waiter')->field('id,name,img')->where(['id'=>['in',$staffs]])->select();
            foreach ($tempwaiter as $key => $row) {
                $tempwaiter[$key]['img'] = C("UPLOADURL").$row['img'];
            }
            $retrundata['staff'] = $tempwaiter;
        }
        return $retrundata;
    }


    public function verifyKerperOrder($specid,$num, $price,$provinceid,$ciytid,$countyid = 0, $townid = 0)
    {
        $goodsModel = new GoodsModel();
        $specinfo = $goodsModel->getOneSpec($specid);
        $skuid = $specinfo['kpl_sku'];
        $kerperClass = new KerperApi();
        $cansale = $kerperClass->skuCheck($skuid);
        if (!$cansale) response('该商品暂不可售');
        $res = $kerperClass->verifyJdAddress($provinceid,$ciytid,$countyid,$townid);
        if (!$res) response('请选择完整的地址');
        $area = $provinceid.'_'.$ciytid.'_'.$countyid.'_'.$townid;
        $res = $kerperClass->hasSkuNumCanSale($skuid,$area,$num);
        if (!$res) response('商品库存不足');
        return $specinfo['kpl_sku'];
    }

    public function getJdKerperOrderTrack($jdorder)
    {
        if (!$jdorder) return false;
        $kerperClass = new KerperApi();
        $trackinfo = $kerperClass->orderTrack($jdorder);
        if ($trackinfo) {
            $trackinfo = $trackinfo['orderTrack'];
            $num = count($trackinfo);
            $trackinfo = $trackinfo[$num-1];
            $content = $trackinfo['content'];
            $expresstime = $trackinfo['msgTime'];
            $res['expressinfo'] = $content;
            $res['expresstime'] = Date("Y.m.d G:i:s",strtotime($expresstime));
            return $res;
        } else {
           return false;
        }
    }

    public function getJdKerperOrdetStatus($jdorder)
    {
        if (!$jdorder) return false;
        $kerperClass = new KerperApi();
        $res = $kerperClass->selectjdorderquery($jdorder);
        $orderstate = $res['jdOrderState'];
        return getkerperorderstatus($orderstate);
    }

    public function getjdKerperPushData($type)
    {
        if (!$type) return false;
        $kerperClass = new KerperApi();
        $res = $kerperClass->getjdKerperPushData($type);
        return $res;
    }

}