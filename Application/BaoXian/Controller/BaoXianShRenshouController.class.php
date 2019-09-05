<?php

namespace BaoXian\Controller;

use GuzzleHttp\Exception\GuzzleException;
use Server\Log;
use Think\Controller;
use Org\Util\EasyWeChat;
use GuzzleHttp\Client;
use Server\WeChatRedis;

class BaoxianShRenshouController extends Controller {

    private $excel_api;
    private $row_count = 11;
    private $sign_param;

    public function __construct()
    {
        $this->excel_api = C('phpexcel_api');
        $this->sign_param = C('sign_param');
        parent::__construct();
    }

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

        $this->display();
    }

    public function getJssdkParam()
    {
        $url = I('get.url');
        // tp5.1有问题临时方案：如果tp5.1请求先返回status1, data内容格式
        $type = I('get.type', '');
//        echo $url; die;
        $url = urldecode($url);
//        var_dump($url);exit;
//        var_dump(urldecode(urldecode($url)));die;
        $wehcat = new EasyWeChat();
//        if (!$url) {
//            $url = 'https://'.$_SERVER['HTTP_HOST'].'/myWeb/WeChat/WeChatGuanJia/index?';
//        } else {
//            $url = urldecode(urldecode($url));
//        }
        $jssdk  = $wehcat->getJsSign($url);
        $jssdk['debug'] = false;
        $jssdk['jsApiList'] = ['onMenuShareAppMessage', 'onMenuShareTimeline'];
        //$jssdk['gjEnable'] = true;
        if ($type == 'tmp') {
            exit(json_encode([
                'status' => 1,
                'data' => $jssdk
            ]));
        }
        response('获取成功', 1, $jssdk);
    }

    public function setExcelApiAddr($address)
    {
        $this->excel_api = $address;
    }

    /**
     * 设置拿几条数据
     * @param $row_count
     */
    public function setRowCount($row_count)
    {
        $this->row_count = $row_count;
    }

    /**
     * 稳赢
     */
    public function home_new()
    {
        redirect('https://djgjia.jd.com/bx/baoexcel/renshou?' . $_SERVER['QUERY_STRING']);
        $this->assign('current_jdpin', session('jdaccount'));
        $this->assign('ishome', 'ishome');
        $this->display('index');
    }

    /**
     * 重疾
     */
    public function zhongji()
    {
        redirect('https://djgjia.jd.com/bx/baoexcel/zhongji?' . $_SERVER['QUERY_STRING']);
        $this->assign('current_jdpin', session('jdaccount'));
        $this->assign('ishome', 'ishome');
        $this->display('index');
    }

    private function checkIsReserved($product) {
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

    public function report($key = null)
    {
        if (!$key) {
            echo "<script language=\"javascript\">";
            echo "window.location.href='/myWeb/BaoXian/BaoXianShRenshou/home_new'";
            echo "</script>";
            return;
        }
        if (isset($_COOKIE['jdlogin_pt_key'])) {
            $ptKey = $_COOKIE['jdlogin_pt_key'];
        }
        else {
            $ptKey = $_COOKIE['pt_key'];
        }
        if (session('jdaccount')) {
            $this->assignRedisSheetData($key);
            $this->assign('current_jdpin', session('jdaccount'));
            $this->checkIsReserved('上海人寿—稳赢一生年金保险');
            $this->display('index');
            return;
        }
        if (!$ptKey && APPENV != 'test') {
            //获取域名或主机地址
            $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'https://';
            $url .= $_SERVER['HTTP_HOST'];
            $url .= '/myWeb/BaoXian/BaoXianShRenshou/report/key/' . $key;

            $jd_login_url = 'https://plogin.m.jd.com/user/login.action?appid=422&returnurl=' . $url;
            echo "<script language=\"javascript\">";
            echo "window.location.href=\"$jd_login_url\"";
            echo "</script>";
        }
        else {
            needAuth('jdLogin');
            // 一定是登陆状态，设置了session
            $this->assignRedisSheetData($key);
            $this->assign('current_jdpin', session('jdaccount'));
            $this->checkIsReserved('上海人寿—稳赢一生年金保险');
            $this->display('index');
        }
    }

    /**
     * 显示重疾结果
     * @param null $key
     */
    public function reportZhongji($key = null)
    {
        if (!$key) {
            echo "<script language=\"javascript\">";
            echo "window.location.href='/myWeb/BaoXian/BaoXianShRenshou/zhongji'";
            echo "</script>";
            return;
        }
        if (isset($_COOKIE['jdlogin_pt_key'])) {
            $ptKey = $_COOKIE['jdlogin_pt_key'];
        }
        else {
            $ptKey = $_COOKIE['pt_key'];
        }
        if (session('jdaccount')) {
            $this->assignRedisSheetData($key);
            $this->assign('current_jdpin', session('jdaccount'));
            $this->checkIsReserved('同方全球康健一生终生重大疾病保险');
            $this->display('index');
            return;
        }
        if (!$ptKey && APPENV != 'test') {
            //获取域名或主机地址
            $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'https://';
            $url .= $_SERVER['HTTP_HOST'];
            $url .= '/myWeb/BaoXian/BaoXianShRenshou/reportZhongji/key/' . $key;

            $jd_login_url = 'https://plogin.m.jd.com/user/login.action?appid=422&returnurl=' . $url;
            echo "<script language=\"javascript\">";
            echo "window.location.href=\"$jd_login_url\"";
            echo "</script>";
        }
        else {
            needAuth('jdLogin');
            // 一定是登陆状态，设置了session
            $this->assignRedisSheetData($key);
            $this->assign('current_jdpin', session('jdaccount'));
            $this->checkIsReserved('同方全球康健一生终生重大疾病保险');
            $this->display('index');
        }
    }

    private function assignRedisSheetData($key)
    {
        // 获取redis结果
        $redis = new WeChatRedis();
        $rs = $redis->getBaoxianShrenshouResult($key); // redis存储保险结果
        if ($rs) {
            $this->assign('sheet_data', json_encode($rs, 256));
        }
        else {
            $this->assign('sheet_data', '');
        }
    }

    private function getAgeByBirthday($birthday)
    {
        // 年龄计算
        // 算现在离生日有几天
        $obj = date_diff(date_create(date('Y-m-d')), date_create($birthday));
        return (int)$obj->format("%y");
    }

    /**
     * 参数签名
     * @param $timestamp
     * @return string
     */
    private function signArg($timestamp)
    {
        return md5($timestamp . $this->sign_param);
    }

    public function getSheetZhongji()
    {
        $birthday = I('get.birthday'); // 格式1994/02/06
        $baofei_year = I('get.years_count'); // 1, 5, 10, 15, 20
        $baoe = I('get.baoe'); // 完整价格。需要1000倍数
        $sex = I('get.sex', 'M');
        $need_fujia = I('get.need_fujia', 0);
        $fujia_people_birthday = I('get.fujia_people_birthday');
        $fujia_people_sex = I('get.fujia_people_sex');
        $name = I('get.name'); // 客户名称

        if ((int)$baofei_year !== 1 && (int)$baofei_year !== 5 && (int)$baofei_year !== 10 && (int)$baofei_year !== 15 && (int)$baofei_year !== 20) {
            response('请选择正确的交费期间。');
        }

        if ((int)$need_fujia === 1 && (!$fujia_people_birthday || !$fujia_people_sex)) {
            response('请填写所有附加险信息。');
        }

        if (!(int)$baoe) {
            response('请输入保额。');
        }

        if (!$name) {
            response('请输入被保人姓名。');
        }

        if (!$birthday) {
            response('请输入被保人生日。');
        }

        // 年龄计算
        // 算现在离生日有几天

        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $this->excel_api,
            // You can set any number of default request options.
            'timeout'  => 30,
        ]);

        if ($this->getAgeByBirthday($birthday) < 0) {
            response('请输入正确的被保人年龄。');
        }

        if ((int)$need_fujia === 1 && $this->getAgeByBirthday($fujia_people_birthday) < 0) {
            response('请输入正确的投保人年龄。');
        }

//        $beibao_people_age = $this->getAgeByBirthday($birthday);
//        if ((int)$need_fujia === 1) {
//            $toubao_people_age = $this->getAgeByBirthday($fujia_people_birthday);
//            if ($beibao_people_age > 17) {
//                response('被保人年龄需要小于等于17岁。');
//            }
//            if ($toubao_people_age < 18) {
//                response('投保人年龄需要大于等于18岁。');
//            }
//
//            if ($toubao_people_age - $beibao_people_age < 18 || $toubao_people_age - $beibao_people_age > 48) {
//                response('被保人与投保人的年龄差需要在18岁到48岁之间。');
//            }
//        }
//        else {
//            if ($beibao_people_age > 55) {
//                response('被保人年龄需要小于等于55岁。');
//            }
//        }

        // 选了附加险，被保人年龄只能是0-17岁。否则0-55岁
        // 被保人年龄差需要18-48

        $arg = [
            'age' => $this->getAgeByBirthday($birthday),
            'sex' => $sex,
            'baofei_year' => $baofei_year,
            'baoe' => ((int)$baoe) * 10000
        ];

        if ((int)$need_fujia === 1) {
            $arg['need_fujia'] = 1;
            $arg['fujia_people_age'] = $this->getAgeByBirthday($fujia_people_birthday);
            $arg['fujia_people_sex'] = $fujia_people_sex;
        }

        try {
            $arg['timestamp'] = time();
            $arg['sign'] = $this->signArg($arg['timestamp']);
            $res = $client->request('GET', $this->excel_api . '/zhongji/sheet', [
                'query' => $arg
            ]);
        } catch (GuzzleException $e) {
            $log = new Log();
            $log->writeLog($e->getMessage(), 'getSheetZhongji_err');
            response('系统繁忙!');
        }

        $json = (string)$res->getBody();
        $json = json_decode($json, true);

        if (!$json || $json['state'] != 1) {
            $log = new Log();
            $log->writeLog((string)$res->getBody(), 'getSheetZhongji_err');
            response('系统繁忙，请稍后再试。');
        }

        $json['data']['age'] = $this->getAgeByBirthday($birthday);
        if ((int)$need_fujia === 1) {
            $json['data']['fujia_people_birthday'] = $this->getAgeByBirthday($fujia_people_birthday);
        }

        $json['data']['query_arg'] = I('get.');

        $redis = new WeChatRedis();

        $key = $redis->setBaoxianShrenshouResult($json['data'], 'zhongji'); // redis存储保险结果
        return response('Success', 1, ['key' => $key]);

    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSheet()
    {

        $birthday = I('get.birthday'); // 格式1994/02/06
        $years_count = I('get.years_count'); // 1, 3, 5, 10
        $baofei = I('get.baofei'); // 单位万 1年最少1， 否则最少0.5
        $startage = $this->getAgeByBirthday($birthday);
        $sex = I('get.sex');
        $name = I('get.name'); // 客户名称

        $this->row_count = 106 - $startage; // 显示所有数据

        if (!$birthday || !$years_count || !$baofei || !$startage || !$sex || !$name) {
            return response('请填写所有项目!');
        }

        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $this->excel_api,
            // You can set any number of default request options.
            'timeout'  => 30,
        ]);

        $endage = $startage + $this->row_count;
        if ($endage > 106) $endage = 106;

//        127.0.0.1:9501/shrenshou/sheet?endage=50&birthday=1994/02/06&baofei=5&years_count=10
//        var_dump($this->row_count);exit;
        try {
            $arg = [
                'endage' => $endage,
                'startage' => $startage,
                'birthday' => $birthday,
                'baofei' => $baofei,
                'years_count' => $years_count,
                'sex' => $sex,
                'name' => $name,
                'jianbao_opts' => I('get.jianbao_opts', ''),
                'rowcount' => $this->row_count,
                'timestamp' => time()
            ];
            $arg['sign'] = $this->signArg($arg['timestamp']);
            $res = $client->request('GET', $this->excel_api . '/shrenshou/sheet', [
                'query' => $arg
            ]);
        } catch (GuzzleException $e) {
            $log = new Log();
            $log->writeLog($e->getMessage(), 'getwenyin_err');
            response('系统繁忙!');
        }

        $json = (string)$res->getBody();
        $json = json_decode($json, true);
        if (!$json || $json['state'] != 1) {
            $log = new Log();
            $log->writeLog((string)$res->getBody(), 'getwenyin_err');
            response('系统繁忙，请稍后再试。');
        }

        $sheet_data = $json['data']['sheet'];
        $baoe = $json['data']['baoe'];
        $rt['input_info'] = $json['data']['input_info'];

//        var_dump($data);exit;

        // 累计保费F列，现金价值J列，H生存保险金

        $cols = ['F', 'J', 'C', 'H', 'D', 'G', 'I', 'B', 'E'];
        $startrow = 11;
        $endrow = 116;
        $sheet_rt = [];
        for ($row = $startrow; $row <= $endrow; $row ++) {
            foreach ($cols as $col) {
                if (!$sheet_data['C' . $row]) continue;
                $sheet_rt[$row]['shencun_kouchu'] = 0; // 部分领取生存金扣除部分，前台编辑，计算
                switch ($col) {
                    case 'F':
                        $sheet_rt[$row]['total_baofei'] = $sheet_data[$col . $row];
                        break;
                    case 'J':
                        $sheet_rt[$row]['xianjin'] = $sheet_data[$col . $row];
                        break;
                    case 'C':
                        $sheet_rt[$row]['endage'] = $sheet_data[$col . $row];
                        break;
                    case 'H':
                        $sheet_rt[$row]['shencun'] = (int)$sheet_data[$col . $row]; // 生存金加利息累计
                        break;
                    case 'D':
                        $sheet_rt[$row]['dangnian_baofei'] = (int)$sheet_data[$col . $row];
                        break;
                    case 'G':
                        $sheet_rt[$row]['shencun_current_year'] = (int)$sheet_data[$col . $row]; // 当年领取的生存金
                        break;
                    case 'I':
                        $sheet_rt[$row]['shengu'] = (int)$sheet_data[$col . $row]; // 身故保险金
                        break;
                    case 'B':
                        $sheet_rt[$row]['yearend_count'] = (int)$sheet_data[$col . $row]; // 保单年度末
                        break;
                    case 'E':
                        $sheet_rt[$row]['jianbao_amount'] = (int)$sheet_data[$col . $row]; // 保单年度末
                        break;
                }
            }
            if ($sheet_rt[$row]['endage'] == $endage) break;
        }
        $rt['baoe'] = $baoe;
        $rt['sheet'] = array_values($sheet_rt);
        $rt['query_arg'] = I('get.');
        $redis = new WeChatRedis();
        $key = $redis->setBaoxianShrenshouResult($rt); // redis存储保险结果

        $rt['report_key'] = $key;

        return response('Success', 1, $rt);
    }

    /**
     * 记录错误日志
     */
    public function reportLog() {
        $log = new Log();
        $log->writeLog(I('post.content', ''), 'shrenshou');
    }

    /**
     * 下载前端生成的pdf
     */
    public function downloadPdf() {
        $filename = I('get.fileurl');
//        $filename = '/2018/10/15/upload_m5umpkf8u7u3nsfn.pdf';
        $hostname = C('UPLOADURL');
        $pdfcontents = file_get_contents($hostname . $filename);
        Header("Content-type:application/pdf");
        echo $pdfcontents;
    }

    public function pdfpreview() {
        echo '请重新生成PDF，谢谢';
    }

    public function getZhongjiClauseHtml() {
        $htmlfile = realpath('./') . '/Application/BaoXian/View/BaoXianShRenshou/ClauseContent.html';
        $htmlfile2 = realpath('./') . '/Application/BaoXian/View/BaoXianShRenshou/ClauseContent2.html';
        $htmlfile3 = realpath('./') . '/Application/BaoXian/View/BaoXianShRenshou/ClauseContent3.html';
        $htmlfile4 = realpath('./') . '/Application/BaoXian/View/BaoXianShRenshou/ClauseContent4.html';
        $htmlfile5 = realpath('./') . '/Application/BaoXian/View/BaoXianShRenshou/ClauseContent5.html';
        return response('Success', 1, ['html' =>
            [
                file_get_contents($htmlfile), file_get_contents($htmlfile2), file_get_contents($htmlfile3), file_get_contents($htmlfile4), file_get_contents($htmlfile5)
            ]
        ]);
    }

    /**
     * 11月2日静态首页
     */
    public function static_home () {
        redirect('https://djgjia.jd.com/bx/home?' . $_SERVER['QUERY_STRING']);
        $this->h5Auth();
        $this->display('index');
    }
    public function questionaire () {
        redirect('https://djgjia.jd.com/bx/home/questionaire?' . $_SERVER['QUERY_STRING']);
        $this->h5Auth();
        $this->display('index');
    }
    public function article() {
        redirect('https://djgjia.jd.com/bx/article?' . $_SERVER['QUERY_STRING']);
        $this->h5Auth();
        $this->display('index');
    }
    public function jdLogin()
    {
        $product_page = I('get.productpage', 'h5');
        //获取域名或主机地址
        $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'https://';
        $url .= $_SERVER['HTTP_HOST'];
        $url .= '/myWeb/BaoXian/BaoXianShRenshou/' . $product_page;

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
}