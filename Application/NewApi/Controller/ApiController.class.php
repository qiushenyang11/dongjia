<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/11/23
 * Time: 9:59
 */

namespace NewApi\Controller;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use NewApi\Model\AgencyModel;
use Operation\Model\ArticleModel;
use Operation\Model\OrderModel;
use Org\Util\EasyWeChat;
use Server\Banner;
use Server\Category;
use Server\Comment;
use Server\FoucusService;
use Server\Goods;
use Server\JdApi;
use Server\KerperApi;
use Server\Order;
use Server\SmsMessage;
use Server\WeChatRedis;
use Think\Controller;
use WeChat\Controller\WeChatBaseController;
use WeChat\Model\AddressModel;
use WeChat\Model\GoodsModel;
use WeChat\Model\GuanJiaModel;
use WeChat\Model\WeChatUserModel;

class ApiController extends Controller
{


    public function _initialize()
    {
        header('Content-Type:application/json; charset=utf-8');
        /*        session('jdaccount','oE19s0uR75ZiRrmfQNHUtAfwjJ8o_test'); // 正式后删除
                session('userid', 25);
                session('openid', 'oE19s0uR75ZiRrmfQNHUtAfwjJ8o');*/

    }

    public function crossAllow($url = '')
    {

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: *");
        header("Access-Control-Allow-Headers: Content-Type,Access-Token");
        header("Access-Control-Expose-Headers: *");

    }
    /**********************首页相关*********************/

    //兼容原始数据使用
    //将管家供应商信息导入到供应商列表
    public function getAllSuppliers()
    {
        $where['supplier'] = ['neq',''];
        $data = M('guanjia')->field('id as guanjiaid,supplier,suppliershort,customertype,customerphone,userid,guanjiaphone as contactphone')->where($where)->select();
        $res = M('supplier')->addAll($data);
        var_dump($res);die;
    }

    //将管家二级分类添加到管家title中
    public function getSaveGuanjiaTitle()
    {
        $res = M('guanjia')->field('id,guanjiafenlei')->select();
        foreach ($res as $row) {
            $id = $row['id'];
            $fenlei = explode('-',$row['guanjiafenlei'])[1];
            $fenlei.='管家';
            $reulst = M('guanjia')->where(['id'=>$id])->save(['title'=>$fenlei]);
            dump($reulst);
        }
    }

    //将供应商和产品绑定
    public function productBindGuanjiaAndSupplier()
    {
        $res = M('product as p')
            ->join('__SUPPLIER__ as s ON p.guanjiaid = s.guanjiaid')
            ->field('p.id as productid,s.id as supplierid')
            ->select();
        foreach ($res as $row) {
            $id = $row['productid'];
            $supplierid = $row['supplierid'];
            $res = M('product')->where(['id'=>$id])->save(['supplierid'=>$supplierid]);
            var_dump($res);
        }
    }

    public function orderinfoBindSupplier()
    {
        $res =M('order_good as g')->join('__PRODUCT__ as p ON g.productid = p.id')->field('g.orderid as id, p.supplierid')->select();
        $j = 0;
        foreach ($res as $row) {
            $r = M('order_info')->where(['id'=>$row['id']])->save(['supplierid'=>$row['supplierid']]);
            if ($r) $j++;
        }
        var_dump($j);
    }

    public function getAjaxProduct()
    {
        $this->crossAllow();
        $content = I('get.content','');
        $yiji = I('get.yiji','');
        $erji = I('get.erji','');
        $page = I('get.pageNum', 1);
        $where = [];
        $cate = new Category();
        $where['p.type'] = 1;
        if ($content) {
            if (is_numeric($content)) {
                $where['p.id'] = $content;
            } else {
                $where['p.name'] = ['like', '%'.$content.'%'];
            }
        }
        $yijiname = '';
        $erjiname = '';
        if ($yiji) {
            $cates  = $cate->getCategorynameById($yiji);
            $yijiname = $cates[0]['name'];
        }
        if ($erji) {
            $cates =$cate->getCategorynameById($erji);
            $erjiname = $cates[1]['name'];
        }
        if ($yijiname && $erjiname) {
            $where['p.categoryname'] = $yijiname.'-'.$erjiname;
        } elseif ($yijiname) {
            $where['p.categoryname'] = ['like',"$yijiname-%"];
        }
        $limit = 3;
        $list = M('product as p')
                ->join('__SUPPLIER__ as s ON p.supplierid = s.id','left')
                ->field('p.id,p.name,p.categoryname,s.suppliershort,p.status,p.facepic')
                ->where($where)
                ->page($page)
                ->limit($limit)
                ->select();
        foreach ($list as $key => $row) {
            $list[$key]['facepic'] = C('UPLOADURL').$row['facepic'];
        }
        $total = M('product as p')
            ->join('__SUPPLIER__ as s ON p.supplierid = s.id','left')
            ->field('p.id,p.name,p.categoryname,s.suppliershort,p.status,p.facepic')
            ->where($where)
            ->count();
        foreach ($total as $key => $row) {
            $total[$key]['facepic'] = C("UPLOADURL").$row['facepic'];
        }
        $isnull = 0;
        if (count($list) > $limit) {
            $isnull = 1;
        }
        $totalpage = ceil($total/$limit);
        $data['data_content'] = $list;
        $data['totalItem'] = $total;
        $data['pageSize'] = $limit;
        $data['totalPage'] = $totalpage;
        $data['isnull'] = $isnull;
        echo json_encode($data);
       // response('获取成功',1, $data);
    }

    public function getCategory()
    {
        $this->crossAllow();
        $pid = I('get.pid', 0);
        $cateClass = new Category();
       $list = $cateClass->getCategorys(2,$pid);
       response('获取成功',1,$list);
    }

    /**
     * @添加代办产品信息
     */
    public function addAgencyProduct()
    {
        $this->crossAllow();
        $productname = I('post.productname','');
        $specname = I('post.specname','');
        $leveloneid = I('post.leveloneid', '');
        $leveltwoid = I('post.leveltwoid', '');
        $categroyname = I('post.categoryname', '');
        $type = I('post.type', 1);
        $price = I('post.price', 0);
        $refundcondition = I('post.refundcondition', '');
        $data['productname'] = $productname;
        $data['specname'] = $specname;
        $data['leveloneid'] = $leveloneid;
        $data['leveltwoid'] = $leveltwoid;
        $data['categoryname'] = $categroyname;
        $data['type'] = $type;
        $data['price'] = $price;
        $data['refundcondition'] = $refundcondition;
        $agencyModel = new AgencyModel();
        $res = $agencyModel->addProduct($data);
        $res ?response('添加成功',1, $res) : response('添加失败');
    }

    public function agencyProdctCanuse()
    {
        $id = I('post.id', 0);
        $agencyModel = new AgencyModel();
        $info = $agencyModel->getProduct($id);
        $addtime = $info['addtime'];
        $addtime = intval($addtime);
        if (time()-$addtime>3*3600){
            response('链接已经失效');
        } else {
            response('可使用', 1);
        }

    }


    public function testajax()
    {

            $pageNum = I('pageNum');
            $totalItem = M('label')->count();
            $pageSize = 6;
            $totalPage = ceil($totalItem/$pageSize);

            $startItem = ($pageNum-1) * $pageSize;
            $arr['totalItem'] = $totalItem;
            $arr['pageSize'] = $pageSize;
            $arr['totalPage'] = $totalPage;

            $labels = M('label')->limit($startItem,$pageSize)->select();

            foreach($labels as $lab) {
                $arr['data_content'][] = $lab;
            }

            echo json_encode($arr);

    }
    //供供应商调用
    public function orderOperation()
    {
        $jsonData = file_get_contents('php://input', 'r');
        $jsonData = json_decode($jsonData, true);
//        dump($jsonData);die;
//        $operationType = I('post.operationType',0);
        $operationType = $jsonData['operationType'];
        if (!$operationType) {
            $operationType = 0;
        }
        $orderClass = new Order();
//        $supplierid = I('post.supplierid',0);           //提供供应商接口记录用户  供应商id为负数
        $supplierid = $jsonData['supplierid'];           //提供供应商接口记录用户  供应商id为负数
        if (!$supplierid) {
            $supplierid = 0;
        }
//        $key = I('post.key', '');
        $key = $jsonData['key'];
        if (!$key) response('无权限操作');
        $msg = $orderClass->getOperationInfo('',$supplierid,$key);
        if ($supplierid <0) $userid = $supplierid;
        if ($msg === false) response('无权限操作');
//        $ordersn = I('post.ordersn', '');
        $ordersn = $jsonData['ordersn'];
//        $expressno = I('post.expressno', '');
        $expressno = isset($jsonData['expressno'])?$jsonData['expressno'] : '';
//        $refundreason = I('post.refundreason','');
        $refundreason = $jsonData['refundreason'];
//        $refundinfo = I('post.refundinfo', '');
        $refundinfo = $jsonData['refundinfo'];
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
                $res = $orderClass->confirmOrder($ordersn, $userid,$msg, 1,'','',$expressno);
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
            } elseif ($operationType == 20) {
                $res = $orderClass->finishChildOrdersn($ordersn,$userid,$msg);         //注: $ordersn 为子订单号
            } else {
                response('参数异常');
            }
            $wechatRedis->delLock($key);
            $res ? response('操作成功',1):response('操作失败');
        } else {
            response('订单状态已更新');
        }


    }


    /**
     * @breif 提供供应商处理状态
     * @param  ordersn 订单号
     * @param settletype 1设置为待处理状态 2设置为已处理状态
     */
    public function setSettleType()
    {
        $jsonData = file_get_contents('php://input', 'r');
        $jsonData = json_decode($jsonData, true);
        $ordersn = $jsonData['ordersn'];
        $settleType = $jsonData['settletype'];
        if (!$ordersn) response('参数错误');
        $orderClass = new Order();
        $res = $orderClass->setHandleType($ordersn,$settleType);
        $res ? response('设置成功',1):response('设置失败');
    }

    public function sendSmsCode()
    {
        $phone = I('get.phone','');
        $code = I('get.code','');
        $key = I('get.key','');
        if (md5($phone.'dongjia') != $key) response('验证失败');
        $smsClass = new SmsMessage();
        $res = $smsClass->sendSmsCode($phone, $code);
        $res ? response('发送成功',1):response('发送失败');
    }

    /**
     * 保险api监控短信预警
     */
    public function sendApiMonitorMsg()
    {
        $msg = I('get.msg','');
        $phone = I('get.phone', '');
        $key = I('get.key', '');
        if (md5($msg.'Dongjia@') != $key) response('Key validation failed');
        $smsClass = new SmsMessage();
        $res = $smsClass->sendApiMonitorMsg($phone, $msg);
        $res ? response('Success',1):response('Failed');
    }

    /**
     * @breif 获取京东开普勒地址
     *
     */
    public function  getJdAddress()
     {
         $parentid = I("get.parentid", 0);
         $level = I('get.level', 1);  //1省  2市 3区 4镇
         $kerperClass = new KerperApi();
         $address = $kerperClass->getJdAddress($level, $parentid);
         $data = [];
         foreach ($address as $name => $id)
         {
             $data[] =[
               'id' =>$id,
                 'name'=>$name
             ];
         }
         response('获取成功', 1, $data);
     }

    public function getCommonAddress()
    {
        $parentid = I("get.parentid", 0);
        $servicety = I('get.servicety','');
        $mainids = [];
        if ($parentid) {
            $where['id'] = ['elt',3799];
            $where['parentareaid'] = $parentid;
            $mainids = M('areanew')->where($where)->field('id,areaname as name')->select();
            if (!$mainids){
                $mainids = [];
            }
        } else {
            $isall = true;
            if ($servicety) {
                $isall = false;
                $servicety = explode('|',$servicety);
                foreach ($servicety as $row) {
                    $temp = explode(',',$row);
                    if ($temp[1] =='全国') {
                        $isall = true;
                    }
                }

            }
            if (!$isall) {
                foreach ($servicety as $row) {
                    $temp = explode(',', $row);
                    $areaids[] = intval($temp[0]);
                }
                $where['id'] = ['in',$areaids];
                $info = M('areanew')->where($where)->field('id,areaname,parentareaid,level')->select();

                $twoids = [];
                $threeids = [];
                foreach ($info as $row) {
                    if ($row['level'] == 1) {
                        $mainids[] = [
                            'id'=>$row['id'],
                            'name'=>$row['areaname']
                        ];
                    } elseif ($row['level'] == 2) {
                        $twoids[] = $row['parentareaid'];

                    } elseif ($row['level'] == 3) {
                        $threeids[] = $row['parentareaid'];
                    }
                }
                if ($twoids) {
                    unset($where);
                    $where['id'] = ['in',$twoids];
                    $info1 = M('areanew')->where($where)->field('id,areaname as name')->select();
                    $mainids = array_merge($mainids,$info1);
                }
                if ($threeids) {
                    unset($where);
                    $where['a.id'] = ['in',$threeids];
                    $info2 = M('areanew as a')->field('an.id,an.areaname as name')->join('__AREANEW__ as an ON a.parentareaid = an.id')->where($where)->select();
                    $mainids = array_merge($mainids,$info2);
                }
                foreach ($mainids as $k=> $row) {
                    $mainids[$k] = implode(',',$row);
                }
                $mainids = array_unique($mainids);
                foreach ($mainids as $k =>$row) {
                    $temp = explode(',',$row);
                    $mainids[$k] = [
                      'id'=>$temp[0],
                      'name'=>$temp[1]
                    ];
                }

            } else {
                $where['id'] = ['elt',3799];
                $where['level'] = 1;
                $mainids = M('areanew')->where($where)->field('id,areaname as name')->select();
            }
            if (!$mainids) $mainids = [];
        }
        response('获取成功',1,$mainids);
    }

     public function getJdKerperQuery()
     {
         $jdorder = I('get.jdOrderId','');
         if (!$jdorder) response('订单异常');
         $kerperClass = new KerperApi();
         $res = $kerperClass->jdOrderQuery($jdorder);
         var_dump($res);
     }

     public function getJdKerperQueryByOrdersn()
     {
         $ordersn = I('get.ordersn','');
         if (!$ordersn) response('订单异常');
         $kerperClass = new KerperApi();
         $res = $kerperClass->jdOrderQueryByOrderSn($ordersn);
         var_dump($res);
     }

    public function getbalanceprice()
    {
        $kerperClass = new KerperApi();
        $res = $kerperClass->getbalanceprice();
        var_dump($res);
    }

     public function cancelKerperOrder()
     {
         $jdorder = I('get.jdOrderId','');
         if (!$jdorder) response('订单异常');
         $kerperClass = new KerperApi();
         $res = $kerperClass->cancelKerperOrder($jdorder);
         var_dump($res);
     }

    public function getOrderTrack()
    {
        $jdorder = I('get.jdOrderId','');
        if (!$jdorder) response('订单异常');
        $kerperClass = new KerperApi();
        $res = $kerperClass->orderTrack($jdorder);
        var_dump($res);
    }


     public function canAfterSale()
     {
         //$orderClass = new Order();
         //$orderClass->verifyKerperOrder(536,3,1000,1,2812,51130,0);
         $ordersn = I('post.ordersn','');
         $orderModel = new OrderModel();
         $orderinfo = $orderModel->getOrderInfo($ordersn);
         $jdkerperorder = $orderinfo['jdkerperorder'];
         $skuid = $orderinfo['skuid'];
         $kerperClass = new KerperApi();
         $num = $kerperClass->afterSaleAvailableNumber($jdkerperorder, $skuid);
         if (!$num) {
             response('不可以提交售后服务');
         }
         $saletype = $kerperClass->afterSaleAvailableRefund($jdkerperorder, $skuid);
         if (!$saletype){
             response('该商品不支持售后服务');
         }
         $data = [];
         $message = '可支持的售后类型有:';
         foreach ($saletype as $row) {
             $message .= $row['name'].'('.$row['type'].'),';
             $data['saletype'][] = [
               'type' => $row['code'],
               'name' => $row['name']
             ];
         }

         $returnWay = $kerperClass->afterSaleReturnJdWay($jdkerperorder, $skuid);

         foreach ($returnWay as $row) {
             $message.=$row['name'].'('.$row['code'].'),';
             $data['returnway'][] = [
               'code' => $row['code'],
               'name' => $row['name']
             ];
         }
/*         $data['saletype']=[
             ['type'=>10,'name'=>'退货'],
             ['type'=>20,'name'=>'换货'],
             ['type'=>30,'name'=>'维修'],
         ];
         $data['returnway'] = [
             ['code'=>4,'name'=>'上门取件'],
             ['code'=>40,'name'=>'客户发货'],
             ['code'=>7,'name'=>'客户送货'],
         ];*/
         $data['jdkerperorder'] = $orderinfo['jdkerperorder'];
         $data['skuid'] = $orderinfo['skuid'];
         response('获取成功',1 ,$data);
     }

     public function aftersaleApply()
     {
        $ordersn = I('ordersn','');
        $customerExpect = I("customerExpect",'');
        $questionDesc = I('questionDesc','');
        $isNeedDetectionReport = I('isNeedDetectionReport', false);
        $questionPic = I('questionPic', '');
        $isHasPackage = I('isHasPackage', false);
        $packageDesc = I('packageDesc', '');
        $customerContactName = I('customerContactName', '');
        $customerTel = I('customerTel', '');
        $customerMobilePhone = I('customerMobilePhone', '');
        $customerEmail = I('customerEmail', '');
        $customerPostcode = I('customerPostcode', '');

        $pickwareType = I('pickwareType','');
        $pickwareProvince = I('pickwareProvince','');
        $pickwareCity = I('pickwareCity','');
        $pickwareCounty = I('pickwareCounty','');
        $pickwareVillage = I('pickwareVillage','');
        $pickwareAddress = I('pickwareAddress','');

        $returnwareType = I('returnwareType','');
        $returnwareProvince = I('returnwareProvince','');
        $returnwareCity = I('returnwareCity','');
        $returnwareCounty = I('returnwareCounty','');
        $returnwareVillage = I('returnwareVillage','');
        $returnwareAddress = I('returnwareAddress','');
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $jdOrderId = $orderinfo['jdkerperorder'];
        $skuId = $orderinfo['skuid'];
        $skuNum = $orderinfo['num'];
        if ($pickwareType != 4) {
            $kerperaddressid = $orderinfo['kerperaddressid'];
            $kerperaddressid = explode('_',$kerperaddressid);
            $pickwareProvince = $kerperaddressid[0];
            $pickwareCity = $kerperaddressid[1];
            $pickwareCounty = $kerperaddressid[2];
            $pickwareVillage = $kerperaddressid[3];
            $orderinfos = $orderinfo['orderinfo'];
            $orderinfos = htmlspecialchars_decode($orderinfos);

            $orderinfos = json_decode($orderinfos, true);
            $orderinfos = $orderinfos[0];
            foreach ($orderinfos as $row) {
                if ($row['name'] == '地址') {
                    $pickwareAddress = $row['value'];
                    break;
                }
            }

        }
         $pickwareAddress = '明天广场9号楼';
         $data['jdOrderId'] = $jdOrderId;
         $data['customerExpect'] = intval($customerExpect);
         $data['questionDesc'] = $questionDesc;
         $data['isNeedDetectionReport'] = $isNeedDetectionReport ? true : false;
         $data['questionPic'] = $questionPic;
         $data['isHasPackage'] = $isHasPackage ? true : false;
         $data['packageDesc'] = $packageDesc ? $packageDesc : 0;

         $asCustomerDto['customerContactName'] = $customerContactName;
         $asCustomerDto['customerTel'] = $customerTel;
         $asCustomerDto['customerMobilePhone'] = $customerMobilePhone;
         $asCustomerDto['customerEmail'] = $customerEmail;
         $asCustomerDto['customerPostcode'] = $customerPostcode;
         $data['asCustomerDto'] = $asCustomerDto;

         $asPickwareDto['pickwareType'] = intval($pickwareType);
         $asPickwareDto['pickwareProvince'] = intval($pickwareProvince);
         $asPickwareDto['pickwareCity'] = intval($pickwareCity);
         $asPickwareDto['pickwareCounty'] = intval($pickwareCounty);
         $asPickwareDto['pickwareVillage'] = intval($pickwareVillage);
         $asPickwareDto['pickwareAddress'] = $pickwareAddress;
         $data['asPickwareDto'] = $asPickwareDto;

         $asReturnwareDto['returnwareType'] = $returnwareType ? intval($returnwareType) :0;
         $asReturnwareDto['returnwareProvince'] = $returnwareProvince ? intval($returnwareProvince) : 0;
         $asReturnwareDto['returnwareCity'] = $returnwareCity ? intval($returnwareCity) :0;
         $asReturnwareDto['returnwareCounty'] = $returnwareCounty ? intval($returnwareCounty) : 0;
         $asReturnwareDto['returnwareVillage'] = $returnwareVillage ? intval($returnwareVillage) : 0;
         $asReturnwareDto['returnwareAddress'] = $returnwareAddress ? $returnwareAddress : '';
         $data['asReturnwareDto'] = $asReturnwareDto;

         $asDetailDto['skuId'] = intval($skuId);
         $asDetailDto['skuNum'] = intval($skuNum);
         $data['asDetailDto'] = $asDetailDto;
         $kerperClass = new KerperApi();
         $res = $kerperClass->afterSaleApply($data);
         $res ? response('退货成功',1):response('退货失败');

     }

     public function getBaoXianTest()
     {
         $url = 'http://g.jsf.jd.test/com.jd.baoxian.trade.newproductlistservice.NewProductService/jr_bx_newproduct/searchProductInfo/5299';
        $client = new Client();
        try {
            $param = [['productCode'=>'15800001','sourceType'=>'mobile']];
            $param = json_encode($param);
            $response = $client->request('post',$url,[
                'body'=>$param
            ]);
            var_dump(\GuzzleHttp\json_decode($response->getBody(),true));
        } catch (BadResponseException $e) {
            echo $e->getMessage();
        }


     }

    /**
     * orderInfoDto------------------
     * @param orderType 订单类型 1000 健康 2000 旅行 3000 意外 4000 车险 5000 服务 7000 财产 8000 人寿
     * @param orderStatus 订单状态 0 订单创建 1 待支付 2 取消 4 支付完成 3 完成 6 车险失算 12 已退保 20 退款中 22 退款成功 29 服务单已提交 30 待录入受检人 31 已录入受检人
     * @param  orderStatusTime 订单状态最新更改时间
     * @param  orderCreatedTime 下单时间
     * @param  orderCreatedTime 下单时间
     * @param  orderCancelReason 订单取消原因
     * @param  buyerType 买家账号类型 JD_PIN
     * @param  buyerAccount 买家账号
     * @param  buyerNickName 买家昵称
     * @param  buyerPhone 买家手机号
     * @param  insuranceCompanyCode 保险公司编号          需要给到
     * @param  insuranceCompanyName 保险公司名称
     * @param  insuranceCompanyShortname 保险公司简称
     * @param  cooperateCompanyCode 供应商编号
     * @param  cooperateCompanyName 供应商名称
     * @param  orderTotalMoney 订单总金额                分？
     * @param  orderDiscountMoney 订单优惠总金额
     * @param  orderPayMoney 订单应付金额=订单总金额 - 订单优惠总金额
     * @param  orderPayMoney 订单应付金额=订单总金额 - 订单优惠总金额
     * @param  orderRefundMoney 订单退款金额
     * @param  createAccount 创建人
     * @param  createAccountSrc 创建人来源
     * @param  updateAccount 修改人
     * @param  updateAccountSrc 修改人来源
     * @param  channel 标准化渠道 微信 京东 金融
     * @param  terminal 标准化终端 APP H5 PC 小程序 公众号
     * @param  sourceType 运营渠道 活动页 gshop 短信
     * @param  resourceplace 运营资源位 小金库频道页
     * @param  bitmap 订单打标 默认是000000000000000000000000 第一位 1 代表已理赔 0 代表未理赔 第二位 1 代表已退保 0 代表未退保 第三位 1 代表已退款 0 代表未退款
     * @param  productCode 产品编号
     * @param  productName 产品名称
     * @param  productImgUrl 商品图片
     * @param  thirdBizId 第三方业务编号
     * @param  sceneCode 订单下单场景代码
     * @param  merchantNo 商户编号
     * @param  merchantName 商户名称
     * @param  tradeType 交易类型 0 购买 1 追加保费 3 续期扣费 4 追加保额
     * @param  parentId 父订单编号
     * -------------------------------------
     * -policyInfoDtolist
     * --policyDto
     * ---insureNum 投保份数
     * ---policyType 保单类型 1000 健康 2000 旅行 3000 意外 4000 车险 5000 服务 7000 财产 8000 人寿
     * ---premiumTotalMoney 保费总金额
     * ---premiumDiscountMoney 保费优惠总金额
     * ---premiumPayMoney 保费应付金额
     * ---premiumRefundMoney 保费退款金额
     * ---policyStatus 保单状态 0 初始 1 核保成功 2 核保失败 3 承保成功 4 承保失败 6 失效 7 终止 12 已退保 20 退款中 22 退款成功 29 服务单已提交 30 待录入受检人 31 已录入受检人
     * ---policyBeginTime 保障开始时间
     * ---policyEndTime 保障结束时间
     * ---policyEndTime 保障结束时间
     * ---isRenewal 是否是续期
     * ---dutyCoverage 保额
     * ---isGive 是否赠送 0 否 1 是
     * ---hesitationPeriod 是否有犹豫期 0 否 1 是
     * ---hesitationPeriodDays 犹豫期天数 默认是0
     * ---surrender 是否支持京东退保 0 否 1 是
     * ---rewalDeductionType 扣费方式 1 平台代扣 2 保险公司代扣
     * ---claim 是否支持京东理赔 0 否 1 是
     * ---createAccount 创建人
     * ---createAccountSrc 创建人来源
     * ---updateAccount 修改人
     * ---updateAccountSrc 修改人来源
     * ---buyerType 买家账号类型 JD_PIN
     * ---buyerAccount 买家账号
     * ---productCode
     * ---setProductName
     * ---insuranceCompanyCode 保险公司编号
     * ---insuranceCompanyName
     * ---insuranceCompanyShortname
     * ---cooperateCompanyCode 供应商编号
     * ---cooperateCompanyName
     * ---claimType 理赔方式 0不支持理赔 1 在线理赔 2电话理赔
     * ---isDeleted 订单是否删除 0否 1 是
     * ---sceneCode 订单下单场景代码
     *
     * -policyRiskDtoList
     * --riskCode 险种保险责任代码
     * --riskName 险种责任名称
     * --dutyCoverage 保额
     * --dutyPremium 保费
     * --riskType 是否主险 0 否 1 是
     * --riskFlag 是否不计免赔 0 否 1 是
     * --createAccount
     * --createAccountSrc
     * --updateAccount
     * --updateAccountSrc
     * --buyerType 买家账号类型 JD_PIN
     * --buyerAccount
     * --modifiedTime
     * --insuredName 姓名
     * --insuredEmail
     * --insuredCardType 证件类型 1 身份证
     * --insuredCardNo 证件号
     * --dutyCoverageDesc 证件号
     *
     * -serviceProcessInfoDtoList
     * --buyerType
     * --buyerAccount
     * --code 盒子号
     * --shipStatus 物流状态
     * --expressNo 快递单号
     * --recheckCount 是否为重新检测订单
     * --status 状态
     * --sendDate 信息推送时间
     * --ext1 扩展保留字段1
     * --ext2 扩展保留字段2
     *
     * -policyInsuredHolderDto   投保人
     * --insuredType 人员类型 1 投保人 2 被保人
     * --insuredName 姓名
     * --insuredSex
     * --insuredEmail
     * --insuredCardType 1 身份证
     * --insuredCardNo 1 身份证
     * --insuredMobile
     * --insuredRelation 投被保人关系（被保人是投保人XXX） 0 本人 1父母 2 子女 4 其它 5配偶 9 雇佣关系 11 劳动关系人\\\\n当insured_type 为1时，表示是被保人，默认成0
     * --socialSecurity 有无社保 0 无 1 有
     * --ethnic 民族代码
     * --height 身高CM
     * --weight 体重公斤
     * --province
     * --provinceName
     * --city
     * --cityName
     * --country
     * --countryName
     * --adress
     * --buyerType
     * --buyerAccount
     * --createAccount
     * --createAccountSrc
     * --updateAccount
     * --updateAccountSrc
     * --benefitType 受益人类型 1 法定 2 指定
     *
     *
     * -policyInsuredInfoDtoList 被保人
     * --policyInsuredDto 被保人
     * ---insuredType 人员类型 1 投保人 2 被保人
     * ---insuredName 姓名
     * ---insuredSex
     * ---insuredEmail
     * ---insuredCardType
     * ---insuredCardNo
     * ---insuredMobile
     * ---insuredRelation
     * ---socialSecurity 有无社保 0 无 1 有
     * ---ethnic 民族代码
     * ---height
     * ---weight
     * ---province
     * ---provinceName
     * ---city
     * ---cityName
     * ---country
     * ---countryName
     * ---adress
     * ---buyerType
     * ---buyerAccount
     * ---createAccount 创建人
     * ---createAccountSrc
     * ---updateAccount
     * ---updateAccountSrc
     * ---benefitType 受益人类型 1 法定 2 指定
     *
     *--policyInsuredBenefitDtoList 被保人下对应受益人
     * ---insuredName
     * ---insuredSex
     * ---insuredCardType
     * ---insuredCardNo
     * ---benefitName 姓名
     * ---benefitSex
     * ---benefitEmail
     * ---benefitCardType
     * ---benefitCardNo
     * ---benefitMobile
     * ---benefitScale 受益人比例（0,100）默认是100
     * ---benefitSeq 受益人优先顺序第一受益人1,2,3
     * ---adress
     * ---createAccount
     * ---createAccountSrc
     * ---updateAccount
     * ---updateAccountSrc
     * ---buyerType
     * ---buyerAccount
     * ---benefitType 受益人类型 1 法定 2 指定
     *
     *
     * -policyProductDtoList
     * --productCode
     * --productName
     * --insuranceCompanyCode 保险公司编号
     * --insuranceCompanyName
     * --insuranceCompanyShortname
     * --cooperateCompanyCode  供应商编号
     * --isRewal  是否是续期
     * --rewalDeductionType  扣费方式 1 平台代扣 2 保险公司代扣
     * --surrender  是否支持京东退保 0 否 1 是
     * --claim  是否支持京东理赔申请 0 否 1 是
     * --claimType  理赔方式 1 在线理赔
     * --buyerType  买家账号类型 JD_PIN
     * --buyerAccount
     * --createAccount
     * --createAccountSrc
     * --ext 扩展字段
     * --productVersion 产品版本信息
     *
     *
     * -policyExtendDto 保单拓展
     * --ext 扩展字段
     * --buyerType
     * --buyerAccount
     * --createAccount
     * --createAccountSrc
     * --updateAccount
     * --updateAccountSrc
     * --buyerProvince
     * --buyerCity
     * --buyerCityName
     * --buyerCountry
     * --buyerCountryName
     * --buyerAdress
     *
     *
     *
     * orderVehicleDto
     * -buyerType
     * -buyerAccount
     * -licenseNo 车牌号码
     * -engineNo 发动机号
     * -engineNo 发动机号
     * -vin 车架号
     * -vehicleNo 品牌编码
     * -gears 汽车档位
     * -invoiceDate 购车发票日期
     * -invoiceNo 购车发票号
     * -vehicleFamily 车辆车型车系
     * -licenseCityCode 牌归属城市代码
     * -cityCode 投保城市代码、行驶城市
     * -cityName
     * -marketDate 车辆上市时间
     * -importFlag  车型种类:国产/进口/合资
     * -nature  使用性质：家庭自用
     * -fuelType 能源类型
     * -ownerName 车主姓名
     * -ownerSex
     * -ownerCardType 车主证件类型 1 身份证
     * -ownerCardNo 车主证件号
     * -ownerMobile
     * -createAccount
     * -createAccountSrc
     * -updateAccount
     * -updateAccountSrc
     *
     * orderExtendDto
     * -ext
     * -buyerType
     * -buyerAccount
     * -buyerProvince
     * -buyerProvinceName
     * -buyerCity
     * -buyerCityName
     * -buyerCountry
     * -buyerCountryName
     * -buyerAdress
     * -buyerIp
     * -createAccount
     * -createAccountSrc
     * -updateAccount
     * -updateAccountSrc
     *
     *
     * paymentDetailDtoList
     * -buyerType
     * -buyerAccount
     * -payMoney 支付金额
     * -orderPayMoney 订单应付金额=订单总金额 - 订单优惠总金额
     * -payType 支付订单类型
     * -payEnum 支付机构枚举
     * -payTime 支付时间
     * -platId 平台号
     * -platName 支付枚举名称
     * -payId 支付流水号
     * -bankPayId 银行流水号
     */
    public function baoxianOrderSync()
     {
         $url = 'http://g.jsf.jd.test/com.jd.baoxian.order.trade.export.resource.OrderCreateService/orderCreateService_test/orderSync/5299';
         $client = new Client();
         try {
             $prama = '{"orderInfoDto":{"orderDto":{"bitmap":"1111111000011","buyerAccount":"testJdpin","buyerNickName":"setBuyerNickName","buyerPhone":"setBuyerPhone","buyerType":"JD_PIN","channel":"test","cooperateCompanyCode":"test","cooperateCompanyName":"test","createAccount":"test","createAccountSrc":"test","insuranceCompanyCode":"setInsuranceCompanyCode","insuranceCompanyName":"setInsuranceCompanyName","insuranceCompanyShortname":"setInsuranceCompanyShortname","merchantName":"京东金融","merchantNo":"1012050001","orderCancelReason":"setOrderCancelReason","orderCancelTime":1544431769120,"orderClosedTime":1544431769120,"orderCreatedTime":1544431769120,"orderDiscountMoney":0,"orderPayMoney":10000,"orderRefundMoney":0,"orderStatus":0,"orderStatusTime":1544431769120,"orderTotalMoney":10000,"orderType":"1000","parentId":"","productCode":"test","productImgUrl":"setProductImgUrl","productName":"setProductName","resourceplace":"test","sceneCode":"JDAPP","sourceType":"test","terminal":"test","thirdBizId":"setThirdBizId","tradeType":"0","updateAccount":"test","updateAccountSrc":"test"},"orderExtendDto":{"buyerAccount":"testJdpin","buyerAdress":"setBuyerAdress","buyerCity":"","buyerCityName":"","buyerCountry":"","buyerCountryName":"","buyerIp":"","buyerProvince":"","buyerProvinceName":"","buyerType":"JD_PIN","createAccount":"test","createAccountSrc":"test","ext":"setExt","updateAccount":"test","updateAccountSrc":"test"},"orderVehicleDto":{"buyerAccount":"testJdpin","buyerType":"JD_PIN","cityCode":"setCityCode","cityName":"setCityName","createAccount":"test","createAccountSrc":"test","engineNo":"setEngineNo","fuelType":"1","gears":"1","importFlag":"1","invoiceDate":1544431769120,"invoiceNo":"341234134123sdf","licenseCityCode":"110100","licenseNo":"setLicenseNo","marketDate":1544431769120,"nature":"1","ownerCardNo":"setOwnerCardNo","ownerCardType":"1","ownerMobile":"setOwnerMobile","ownerName":"setOwnerName","ownerSex":"1","updateAccount":"test","updateAccountSrc":"test","vehicleFamily":"sdfa是电饭锅","vehicleNo":"123432dsfasd","vin":"setVin"},"paymentDetailDtoList":[{"bankPayId":"银行流水","buyerAccount":"testJdpin","buyerType":"JD_PIN","orderPayMoney":200,"payEnum":"lxn123","payId":"0000000000000000003","payMoney":200,"payTime":1544431769121,"payType":"1","platId":"lxn平台号平台号","platName":"平台名称"}],"policyInfoDtolist":[{"policyDto":{"buyerAccount":"testJdpin","buyerType":"JD_PIN","claim":"1","claimType":"1","cooperateCompanyCode":"setCooperateCompanyCode","cooperateCompanyName":"setCooperateCompanyName","createAccount":"testJdpin","createAccountSrc":"testJdpin","dutyCoverage":1000,"hesitationPeriod":"0","hesitationPeriodDays":"100","insuranceCompanyCode":"setInsuranceCompanyCode","insuranceCompanyName":"setInsuranceCompanyName","insuranceCompanyShortname":"setInsuranceCompanyShortname","insureNum":1,"isDeleted":1,"isGive":"1","isRenewal":"1","policyBeginTime":1544431769121,"policyEndTime":1544431769121,"policyStatus":1,"policyType":"1","premiumDiscountMoney":0,"premiumPayMoney":1000,"premiumRefundMoney":0,"premiumTotalMoney":1000,"productCode":"setProductName","productName":"setProductName","rewalDeductionType":"1","sceneCode":"JDAPP","surrender":"1","updateAccount":"testJdpin","updateAccountSrc":"testJdpin"},"policyExtendDto":{"buyerAccount":"testJdpin","buyerAdress":"北京地区阿萨德发发示范区额外人情味","buyerCity":"110100","buyerCityName":"北京大兴区","buyerCountry":"大兴区","buyerCountryName":"大兴区","buyerProvince":"110000","buyerType":"JD_PIN","createAccount":"testJdpin","createAccountSrc":"testJdpin","ext":"asdfasdf34efasdfasdf12312312321","updateAccount":"test","updateAccountSrc":"test"},"policyInsuredHolderDto":{"adress":"案发生的发达","benefitType":"1","buyerAccount":"testJdpin","buyerType":"JD_PIN","city":"110100","cityName":"beijing","country":"110100","countryName":"beijing","createAccount":"test","createAccountSrc":"test","ethnic":"1","height":"168","insuredCardNo":"152222198603162219","insuredCardType":"1","insuredEmail":"afa@123.com","insuredMobile":"13811790226","insuredName":"test","insuredRelation":"1","insuredSex":"1","insuredType":"1","province":"110100","provinceName":"山东省","socialSecurity":"1","updateAccount":"test","updateAccountSrc":"test","weight":"150"},"policyInsuredInfoDtoList":[{"policyInsuredDto":{"adress":"案发生的发达","benefitType":"1","buyerAccount":"testJdpin","buyerType":"JD_PIN","city":"110100","cityName":"beijing","country":"110100","countryName":"beijing","createAccount":"test","createAccountSrc":"test","ethnic":"1","height":"168","insuredCardNo":"152222198603162219","insuredCardType":"1","insuredEmail":"afa@123.com","insuredMobile":"13811790226","insuredName":"test","insuredRelation":"1","insuredSex":"1","insuredType":"2","province":"110100","provinceName":"山东省","socialSecurity":"1","updateAccount":"test","updateAccountSrc":"test","weight":"150"}},{"policyInsuredBenefitDtoList":[{"adress":"adfa阿萨德","benefitCardNo":"140100198506020029","benefitCardType":"1","benefitEmail":"asas@123.com","benefitMobile":"13811790226","benefitName":"adsf阿什顿发","benefitScale":100,"benefitSeq":"1","benefitSex":"1","benefitType":"1","buyerAccount":"testJdpin","buyerType":"JD_PIN","createAccount":"test","createAccountSrc":"test","insuredCardNo":"152222198603162219","insuredCardType":"1","insuredName":"test","insuredSex":"1","updateAccount":"test","updateAccountSrc":"test"}],"policyInsuredDto":{"adress":"案发生的发达","benefitType":"2","buyerAccount":"testJdpin","buyerType":"JD_PIN","city":"110100","cityName":"beijing","country":"110100","countryName":"beijing","createAccount":"test","createAccountSrc":"test","ethnic":"1","height":"168","insuredCardNo":"152222198603162219","insuredCardType":"1","insuredEmail":"afa@123.com","insuredMobile":"13811790226","insuredName":"test","insuredRelation":"1","insuredSex":"1","insuredType":"2","province":"110100","provinceName":"山东省","socialSecurity":"1","updateAccount":"test","updateAccountSrc":"test","weight":"150"}}],"policyProductDtoList":[{"buyerAccount":"testJdpin","buyerType":"JD_PIN","claim":"1","claimType":"1","cooperateCompanyCode":"setCooperateCompanyCode","cooperateCompanyName":"setCooperateCompanyName","createAccount":"test","createAccountSrc":"test","ext":"aaaa","insuranceCompanyCode":"setInsuranceCompanyCode","insuranceCompanyName":"setCooperateCompanyName","insuranceCompanyShortname":"setInsuranceCompanyShortname","isRewal":"","productCode":"setProductName","productName":"setProductName","productVersion":"1000","rewalDeductionType":"1","surrender":"1","updateAccount":"test","updateAccountSrc":"test"}],"policyRiskDtoList":[{"buyerAccount":"testJdpin","buyerType":"JD_PIN","createAccount":"test","createAccountSrc":"test","dutyCoverage":100,"dutyCoverageDesc":"baoe描述","dutyPremium":12,"insuredCardNo":"120101198506020056","insuredCardType":"1","insuredEmail":"13213@qwe.com","insuredName":"阿卜杜拉阿卜杜拉买买提","insuredSex":"1","isDeleted":1,"modifiedTime":1544431769123,"riskCode":"002","riskFlag":"1","riskName":"破粗险","riskType":"1","updateAccount":"test","updateAccountSrc":"test"},{"buyerAccount":"testJdpin","buyerType":"JD_PIN","createAccount":"test","createAccountSrc":"test","dutyCoverage":100,"dutyCoverageDesc":"baoe描述","dutyPremium":12,"insuredCardNo":"120101198506020056","insuredCardType":"1","insuredEmail":"13213@qwe.com","insuredName":"阿卜杜拉阿卜杜拉买买提","insuredSex":"1","isDeleted":1,"modifiedTime":1544431769123,"riskCode":"001","riskFlag":"1","riskName":"破粗险","riskType":"1","updateAccount":"test","updateAccountSrc":"test"}]}]}},{"operAccount":"testJdpin","operAccountType":"system","operRemark":"aaa","requestSeq":"15c21734-82fc-4d2b-a4f2-50811a8100f9","sourceSystem":"unit test"}';
             $response = $client->request('post',$url,[
                 'body'=>$prama
             ]);
             var_dump(\GuzzleHttp\json_decode($response->getBody(),true));
         } catch (BadResponseException $e) {
             echo $e->getMessage();
         }
     }

     public function baoxianHeBao()
     {
         $url = 'http://g.jsf.jd.test/com.jd.baoxian.service.platform.export.resource.contract.UnderWriteResource/bx_service_p_underwrite_test/underwrite/10001';
         $client = new Client();
         try {
             $applicant['name'] = '邱沈阳';
             $applicant['certificateNo'] = '330803199303175474';
             $applicant['certificateType'] = 1;
             $applicant['mobile'] = '18367826195';
             $insurance['itemId'] = '15800001';
             $insurance['totalPrice'] = 100000;
             $insurance['insurancePeriodType'] = "A";
             $insurance['insurancePeriod'] = "999";
             $insurance['paymentPeriodType'] = "Y";
             $insurance['paymentPeriod'] = "10";
             $insuredList[] =[
               'number'=>'1',
                 'name'=>'邱沈阳',
                 'certificateNo'=>'330803199303175474',
                 'certificateType'=>1,
                 'relation'=>'0',
                 'count'=>1,
                 'mainPrice'=>100000
             ];
            $merchant['merchantNo'] = '100001';
            $merchant['merchantName'] = '测试商户';
             $data['account'] = 'roda111';
             $data['accountType'] = 'JD_PIN';
             $data['applicant'] = $applicant;
             $data['insurance'] = $insurance;
             $data['insuredList'] = $insuredList;
             $data['ip'] = get_client_ip();
             $data['resourcePlace'] = 'test';
             $data['sourceType'] = 'test';
             $data['sourceType'] = 'test';
             $data['merchant'] = $merchant;
             $data = json_encode($data);
/*             $prama = '{"account":"AndyLau_zhl","accountType":"JD_PIN","applicant":{"name":"宿迁际扬"},"insurance":{"amount":"20000","insurancePeriod":"1","insurancePeriodType":"Y","itemId":"2018121001","totalPrice":0},"insuredList":[{"certificateNo":"110101201603077110","certificateType":"1","count":1,"mainPrice":0,"name":"子女姓名","number":"1","relation":"2","useSpecial":false}],"merchant":{"merchantNo":"100001"},"merchantNo":"100001","version":"v2"}';*/
             $response = $client->request('post',$url,[
                 'body'=>$data
             ]);
             var_dump(\GuzzleHttp\json_decode($response->getBody(),true));
         } catch (BadResponseException $e) {
             echo $e->getMessage();
         }
     }

}



