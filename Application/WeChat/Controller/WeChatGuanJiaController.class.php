<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/11/16
 * Time: 14:06
 */

namespace WeChat\Controller;


use Server\JdApi;
use Think\Controller;
use Think\Exception;

class WeChatGuanJiaController extends WeChatBaseController
{

    /**
     * @breif 管家页面主体
     */
    public function index()
    {
        // $code = I('get.code', '');
        // $gets = I('get.');
        // if ($code) {
        //     unset($gets['id']);
        //     unset($gets['code']);
        //     unset($gets['state']);
        //     $param = http_build_query($gets);
        //     $url = getUrl().$_SERVER['REQUEST_URI'];
        //     $url = explode('?', $url)[0];
        //     if (!!$param) {
        //         $url .= '?'.$param;
        //     }
        //     $url = str_replace('?&#', '?#', $url);

        //     header('Location:'.$url);
        // } else {
        //     if (C("ISONLINE")) {
        //         $loginurl = 'https://plogin.m.jd.com';
        //     } else {
        //         $loginurl = 'https://plogin.m.jd.com';
        //     }
    
        //     $this->assign('loginurl',$loginurl);
    
        //     $this->assign('kefuPhone', '4001063999');
        //     $this->assign('kefuTime', '09:00-21:00');
        //     $this->display('index');    
        // }
        $this->_empty('index');
    }

    /**
     * 前端h5路由显示
     */
    public function _empty($route)
    {
        $code = I('get.code', '');
        $gets = I('get.');

        if ($code) {
            unset($gets['id']);
            unset($gets['code']);
            unset($gets['state']);
            $param = http_build_query($gets);
            $url = getUrl().$_SERVER['REQUEST_URI'];
            $url = explode('?', $url)[0];
            if (!!$param) {
                $url .= '?'.$param;
            }
            $url = str_replace('?&#', '?#', $url);

            header('Location:'.$url);
        } else {
            if (C("ISONLINE")) {
                $loginurl = 'https://plogin.m.jd.com';
            } else {
                $loginurl = 'https://plogin.m.jd.com';
            }
    
            $this->assign('loginurl',$loginurl);
    
            $this->assign('kefuPhone', '4001063999');
            $this->assign('kefuTime', '09:00-21:00');
            $this->assign('city', session('address'));
            $this->assign('ipcity', getLocation()); // ip定位所在城市
            $this->assign('jdpin', session('jdaccount'));
//            echo $this->getLocation();die;

            $this->display('index');    
        }
    }

    public function h5Auth()
    {
        if (isset($_COOKIE['jdlogin_pt_key'])) {
            $ptKey = $_COOKIE['jdlogin_pt_key'];
        }
        else {
            $ptKey = $_COOKIE['pt_key'];
        }
        if (!$ptKey) {
            $this->assign('user_jdpin', '');
        }
        else {
            needAuth('jdLogin');
            // 一定是登陆状态，设置了session
            $this->assign('user_jdpin', session('jdaccount'));
        }
    }

    public function manager()
    {
        $managerid = I('get.id',0);
        $gets = I('get.');
        if (!$managerid) {
            $url = getUrl().'/myWeb/WeChat/WeChatGuanJia/index';
        } else {
            unset($gets['id']);
            unset($gets['code']);
            unset($gets['state']);
            $param = http_build_query($gets).'&';
            $url = getUrl().'/myWeb/WeChat/WeChatGuanJia/index?'.$param.'#/manager/'.$managerid;
            $url = getUrl().'/manager/'.$managerid;
            $url = str_replace('?&#', '?#', $url);
        }
        header('Location:'.$url);
    }

    public function product()
    {
        $productid = I('get.id',0);
        $productModel = new \WeChat\Model\GoodsModel();
        $guanjiaid = $productModel->getGuanJiaIdByProductid($productid);
        $gets = I('get.');
        if (!$productid) {
            $url = getUrl().'/myWeb/WeChat/WeChatGuanJia/index';
        } else {
            unset($gets['id']);
            unset($gets['code']);
            unset($gets['state']);
            $param = http_build_query($gets).'&';
            $url = getUrl().'/myWeb/WeChat/WeChatGuanJia/index?'.$param.'#/servicesDetail/'.$guanjiaid.'/'.$productid;
            $url = getUrl().'/servicesDetail/'.$productid;
            $url = str_replace('?&#', '?#', $url);
        }
        header('Location:'.$url);
    }


    /**
     * 以下为活动专用方法
     */
    public function activity(){
        $aid = I('get.aid');//
        $aName = '0710ejq';

        if ($aid == 11) {
            $aName = '0328free';
        }
        if ($aid == 12) {
            $aName = '0328v2free';
        }
        if ($aid == 13) {
            $aName = '0328v3free';
        }
        if ($aid == 14) {
            $aName = '0710ejq';
        }

        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }

    public function libao() {
        $aName = '0718libao';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }
    public function shmtuina() {
        $aName = '0719smtn';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }
    public function jhtop() {
        $aName = '0725jhtop';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }
    public function quanyi() {
        $aName = '0726quanyi';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }
    public function jiangren() {
        $aName = '0801jiangren';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }
    public function jijing() {
        $aName = '0801jijing';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }
    public function yuanqi() {
        $aName = '0808yuanqi';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }
    public function huiyuanri() {// 标准模版（自己用）
        $aName = '0808huiyuanri';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }
    public function jieya() {// 标准模版（京东）
        $aName = '0808jieya';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }
    public function qixi(){
        $aName = '0816qixi';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }

    public function dongrich(){
        $aName = '0816dongrich';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }

    public function dalibao(){
        $aName = '0821dalibao';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }

    public function dongjiahuiyuanri(){
        $aName = '0821dongjiahuiyuanri';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }

    public function test()
    {
        $jdapi = new JdApi();
        $rs = $jdapi->getJdUserBaseInfo('roda111');
        var_dump($rs);
    }
    public function jianjingyao() {// 标准模版（京东）
        $aName = '0828jianjingyao';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }

    public function jingxi() {
        $aName = '0828jingxi';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }

    public function newyuanqi() {
        $aName = '0830yuanqi';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }

    public function chinaunicom0910() {
        $aName = '0910chinaunicom';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        // $jdUnder = $this->fetch('jdUnder');
        // $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }

    public function huaplus() {
        $aName = '0911huaplus';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        // $jdUnder = $this->fetch('jdUnder');
        // $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }

    public function jdchinaunicom0910() {
        $aName = '0910jdchinaunicom';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }

    public function jdhuaplus() {
        $aName = '0911jdhuaplus';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }

    public function jianjingyao0913() {// 标准模版（京东）
        $aName = '0913jianjingyao';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }

    public function midautumn0921() {
        // $aName = '0921MidAutumn';
        $aName = '0927huaplus';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }

    public function huaplus0927() {
        $aName = '0927huaplus';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);
    }

    public function shj1010_ld() {
        $aName = '1010shj';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);

    }

    public function xinnuo1016() {
        $aName = '1016xinnuo';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->h5Auth();
        $this->assign('loginurl',$loginurl);

        $this->display($aName);

    }

    public function shj1010() {
        $aName = '1112shj';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->assign('loginurl',$loginurl);

        $jdUnder = $this->fetch('jdUnder');
        $this->assign('jdUnder', $jdUnder);
        $this->display($aName);

    }

    public function sishu1112() {
        $aName = '1112sishuyiliao';
        if (C("ISONLINE")) {
            $loginurl = 'https://plogin.m.jd.com';
        } else {
            $loginurl = 'https://plogin.m.jd.com';
        }
        $this->h5Auth();
        $this->assign('loginurl',$loginurl);

        $this->display($aName);

    }

    public function ss01() {
        echo 'ss01';
    }

    public function getLocation() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $url = "http://ip.taobao.com/service/getIpInfo.php?ip=$ip";
        $result = json_decode(curl_get($url), true);
        return $result['data']['city'];
    }

    public function zhaopin() {
        $this->display('zhaopin');
    }

    public function makenewdjia() {
        $aName = '0928makenewdongjia';
        $this->display($aName);

    }
    public function newdongjia() {
        $aName = '0928newdongjia';
        $this->display($aName);
    }
    public function zhongou() {
        $aName = 'zhongou';
        $this->display($aName);
    }

    public function zhongoustatus(){
        $aName = 'zhongoustatus';
        $this->display($aName);
    }
    public function yinghua(){
        $aName = 'yinghua';
        $this->display($aName);
    }
}
