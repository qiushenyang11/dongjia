<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/8/1
 * Time: 13:23
 */

namespace Server;


use WeChat\Model\GoodsModel;

class Card
{
    /**
     * @breif 验证礼品卡是否可用
     * @param $id
     * @param $jdaccount
     * @param $useprice
     * @param $levelone
     * @param $leveltwo
     * @param $guanjiaid
     * @param $productid
     * @param $supplierid
     * @return bool
     */
    public function verifyCard($id, $jdaccount, $useprice, $levelone, $leveltwo, $guanjiaid, $productid, $supplierid)
    {
        if ($useprice <=0) return false;
        $where['id'] = $id;
        $where['jdaccount'] = $jdaccount;
        $cardinfo = M('card')->where($where)->limit(1)->find();
        if (!$cardinfo) return false;
        $endtime = $cardinfo['endtime'];            //需要核对
        if (time()>$endtime) return false;              //过期
        $nowprice = $cardinfo['nowprice'];

        if ($nowprice < $useprice) return false;
        $usetype = $cardinfo['usetype'];
        $isall = $cardinfo['isall'];
        $levelones = $cardinfo['levelones'];
        if ($levelones) {
            $levelones = substr($levelones,1,strlen($levelones)-2);
            $levelones = explode('-', $levelones);
        }
        $leveltwos = $cardinfo['leveltwos'];
        if ($leveltwos) {
            $leveltwos = substr($leveltwos, 1, strlen($leveltwos)-2);
            $leveltwos = explode('-',$leveltwos);
        }
        $guanjiaids = $cardinfo['guanjiaids'];
        if ($guanjiaids) {
            $guanjiaids = substr($guanjiaids, 1, strlen($guanjiaids)-2);
            $guanjiaids = explode('-', $guanjiaids);
        }
        $productids = $cardinfo['productids'];
        if ($productids) {
            $productids = substr($productids, 1, strlen($productids)-2);
            $productids = explode('-', $productids);
        }
        $supplierids = $cardinfo['supplierids'];
        if ($supplierids) {
            $supplierids = substr($supplierids, 1, strlen($supplierids)-2);
            $supplierids = explode('-', $supplierids);
        }

        if ($usetype  == 1) {                       //白名单
            if ($isall == 1) return true;
            if ($levelones) {
                if (in_array($levelone, $levelones)) return true;
            }
            if ($leveltwos) {
                if (in_array($leveltwo, $leveltwos)) return true;
            }
            if ($guanjiaids) {
                if (in_array($guanjiaid, $guanjiaids)) return true;
            }
            if ($productids) {
                if (in_array($productid, $productids)) return true;
            }
            if ($supplierids) {
                if (in_array($supplierid, $supplierids)) return true;
            }
            return false;
        } elseif ($usetype == 2) {
            if (!($levelones || $leveltwos || $guanjiaids || $productids || $supplierids)) return true;    //黑名单条件为空
            if ($levelones) {
                if (in_array($levelone, $levelones)) return false;
            }
            if ($leveltwos) {
                if (in_array($leveltwo, $leveltwos)) return false;
            }
            if ($guanjiaids) {
                if (in_array($guanjiaid, $guanjiaids)) return false;
            }
            if ($productids) {
                if (in_array($productid, $productids)) return false;
            }
            if ($supplierids) {
                if (in_array($supplierid, $supplierids)) return false;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function lockCard($jdAccount,$cardname, $id, $cardprice,$ordersn)
    {
        $where['id'] = $id;
        $where['jdaccount'] = $jdAccount;
        $cardinfo = M('card')->where($where)->limit(1)->find();
        $data['nowprice'] = $cardinfo['nowprice'] - $cardprice;
        if (!$data['nowprice']) $data['isuseall'] = 1;
        $res1 = M('card')->where($where)->save($data);
        $res2 = $this->addOneCardHistory($jdAccount, $cardname, $id,$cardprice,$ordersn,0);
        if ($res1 && $res2) return true;
        return false;
    }

    public function backCard($type = 1,$jdAccount,$cardname, $id, $cardprice,$ordersn)
    {
        $where['id'] = $id;
        $where['jdaccount'] = $jdAccount;
        $cardinfo = M('card')->where($where)->limit(1)->find();
        $data['nowprice'] = $cardinfo['nowprice'] + $cardprice;
        $data['isuseall'] = 0;
        if ($type == 1) {           //未支付解除锁定
            $res1 = M('card')->where($where)->save($data);
            $res2 = $this->addOneCardHistory($jdAccount, $cardname, $id,$cardprice,$ordersn,-1);
            if ($res1 && $res2) return true;
        } elseif ($type == 2) {         //退款
            $res1 = M('card')->where($where)->save($data);
            $res2 = $this->addOneCardHistory($jdAccount, $cardname, $id,$cardprice,$ordersn,3);
            if ($res1 && $res2) return true;
        }
        return false;
    }

    public function addOneCardHistory($jdAccount, $cardname,$cardid,$price,$ordersn,$type)
    {
        $CardHistory = M('cardlog');

        $data["jdaccount"]=$jdAccount;

        $data["cardname"]=$cardname;

        $data["cardid"]=$cardid;

        $data["price"]=$price;

        $data["ordersn"]=$ordersn;

        $data["type"]=$type;

        $data['addtime'] = time();

        $rst=$CardHistory->add($data);

        if($rst)
        {
            return true;//response("Success Add One History",1,$data);
        }
        else
        {
            return false;//response("Failure Add One History");
        }
    }

    //format [{"id":0,"productid":23,"selects":["1-2","2-3"],"nums":10,"title":"12234"}]
    public function verifyExchangeProduct($str)
    {
        if (!$str) response('参数错误');
        $str = json_decode($str,true);
        foreach ($str  as $key=> $row)
        {
            if (!isset($row['productid']) || !isset($row['selects']) || !isset($row['nums']) || !isset($row['title'])) response('兑换产品参数异常');
            if (!$row['productid']||!$row['selects'] ||!$row['nums'] || !$row['title']) response('兑换产品参数异常');
            if (!is_array($row['selects'])) response('参数错误');
            $str[$key]['restnum'] =$row['nums'];
        }
        return json_encode($str);

    }

    public function getExchangeProductInfo($cardid,$jdaccount)
    {
        $where['id'] = $cardid;
        $where['jdaccount'] = $jdaccount;
        $res = M('card')->where($where)->limit(1)->find();
        if (!$res) response('礼品卡异常');
        $type = $res['type'];
        $parent = $res['parent'];
        if ($type == 1) {           //现金卡(礼包应该不会出现)
            $cardid=I("post.cardid");

            $CardHistory=M("cardlog as c");

            $where=array();

            $where["c.cardid"]=$cardid;

            $where["c.jdaccount"]=$jdaccount;

            $where['c.type'] = ['in',[1,2,3]];

            $rst=$CardHistory
                ->field('c.*,og.productname')
                ->join('__ORDER_INFO__ as o ON c.ordersn = o.ordersn','left')
                ->join('__ORDER_GOOD__ as og ON o.id = og.orderid','left')
                ->where($where)
                ->order('c.id asc')
                ->select();
        } else {                    //兑换卡
            $exchangelist = $res['exchangelist'];
            $exchangelist = json_decode($exchangelist, true);
            $rst = [];
            $goodsModel = new GoodsModel();
            foreach ($exchangelist as $one) {
                $productid = $one['productid'];
                $nums = $one['nums'];
                $title = $one['title'];
                $restnum = $one['restnum'];
                $pinfo = $goodsModel->getOneProductInfo($productid);
                $rst[] =[
                    'id'=>$one['id'],
                    'productid'=>$productid,
                    'restnum'=>$restnum,
                    'title'=>$title,
                    'productname'=>$pinfo['name'],
                    'facepic'=>getshowImgUrl($pinfo['facepic'])
                ];
            }
        }
        return $rst;
    }

    public function getExchangeCanUseGoodAndName($cardid,$cardtag)
    {
        $where['id'] = $cardid;
        $res = M('card')->where($where)->limit(1)->find();
        if ($res['type'] !=2) response('无兑换卡可使用');
        $exchangelist = $res['exchangelist'];
        $exchangelist = json_decode($exchangelist, true);
        $selectspec = [];
        $selectgoods = [];
        $restnum = 0;
        foreach ($exchangelist as $row) {
            if ($row['id'] != $cardtag) continue;
            if ($row['id'] == $cardtag) {
                $selects = $row['selects'];
                foreach ($selects as $row1) {
                    $temp = explode('-',$row1);
                    $selectgoods[] = $temp[0];
                    $selectspec[] = $temp[1];
                }
                $restnum = $row['restnum'];
                break;
            }
        }
        $selectgoods = array_unique($selectgoods);
        return ['restnum'=>$restnum,'selectspec'=>$selectspec,'selectgoods'=>$selectgoods];
    }

    public function verifyExchange($cardid,$cardtag,$productid,$goodsid,$specid,$num)
    {
        $where['id'] =$cardid;
        $cardinfo = M('card')->where($where)->limit(1)->find();
        $exchangelist = $cardinfo['exchangelist'];
        $exchangelist = json_decode($exchangelist, true);

        $selected = $goodsid.'-'.$specid;
        foreach ($exchangelist as $key => $row) {
            $selects = $row['selects'];
            $restnum = $row['restnum'];
            if ($row['id'] == $cardtag) {
                if ($row['productid'] != $productid) {
                    response('您的兑换卡不能兑换该产品');
                }
                if (!in_array($selected,$selects)) {
                    response('您的兑换卡不能兑换该产品');
                }
                if ($restnum < $num) {
                    response('超过兑换个数');
                }
                $restnum = $restnum - $num;
                $exchangelist[$key]['restnum'] = $restnum;
                return $exchangelist;
            }
        }
        response('兑换卡不能兑换该产品');
    }

    public function addExchageLog($jdaccount,$ordersn,$cardtag,$cardid,$productid,$goodid,$specid,$num,$type = 1)
    {
        $exchagelog = [];
        $exchagelog['jdaccount'] = $jdaccount;
        $exchagelog['ordersn'] = $ordersn;
        $exchagelog['cardid'] = $cardid;
        $exchagelog['productid'] = $productid;
        $exchagelog['goodid'] = $goodid;
        $exchagelog['specid'] = $specid;
        $exchagelog['num'] = $num;
        $exchagelog['type'] = $type;
        $exchagelog['addtime'] = time();
        $exchagelog['cardtag'] = $cardtag;
        return M('exchangelog')->add($exchagelog);
    }

    public function saveExchage($cardid,$exchangelist,$isuseall)
    {
        $exchangelist = json_encode($exchangelist);
        $where['id'] = $cardid;
        $savedata['exchangelist'] =$exchangelist;
        $savedata['isuseall'] = $isuseall;
        return M('card')->where($where)->save($savedata);
    }


}