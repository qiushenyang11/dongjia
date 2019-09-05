<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/9/29
 * Time: 10:11
 */

namespace Server;


class TencentOnLiveSDK
{
    /*API鉴权key*/
    const TencentOnLiveAPIKey="1531de0e84791fd3ca5f85ebb4f8fbb5";
    /*防盗链流推Key*/
    const TencentOnLiveSecurityKey="fe8805f733b9ff10795cac72c27886f0";

    const TencentAppId="1254468639";

    const TencentBizid="11574";

    const TencentAPIURL="http://fcgi.video.qcloud.com/common_access";

    /*安全机制

    由于对 API 的调用采用的是普通的 HTTP 协议（出于性能考虑），这就需要一套行之有效的办法来确保您的服务器
    与腾讯云后台之间的通讯安全。所有直播码相关的云端 API 都采用了同一种安全检查机制， t + sign 校验：
    t（过期时间）：如果一个API请求或者通知中的 t 值所规定的时间已经过期，则可以判定这个请求或者通知为无效的，
    这样做可以防止网络重放攻击。t 的格式为UNIX时间戳，即从1970年1月1日（UTC/GMT的午夜）开始所经过的秒数。
    sign（安全签名）: sign = MD5(key + t) ，即把加密key 和 t 进行字符串拼接后，计算一下md5值。
    这里的key即CGI调用key，您在腾讯云直播管理控制台 中可以进行设置：*/

    public function __construct()
    {
        echo "OnLive SDK has been construct";
    }



    static public function onLiveSign($t)
    {
        $str=md5(self::TencentOnLiveSecurityKey.$t);

        return $str;
    }
    /*
     * 判断签名是否正确
     * */

    public function checkSignValidate($eTime,$sign)
    {
        $str=$this::onLiveSign($eTime);

        if($str===$sign)
        {
            return true;
        }
        else
        {
            return false;
        }
    }



    /*
     * 获取推流地址
     *@param bizId 您在腾讯云分配到的bizid
     * time 过期时间 自动当时时间+86400
     *
     * 返回流推地址
     * */

    public function getPushUrl($streamId,$timeNow)
    {


            $txTime = $timeNow+86400;

            $livecode = self::TencentBizid."_".$streamId; //直播码

            $txSecret = md5(self::TencentOnLiveSecurityKey.$livecode.$txTime);

            $ext_str = "?".http_build_query(array(
                    "bizid"=> self::TencentBizid,
                    "txSecret"=> $txSecret,
                    "txTime"=> $txTime
                ));

        return "rtmp://".self::TencentBizid.".livepush.myqcloud.com/live/".$livecode.(isset($ext_str) ? $ext_str : "");

    }
    /*
     * 返回播放地址
     *
     *
     *
     * return 数组形式的播放地址
     *
     * 0：RTMP 1:FLV 2:HLS
     */

    public function getPlayUrl($streamId)
    {
        $livecode = self::TencentBizid."_".$streamId; //直播码
        return array(
            "rtmp://".self::TencentBizid.".liveplay.myqcloud.com/live/".$livecode,
            "http://".self::TencentBizid.".liveplay.myqcloud.com/live/".$livecode.".flv",
            "http://".self::TencentBizid.".liveplay.myqcloud.com/live/".$livecode.".m3u8"
        );
    }

    /*获得一个直播流推的状态*/

    public function getStatusOfLiveChannel($streamId,$safeTime)
    {
        $URL=self::TencentAPIURL."?appid=".
             self::TencentAppId."&interface=Live_Channel_GetStatus&&Param.s.channel_id=".
             self::TencentAppId."_".$streamId."&sign=".$this::onLiveSign($safeTime);

        $rst=$this->httpGet($URL);

     /*   ret	   返回码	   int	0:成功；其他值:失败
       message	  错误信息	 string	  错误信息
        output	 消息内容	     array	详情见下
        output的主要内容为：

      字段名	含义	            类型	               备注
     rate_type	码率	int	0:原始码率；10:普清；20:高清
     recv_type	播放协议	int	1:rtmp/flv；2:hls；3:rtmp/flv+hls
     status	状态	int	0:断流；1:开启；3:关闭
     */

        return $rst;
    }

    /*查询直播统计*/






    private function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }
}