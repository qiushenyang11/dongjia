<?php
/**
 * Created by PhpStorm.
 * User: xiyou
 * Date: 2017/11/10
 * Time: 11:00
 *http://www.dservie.cn/myWeb/index.php/Operation/OperationOrder/index
 * 订单管理  :订单列表  订单详情
 */
namespace Operation\Controller;
use function GuzzleHttp\Psr7\str;
use Operation\Model\OrderModel;
use Server\KerperApi;
use Server\Order;
use Server\WeChatRedis;
use Think\Controller;
use WeChat\Model\WeChatUserModel;

class OperationOrderController extends OperationBaseController
{

    public  function  settletype()
    {    // settletype  结算类型 0不显示 1未结算 2不结算 3结算中 4已结算

        $idlist = I('idlist','');

        $idarr  = explode( ',',$idlist ) ;

        $nid = array() ;

        foreach( $idarr as $id  ){
            if( is_numeric( $id ) ){
                $nid[] = intval( $id ) ;
            }
        }

        if( count($nid) <=0 ){

            response('没有参数!'); exit;
        }
        $msg = '' ;

        $idparm =  implode( ',' , $nid ) ;
        $oModel = M("order_info");

        $nots = $oModel->query("select count(*) as zs  from order_info where id in( $idparm ) AND  (not settletype in(1,3)) ");

        if( $nots[0][zs]>0 )   $msg = $msg.'结算状态错误：'.$nots[0][zs].'个 , ' ;

        $res = $oModel->where(' id in( '.$idparm.' ) AND settletype in(1,3)')->setField('settletype' ,'4' );


        $msg = $msg.'改变结算状态：'.$res.'个' ;
        response($msg,1) ;

    }

    public  function  index()
    {
        $startTime = I('get.starttime','');
        $startTime = str_replace('+',' ', $startTime);
        $endTime = I('get.endtime', '');
        $endTime = str_replace('+', ' ', $endTime);
        $type = I('get.type', '');
        $typename = I('get.typename', '');
        $ordertype = I("get.ordertype", '');
        $paytype = I('get.paytype', '');
        $status = I('get.status', '');
        $bdname = I('get.bdname', '');
        $isexcel = I('get.isexcel','');
        $settletype = I('get.settletype',0);
        $guanjianame = I('get.guanjianame','');
        $totalprice = I('get.totalprice', '');
        $pricetype = I('get.priceType',0);
        $handletype = I('get.handletype',0);
        $p=I('get.p', 1);
        $where = [];
        if ($startTime && $endTime) {
            $where['o.addtime'] = [['gt',strtotime($startTime)],['elt',strtotime($endTime)]];
        } elseif ($startTime) {
            $where['o.addtime'] = ['gt',strtotime($startTime)];
        } elseif ($endTime) {
            $where['o.addtime']  = ['lt', strtotime($endTime)];
        }
        if ($typename && $typename != '订单类型') {
            if ($type == 1) {
                $where['o.ordersn'] = $typename;   //订单id
            } elseif ($type == 2) {                //产品名称
                $where['og.productname'] = ['like', "%$typename%"];
            } elseif ($type == 3) {               //商品名称
                $where['og.goodname'] = ['like', "%$typename%"];
            } elseif ($type == 4) {
                $where['o.username'] = ['like', "%$typename%"];
            } elseif ($type == 5) {
                $where['o.userid'] = $typename;
            } elseif ($type == 6) {
                $where['o.addressname'] = ['like', "%$typename%"];
            } elseif ($type == 7) {
                $where['og.code'] = $typename;
            } elseif ($type == 8) {
                $where['o.mobile'] = $typename;
            }
        }
        if ($pricetype == 1) {
            $where['o.totalprice'] = ['gt',0];
        } elseif ($pricetype == 2) {
            $where['o.totalprice'] = 0;
        }

        if ($ordertype) {
            $where['og.type'] = $ordertype;
        }
        if ($status == 1) {
            $where['o.status'] = ['in',[0,2000]];
        } elseif ($status == 2) {
            $where['o.status'] = ['in',[1000]];
        } elseif ($status == 3) {
            $where['o.status'] = ['in',[1001]];
        } elseif ($status == 4) {
            $where['o.status'] = ['in',[1002]];
        } elseif ($status == 5) {
            $where['o.status'] = ['in',[2001]];
        } elseif ($status == 6) {
            $where['o.status'] = ['in',[2002]];
        } elseif ($status == 7) {
            $where['o.status'] = ['in',[2003]];
        } elseif ($status == 8) {
            $where['o.status'] = ['in',[1002,1003,1004,2004,2005]];
        } elseif ($status == 9) {
            $where['o.status'] = ['in',[1900,2900]];
        } elseif ($status == 10) {
            $where['o.status'] = ['in',[1901,2901]];
        } elseif ($status == 11) {
            $where['o.status'] = ['in',[1902,2902]];
        }
        if ($bdname) {
            $where['o.bdname'] = $bdname;
        }
        if ($guanjianame) {
            $where['o.guanjianame'] = $guanjianame;
        }
        if ($handletype) {
            $where['o.handletype'] = $handletype;
        }
        if ($settletype) {
            $where['o.settletype'] = $settletype;
        }
        $orderModel = new OrderModel();
        $data=$orderModel->orderList( $p,$where,$isexcel);
        $orderClass = new Order();
        $searchStatus = $orderClass->getSearchOrder();
        $Page=$data['Page'];
        $count=$data['count'];
        $first=$Page->firstRow+1;
        $rest=($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
        $end = $first + $rest-1;
        $all=$first.'-'.$end;
        $res=$data['res'];
        $settletypes = [
            0=>'',
            1=>'未结算',
            2=>'不结算',
            3=>'结算中',
            4=>'已结算'
        ];
        if ($isexcel) {
            $exceldata = [];
            foreach ($res as $key=>$row) {
                $orderinfo = $row['orderinfo'];
                $otherdata = '';
                if ($orderinfo) {
                    $orderinfo = htmlspecialchars_decode($orderinfo);

                    $orderinfo = json_decode($orderinfo, true);

                    foreach ($orderinfo as $one) {
                        foreach ($one as $row1) {
                            if ($row1['name'] == '姓名' || $row1['name'] =='手机号') continue;
                            $otherdata.=$row1['name'].':'.$row1['value'].'|';
                        }
                    }
                    $otherdata = rtrim($otherdata,'|');
                }
                $exceldata[$key]['ordersn'] = $row['ordersn'];
                $exceldata[$key]['productname'] = $row['productname'];
                $exceldata[$key]['productid'] = $row['productid'];
                $exceldata[$key]['goodname'] = $row['goodname'];
                $exceldata[$key]['goodid'] = $row['goodid'];
                $exceldata[$key]['specname'] = $row['specname'];
                $exceldata[$key]['specid'] = $row['specid'];
                $exceldata[$key]['num'] = $row['num'];
                $exceldata[$key]['totalprice'] = $row['totalprice'];
                $exceldata[$key]['status'] = $orderClass->showOrderStatus($row['type'],$row['status'],$row['isexpress']);
                $exceldata[$key]['type'] = $row['type'] == 1 ?"服务类":"快递类";
                $exceldata[$key]['username'] = $row['username'];
                $exceldata[$key]['addressname'] = $row['addressname'];
                $exceldata[$key]['addressphone'] = $row['mobile'];
                $exceldata[$key]['guanjianame'] = $row['guanjianame'];
                $exceldata[$key]['guanjiaphone'] = $row['guanjiaphone'];
                $temp = explode('-',$row['producttype']);
                $exceldata[$key]['productone'] = $temp[0];
                $exceldata[$key]['producttwo'] = $temp[1];
                $exceldata[$key]['bdname'] = $row['bdname'];
                $exceldata[$key]['bdphone'] = $row['bdphone'];
                $temp = '';
                if ($row['paystyle'] == 1) {
                    $temp = '微信支付';
                } elseif ($row['paystyle'] == 2) {
                    $temp = '京东支付';
                }
                $exceldata[$key]['paystyle'] = $temp;
                $exceldata[$key]['paytime'] = $row['paytime']?Date('Y-m-d G:i:s', $row['paytime']):'';
                $exceldata[$key]['payrecordsn'] = $row['payrecordsn']?$row['payrecordsn']:'';
                $exceldata[$key]['couponid'] = $row['couponid'];
                $exceldata[$key]['couponname'] = $row['couponname']?$row['couponname']:'';
                $exceldata[$key]['couponprice'] = ''.floatval($row['couponmoney'])?floatval($row['couponmoney']) : '';
                $exceldata[$key]['coin'] = ''.floatval($row['coin']);
                $exceldata[$key]['coinprice'] = ''.floatval($row['coinprice']);
                //礼品卡
                $cardinfo = $row['cardinfo'];
                $cardprice = '';
                $cardid = '';
                $cardloopid = '';
                if ($cardinfo) {
                    $cardinfo = json_decode($cardinfo, true);
                    $cardprice = $cardinfo['cardprice'];
                    $cardid = $cardinfo['cardid'];
                    $cardloopid = $cardinfo['loopid'];
                }
                $exceldata[$key]['cardid'] = $cardid;
                $exceldata[$key]['cardloopid'] = $cardloopid;
                $exceldata[$key]['cardprice'] = ''.floatval($cardprice);
                $exceldata[$key]['settletype'] = $settletypes[$row['settletype']];
                $settles = $row['settles'];
                if ($settles) {
                    $settles = json_decode($settles, true);
                    $exceldata[$key]['settlename'] = $settles['settlename'];
                    if ($settles['settletype'] == 1) {
                        $exceldata[$key]['settlevalue'] = "每份结算".floatval($settles['settlevalue'])."元";
                    } elseif ($settles['settletype'] == 2) {
                        $exceldata[$key]['settlevalue'] = "收取".floatval($settles['settlevalue'])."%佣金";
                    } else {
                        $exceldata[$key]['settlevalue'] = "每份".floatval($settles['settlevalue'])."元佣金";
                    }
                    $exceldata[$key]['yongjin'] = ''.floatval($row['totalprice'] - $settles['allvalue']);
                    $exceldata[$key]['allvalue'] = ''.floatval($settles['allvalue']);
                } else {
                    $exceldata[$key]['settlename'] = '';
                    $exceldata[$key]['settlevalue'] = '';
                    $exceldata[$key]['yongjin'] = '';
                    $exceldata[$key]['allvalue'] = '';
                }
                $exceldata[$key]['addtime'] = Date("Y-m-d G:i:s",$row['addtime']);
                $exceldata[$key]['otherdata'] = $otherdata;
                $exceldata[$key]['sku_remark'] = $row['sku_remark'] ; //sku备注
            }
            $this->excel($exceldata);
        } else {
            $this->assign('settletype',$settletype);
            $this->assign('handletype', $handletype);
            $this->assign('pricetype', $pricetype);
            $this->assign('totalprice', $totalprice);
            $this->assign('searchStatus', $searchStatus);
            $this->assign('starttime',$startTime);
            $this->assign('endtime',$endTime);
            $this->assign('type',$type);
            $this->assign('typename',$typename);
            $this->assign('ordertype',$ordertype);
            $this->assign('paytype',$paytype);
            $this->assign('status',$status);
            $this->assign('bdname',$bdname);
            $this->assign('guanjianame',$guanjianame);
            $this->assign('result',$res);
            $this->assign('page',$Page->show());
            $this->assign('nowPage',$p);
            $this->assign('totalPages',$Page->totalPages);
            $this->assign("count",$count);
            $this->assign('all',$all);
            $this->display();
        }
    }

    public function excelExport($data=[],$filename="默认列表"){
        vendor("excel.PHPExcel");
        $objPHPExcel=new \PHPExcel();
        if(empty($data)){
            return;
        }
        $pColumnIndex = 0;

        foreach ($data as $key=>$row){
            $num=$key+1;

            foreach ($row as $row1){
                if($pColumnIndex<26){
                    $charIndex=chr(65+$pColumnIndex);
                }else if($pColumnIndex<702){
                    $charIndex=chr(64 + ($pColumnIndex / 26)) . chr(65 + $pColumnIndex % 26);
                }else{
                    $charIndex=chr(64 + (($pColumnIndex - 26) / 676)) . chr(65 + ((($pColumnIndex - 26) % 676) / 26)) . chr(65 + $pColumnIndex % 26);
                }
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue($charIndex.$num," ".$row1);
                $pColumnIndex++;
            }
            $pColumnIndex=0;

        }
        $filename=$filename."(".Date("Y-m-d").")".".xls";
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.$filename.'');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        die;
    }

    /*订单列表页excel导出*/
    private function excel($res){
        if (empty($res)) return false;
        $excelData = $res;
        $arr=["订单ID","产品名称","产品ID","商品名称","商品ID","规格名称","规格ID","购买份数","总价","订单状态","订单类型","下单用户","下单人信息","下单人手机号","管家名称","管家电话","一级分类","二级分类","BD名称","BD电话","支付方式","支付时间","支付流水号","优惠券id","优惠券名称","优惠券抵扣金额","使用银子","银子抵扣金额","礼品卡卡号","礼品卡批次","礼品卡抵扣金额","结算状态","结算方式","结算规则","佣金金额","应结金额","下单时间",'其他填单信息'];
        array_unshift($excelData, $arr);
        $this->excelExport($excelData,'订单列表数据');
    }


    /**
     * @breif 订单操作
     * @param operationType    1客服取消待支付订单订单(服务类) 2客服确认订单未通过 3客服取消预订成功订单
     *                          4客服取消待支付订单(快递类) 5客服取消待发货免费订单 6客服取消已签收免费订单
     *                          7客服确认订单预约成功（服务类）8客服确认已完成订单(服务类) 9客服确认已发货（快递类）
     *                          10 客服确认已签收 11客服申请退款预约成功订单（服务）
     *                          12客服申请退款待发货订单（快递类） 13客服确认退款已签收订单（服务）
     *                          14确认预约失败退款（服务） 15确认预约成功后的退款（服务)
     *                          16确认退款（用户取消服务类已支付退款） 17确认待发货退款（快递） 18确认已签收退款（快递）19确认退款(用户取消快递类已支付退款)
     *
     *
     */
    public function orderOperation()
    {
        $operationType = I('post.operationType',0);
        $userid = session('adminuserid');                    //管理人员userid
        $orderClass = new Order();
        $supplierid = '';
        $key = '';
        if ($userid) {                              //提供供应商调用
            $supplierid = I('post.supplierid',0);           //提供供应商接口记录用户  供应商id为负数
            $key = I('post.key', '');
        }
        $msg = $orderClass->getOperationInfo($userid,$supplierid,$key);
        if ($supplierid <0) $userid = $supplierid;
        if ($msg === false) response('无权限操作');
        $ordersn = I('post.ordersn', '');
        $expressno = I('post.expressno', '');
        $refundreason = I('post.refundreason','');
        $refundinfo = I('post.refundinfo', '');
        $wechatRedis = new WeChatRedis();
        $key = 'orderOpeation'.$ordersn;
        $time = 20;
        $haslock = $wechatRedis->setLock($key, $time, 1);
        if ($haslock) {

            if ($operationType == 1) {
                $res = $orderClass->cancelOrder($ordersn,$userid,$msg, 1);
            } elseif ($operationType == 2) {
                $res = $orderClass->confirmOrder($ordersn, $userid,$msg, 0,$refundreason,$refundinfo);
            } elseif ($operationType == 3) {
                $res = $orderClass->cancelOrder($ordersn, $userid, $msg, 2);
            } elseif ($operationType == 4) {
                $res = $orderClass->cancelOrder($ordersn, $userid,$msg, 3);
            } elseif ($operationType == 5) {
                $res = $orderClass->cancelOrder($ordersn, $userid,$msg, 4);
            } elseif ($operationType == 6) {
                $res = $orderClass->cancelOrder($ordersn, $userid,$msg, 5);
            } elseif ($operationType == 7) {
                $res = $orderClass->confirmOrder($ordersn, $userid,$msg, 1);
            } elseif ($operationType == 8) {
                $res = $orderClass->confirmFinishOrder($ordersn, $userid,$msg);
            } elseif ($operationType == 9) {
                $res = $orderClass->confirmShippingOrder($ordersn, $userid, $expressno);
            } elseif ($operationType == 10) {
                $res = $orderClass->confirmSignOrder($ordersn, $userid);
            } elseif ($operationType == 11) {
                $res = $orderClass->applyRefunOrder($ordersn, $userid,$msg ,1, $refundreason, $refundinfo);
            } elseif ($operationType == 12) {
                $res = $orderClass->applyRefunOrder($ordersn, $userid,$msg, 2);
            } elseif ($operationType == 13) {
                $res = $orderClass->applyRefunOrder($ordersn, $userid,$msg,3);
            } elseif ($operationType == 14) {
                $res = $orderClass->confirmRefundOrder($ordersn, $userid,$msg, 1);
            } elseif ($operationType == 15) {
                $res = $orderClass->confirmRefundOrder($ordersn, $userid, $msg,2);
            } elseif ($operationType == 16) {
                $res = $orderClass->confirmRefundOrder($ordersn, $userid,$msg, 3);
            }  elseif ($operationType == 17) {
                $res = $orderClass->confirmRefundOrder($ordersn, $userid,$msg, 4);
            } elseif ($operationType == 18) {
                $res = $orderClass->confirmRefundOrder($ordersn, $userid,$msg, 5);
            } elseif ($operationType == 19) {
                $res = $orderClass->confirmRefundOrder($ordersn, $userid, $msg,6);
            } else {
                response('参数异常');
            }
            $wechatRedis->delLock($key);
            $res ? response('操作成功',1):response('操作失败');
        } else {
            response('订单状态已更新');
        }


    }

    public function getAllBdAndGuanjia()
    {


        $userModel = new WeChatUserModel();
        $BDInfo =$userModel->getAllBD();
        $guanJiaInfo=$userModel->getAllGuanJia();
        response('获取成功', 1, ['bd'=>$BDInfo,'guanjia'=>$guanJiaInfo]);
    }
    public  function  orderDetail()
    {
        $orderid=I("get.orderid",'');
        $orderModel = new OrderModel();
        $data= $orderModel->orderDetails($orderid);
        $orderinfo = $data['orderinfo']['orderinfo'];
        $cardinfo = $data['orderinfo']['cardinfo'];
        $ordersn = $data['orderinfo']['ordersn'];
        $settles = $data['orderinfo']['settles'];
        $ishomeservice = $data['orderinfo']['ishomeservice'];
        $jdkerperorder = $data['orderinfo']['jdkerperorder'];
        $orderchildinfo = $orderModel->getOrderChild($ordersn);
        if ($ishomeservice) {
            $staffdata = [];
            foreach ($orderchildinfo as $key => $row) {
                $staffdata[$key] = '';
                $servicetimetemp = $row['servicetime'];

                if($servicetimetemp){
                    $servicetimetemp = Date("Y-m-d G:i",$servicetimetemp);
                }else{
                    $servicetimetemp = '未设置';
                }
//                dump($servicetimetemp);
                $staffnametemp = $row['staffname'];
                $status = $row['status'];
                if ($status == 0) {
                    $status = '未完成';
                } elseif ($status ==2000) {
                    $status = '已完成';
                } else {
                    $status = '未知';
                }
                if (!$staffnametemp) {
                    $staffnametemp = '未设置';
                }  else {
                    $staffnametemp = trim($staffnametemp, '-');
                    $staffnametemp = str_replace('-',',',$staffnametemp);
                }
                $staffdata[$key]['id'] = $key+1;
                $staffdata[$key]['staff'] = $staffnametemp;
                $staffdata[$key]['time'] = $servicetimetemp;
                $staffdata[$key]['status'] = $status;
            }
        }
        if ($cardinfo) {
            $cardinfo = json_decode($cardinfo, true);
            $cardinfo['cardprice'] = floatval($cardinfo['cardprice']);
        }

        $orderinfo = htmlspecialchars_decode($orderinfo);
        if (!$orderinfo) {
            $orderinfo[0]=[
                [
                    'name'=>'姓名',
                    'type'=>1,
                    'value'=>$data['orderinfo']['addressname']
                ],
                [
                    'name'=>'手机号',
                    'type'=>1,
                    'value'=>$data['orderinfo']['mobile']
                ]
            ];
            $orderinfo = json_encode($orderinfo, true);
        }
        $data['orderinfo']['coin'] = floatval($data['orderinfo']['coin']);
        $data['orderinfo']['totalprice'] = floatval($data['orderinfo']['totalprice']);
        $data['orderinfo']['price'] = floatval($data['orderinfo']['price']);
        $data['orderinfo']['couponmoney'] = floatval($data['orderinfo']['couponmoney']);
        $data['orderinfo']['payrealprice'] = floatval($data['orderinfo']['payrealprice']);
        if (!$data['orderinfo']['payrealprice']) {                                              //兼容之前订单
            if (!$data['orderinfo']['coin'] && !$data['orderinfo']['couponmoney'] && !$data['orderinfo']['cardinfo']) $data['orderinfo']['payrealprice'] = $data['orderinfo']['totalprice'];
        }
        if ($data['orderinfo']['coin'] >0) {
            $data['orderinfo']['coinprice'] = floatval($data['orderinfo']['coinprice']);
        } else {
            $data['orderinfo']['coinprice'] = 0;
        }
        $orderClass = new Order();
        $orderinfo = $orderClass->getOperationOrderInfoFormat($orderinfo);
        //dump($data['res']);exit;
        $res = $data['orderinfo'];
        $orderClass = new Order();
        $res['tempstatus'] = $res['status'];
        $res['status'] = $orderClass->showOrderStatus($res['type'],$res['status'],$res['isexpress']);
        $res['paytime'] = Date('Y.m.d G:i:s', $res['paytime']);
        $record = $orderModel->getOrderRecord($orderid);
        foreach ($record as $key => $row) {
            $record[$key]['addtime'] = Date("Y.m.d G:i:s", $row['addtime']);
        }
        if ($settles) $settles= json_decode($settles,true);
        //开普勒
        $jdorderinfo = '';
        if ($jdkerperorder) {
            $ordertrackinfo = $orderClass->getJdKerperOrderTrack($jdkerperorder);
            $jdorderinfo['track'] = $ordertrackinfo['expressinfo'].'('.$ordertrackinfo['expresstime'].')';
            $jdorderinfo['jdorder'] = $jdkerperorder;
            $jdorderinfo['jdstatus'] = $orderClass->getJdKerperOrdetStatus($jdkerperorder);
        }
        $this->assign('jdorderinfo',$jdorderinfo);
        $this->assign('settles', $settles);
        $this->assign('staffdata',$staffdata);
//        dump($staffdata);
        $this->assign('cardinfo', $cardinfo);
        $this->assign('txtdata',$orderinfo['txtdata']);
        $this->assign('imagedata',$orderinfo['imagedata']);
        $this->assign('record', $record);
        $this->assign('res',$res);
        $this->assign('descinfo',$data['descinfo']);
        $this->display();
    }


    public  function  saveDesc(){
        $orderid=I("post.orderid",'');
        $descinfo=I("post.descinfo",'');
        if(!($orderid && $descinfo))response("提交参数错误");
        $data['orderid']=$orderid;
        $data['descinfo']=$descinfo;
        $data['addtime'] = time();
        $data['name'] = session('username');
        $orderModel = new OrderModel();
        $res=$orderModel->addDesc($data);
        $res ? response('添加成功', 1):response("添加失败");


    }
    public function handleOrder()
    {
        $ordersn = I('post.ordersn','');
        $orderModel = new OrderModel();
        $res = $orderModel->handleOrder($ordersn);
        if ($res) {
            response('处理成功',1);
        } else {
            response('初始失败');
        }
    }

    public function setSettleType()
    {
        $ordersn = I('post.ordersn','');
        $settleType = I('post.settletype',1);
        if (!$ordersn) response('参数错误');
        $orderClass = new Order();
        $res = $orderClass->setSettleType($ordersn,$settleType);
        $res ? response('设置成功',1):response('设置失败');
    }

    public function handelOrder()
    {
        A("CouponApi/Coupon")->verificationOfCoupon(18);
        $orderModel = new OrderModel();
        $res1 = $orderModel->addOrderRecord(3271, '2019022510249989',1001,'', 16,'员工(mary)'.':确认订单通过,修改订单状态为"预订成功"',1);
        $where['ordersn'] = '2019022510249989';
        $data['paytime'] = strtotime('2019-02-25 13:38:44');
        $data['paystatus'] = 1;
        $data['settletype'] = 2;
        $data['paystyle'] = 2;
        $data['confirmtime'] = time();
        $data['payrecordsn'] = '2019022510249989_1551073104';
        $data['status'] = 1001;
        $res2 = M('order_info')->where($where)->save($data);
        var_dump($res1);
        var_dump($res2);
    }
}