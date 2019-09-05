<?php

namespace BaoXian\Controller;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Server\GJAES;
use Think\Controller;
use Think\Exception;

//https://www.dservie.cn/myWeb/index.php/BaoXian/

class BaoXianLeadsController extends Controller
{
    //https://www.dservie.cn/myWeb/index.php/BaoXian/BaoXianLeads/index

    private $is_getrealname = false;

    public function index()
    {
        $this->display();
    }
    //https://www.dservie.cn/myWeb/index.php/BaoXian/BaoXianLeads/shrsLeads
    //Leads 预约//
    /**
     * @throws GuzzleException
     */
    public function shrsLeads()
    {
        $jdAccount=session('jdaccount');
//        $jdAccount='123';

        $fromBy=$_GET["fromby"];

        $testSelf=$_GET["testself"];

        $product_name = I('get.product_name', '上海人寿—稳赢一生年金保险');

        $testSelfData = json_decode($testSelf, true);

        if (!$jdAccount) {
            response("请刷新页面重试。 ");
        }

        if(!$fromBy)
        {
            $fromBy="JD RICH GUANJIA";
        }

        if(!$testSelf)
        {
            $testSelf="没有测试";
        }
        /********测试数据*********/

//        $phone=18019159738;
//
//        $fromBy="Fuck You!";
//
//        $testSelf="dddhjhdhhdhd";
//
//        $jdAccount="GFDFGGFCC44542c";
//
        /********测试数据*********/

        $product = $product_name;

        $rst=D('Baoxianyuyue')->checkIsReserved($jdAccount, $product);


        if($rst)
        {
            response("亲爱的用户，感谢您已经预约过东家金服保险规划师 ");
        }
        else
        {
            $data["jdaccount"]=$jdAccount;

            $data["fromby"]=$fromBy;

            $data["testself"]=$testSelf;

            // 获取用户姓名，地址，手机号信息

            $testSelf_arr = json_decode($data["testself"], true);

            try {
                $user_data = $this->getUserNameByJdpin($jdAccount);

                if ($user_data) {
                    $testSelf_arr['address'] = $user_data['address'];
                    $testSelf_arr['name'] = $user_data['realname'];
                }
            } catch (GuzzleException $e) {
            }

            $data["testself"] = json_encode($testSelf_arr, 256);

            $jdApiClass = new \Server\JdApi();
            $baseinfo = $jdApiClass->getJdUserBaseInfo($jdAccount);
            if ($baseinfo) {
                $data["phone"] = $baseinfo['mobile'];;
            }
            else {
                $data["phone"] = 'no phone';
            }
            $isadd = 0;
            if (D('Baoxianyuyue')->checkIsMarkedCancel($jdAccount, $product)) {
                $data['addtime'] = date('Y-m-d h:i:s', time());
                $rst=M('Baoxianyuyue')->where([
                    'jdaccount' => session('jdaccount'),
                    'product' => $product
                ])->save($data);
            }
            else {
                $isadd = 1;
                $data["product"]=$product;
                $rst=M('Baoxianyuyue')->add($data);
            }
            if ($isadd) {
                $client = new Client();
                $response = $client->request('POST', "https://bx.dongrich.cn/api/Leads",['form_params'=>[
                    'data'=>$data,
                ]]);
            }

            if($rst)
            {
                response("Success",1,'预约成功，您的保险规划师将尽快联系您');
            }
            else
            {
                response("预约失败，请重试 ");
            }
        }


    }

    /**
     * 取消预约
     */
    public function cancelLeads() {
        $jdAccount=session('jdaccount');
        if (!$jdAccount) response("没有登陆");
        $product_name = I('get.product_name', '');
        if (!$product_name)  response("检查参数！");
        $where["jdaccount"]=array('eq',$jdAccount);
        $where['product'] = $product_name;
        $YuYue=M("baoxianyuyue");
        $rst=$YuYue->where($where)->limit(1)->find();
        if(!$rst)
        {
            response("没有预约纪录");
        }
        $YuYue->where($where)->setField('fromby', 'CANCEL'); // 标记取消预约
        response("Success",1,'取消预约成功！');
    }

    /**
     * @param \PHPExcel $excel
     * @throws \PHPExcel_Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    // https://djgjia.jd.com/myWeb/BaoXian/BaoXianLeads/leadsExport?starttime=2018/09/02&endtime=2018/09/03
    private function formatExcel($excel = null) {
        vendor("excel.PHPExcel");
        $excelWrite = new \PHPExcel();

//        $PHPExcelHandler=new \PHPExcel_Reader_Excel2007();
//        $excel = $PHPExcelHandler->load('/mnt/g/www/leadslist0907.xlsx');

        $sheet = $excel->getSheet(0);
        $rows = $sheet->getHighestRow();
        $start_row = 2;
        $end_row = $rows;
        $channel_push = [];
        $channel_banner = [];
        $channel_sms = [];
        $channel_none = [];

        // 设置表头
        $head_arr = ['ID', '客户PIN', '客户姓名', '客户手机号', '号码归属地', '客户地址', '合格投资者',
            '产品名称', '需求描述', '理财师ID', '理财师姓名', '部门', '预约来源', '预约渠道', '预约方式', '资讯标题', '预约类型（4）', '预约时间'];
        $write_sheet = $excelWrite->setActiveSheetIndex(0);
        $col = 'A';
        foreach ($head_arr as $head) {
            $write_sheet->setCellValue($col ++ . '1', $head);
        }

        $write_index = 2;
        for ($row = $start_row; $row <= $end_row; $row ++) {
            $pin = $sheet->getCell('B' . $row)->getValue();
            $phone = $sheet->getCell('C' . $row)->getValue();
            $way = $sheet->getCell('D' . $row)->getValue();
            $time = $sheet->getCell('E' . $row)->getValue();
            $json = $sheet->getCell('F' . $row)->getValue();
            $product = $sheet->getCell('G' . $row)->getValue();

            $arr = json_decode($json, true);
            $channel = $arr['channel'];

            $write_sheet->setCellValue('B' . $write_index, $pin);
            $write_sheet->setCellValue('C' . $write_index, isset($arr['name'])?$arr['name']:''); // 客户姓名
            $write_sheet->setCellValue('D' . $write_index, $phone);
            $write_sheet->setCellValue('E' . $write_index, ''); // 号码归属地
            $write_sheet->setCellValue('F' . $write_index, isset($arr['address'])?$arr['address']:''); // 客户地址
            $write_sheet->setCellValue('H' . $write_index, $product);
            $write_sheet->setCellValue('I' . $write_index, $product);
//            $write_sheet->setCellValue('M' . $write_index, $way);
            $write_sheet->setCellValue('M' . $write_index, 'offline');
            $write_sheet->setCellValue('N' . $write_index, $channel);
            $write_sheet->setCellValue('R' . $write_index, date("Y-m-d", strtotime($time)));
            $write_sheet->setCellValue('Q' . $write_index, '3');


            $write_sheet->getColumnDimension('H')->setWidth(40);
            $write_sheet->getColumnDimension('F')->setWidth(40);
            $write_sheet->getColumnDimension('B')->setWidth(30);
            $write_sheet->getColumnDimension('D')->setWidth(30);
            $write_sheet->getColumnDimension('M')->setWidth(30);
            $write_sheet->getColumnDimension('R')->setWidth(30);

            if (strstr($arr['channel'], 'push')) {
                $channel_push[] = $arr['userjdpin'];
            }
            else if (strstr($arr['channel'], 'banner')) {
                $channel_banner[] = $arr['userjdpin'];
            }
            else if (strstr($arr['channel'], 'banner')) {
                $channel_sms[] = $arr['userjdpin'];
            }
            else if (strstr($arr['channel'], 'makemoney')) {
                $channel_makemoney[] = $arr['userjdpin'];
            }
            else $channel_none[] = $arr['userjdpin'];
            $write_index ++;
        }

        $write_sheet->setCellValue('S2', 'push:'. count($channel_push));
        $write_sheet->setCellValue('T2', 'banner:'. count($channel_banner));
        $write_sheet->setCellValue('U2', 'sms:'. count($channel_sms));
        $write_sheet->setCellValue('V2', 'none:'. count($channel_none));
        $write_sheet->setCellValue('W2', 'makemoney:'. count($channel_makemoney));
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.date('Y-m-d').'new.xlsx');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($excelWrite, 'Excel5');
        $objWriter->save('php://output');

    }

    /**
     * 财富接口拿用户姓名，地址
     * @param $jdpin
     * @return bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getUserNameByJdpin($jdpin = 'jd_test01') {
        if (APPENV === 'test') return false;
        $aes = new GJAES();
        $aes_pwd = 'woshinibaba';
//        jd_test01 测试jdpin
//       result { ["address"]=> string(0) "" ["jdaccount"]=> string(9) "jd_test01" ["realname"]=> string(9) "jd_test01" }
        $jdpin = $aes->aes_encrypt($jdpin, $aes_pwd);
        $client = new Client();
        $api_url = C('realname_api');
        $response = $client->request('POST', $api_url . '/webUser/getUserInfo', [
            'json' => [
                'jdaccount' => $jdpin
            ]
        ]);
        $res = $response->getBody();
        $res = json_decode((string)$res, true);
        $encryped_data = json_decode($res['data'], true);
        $decrypted_json = $aes->aes_decrypt($encryped_data['data'], $aes_pwd);
        $decrypted_res = json_decode($decrypted_json, true);
        if ($decrypted_res) return $decrypted_res;
        return false;
    }


    //https://www.dservie.cn/myWeb/index.php/BaoXian/BaoXianLeads/shrsList
    //显示Leads 表
    public function shrsList()
    {
        $this->checkAuth();
        $p=I('get.p', 1);

        $startTime = I('get.starttime','');

        if ($startTime) {
            $this->assign('starttime', $startTime);
        }

        $startTime = str_replace('+',' ', $startTime);

        $endTime = I('get.endtime', '');

        if ($endTime) {
            $this->assign('endTime', $endTime);
        }

        $endTime = str_replace('+', ' ', $endTime);

//        var_dump([$p,$startTime,$endTime]);die;
        $where = [];

        if ($startTime && $endTime)
        {
            $where['addtime'] = [['gt',strtotime($startTime)],['elt',strtotime($endTime)]];
        }
        elseif ($startTime)
        {
            $where['addtime'] = ['gt',strtotime($startTime)];
        }
        elseif ($endTime)
        {
            $where['addtime']  = ['lt', strtotime($endTime)];
        }

//        dump($where);die;
        $YuYue=M("baoxianyuyue");

        $allCountLeads=$YuYue->count();

        $count=$YuYue->where($where)->count();

        $Page=new \Think\Page($count,20);

        $Page->nowPage=$p;

        $rst=$YuYue->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $first=$Page->firstRow+1;
        $rest=($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
        $end = $first + $rest-1;
        $all=$first.'-'.$end;

        for($i=0;$i<$count;$i++)
        {
            $tempJson=$rst[$i]["testself"];

            $jsonObj=json_decode($tempJson);

            $rst[$i]["name"]=$jsonObj->name;

            $rst[$i]["testprice"]=$jsonObj->price;

            $rst[$i]["testyears"]=$jsonObj->year;

            $rst[$i]['jdaccount'] = urldecode($rst[$i]['jdaccount']);
        }

        // var_dump($Page);

        $outPage = $Page->show();

        $outPage = str_replace('href="', 'href="/myWeb', $outPage);
        $this->assign('result',$rst);

        $this->assign('page',$outPage);

        $this->assign('nowPage',$p);

        $this->assign('totalPages',$Page->totalPages);

        $this->assign("count",$count);

        $this->assign('all',$all);

        $this->assign("allLeads",$allCountLeads);

        $this->display();

    }


    //https://www.dservie.cn/myWeb/index.php/BaoXian/shrsLeads
    //Leads 导出
    public function leadsExport()
    {
        $this->checkAuth();
//        http://gjlocal.jd.com/index.php/BaoXian/BaoXianLeads/leadsExport?starttime=2018/08/27&endtime=2018/08/29
        $startTime = I('get.starttime','');

        $endTime = I('get.endtime', '');

        $this->is_getrealname = I('get.realname', false);

        if ($startTime && $endTime)
        {
            $where = "UNIX_TIMESTAMP(addtime) > " . strtotime($startTime) . ' AND UNIX_TIMESTAMP(addtime) <= ' . strtotime($endTime);
        }
        elseif ($startTime)
        {
            $where = "UNIX_TIMESTAMP(addtime) > " . strtotime($startTime);
        }
        elseif ($endTime)
        {
            $where = "UNIX_TIMESTAMP(addtime) < " . strtotime($endTime);
        }

        $where .= ' AND fromby != "CANCEL"';

        $YuYue=M("baoxianyuyue");

        $res=$YuYue->where($where)->field("id,jdaccount,phone,fromby,addtime,testself,product")->select();

        if ($this->is_getrealname) {
            set_time_limit(0);  //设置程序执行时间
            ignore_user_abort(true);    //设置断开连接继续执行
            header('X-Accel-Buffering: no');    //关闭buffer
            header('Content-type: text/html;charset=utf-8');    //设置网页编码
            ob_start(); //打开输出缓冲控制
            echo 'testing...<br>';
            // 更新数据库再重定向导出
            foreach ($res as $item) {
                echo "getting jdaccount {$item['jdaccount']}...<br>";
                try {
                    $user_data = $this->getUserNameByJdpin($item['jdaccount']);
                    if ($user_data) {
                        $org_res = $YuYue->where(['jdaccount' => $item['jdaccount']])->select();
                        $org_json = $org_res[0]['testself'];
                        $org_info_arr = json_decode($org_json, true);
                        if (isset($org_info_arr['address']) || isset($org_info_arr['name'])) {
                            echo 'already has realname info, skip<br>';
                            echo ob_get_clean();
                            flush();
                            continue;
                        }
                        $reserve_name = $user_data['realname'];
                        $address = $user_data['address'];
                        echo "name {$reserve_name} address {$address}<br>";
                        $origin_json = json_decode($item['testself'], true);
                        $origin_json['address'] = $address;
                        $origin_json['name'] = $reserve_name;
                        $YuYue->where(['jdaccount' => $item['jdaccount']])->setField('testself', json_encode($origin_json, 256));
                        echo ob_get_clean();
                        flush();

                    } else {
                        echo 'no realname data<br>';
                        echo ob_get_clean();
                        flush();
                    }
                } catch (GuzzleException $e) {
                    echo 'error getting with GuzzleException ' . $e->getMessage();
                    echo ob_get_clean();
                    flush();
                }
            }
            var_dump('finish updating database');
            echo "<script language=\"javascript\">";
            echo "window.location.href='" . "/myWeb/BaoXian/BaoXianLeads/leadsExport?starttime={$startTime}&endtime={$endTime}'";
            echo "</script>";
            exit;
        }

        if(empty($res))
        {
            response("No Record");
        }

        $count=count($res);

        for($i=0;$i<$count;$i++)
        {
            $tempJson=$res[$i]["testself"];

            $jsonObj=json_decode($tempJson);

            $res[$i]["name"]=$jsonObj->name;

            $res[$i]['jdaccount'] = urldecode($res[$i]['jdaccount']);

        }
        $excelData = $res;

        $arr=["标号","JDPin","手机","渠道","时间","测试数据","保险产品","姓名"];

        array_unshift($excelData, $arr);

        $this->excelExport($excelData,'产品列表数据' );

        response("Success Export",1,1);

    }

    /**
     * 尝试重新获取手机号
     */
    public function retryGetNophoneData() {
        $this->checkAuth();
        try {
            $startTime = I('get.starttime','');

            $endTime = I('get.endtime', '');

            if ($startTime && $endTime)
            {
                $where = "UNIX_TIMESTAMP(addtime) > " . strtotime($startTime) . ' AND UNIX_TIMESTAMP(addtime) <= ' . strtotime($endTime);
            }
            elseif ($startTime)
            {
                $where = "UNIX_TIMESTAMP(addtime) > " . strtotime($startTime);
            }
            elseif ($endTime)
            {
                $where = "UNIX_TIMESTAMP(addtime) < " . strtotime($endTime);
            }

            $where .= " AND phone = 'no phone'";

            $YuYue=M("baoxianyuyue");

            $res=$YuYue->where($where)->field("id,jdaccount,phone,fromby,addtime,testself,product")->select();

            $jdApiClass = new \Server\JdApi();

            set_time_limit(0);  //设置程序执行时间
            ignore_user_abort(true);    //设置断开连接继续执行
            header('X-Accel-Buffering: no');    //关闭buffer
            header('Content-type: text/html;charset=utf-8');    //设置网页编码
            ob_start(); //打开输出缓冲控制
            $success_count = 0;
            foreach ($res as $row) {
                $pin = $row['jdaccount'];
                $baseinfo = $jdApiClass->getJdUserBaseInfo($pin);
                $phone = $baseinfo['mobile'];
                if ($phone) {
                    $YuYue->where(['jdaccount' => $row['jdaccount']])->setField('phone', $phone);
                    $success_count ++;
                    echo 'update phone success<br>';
                    echo ob_get_clean();
                    flush();
                }
                else {
                    echo 'update phone fail<br>';
                    echo ob_get_clean();
                    flush();
                }
            }

            echo 'finish with success count: ' . $success_count;
            echo ob_get_clean();
            flush();
        } catch (Exception $e) {
            var_dump($e);
        }
    }

    private function excelExport($data=array(),$filename="保险Leads导出"){
        vendor("excel.PHPExcel");
        $objPHPExcel=new \PHPExcel();
        if(empty($data)){
            return;
        }
        $pColumnIndex = 0;

        foreach ($data as $key=>$row){
            $num=$key+1;

            foreach ($row as $row1){
                if($pColumnIndex<26){
                    $charIndex=chr(65+$pColumnIndex);
                }else if($pColumnIndex<702){
                    $charIndex=chr(64 + ($pColumnIndex / 26)) . chr(65 + $pColumnIndex % 26);
                }else{
                    $charIndex=chr(64 + (($pColumnIndex - 26) / 676)) . chr(65 + ((($pColumnIndex - 26) % 676) / 26)) . chr(65 + $pColumnIndex % 26);
                }
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue($charIndex.$num," ".$row1);
                $pColumnIndex++;
            }
            $pColumnIndex=0;

        }
        $filename=$filename."(".Date("Y-m-d").")".".xls";
        $this->formatExcel($objPHPExcel);
    }


    public function expert() {
        $this->display();
    }

    // 重命名测试数据
    public function renameTestAccount() {
        $this->checkAuth();
        $rand_num = rand(1, 99999999999);
        $pin = ['axer0910', 'axer0912'];

        $YuYue = M("baoxianyuyue");
        $res = $YuYue->where(['jdaccount' => ['in', $pin]])->select();
        var_dump($res);
        if (count($res) > 10) return;
        $data["jdaccount"]=$rand_num;

        $data["phone"]=$rand_num;

        $data["fromby"]='test';

        $data["testself"]= '{"userjdpin":"axer0910"}';

        $data["product"]='test';
        $YuYue->where(['jdaccount' => ['in', $pin]])->save($data);
        echo 'finish rename';

    }

    //https://www.dservie.cn/myWeb/index.php/BaoXian/login
    public function login()
    {
        $phone=$_POST["phone"];

        $code=$_POST["code"];

        $staffList = [
            '18019159738',
            '18018699131',
            '18521098192'
        ];

        // surprise action!!

        if (in_array($phone, $staffList) && strtolower($code) == 'dongrich2018') {
            session('adminuserid',999);
            session('username','Administrator');
            session('isadmin',1);
            response("Success ",1,"shrsList");
        } else {
            response("Failure No Staff");
        }
        $Staff=new StaffModel();

        $where=array();

        $where["phone"]=$phone;

        $where["code"]=md5Password($phone, $code);

        $rst=$Staff->where($where)->limit(1)->find();

        if(!$rst)
        {
            response("Failure No Staff");
        }
        else
        {

            if($rst["level"]==1)
            {
                session('adminuserid',$rst['id']);
                session('username',$rst['name']);
                session('isadmin',0);
                response("Success ",1,"expert");
            }
            else if($rst["level"]==2)
            {
                session('adminuserid',$rst['id']);
                session('username',$rst['name']);
                session('isadmin',1);
                response("Success",1,"admin");
            }
            else if($rst["level"]==0)
            {
                session('adminuserid',$rst['id']);
                session('username',$rst['name']);
                session('isadmin',0);
                response("Success",1,"staff");
            }
            else
            {
                response("Failure Level Wrong");
            }
        }




    }

    //https://www.dservie.cn/myWeb/index.php/BaoXian/BaoXianDoor/loginOut
    public  function loginOut(){
        //$this->success('退出成功',U('OperationLogin/work'));
        session('[destroy]');
        $this->redirect("/BaoXian/BaoXianLeads/index");
    }

    private function checkAuth() {
        if (!session('username')) {
            $this->redirect("/BaoXian/BaoXianLeads/index");
        }
    }
}