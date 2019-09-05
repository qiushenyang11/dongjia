<?php
/**
* Created by PhpStorm.
* User: admin
* Date: 2017/11/23
* Time: 9:59
*/

namespace XCXApi\Controller;

use Common\Common\pinyinfirstchar;
use Think\Controller;
use Server\WeChatRedis;
use Server\Order;
use XCXApi\Model\LicaishiModel;


/***

小程序相关
redis-cli -h 101.124.73.24 -a woshini88

 */

class XCXController extends Controller
{
    private $appid="wxce6adf9479b7fc00"; // 小程序appId

    private $mchid = '1507498971'; // 小程序支付商户号

    private $sessionKey;

    private $appsecret="d675dc294627b74744670466a5988bd6";

    private $XCXURL="https://api.weixin.qq.com/sns/jscode2session?appid=";

    //小程序
    //https://www.dservie.cn/myWeb/index.php/XCXApi/XCX/index
    public function index()
    {
        echo "This is XCX";//
    }

    //https://www.dservie.cn/myWeb/index.php/XCXApi/XCX/checkParaIllegal
    public function checkParaIllegal() {
        $url = I('get.url', '');
        $url = htmlspecialchars_decode($url);
        $whiteList = ['dservie', 'jd', 'runtest', 'weixin'];
        $whiteSubList = ['www', 'djgj', 'djgjia', 'sale', 'mp'];
        $rule = '/^(([a-zA-Z]+)(:\/\/))?([a-zA-Z]+)\.(\w+)\.([\w.]+)(\/([\w]+)\/?)*(\/[a-zA-Z0-9]+\.(\w+))*(\/([\w]+)\/?)*(\?([\s\S]+=?[\s\S]*))*((&?[\s\S]+=?[\s\S]*))*$/';
        preg_match($rule, $url, $result);
        $mainDomain = $result[5];
        $subDomain = $result[4];
        if (count($result) && in_array($mainDomain, $whiteList) && in_array($subDomain, $whiteSubList)) {
            //初始化
            $curl = curl_init();
            //设置抓取的url
            curl_setopt($curl, CURLOPT_URL, $url);
            //设置头文件的信息作为数据流输出
            curl_setopt($curl, CURLOPT_HEADER, 0);
            //设置获取的信息以文件流的形式返回，而不是直接输出。
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            //执行命令
            $output = curl_exec($curl);
            $flag = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            //关闭URL请求
            curl_close($curl);
            if ($flag !== 200) {
                echo '<html><head><title>404 NotFound</title></head><body bgcolor="white"><center><h1>404 NotFound</h1></center><hr><center>cirno/9.9.9 (Touhou Project)</center></body></html>';
            } else {
                echo $output;
            }
        } else {
            echo '<html><head><title>403 Forbidden</title></head><body bgcolor="white"><center><h1>403 Forbidden</h1></center><hr><center>cirno/9.9.9 (Touhou Project)</center></body></html>';
        }
    }

    //code换取sessionkey
    //https://www.dservie.cn/myWeb/index.php/XCXApi/XCX/xcxGetSessionKey
    public function xcxGetSessionKey()
    {
        $code=I("post.code");

        if(!$code)
        {
            response("Failure Of Empty Code");
        }

        $back=$this->getSessionKey($code);

        $sessionKey=$back["session_key"];

        $Redis= new WeChatRedis();

        $Redis->addXCXSessionKey($sessionKey);

        if($back)
        {
            response("Success",1,$back);
        }
        else
        {
            response("Failure");
        }
    }

    public function getSessionKey($code)
    {
        $URL=$this->XCXURL.$this->appid."&secret=".$this->appsecret."&js_code=".$code."&grant_type=authorization_code";

        $rst=$this->doGet($URL);

        $obj=json_decode($rst);

        if($obj->errcode)
        {
            return false;
        }
        else
        {
            return json_decode($rst,ture);
        }

    }

    /**
     * 通过wx.login取得用户openId
     * @param $code
     */
    public function getOpenId() {
        $jscode = I("get.jscode");
        $URL=$this->XCXURL.$this->appid."&secret=".$this->appsecret."&js_code=".$jscode."&grant_type=authorization_code";

        $rst=$this->doGet($URL);

        $obj=json_decode($rst);

        if($obj->errcode)
        {
            response($obj->errcode);
        }
        else
        {
            response("Success",1, $obj);
        }
    }


    /**
     * 改圖片路徑
     */
    public function hideGuanjiaOption0925() {
//        $hide_list = [
//            'showguanjialist' => false,
//            'guanjiaid' => [
//                '18',  //推拿
//                '17',  //陪诊
//                '14',  //美孕
//                '2',  //移民
//                '5',  //齿科
//                '8',  //体检
//                '1',  //投资
//                '4',  //留学
//                '12',  //法律
//            ],
//            'lv2id' => [
//                '60',  //推拿
//                '36',  //体检
//                '30',  //留学
//            ]
//        ];
        $hide_list = [
            'showguanjialist' => true,
            'guanjiaid' => [],
            'lv2id' => []
        ];
        response('Success', 1, $hide_list);
    }

    /**
     * 改圖片路徑
     */
    public function hideGuanjiaOption1011() {
//        $hide_list = [
//            'showguanjialist' => false,
//            'guanjiaid' => [
//                '18',  //推拿
//                '17',  //陪诊
//                '14',  //美孕
//                '2',  //移民
//                '5',  //齿科
//                '8',  //体检
//                '1',  //投资
//                '4',  //留学
//                '12',  //法律
//            ],
//            'lv2id' => [
//                '60',  //推拿
//                '36',  //体检
//                '30',  //留学
//            ]
//        ];
        $hide_list = [
            'showguanjialist' => true,
            'guanjiaid' => [],
            'lv2id' => []
        ];
        response('Success', 1, $hide_list);
    }

    /**
     * 改圖片路徑
     */
    public function hideGuanjiaOption1026() {
//        $hide_list = [
//            'showguanjialist' => false,
//            'guanjiaid' => [
//                '18',  //推拿
//                '17',  //陪诊
//                '14',  //美孕
//                '2',  //移民
//                '5',  //齿科
//                '8',  //体检
//                '1',  //投资
//                '4',  //留学
//                '12',  //法律
//            ],
//            'lv2id' => [
//                '60',  //推拿
//                '36',  //体检
//                '30',  //留学
//            ]
//        ];
        $hide_list = [
            'showguanjialist' => true,
            'guanjiaid' => [],
            'lv2id' => []
        ];
        response('Success', 1, $hide_list);
    }

    public function doGet($url)
    {
        //初始化
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        return $output;
    }


    //添加一个小程序用户
    //https://www.dservie.cn/myWeb/index.php/XCXApi/XCX/addOneXCXUser
    public function addOneXCXUser()
    {
       $openid=I("post.openid");

       $XCXUser = M("xcxuser");

       $where = array();

       $where["openid"] = $openid;

       $rst = $XCXUser->where($where)->limit(1)->find();

       if($rst)
       {
           response("Old User");
       }
       else
       {

           $rstAdd=$XCXUser->add($where);

           if($rstAdd)
           {
               response("New User Add");
           }
           else
           {
               response("New User Cant Add");
           }
       }

    }


    //添加一个shareTicket,用户转发到群里生成一个shareTicket
    //https://www.dservie.cn/myWeb/index.php/XCXApi/XCX/addOneShareTicket
    public function addOneShareTicket()
    {

        $encryptedData=I("post.encryptedData");

        $jdaccount=I("post.jdaccount");

        if(!$jdaccount)
        {
            response("No JDAccount No Record");
        }

        $iv=I("post.iv");

        $errCode = $this->decryptData($encryptedData, $iv, $data );

        if ($errCode == 0)
        {
            //{"openGId":"GN59V49reaB9MOdwXNBrxtjbFEu0","watermark":{"timestamp":1528186851,"appid":"wxce6adf9479b7fc00"}}

            $openGId=$data->openGId;

            $dataS["groupid"]=$openGId;

            $dataS["jdaccount"]=$jdaccount;

            $Share=M("xcxshare");

            $rstCheck=$Share->where($dataS)->limit(1)->find();

            if($rstCheck)
            {
                response("This Group Has Been Taken");
            }
            else
            {
                $rst=$Share->add($data);

                if($rst)
                {
                    response("Success",1,$data);
                }
                else
                {
                    response("DataBase Busy");
                }
            }

        }
        else
        {
           response("Faliure");
        }

    }

    //根据 检查ShareTicket
    //https://www.dservie.cn/myWeb/index.php/XCXApi/XCX/checkShareTicket
    public function checkShareTicket()
    {
        $iv = I("post.iv");

        $encryptedData = I("post.encryptedData");

        $openid = I("post.openid");

        //$jdaccount=I("post.jdaccount");

        $XCXUser = M("xcxuser");

        $XCXUser->startTrans();

        $where = array();

        $where["openid"] = $openid;

        $rst = $XCXUser->where($where)->limit(1)->find();

        $new = false;

        if ($rst)
        {
            //已有用户
        }
        else
        {
            //新增用户
            $new = true;
        }

        $data = $this->mantingDecryptData($encryptedData, $iv);

        if($data)
        {
            $openGId=$data->openGId;

            $XCXShare=M("xcxshare");

            $whereXCXShare=array();

            $whereXCXShare["groupid"]=$openGId;

            $rstShare=$XCXShare->where($whereXCXShare)->limit(1)->find();

            if(!$rstShare)
            {
               response("Failure Not Find OpenGroupID");
            }
            if($new)
            {
               //新用户
                $dataXCXUser=array();

                $dataXCXUser["openid"]=$openid;

                $dataXCXUser["groupshare"]=1;

                $rstAdd=$XCXUser->add($dataXCXUser);

                $dataXCXShare=array();

                $dataXCXShare["count"]=$rstShare["count"]+1;

                $dataXCXShare["newcount"]=$rstShare["newcount"]+1;

                $rstShareAdd=$XCXShare->where($whereXCXShare)->save($dataXCXShare);

                if($rstShareAdd&&$rstAdd)
                {
                    $XCXUser->commit();

                    response("Success",1,$data);
                }
                else
                {
                    $XCXUser->rollback();

                    response("Failure DataBaseBusy");
                }

            }
            else
            {
                $dataXCXShare=array();

                $dataXCXShare["count"]=$rstShare["count"]+1;

                $rstShareAddNotNew=$XCXShare->where($whereXCXShare)->setInc("count");

                if($rstShareAddNotNew)
                {
                    $XCXUser->commit();

                    response("Success",1,$data);
                }
                else
                {
                    $XCXUser->rollback();

                    response("Failure DataBase Busy");
                }
            }

        }
        else
        {
            response("Failure To Get OpenGroupId");
        }

    }


    //拿到jdaccount以后的更新用户数据表
    //https://www.dservie.cn/myWeb/index.php/XCXApi/XCX/updateWithJDAccount
    public function updateWithJDAccount()
    {

    }


    /***************************理财师相关*********************************/







    /**
     * 小程序微信支付接口下单
     */
    public function pullPayOrder() {
        $ordersn = I('post.ordersn', '');
        $openid = I('post.openid', '');
        $userid = session('userid');

        $orderClass = new Order();
        $res = $orderClass->payOrderXCX($ordersn, $userid, $openid, $this->appid, $this->mchid);
        $res ? response('获取成功', 1, $res) : response('获取失败');
    }

    /**
     * 小程序检查订单状态
     */
    public function checkPayOrder() {
        $ordersn = I('get.ordersn', '');
        $orderClass = new Order();
        $orderClass->checkXCXPayOrder($ordersn, $this->appid, $this->mchid);
    }

    /**********************理财师相关*****************************/

    //https://www.dservie.cn/myWeb/index.php/XCXApi/XCX/checkLiCaiShi
    //判断是不是理财师
    public function checkLiCaiShi()
    {
        $jdaccount=I("post.jdaccount");

        $LiCaiShi=M("licaishi");

        $where=array();

        $where["jdaccount"]=$jdaccount;

        $where["status"]=1;

        $rst=$LiCaiShi->where($where)->limit(1)->find();

        if($rst)
        {
            response("IsLiCaiShi",1,true);
        }
        else
        {
            response("NotLiCaiShi",1,false);
        }

    }

    //https://www.dservie.cn/myWeb/index.php/XCXApi/XCX/getLiCaiShiClientList
    //获得该理财师下属的客户
    public function getLiCaiShiClientList()
    {
        $jdaccount=I("post.jdaccount");

        $licaishiModel = new LicaishiModel();

        $pinyin = new pinyinfirstchar();

        $kehu_list = $licaishiModel->getLicaishiKehu('oMdTzvkXjZhJrQXD4WQYPf-rw5QQ_test');

        $ordered_list = [];

        foreach ($kehu_list as $kehu) {
            $char = strtoupper($pinyin->getFirstchar($kehu['name']));
            $ordered_list[$char]['title'] = $char;
            $row = ['name' => $kehu['name'],
                    'avatar' => $kehu['avatar']
                ];
            $ordered_list[$char]['items'][] = $row;
        }

        ksort($ordered_list);
        $ordered_list = array_values($ordered_list);

        response('Success', 1, $ordered_list);

    }
    //https://www.dservie.cn/myWeb/index.php/XCXApi/XCX/getLiCaiShiServiceList
    //获得该理财师下属客户的单子
    public function getLiCaiShiServiceList()
    {

    }

    /**
     * redis保存一下理财师为客户填写的订单信息
     */
    public function addLicaishiSharedOrder()
    {
        // key加在理财师
        $Redis= new WeChatRedis();
        $jdaccount = session('jdaccount');
        $jsonOrderInfo = I('post.jsonorderinfo');
        $jsonOrderInfo = str_replace('&quot;', '"', $jsonOrderInfo);
        $token = $Redis->addLicaishiSharedOrderInfo($jdaccount, $jsonOrderInfo);
        response('Success', 1, $token);
    }

    /**
     * 客户获取理财师下单的信息
     */
    public function getLicaishiSharedOrder()
    {
        // todo 匹配客户的jdaccount
        $Redis= new WeChatRedis();
        $token = I('get.orderToken');
        $rs = $Redis->getLicaishiSharedOrderInfo($token);
        if (!$rs) {
            response('没有理财师下单信息');
        }
        response('Success', 1 ,$rs);
    }

    /**********************小程序公共方法**************************/
    private function mantingDecryptData($encryptedData,$iv)
    {
        if(!($this->sessionKey))
        {
            $Redis= new WeChatRedis();

            $this->sessionKey=$Redis->getXCXSessionKey();
        }

        if((strlen($this->sessionKey)!= 24))
        {
            return false;
        }

        $aesKey=base64_decode($this->sessionKey);


        if (strlen($iv) != 24)
        {
            return false;
        }
        $aesIV=base64_decode($iv);

        $aesCipher=base64_decode($encryptedData);

        $result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        $dataObj=json_decode( $result );

        if( $dataObj  == NULL )
        {
            return fasle;
        }
        if( $dataObj->watermark->appid != $this->appid )
        {
            return false;
        }

        $data = $result;

        return $data;
    }




    private function decryptData( $encryptedData, $iv, &$data )
    {
        if(!($this->sessionKey))
        {
            $Redis= new WeChatRedis();

            $this->sessionKey=$Redis->getXCXSessionKey();
        }

        if((strlen($this->sessionKey)!= 24))
        {
            return ErrorCode::$IllegalAesKey;
        }
        $aesKey=base64_decode($this->sessionKey);


        if (strlen($iv) != 24) {
            return ErrorCode::$IllegalIv;
        }
        $aesIV=base64_decode($iv);

        $aesCipher=base64_decode($encryptedData);

        $result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        $dataObj=json_decode( $result );
        if( $dataObj  == NULL )
        {
            return ErrorCode::$IllegalBuffer;
        }
        if( $dataObj->watermark->appid != $this->appid )
        {
            return ErrorCode::$IllegalBuffer;
        }
        $data = $result;
        return ErrorCode::$OK;
    }

}

class ErrorCode
{
    public static $OK = 0;
    public static $IllegalAesKey = -41001;
    public static $IllegalIv = -41002;
    public static $IllegalBuffer = -41003;
    public static $DecodeBase64Error = -41004;
}