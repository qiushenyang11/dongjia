<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/9/29
 * Time: 10:11
 */

namespace Server;

use Operation\Model\GoodsModel;
use WeChat\Model\GuanJiaModel;

class SmsMessageTest
{
    public $msgStr = "亲爱的用户，您的短信验证码为###，5分钟内有效，若非本人操作请忽略。";
    public $msgFormat= [
        'subscribeSuccess'=>'【东家管家】您的"product"预订成功，服务码为"code",可直接凭此码消费,如有问题，可咨询客服servicePhone。',
        'subscribeFail' =>'【东家管家】很抱歉,您的"product"由于管家无法提供服务,已为您发起退款,金额money。如有问题,可咨询客服servicePhone。',
        'refundNoFree' =>'【东家管家】您的"product"已退款,退款金额"money"请注意查收。如有问题，可咨询客服servicePhone。',
        'refundFree' => '【东家管家】很抱歉,您的"product"由于管家无法提供服务,已为您取消订单。如有问题，可咨询客服servicePhone。',
        'refund' =>'【东家管家】您的"product"已退款,退款金额"money"请注意查收。如有问题，可咨询客服servicePhone。'
    ];
    public $kefuPhone = '4001063999';

    public $jdinfoToken = 'wvUOrlT5q21i';
    public $jdinfoAccount = 'wangmeng62';

    public $jdverifyToken = 'hvORrtICeMFe';
    public $jdverifyAccount = 'wangmeng286';

    public $jdsaleToken = 'gHwy4VTkpKpT';
    public $jdsaleAccount = 'wangmeng222';

    public $jdwarnToken = 'CCgHRtM3eQrl';
    public $jdwarnAccount = 'wangmeng223';

    /**
     * @breif send smsCode
     * @param string $phone
     * @return bool
     */
    public function sendMessage($phone = '')
    {
        if (!$phone) response("手机格式不正确");
        if (!preg_match("/^1\d{10}$/", $phone)) response("手机格式不正确");
        $redisClass = new WeChatRedis();
        $arr = $redisClass -> getSmsCodeinfo($phone);
        if ($arr['count'] >= C("LIMIT")) response("超过当天发送限额");  //大于当天发送次数，创蓝默认通用户10条
        $code = mt_rand(100000,999999);
        vendor("ChuanglanSms.ChuanglanSmsApi");
        $smsApi = new \ChuanglanSmsApi();
        $str =  str_replace('###',$code, $this->msgStr);
        $res = $smsApi -> sendSMS($phone, $str);
        //$res = true;
        if (!$res) response("发送失败，请重试")  ;                  //发送失败
        $res = $redisClass ->addSmsCode($phone, $code);
        return $res ? true : false;
    }

    /**
     * @breif 短信验证码验证
     * @param string $phone
     * @param string $code
     * @return bool
     */
    public function checkSmsCode($phone = '', $code = '')
    {
        if (!$phone) response("手机号码不能为空");
        if (!$code) response("请输入验证码");
        if (!preg_match("/^1\d{10}$/", $phone)) response("请输入正确的手机号");
        $redisClass = new WeChatRedis();
        $arr = $redisClass -> getSmsCodeinfo($phone);
        if ($arr['expiretime'] < time()) return false;  //验证时间以过期
        if ($arr['code'] == $code) {
            $redisClass -> delSmCodeInfo($phone);           //验证成功清除redis
            return true;
        } else {
            return false;
        }

    }

    public function sendsubscribeSuccess($phone,$param)
    {
        $tempparam = [];
        if ($param['servicetime']) {
            $msg = '【东家管家】您的"'.$param['product'].'"预订成功,服务时间'.$param['servicetime'].'，服务码为'.$param['code'].'，可直接凭此码消费，如有问题，可咨询客服'.$this->kefuPhone.'。';
            $templateid = '2806';
            $tempparam[] = $param['product'];
            $tempparam[] = $param['servicetime'];
            $tempparam[] = $param['code'];
            $tempparam[] = $this->kefuPhone;
        } else {
            $msg = '【东家管家】您的"'.$param['product'].'"预订成功,服务码为'.$param['code'].'，可直接凭此码消费，如有问题，可咨询客服'.$this->kefuPhone.'。';
            $templateid = '2632';
            $tempparam[] = $param['product'];
            $tempparam[] = $param['code'];
            $tempparam[] = $this->kefuPhone;
        }
        if (false) {
            vendor("ChuanglanSms.ChuanglanSmsApi");
            $smsApi = new \ChuanglanSmsApi();
            $res = $smsApi->sendSMS($phone,$msg);
        } else {
            $jdApi = new JdApi();
            $res = $jdApi->sendJdSms($this->jdinfoAccount,$templateid,$tempparam,$phone,0,false,$this->jdinfoToken);
        }

        return $res;

    }

    public function sendsubscribeFail($phone,$param)
    {
        $coin = intval($param['coin']);
        $payrealprice = floatval($param['payrealprice']);
        if ($coin && $payrealprice) {
            $str = '退款金额'.$payrealprice.'元，退东家银子'.$coin.'个';
        } elseif ($coin) {
            $str = '退东家银子'.$coin.'个';
        } elseif ($payrealprice) {
            $str = '退款金额'.$payrealprice.'元';
        } else {
            return true;
        }
        $msg = '【东家管家】很抱歉，您的"'.$param['product'].'"由于管家无法提供服务，已为您发起退款，'.$str.'。如有问题，可咨询客服'.$this->kefuPhone.'。';
        $templateid = '2837';
        $tempparam = [];
        $tempparam[] =$param['product'];
        $tempparam[] =$str;
        $tempparam[] =$this->kefuPhone;
        if (false) {
            vendor("ChuanglanSms.ChuanglanSmsApi");
            $smsApi = new \ChuanglanSmsApi();
            $res = $smsApi->sendSMS($phone,$msg);
        } else {
            $jdApi = new JdApi();
            $res = $jdApi->sendJdSms($this->jdinfoAccount,$templateid,$tempparam,$phone,0,false,$this->jdinfoToken);
        }

        return $res;
    }

    public function sendrefundNoFree($phone, $param)        //服务类退款短信
    {
        $coin = intval($param['coin']);
        $payrealprice = floatval($param['payrealprice']);
        if ($coin && $payrealprice) {
            $str = '退款金额'.$payrealprice.'元，退东家银子'.$coin.'个';
        } elseif ($coin) {
            $str = '退东家银子'.$coin.'个';
        } elseif ($payrealprice) {
            $str = '退款金额'.$payrealprice.'元';
        } else {
            return true;
        }
        $templateid = '2808';
        $tempparam = [];
        $tempparam[] = $param['product'];
        $tempparam[] = $str;
        $tempparam[] = $this->kefuPhone;
        if (false) {
            $msg = '【东家管家】您的"'.$param['product'].'"已退款，'.$str.'，请注意查收。如有问题，可咨询客服'.$this->kefuPhone.'。';
            vendor("ChuanglanSms.ChuanglanSmsApi");
            $smsApi = new \ChuanglanSmsApi();
            $res = $smsApi->sendSMS($phone,$msg);
        } else {
            $jdApi = new JdApi();
            $res = $jdApi->sendJdSms($this->jdinfoAccount,$templateid,$tempparam,$phone,0,false,$this->jdinfoToken);
        }
        return $res;
    }

    public function sendrefundFree($phone, $param)
    {
        $msg = '【东家管家】很抱歉，您的"'.$param['product'].'"由于管家无法提供服务，已为您取消订单。如有问题，可咨询客服'.$this->kefuPhone.'。';
        $templateid = '2633';
        $tempparam = [];
        $tempparam[] = $param['product'];
        $tempparam[] = $this->kefuPhone;
        if (false) {
            vendor("ChuanglanSms.ChuanglanSmsApi");
            $smsApi = new \ChuanglanSmsApi();
            $res = $smsApi->sendSMS($phone,$msg);
        } else {
            $jdApi = new JdApi();
            $res = $jdApi->sendJdSms($this->jdinfoAccount,$templateid,$tempparam,$phone,0,false,$this->jdinfoToken);
        }

        return $res;
    }

    public function sendrefund($phone,$param)           //商品类退款短信
    {
        $msg = '【东家管家】您的"'.$param['product'].'"已退款，退款金额'.$param['money'].'请注意查收。如有问题，可咨询客服'.$this->kefuPhone.'。';
        vendor("ChuanglanSms.ChuanglanSmsApi");
        $smsApi = new \ChuanglanSmsApi();
        $res = $smsApi->sendSMS($phone,$msg);
        return $res;
    }

    public function sendcustom($phone, $message,$orderinfo)        //发送自定义短信
    {
        $guanjiaid = $orderinfo['guanjiaid'];
        $ganjiaModel = new GuanJiaModel();
        $guanjiainfo = $ganjiaModel->getGuanjiaInfo($guanjiaid, $orderinfo['productid']);
        $servicetime = $orderinfo['servicetime'];
        $format=[
            ['name'=>"{{产品名称}}",'replace'=>$orderinfo['productname']],
            ['name'=>"{{服务码}}",'replace'=>$orderinfo['code']],
            ['name'=>"{{客服电话}}",'replace'=>$this->kefuPhone],
            ['name'=>"{{供应商电话}}",'replace'=>$guanjiainfo['customerphone']],
            ['name'=>"{{供应商简称}}",'replace'=>$guanjiainfo['suppliershort']],
        ];
        if ($servicetime) {
            $servicetime = Date("Y-m-d日 G:i",strtotime($servicetime));
            $format[]=['name'=>"{{服务时间}}",'replace'=>$servicetime];
        }
        foreach ($format as $row)
        {
            $message = str_replace($row['name'],$row['replace'],$message);
        }
        if (!$servicetime) {
            $message = str_replace('服务时间{{服务时间}}，','',$message);
        }
        if (false) {
            $message = '【东家管家】'.$message;
            $message = str_replace(' ','',$message);
            vendor("ChuanglanSms.ChuanglanSmsApi");
            $smsApi = new \ChuanglanSmsApi();
            $res = $smsApi->sendSMS($phone,$message);
        } else {
            var_dump($message);
            $templateid = '2636';
            $tempparam = [];
            $tempparam[] =$orderinfo['productname'];
            $tempparam[] = str_replace(' ','',preg_replace('/^您的\“.*\”预订成功，服务码为/','',$message));
            $jdApi = new JdApi();
            $res = $jdApi->sendJdSms($this->jdinfoAccount,$templateid,$tempparam,$phone,0,false,$this->jdinfoToken);
        }


        return $res;
    }

    public function sendmarketing($phone, $productname)
    {
        $msg ='【东家管家】您预订的“'.$productname.'”，如需查看详情请点击'.createShortUrl(getUrl().'/myWeb/WeChat/WeChatGuanJia/index?#/orderList').'，或关注微信服务号“东家会”，随时查看订单信息 ';
        if (false) {
            vendor("ChuanglanSms.ChuanglanSmsApi");
            $smsApi = new \ChuanglanSmsApi();
            $res = $smsApi->sendSMS($phone,$msg);
        } else {
            $jdApi = new JdApi();
            $templateid = '2809';
            $tempparam = [];
            $tempparam[] =$productname;
            $tempparam[] = $jdApi->creatJdShortUrl(getUrl().'/orderList');
            $res = $jdApi->sendJdSms($this->jdinfoAccount,$templateid,$tempparam,$phone,0,false,$this->jdinfoToken);
        }

        return $res;
    }

    public function sendPayPush($phone,$productname)
    {
        //您预订的“a”还未支付，好服务不等人，支付请点击b；
        $msg ='【东家管家】您预订的“'.$productname.'”还未支付，好服务不等人，支付请点击 '.createShortUrl(getUrl().'/myWeb/WeChat/WeChatGuanJia/index?#/orderList').' ';
        if (false) {
            vendor("ChuanglanSms.ChuanglanSmsApi");
            $smsApi = new \ChuanglanSmsApi();
            $res = $smsApi->sendSMS($phone,$msg);
        } else {
            $jdApi = new JdApi();
            $templateid = '2810';
            $tempparam = [];
            $tempparam[] =$productname;
            $tempparam[] = $jdApi->creatJdShortUrl(getUrl().'/orderList');
            $res = $jdApi->sendJdSms($this->jdinfoAccount,$templateid,$tempparam,$phone,0,false,$this->jdinfoToken);
        }
        return $res;
    }

    public function sendSmsCode($phone, $code)
    {
        $msg = '【东家管家】您的验证码为'.$code;
        if (false) {
            vendor("ChuanglanSms.ChuanglanSmsApi");
            $smsApi = new \ChuanglanSmsApi();
            $res = $smsApi->sendSMS($phone,$msg);
        } else {
            $templateid = '2811';
            $tempparam = [];
            $tempparam[] = $code;
            $jdApi = new JdApi();
            $res = $jdApi->sendJdSms($this->jdverifyAccount,$templateid,$tempparam,$phone,1,false,$this->jdverifyToken);
        }

        return $res;
    }

    public function sendSupplierInfo($phone, $ordername, $orderphone,$str)
    {
        $msg = '【东家管家】您有一笔新订单，用户'.$ordername.'，联系方式'.$orderphone.'，购买'.$str.'，请尽快处理。';
        if (false) {
            vendor("ChuanglanSms.ChuanglanSmsApi");
            $smsApi = new \ChuanglanSmsApi();
            $res = $smsApi->sendSMS($phone,$msg);
        } else {
            $templateid = '2812';
            $tempparam = [];
            $tempparam[] = $ordername;
            $tempparam[] = $orderphone;
            $tempparam[] = $str;
            $jdApi = new JdApi();
            $res = $jdApi->sendJdSms($this->jdinfoAccount,$templateid,$tempparam,$phone,0,false,$this->jdinfoToken);
        }

        return $res;
    }
}