<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/11/2
 * Time: 17:56
 */
/*
 * 所有产品商品的数据库操作
 * */
namespace WeChat\Model;


use function GuzzleHttp\Psr7\rewind_body;
use Server\Card;
use Think\Page;

class GoodsModel
{
    public function addProduct($param)
    {
        $productMdoel = M("product");
        return $productMdoel->data($param)->add();
    }

    public function saveProduct($id, $param)
    {
        $productMdoel = M("product");
        $where['id'] = $id;
        return $productMdoel->where($where)->save($param);
    }

    public function hasProductName($name,$guanjiaid)
    {
        $where['name'] = $name;
        $where['guanjiaid'] = $guanjiaid;
        $productMdoel = M("product");
        return $productMdoel->where($where)->limit(1)->find();
    }

    public function productList( $p = 1,$type = '', $producttype = 0,$condition = '', $yijiname = '', $erjiname = '',$servicity = '', $status = 0, $limit = 20,$ptype = '',$isexcel = 0 )
    {
        $where = [];
        if ($type == 1 && $condition) {
            $where['p.id'] = $condition;
        } elseif ($type == 2 && $condition) {
            $where['p.name'] = ['like',"%$condition%"];
        } elseif ($type == 3 && $condition) {
            if (!$isexcel) {
                $where['g.guanjianame'] = ['like', "%$condition%"];
            } else {
                $where['gj.guanjianame'] = ['like', "%$condition%"];
            }

        } elseif ($type == 4 && $condition) {

            if (!$isexcel) {
                $where['s.supplier'] = ['like',"%$condition%"];
            } else {
                $where['su.supplier'] = ['like',"%$condition%"];
            }
        } elseif ($type ==5) {
            $where['p.kpl_sku'] = $condition;
        }
        if ($servicity) {
            $areaModel = M("areanew");
            $tempres = $areaModel->where(['areaname'=>$servicity])->limit(1)->find();
            if ($servicity == '全国') {
                $where['_string'] = "p.servicecity like '%全国%'";
            } elseif ($tempres['level'] == 1) {
                $where['_string'] = "(p.servicecity like '%".$tempres['areaname']."%' or p.servicecity like '%全国%')";
            } elseif ($tempres['level'] == 2) {
                $pid = $tempres['parentareaid'];
                $tempres1 = $areaModel->where(['areaid'=>$pid])->limit(1)->find();
                $where['_string'] = "(p.servicecity like '%".$tempres['areaname']."%'or p.servicecity like '%".$tempres1['areaname']."%' or p.servicecity like '%全国%')";
            } elseif ($tempres['level'] == 3) {
                $pid = $tempres['parentareaid'];
                $tempres1 = $areaModel->where(['areaid'=>$pid])->limit(1)->find();
                $pid1 = $tempres1['parentareaid'];
                $tempres2 = $areaModel->where(['areaid'=>$pid1])->limit(1)->find();
                $where['_string'] = "(p.servicecity like '%".$tempres['areaname']."%'or p.servicecity like '%".$tempres1['areaname']."%' or p.servicecity like '%".$tempres2['areaname']."%' or p.servicecity like '%全国%')";
            }
        }
        if ($status) {
            $where['p.status'] = $status;
        }
        if ($producttype) {
            $where['p.type'] = $producttype;
        }
        if ($yijiname && $erjiname) {
            $where['p.categoryname'] = $yijiname.'-'.$erjiname;
        } elseif ($yijiname) {
            $where['p.categoryname'] = ['like',"$yijiname-%"];
        }
        if ($ptype) $where['p.ptype'] = $ptype;
        if (!$isexcel) {
            $count = $this->productTotalNum($where);
            $Page = new Page($count, $limit);
            $productMdoel = M("product as p");
            $Page->nowPageage = $p;
            $res = $productMdoel
                ->field('p.id,p.kpl_sku,p.name,p.categoryid,p.categoryname,p.type,p.status,p.servicecity,g.guanjianame,g.guanjiaphone,u.phone as bdphone, u.name as bdname,p.isrecommend,p.weight,p.showstarttime,p.showendtime,s.supplier,s.id as supplierid,p.ptype,p.kpl_sku')
                ->join('__GUANJIA__ g ON p.guanjiaid = g.id','left')
                ->join('__USER__ u ON p.userid = u.id','left')
                ->join('__SUPPLIER__ as s ON p.supplierid = s.id','left')
                ->where($where)
                ->order('p.addtime desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
            foreach ($res as $key => $row) {
                $res[$key]['showstarttime'] = Date('Y.m.d G.i.s',$row['showstarttime']);
                $res[$key]['showendtime'] = Date('Y.m.d G.i.s',$row['showendtime']);
                $res[$key]['supplier'] = $row['supplier'].'('.$row['supplierid'].')';
                if(!empty($res[$key]['categoryname'])){
                    $res[$key]['categoryname'] = $row['categoryname'].'('.$row['categoryid'].')';
                }
                $servicecity = explode('|',$row['servicecity']);
                $tempservicecity = [];
                foreach($servicecity as $keyy => $roww){
                    $temp = explode(',',$roww);
                    $tempservicecity[] = $temp[1];
                }
                $tempservicecity = implode(',',$tempservicecity);
                $res[$key]['servicecity'] = $tempservicecity;
            }
            $data['Page'] = $Page;
            $data['res'] = $res;
            $data['count'] = $count;
        } else {
            $productMdoel = M("product as p");
            $res = $productMdoel
                ->field('p.id,p.name,p.ptype,p.servicecity,p.categoryname as levelone,p.categoryname as leveltwo,su.supplier,su.id as supplierid,gj.guanjianame,gj.id as guanjiaid,p.messagetype,p.status,p.bookingtype,s.specname,g.name as goodsname,s.price,s.nums,s.limitype,s.limitnum,s.status as specstatus,s.orginprice,s.minnum,s.settletype,s.settlevalue,s.endtime')
                ->join('supplier su ON p.supplierid = su.id')
                ->join('guanjia gj ON p.guanjiaid = gj.id')
                ->join('goods g ON p.id = g.productid')
                ->join('spec s ON g.id = s.goodsid')
                ->where($where)
                ->order('p.addtime desc')
                ->select();
            if($res){
                foreach($res as $key=>$row){
                    $producttype=$row['ptype'];
                    if($producttype==1){
                        $res[$key]['ptype']='其它服务';
                    }elseif($producttype==2){
                        $res[$key]['ptype']='在线咨询';
                    }elseif($producttype==3){
                        $res[$key]['ptype']='上门服务';
                    }elseif($producttype==4){
                        $res[$key]['ptype']='到店服务';
                    }else{
                        $res[$key]['ptype']='实物商品';
                    }

                    $category=$row['levelone'];
                    $category = explode('-',$category);
                    $res[$key]['levelone'] = $category[0];
                    $res[$key]['leveltwo'] = $category[1];

                    $message=$row['messagetype'];
                    if($message==0){
                        $res[$key]['messagetype'] ='默认短信';
                    }elseif($message==1){
                        $res[$key]['messagetype'] ='自定义预定短信';
                    }else{
                        $res[$key]['messagetype'] ='不发送';
                    }

                    $pstatus=$row['status'];
                    if($pstatus==1){
                        $res[$key]['status'] ='上线';
                    }else{
                        $res[$key]['status'] ='下线';
                    }

                    $bookingtype=$row['bookingtype'];
                    if($bookingtype==0){
                        $res[$key]['bookingtype'] ='未设置';
                    }elseif($bookingtype==1){
                        $res[$key]['bookingtype'] ='需要供应商确认';
                    }else{
                        $res[$key]['bookingtype'] ='直接预订成功';
                    }

                    $specstatus=$row['specstatus'];
                    if($specstatus==1){
                        $res[$key]['specstatus'] ='上线';
                    }else{
                        $res[$key]['specstatus'] ='下线';
                    }

                    $settletype=$row['settletype'];
                    if($settletype==0){
                        $res[$key]['settletype'] ='初始化未设置';
                    }elseif($settletype==1){
                        $res[$key]['settletype'] ='固定结算金额'.$row['settlevalue'].'元';
                    }elseif($settletype==2){
                        $res[$key]['settletype'] ='固定佣金比例'.$row['settlevalue'];
                    }else{
                        $res[$key]['settletype'] ='固定佣金金额'.$row['settlevalue'].'元';
                    }

                    if($row['limitype']==1){
                        $res[$key]['limitype'] = '不限购';
                    }else{
                        $res[$key]['limitype'] = '限购'.$row['limitnum'].'份';
                    }
                    $res[$key]['price'] = floatval($row['price']);
                    $res[$key]['orginprice'] = floatval($row['orginprice']);

                    if($row['endtime']){
                        $res[$key]['endtime'] = Date('Y.m.d G.i.s',$row['endtime']);
                    }
                    if ($row['specname']) {
                        $res[$key]['specname'] = $row['goodsname'].'+'.$row['specname'];
                    } else {
                        $res[$key]['specname'] = $row['goodsname'];
                    }
                    unset(  $res[$key]['goodsname'] );
                    unset(  $res[$key]['settlevalue'] );
                    unset($res[$key]['limitnum']);
                }
            }
            $data =$res;
        }


        return $data;

    }

    //开普勒产品列表数据库查询
    public function kplproductList( $p = 1,$type = '',$condition='',$limit = 50){
        $where=[];
        if ($type == 1 && $condition) {
            $where['sku'] = $condition;
        } elseif ($type == 2 && $condition) {
            $where['name'] = ['like',"%$condition%"];
        }
        $count = $this->kplproductTotalNum($where);
        $Page = new Page($count, $limit);
        $kplproductMdoel = M("keplerlist");
        $Page->nowPageage = $p;
        $res=$kplproductMdoel->field('sku,is_online,state,brandName ,name,jdprice,price,productArea,upc,saleUnit,category,page_num,page_name,introduction')->where($where)->order('sku asc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $data['Page'] = $Page;
        $data['res'] = $res;
        $data['count'] = $count;
        return $data;
    }

    //统计开普勒产品数量
    public function kplproductTotalNum($where){
        $kplproductModel=M("keplerlist");
        $nums=$kplproductModel->where($where)->count();
        return $nums;


    }




    private function productTotalNum($where)
    {
        $productMdoel = M("product as p");
        $num = $productMdoel
                ->join('__GUANJIA__ g ON p.guanjiaid = g.id','left')
                ->join('__USER__ u ON p.userid = u.id','left')
                ->join('__SUPPLIER__ s ON p.supplierid = s.id','left')
                ->where($where)
                ->count();
        return $num;
    }

    public function getOneProductIdAndName($id)
    {
        $where['id'] = $id;
        $productMdoel = M('product');
        $info = $productMdoel->field('id,name')->where($where)->limit(1)->find();
        return $info;
    }

    public function getOneProductIdAndName1($id)
    {
        if(is_numeric($id)){
            $where['id'] = $id;
        }else{
            $where['name'] = ['like','%'.$id.'%'];
        }
        $productMdoel = M('product');
        $info = $productMdoel->field('id,name')->where($where)->limit(1)->find();
        return $info;
    }

    public function getProductIdAndName($where)
    {
        $productMdoel = M('product');
        $info = $productMdoel->field('id,name')->where($where)->select();
        return $info;
    }

    public function getOneProduct($id)
    {
        $where['id'] = $id;
        $productMdoel = M('product');
        $info = $productMdoel->field('id,name,serviceinfo,servicetime,productpic,facepic,type,isshipping,categoryname,servicecity,guanjiaid,userid,productinfo,notes,status,endtime,isrecommend,weight,showstarttime,showendtime,messageid,bookingtype,sortweight,supplierid,messagetype,ptype')->where($where)->limit(1)->find();
        list($info['yiji'],$info['erji']) = explode('-',$info['categoryname']);
        $info['productpicstr'] = $info['productpic'];
        $info['facepicstr'] = $info['facepic'];
        $info['productpic'] = explode(',', $info['productpic']);
        $info['facepic'] = explode(',', $info['facepic']);
        $info['endtime'] = $info['endtime'] ?Date("Y-m-d G:i:s", $info['endtime']): '';
        $info['productinfo'] = htmlspecialchars_decode($info['productinfo']);
        $info['notes'] = htmlspecialchars_decode($info['notes']);
        $info['serviceinfo'] = explode('+-',$info['serviceinfo']);
        if ($info['showstarttime']) $info['showstarttime'] = Date('Y-m-d G:i:s',$info['showstarttime']);
        if ($info['showendtime']) $info['showendtime'] = Date('Y-m-d G:i:s', $info['showendtime']);
        if (empty($info['showstarttime'])) $info['showstarttime'] = '';
        if (empty($info['showendtime'])) $info['showendtime'] = '';
        if (empty($info['weight'])) $info['weight'] = '';
        return $info;
    }

    public function getProdcutListInfoById($ids)
    {
        $productModel = M('product as p');
        $where['p.status'] = 1;
        $where['s.isdelete'] = 0;
        $where['p.id'] = ['in', $ids];
        $productList = $productModel
            ->field('p.id,j.guanjianame,p.name,p.type,p.guanjiaid,p.facepic,serviceinfo,count(p.id) as onenum')
            ->join('__GOODS__ as g ON p.id = g.productid')
            ->join('__GUANJIA__ as j ON p.guanjiaid = j.id')
            ->join('__SPEC__ as s ON g.id = s.goodsid')
            ->where($where)
            ->group('p.id')
            ->order('p.id desc')
            ->select();
        $productids = array_column($productList,'id');
        if (!count($productids)) {
            return [];
        }
        $goodsModel = M('goods as g');
        unset($where1);
        $pricelist = $goodsModel
            ->field('g.productid,s.price,s.nums,s.status,s.orginprice')
            ->join('__SPEC__ as s ON g.id = s.goodsid')
            ->where(['s.isdelete'=>0])
            ->select();
        $tempList = [];
        foreach ($pricelist as $key => $row) {
            $productid = $row['productid'];
            $price = $row['price'];
            $orginprice = $row['orginprice'];
            if (!isset($tempList[$productid]['minorginprice'])) {
                $tempList[$productid]['minorginprice'] = $orginprice;
                $tempList[$productid]['orginunqiue'] = 1;
            } else {
                if ($orginprice >0) {
                    if ($tempList[$productid]['minorginprice'] != $orginprice && $tempList[$productid]['minorginprice'] >0) {
                        $tempList[$productid]['orginunqiue'] = 0;
                    }
                    if ($tempList[$productid]['minorginprice'] == 0 || $tempList[$productid]['minorginprice'] > $orginprice) {
                        $tempList[$productid]['minorginprice'] = $orginprice;
                    }
                }
            }
            if (!isset($tempList[$productid]['minprice']) ) {
                $tempList[$productid]['minprice'] = $price;
                $tempList[$productid]['unqiue'] = 1;
            } else {
                if ($tempList[$productid]['minprice'] != $price) {
                    $tempList[$productid]['unqiue'] = 0;
                }
                if ($tempList[$productid]['minprice'] > $price) {
                    $tempList[$productid]['minprice'] = $price;
                }
            }

            if ($row['nums'] > 0 && $row['status'] == 1) {
                if (!isset($tempList[$productid]['orginprice'])) {
                    $tempList[$productid]['orginprice'] = $orginprice;
                    $tempList[$productid]['isorginunqiue'] = 1;
                } else {
                    if ($orginprice >0) {
                        if ($tempList[$productid]['orginprice'] != $orginprice && $tempList[$productid]['orginprice'] >0) {
                            $tempList[$productid]['isorginunqiue'] = 0;
                        }
                        if ($tempList[$productid]['orginprice'] == 0 ||$tempList[$productid]['orginprice'] > $orginprice) {

                            $tempList[$productid]['orginprice'] = $orginprice;
                        }
                    }
                }
                if (!isset($tempList[$productid]['price'])) {
                    $tempList[$productid]['price'] = $price;
                    $tempList[$productid]['ispriceunqiue'] = 1;
                } else {
                    if ($price != $tempList[$productid]['price']) {
                        $tempList[$productid]['ispriceunqiue'] = 0;
                    }
                    if ($price < $tempList[$productid]['price']) {
                        $tempList[$productid]['price'] = $price;
                    }
                }

            }

        };
        foreach ($productList as $key=> $row) {
            $id = $row['id'];
            if (isset($tempList[$id]['price'])) {
                $productList[$key]['price'] = $tempList[$id]['price'];
                $productList[$key]['ispriceunqiue'] = $tempList[$id]['ispriceunqiue'];
            } else {
                $productList[$key]['price'] = $tempList[$id]['minprice'];
                $productList[$key]['ispriceunqiue'] = $tempList[$id]['unqiue'];
            }
            if (isset($tempList[$id]['orginprice'])) {
                $productList[$key]['orginprice'] = $tempList[$id]['orginprice'];
                $productList[$key]['isorginunqiue'] = $tempList[$id]['isorginunqiue'];
            } else {
                $productList[$key]['orginprice'] = $tempList[$id]['minorginprice'];
                $productList[$key]['isorginunqiue'] = $tempList[$id]['orginunqiue'];
            }
        }
        return $productList;
    }

    /**
     * @breif 微信端获取管家产品列表
     * @param $guanjiaid
     */
    public function getProductListInfo($guanjiaid = 0, $limit = 0, $nowpage =1, $isfiter = false, $ishome = false, $where = []  )
    {
        $productModel = M('product as p');
        if ($guanjiaid) {
            $where['p.guanjiaid'] = $guanjiaid;
        }
        $unshowProduct = C('UNSHOWPRODUCT');
        if (is_array($unshowProduct) && count($unshowProduct) && $isfiter) {
            $where['p.id'] = ['not in',$unshowProduct];
        }
        
        $where['p.status'] = 1 ; 
        
        
        $where['s.isdelete'] = 0;

        if ($ishome) {                                            //首页列表查询
            $where['p.isrecommend'] = 1;
            $time = time();
            $where['p.showstarttime'] = ['elt',$time];
            $where['p.showendtime'] = ['egt',$time];
            if ($limit) {
                $productList = $productModel
                    ->field('p.id,j.guanjianame,j.avatarurl,j.title as guanjiafenlei,p.name,p.type,p.guanjiaid,p.facepic,serviceinfo,count(p.id) as onenum')
                    ->join('__GOODS__ as g ON p.id = g.productid')
                    ->join('__GUANJIA__ as j ON p.guanjiaid = j.id')
                    ->join('__SPEC__ as s ON g.id = s.goodsid')
                    ->where($where)
                    ->group('p.id')
                    ->having('sum(s.nums)>0')
                    ->order('p.weight desc,p.id desc')
                    ->page($nowpage)
                    ->limit($limit)
                    ->select();
            } else {
                $productList = $productModel
                    ->field('p.id,j.guanjianame,j.avatarurl,j.title as guanjiafenlei,p.name,p.type,p.guanjiaid,p.facepic,serviceinfo,count(p.id) as onenum')
                    ->join('__GOODS__ as g ON p.id = g.productid')
                    ->join('__GUANJIA__ as j ON p.guanjiaid = j.id')
                    ->join('__SPEC__ as s ON g.id = s.goodsid')
                    ->where($where)
                    ->group('p.id')
                    ->having('sum(s.nums)>0')
                    ->order('p.weight desc,p.id desc')
                    ->select();

            }

        } else {
            if ($limit) {
                $productList = $productModel
                    ->field('p.id,j.guanjianame,j.avatarurl,j.title as guanjiafenlei,p.name,p.type,p.guanjiaid,p.facepic,serviceinfo,count(p.id) as onenum')
                    ->join('__GOODS__ as g ON p.id = g.productid')
                    ->join('__GUANJIA__ as j ON p.guanjiaid = j.id')
                    ->join('__SPEC__ as s ON g.id = s.goodsid')
                    ->where($where)
                    ->group('p.id')
                    ->order('p.sortweight desc,p.id desc')
                    ->page($nowpage)
                    ->limit($limit)
                    ->select();
            } else {
                $productList = $productModel
                    ->field('p.id,j.guanjianame,j.avatarurl,j.title as guanjiafenlei,p.name,p.type,p.guanjiaid,p.facepic,serviceinfo,count(p.id) as onenum')
                    ->join('__GOODS__ as g ON p.id = g.productid')
                    ->join('__GUANJIA__ as j ON p.guanjiaid = j.id')
                    ->join('__SPEC__ as s ON g.id = s.goodsid')
                    ->where($where)
                    ->group('p.id')
                    ->order('p.sortweight desc,p.id desc')
                    ->select();
            }
        }
        $productids = array_column($productList,'id');
        if (!count($productids)) {
            return [];
        }
        $goodsModel = M('goods as g');
        unset($where1);
        $pricelist = $goodsModel
            ->field('g.productid,s.price,s.nums,s.status,s.orginprice')
            ->join('__SPEC__ as s ON g.id = s.goodsid')
            ->where(['s.isdelete'=>0])
            ->select();
        $tempList = [];
        foreach ($pricelist as $key => $row) {
            $productid = $row['productid'];
            $price = $row['price'];
            $orginprice = $row['orginprice'];
            if (!isset($tempList[$productid]['minorginprice'])) {
                $tempList[$productid]['minorginprice'] = $orginprice;
                $tempList[$productid]['orginunqiue'] = 1;
            } else {
                if ($orginprice >0) {
                    if ($tempList[$productid]['minorginprice'] != $orginprice && $tempList[$productid]['minorginprice'] >0) {
                        $tempList[$productid]['orginunqiue'] = 0;
                    }
                    if ($tempList[$productid]['minorginprice'] == 0 || $tempList[$productid]['minorginprice'] > $orginprice) {
                        $tempList[$productid]['minorginprice'] = $orginprice;
                    }
                }
            }
            if (!isset($tempList[$productid]['minprice']) ) {
                $tempList[$productid]['minprice'] = $price;
                $tempList[$productid]['unqiue'] = 1;
            } else {
                if ($tempList[$productid]['minprice'] != $price) {
                    $tempList[$productid]['unqiue'] = 0;
                }
                if ($tempList[$productid]['minprice'] > $price) {
                    $tempList[$productid]['minprice'] = $price;
                }
            }

            if ($row['nums'] > 0 && $row['status'] == 1) {
                if (!isset($tempList[$productid]['orginprice'])) {
                    $tempList[$productid]['orginprice'] = $orginprice;
                    $tempList[$productid]['isorginunqiue'] = 1;
                } else {
                    if ($orginprice >0) {
                        if ($tempList[$productid]['orginprice'] != $orginprice && $tempList[$productid]['orginprice'] >0) {
                            $tempList[$productid]['isorginunqiue'] = 0;
                        }
                        if ($tempList[$productid]['orginprice'] == 0 ||$tempList[$productid]['orginprice'] > $orginprice) {

                            $tempList[$productid]['orginprice'] = $orginprice;
                        }
                    }
                }
                if (!isset($tempList[$productid]['price'])) {
                    $tempList[$productid]['price'] = $price;
                    $tempList[$productid]['ispriceunqiue'] = 1;
                } else {
                    if ($price != $tempList[$productid]['price']) {
                        $tempList[$productid]['ispriceunqiue'] = 0;
                    }
                    if ($price < $tempList[$productid]['price']) {
                        $tempList[$productid]['price'] = $price;
                    }
                }

            }

        };
        foreach ($productList as $key=> $row) {
            $id = $row['id'];
            if (isset($tempList[$id]['price'])) {
                $productList[$key]['price'] = $tempList[$id]['price'];
                $productList[$key]['ispriceunqiue'] = $tempList[$id]['ispriceunqiue'];
            } else {
                $productList[$key]['price'] = $tempList[$id]['minprice'];
                $productList[$key]['ispriceunqiue'] = $tempList[$id]['unqiue'];
            }
            if (isset($tempList[$id]['orginprice'])) {
                $productList[$key]['orginprice'] = $tempList[$id]['orginprice'];
                $productList[$key]['isorginunqiue'] = $tempList[$id]['isorginunqiue'];
            } else {
                $productList[$key]['orginprice'] = $tempList[$id]['minorginprice'];
                $productList[$key]['isorginunqiue'] = $tempList[$id]['orginunqiue'];
            }
        }
        return $productList;
    }


    /**
     * @brief 微信端服务类详情
     * @param $productid
     * @return mixed
     */
    public function getOneServiceProductInfo($productid)
    {
        /*  $productModel = M('product as p');
          $where['p.id'] = $productid;
          $where['p.type'] = 1;*/
        $productModel = M('product');
        $where['id'] = $productid;
        $where['type'] = 1;
        $productInfo = $productModel
            ->field('id,status,productinfo,notes,productpic,name,serviceinfo,categoryname,servicecity,facepic,type,ptype,guanjiaid')  //v1
            ->where($where)
            ->limit(1)
            ->find();
        unset($where);
        $productInfo['serviceinfo'] = explode('+-',$productInfo['serviceinfo']);
        if ($productInfo['servicecity']) {
            $servicecity = [];
            $productInfo['servicecity'] = explode('|',$productInfo['servicecity']);
            foreach ( $productInfo['servicecity'] as $row) {
                $temp = explode(',',$row);
                $servicecity[]= $temp[1];
            }
            if (in_array('全国', $servicecity)) {
                $productInfo['servicecity'] = ['全国'];
            } else {
                $productInfo['servicecity'] = $servicecity;
            }
        }
        $where['g.productid'] = $productid;
        $goodModel = M('goods as g');
        $specList = $goodModel
            ->join('__SPEC__ as s ON g.id = s.goodsid')
            ->field('s.id,s.price,s.status,s.nums,s.orginprice')
            ->where(['g.productid'=>$productid,'s.isdelete'=>0])
            ->select();
        $tempList = [];
        foreach ($specList as $row) {
            $price = $row['price'];
            $nums = $row['nums'];
            $status = $row['status'];
            $orginprice = $row['orginprice'];
            if (!isset($tempList['minprice'])) {
                $tempList['minprice'] = $price;
                $tempList['unquie'] = 1;
            } else {
                if ($tempList['minprice'] != $price) {
                    $tempList['unquie'] = 0;
                }
                if ($tempList['minprice'] > $price) {
                    $tempList['minprice'] = $price;
                }
            }
            if (isset($tempList['minorginprice'])) {
                $tempList['minorginprice'] = $orginprice;
                $tempList['orginunquie'] = 1;
            } else {
                if ($orginprice >0) {
                    if ($tempList['minorginprice'] != $orginprice && $tempList['minorginprice'] >0) {
                        $tempList['orginunqiue'] = 0;
                    }
                    if ($tempList['minorginprice'] == 0 || $tempList['minorginprice'] > $orginprice) {
                        $tempList['minorginprice'] = $orginprice;
                    }
                }
            }


            if ($nums >0 && $status ==1) {
                if (!isset($tempList['price'])) {
                    $tempList['price'] = $price;
                    $tempList['ispriceunqiue'] = 1;
                } else {
                    if ($tempList['price'] != $price) {
                        $tempList['ispriceunqiue'] = 0;
                    }
                    if ($tempList['price'] > $price) {
                        $tempList['price'] = $price;
                    }
                }

                if (!isset($tempList['orginprice'])) {
                    $tempList['orginprice'] = $orginprice;
                    $tempList['isorginunqiue'] = 1;
                } else {
                    if ($orginprice >0) {
                        if ($tempList['orginprice'] != $orginprice && $tempList['orginprice'] >0) {
                            $tempList['isorginunqiue'] = 0;
                        }
                        if ($tempList['orginprice'] == 0 ||$tempList['orginprice'] > $orginprice) {

                            $tempList['orginprice'] = $orginprice;
                        }
                    }
                }
            }
        }
        if (isset($tempList['price'])) {
            $productInfo['price'] = $tempList['price'];
            $productInfo['ispriceunqiue'] = $tempList['ispriceunqiue'];
        } else {
            $productInfo['price'] = $tempList['minprice'];
            $productInfo['ispriceunqiue'] = $tempList['unquie'];
        }
        if (isset($tempList['orginprice'])) {
            $productInfo['orginprice'] = $tempList['orginprice'];
            $productInfo['isorginunqiue'] = $tempList['isorginunqiue'];
        } else {
            $productInfo['orginprice'] = $tempList['minorginprice'];
            $productInfo['isorginunqiue'] = $tempList['orginunqiue'];
        }
        return $productInfo;
    }

    /**
     * @breif 微信端商品类详情（即快递类）
     * @param $productid
     * @return mixed
     */
    public function getOneExpressProductInfo($productid)
    {
        $where['id'] = $productid;
        $where['type'] = 2;
        $productModel = M('product');
        $res = $productModel->field('id,status,isshipping,productinfo,notes,productpic,name,serviceinfo')->where($where)->limit(1)->find();
        $res['serviceinfo'] = explode('+-',$res['serviceinfo']);  //v1
        $specModel = M('spec');
        unset($where);
        $where['productid'] = $productid;
        $where['isdelete'] = 0;
        $specList = $specModel->field('id,price,nums,status,orginprice')->where($where)->select();
        $tempList = [];
        foreach ($specList as $row) {
            $price = $row['price'];
            $nums = $row['nums'];
            $status = $row['status'];
            $orginprice = $row['orginprice'];
            if (!isset($tempList['minprice'])) {
                $tempList['minprice'] = $price;
                $tempList['unquie'] = 1;
            } else {
                if ($tempList['minprice'] != $price) {
                    $tempList['unquie'] = 0;
                }
                if ($tempList['minprice'] > $price) {
                    $tempList['minprice'] = $price;
                }
            }

            if (isset($tempList['minorginprice'])) {
                $tempList['minorginprice'] = $orginprice;
                $tempList['orginunquie'] = 1;
            } else {
                if ($orginprice >0) {
                    if ($tempList['minorginprice'] != $orginprice && $tempList['minorginprice'] >0) {
                        $tempList['orginunqiue'] = 0;
                    }
                    if ($tempList['minorginprice'] == 0 || $tempList['minorginprice'] > $orginprice) {
                        $tempList['minorginprice'] = $orginprice;
                    }
                }
            }
            if ($nums >0 && $status ==1) {
                if (!isset($tempList['orginprice'])) {
                    $tempList['orginprice'] = $orginprice;
                    $tempList['isorginunqiue'] = 1;
                } else {
                    if ($orginprice >0) {
                        if ($tempList['orginprice'] != $orginprice && $tempList['orginprice'] >0) {
                            $tempList['isorginunqiue'] = 0;
                        }
                        if ($tempList['orginprice'] == 0 ||$tempList['orginprice'] > $orginprice) {

                            $tempList['orginprice'] = $orginprice;
                        }
                    }
                }
                if (!isset($tempList['price'])) {
                    $tempList['price'] = $price;
                    $tempList['ispriceunqiue'] = 1;
                } else {
                    if ($tempList['price'] != $price) {
                        $tempList['ispriceunqiue'] = 0;
                    }
                    if ($tempList['price'] > $price) {
                        $tempList['price'] = $price;
                    }
                }
            }
        }
        if (isset($tempList['price'])) {
            $res['price'] = $tempList['price'];
            $res['ispriceunqiue'] = $tempList['ispriceunqiue'];
        } else {
            $res['price'] = $tempList['minprice'];
            $res['ispriceunqiue'] = $tempList['unquie'];
        }
        if (isset($tempList['orginprice'])) {
            $res['orginprice'] = $tempList['orginprice'];
            $res['isorginunqiue'] = $tempList['isorginunqiue'];
        } else {
            $res['orginprice'] = $tempList['minorginprice'];
            $res['isorginunqiue'] = $tempList['orginunqiue'];
        }
        return $res;
    }

    /**
     * @breif获取服务类产品下所有商品商品
     * @param $productid
     */
    public function getAllServiceGoodsByProductid($productid,$selectgoods,$selectspec)
    {
        $goodModle=M('goods');

        $specModel=M("spec");

        $whereG=array();

        $whereG["productid"]=$productid;

        $whereG["status"]=1;

        if ($selectgoods) {
            $whereG['id'] = ['in',$selectgoods];
        }

        $resG=$goodModle->field('id,info,paystyle,name,spec')->where($whereG)->select();
     
        $countG=count($resG);

        $data = [];

        for($i=0;$i<$countG;$i++)
        {

            $whereS=array();

            $whereS["status"]=1;

            $whereS["isdelete"]=0;

            $whereS["goodsid"]=$resG[$i]["id"];

            if ($selectspec) {
                $whereS['id'] = ['in',$selectspec];
            }

            $resS=$specModel->where($whereS)->select();

            if (!count($resS)) {
                continue;
            }

            /*发现最小值和有没有库存*/

            $countS=count($resS);

            $specminprice=0;

            $spectotalprice=0;

            $restnum=0;

            for($j=0;$j<$countS;$j++)
            {
                $price=$resS[$j]["price"];
                $spectotalprice=$spectotalprice+$price;

                if($j==0)
                {
                    $specminprice=$price;
                    if($resS[$j]["minnum"]==1)
                    {
                        if($resS[$j]["nums"]>=1)
                        {
                            $restnum=1;
                        }
                    }
                    else
                    {

                        if($resS[$j]["minnum"]>$resS[$j]["nums"])
                        {

                        }
                        else
                        {
                            $restnum=1;
                        }
                    }
                }
                else
                {
                    if($specminprice>$price)
                    {
                        $specminprice=$price;
                    }
                    /*找库存*/
                    if($resS[$j]["minnum"]==1)
                    {
                        if($resS[$j]["nums"]>=1)
                        {
                            $restnum=1;
                        }
                    }
                    else
                    {

                        if($resS[$j]["minnum"]>$resS[$j]["nums"])
                        {

                        }
                        else
                        {
                            $restnum=1;
                        }
                    }

                }
            }

            $data[] =[
                'id' =>$resG[$i]['id'],
                'info' =>$resG[$i]['info'],
                'paystyle' =>$resG[$i]['paystyle'],
                'spec'=>$resG[$i]['spec'],
                'name' =>$resG[$i]['name'],
                'specminprice'=>$specminprice,
                'spectotalprice' =>$spectotalprice,
                'spectotalnum' =>$countS,
                'restnum'=>$restnum
            ];

        }
        return $this->orderByPhara($data);
    }

    /**
     *
     *楼上排序 restnum desc,specminprice asc,id asc
     */

    private function orderByPhara($resG)
    {

        return $this->sortArrayMultiFields($resG, ['restnum' => SORT_DESC, 'specminprice' => SORT_ASC, 'id' => SORT_ASC]);

    }

    public function sortArrayMultiFields(&$data, $condition)
    {
        if (count($data) <= 0 || empty($condition)) {
            return $data;
        }
        $fieldsCount = count($condition);
        $fileds = array_keys($condition);
        $types = array_values($condition);
        switch ($fieldsCount) {
            case 1:
                $data = $this->sort1Field($data, $fileds[0], $types[0]);
                break;
            case 2:
                $data = $this->sort2Fields($data, $fileds[0], $types[0], $fileds[1], $types[1]);
                break;
            default:
                $data = $this->sort3Fields($data, $fileds[0], $types[0], $fileds[1], $types[1], $fileds[2], $types[2]);
                break;
        }
        return $data;
    }

    public function sort1Field(&$data, $filed, $type)
    {
        if (count($data) <= 0) {
            return $data;
        }
        foreach ($data as $key => $value) {
            $temp[$key] = $value[$filed];
        }
        array_multisort($temp, $type, $data);
        return $data;
    }

    public function sort2Fields(&$data, $filed1, $type1, $filed2, $type2)
    {
        if (count($data) <= 0) {
            return $data;
        }
        foreach ($data as $key => $value) {
            $sort_filed1[$key] = $value[$filed1];
            $sort_filed2[$key] = $value[$filed2];
        }
        array_multisort($sort_filed1, $type1, $sort_filed2, $type2, $data);
        return $data;
    }

    public function sort3Fields(&$data, $filed1, $type1, $filed2, $type2, $filed3, $type3)
    {
        if (count($data) <= 0) {
            return $data;
        }
        foreach ($data as $key => $value) {
            $sort_filed1[$key] = $value[$filed1];
            $sort_filed2[$key] = $value[$filed2];
            $sort_filed3[$key] = $value[$filed3];
        }
        array_multisort($sort_filed1, $type1, $sort_filed2, $type2, $sort_filed3, $type3, $data);
        return $data;
    }

    /**
     * @breif获取快递类商品下所有商品
     * @param $productid
     *
     */
    public function getAllExpressGoodsByProductid($productid)
    {
        $where['productid'] = $productid;
        $where['status'] = 1;
        $goodsModel = M('spec');
        $res = $goodsModel
            ->field('id as specid,specname as name,goodsid,price,nums as restnum,pic,limitype aslimittype,limitnum,if(nums>0,1,0) as ordertype')
            ->where($where)
            ->order('ordertype desc,price asc')
            ->select();
        return $res;
    }

    /**
     * @breif 产品是否下线
     * @param $productid
     * @return bool
     */
    public function isProductOffline($productid)
    {
        $productModel = M("product");
        $where['id'] = $productid;
        $res = $productModel->where($where)->getField('status');
        if ($res == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function productOffline($productid)
    {
        $productModel = M("product");
        $where['id'] = $productid;
        $saveData['status'] = 2;
        $res = $productModel->where($where)->save($saveData);
        return $res;
    }

    public function checkExpressProduct($productid)
    {
        $where['id'] = $productid;
        $productModel = M('product');
        $status = $productModel->where($where)->getField('status');
        if ($status == 1) {
            $goddsModel = M('goods as g');
            $res = $goddsModel
                ->join('__SPEC__ as s ON g.id=s.goodsid')
                ->field('s.id,s.status,s.nums,s.price')
                ->where(['g.productid'=>$productid])
                ->select();
            $nums = 0;
            $nowstatus = 0;
            $canbuy = false;
            foreach ($res as $row) {
                $nums+=$row['nums'];
                $nowstatus += $row['status'];
                if ($row['status'] == 1 && $row['nums'] > 0) {
                    $canbuy = true;
                    break;
                }
            }
            if (!$canbuy && $nums == 0) {
                $status = 3;                                          //已售罄
            } elseif (!$canbuy && $nowstatus == count($res)*2) {
                $status = 4;                                           //已下线
            } elseif (!$canbuy) {
                $status = 5;                                            //有已下线，有无库存
            }
        }
        return $status;
       /* $where['p.id'] = $productid;
        $productModel = M('product as p');
        $res= $productModel
            ->join('__GOODS__ as g ON p.id = g.productid')
            ->join('__SPEC__ as s ON g.id=s.goodsid')
            ->field('p.id,p.status,count(p.id) as totalcount,sum(s.status) as sumstatus,sum(s.nums) as restnum')
            ->where($where)
            ->limit(1)
            ->find();
        return $res;*/
    }

    /**
     * @breif 检测预订服务类产品状态
     * @param $productid
     * @return mixed        1 通过 2产品已下线
     */
    public function checkServieProduct($productid,$selectspec = '')
    {

        $where['id'] = $productid;
        $productModel = M('product');
        $status = $productModel->where($where)->getField('status');
        $where = [];

        if ($status == 1) {
            $goddsModel = M('goods as g');
            $where['g.productid'] = $productid;
            $where['s.isdelete'] = 0;
            if ($selectspec) {
                $where['s.id'] = ['in',$selectspec];
            }
            $res = $goddsModel
                ->join('__SPEC__ as s ON g.id=s.goodsid')
                ->field('s.id,s.status,s.nums,s.price,s.minnum')
                ->where(['g.productid'=>$productid,'s.isdelete'=>0])
                ->select();
            $nums = 0;
            $nowstatus = 0;
            $canbuy = false;
            foreach ($res as $row)
            {
                /**/
                $nums+=$row['nums'];
                $nowstatus += $row['status'];
                if($row["minnum"]==1)
                {
                    if ($row['status'] == 1 && $row['nums'] > 0) {
                        $canbuy = true;
                        break;
                    }
                }
                else
                {
                    if($row["minnum"]>$row["nums"])
                    {

                    }
                    else
                    {
                        if ($row['status'] == 1 && $row['nums'] > 0) {
                            $canbuy = true;
                            break;
                        }
                    }
                }

            }
            if (!$canbuy && $nums == 0) {
                $status = 3;                                          //已售罄
            } elseif (!$canbuy && $nowstatus == count($res)*2) {
                $status = 4;                                           //已下线
            } elseif (!$canbuy) {
                $status = 5;                                            //有已下线，有无库存
            }
        }
        return $status;
        /*$where['p.id'] = $productid;
        $productModel = M('product as p');
        $res= $productModel
            ->join('__GOODS__ as g ON p.id = g.productid')
            ->join('__SPEC__ as s ON g.id = s.goodsid')
            ->field('p.id,p.status,count(p.id) as totalcount,sum(s.status) as sumstatus,sum(s.nums) as restnum')
            ->where($where)
            ->limit(1)
            ->find();*/
        /*  return $res;*/
    }

    /**
     * @breif 检测预订服务类商品时商品规格状态
     * @param $productid
     * @param $goodsid
     * @return mixed
     */
    public function checkServiceGoodsStatus($goodsid,$specid)
    {
        $specModel = M('spec');
        $goodsModel = M('goods');
        if (!$goodsid) return false;
        if (!$specid) return false;
        $where['id'] = $goodsid;
        $goodstatus = $goodsModel->where($where)->getField('status');
        $whereS = array();

        $whereS["status"] = 1;

        $whereS["isdelete"] = 0;

        $whereS["goodsid"] = $goodsid;

        $whereS['id'] = $specid;

        $resS = $specModel->field('status,nums,minnum,limitype,limitnum')->where($whereS)->limit(1)->find();
        $resS['goodstatus'] = $goodstatus;
        return $resS;

    }

    /**
     * @breif 获取服务商品基本信息
     * @param $goodsid
     * @return mixed
     */
    public function getServiceBaseGoods($goodsid)
    {
        $where['g.id'] = $goodsid;
        $goodModel = M('goods as g');
        return $goodModel
            ->join('__PRODUCT__ as p ON g.productid = p.id')
            ->field('g.id,p.facepic,g.name,g.spec,g.paystyle,g.type,g.isselecttime,g.caltype,g.advancetime,g.booktime,g.noservicetime,g.isselectstaff,g.staffgroup')
            ->where($where)
            ->limit(1)
            ->find();
    }

    public function getExpressBaseGoods($productid, $goodsid,$specid)
    {
        $where['p.id'] = $productid;
        $where['g.id'] = $goodsid;
        $where['s.id'] = $specid;
        $productModel = M('product as p');
        return $productModel
            ->join('__GOODS__ as g ON p.id = g.productid')
            ->join('__SPEC__ as s ON g.id=s.goodsid')
            ->field('p.id as productid,s.nums,s.limitype as limittype,s.limitnum,s.id as goodsid,s.specname as goodsname,s.pic,s.price,p.name as productname,p.isshipping')
            ->where($where)
            ->limit(1)
            ->find();
    }

    /**
     * @breif 获取所有规格
     * @param $goodid
     * @return mixed
     */
    public function getAllSpec($goodsid,$selectspec)
    {
        $where['goodsid'] = $goodsid;
        $where['status'] = 1;
        $where['isdelete'] = 0;
        if ($selectspec) {
            $where['id'] = ['in',$selectspec];
        }
        $specModel = M('spec');
        $res = $specModel->field('id,specname,price,limitype,limitnum,nums,minnum,tips,swimg')->where($where)->select();
        foreach ($res as $key=> $row) {
            if ($row['swimg']) $res[$key]['swimg'] =getshowImgUrl($row['swimg']);
        }
        return $res;

    }

    public function getOneSpec($specid)
    {
        $where['id'] = $specid;
        $where['status'] = 1;
        $where['isdelete'] = 0;
        $specModel = M('spec');
        $res = $specModel->field('id,specname,price,limitype,limitnum,nums,minnum,swimg,kpl_sku,remark')->where($where)->limit(1)->find();
        return $res;
    }

    /**
     * @breif 用户提交订单验证信息
     * @param $goodsid
     * @return mixed
     */
    public function getSubmitProductInfo($productid, $gooodsid, $specid, $type)
    {
        $productModel = M('product as p');
        if ($type == 1) {
            $where['p.id'] = $productid;
            $where['g.id'] = $gooodsid;
            $where['s.id'] = $specid;
            $where['s.isdelete'] = 0;
            $res = $productModel->join('__GOODS__ as g ON p.id = g.productid')
                ->join('__SPEC__ as s ON g.id = s.goodsid')
                ->join('__SUPPLIER__ as sp ON p.supplierid = sp.id','left')
                ->field('sp.userid as bdid,p.guanjiaid,p.type,p.id as productid,g.id as goodid,s.id as specid,p.name as productname,p.categoryname as producttype,p.categoryid,p.facepic as productpic,g.name as goodname,g.pic as goodpic,s.specname,g.paystyle,s.price,s.nums,p.status as productstatus,g.status as goodsstatus,g.type as servicetype,s.status as specstatus,s.limitype as limittype,s.limitnum,p.supplierid,s.kpl_sku,s.settletype,s.settlevalue')
                ->where($where)
                ->limit(1)
                ->find();
        } elseif ($type == 2) {
            $where['p.id'] = $productid;
            $where['s.id'] = $specid;
            $where['s.isdelete'] = 0;
            $res = $productModel
                ->join('__GOODS__ as g ON p.id = g.productid')
                ->join('__SPEC__ as s ON g.id = s.goodsid')
                ->field('p.userid as bdid,p.guanjiaid,p.type,p.id as productid,s.id as specid,s.goodsid as goodid,p.name as productname,p.categoryname as producttype,p.categoryid,p.facepic as productpic,s.specname as goodname,s.pic as goodpic,s.price,s.nums,p.status as productstatus,s.status as goodsstatus,s.limitype as limittype,s.limitnum,p.supplierid')
                ->where($where)
                ->limit(1)
                ->find();

        } else {
            return false;
        }
        return $res;

    }

    /**
     * @breif 获取该规格信息（包括 产品状态、商品状态）
     * @param $specid
     * @return array
     */
    public function getSpecInfo($specid)
    {
        $specModel  = M('spec as s');
        $where['s.id'] = $specid;
        $res = $specModel
                ->field('s.*,p.status as productstatus,p.name as productname,p.guanjiaid,p.userid as bdid,p.id as productid,g.id as goodsid,g.type,g.paystyle,g.spec,g.status as goodsstatus,g.name as goodname')
                ->join('__GOODS__ as g ON s.goodsid = g.id')
                ->join('__PRODUCT__ as p ON g.productid = p.id')
                ->where($where)
                ->limit(1)
                ->find();

        return $res;
    }


    public function saveSpec($specid, $param)
    {
        $where['id'] = $specid;
        $specModel = M('spec');
        return $specModel->where($where)->save($param);
    }

    public function delSpecNum($id, $delnum)
    {
            $where['id'] = $id;
            $where['nums'] = ['egt',$delnum];
            $specModel = M('spec');
            return $specModel->where($where)->setDec('nums', $delnum);
    }

    public function getCateoryIdByCateoryName($categoryname = [], $type = 1)
    {
        if ((!(is_array($categoryname) && count($categoryname) >0))) return false;
        $model = M('category');
        $where['name'] = ['in', $categoryname];
        $where['type'] = $type;
        $where['status'] = 1;
        $res = $model->field('id,name,level')->where($where)->select();
        return $res;

    }

    public function getRecommendProdct($where)
    {
        $model = M('product as p');
        $product = $model
            ->join('__GOODS__ as g ON p.id = g.productid')
            ->join('__SPEC__ as s ON s.goodsid = g.id')
            ->field('distinct(p.id),p.categoryname')
            ->where($where)->select();
        return $product;
    }
    public function getGuanJiaIdByProductid($productid)
    {
        $model = M('product');
        $where['id'] = $productid;
        $guanjiaid = $model->where($where)->getField('guanjiaid');
        return $guanjiaid;
    }

    public function getProductBooking($productid)
    {
        $model = M('product');
        $where['id'] = $productid;
        return $model->where($where)->getField('bookingtype');
    }

    public function getProductName($productids)
    {
        $model = M('product');
        $where['id'] = $productids;
        return $model->field('id,name')->where($where)->select();
    }

    public function getServiceGoods($where)
    {
        $model = M('goods');
        return $model->field('id,name,spec')->where($where)->select();
    }

    public function getSpec($where)
    {
        $model = M('spec');
        return $model->field('id,specname')->where($where)->select();
    }

    public function getCateoryProductList($leveltwoid, $address,$nowpage, $limit = 10)
    {
        if ($leveltwoid) {
            $where['p.categoryid'] = $leveltwoid;
        }
        if ($address) {
            $where['_string'] = "p.`servicecity` like '%全国%' or p.`servicecity` like '%".$address."%'";
        }
        $productModel = M('product as p');
        $where['p.status'] = 1;
        $where['s.isdelete'] = 0;
        $where['s.nums'] = ['gt',0];
        $productList = $productModel
            ->field('p.id,j.guanjianame,j.avatarurl,j.title as guanjiafenlei,p.name,p.type,p.guanjiaid,p.facepic,serviceinfo,count(p.id) as onenum')
            ->join('__GOODS__ as g ON p.id = g.productid')
            ->join('__GUANJIA__ as j ON p.guanjiaid = j.id')
            ->join('__SPEC__ as s ON g.id = s.goodsid')
            ->where($where)
            ->group('p.id')
            ->having('sum(s.nums)>0')
            ->order('p.sortweight desc,p.id desc')
            ->select();

        $productids = array_column($productList,'id');
        if (!count($productids)) {
            return [];
        }
        $goodsModel = M('goods as g');
        unset($where1);
        $pricelist = $goodsModel
            ->field('g.productid,s.price,s.nums,s.status,s.orginprice')
            ->join('__SPEC__ as s ON g.id = s.goodsid')
            ->where(['s.isdelete'=>0])
            ->select();
        $tempList = [];
        foreach ($pricelist as $key => $row) {
            $productid = $row['productid'];
            $price = $row['price'];
            $orginprice = $row['orginprice'];
            if (!isset($tempList[$productid]['minorginprice'])) {
                $tempList[$productid]['minorginprice'] = $orginprice;
                $tempList[$productid]['orginunqiue'] = 1;
            } else {
                if ($orginprice >0) {
                    if ($tempList[$productid]['minorginprice'] != $orginprice && $tempList[$productid]['minorginprice'] >0) {
                        $tempList[$productid]['orginunqiue'] = 0;
                    }
                    if ($tempList[$productid]['minorginprice'] == 0 || $tempList[$productid]['minorginprice'] > $orginprice) {
                        $tempList[$productid]['minorginprice'] = $orginprice;
                    }
                }
            }
            if (!isset($tempList[$productid]['minprice']) ) {
                $tempList[$productid]['minprice'] = $price;
                $tempList[$productid]['unqiue'] = 1;
            } else {
                if ($tempList[$productid]['minprice'] != $price) {
                    $tempList[$productid]['unqiue'] = 0;
                }
                if ($tempList[$productid]['minprice'] > $price) {
                    $tempList[$productid]['minprice'] = $price;
                }
            }

            if ($row['nums'] > 0 && $row['status'] == 1) {
                if (!isset($tempList[$productid]['orginprice'])) {
                    $tempList[$productid]['orginprice'] = $orginprice;
                    $tempList[$productid]['isorginunqiue'] = 1;
                } else {
                    if ($orginprice >0) {
                        if ($tempList[$productid]['orginprice'] != $orginprice && $tempList[$productid]['orginprice'] >0) {
                            $tempList[$productid]['isorginunqiue'] = 0;
                        }
                        if ($tempList[$productid]['orginprice'] == 0 ||$tempList[$productid]['orginprice'] > $orginprice) {

                            $tempList[$productid]['orginprice'] = $orginprice;
                        }
                    }
                }
                if (!isset($tempList[$productid]['price'])) {
                    $tempList[$productid]['price'] = $price;
                    $tempList[$productid]['ispriceunqiue'] = 1;
                } else {
                    if ($price != $tempList[$productid]['price']) {
                        $tempList[$productid]['ispriceunqiue'] = 0;
                    }
                    if ($price < $tempList[$productid]['price']) {
                        $tempList[$productid]['price'] = $price;
                    }
                }

            }

        };
        foreach ($productList as $key=> $row) {
            $id = $row['id'];
            if (isset($tempList[$id]['price'])) {
                $productList[$key]['price'] = $tempList[$id]['price'];
                $productList[$key]['ispriceunqiue'] = $tempList[$id]['ispriceunqiue'];
            } else {
                $productList[$key]['price'] = $tempList[$id]['minprice'];
                $productList[$key]['ispriceunqiue'] = $tempList[$id]['unqiue'];
            }
            if (isset($tempList[$id]['orginprice'])) {
                $productList[$key]['orginprice'] = $tempList[$id]['orginprice'];
                $productList[$key]['isorginunqiue'] = $tempList[$id]['isorginunqiue'];
            } else {
                $productList[$key]['orginprice'] = $tempList[$id]['minorginprice'];
                $productList[$key]['isorginunqiue'] = $tempList[$id]['orginunqiue'];
            }
        }
        return $productList;
    }

    public function getOneProductInfo($id)
    {
        $where['id'] = $id;
        return M('product')->where($where)->limit(1)->find();
    }
}


