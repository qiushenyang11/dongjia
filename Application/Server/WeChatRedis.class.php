<?php
namespace Server;

use Redis;

class WeChatRedis
{
    const FIRSTCREDIT=200;

    /****
     *
     * db0==
     *
     * db1==
     *
     * db2==
     *
     * db3==
     *
     * db4==
     *
     * db5==
     *
     * db6==
     *
     * db7==
     *
     * db8==给管家token验证用，其他的不要进来
     *
     * db9==短信
     *
     * ****/

    public function __construct()
    {
        $this->r =new Redis();
        if (APPENV == 'production')  {
            $url = 'r2m-proxy.jdfin.local';
            $port = 6379;
            $this->r->connect($url, $port);
            $this->r->auth('dongjia');
        } elseif (APPENV == 'present') {
            $url = '172.25.44.101';
            $port = 6379;
            $this->r->connect($url, $port);
            $this->r->auth('dongjia');
        } else {
            $url = '127.0.0.1';
            $port = 6379;
            $this->r->connect($url, $port);
        }

    }

    /*
     * 记录理财师替客户下单的信息
     */
    public function addLicaishiSharedOrderInfo($jdaccount, $jsonOrderInfo)
    {
        $key = md5(time() . $jdaccount);
        $this->r->set($key, $jsonOrderInfo);
        return $key;
    }

    /*
     * 获取理财师替客户下单的信息
     */
    public function getLicaishiSharedOrderInfo($token)
    {
        $jsonData = $this->r->get($token);
        if (!$jsonData) return false;
        return $jsonData;
    }

    public function addXCXSessionKey($value)
    {
        $key="XCXSeeionKey";

        $this->r->set($key,$value);
    }

    public function getXCXSessionKey()
    {
        $key="XCXSeeionKey";

        return $this->r->get($key);

    }


    public function addSmsCode($phone, $code)
    {
        //验证码存储
        $key  = "SmsCode".$phone;
        //当天发送数目
        $key1 = Date("Ymd").'SmsCount'.$phone;
        $count = $this->r->get($key1);
        if ($count) {
            $count ++;
        } else {
            $count = 1;
        }
        $info = [
            'code' => $code,
            'expiretime' =>time()+C("EXPIRETIME")*60
        ];
        $res1 = $this->r->hMset($key, $info);
        $this->r->set($key1, $count);
        $difftime = strtotime(date("Y-m-d",strtotime("+1 day")))-time();
        $this->r->expire($key1,$difftime);
        $this->r->expire($key,3600);
        return $res1 !== false ? true : false;
    }

    public function getSmsCodeinfo($phone)
    {
        $key  = "SmsCode".$phone;
        $key1 = Date("Ymd").'SmsCount'.$phone;
        $arr = $this->r->hGetAll($key);
        $count = $this->r->get($key1);
        if (!$count) $count = 0;
        $arr['count'] = $count;
        return $arr;
    }

    public function delSmCodeInfo($phone)
    {
        $key  = "SmsCode".$phone;
        $key1 = Date("Ymd").'SmsCount'.$phone;
        $this->r->del($key);
        $this->r->del($key1);
        return true;
    }

    public function getAccessTokenData(){}
    public function addWeChatAccessToken(){}
    public function addWeChatJsApiTicket(){}
    public function getJsApiTicketData(){}

    public function addSpecid($specid)
    {
        $key = 'updateSpecid'.$specid;
        return $this->r->set($key, 1,5);
    }
    public function delSpecid($specid)
    {
        $key = 'updateSpecid'.$specid;
        return $this->r->del($key);
    }

    public function getSpecid($specid)
    {
        $key = 'updateSpecid'.$specid;
        return $this->r->get($key);
    }

    public function addSubmitToken($jdaccount)
    {
        $key = md5(time().$jdaccount);
        $res = $this->r->setex($key,15*60,1);
        if ($res) {
            return $key;
        } else {
            return false;
        }
    }

    public function checkSubmitToken($token)
    {
        if ($this->r->get($token) == 1) {
            $this->r->del($token);
            return true;
        } else {
            return false;
        }
    }


    //已生成的服务码
    public function addServiceCode($code)
    {
        if (!$code) return false;
        $key = 'serviceCode';
        return $this->r->sAdd($key, $code);
    }

    public function setOrderKey($key, $price)
    {
        $time = 3601;
        return $this->r->setex($key, $time, $price);
    }

    public function delOrderkey($key)
    {
        return $this->r->del($key);
    }


    public function setLock($key, $time, $data)
    {
        $res1 = $this->r->getSet($key, $data);
        $res2 = $this->r->expire($key, $time);
        if ($res1 != $data && $res2) return true;
        return false;
    }

    public function delLock($key)
    {
        return $this->r->delete($key);
    }


    public function getOrderkey($key)
    {
        return $this->r->get($key);
    }

    public function orderLock($key)
    {
        $res = $this->r->getSet($key, 1);
        $this->r->expire($key,12*3600);
        return $res;
    }

    public function orderRelase($key)
    {
        return $this->r->del($key);
    }

    public function setUserOrderHistory($userid, $address, $phone)
    {
        $key = 'serverWriteAddress'.$userid;
        $res = $this->r->setex($key,3600*24*30, $address.','.$phone);
        return $res;
    }

    public function getUserOrderHistory($userid)
    {
        $key = 'serverWriteAddress'.$userid;
        $res = $this->r->get($key);
        return $res;
    }

    public function createOrdersn($ordersn)
    {
        $res = $this->r->getSet($ordersn, 1);
        $this->r->expire($ordersn, 10);
        return $res;
    }


    /*
     * 微信用户数据，埋点
     * VanillaChocola
     *
     */
    public function countVisit($name, $path, $jdaccount, $type)
    {
        $this->r->sadd("allPath",$path);

        $this->r->sadd("allType",$type);

        if($type == 'direct')
        {
            $key = 'direct,'.$path;
            if(!$this->r->exists($key)) {
                if($jdaccount)
                {
                    $res = $this->r->setex($key,3600*24*30,'1,1');
                    return 'SUCCESS1';
                }
                else{
                    $res = $this->r->setex($key,3600*24*30,'1,0');
                    return 'SUCCESS2';
                }
            }
            else{
                $count = $this->r->get($key);
                $counts = explode(',',$count);
                if($jdaccount)
                {
                    $res = $this->r->setex($key,3600*24*30,($counts[0] + 1).','.($counts[1] + 1));
                    return 'SUCCESS3';
                }
                else{
                    $res = $this->r->setex($key,3600*24*30,($counts[0] + 1).','.$counts[1]);
                    return 'SUCCESS4';
                }
            }
        }
        else
        {
            $key = $type;
            if(!$this->r->exists($key)) {
                $res = $this->r->setex($key,3600*24*30,1);
                return 'SUCCESS5';
            }
            else{
                $count = $this->r->get($key);
                $res = $this->r->setex($key,3600*24*30,$count + 1);
                return 'SUCCESS6';
            }
        }
    }

    /*
     * 微信用户页面热点记录
     * VanillaChocola
     *
     */
    public function logClickPoint($path, $point){

        $this->r->sadd("allClickPointPath",$path);
        $res = $this->r->lPush('Points,'.$path, $point['X'].','.$point['Y']);
        if($res)
            return "SUCCESS POINT";
        else
            return "FAIL POINT";
    }

    /*
     * 微信用户页面停留时间记录
     * VanillaChocola
     *
     */
    public function logWaitTime($path, $waitTime){

        $this->r->sadd("allWaitTimePath",$path);
        $res = $this->r->lPush('WaitTimes,'.$path, $waitTime);
        if($res)
            return "SUCCESS TIME";
        else
            return "FAIL TIME";
    }

    /*
     * 活动页面数据记录 已弃用
     * VanillaChocola
     *
     */
    public function logEventData($name, $path, $type){
        $key = 'Event,'.$name.',Pv';

        if(!$this->r->exists($key))
        {
            $this->r->setex($key, 3600*24*30, 1);
        }

        $pv = $this->r->get($key);
        $this->r->set($key, $pv+1);

        $tmp = explode(',', $path);
        //若有手机号
        if(count($tmp) == 2)
        {
            $agid = $tmp[0];
            $phone = $tmp[1];
            $sKey = 'Event,'.$name.',Uv,'.$agid;

            $this->r->sadd('Event,'.$name.',allAgid',$agid);
            $this->r->sAdd($sKey,$phone);
        }
        return 'event';
    }

    public function logChannelData($channelId, $sgid, $scenceId, $status)
    {
        $keyChannel="Channel".$channelId;
        $keySgid="Show".$sgid;
        $keyScenceId="Scence".$scenceId;

        if ((int)$status === 0)//刚进页
        {
            if($channelId!=="NO")
            {
                if(!$this->r->exists($keyChannel))
                {

                    $this->r->sadd("allKeyChannel",$keyChannel);

                    $this->r->hset($keyChannel, "PV", 1);

                    if(session("jdaccount"))
                    {
                        $this->r->hset($keyChannel, "UV", 1);
                    }

                    $this->r->hSet($keyChannel,"Order",0);

                    $this->r->hSet($keyChannel,"Free",0);

                }
                else
                {
                    $this->r->hIncrBy($keyChannel,"PV",1);

                    if(session("jdaccount"))
                    {
                        $this->r->hIncrBy($keyChannel, "UV", 1);
                    }
                }
            }

            if($sgid!=="NO")
            {
                if(!$this->r->exists($keySgid))
                {
                    $this->r->sadd("allKeySgid",$keySgid);

                    $this->r->hset($keySgid, "PV", 1);

                    if(session("jdaccount"))
                    {
                        $this->r->hset($keySgid, "UV", 1);
                    }


                    $this->r->hSet($keySgid,"Order",0);

                    $this->r->hSet($keySgid,"Free",0);
                }
                else
                {
                    $this->r->hIncrBy($keySgid,"PV",1);

                    if(session("jdaccount"))
                    {
                        $this->r->hIncrBy($keySgid, "UV", 1);
                    }

                }
            }

            if($scenceId!=="NO")
            {
                if(!$this->r->exists($keyScenceId))
                {
                    $this->r->sadd("allKeyScenceId",$keyScenceId);

                    $this->r->hset($keyScenceId, "PV", 1);

                    if(session("jdaccount"))
                    {
                        $this->r->hset($keyScenceId, "UV", 1);
                    }


                    $this->r->hSet($keyScenceId,"Order",0);

                    $this->r->hSet($keyScenceId,"Free",0);
                }
                else
                {
                    $this->r->hIncrBy($keyScenceId,"PV",1);

                    if(session("jdaccount"))
                    {
                        $this->r->hIncrBy($keyScenceId, "UV", 1);
                    }

                }
            }
        }
        else if((int)$status === 8)//免费成功
        {

            if($channelId!=="NO")
            {
                $this->r->hIncrBy($keyChannel,"Free",1);

                $this->r->hIncrBy($keyChannel,"UV",1);

                $this->r->hIncrBy($keyChannel,"PV",1);

            }

            if($sgid!=="NO")
            {
                $this->r->hIncrBy($keySgid,"Free",1);

                $this->r->hIncrBy($keySgid,"UV",1);

                $this->r->hIncrBy($keyChannel,"PV",1);
            }

            if($scenceId!=="NO")
            {
                $this->r->hIncrBy($keyScenceId,"Free",1);

                $this->r->hIncrBy($keyScenceId,"UV",1);

                $this->r->hIncrBy($keyChannel,"PV",1);
            }

        }
        else if((int)$status === 9)//支付成功
        {

            if($channelId!=="NO")
            {
                $this->r->hIncrBy($keyChannel,"Order",1);

                $this->r->hIncrBy($keyChannel,"UV",1);

                $this->r->hIncrBy($keyChannel,"PV",1);
            }

            if($sgid!=="NO")
            {
                $this->r->hIncrBy($keySgid,"Order",1);

                $this->r->hIncrBy($keySgid,"UV",1);

                $this->r->hIncrBy($keyChannel,"PV",1);
            }

            if($scenceId!=="NO")
            {
                $this->r->hIncrBy($keyScenceId,"Order",1);

                $this->r->hIncrBy($keyScenceId,"UV",1);

                $this->r->hIncrBy($keyChannel,"PV",1);
            }

        }
        return "channel";
    }

    //每天Pv,Uv定时统计
    public function dailyPvUvStatistics()
    {
        //$this->r->select(7);

        $pathArray=$this->r->sMembers("allPath");

        $count = count($pathArray);

        $pvCount=0;

        $uvCount=0;

        $dailyPvUvKey = "#PvUv,".date("Y-m-d",strtotime("-1 day"));

        for($j = 0; $j < $count; $j++)
        {
            $key = 'direct,'.$pathArray[$j];

            $res=$this->r->get($key);

            if ($res)
            {
                $sArray=explode(",",$res);

                // 累加
                $pvCount=(int)$pvCount+$sArray[0];

                $uvCount=(int)$uvCount+$sArray[1];

                // 放进一张哈希表
                $data=array();

                $data["Pv"]=$sArray[0];

                $data["Uv"]=$sArray[1];

                $this->r->hset($dailyPvUvKey,$pathArray[$j],json_encode($data));

                //重置数据
                $this->r->setex($key,3600*24*30,'0,0');
            }

        }

        $data=array();

        $data["Pv"]=$pvCount;

        $data["Uv"]=$uvCount;

        $this->r->hset($dailyPvUvKey, 'totalSum', json_encode($data));

        $this->r->expire($dailyPvUvKey, 3600*24*35);

        $this->deleteZeroPvUv($dailyPvUvKey);

        //每周一统计上周数据
        if($this->checkIfYesterdayEndOfWeek())
        {
            $week = date('W', strtotime("-1 day"));

            $wKey = "#PvUv,".date("Y").","."week".",".$week;

            $weekData = $this->getOneWeekData(1, 'PvUv');

            $beginDate = date("Y-m-d",strtotime('-7 day'));

            $endDate = date("Y-m-d",strtotime('-1 day'));

            // 将所有key汇总到一起，去重，生成type的并集
            $mergeArray = array_keys($weekData[0]);

            for($i = 1; $i < 7; $i++)
            {
                $mergeArray = array_merge($mergeArray, array_keys($weekData[$i]));
            }

            $weekPathArray = $this->deleteRepeat($mergeArray);

            // 在每一天的数据中搜索pathArray，并累加
            $weekSum = array();

            for($i = 0; $i < count($weekPathArray); $i++)
            {
                $currentPath = $weekPathArray[$i];

                $sumPv = 0;

                $sumUv = 0;

                for($j = 0; $j < 7; $j++)
                {
                    $currentData = $weekData[$j];

                    if(array_key_exists($currentPath,$currentData))
                    {
                        $pvUv = json_decode($currentData[$currentPath],true);

                        $sumPv = $sumPv + $pvUv['Pv'];

                        $sumUv = $sumUv + $pvUv['Uv'];
                    }
                }
                $tmp = array();

                $tmp["Pv"] = $sumPv;

                $tmp["Uv"] = $sumUv;

                $weekSum[$currentPath] = json_encode($tmp);

            }

            $weekSum['beginDate'] = $beginDate;

            $weekSum['endDate'] = $endDate;

            $this->r->hMset($wKey, $weekSum);
        }

        if($this->checkIfYesterdayEndOfMonth())
        {
            $month=date('m',strtotime("-1 day"));

            $mKey="#PvUv,".date("Y").","."month".",".$month;

            $beginDate=date('Y-m-01', strtotime("-1 day"));

            $endDate=date('Y-m-d', strtotime($beginDate." +1 month -1 day"));

            $dayCount=date("t");

            $monthData = $this->getOneMonthData($dayCount,"PvUv");

            // 将所有key汇总到一起，去重，生成path的并集
            $mergeArray = array_keys($monthData[0]);

            for($i = 1; $i < $dayCount; $i++)
            {
                $mergeArray = array_merge($mergeArray, array_keys($monthData[$i]));
            }

            $monthPathArray = $this->deleteRepeat($mergeArray);

            // 在每一天的数据中搜索pathArray，并累加
            $monthSum = array();

            for($i = 0; $i < count($monthPathArray); $i++)
            {
                $currentPath = $monthPathArray[$i];

                $sumPv = 0;

                $sumUv = 0;

                for($j = 0; $j < $dayCount; $j++)
                {
                    $currentData = $monthData[$j];

                    if(array_key_exists($currentPath,$currentData))
                    {
                        $pvUv = json_decode($currentData[$currentPath], true);

                        $sumPv = $sumPv + $pvUv['Pv'];

                        $sumUv = $sumUv + $pvUv['Uv'];
                    }
                }
                $tmp = array();

                $tmp["Pv"] = $sumPv;

                $tmp["Uv"] = $sumUv;

                $monthSum[$currentPath] = json_encode($tmp);

            }
            $monthSum['beginDate'] = $beginDate;

            $monthSum['endDate'] = $endDate;

            $this->r->hMset($mKey, $monthSum);
        }
    }

    //每天Click定时统计
    public function dailyClickStatistics()
    {
        //$this->r->select(7);

        // 拿到type set
        $typeArray=$this->r->sMembers("allType");

        $count = count($typeArray);

        $dailyClickKey = "#Click,".date("Y-m-d",strtotime("-1 day"));

        for($i = 0; $i < $count; $i++)
        {
            $key = $typeArray[$i];

            $click = $this->r->get($key);

            $types = explode(',', $typeArray[$i]);

            // 判断是否是click埋点
            if (count($types) == 3)
            {
                $pathFrom = $types[0];

                $trackType = $types[1];

                $pathTo = $types[2];

                // 放进一张哈希表
                $data=array();

                $data["pathFrom"] = $pathFrom;

                $data["trackType"]= $trackType;

                $data["pathTo"]= $pathTo;

                $data["click"]= $click;

                $this->r->hset($dailyClickKey,$key,json_encode($data));

                $this->r->expire($dailyClickKey, 3600*24*35);

                //重置数据
                $this->r->setex($key,3600*24*30,'0');
            }
        }

        $this->deleteZeroClick($dailyClickKey);

        //每周一统计上周数据
        if($this->checkIfYesterdayEndOfWeek())
        {
            $week = date('W', strtotime("-1 day"));

            $wKey = "#Click,".date("Y").","."week".",".$week;

            $weekData = $this->getOneWeekData(1, 'Click');

            $beginDate = date("Y-m-d",strtotime('-7 day'));

            $endDate = date("Y-m-d",strtotime('-1 day'));

            // 将所有key汇总到一起，去重，生成type的并集
            $mergeArray = array_keys($weekData[0]);

            for($i = 1; $i < 7; $i++)
            {
                $mergeArray = array_merge($mergeArray, array_keys($weekData[$i]));
            }

            $weekTypeArray = $this->deleteRepeat($mergeArray);

            // 在每一天的数据中搜索pathArray，并累加
            $weekSum = array();

            for($i = 0; $i < count($weekTypeArray); $i++)
            {
                $currentType = $weekTypeArray[$i];

                $pathFrom = '';

                $trackType = '';

                $pathTo = '';

                $sumClick = 0;

                for($j = 0; $j < 7; $j++)
                {
                    $currentData = $weekData[$j];

                    if(array_key_exists($currentType,$currentData))
                    {
                        $data = json_decode($currentData[$currentType], true);

                        $pathFrom = $data["pathFrom"];

                        $trackType = $data["trackType"];

                        $pathTo = $data["pathTo"];

                        $sumClick = $sumClick + $data["click"];
                    }
                }
                $tmp = array();

                $tmp["pathFrom"] = $pathFrom;

                $tmp["trackType"]= $trackType;

                $tmp["pathTo"]= $pathTo;

                $tmp["click"]= $sumClick;

                $weekSum[$currentType] = json_encode($tmp);
            }

            $weekSum['beginDate'] = $beginDate;

            $weekSum['endDate'] = $endDate;

            $this->r->hMset($wKey, $weekSum);
        }

        if($this->checkIfYesterdayEndOfMonth())
        {
            $month=date('m',strtotime("-1 day"));

            $mKey="#Click,".date("Y").","."month".",".$month;

            $beginDate=date('Y-m-01', strtotime("-1 day"));

            $endDate=date('Y-m-d', strtotime($beginDate." +1 month -1 day"));

            $dayCount=date("t");

            $monthData = $this->getOneMonthData($dayCount,"Click");

            // 将所有key汇总到一起，去重，生成path的并集
            $mergeArray = array_keys($monthData[0]);

            for($i = 1; $i < $dayCount; $i++)
            {
                $mergeArray = array_merge($mergeArray, array_keys($monthData[$i]));
            }

            $monthTypeArray = $this->deleteRepeat($mergeArray);

            // 在每一天的数据中搜索pathArray，并累加
            $monthSum = array();

            for($i = 0; $i < count($monthTypeArray); $i++)
            {
                $currentType = $monthTypeArray[$i];

                $pathFrom = '';

                $trackType = '';

                $pathTo = '';

                $sumClick = 0;

                for($j = 0; $j < $dayCount; $j++)
                {
                    $currentData = $monthData[$j];

                    if(array_key_exists($currentType,$currentData))
                    {
                        $data = json_decode($currentData[$currentType], true);

                        $pathFrom = $data["pathFrom"];

                        $trackType = $data["trackType"];

                        $pathTo = $data["pathTo"];

                        $sumClick = $sumClick + $data["click"];
                    }
                }

                $tmp = array();

                $tmp["pathFrom"] = $pathFrom;

                $tmp["trackType"]= $trackType;

                $tmp["pathTo"]= $pathTo;

                $tmp["click"]= $sumClick;

                $monthSum[$currentType] = json_encode($tmp);

            }
            $monthSum['beginDate'] = $beginDate;

            $monthSum['endDate'] = $endDate;

            $this->r->hMset($mKey, $monthSum);
        }

    }

    //每天页面停留时间统计
    public function dailyWaitTimeStatistics()
    {

        //$this->r->select(7);

        // 拿到type set
        $pathArray=$this->r->sMembers("allWaitTimePath");

        $count = count($pathArray);

        $dailyClickKey = "#WaitTime,".date("Y-m-d",strtotime("-1 day"));

        for($i = 0; $i < $count; $i++)
        {
            $key = 'WaitTimes,'.$pathArray[$i];

            $path = $pathArray[$i];

            $length = $this->r->lLen($key);

            $waitTimes = $this->r->lRange($key, 0, $length);

            $sum = 0;

            for($j = 0; $j < count($waitTimes); $j++)
            {
                $sum = $sum + $waitTimes[$j];
            }

            $average = $sum / count($waitTimes);

            // 放进一张哈希表
            $data=array();

            $data["path"] = $path;

            $data["waitTime"]= $average;

            $this->r->hset($dailyClickKey,$path,json_encode($data));

            $this->r->expire($dailyClickKey, 3600*24*35);

            //重置数据
            $this->r->lTrim($key, 1,0);
        }

        $this->deleteZeroWaitTime($dailyClickKey);

        //每周一统计上周数据
        if($this->checkIfYesterdayEndOfWeek())
        {
            $week = date('W', strtotime("-1 day"));

            $wKey = "#WaitTime,".date("Y").","."week".",".$week;

            $weekData = $this->getOneWeekData(1, 'WaitTime');

            $beginDate = date("Y-m-d",strtotime('-7 day'));

            $endDate = date("Y-m-d",strtotime('-1 day'));

            // 将所有key汇总到一起，去重，生成type的并集
            $mergeArray = array_keys($weekData[0]);

            for($i = 1; $i < 7; $i++)
            {
                $mergeArray = array_merge($mergeArray, array_keys($weekData[$i]));
            }

            $weekPathArray = $this->deleteRepeat($mergeArray);

            // 在每一天的数据中搜索pathArray，并累加
            $weekSum = array();

            for($i = 0; $i < count($weekPathArray); $i++)
            {
                $currentPath = $weekPathArray[$i];

                $sum1 = 0;

                $counter = 0;

                for($j = 0; $j < 7; $j++)
                {
                    $currentData = $weekData[$j];

                    if(array_key_exists($currentPath,$currentData))
                    {
                        $data = json_decode($currentData[$currentPath],true);

                        $sum1 = $sum1 + $data['waitTime'];

                        $counter++;
                    }
                }

                $average1 = $sum1 / $counter;

                $tmp = array();

                $tmp["path"] = $currentPath;

                $tmp["waitTime"] = $average1;

                $weekSum[$currentPath] = json_encode($tmp);

            }

            $weekSum['beginDate'] = $beginDate;

            $weekSum['endDate'] = $endDate;

            $this->r->hMset($wKey, $weekSum);
        }

        if($this->checkIfYesterdayEndOfMonth())
        {
            $month=date('m',strtotime("-1 day"));

            $mKey="#WaitTime,".date("Y").","."month".",".$month;

            $beginDate=date('Y-m-01', strtotime("-1 day"));

            $endDate=date('Y-m-d', strtotime($beginDate." +1 month -1 day"));

            $dayCount=date("t");

            $monthData = $this->getOneMonthData($dayCount,"WaitTime");

            // 将所有key汇总到一起，去重，生成path的并集
            $mergeArray = array_keys($monthData[0]);

            for($i = 1; $i < $dayCount; $i++)
            {
                $mergeArray = array_merge($mergeArray, array_keys($monthData[$i]));
            }

            $monthPathArray = $this->deleteRepeat($mergeArray);

            // 在每一天的数据中搜索pathArray，并累加
            $monthSum = array();

            for($i = 0; $i < count($monthPathArray); $i++)
            {
                $currentPath = $monthPathArray[$i];

                $sum2 = 0;

                $counter = 0;

                for($j = 0; $j < $dayCount; $j++)
                {
                    $currentData = $monthData[$j];

                    if(array_key_exists($currentPath,$currentData))
                    {
                        $data = json_decode($currentData[$currentPath], true);

                        $sum2 = $sum2 + $data['waitTime'];

                        $counter++;
                    }
                }

                $average2 = $sum2 / $counter;

                $tmp = array();

                $tmp["path"] = $currentPath;

                $tmp["waitTime"] = $average2;

                $monthSum[$currentPath] = json_encode($tmp);

            }
            $monthSum['beginDate'] = $beginDate;

            $monthSum['endDate'] = $endDate;

            $this->r->hMset($mKey, $monthSum);
        }
    }

    //每天渠道统计1
    public function dailyChannelStatistics(){
        $channelArray = $this->r->sMembers("allKeyChannel");

        $dailyChannelKey = "#Channel,".date("Y-m-d",strtotime("-1 day"));

        for($i = 0; $i < count($channelArray); $i++)
        {
            $data = $this->r->hGetAll($channelArray[$i]);
            if($data)
            {
                $json = json_encode($data);
                $this->r->hSet($dailyChannelKey, $channelArray[$i], $json);
            }
            //重置数据
            $this->r->del($channelArray[$i]);
        }
        $this->r->del("allKeyChannel");

        if($this->checkIfYesterdayEndOfWeek())
        {
            $week = date('W', strtotime("-1 day"));

            $wKey = "#Channel,".date("Y").","."week".",".$week;

            $weekData = $this->getOneWeekData(1, 'Channel');

            $beginDate = date("Y-m-d",strtotime('-7 day'));

            $endDate = date("Y-m-d",strtotime('-1 day'));

            // 将所有key汇总到一起，去重，生成type的并集
            $mergeArray = array_keys($weekData[0]);

            for($i = 1; $i < 7; $i++)
            {
                $mergeArray = array_merge($mergeArray, array_keys($weekData[$i]));
            }

            $weekPathArray = $this->deleteRepeat($mergeArray);

            // 在每一天的数据中搜索pathArray，并累加
            $weekSum = array();

            for($i = 0; $i < count($weekPathArray); $i++)
            {
                $currentPath = $weekPathArray[$i];

                $sumPv = 0;

                $sumUv = 0;

                $sumFree = 0;

                $sumOrder = 0;

                for($j = 0; $j < 7; $j++)
                {
                    $currentData = $weekData[$j];

                    if(array_key_exists($currentPath,$currentData))
                    {
                        $obj = json_decode($currentData[$currentPath]);

                        $sumPv = $sumPv + $obj->PV;

                        $sumUv = $sumUv + $obj->UV;

                        $sumFree = $sumFree + $obj->Free;

                        $sumOrder = $sumOrder + $obj->Order;
                    }
                }
                $tmp = array();

                $tmp["PV"] = $sumPv;

                $tmp["UV"] = $sumUv;

                $tmp["Order"] = $sumOrder;

                $tmp["Free"] = $sumFree;

                $weekSum[$currentPath] = json_encode($tmp);
            }

            $weekSum['beginDate'] = $beginDate;

            $weekSum['endDate'] = $endDate;

            $this->r->hMset($wKey, $weekSum);
        }
        if($this->checkIfYesterdayEndOfMonth())
        {
            $month=date('m',strtotime("-1 day"));

            $mKey="#Channel,".date("Y").","."month".",".$month;

            $beginDate=date('Y-m-01', strtotime("-1 day"));

            $endDate=date('Y-m-d', strtotime($beginDate." +1 month -1 day"));

            $dayCount=date("t");

            $monthData = $this->getOneMonthData($dayCount,"Channel");

            // 将所有key汇总到一起，去重，生成path的并集
            $mergeArray = array_keys($monthData[0]);

            for($i = 1; $i < $dayCount; $i++)
            {
                $mergeArray = array_merge($mergeArray, array_keys($monthData[$i]));
            }

            $monthPathArray = $this->deleteRepeat($mergeArray);

            // 在每一天的数据中搜索pathArray，并累加
            $monthSum = array();

            for($i = 0; $i < count($monthPathArray); $i++)
            {
                $currentPath = $monthPathArray[$i];

                $sumPv = 0;

                $sumUv = 0;

                $sumFree = 0;

                $sumOrder = 0;

                for($j = 0; $j < $dayCount; $j++)
                {
                    $currentData = $monthData[$j];

                    if(array_key_exists($currentPath,$currentData))
                    {
                        $obj = json_decode($currentData[$currentPath]);

                        $sumPv = $sumPv + $obj->PV;

                        $sumUv = $sumUv + $obj->UV;

                        $sumFree = $sumFree + $obj->Free;

                        $sumOrder = $sumOrder + $obj->Order;
                    }
                }
                $tmp = array();

                $tmp["PV"] = $sumPv;

                $tmp["UV"] = $sumUv;

                $tmp["Order"] = $sumOrder;

                $tmp["Free"] = $sumFree;

                $monthSum[$currentPath] = json_encode($tmp);

            }
            $monthSum['beginDate'] = $beginDate;

            $monthSum['endDate'] = $endDate;

            $this->r->hMset($mKey, $monthSum);
        }
    }

    //每天渠道统计2
    public function dailySgidStatistics(){
        $sgidArray = $this->r->sMembers("allKeySgid");

        $dailySgidKey = "#Sgid,".date("Y-m-d",strtotime("-1 day"));

        for($i = 0; $i < count($sgidArray); $i++)
        {
            $data = $this->r->hGetAll($sgidArray[$i]);
            if($data)
            {
                $json = json_encode($data);
                $this->r->hSet($dailySgidKey, $sgidArray[$i], $json);
            }
            //重置数据
            $this->r->del($sgidArray[$i]);
        }

        $this->r->del("allKeySgid");

        if($this->checkIfYesterdayEndOfWeek())
        {
            $week = date('W', strtotime("-1 day"));

            $wKey = "#Sgid,".date("Y").","."week".",".$week;

            $weekData = $this->getOneWeekData(1, 'Sgid');

            $beginDate = date("Y-m-d",strtotime('-7 day'));

            $endDate = date("Y-m-d",strtotime('-1 day'));

            // 将所有key汇总到一起，去重，生成type的并集
            $mergeArray = array_keys($weekData[0]);

            for($i = 1; $i < 7; $i++)
            {
                $mergeArray = array_merge($mergeArray, array_keys($weekData[$i]));
            }

            $weekPathArray = $this->deleteRepeat($mergeArray);

            // 在每一天的数据中搜索pathArray，并累加
            $weekSum = array();

            for($i = 0; $i < count($weekPathArray); $i++)
            {
                $currentPath = $weekPathArray[$i];

                $sumPv = 0;

                $sumUv = 0;

                $sumFree = 0;

                $sumOrder = 0;

                for($j = 0; $j < 7; $j++)
                {
                    $currentData = $weekData[$j];

                    if(array_key_exists($currentPath,$currentData))
                    {
                        $obj = json_decode($currentData[$currentPath]);

                        $sumPv = $sumPv + $obj->PV;

                        $sumUv = $sumUv + $obj->UV;

                        $sumFree = $sumFree + $obj->Free;

                        $sumOrder = $sumOrder + $obj->Order;
                    }
                }
                $tmp = array();

                $tmp["PV"] = $sumPv;

                $tmp["UV"] = $sumUv;

                $tmp["Order"] = $sumOrder;

                $tmp["Free"] = $sumFree;

                $weekSum[$currentPath] = json_encode($tmp);
            }

            $weekSum['beginDate'] = $beginDate;

            $weekSum['endDate'] = $endDate;

            $this->r->hMset($wKey, $weekSum);
        }
        if($this->checkIfYesterdayEndOfMonth())
        {
            $month=date('m',strtotime("-1 day"));

            $mKey="#Sgid,".date("Y").","."month".",".$month;

            $beginDate=date('Y-m-01', strtotime("-1 day"));

            $endDate=date('Y-m-d', strtotime($beginDate." +1 month -1 day"));

            $dayCount=date("t");

            $monthData = $this->getOneMonthData($dayCount,"Sgid");

            // 将所有key汇总到一起，去重，生成path的并集
            $mergeArray = array_keys($monthData[0]);

            for($i = 1; $i < $dayCount; $i++)
            {
                $mergeArray = array_merge($mergeArray, array_keys($monthData[$i]));
            }

            $monthPathArray = $this->deleteRepeat($mergeArray);

            // 在每一天的数据中搜索pathArray，并累加
            $monthSum = array();

            for($i = 0; $i < count($monthPathArray); $i++)
            {
                $currentPath = $monthPathArray[$i];

                $sumPv = 0;

                $sumUv = 0;

                $sumFree = 0;

                $sumOrder = 0;

                for($j = 0; $j < $dayCount; $j++)
                {
                    $currentData = $monthData[$j];

                    if(array_key_exists($currentPath,$currentData))
                    {
                        $obj = json_decode($currentData[$currentPath]);

                        $sumPv = $sumPv + $obj->PV;

                        $sumUv = $sumUv + $obj->UV;

                        $sumFree = $sumFree + $obj->Free;

                        $sumOrder = $sumOrder + $obj->Order;
                    }
                }
                $tmp = array();

                $tmp["PV"] = $sumPv;

                $tmp["UV"] = $sumUv;

                $tmp["Order"] = $sumOrder;

                $tmp["Free"] = $sumFree;

                $monthSum[$currentPath] = json_encode($tmp);

            }
            $monthSum['beginDate'] = $beginDate;

            $monthSum['endDate'] = $endDate;

            $this->r->hMset($mKey, $monthSum);
        }
    }

    //每天渠道统计3
    public function dailySceneIdStatistics(){
        $sceneIdArray = $this->r->sMembers("allKeyScenceId");

        $dailySceneIdKey = "#SceneId,".date("Y-m-d",strtotime("-1 day"));

        for($i = 0; $i < count($sceneIdArray); $i++)
        {
            $data = $this->r->hGetAll($sceneIdArray[$i]);
            if($data)
            {
                $json = json_encode($data);
                $this->r->hSet($dailySceneIdKey, $sceneIdArray[$i], $json);
            }
            //重置数据
            $this->r->del($sceneIdArray[$i]);
        }
        $this->r->del("allKeyScenceId");

        if($this->checkIfYesterdayEndOfWeek())
        {
            $week = date('W', strtotime("-1 day"));

            $wKey = "#SceneId,".date("Y").","."week".",".$week;

            $weekData = $this->getOneWeekData(1, 'SceneId');

            $beginDate = date("Y-m-d",strtotime('-7 day'));

            $endDate = date("Y-m-d",strtotime('-1 day'));

            // 将所有key汇总到一起，去重，生成type的并集
            $mergeArray = array_keys($weekData[0]);

            for($i = 1; $i < 7; $i++)
            {
                $mergeArray = array_merge($mergeArray, array_keys($weekData[$i]));
            }

            $weekPathArray = $this->deleteRepeat($mergeArray);

            // 在每一天的数据中搜索pathArray，并累加
            $weekSum = array();

            for($i = 0; $i < count($weekPathArray); $i++)
            {
                $currentPath = $weekPathArray[$i];

                $sumPv = 0;

                $sumUv = 0;

                $sumFree = 0;

                $sumOrder = 0;

                for($j = 0; $j < 7; $j++)
                {
                    $currentData = $weekData[$j];

                    if(array_key_exists($currentPath,$currentData))
                    {
                        $obj = json_decode($currentData[$currentPath]);

                        $sumPv = $sumPv + $obj->PV;

                        $sumUv = $sumUv + $obj->UV;

                        $sumFree = $sumFree + $obj->Free;

                        $sumOrder = $sumOrder + $obj->Order;
                    }
                }
                $tmp = array();

                $tmp["PV"] = $sumPv;

                $tmp["UV"] = $sumUv;

                $tmp["Order"] = $sumOrder;

                $tmp["Free"] = $sumFree;

                $weekSum[$currentPath] = json_encode($tmp);
            }

            $weekSum['beginDate'] = $beginDate;

            $weekSum['endDate'] = $endDate;

            $this->r->hMset($wKey, $weekSum);
        }
        if($this->checkIfYesterdayEndOfMonth())
        {
            $month=date('m',strtotime("-1 day"));

            $mKey="#SceneId,".date("Y").","."month".",".$month;

            $beginDate=date('Y-m-01', strtotime("-1 day"));

            $endDate=date('Y-m-d', strtotime($beginDate." +1 month -1 day"));

            $dayCount=date("t");

            $monthData = $this->getOneMonthData($dayCount,"SceneId");

            // 将所有key汇总到一起，去重，生成path的并集
            $mergeArray = array_keys($monthData[0]);

            for($i = 1; $i < $dayCount; $i++)
            {
                $mergeArray = array_merge($mergeArray, array_keys($monthData[$i]));
            }

            $monthPathArray = $this->deleteRepeat($mergeArray);

            // 在每一天的数据中搜索pathArray，并累加
            $monthSum = array();

            for($i = 0; $i < count($monthPathArray); $i++)
            {
                $currentPath = $monthPathArray[$i];

                $sumPv = 0;

                $sumUv = 0;

                $sumFree = 0;

                $sumOrder = 0;

                for($j = 0; $j < $dayCount; $j++)
                {
                    $currentData = $monthData[$j];

                    if(array_key_exists($currentPath,$currentData))
                    {
                        $obj = json_decode($currentData[$currentPath]);

                        $sumPv = $sumPv + $obj->PV;

                        $sumUv = $sumUv + $obj->UV;

                        $sumFree = $sumFree + $obj->Free;

                        $sumOrder = $sumOrder + $obj->Order;
                    }
                }
                $tmp = array();

                $tmp["PV"] = $sumPv;

                $tmp["UV"] = $sumUv;

                $tmp["Order"] = $sumOrder;

                $tmp["Free"] = $sumFree;

                $monthSum[$currentPath] = json_encode($tmp);

            }
            $monthSum['beginDate'] = $beginDate;

            $monthSum['endDate'] = $endDate;

            $this->r->hMset($mKey, $monthSum);
        }
    }


    //每天热力图点统计
//    public function dailyPointStatistics()
//    {
//
//    }


    //获取某一个星期的统计
    public function getOneWeekData($page, $type)
    {
        $keyDayArray = array();

        for($i = 0; $i < 7; $i++)
        {
            $aimDay="-".($i + 1 + ($page - 1)* 7)." day";

            $keyDayArray[$i]='#'.$type.','.date("Y-m-d",strtotime($aimDay));

        }

        $data=array();

        for($j=0;$j<7;$j++)
        {
            $data[$j]=$this->r->hGetAll($keyDayArray[$j]);
        }
        return $data;
    }

    //获取某一个月的统计
    private function getOneMonthData($dayCount,$type)
    {
        $keyDayArray = array();

        for($i = 0; $i < $dayCount; $i++)
        {
            $aimDay="-".($i + 1)." day";

            $keyDayArray[$i]='#'.$type.','.date("Y-m-d",strtotime($aimDay));

        }

        $data=array();

        for($j = 0; $j < $dayCount; $j++)
        {
            $data[$j]=$this->r->hGetAll($keyDayArray[$j]);
        }

        return $data;
    }

    //判断是否昨天是本周的最后一天 星期天
    private function checkIfYesterdayEndOfWeek()
    {
        if(date('w',strtotime("-1 day")) == 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    private function checkIfYesterdayEndOfMonth()
    {
        $month = date('m',strtotime("-1 day"));

        $nowMonth=date("m",strtotime("today"));


        if($month == $nowMonth)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    // 数组去重
    public function deleteRepeat($array)
    {
        $count = count($array);
        for($i = 0;$i<$count;$i++)
        {
            $change = false;
            for($j=$i+1;$j<$count;$j++)
            {
                if($array[$i] == $array[$j])
                {
                    $change=true;
                    break;
                }
            }
            if($change==false)
            {
                $tmpArr[] = $array[$i];
            }
        }
        return $tmpArr;
    }

    public function hGetAll($key)
    {


        $res = $this->r->hGetAll($key);

        if($res)
            return $res;

        else
            return false;
    }

    public function hGet($key, $field)
    {


        $res = $this->r->hGet($key,$field);

        if ($res)
            return $res;

        else
            return false;
    }

    public function deleteZeroPvUv($key)
    {
        $pathArray = $this->r->sMembers('allPath');
        for($i = 0; $i < count($pathArray); $i++)
        {
            $res = $this->r->hGet($key, $pathArray[$i]);
            if($res)
            {
                $obj = json_decode($res);
                $pv = $obj->Pv;
                if ($pv == 0)
                    $this->r->hDel($key, $pathArray[$i]);
            }
        }
    }

    public function deleteZeroWaitTime($key)
    {
        $pathArray = $this->r->sMembers('allPath');
        for($i = 0; $i < count($pathArray); $i++)
        {
            $res = $this->r->hGet($key, $pathArray[$i]);
            if(!$res)
            {
                $this->r->hDel($key, $pathArray[$i]);
            }
        }
    }

    public function deleteZeroClick($key)
    {
        $pathArray = $this->r->sMembers('allType');
        for($i = 0; $i < count($pathArray); $i++)
        {
            $res = $this->r->hGet($key, $pathArray[$i]);
            if($res)
            {
                $obj = json_decode($res);
                $click = $obj->click;
                if ($click == 0)
                    $this->r->hDel($key, $pathArray[$i]);
            }
        }
    }

    public function delGuanjiaOrder($guanjiaid)
    {
        $key = 'isguanjiatotle'.$guanjiaid;
        return $this->r->del($key);
    }

    public function hasTotalGunjiaOrder($guanjiaid)
    {
        $key = 'isguanjiatotle'.$guanjiaid;
        return $this->r->getSet($key, 1);
    }

    public function getGuanJiaOrders($guanjiaid)
    {
        $key = 'gjnums'.$guanjiaid;
        return $this->r->get($key);
    }

    public function setGuanJiaOrders($guanjiaid)
    {
        $key = 'gjnums'.$guanjiaid;
        return $this->r->set($key, 0);
    }

    public function addGuanJiaOrders($guanjiaid, $num)
    {
        $key = 'gjnums'.$guanjiaid;
        return $this->r->incrBy($key, $num);
    }

    public function addcronOrdernum($guanjiaid,$time, $num)
    {
        $key = 'gjsnums'.$guanjiaid;
        return $this->r->zAdd($key, $time,$num);
    }

    public function getcronOrdernum($guanjiaid,$time)
    {
        $key = 'gjsnums'.$guanjiaid;
        $num = $this->r->zRangeByScore($key, 0, $time);
        $this->r->zRemRangeByScore($key, 0, $time);
        return $num;
    }

    //支付弹窗显示 $value  wechat(微信公众号)  h5wechat（h5支付）
    public function addPayAlert($ordersn)
    {
        $key = 'alert'.$ordersn;
        return $this->r->setex($key, 30, 1);
    }

    public function getPayAlert($ordersn)
    {
        $key = 'alert'.$ordersn;
        return $this->r->get($key);
    }

    public function delPayAlert($ordersn)
    {
        $key = 'alert'.$ordersn;
        return $this->r->del($key);
    }


    public function bindCardWrong($jdaccount)
    {
        $key="BindCard".$jdaccount;

        if($this->r->exists($key))
        {
            $value=$this->r->get($key);

            if($value==10)
            {
                return false;
            }
            else
            {
                $ttl=$this->r->ttl($key);

                $ttl=3600*34-$ttl;

                return $this->r->setex($key,$ttl,$value+1);
            }
        }
        else
        {
            return $this->r->setex($key,3600*24,1);
        }

    }

    public function getBindWrong($jdaccount)
    {
        $key="BindCard".$jdaccount;

        return $this->r->get($key);

    }

    /*
     * 保险临时保存结果
     */
    public function setBaoxianShrenshouResult($data, $type = 'shrenshou')
    {
        $ttl = 3600 *24; // 一天有效
        $key = $type . time() . rand(0, 999999);
        if ($this->r->setex($key, $ttl, json_encode($data))) {
            return $key;
        }
        return false;
    }

    /*
     * 保险取结果
     */
    public function getBaoxianShrenshouResult($key)
    {
        $jsonData = $this->r->get($key);
        if (!$jsonData) return false;
        return json_decode($jsonData, true);
    }

    public function setUserRecentTime($jdaccount, $nowtime)
    {
        $key = 'logintime';
        return $this->r->hSet($key,$jdaccount,$nowtime);
    }

    public function getUserRecentTime($jdaccount)
    {
        $key = 'logintime';
        return $this->r->hGet($key,$jdaccount);
    }

}