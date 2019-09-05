<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/1/2
 * Time: 17:56
 *
 * 京东金融开放平台
 */

namespace Server;


class JDJROpen
{
    public $JDAppID;//商户号商户的唯一标识，不需要手动配置，申请开放平台商户号成功后自动分配

    public $JDAppKey;//

    public $JDScope;//接口权限范围值，不需要手动配置，根据商户当前所获得的权限范围进行展示，目前只支持base和white两个值

    public $JDRev;

    const JDJROauthUrl="https://open.jr.jd.com/oauth2/authorization/forward?appid=";

    const JDJRUserTokenUrl="https://open.jr.jd.com/oauth2/code/c2t?code=";

    const JDJRUserInfoBaseUrl="https://open.jr.jd.com/gw/generic/jimu/outside/m/getUserBaseInfo";

    const JDJRRefreshTokenUrl="https://open.jr.jd.com/oauth2/authorization/refresh_token?appid=";


    public function __construct($appId,$appKey,$scope)
    {
        if($appId&&$appKey)
        {
            $this->JDAppId=$appId;

            $this->JDAppKey=$appKey;

            $this->JDScope=$scope;
        }
    }

    //https://open.jr.jd.com/oauth2/authorization/forward?appid=APPID&redirect_uri=REDIRECT_URI&scope=SCOPE&state=STATE
    public function getJDJRCodeUrl($url)
    {
         $redirectUrl=urlencode($url);

         $aim=self::JDJROauthUrl.$this->JDAppId."&redirect_uri=".$redirectUrl."&scope=".$this->JDScope."&state=STATE";

         return $aim;

    }

    public function fromCodeToGetUserToken($code)
    {
        $aim=self::JDJRUserTokenUrl.$code;

        $result=$this->curlGet($aim);

        /*正确时返回的JSON数据包如下：

         "result_code":0,
         "result_msg":”Success”,
         "signature":"",
         "result_data":
	         "user_token":"ACCESS_TOKEN",
   	         "timeout":7200,
   	         "refresh_token":"REFRESH_TOKEN",
   	         "openId":"OPENID"
        */

        $resultCode=$result->result_code;

        if($resultCode==0)
        {
             $this->JDRev=$result->result_data;

             return $this->JDRev->user_token;
        }
        else
        {
             return false;
        }

    }

    public function fromCodetoRefreshToken($code)
    {

    }



    public function getJDUserInfoBase($jdJROpenId)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, self::getJDUserInfoBase);//设置头文件的信息作为数据流输出

        curl_setopt($curl, CURLOPT_HEADER, 1);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($curl, CURLOPT_POST, 1);//设置post数据

        $post_data = array();

        $post_data["token"]=$this->JDRev->user_token;

        $post_data["openid"]=$jdJROpenId;

        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);

        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        $obj=json_decode($data);

        if($obj->resultCode==0)
        {
            return $obj->resultData;
        }
        else
        {
            return false;
        }

    }



    private function curlGet($url,$headers = '')
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_HEADER, false);

        if (count($headers))
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $output = curl_exec($ch);

        curl_close($ch);

        return json_decode($output, true);
    }



}