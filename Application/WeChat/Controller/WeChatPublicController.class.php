<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/11/24
 * Time: 16:39
 */

namespace WeChat\Controller;


use Operation\Model\OrderModel;
use Server\JdApi;
use Server\Order;
use Server\WeChatRedis;
use Think\Controller;

class WeChatPublicController extends Controller
{
    //微信支付回调
    public function notifyWeChat()
    {
        $info = file_get_contents('php://input');
        $msg = (array)simplexml_load_string($info, 'SimpleXMLElement', LIBXML_NOCDATA);
        $orderClass = new Order();
        $orderClass->orderNotifyWeChat($msg);
        echo 'SUCCESS';
    }

    //小程序微信支付回调
    public function notifyWeChatXCX()
    {
        $info = file_get_contents('php://input');
        $msg = (array)simplexml_load_string($info, 'SimpleXMLElement', LIBXML_NOCDATA);
        $orderClass = new Order();
        $orderClass->orderNotifyWeChat($msg, true);
        echo 'SUCCESS';
    }

    //京东支付回调
    public function notifyJd()
    {

        $info = file_get_contents('php://input');
        $orderClass = new Order();
        $orderClass->orderNotifyJd($info);
        echo "ok";
    }
}