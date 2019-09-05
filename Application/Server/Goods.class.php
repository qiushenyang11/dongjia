<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/11/2
 * Time: 17:52
 */

namespace Server;


use NewApi\Model\AgencyModel;
use Operation\Model\MessageModel;
use Operation\Model\OrderModel;
use Operation\Model\SupplierModel;
use WeChat\Model\GoodsModel;
use WeChat\Model\GuanJiaModel;

class Goods
{
    public function addProduct($param)
    {
        $type = 1;

        $name =$param['name'];
        $isshipping = $param['isshipping'];
        $serviceinfo = $param['serviceinfo']; //服务亮点
        $productpic = $param['productpic'];
        $categoryid = $param['categoryid'];
        $yijiname = $param['yijiname'];
        $erjiname = $param['erjiname'];
        $facepic = $param['facepic'];
        $guanjiaid = $param['guanjiaid'];
        $bdid = $param['bdid'];
        $supplierid = $param['supplierid'];
        $productinfo = $param['productinfo'];
        $notes = htmlspecialchars($param['notes']);
        $status = $param['status'];
        $endtime = $param['endtime'];
        $isrecommend = isset($param['isrecommend']) ? $param['isrecommend'] : 0;
        $weight = $param['weight'];
        $messagetype = isset($param['messagetype']) ? $param['messagetype'] : 0;                             //type = 0 使用默认短信, type = 1 自定义短信
        $ptype = isset($param['ptype']) ? $param['ptype'] : 1;
        $message = isset($param['message']) ? $param['message'] : '';
        $showstarttime = $param['showstarttime'];
        $showendtime = $param['showendtime'];
        $bookingtype = isset($param['bookingtype'])?$param['bookingtype'] : 0;
        $servicetime = $param['servicetime'] ? $param['servicetime'] : '';                                  //todo v3
        $sortweight = empty($param['sortweight'])?0:$param['sortweight'];
        $servicetime = $this->parseServicetimeToJson($servicetime);
        $length  =mb_strlen($name,'utf8');
        if (!$name) response('产品名称不能为空');
        /* if ($length>24) response('产品名称24以内');*/
        if (!$productpic) response('请上传头图');
        if (!$facepic) response('请上传封面图');
        if (!$categoryid) response('选择所属管家');
        if (!$supplierid) response('选择所属供应商');
        if ($messagetype == 1 && !$message) response('请填写短信模板');
        if ($bookingtype > 2) response('预订流程状态异常');
        /*     if (mb_strlen($productinfo,'utf8') <100) response('产品介绍至少100字');
             if (!$notes) response('预订须知不能为空');
             if (mb_strlen($serviceinfo,'utf8')>20) response('服务亮点不能超过20字');*/
        if ($isrecommend) {
            if (intval($weight) < 1) response('请输入正确的排序值');
            if (!$showstarttime) response('请填写推荐开始时间');
            if (!$showendtime) response('请填写推荐结束时间');
            if ($showendtime <= $showstarttime) response('请填写正确的时间区间');
            $showstarttime = strtotime($showstarttime);
            $showendtime = strtotime($showendtime);
        }
        if ($type == 1) {
            $servicecity = $param['servicecity'];
            $data['servicecity'] =$servicecity;
        } else {
            $data['isshipping'] = $param['isshipping'];
        }
        $productinfo=htmlspecialchars($productinfo);
        $data['facepic'] = $facepic;
        $data['serviceinfo'] = $serviceinfo;
        $data['type'] = $type;
        $data['name'] = $name;
        $data['isshipping'] = $isshipping;
        $data['productpic'] = $productpic;
        $data['categoryid'] = $categoryid;
        $data['categoryname'] = $yijiname."-".$erjiname;
        $data['guanjiaid'] = $guanjiaid;
        $data['supplierid'] = $supplierid;
        $data['productinfo'] = $productinfo;
        $data['notes'] = $notes;
        $data['status'] = $status;
        $data['endtime'] = strtotime($endtime);
        $data['addtime'] = time();
        $data['modified_date'] = time();
        $data['isrecommend'] = $isrecommend;
        $data['weight'] = $weight;
        $data['showendtime'] = $showendtime;
        $data['showstarttime'] = $showstarttime;
        $data['bookingtype'] = $bookingtype;
        $data['servicetime'] = $servicetime;
        $data['sortweight'] = $sortweight;
        $data['messagetype']=$messagetype;
        $data['ptype'] = $ptype;
        $goodsModel = new GoodsModel();
        if ($goodsModel->hasProductName($name, $guanjiaid)) response('该产品名已存在');
        if ($messagetype == 1) {
            $messageModel = new MessageModel();
            $messageid = $messageModel->addMessage($message);
            $data['messageid'] = $messageid;
        }
        $res = $goodsModel->addProduct($data);
        $res1 = true;
        if ($data['endtime']) {
            $addstring =  json_encode(['id'=>$res,'type'=>'product']);
            $crotabClass = new Crontab($addstring);
            $message = "产品:".$res.",".$endtime.'自动下线';
            $res1 = $crotabClass->addProductOfflineTick($data['endtime'], $message);
        }
        if ($res && $res1) {
            return $res;
        } else {
            return false;
        }

    }

    public function saveProduct($id, $param)
    {
        $name =$param['name'];
        $isshipping = $param['isshipping'];
        $serviceinfo = $param['serviceinfo']; //服务亮点
        $productpic = $param['productpic'];
        $facepic = $param['facepic'];
        $categoryid = $param['categoryid'];
        $yijiname = $param['yijiname'];
        $erjiname = $param['erjiname'];
        $servicecity = $param['servicecity'];
        $guanjiaid = $param['guanjiaid'];
        $supplierid = $param['supplierid'];
        $productinfo = $param['productinfo'];
        $notes = htmlspecialchars($param['notes']);
        $status = $param['status'];
        $endtime = $param['endtime'];
        $isrecommend = isset($param['isrecommend']) ? $param['isrecommend'] : 0;
        $weight = $param['weight'];
        $showstarttime = $param['showstarttime'];
        $showendtime = $param['showendtime'];
        $length  =mb_strlen($name,'utf8');
        $messagetype = isset($param['messagetype']) ? $param['messagetype'] : 0;                             //type = 0 使用默认短信, type = 1 自定义短信
        $message = isset($param['message']) ? $param['message'] : '';
        $bookingtype = isset($param['bookingtype'])?$param['bookingtype'] : 0;
        $servicetime = $param['servicetime'] ? $param['servicetime'] : '';                                  //todo v3
        $sortweight = empty($param['sortweight'])?0:$param['sortweight'];
        $servicetime = $this->parseServicetimeToJson($servicetime);
        $ptype = isset($param['ptype']) ? $param['ptype'] : 1;
        if (!$name) response('产品名称不能为空');
        /* if ($length>24) response('产品名称24以内');*/
        /*   if (mb_strlen($serviceinfo,'utf8')>20) response('服务亮点不能超过20字');*/
        if ($bookingtype > 2) response('预订流程状态异常');
        if (!$categoryid) response('选择所属管家');
        if (!$supplierid) response('选择所属供应商');
        /*       if (mb_strlen($productinfo,'utf8') <100) response('产品介绍至少100字');*/
        if (!$notes) response('预订须知不能为空');
        if ($productpic) {
            $data['productpic'] = $productpic;
        }
        if ($isrecommend) {
            if (intval($weight) < 1) response('请输入正确的排序值');
            if (!$showstarttime) response('请填写推荐开始时间');
            if (!$showendtime) response('请填写推荐结束时间');
            if ($showendtime <= $showstarttime) response('请填写正确的时间区间');
            $showstarttime = strtotime($showstarttime);
            $showendtime = strtotime($showendtime);
        }
        $productinfo=htmlspecialchars($productinfo);
        $data['name'] = $name;
        $data['isshipping'] = $isshipping;
        $data['facepic'] = $facepic;
        $data['serviceinfo'] = $serviceinfo;
        $data['categoryid'] = $categoryid;
        $data['categoryname'] = $yijiname."-".$erjiname;
        $data['servicecity'] =$servicecity;
        $data['guanjiaid'] = $guanjiaid;
        $data['supplierid'] = $supplierid;
        $data['productinfo'] = $productinfo;
        $data['notes'] = $notes;
        $data['status'] = $status;
        $data['endtime'] = strtotime($endtime);
        $data['isrecommend'] = $isrecommend;
        $data['weight'] = $weight;
        $data['showendtime'] = $showendtime;
        $data['showstarttime'] = $showstarttime;
        $data['bookingtype'] = $bookingtype;
        $data['servicetime'] = $servicetime;
        $data['sortweight'] = $sortweight;
        $data['messagetype'] = $messagetype;
        $data['ptype'] = $ptype;
        $data['modified_date'] = time();
        $goodsModel = new GoodsModel();
        $info = $goodsModel->getOneProduct($id);
        if ($info['name'] != $name) {
            if ($goodsModel->hasProductName($name, $guanjiaid)) response('该产品名已存在');
        }
        $messageModel = new MessageModel();
        if ($messagetype == 1) {
            if ($info['messageid']) {
                $messageModel->saveMessage($info['messageid'],$message);
            } else {
                $messageid = $messageModel->addMessage($message);
                $data['messageid'] = $messageid;
            }

        } else {
            if ($info['messageid']) {
                $messageModel->delMessage($info['messageid']);
            }
            $data['messageid'] = 0;
        }
        $res = $goodsModel->saveProduct($id,$data);
        if ($data['endtime']) {
            $addstring =  json_encode(['id'=>$id,'type'=>'product']);
            $crotabClass = new Crontab($addstring);
            $message = "产品:".$id.",修改".$endtime.'自动下线';
            $res1 = $crotabClass->addProductOfflineTick($data['endtime'], $message);
        }
        return 1;
    }

    public function getProductById($ids = [])
    {
        if (!count($ids)) return false;
        $goodsModel = new GoodsModel();
        $result = $goodsModel->getProdcutListInfoById($ids);
        foreach ($result as $key => $row) {
            if (substr($row['facepic'], 0, 4) != 'http') {
                $result[$key]['facepic'] = C("UPLOADURL") . $row['facepic'];
            }
            $price = $row['price'];
            $orginprice = $row['orginprice'];
            $serviceinfo = explode("+-",$row['serviceinfo']);                           //v1
            $result[$key]['serviceinfo'] = $serviceinfo[0];
            if (intval($price) == $price) $result[$key]['price'] = intval($price);
            if (intval($orginprice) == $orginprice) $result[$key]['orginprice'] = intval($orginprice);
            unset($result[$key]['onenum']);
        }
        return $result;
    }

    /**
     * @breif 微信端获取所有产品
     * @param int $guanjiaid 0 所有产品   >0该管家下所有的产品
     * @param int $limit
     * @param int $nowpage
     * @return bool
     */
    public function getProduct($guanjiaid = 0, $limit = 0, $nowpage = 1, $isfliter = false, $ishome = false, $where = [] )
    {
        $goodsModel = new GoodsModel();
        $result = $goodsModel->getProductListInfo($guanjiaid, $limit, $nowpage ,$isfliter,$ishome, $where );

        $product_ids = [];

        foreach ($result as $key => $row) {
            if (substr($row['facepic'], 0, 4) != 'http') {
                $result[$key]['facepic'] = C("UPLOADURL") . $row['facepic'];
            }
            $result[$key]['avatarurl'] = C("UPLOADURL") . $row['avatarurl'];                                //新增管家头像
            $price = $row['price'];
            $orginprice = $row['orginprice'];
            $serviceinfo = explode("+-",$row['serviceinfo']);                           //v1
            $result[$key]['serviceinfo'] = $serviceinfo[0];
            if (intval($price) == $price) $result[$key]['price'] = intval($price);
            if (intval($orginprice) == $orginprice) $result[$key]['orginprice'] = intval($orginprice);
            unset($result[$key]['onenum']);
            $product_ids[] = $row['id'];
        }

        // 获取收藏列表
        $cond['productId']=array('in', $product_ids);
        $cond['isdelete'] = 0;

        // todo 没session?
        $cond['jdaccount'] = session('jdaccount');
//        $cond['jdaccount'] = 'oMdTzvkXjZhJrQXD4WQYPf-rw5QQ_test';

        $collect_list = M('collect')->where($cond)->select();

        foreach ($result as &$product_item) {
            $finded = false;
            foreach ($collect_list as $collect_item) {
                if ($collect_item['productid'] == $product_item['id']) {
                    $product_item['colleted'] = 1;
                    $finded = true;
                    break;
                }
            }
            if (!$finded) $product_item['colleted'] = 0;

        }

        return $result;
    }


    public function getCategoryProduct($leveltwoid = 0, $address = '',$nowpage = 1)
    {
        $goodsModel = new GoodsModel();
        $result = $goodsModel->getCateoryProductList($leveltwoid, $address,$nowpage);
        $product_ids = [];

        foreach ($result as $key => $row) {
            if (substr($row['facepic'], 0, 4) != 'http') {
                $result[$key]['facepic'] = C("UPLOADURL") . $row['facepic'];
            }
            $result[$key]['avatarurl'] = C("UPLOADURL") . $row['avatarurl'];                                //新增管家头像
            $price = $row['price'];
            $orginprice = $row['orginprice'];
            $serviceinfo = explode("+-",$row['serviceinfo']);                           //v1
            $result[$key]['serviceinfo'] = $serviceinfo[0];
            if (intval($price) == $price) $result[$key]['price'] = intval($price);
            if (intval($orginprice) == $orginprice) $result[$key]['orginprice'] = intval($orginprice);
            unset($result[$key]['onenum']);
            $product_ids[] = $row['id'];
        }
        return $result;
    }

    /**
     * @breif 获取服务类产品详情页
     * @param int $productid
     * @param int $guanjiaid
     * @return array|bool
     */
    public function getProdutDetail($productid = 0, $guanjiaid = 0)
    {
        if (!$productid) return false;
        if (!$guanjiaid) return false;
        $userModel = new GuanJiaModel();
        $guanjiainfo  = $userModel->getGuanjiaInfo($guanjiaid, $productid);
        $guanjiainfo['avatarurl'] = C("UPLOADURL").$guanjiainfo['avatarurl'];
        $productinfo = $this->getOneServiceProductInfo($productid);
        $recommandinfo = $this-> getRecommendProdct($guanjiaid,$productinfo['categoryname'],$productid,$productinfo['servicecity']);
        $commentClass = new Comment();
        $comment = $commentClass->getRecentComment($productid);

        // todo 没session?
        $cond['jdaccount'] = session('jdaccount');
        //$cond['jdaccount'] = 'oMdTzvkXjZhJrQXD4WQYPf-rw5QQ_test';
        $cond['productid'] = $productid;
        $cond['isdelete'] = '0';
        $res = M('collect')->where($cond)->limit(1)->select();
        if (count($res)) {
            $collected = 1;
        }
        else {
            $collected = 0;
        }
        return ['guanjiainfo'=>$guanjiainfo,'productinfo'=>$productinfo, 'recommandinfo' =>$recommandinfo,'comment'=>$comment,'collected' => $collected];
    }

    /**
     * @beif 获取快递类产品详情页
     * @param int $productid
     * @return bool|mixed
     */
    public function getExpressDetail($productid = 0, $guanjiaid = 0)
    {
        if (!$productid) return false;
        if (!$guanjiaid) return false;
        $userModel = new GuanJiaModel();
        $guanjiainfo  = $userModel->getGuanjiaInfo($guanjiaid,$productid);
        $guanjiainfo['avatarurl'] = C("UPLOADURL").$guanjiainfo['avatarurl'];
        $productinfo = $this->getOneExpressProductInfo($productid);
        return ['guanjiainfo'=>$guanjiainfo,'productinfo'=>$productinfo];
    }

    /**
     * @breif 获取一个服务类产品信息
     * @param $productid
     * @return mixed
     */
    public function getOneServiceProductInfo($productid)
    {
        $goodsModel = new GoodsModel();
        $productinfo = $goodsModel->getOneServiceProductInfo($productid);
        if (intval($productinfo['price']) == $productinfo['price']) $productinfo['price'] = intval($productinfo['price']);
        if (intval($productinfo['orginprice']) == $productinfo['orginprice']) $productinfo['orginprice'] = intval($productinfo['orginprice']);
        $productinfo['productinfo'] = htmlspecialchars_decode($productinfo['productinfo']);
        $productinfo['notes'] = htmlspecialchars_decode($productinfo['notes']);
        $pic = explode(',',$productinfo['productpic']);
        foreach ($pic as $key =>$row) {
            $pic[$key] =$row?((substr($row,0,4) != 'http')?C('UPLOADURL').$row:$row):'';
        }
        $productinfo['productpic'] = $pic;

        $productinfo['productpic'] = $pic;

        $infoStatus = $goodsModel->checkServieProduct($productid); // 2 该产品已下线, 3|5 该产品已售罄, 4 该产品规格已全部下线

        $productinfo['infoStatus'] = $infoStatus;
        return $productinfo;
    }

    /**
     * @breif 获取一个商品类产品信息
     * @param $productid
     * @return mixed
     */
    public function getOneExpressProductInfo($productid)
    {
        $goodsModel = new GoodsModel();
        $productinfo = $goodsModel->getOneExpressProductInfo($productid);
        if (intval($productinfo['price']) == $productinfo['price']) $productinfo['price'] = intval($productinfo['price']);
        if (intval($productinfo['orginprice']) == $productinfo['orginprice']) $productinfo['orginprice'] = intval($productinfo['orginprice']);
        $productinfo['productinfo'] = htmlspecialchars_decode($productinfo['productinfo']);
       /* $goodsminprice = $productinfo['goodsminprice'];
        $goodstotalnum = $productinfo['goodstotalnum'];
        $goodstotalprice = $productinfo['goodstotalprice'];
        if ($goodsminprice * $goodstotalnum == $goodstotalprice) {
            $productinfo['ispriceunqiue'] = 1;
        } else {
            $productinfo['ispriceunqiue'] = 0;
        }
        if ($productinfo['goodstotalnum'] * 2 == $productinfo['totalstatus']) {         //快递类商品下所有规格下线属于产品下线
            $productinfo['status'] = 2;
        }*/
        $pic = explode(',',$productinfo['productpic']);
        foreach ($pic as $key =>$row) {
            $pic[$key] = C('UPLOADURL').$row;
        }
        $productinfo['productpic'] = $pic;
      /*  $productinfo['price'] = $productinfo['goodsminprice'];*/
      /*  unset($productinfo['goodsminprice']);
        unset($productinfo['goodstotalnum']);
        unset($productinfo['goodstotalprice']);
        unset($productinfo['totalstatus']);*/
        return $productinfo;
    }

    /**
     * @breif 获取产品详情页商品详情
     * @param int $productid
     * @return bool
     */
    public function getAllGoods($productid = 0,$cardid = 0,$cardtag = 0)
    {
        $selectgoods = '';
        $selectspec = '';
        $restnum = 0;
        if ($cardid) {
            $cardClass = new Card();
            $exchagecardinfo = $cardClass->getExchangeCanUseGoodAndName($cardid,$cardtag);
            $restnum = $exchagecardinfo['restnum'];
            if (!$restnum) response('该产品您已定完');
            $selectgoods = $exchagecardinfo['selectgoods'];
            $selectspec = $exchagecardinfo['selectspec'];
        }
        if (!$productid) return false;
        $goodsModel = new GoodsModel();
        $infoStatus = $goodsModel->checkServieProduct($productid,$selectspec);
        if ($infoStatus == 2) {
            response('该产品已下线');
        } elseif ($infoStatus == 3) {
            response('该产品已售罄');
        } elseif ($infoStatus == 4) {
            response('该产品规格已全部下线');
        } elseif ($infoStatus == 5) {
            response('该产品已售罄');
        }
        $result = $goodsModel->getAllServiceGoodsByProductid($productid,$selectgoods,$selectspec);
        foreach ($result as $key => $row) {
            $specminprice = $row['specminprice'];
            $spectotalnum = $row['spectotalnum'];
            $spectotalprice = $row['spectotalprice'];
            $allspec = $this->getAllSpecByGoodsid($row['id'],session('jdaccount'),$productid,$selectspec);
            $result[$key]['speclist'] = $allspec;
            $result[$key]['name'] = htmlspecialchars_decode($row['name']);
            if ($specminprice * $spectotalnum == $spectotalprice) {
                $result[$key]['ispriceunqiue'] = 1;
            } else {
                $result[$key]['ispriceunqiue'] = 0;
            }
            if (intval($specminprice) == $specminprice) $specminprice = intval($specminprice);
            $result[$key]['price'] = $specminprice;
            unset($result[$key]['specminprice']);
            unset($result[$key]['spectotalnum']);
            unset($result[$key]['spectotalprice']);
        }

        return ['restnum'=>$restnum,'res'=>$result];
    }

    /**
     * @breif 获取快递类商品
     * @param int $productid
     */
    public function getAllExpressGoods($productid = 0)
    {
        if (!$productid) return false;
        $goodsModel = new GoodsModel();
        $infoStatus = $goodsModel->checkExpressProduct($productid);
        if ($infoStatus == 2) {
            response('该产品已下线');
        } elseif ($infoStatus == 3) {
            response('该产品已售罄');
        } elseif ($infoStatus == 4) {
            response('该产品规格已全部下线');
        } elseif ($infoStatus == 5) {
            response('该产品已售罄');
        }
        $result = $goodsModel->getAllExpressGoodsByProductid($productid);
        foreach ($result as $key => $row) {
            $result[$key]['pic'] = C('UPLOADURL').$row['pic'];
            if ($row['price'] == intval($row['price'])) $result[$key]['price'] = intval($row['price']);
            unset($result[$key]['ordertype']);
        }
        return $result;
    }

    public function verifySubscribeServiceGood($productid, $goodsid,$specid,$nums,$jdaccount)
    {
        if (!$productid) response('参数异常');
        if (!$goodsid) response('参数异常');
        if (!$specid) response('参数异常');
        if (!$nums) response('参数异常');
        $goodsModel = new GoodsModel();
        $status = $goodsModel->isProductOffline($productid);
        if (!$status) response('该产品已下线');
        $res = $goodsModel->checkServiceGoodsStatus($goodsid,$specid);
        $goodstatus = $res['goodstatus'];
        $status = $res['status'];
        $restnum = $res['nums'];
        $minnum = $res['minnum'];
        $limittype = $res['limitype'];
        if (!$goodstatus) response('该商品已下线');
        if (!$status) response('该规格已下线');
        if ($nums >$restnum) response('库存不足');
        if ($nums < $minnum) response('小于起订份数');
        $limitnum = $res['limitnum'];
        if ($limittype == 2) {
            $orderModel = new OrderModel();
            $hasnum =$orderModel->getOrderNum($jdaccount, $productid, $goodsid, $specid);
            if ($hasnum + $nums > $limitnum) response('每用户限购'.$limitnum.'份');
        } elseif ($limittype == 3) {
            if ($nums > $limitnum) response('每单限购'.$limitnum.'份');
        }
        return true;
    }

    /**
     * @breif 服务类填单页数据
     * @param $productid
     * @param $goodsid
     * @param $specid
     * @param $jdaccount
     * @param $userid
     * @param int $agenceyid   如果是代办订单则大于0,为产品id
     * @return array
     */
    public function subscribeServiceGood($productid, $goodsid, $specid, $jdaccount, $userid, $agenceyid = 0)
    {
        $goodsModel = new GoodsModel();

            $goodsInfo = $goodsModel->getServiceBaseGoods($goodsid);
            if (substr($goodsInfo['facepic'], 0, 4) != 'http') {
                $goodsInfo['facepic'] = C("UPLOADURL") . $goodsInfo['facepic'];
            }
            $specs = $goodsModel->getOneSpec($specid);
            if ($goodsInfo['type'] == 5 && $specs['swimg']) {
                $specs['swimg'] =getshowImgUrl($specs['swimg']);
            }
            $productinfo = $goodsModel->getOneProduct($productid);
            $supplierid = $productinfo['supplierid'];
            $supplierModel = new SupplierModel();
            $supplierinfo = $supplierModel->getOneSupplier($supplierid);
            $servicetime = '';
            if ($goodsInfo['isselecttime']) {
                $servicetime = $this->calTheGoodsBookingDate(time(), $goodsInfo['caltype'], $goodsInfo['advancetime'], $goodsInfo['booktime'], $goodsInfo['noservicetime'],$supplierinfo['stime'],$supplierinfo['sinterval']);
            }
            //$servicetime = $productinfo['servicetime'];
            //$servicetime = $this->getServiceTime($servicetime);
            $guanjaiid = $productinfo['guanjiaid'];
            $orderModel = new OrderModel();
            $specid = $specs['id'];
            if ($specs['limitype'] != 2) {
                $specs['hasNum'] = -1;
            } else {
                $specs['hasNum'] = $orderModel->getOrderNum($jdaccount, $productid, $goodsid, $specid);
            }
            if ($specs['price'] == intval($specs['price'])) $specs['price'] = intval($specs['price']);
            $refundcondition = '';
            $canrefund = '';
            if ($agenceyid) {
                $agencyModel = new AgencyModel();
                $agencyInfo = $agencyModel->getProduct($agenceyid);
                $goodsInfo['facepic'] = C("AGENTIMG");
                $goodsInfo['name'] = $agencyInfo['goodname'];
                $goodsInfo['paystyle'] = $agencyInfo['type'];
                $productinfo['name'] = $agencyInfo['productname'];
                $guanjaiid = 2;
                $servicetime = '';
                $productid['name'] = $agencyInfo['productname'];
                $specs['hasNum']= -1;
                $specs['limitnum']= 0;
                $specs['minnum']= 1;
                $specs['nums']= 1;
                $specs['price']= $agencyInfo['price'];

            }
        $wechatRedis = new WeChatRedis();
        $address = $wechatRedis->getUserOrderHistory($userid);
        if ($address) {
            $temp = explode(',', $address);
            unset($address);
            $address['addressname'] = $temp[0];
            $address['mobile'] = $temp[1];

        } else {
            $address = '';
        }
        $orderClass = new Order();
        $orderformat = $orderClass->getWeChatOrderFormat($goodsid);

        $coinClass = new Coin();
        $coin = $coinClass->getUserCoin($jdaccount);
        //是否是开普勒产品
        $iskerper = 0;
        $iskerper = $specs['kpl_sku']?1:0;
        return ['goodsInfo'=>$goodsInfo,
            'specs'=>$specs,
            'address'=>$address,
            'orderformat'=>$orderformat,
            'guanjiaid'=>$guanjaiid,
            'productInfo'=>$productinfo,
            'servicetime'=>$servicetime,
            'coin'=> $coin,
            'canrefund' => $canrefund,
            'refundcondition' => $refundcondition,
            'iskerper' => $iskerper
        ];  //v2
    }

    /**
     * @breif 获取代办管家信息
     * @param $agenceyid
     */
    private function getAgencyGood($agenceyid)
    {

    }

    public function verifySubscribeExpressGood($productid, $goodsid,$specid)
    {
        $goodModel = new GoodsModel();
        $info = $goodModel->getSubmitProductInfo($productid, $goodsid, $specid, 2);
        if (!$info) response('产品异常');
        if ($info['productstatus'] != 1) response('该商品已下线,返回商品详情页并刷新页面');
        if ($info['goodsstatus'] != 1) response('该商品已规格下线,返回商品详情页并刷新页面');
        if (!$info['nums']) response('该商品已售罄,刷新商品状态');
        return true;

    }

    public function subscribeExpressGood($productid, $goodsid,$specid, $jdaccount)
    {
        $this->verifySubscribeExpressGood($productid, $goodsid,$specid);
        $goodsModel = new GoodsModel();
        $goodsInfo = $goodsModel->getExpressBaseGoods($productid,$goodsid,$specid);
        $goodsInfo['price'] = $goodsInfo['price'] == intval($goodsInfo['price']) ? intval($goodsInfo['price']) : $goodsInfo['price'];
        if ($goodsInfo['limittype'] == 2) {
            $orderModel = new OrderModel();
            $num = $orderModel->getOrderNum($jdaccount,$productid,0,$goodsid,2);
            $goodsInfo['hasNum'] = $num;
        } else {
            $goodsInfo['hasNum'] =-1;
        }
        $goodsInfo['pic'] = C("UPLOADURL").$goodsInfo['pic'];
        return $goodsInfo;
    }

    /**
     * @breif 后台添加服务类商品
     * @param $param
     * @return bool
     */
    public function addGoods($param)
    {
        if (!$param['name']) response('商品名称不能为空');
        /* if (mb_strlen($param['name'],'utf8') >24) response('商品名称需要24字以内');*/
        if (!$param['specinfo']) response('参数错误');
        $type = $param['type'];
        $isselecttime = $param['isselecttime'];
        $caltype = $param['caltype'];
        $advancetime = $param['advancetime'];
        $booktime = $param['booktime'];
        $isselectstaff = $param['isselectstaff'];
        $staffgroup = $param['staffgroup'];
        $noservicetime = $param['noservicetime'];
        if ($type ==3) {
            if ($isselecttime === '') response('请选择是否选择服务时间');
            if ($isselecttime == 1){
                if ($caltype === '') response('请选择提前预约条件');
                if ($advancetime === '') response('请填写提前时间');
                if (!$booktime) response('请选择日期范围');
            }
            if (!$isselectstaff) response('选择下单可选人员');
            if (!$staffgroup) response('请选择可接单的员工组');
            $param['isselecttime'] = $isselecttime;
            $param['caltype'] = $caltype;
            $param['advancetime'] = $advancetime;
            $param['booktime'] = $booktime;
            $param['isselectstaff'] = $isselectstaff;
            $param['staffgroup'] = $staffgroup;
            $param['noservicetime'] = $noservicetime ? $this->ecodeNoServiceTime($noservicetime) : '';
        } else {
            $param['isselecttime'] = '';
            $param['caltype'] = '';
            $param['advancetime'] = '';
            $param['booktime'] = '';
            $param['isselectstaff'] = '';
            $param['staffgroup'] = '';
            $param['noservicetime'] = '';
        }

        $orderformat = $param['orderformat'];
        $goodModel = new \Operation\Model\GoodsModel();
        $model = M();
        $model->startTrans();
        $goodid = $goodModel->addGoods($param);
        if (!$goodid) {
            $model->rollback();
            response('添加商品异常');
        }
        $speclist = json_decode($param['specinfo'],true);
        $specs = [];
        for ($i=0; $i<count($speclist); $i++)
        {
            $oneSpec = $speclist[$i];

            for ($j=0; $j<count($oneSpec); $j++)
            {
                $temp1 = $oneSpec[$j];
                if ($temp1[0] == 'specname') {
                    if ($param['spec'] == 0 && $temp1[1]) response('无规格不能填写规格名称');
                } elseif ($temp1[0] == 'price') {
                    if (!preg_match('/^(0|[1-9][0-9]{0,9})(\.[0-9]{1,2})?$/',$temp1[1])) response('价格异常');
                } elseif ($temp1[0] == 'nums') {
                    if (!is_int(intval($temp1[1])) || !($temp1[1]>=0)) response('库存数量异常');
                } elseif ($temp1[0] == 'limitnum') {
                    if ($oneSpec[$j-1][1] ==1 && $temp1[1]) response('不限购类型不能填写份数分数');
                } elseif ($temp1[0] == 'stime') {
                    if ($type == 3 && !$temp1[1]) {
                        response('请填写正确的服务时长');
                    }
                } elseif ($temp1[0] == 'swimg') {
                    if ($type == 5) {
                        if (!$temp1[1]) {
                            response('请上传实物图片');
                        }
                    }

                }
                if ($temp1[0] == 'orginprice') {
                    if (isset($temp1[1])){
                        $specs[$i][$temp1[0]] = $temp1[1];
                    }
                } elseif ($temp1[0] == 'swimg') {
                    if ($type == 5 && $temp1[1]) {
                        $specs[$i][$temp1[0]] = $temp1[1];
                    }
                }
                else {
                    $specs[$i][$temp1[0]] = $temp1[1]?$temp1[1]:'';
                }

            }
            $specs[$i]['addtime'] = time();
            $specs[$i]['goodsid'] = $goodid;
        }

        $res = $goodModel->addSpec($specs);
        $orderClass = new Order();
        $orderformats = $orderClass->getOrderFormat($orderformat,$goodid);
        $res1 = $goodModel->addOrderFormat($orderformats);
        if ($res && $res1) {
            $model->commit();
            $endtimes = M('spec')->where(['goodsid'=>$goodid])->field('id,endtime')->select();
            for($k=0;$k<count($endtimes);$k++) {
                $endtime=$endtimes[$k]['endtime'];
                $spid = $endtimes[$k]['id'];
                if ($endtime) {
                    $tempdata = [];
                    $tempdata['id'] = "$spid";
                    $tempdata['type'] = 'offlinespec';
                    $tempdata = json_encode($tempdata);
                    $cronClass = new Crontab($tempdata);
                    $res = $cronClass->addSpecOfflineTikc($endtime);
                }
            }
            return true;
        } else {
            $model->rollback();
            return false;
        }
    }

    /**
     * @breif 后台修改服务类商品
     * @param int $goodid
     * @param $param
     * @return bool
     */
    public function saveGoods($goodid = 0, $param)
    {
        if (!$goodid) return false;
        if (!$param['name']) response('商品名称不能为空');
        /*   if (mb_strlen($param['name'],'utf8') >24) response('商品名称需要24字以内');*/
        if (!$param['specinfo']) response('参数错误');
        $type = $param['type'];
        $isselecttime = $param['isselecttime'];
        $caltype = $param['caltype'];
        $advancetime = $param['advancetime'];
        $booktime = $param['booktime'];
        $isselectstaff = $param['isselectstaff'];
        $staffgroup = $param['staffgroup'];
        $noservicetime = $param['noservicetime'];
        if ($type ==3) {
            if ($isselecttime === '') response('请选择是否选择服务时间');
            if ($isselecttime == 1) {
                if ($caltype === '') response('请选择提前预约条件');
                if ($advancetime === '') response('请填写提前时间');
                if (!$booktime) response('请选择日期范围');
            }
            if (!$isselectstaff) response('选择下单可选人员');
            if (!$staffgroup) response('请选择可接单的员工组');
            $goodData['isselecttime'] = $isselecttime;
            $goodData['caltype'] = $caltype;
            $goodData['advancetime'] = $advancetime;
            $goodData['booktime'] = $booktime;
            $goodData['isselectstaff'] = $isselectstaff;
            $goodData['staffgroup'] = $staffgroup;
            $goodData['noservicetime'] = $noservicetime ? $this->ecodeNoServiceTime($noservicetime) : '';
        } else {
            $goodData['isselecttime'] = '';
            $goodData['caltype'] = '';
            $goodData['advancetime'] = '';
            $goodData['booktime'] = '';
            $goodData['isselectstaff'] = '';
            $goodData['staffgroup'] = '';
            $goodData['noservicetime'] = '';
        }
        $goodData['name'] = $param['name'];
        $goodData['type'] = $param['type'];
        $goodData['spec'] = $param['spec'];
        $goodData['productid'] = $param['productid'];
        $goodData['paystyle'] = $param['paystyle'];
        $orderformat = $param['orderformat'];
        $goodModel = new \Operation\Model\GoodsModel();
        $delids = $param['delids'];
        //需要删除的规格
        if ($delids) {
            $model = new \Operation\Model\GoodsModel();
            $model->delSpec(explode(',', $delids));
        }
        //更新商品
        $goodModel->saveGoods($goodid, $goodData);
        //更新规格
        $speclist = $param['specinfo'];

        $speclist = json_decode($speclist,true);

       // $speclist = explode('{|}', $specinfo);
        $specs = [];
        for ($i=0; $i<count($speclist); $i++)
        {
            $oneSpec = $speclist[$i];

            $temparra = [];
            for ($j=0; $j<count($oneSpec); $j++)
            {
                $temp1 = $oneSpec[$j];
                if ($temp1[0] == 'specname') {
                    if ($param['spec'] == 0 && $temp1[1]) response('无规格不能填写规格名称');
                } elseif ($temp1[0] == 'price') {
                    if (!preg_match('/^(0|[1-9][0-9]{0,9})(\.[0-9]{1,2})?$/',$temp1[1])) response('价格异常');
                } elseif ($temp1[0] == 'nums') {
                    if (!is_int(intval($temp1[1])) || !($temp1[1]>=0)) response('库存数量异常');
                } elseif ($temp1[0] == 'limitnum') {
                    if ($oneSpec[$j-1][1] ==1 && $temp1[1]) response('不限购类型不能填写份数分数');
                } elseif ($temp1[0] == 'stime') {
                    if ($type == 3 && !$temp1[1]) {
                        response('请填写正确的服务时长');
                    }
                } elseif ($temp1[0] == 'swimg') {
                    if ($type == 5) {
                        if (!$temp1[1]) {
                            response('请上传实物图片');
                        }
                    }

                }
                if ($temp1[0] == 'orginprice') {
                    if (isset($temp1[1])){
                        $temparra[$temp1[0]] = $temp1[1];
                    }
                } elseif ($temp1[0] == 'swimg') {
                    if ($type == 5 && $temp1[1]) {
                        $temparra[$temp1[0]] = $temp1[1];
                    }
                }
                else {
                    $temparra[$temp1[0]] = $temp1[1]?$temp1[1]:'';
                }

            }
            if ($temparra['specid']) {
                $specid = $temparra['specid'];
                unset($temparra['specid']);
                $goodModel->saveSpec($specid, $temparra);
                $tempdata = [];
                $tempdata['id'] = "$specid";
                $tempdata['type'] = 'offlinespec';
                $tempdata = json_encode($tempdata);
                $cronClass = new Crontab($tempdata);
                $res = $cronClass->delOneTimeTick($tempdata,false);
                if ($temparra['endtime']) {
                    $res = $cronClass->addSpecOfflineTikc($temparra['endtime']);
                }
                unset($cronClass);


            } else {
                unset($temparra['specid']);
                $temparra['addtime'] = time();
                $temparra['goodsid'] = $goodid;
                $specs[] = $temparra;
            }

        }
        if (count($specs)) {
            $goodModel->addSpec($specs);
            $endtimes = M('spec')->where(['goodsid'=>$goodid])->field('id,endtime')->select();
            foreach ($endtimes as $row) {
                if ($row['endtime']) {
                    $tempdata = [];
                    $tempdata['id'] = $row['id'];
                    $tempdata['type'] = 'offlinespec';
                    $tempdata = json_encode($tempdata);
                    $cronClass = new Crontab($tempdata);
                    $res = $cronClass->addSpecOfflineTikc($row['endtime']);
                    unset($cronClass);
                }
            }
        }
        //编辑自定义
        $orderClass = new Order();
        $orderformat = $orderClass->editOrderFormat($orderformat,$goodid);
        $deldata = $orderformat['delData'];
        $editData = $orderformat['editData'];
        $addData = $orderformat['addData'];
        if (count($deldata)) {
            $goodModel->delOrderFormat($deldata);
        }
        foreach ($editData as $id=>$data) {
            $goodModel->editOrderFormat($id, $data);
        }
        if (count($addData)) {
            $goodModel->addOrderFormat($addData);
        }
        return true;
    }

    /**
     * @breif 后台添加快递类商品
     * @param $param
     * @return mixed
     */
    public function addExpressGoods($param)
    {
        $name = $param['name'];
        $pic = $param['pic'];
        $nums = $param['nums'];
        $limitnum = $param['limitnum'];
        $limittype = $param['limittype'];
        $price = $param['price'];
        $status  =$param['status'];
        $productid = $param['productid'];
        $orginprice = $param['orginprice'];
        if (!$name) response('请输入产品名称');
      /*  if (mb_strlen($name) > 24) response('产品名称最多输入24字');*/
        if (!$pic) response('请上传图片');
        if (!$nums) response('请输入价格');
        if ($limittype >1  && !$limitnum) response('请输入正确的限购数量');
        if (!preg_match('/^(0|[1-9][0-9]{0,9})(\.[0-9]{1,2})?$/',$price)) response('请输入正确的价格');
        $goodModel = new \Operation\Model\GoodsModel();
        if ($goodModel->hasGoodsSpec($param['name'],$param['productid'])) response('商品已存在');
        $temp['name'] = '快递商品';
        $temp['productid'] = $productid;
        $temp['status'] = 1;
        $goodid = $goodModel->addGoods($temp);
        $data['goodsid'] = $goodid;
        $data['specname'] = $name;
        $data['pic'] = $pic;
        $data['nums'] = $nums;
        $data['limitnum'] = $limitnum;
        $data['limitype'] = $limittype;
        $data['price'] = $price;
        $data['status'] = $status;
        $data['addtime'] = time();
        $data['productid'] = $productid;
        $data['orginprice'] = $orginprice;
        $goodsModel = new \Operation\Model\GoodsModel();
        return $goodsModel->addGoodsSpec($data);
    }

    /**
     * @breif 后台修改快递类商品
     * @param $param
     * @return bool
     */
    public function saveExpressGoods($param)
    {
        $name = $param['name'];
        $pic = $param['pic'];
        $nums = $param['nums'];
        $limitnum = $param['limitnum'];
        $limittype = $param['limittype'];
        $price = $param['price'];
        $status  =$param['status'];
        $id = $param['id'];
        $orginprice = $param['orginprice'];
        if (!$name) response('请输入产品名称');
      /*  if (mb_strlen($name) > 24) response('产品名称最多输入24字');*/
        if (!$pic) response('请上传图片');
        if ($limittype >1  && !$limitnum) response('请输入正确的限购数量');
        if (!preg_match('/^(0|[1-9][0-9]{0,9})(\.[0-9]{1,2})?$/',$price)) response('请输入正确的价格');
        $goodModel = new \Operation\Model\GoodsModel();
        $info = $goodModel->getGoodsSpecName($id);
        $oldgoodname = $info['specname'];
        $productid = $info['productid'];
        if ($oldgoodname != $param['name'] && $goodModel->hasGoodsSpec($param['name'],$productid)) response('商品已存在');
        $data['specname'] = $name;
        $data['pic'] = $pic;
        $data['nums'] = $nums;
        $data['limitnum'] = $limitnum;
        $data['limitype'] = $limittype;
        $data['price'] = $price;
        $data['status'] = $status;
        $data['orginprice'] = $orginprice;
        $goodModel->saveExpressGoods($id, $data);
        return true;
    }

    /**
     * @param $guanjiaid
     * @param $categroyname        format: 海外-留学
     * @param int $nowproductid
     */
    public function getRecommendProdct($guanjiaid, $categroyname, $nowproductid = 0, $area,$limit = 5)
    {
        $goodsModel = new GoodsModel();
//        $area = implode(',', $area);
        if (!in_array( '全国', $area)) {
            $where['p.servicecity'] = [];
            $where['p.servicecity'][] = ['like','%全国%'];

            foreach ($area as $k => $v) {
                $where['p.servicecity'][] = ['like','%'.$v.'%'];
            }

            $where['p.servicecity'][] = 'or';
        }
        $where['p.id'] =['not in',[$nowproductid]];
        $where['p.guanjiaid'] = $guanjiaid;
        $where['p.status'] = 1;
        $products = $goodsModel->getRecommendProdct($where);
        //同一管家内
        if (count($products) >= $limit) {
            $productids = $this->getRandomProduct($products,$categroyname,$limit) ;
        } else {

            $productids = array_column($products, 'id');
            $limit = $limit - count($products);
            $where['p.guanjiaid'] = ['neq', $guanjiaid];
            $products = $goodsModel->getRecommendProdct($where);
            $tempProductids = $this->getRandomProduct($products, $categroyname, $limit);
            $productids = array_merge($productids, $tempProductids);
        }
        if (!count($productids)) {
            return [];
        }
        unset($where);
        $where['p.id'] =['in',$productids];
        $productInfo = $this->getProduct(0,0,1,false,false,$where);
        $tempProductInfo = [];
        foreach ($productids as $id) {
            foreach ($productInfo as $row) {
                if ($id == $row['id']) {
                    $tempProductInfo[] = $row;
                    break;
                }
            }
        }
        return $tempProductInfo;
    }

    private function getRandomProduct($data = [], $categoryname, $limit)
    {
        if (!count($data)) return [];
        $products =[];
        $sameGuanJiaProduct = [];
        $dirffentGuanJiaProduct = [];
        foreach ($data as $row) {
            $nowcategoryname = $row['categoryname'];
            if ($nowcategoryname == $categoryname) {
                $sameGuanJiaProduct[] = $row;
            } else {
                $dirffentGuanJiaProduct[] = $row;
            }
        }
        //相同二级分类足够
        if (count($sameGuanJiaProduct) >= $limit) {
            $products = $this->getRandomLimitProduct($sameGuanJiaProduct, $limit);
            return $products;
        } else {
            $products = array_column($sameGuanJiaProduct,'id');
            $yijisameproduct = [];
            $yijidifferentproduct = [];
            $limit = $limit - count($sameGuanJiaProduct);
            $yijiname = explode('-',$categoryname)[0];
            foreach ($dirffentGuanJiaProduct as $row) {
                $nowcategoryname = $row['categoryname'];
                $nowyijiname = explode('-',$nowcategoryname)[0];
                if ($yijiname == $nowyijiname) {
                    $yijisameproduct[] = $row;
                } else {
                    $yijidifferentproduct[] = $row;
                }
            }
            if (count($yijisameproduct) >= $limit) {
                $tempProducts = $this->getRandomLimitProduct($yijisameproduct, $limit);
                $products = array_merge($products,$tempProducts);
                return $products;
            } else {
                $limit = $limit - count($yijisameproduct);
                $tempProducts = array_column($yijisameproduct,'id');
                $temp1Products = $this->getRandomLimitProduct($yijidifferentproduct, $limit);
                $products = array_merge($products,$tempProducts, $temp1Products);

                return $products;
            }
        }

    }

    private function getRandomLimitProduct($data, $limit)
    {
        $keys = array_rand($data,$limit);
        $products = [];
        if ($limit == 1) {
            $products = [$data[$keys]['id']];
        } else {
            for ($i=0; $i<count($keys); $i++) {
                $products[] = $data[$keys[$i]]['id'];
            }
        }
        return $products;


    }

    public function getAllSpecByGoodsid($id,$jdaccount,$productid,$selectspec = '')
    {
        $goodsid = $id;
        $goodsModel = new GoodsModel();
        $allSpec = $goodsModel->getAllSpec($goodsid,$selectspec);
        $orderModel = new OrderModel();
        for ($i = 0; $i < count($allSpec); $i++) {
            $specid = $allSpec[$i]['id'];
            if ($allSpec[$i]['limitype'] != 2) {
                $allSpec[$i]['hasNum'] = -1;
            } else {
                $allSpec[$i]['hasNum'] = $orderModel->getOrderNum($jdaccount, $productid, $goodsid, $specid);
            }
            if ($allSpec[$i]['price'] == intval($allSpec[$i]['price'])) $allSpec[$i]['price'] = intval($allSpec[$i]['price']);
        }
        return $allSpec;
    }


    /**
     * @brefi 解析服务时间
     * @param $servietime
     */
    //format 2018-5-17,2018-5-25,1;2;3;4;5|1,4;23
    private function parseServicetimeToJson($servietime)
    {
        if (!$servietime) return '';            //不选择服务时间
        $servietime = explode('|',$servietime);
        if (count($servietime) != 2) return false;
        $servie = explode(',',$servietime[0]);
        $booking = explode(',',$servietime[1]);
        $starttime = isset($servie[0])?$servie[0]:'';
        $endtime = isset($servie[1])?$servie[1]:'';
        $timearea = isset($servie[2])?$servie[2]:'';
        $scheduletype = isset($booking[0])?$booking[0]:'';
        $time = isset($booking[1])?$booking[1]:'';
        if (!$time) return false;
        if (!$starttime) return false;
        if (!$endtime) return false;
        if ($endtime < $starttime) return false;
        if ($scheduletype == 1) $time = explode(';',$time);
        $data['starttime'] = $starttime;
        $data['endtime'] = $endtime;
        $data['timearea'] = explode(';',$timearea);
        $data['scheduletype'] = $scheduletype;
        if ($scheduletype == 1) {
            $data['daynum'] = $time[0];
            $data['hourtime'] = $time[1];
        } else {
            $data['hourtime'] = $time;
        }
        return json_encode($data);
    }

    public function getServiceTime($servicetime)
    {
        if (!$servicetime) return '';
        $servicetime = json_decode($servicetime, true);
        $starttime = $servicetime['starttime'];
        $endtime = $servicetime['endtime'];
        $timearea = $servicetime['timearea'];
        $scheduletype = $servicetime['scheduletype'];
        $nowtime = time();
        $maxtimearea = max($timearea);
        $maxtime = strtotime(Date($endtime.' '.$maxtimearea.':00'));
        if ($nowtime > $maxtime) return '';   //无可选时间
        if ($scheduletype == 1) {
            $daynum = $servicetime['daynum'];
            $daynum = intval($daynum);
            $hourtime = $servicetime['hourtime'];
            $nowmaxdaytime = strtotime(Date("Y-m-d ".$hourtime.':00'));
            if ($nowtime > $nowmaxdaytime) $daynum++;
            $mintime = strtotime(Date('Y-m-d 00:00',strtotime('+'.$daynum.' day')));
            if ($mintime > $maxtime) return ''; //无可选服务
        } else {
            $hourtime = $servicetime['hourtime'];
            $hourtime = intval($hourtime);
            $mintime = $hourtime*3600 + $nowtime;
            $mintime = strtotime(Date("Y-m-d G:00",$mintime));
            if ($mintime > $maxtime) return ''; //无可选时间
        }
        $alltime = [];
        for ($i = strtotime($starttime);$i<= strtotime($endtime);$i = $i+86400)
        {
            $datetime = Date("Y-m-d",$i);
            foreach ($timearea as $row) {
                if ($mintime < strtotime($datetime.' '.$row.':00')) {
                    $alltime[$datetime][] = $row;
                }
            }
        }
        $data = [];
        foreach ($alltime as $key =>$time)
        {
            $data[] =[
                'date'=>$key,
                'week'=>getWeekByDate($key),
                'time'=>$time
            ];
        }
        return $data;
    }


    /**
     * @breif 解析商品不可服务时间
     * @param $servicetime
     */
    //format 2018/1/1-2018/1/5,1;2;3;4;5;6;0|,2;4;5
    public function ecodeNoServiceTime($noservicetime)
    {
        if (!$noservicetime) return false;
        $noservicetime = explode('|',$noservicetime);
        $data = [];
        foreach ($noservicetime as $row) {
            $temp = explode(',', $row);

            if (count($temp) !=2) return false;
            $data[] = [
                'date'=>$temp[0]?explode('-',$temp[0]):'',
                'weekends'=>$temp[1]?explode(';',$temp[1]):''
            ];
        }
        return json_encode($data);
    }

    //json字段
    private function decodeNoServiceTime($noservicetime)
    {
        if (!$noservicetime) return false;
        return json_decode($noservicetime, true);
    }
    private  function verifyDayCanService($day, $rule)
    {
        if (!$rule) return true;   //无不可以服务时间直接成功
        $day = strtotime($day);
        $nowweekend = date('w', $day);
        foreach ($rule as $row) {
            $date = $row['date'];
            $weekends = $row['weekends'];
            if ($date && $weekends) {
                $starttime = strtotime($date[0]);
                $entime = strtotime($date[1]);
                if ($day>= $starttime && $day<=$entime && in_array($nowweekend,$weekends)) return false;
            } elseif ($date) {
                $starttime = strtotime($date[0]);
                $entime = strtotime($date[1]);
                if ($day>= $starttime && $day<=$entime) return false;
            } elseif ($weekends) {
                if (in_array($nowweekend,$weekends)) return false;
            }
        }
        return true;
    }
    /**
     * @breif 计算可该商品可预约的时间
     * @param $caltype 计算类型 1固定时间 2自然日计算
     * @param $advancetime 提前多少时间
     * @param $booktime 预约日期范围
     * @param $noservicetime 不能服务时间
     * @param $stime     上门时间
     * @param $sinterval 接单间隔
     * @return bool
     */
    private function calTheGoodsBookingDate($nowtime,$caltype, $advancetime, $booktime, $noservicetime,$stime,$sinterval)
    {
        if (!$stime) return '';
        if (!$sinterval) return '';
        $lastcantime = 0;
        $stime = explode(',',$stime);
        $length = count($stime);
        $noservicetime = $this->decodeNoServiceTime($noservicetime);
        if ($caltype == 2) {
            $advancetime = explode(',',$advancetime);
            $day = $advancetime[0];
            $time = $advancetime[1];
            if ($nowtime > strtotime(Date("Y-m-d $time"))) {
                $lastcantime = strtotime(Date("Y-m-d"))+3600*24*($day+1);
            } else {
                $lastcantime = strtotime(Date("Y-m-d"))+3600*24*$day;
            }
        } else {
            $lastcantime = $nowtime+$advancetime*3600;
        }

        if ($length >1) {
            for ($i = 0; $i < $length -1; $i++) {
                $onetime = explode('-',$stime[$i]);
                $smtime = strtotime('2018-8-8 '.$onetime[0]);
                for ($j = $i+1;$j<$length;$j++) {
                    $temptime = explode('-',$stime[$j]);
                    $smtime1 = strtotime('2018-8-8 '.$temptime[0]);
                    if ($smtime > $smtime1) {
                        $temp = $stime[$i];
                        $stime[$i] = $stime[$j];
                        $stime[$j] = $temp;
                    }
                }
            }
        }
        $newstime = [];
        $j = 0;
        for ($i=0;$i<count($stime);$i++) {
            if (!count($newstime)) {
                $temp1 =explode('-',$stime[$i]) ;
                $big = strtotime('2018-8-8 '.$temp1[1]);


                $temp2 =explode('-',$stime[$i+1]) ;
                $small = strtotime('2018-8-8 '.$temp2[0]);
                $j = 1;
                if ($big == $small) {
                    $newstime[$j] = $temp1[0].'-'.$temp2[1];
                    $i++;
                } else {
                    $newstime[$j] = $stime[$i];
                }

            } else {
                $temp1 =explode('-',$newstime[$j]) ;
                $big = strtotime('2018-8-8 '.$temp1[1]);
                $temp2 = explode('-',$stime[$i]);
                $small = strtotime('2018-8-8 '.$temp2[0]);

                if ($big == $small) {
                    $newstime[$j] = $temp1[0].'-'.$temp2[1];
                } else {
                    $j++;
                    $newstime[$j] = $stime[$i];
                }
            }
        }
        $allseelcttime = [];
        for ($i=strtotime(Date("Y-m-d"));$i <strtotime(Date("Y-m-d"))+24*3600*$booktime;$i = $i+3600*24) {
            if ($this->verifyDayCanService(Date('Y-m-d',$i),$noservicetime)) {
                foreach ($newstime as $row) {
                    $temp = explode('-',$row);
                    $starttime = $temp[0];
                    $endtime = $temp[1];
                    $k = 0;
                    while (true) {
                        $temp = strtotime(Date("Y-m-d",$i).' '.$starttime)+$k;
                        if ($temp > strtotime(Date("Y-m-d",$i).' '.$endtime)) break;
                        if ($lastcantime <= $temp) {
                            $allseelcttime[Date("Y-m-d",$i)][] =Date("G:i",$temp);
                        }
                        $k+=$sinterval*60;
                    }

                }
            }

        }
        $data = '';
        foreach ($allseelcttime as $key =>$time)
        {
            $data[] =[
                'date'=>$key,
                'week'=>getWeekByDate($key),
                'time'=>$time
            ];
        }
        return $data;

    }

    public function getAllgoodsAndSpec($productid)
    {
        $goodsModel = new \Operation\Model\GoodsModel();
        $goodsinfo = $goodsModel->getAllgoodsAndSpec($productid);
        $data = [];
        foreach ($goodsinfo as $row)
        {
            $data[] = [
              'id'=>$row['goodid'].'-'.$row['specid'],
              'name'=>$row['goodname'].($row['specname']?('-'.$row['specname']):'')
            ];
        }
        return $data;
    }
}