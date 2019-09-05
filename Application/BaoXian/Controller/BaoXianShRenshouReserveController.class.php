<?php

namespace BaoXian\Controller;

use Think\Controller;
use Org\Util\EasyWeChat;
use GuzzleHttp\Client;

class BaoXianShRenshouReserveController extends Controller {
    /**
     * pc端预约页面
     */
    public function index()
    {
        $pin = $_COOKIE['pin'];

        if (!$pin) {
            $this->jdLoginPc();
        }
        else {
            needAuthPc('jdLogin');
            // 一定是登陆状态，设置了session
            $this->assign('current_jdpin', session('jdaccount'));
        }
        $this->display();
    }

    public function jdLoginPc()
    {
        //获取域名或主机地址
        $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'https://';
        $url .= $_SERVER['HTTP_HOST'];
        $url .= '/myWeb/BaoXian/BaoXianShRenshouReserve';

        $jd_login_url = 'https://passport.jd.com/new/login.aspx?ReturnUrl=' . $url;
        echo "<script language=\"javascript\">";
        echo "window.location.href=\"$jd_login_url\"";
        echo "</script>";

    }

    /**
     * 京东登陆
     */
    public function jdLogin()
    {
        $product_page = I('get.productpage', 'h5');
        //获取域名或主机地址
        $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'https://';
        $url .= $_SERVER['HTTP_HOST'];
        if ($product_page === 'xinnuo') {
            $url .= '/myWeb/WeChat/WeChatGuanJia/xinnuo1016';
        }
        else {
            $url .= '/myWeb/BaoXian/BaoXianShRenshouReserve/' . $product_page;
        }

        $jd_login_url = 'https://plogin.m.jd.com/user/login.action?appid=422&returnurl=' . $url;
        echo "<script language=\"javascript\">";
        echo "window.location.href=\"$jd_login_url\"";
        echo "</script>";
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

    public function checkIsReserved($product) {
        if (session('jdaccount')) {
            // 查询预约状态
            $isReserved = D('Baoxianyuyue')->checkIsReserved(session('jdaccount'), $product);
            if ($isReserved) {
                $this->assign('isreserved', 1);
            }
            else {
                $this->assign('isreserved', 0);
            }
        } else {
            $this->assign('isreserved', 0);
        }
    }

    /**
     * 上海人寿稳赢一生h5页
     */
    public function h5()
    {
        redirect('https://djgjia.jd.com/bx/act/nianjin?' . $_SERVER['QUERY_STRING']);
        $product = '上海人寿—稳赢一生年金保险';
        $this->h5Auth();
        $this->checkIsReserved($product);
        $this->display('h5');
    }

    /**
     * 同方重疾h5页
     */
    public function h5blue()
    {
        redirect('https://djgjia.jd.com/bx/act/zhongji?' . $_SERVER['QUERY_STRING']);
        $product = '同方全球康健一生终生重大疾病保险';
        $this->h5Auth();
        $this->checkIsReserved($product);
        $this->display('h5blue');
    }

    public function baomc3()
    {
        redirect('https://djgjia.jd.com/bx/act/baomc3?' . $_SERVER['QUERY_STRING']);
        $product = 'MC3国际产子赴美险';
        $this->h5Auth();
        $this->checkIsReserved($product);
        $this->display('baomc3');
    }

    public function xinlian()
    {
        redirect('https://djgjia.jd.com/bx/act/xinlian?' . $_SERVER['QUERY_STRING']);
        $product = '东家鑫联星赴美防癌医疗保险';
        $this->h5Auth();
        $this->checkIsReserved($product);
        $this->display('xinlian');
    }

}