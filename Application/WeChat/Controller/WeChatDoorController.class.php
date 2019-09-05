<?php
namespace WeChat\Controller;
use Server\GJAES;
use Server\Scene;
use Server\WeChat;
use Think\Controller;
header("Content-Type: text/html;charset=utf-8");

/*
  微信服务号入口文件
  http://www.dservie.net/myWeb/index.php/WeChat/WeChatDoor/index

  包含微信菜单重建接口

  @author:leemanting created 2017/9/18

*/

class WeChatDoorController extends Controller
{
   /* const token="limanting";
    const encodingaeskey="kZeK34QGZoSWmO1tHP2xRAXjUBYV3nebbFNneZfaVEJ";
    const appid="wxdcd1cf281c9cae7d";
    const appsecret="a6a04c5df135fe3faae176fbf55ca465";*/
    public $token = '';
    public $encodingaeskey = '';
    public $appid = '';
    public $appsecret = '';

    /*所有具体的板块入口链接*/
    const testURL="http://www.baidu.com";


    public $option=array();

     /*微信公众号参数*/
    public $WeObj;


    public function __construct()
    {
        $this->token = C("TOKEN");
        $this->encodingaeskey = C("ENCODINGAESKEY");
        $this->appid = C("APPID");
        $this->appsecret = C("APPSECRET");
        $this->option=['token'=>$this->token,
        'encodingaeskey'=>$this->encodingaeskey,
        'appid'=>$this->appid,
        'appsecret'=>$this->appsecret];
        $this->WeObj=new \Org\Util\EasyWeChat($this->option);

        /*测试相关*/
    }

     /*微信服务号入口文件  http://www.dservie.net/myWeb/index.php/WeChat/WeChatDoor/index*/
    public function index()
     {
        //$this->WeObj=new \Org\Util\EasyWeChat($this->option);
        //明文或兼容模式可以在接口验证通过后注释此句，但加密模式一定不能注释，否则会验证失败
        $this->WeObj->valid();

        $type = $this->WeObj->getRev()->getRevType();
        $eventEvent = $this->WeObj->getRev()->getRevEvent();

         $eventType = $eventEvent['event'];

         $eventKey = $eventEvent['key'];

        $txtContent = $this->WeObj->getRev()->getRevContent();

        switch($type)
        {
            //文字消息
        	//文字消息
            case \Org\Util\EasyWeChat::MSGTYPE_TEXT:

                $openId=$this->WeObj->getRev()->getRevFrom();

               // $this->WeObj->text('汤哥最牛!')->reply();

                //$this->handelTxtMessage($txtContent,$openId);

                exit;

                break;

            //事件消息
            case \Org\Util\EasyWeChat::MSGTYPE_EVENT:

                switch($eventType)
                    {
                    //初次关注事件
                    case \Org\Util\EasyWeChat::EVENT_SUBSCRIBE:

                        $openId=$this->WeObj->getRev()->getRevFrom();
                        $wechatClass = new WeChat();
                        $wechatClass->handelFirstSubscribe($openId);
                       /* $userInfo=$this->WeObj->getUserInfo($openId);

                        $sceneid = $this->WeObj->getRev()->getRevSceneId();

                        $this->handelFirstSubscribe($userInfo, $sceneid);*/
                        break;

                    case \Org\Util\EasyWeChat::EVENT_LOCATION:

                        /****获取地理位置事件*****/

                        $openId = $this->WeObj->getRev()->getRevFrom();
                        //$this->WeObj->text("地理位置")->reply();

                        break;

                    case \Org\Util\EasyWeChat::EVENT_SCAN:
                        $sceneid = $this->WeObj->getRev()->getRevSceneId();
                        $this->handelScene($sceneid);
                        break;

                    case \Org\Util\EasyWeChat::EVENT_MENU_SCAN_PUSH:
                        $this->WeObj->text("scanpush__$type,$eventType")->reply();
                        break;

                    //click事件
                    case \Org\Util\EasyWeChat::EVENT_MENU_CLICK:
                        $this->handelEventClick($eventKey);
                        break;
                    //点击按钮事件


                    }
                    break;
            default:
            $this->WeObj->text("$type,$eventType")->reply();

        }//typeSwitch
     }//本方法最后一个

    /*初次关注事件------调试时候public #上线前private*/
    public function handelFirstSubscribe($userInfo, $sceneid)
    {
       /*入Redis*/

       /* $newsData = array(
            "0"=>array(
                'Title'=>'亲爱的'.$userInfo['nickname'].'欢迎关注东家会',
                'Description'=>'请完善信息 搜狐',
                'PicUrl'=>'',
                'Url'=>'http://www.sohu.com'),
            "1"=>array(
                'Title'=>'关于我们',//.$userInfo['nickname'],
                'Description'=>'猛戳这里',
                'PicUrl'=>'http://www.baidu.com',
                'Url'=>""
            ));
        if (!$sceneid) {
            $this->WeObj->news($newsData)->reply();
        } else {
            $this->handelScene($sceneid, $newsData);
        }*/



    }
    /*我的预约等可以显示推送*/

    public function handelScene($sceneid, $data = '')
    {
        if (!$sceneid) return false;
        $sceneClass = new Scene();
        $res = $sceneClass->getEventBySceneid($sceneid);
        $method = $res['method'];
        $class = $res['class'];
        $extrdata = $res['extrdata'];
        $uniqueid = $res['uniqueid'];
        if ($extrdata) {
            $extrdata = json_decode($extrdata,true);
            $extrdata = implode(",",$extrdata);
        }
        if (!$class) {

            call_user_func([$this,$method],$uniqueid, $data);

        } else {
            $newClass = new $class();
            call_user_func([$newClass,$method],$uniqueid, $data);

        }
    }

    /*文字消息进入*/
    public function handelTxtMessage($txtContent,$openId)
    {
      //   $this->WeObj->text($txtContent.'汤哥最牛')->reply();
    }

    /**
     * @breif  eventclick 根据key推送
     * @param $key
     * @return bool
     */
    public function handelEventClick($key)
    {
        if (!$key) return false;
        //达人志图文推送
        if ($key == 'magazine') {
            $this->WeObj->text("达人志图文推送,暂定")->reply();
        } else {

        }
    }

    /*语音消息进入*/
    public function handleVoiceMessage()
    {

    }

    /*临时签到菜单设置*/
    //http://www.dservie.net/myWeb/index.php/WeChat/WeChatDoor/makeMenu
    public function makeMenuZenGe()
    {
        $newMenuOneButtonArray=[
            "name"=>"活动签到",
            "type"=>"scancode_push",
            'key'=>'menu_01',
        ];

        $newMenu=["button"=>[$newMenuOneButtonArray]];
        $result = $this->WeObj->createMenu($newMenu);

        if($result)
        {
            echo "success";
        }
        else
        {
            echo "failure";
        }

    }//本方法最后一个



    /*原来的菜单设置*/
    //http://www.dservie.net/myWeb/index.php/WeChat/WeChatDoor/makeMenu
    public function makeMenu()
    {
        /*$newMenuOneButtonArray=array("name"=>"东家管家","sub_button"=>array
        (
            array("type"=>"view","name"=>"十八洞","url"=>$this->getMenuUrl('ShiBaDong')),
            array("type"=>"view","name"=>"东家严选","url"=>$this->getMenuUrl('YanXuan')),
            array("type"=>"view","name"=>"东家海外","url"=>$this->getMenuUrl('HaiWai')),
            array("type"=>"view","name"=>"少东家","url"=>$this->getMenuUrl('ShaoDongJia')),
            array("type"=>"view","name"=>"东家健康","url"=>$this->getMenuUrl('JianKang')),
        )
        );

        $newMenuTwoButtonArray=array("name"=>"东家尊享","sub_button"=>array
        (
            array("type"=>"view","name"=>"东家权益","url"=>$this->getMenuUrl('QuanYi')),
            array("type"=>"view","name"=>"东家公益","url"=>$this->getMenuUrl('GongYi')),
            array("type"=>"view","name"=>"热推","url"=>$this->getMenuUrl('ReTui')),
            array("type"=>"click","name"=>"达人志","key"=> 'magazine'),

        )
        );

        $newMenuThreeButtonArray=array("name"=>"东家会员","sub_button"=>array
        (
            array("type"=>"view","name"=>"个人中心","url"=>$this->getMenuUrl('GeRenZhongXin')),
            array("type"=>"view","name"=>"我的订单","url"=>$this->getMenuUrl('WoDeDingDan')),
            array("type"=>"view","name"=>"客服","url"=>$this->getMenuUrl('KeFu')),
        )
        );*/
        $newMenuOneButtonArray=array("type"=>"view","name"=>"管家服务","url"=>$this->getMenuUrl('WeChat/WeChatGuanJia/index'));
        $newMenuTwoButtonArray=array("type"=>"view","name"=>"我的","url"=>$this->getMenuUrl('WeChat/WeChatGuanJia/index'));
        $newMenuThreeButtonArray=array("name"=>"更多","sub_button"=>array
        (
            array("type"=>"view","name"=>"关于我们","url"=>$this->getMenuUrl('WeChat/WeChatGuanJia/index')),
            array("type"=>"view","name"=>"管家招募","url"=>$this->getMenuUrl('WeChat/WeChatGuanJia/index')),
            array("type"=>"view","name"=>"招贤纳士","url"=>$this->getMenuUrl('WeChat/WeChatGuanJia/index')),
        )
        );
        $newMenu=array("button"=>array($newMenuOneButtonArray,$newMenuTwoButtonArray,$newMenuThreeButtonArray));
        $result = $this->WeObj->createMenu($newMenu);

        if($result)
        {
            echo "success";
        }
        else
        {
            echo "failure";
        }

    }//本方法最后一个

    /*公共信息转客服 ---WS 还是第三方？*/
    private function transMessageToCustomerCare()
    {

    }

    public function getMenuUrl($CtrlName){
//        $prev = 'https://www.dservie.cn/myWeb/index.php/';
        $prev = 'https://www.dservie.cn/myWeb/';

        return $prev.$CtrlName;
    }//本方法最后一个

    //集赞推送
   public function jizanPush($guanjiaId, $data)
   {
       /*$AESClass = new GJAES();
       $param['guanjiaid'] = intval($guanjiaId);
       $str = json_encode($param);
       $code = $AESClass->aes_encrypt($str,C("AESPASSWORD"));
       $sceneData = array(
           'Title'=>'您好小旁友',
           'Description'=>'我是管家'.$guanjiaId.'号,请为我投一票',
           'PicUrl'=>'',
           'Url'=>$_SERVER['HTTP_HOST'].U('WeChatScene/jizan',['param'=>$code]));
       if ($data && is_array($data)) {
           //array_unshift($data, $sceneData);
           $data[0] = $sceneData;
       } else {
           unset($data);
           $data[0] = $sceneData;
       }
       $this->WeObj->news($data)->reply();*/
   }
}