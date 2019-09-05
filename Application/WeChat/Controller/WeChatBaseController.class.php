<?php
/**
 * Created by PhpStorm.
 * User: sunyufeng
 * Date: 2017/9/30
 * Time: 上午10:58
 */

namespace WeChat\Controller;
use Org\Util\EasyWeChat;
use Think\Controller;
use WeChat\Model\WeChatUserModel;

class WeChatBaseController extends Controller
{

    public $openid = '';
    public $token = '';
    public $encodingaeskey = '';
    public $appid = '';
    public $appsecret = '';
    public $WeObj;


    public function _initialize()
    {
        $refer = $_SERVER['HTTP_REFERER'];
        $requst_url = $_SERVER['REQUEST_URI'];
        $requst_url = strtolower($requst_url);
        if (strpos($refer,'realty.jd.com')!==false && strpos($requst_url,'servicesdetail')) {
            redirect(getUrl());
        }
        $address = I('get.city', '');
        if (!$address && !session('address')) {
            $address = getLocation();
        }
        session('address', $address);

        if(is_weixin() && APPENV === 'production') {
            if (!session('tmp3')) {
                session('userid','');
                session('openid','');
                session('issave','');
                session('jdaccount','');
            }
            $this->token = C("TOKEN");
            $this->appid = C("APPID");
            $this->appsecret = C("APPSECRET");
            $this->encodingaeskey = C("ENCODINGAESKEY");
            $this->option = [
                'token'         => $this->token,
                'encodingaeskey'=> $this->encodingaeskey,
                'appid'         => $this->appid,
                'appsecret'     => $this->appsecret
            ];
            vendor('WeChatPay.JsApiPay');
            $jsApiPay = new \JsApiPay();
            $openid = $jsApiPay->GetOpenid();
            $this->openid = $openid;
/*            if (!session('userid')) {
                $userModel = new WeChatUserModel();
                $userinfo = $userModel->getUserInfoByOpenid($openid);
                session('userid', $userinfo['id']);
                session('jdaccount', $userinfo['jdaccount']);
            }*/
            $this->WeObj = new \Org\Util\EasyWeChat($this->option);
            session('tmp3',1);
        }
    }
}