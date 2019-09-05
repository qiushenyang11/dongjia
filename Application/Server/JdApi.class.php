<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/1/2
 * Time: 17:56
 */

namespace Server;


use Operation\Model\OrderModel;
use Org\Util\EasyWeChat;
use WeChat\Controller\WeChatTrackController;
use WeChat\Model\WeChatUserModel;

class JdApi
{
    const LOGINAPPID = 422;
    const APIAPPID = 5299;
    const SERVERPAYURL= 'https://h5pay.jd.com/jdpay/saveOrder';                         //h5京东支付服务地址
    const SERVERQUERURL = 'https://paygate.jdfin.local/service/query';                  //京东查询服务地址
    const REFUNDURL = 'https://paygate.jdfin.local/service/refund';                     //退款服务地址
    const UNIORDERURL = 'https://paygate.jd.com/service/uniorder';                      //扫码创建订单
    const QUERYREFUNDURL = 'https://paygate.jdfin.local/service/queryRefund';           //交易查询退款
    const REVOKEURL = 'https://paygate.jd.com/service/revoke';                          //撤销地址
    const FKMPAYURL = 'https://paygate.jdfin.local/service/fkmPay';                     //付款码支付

    public function verifyJdLogin($pt_key)
    {
        vendor('jdlogin.api.WLoginHelper');
        $app_id = self::LOGINAPPID;
        $client_info = new \ClientInfo('m', '4.4.1', '3.8.0', '320x480', 'uuid_12222222', 'appName', 'wifi', 'area', '10.2.44.8');
        $onlineCCAddr = "10.191.10.190:14000|10.191.10.191:14000|10.191.10.192:14000|10.191.10.193:14000"; //TODO 上线修改
        $devCCAddr = "192.168.144.119:14000";
        if (C("ISONLINE")) {
            $wlogin_helper = new \WLoginHelper($onlineCCAddr);
        } else {
            $wlogin_helper = new \WLoginHelper($devCCAddr);
        }
        $need_json = true;
        $ret = $wlogin_helper->verifyH5Login($app_id, $pt_key, $client_info, $need_json, $out_pin, $out_json);
        if ($ret === 0 && $wlogin_helper->getStatus() == 0) {
            if (!session('issave') ) {
                $userinfo = $this->getJdUserBaseInfo($out_pin);
                $res = $this->userBindJdAccount(session('openid'), $out_pin, $userinfo);
                session('userid', $res);
                session('issave', 1);                                     //解绑时需要去除
            }
            session('jdaccount', $out_pin);
            return true;
        } else {
            setcookie('pt_key', '', 1, '/', '.jd.com');
            setcookie('jdlogin_pt_key', '', 1, '/', '.jd.com');
            response('login check failed',3);
        }

    }

    public function verifyJdPcLogin($pin, $tgt)
    {
        vendor('jdlogin.api.WLoginHelper');
        $app_id = self::LOGINAPPID;
        $client_info = new \ClientInfo('m', '4.4.1', '3.8.0', '320x480', 'uuid_12222222', 'appName', 'wifi', 'area', '10.2.44.8');
        $onlineCCAddr = "10.191.10.190:14000|10.191.10.191:14000|10.191.10.192:14000|10.191.10.193:14000"; //TODO 上线修改
        $devCCAddr = "192.168.144.119:14000";
        if (C("ISONLINE")) {
            $wlogin_helper = new \WLoginHelper($onlineCCAddr);
        } else {
            $wlogin_helper = new \WLoginHelper($devCCAddr);
        }
//        $ret = $wlogin_helper->verifyLogin($pin, $app_id, $tgt, $client_info);
        $userinfo = $this->getJdUserBaseInfo($pin);
        session('jdaccount', $pin);
        return true;
//        if ($ret === 0) {
//
//        } else {
//            setcookie('pin', '', 1, '/', '.jd.com');
//            setcookie('thor', '', 1, '/', '.jd.com');
//            response('Pc Login failed',3);
//        }
    }

    public function getJdCitys($id)
    {
        $interface = 'com.jd.primitive.client.address.service.AreaService';
        $method = 'getCitys';
        $alias = C('ADDRESSALIAS');
        $res = $this->getJdApiResult($interface, $method, $alias, ['parentId'=>$id]);
        $areaList = $res['areaList'];
        return $areaList;
    }

    public function getJdCountys($id)
    {
        $interface = 'com.jd.primitive.client.address.service.AreaService';
        $method = 'getCountys';
        $alias = C('ADDRESSALIAS');
        $res = $this->getJdApiResult($interface, $method, $alias, ['parentId'=>$id]);
        $areaList = $res['areaList'];
        return $areaList;
    }

    public function getJdProvince()
    {
        $interface = 'com.jd.primitive.client.address.service.AreaService';
        $method = 'getProvinces';
        $alias = C('ADDRESSALIAS');
        $res = $this->getJdApiResult($interface, $method, $alias);
        var_dump($res);exit;
        $areaList = $res['areaList'];
        return $areaList;
    }

    public function userBindJdAccount($openid, $jdaccount, $otherparam)
    {
        if (!$openid) $openid = '';
        $userModel = new WeChatUserModel();
        $otherData['nickname'] = $otherparam['nickname'];
        $otherData['avatarurl'] = $otherparam['yunMidImageUrl'];
        $otherData['phone'] = $otherparam['mobile'];
        $otherData['openid'] = $openid;
        /*  if ($openid) { //在微信端
              $userModel->userUnBindJdAccount($jdaccount);
              $userModel->userBindJdaccountByOpenid($openid,$jdaccount, $otherData);
              $userinfo  = $userModel->getUserInfoByOpenid($openid);
              return $userinfo['id'];
          } else {                                                        //微信端外部
              $baseInfo = $userModel->getUserInfoByJdaccount($jdaccount);
              if (is_array($baseInfo) && count($baseInfo)) {
                  $userModel->userBindJdaccountByUserid($baseInfo['id'], $otherData);
                  return $baseInfo['id'];
              } else {
                  $res = $userModel->addJdAccount($jdaccount, $otherData);
                  return $res;
              }
          }*/
        $baseInfo = $userModel->getUserInfoByJdaccount($jdaccount);
        if (is_array($baseInfo) && count($baseInfo)) {
            $userModel->userBindJdaccountByUserid($baseInfo['id'], $otherData);
            return $baseInfo['id'];
        } else {
            $res = $userModel->addJdAccount($jdaccount, $otherData);
            $wechatRedis = new WeChatRedis();
            $wechatRedis->setUserOrderHistory($res, '',$otherparam['mobile']);
            return $res;
        }
    }

    private function getJdApiResult($interface, $alias, $method, $param = [])
    {
        $jsfurl = C('JSFURL');
        $sourceid = C('SOURCEID');
        $apiId = self::APIAPPID;

        $url  = $jsfurl.'/'.$interface.'/'.$alias.'/'.$method.'/'.$apiId;
        if (count($param) && is_array($param)) {
            $param = http_build_query($param);
            $url.='?'.$param;
        }
        $headers = [];
        $headers[] = 'token:.source:'.$sourceid;
        return $this->curl_get($url, $headers);
    }

    public function getJdUserBaseInfo($pin, $loadType = 3)
    {
        $interface = 'com.jd.user.sdk.export.UserInfoExportService';
        $method = 'getUserBaseInfoByPin';
        $alias = C('BASEINFOALIAS');
        $userinfo = $this->getJdApiResult($interface, $alias,$method, ['pin'=>$pin,'loadType'=>$loadType]);
        return $userinfo;
    }

    private function curl_get($url,$headers = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        if (count($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output, true);
    }

    public function createJdPayParam($ordersn , $jdaccount)
    {
        if (!$ordersn) response('订单异常');
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $status = $orderinfo['status'];
        if (!($status == 0 || $status == 2000)) {
            response('订单已支付，不能重复支付');
        }
        if ($orderinfo['addtime'] + 15*60 < time()) {
            response('订单已超时,请重新下单');
        }
        if ($orderinfo['jdaccount'] != $jdaccount) response('订单信息异常');

        $param = [];
        $param["version"]='V2.0';
        $param["merchant"]= \Jdpay\ConfigUtil::get_val_by_key("merchantNum");
        $param["tradeNum"]= $ordersn.'_'.time();
        $param["tradeName"]=$orderinfo['productname'];
        $param["tradeTime"]= DATE("YmdGis");
        $param["amount"]= "".$orderinfo['payrealprice']*100;
        $param["currency"]= "CNY";
        $param["note"]= $orderinfo['type'];
        $param["callbackUrl"]=getUrl().'/myWeb/index.php/AjaxApi/WeChatGuanJia/jdPayCallBack';
        $param["notifyUrl"]= getUrl()."/myWeb/index.php/WeChat/WeChatPublic/notifyJd";
        $param["userId"]= $jdaccount;
        $param["orderType"]= '0';
        if ($orderinfo['type'] == 2) {
            $param['receiverInfo'] = json_encode(['userid'=>$orderinfo['jdaccount'],'name'=>$orderinfo['addressname'],'mobile'=>$orderinfo['mobile'],'address'=>$orderinfo['address'],'province'=>$orderinfo['province'],'city'=>$orderinfo['city'],'country'=>$orderinfo['district']]);
        }
        $param['goodsInfo'] = json_encode([['cat1'=>$orderinfo['productid'],'cat2'=>$orderinfo['goodid'],'cat3'=>$orderinfo['specid'],'id'=>$orderinfo['specid'],'name'=>$orderinfo['specname'],'type'=>$orderinfo['servicetype'],'price'=>$orderinfo['totalprice']*100,'num'=>$orderinfo['num']]]);
        $param['riskInfo'] = json_encode(['uid'=>$orderinfo['userid'],'addGoods1ClassName'=>$orderinfo['productname'],'addGoods2ClassName'=>$orderinfo['goodname'],'addGoods3ClassName'=>$orderinfo['specname'],'industryType'=>$orderinfo['type']==1?'服务类':'快递类','orderTime'=>$orderinfo['addtime'],'orderUserName'=>$orderinfo['username'],'orderUserPhone'=>$orderinfo['userphone'],'bdid'=>$orderinfo['bdid'],'bdPhone'=>$orderinfo['bdphone'],'shopId'=>$orderinfo['guanjiaid'],'shopName'=>$orderinfo['guanjianame'],'shopPhone'=>$orderinfo['guanjiaphone'],'wechatId'=>empty($orderinfo['openid'])?$orderinfo['openid']:null]);
        $unSignKeyList = array ("sign");

        $oriUrl = self::SERVERPAYURL;
        $desKey = \Jdpay\ConfigUtil::get_val_by_key("desKey");
        $sign = \Jdpay\SignUtil::signWithoutToHex($param, $unSignKeyList);
        //echo $sign."<br/>";
        $param["sign"] = $sign;
        $keys = base64_decode($desKey);
        if($param["device"] != null && $param["device"]!=""){
            $param["device"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["device"]);
        }
        $param["tradeNum"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["tradeNum"]);
        if($param["tradeName"] != null && $param["tradeName"]!=""){
            $param["tradeName"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["tradeName"]);
        }
        if($param["tradeDesc"] != null && $param["tradeDesc"]!=""){
            $param["tradeDesc"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["tradeDesc"]);
        }

        $param["tradeTime"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["tradeTime"]);
        $param["amount"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["amount"]);
        $param["currency"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["currency"]);
        $param["callbackUrl"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["callbackUrl"]);
        $param["notifyUrl"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["notifyUrl"]);
        $param["ip"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["ip"]);
        if($param["note"] != null && $param["note"]!=""){
            $param["note"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["note"]);
        }
        if($param["userType"] != null && $param["userType"]!=""){
            $param["userType"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["userType"]);
        }
        if($param["userId"] != null && $param["userId"]!=""){
            $param["userId"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["userId"]);
        }
        if($param["expireTime"] != null && $param["expireTime"]!=""){
            $param["expireTime"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["expireTime"]);
        }
        if($param["orderType"] != null && $param["orderType"]!=""){
            $param["orderType"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["orderType"]);
        }
        if($param["industryCategoryCode"] != null && $param["industryCategoryCode"]!=""){
            $param["industryCategoryCode"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["industryCategoryCode"]);
        }
        if($param["specCardNo"] != null && $param["specCardNo"]!=""){
            $param["specCardNo"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["specCardNo"]);
        }
        if($param["specId"] != null && $param["specId"]!=""){
            $param["specId"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["specId"]);
        }
        if($param["specName"] != null && $param["specName"]!=""){
            $param["specName"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["specName"]);
        }
        //风控信息
        if($param["receiverInfo"] != null && $param["receiverInfo"]!=""){
            $param["receiverInfo"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["receiverInfo"]);
        }
        if($param["goodsInfo"] != null && $param["goodsInfo"]!=""){
            $param["goodsInfo"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["goodsInfo"]);
        }
        if($param["riskInfo"] != null && $param["riskInfo"]!=""){
            $param["riskInfo"]=\Jdpay\TDESUtil::encrypt2HexStr($keys, $param["riskInfo"]);
        }
        return ['param'=>$param,'oriUrl'=>$oriUrl];
        /* $str = '<body onload="autosubmit()"><form action="'.$oriUrl.'"  method="post" id="batchForm" >';
         foreach ($param as $key => $row) {
             $str.='<input type="hidden" name="'.$key.'" value="'.$row.'"/>';
         }
         $str.='</form><script>function autosubmit(){document.getElementById("batchForm").submit();}</script></body>';
         echo $str;*/
    }

    public function CheckJdPay($param)
    {
        if (!is_array($param)) die('no access');
        $desKey = \Jdpay\ConfigUtil::get_val_by_key("desKey");
        $keys = base64_decode($desKey);
        if($param["tradeNum"] != null && $param["tradeNum"]!=""){
            $param["tradeNum"]=\Jdpay\TDESUtil::decrypt4HexStr($keys, $param["tradeNum"]);
        }
        if($param["amount"] != null && $param["amount"]!=""){
            $param["amount"]=\Jdpay\TDESUtil::decrypt4HexStr($keys, $param["amount"]);
        }
        if($param["currency"] != null && $param["currency"]!=""){
            $param["currency"]=\Jdpay\TDESUtil::decrypt4HexStr($keys, $param["currency"]);
        }
        if($param["tradeTime"] != null && $param["tradeTime"]!=""){
            $param["tradeTime"]=\Jdpay\TDESUtil::decrypt4HexStr($keys, $param["tradeTime"]);
        }
        if($param["note"] != null && $param["note"]!=""){
            $param["note"]=\Jdpay\TDESUtil::decrypt4HexStr($keys, $param["note"]);
        }
        if($param["status"] != null && $param["status"]!=""){
            $param["status"]=\Jdpay\TDESUtil::decrypt4HexStr($keys, $param["status"]);
        }
        $sign =  $param["sign"];
        unset($param['sign']);
        $strSourceData = \Jdpay\SignUtil::signString($param, array());
        $decryptStr = \Jdpay\RSAUtils::decryptByPublicKey($sign);
        $sha256SourceSignString = hash ( "sha256", $strSourceData);
        if($decryptStr!=$sha256SourceSignString){
            die("no access");
        }else{
            $tradeNum = $param['tradeNum'];
            $tradeNum = explode('_', $tradeNum);
            $ordersn = $tradeNum[0];
            $orderModel = new OrderModel();
            $orderinfo  = $orderModel->getOrderInfo($ordersn);
            $url = getUrl().'/myWeb/WeChat/WeChatGuanJia/index?#';
            // 埋点
            $track = new WeChatTrackController();
            $track->collectChannelInside(session('channelId'),session('sgId'),session('scenceId'));
            session('channelId', '');
            session('sgId', '');
            session('scenceId', '');
            if ($param['status'] != 0) die('no access');
            if ($orderinfo['type'] == 1) {
                $url .='/orderSuccessServices/'.$ordersn;
            } elseif ($orderinfo['type'] == 2) {
                $url .='/orderSuccessGoods';
            } else {
                die("no access");
            }
            header("location:".$url);
        }


    }

    public function sendJdSms($sendAccount, $templateid, $param,$mobile,$isveirfy = 0, $ismuti = false,$token = '')
    {
        if ($isveirfy) { //发送验证
            $alias = C("SMSCODEALIAS");
            $jsfurl = 'http://soa-mms-verf.jd.local';
        } else {
            $alias = C("SMSCOMMONALIAS");
            $jsfurl = 'http://soa-mms.jd.local';
        }
        $interface = 'com.jd.mobilePhoneMsg.sender.client.service.SmsMessageTemplateRpcService';
        $url = $jsfurl.'/'.$interface.'/'.$alias;
        $method = '';
        if ($isveirfy && $ismuti) {
            $method = 'sendBatchSmsTemplateMessage';
        } elseif ($isveirfy) {
            $method = 'sendSmsTemplateMessage';
        } elseif ($ismuti) {
            $method = 'sendBatchSmsTemplateMessage';
        } else {
            $method = 'sendSmsTemplateMessage';
        }
        if ($ismuti) {
            $type = 'com.jd.mobilePhoneMsg.sender.client.request.BatchSmsTemplateMessage';
        } else {
            $type = 'com.jd.mobilePhoneMsg.sender.client.request.SmsTemplateMessage';
        }
        $url.= '/'.$method;
        if (!$token) $token = C('SMSTOKEN');
        if (!C("ISONLINE")) {
            $port = '22000';
        } else {
            $port = '';
        }
        if ($ismuti) {
            $sendparam[] = [
                '@type'=>$type,
                'token'=>$token,
                'templateParam'=>$param,
                'templateId'=>$templateid,
                'senderNum'=>$sendAccount,
                'mobileNumSet'=>$mobile,                                //群发mobile需要是数组
            ];
        } else {
            $sendparam[] = [
                '@type'=>$type,
                'token'=>$token,
                'templateParam'=>$param,
                'templateId'=>$templateid,
                'senderNum'=>$sendAccount,
                'mobileNum'=>$mobile,
            ];
        }
        $sendparam = json_encode($sendparam);
        $result = http_post_data($url, $sendparam,$port);
        $result = json_decode($result, true);
        if ($result['baseResultMsg']['errorCode'] === '999' && $result['resultMsg']['errorCode'] === '0') {
            return true;
        } else {
            $log = new Log();
            $result['url'] = $url;
            $result['sendparam'] = $sendparam;
            $log->writeLog(json_encode($result),'sendmsgFail');
            return false;
        }

    }

    public function creatJdShortUrl($url)
    {
        $interfae = 'com.jd.shorturl.api.jsf.ShortUrlService';
        $method = 'generateURL';
        $jsfurl = C('JSFURL');
        $apiId = self::APIAPPID;
        $jsfurl.='/'.$interfae.'/'.C('SHORTURLALIAS').'/'.$method.'/'.$apiId;
        $param['url'] = $url;
        $param['key'] = C("SHORTKEY");
        $param = http_build_query($param);
        $jsfurl.='?'.$param;
        $result = $this->curl_get($jsfurl);
        return $result['shortUrl'];
    }

}