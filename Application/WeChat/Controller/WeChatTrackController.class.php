<?php
/**
 * Created by PhpStorm.
 * User: vanillachocola
 * Date: 2018/2/26
 * Time: 下午4:11
 */

namespace WeChat\Controller;

/****
 *
 *
 * 微信公众号埋点管理
 *
 */
use Think\Controller;
use WeChat\Controller\WeChatBaseController;
use Server\WeChatRedis;


class WeChatTrackController extends Controller//Controller//
{

    //http://www.dservie.cn/myWeb/index.php/WeChat/WeChatTrack/visitCount
    /* 埋点
     * data: name, path, type
     *
     */

    public function _initialize(){
        header('Content-type: application/json');
    }
    public function visitCount()
    {
        $redis = new WeChatRedis();

        $path = I("post.path", 0);
        $name = I("post.name", 0);
        $type = I("post.type", 0);
        $jdaccount = session('jdaccount');
        $res = $redis->countVisit($name, $path, $jdaccount, $type);
        if($res)
            response("获取成功",1, $res);
    }

    //http://www.dservie.cn/myWeb/index.php/WeChat/WeChatTrack/collectClickPoint
    /* 热力图
     * data: points
     *
     */
    public function collectClickPoint()
    {
        $redis = new WeChatRedis();
        $path = I("post.path", 0);
        $points = $_POST['points'];
        $data = json_decode($points, true);
        foreach ($data as $point){
            $redis->logClickPoint($path, $point);
        }
        response("获取成功",1, "POINT SUCCESS");
    }

    //http://www.dservie.cn/myWeb/index.php/WeChat/WeChatTrack/collectWaitTime
    /* 停留时间
     * data: waitTime
     *
     */
    public function collectWaitTime()
    {
        $redis = new WeChatRedis();
        $path = I("post.path", 0);
        $waitTime = I("post.waitTime", 0);
        $res = $redis->logWaitTime($path, $waitTime);
        response("获取成功",1, $res);
    }

    //http://www.dservie.cn/myWeb/index.php/WeChat/WeChatTrack/collectEvent
    /* 活动页面
     * name:act201838 path: agid,phone type:event
     *
     */
    public function collectEvent()
    {
        $redis = new WeChatRedis();

        $path = I("post.path", 0);
        $name = I("post.name", 0);
        $type = I("post.type", 0);

        $res = $redis->logEventData($name, $path, $type);

        response("获取成功",1, $res);
    }

    public function collectChannel()
    {
//        $json = file_get_contents('php://input');

//        $obj = json_decode($json);

        $channel=I('post.channelId');

        $sgid=I('post.sgId');

        $scenceId=I('post.scenceId');

        $status=I('post.status');

        //var_dump($channel.$sgid.$scenceId.$status);die;

        $redis = new WeChatRedis();

        if(!$channel)
        {
            $channel="NO";
        }
        if(!$sgid)
        {
            $sgid="NO";
        }

        if(!$scenceId)
        {
            $scenceId="NO";
        }

        //var_dump($channel) ;


        $res= $redis->logChannelData($channel,$sgid,$scenceId,$status);

        response("获取成功",1,$res);


    }
    public function collectChannelInside($channel, $sgid, $scenceId, $status = 9)
    {

        $redis = new WeChatRedis();

        if(!$channel)
        {
            $channel="NO";
        }
        if(!$sgid)
        {
            $sgid="NO";
        }

        if(!$scenceId)
        {
            $scenceId="NO";
        }

        $res= $redis->logChannelData($channel,$sgid,$scenceId,$status);

        return $res;
//        response("获取成功",1,$res);
    }
// 以下测试方法
    //http://www.dservie.cn/myWeb/index.php/WeChat/WeChatTrack/getIndexStatistics
    /*
     * 前端接口
     *
     */
    public function getIndexStatistics()
    {
        $json = file_get_contents('php://input');

        $obj = json_decode($json);

        $path = $obj->path;

        $type = $obj->type;

        $page = $obj->page;

        $timeType = $obj->timeType;

        /*测试数据*/
//        $path="/index";
//
//        $type="PvUv";
//
//        $page=1;
//
//        $timeType="Day";

        //统计PvUv
        if($type==="PvUv")
        {
            $pvData = array();

            $uvData = array();

            $xData = array();

            if($timeType==="Day")
            {
                $data = $this->getDayDataByPath($path, $type, 7);

                for($i = 0; $i < count($data); $i++) {

                    $obj = json_decode($data[$i]['count']);

                    $date = $data[$i]['date'];

                    array_push($xData, $date);

                    array_push($pvData, $obj->Pv === null ? 0 : $obj->Pv);

                    array_push($uvData, $obj->Uv === null ? 0 : $obj->Uv);
                }
                //数据组合
                $data["legenddata"]=["PV","UV"];

                $data["xdata"]=$xData;

                $data["data"]=[$pvData,$uvData];

                response("Ok",1,$data);
            }
            else if($timeType==="Week")
            {
                $Redis=new WeChatRedis();

                $keyWeekArray = array();

                $year = date('Y');

                $data=array();

                for($i = 0; $i < 10; $i++)
                {

                    $keyWeekArray[$i]='#'.$type.','.$year.',month,'.date('W',strtotime("-".$i." week"));

                    $xData[$i]=$year.',month,'.date('W',strtotime("-".$i." week"));

                }

                for($j=0;$j<10;$j++)
                {
                    $res=$Redis->hGetByCondition($keyWeekArray[$j],"totalSum");

                    $obj=json_decode($res);

                    if($obj->Pv===null)
                    {
                        $pVArray[$j]=0;
                    }
                    else
                    {
                        $pVArray[$j]=$obj->Pv;
                    }

                    if($obj->Uv===null)
                    {
                        $uVArray[$j]=0;
                    }
                    else
                    {
                        $uVArray[$j]=$obj->Uv;
                    }

                }

                $data["legenddata"]=["整体PV","整体UV"];

                $data["xdata"]=$xData;

                $data["data"]=[$pVArray,$uVArray];

                response("Ok",1,$data);

            }
            else if($timeType==="Month")
            {
                $Redis=new WeChatRedis();

                $keyMonthArray = array();

                $year = date('Y');

                $data=array();

                for($i = 0; $i < 12; $i++)
                {

                    $keyMonthArray[$i]='#'.$type.','.$year.',month,'.date('m',strtotime("-".$i." month"));

                    $xData[$i]=$year.',month,'.date('m',strtotime("-".$i." month"));

                }

                for($j=0;$j<12;$j++)
                {
                    $res=$Redis->hGetByCondition($keyMonthArray[$j],"totalSum");

                    $obj=json_decode($res);

                    if($obj->Pv===null)
                    {
                        $pVArray[$j]=0;
                    }
                    else
                    {
                        $pVArray[$j]=$obj->Pv;
                    }

                    if($obj->Uv===null)
                    {
                        $uVArray[$j]=0;
                    }
                    else
                    {
                        $uVArray[$j]=$obj->Uv;
                    }
                }
                response("Ok",1,$data);
            }
        }
        //统计C1，C2，C3
        else if($type==="Click")
        {
            $keyDayArray = array();

            $data=array();

            $clickArray=array();

            $xData=array();

            $legendArray=array();

            $pathArray=array();


            if($timeType==="Day")
            {
                //统计所有的PvUv 拿最近七天的所有数据
                $Redis=new WeChatRedis();

                for($i = 0; $i < 7; $i++)
                {
                    $aimDay="-".($i + 1 + ($page - 1)* 7)." day";

                    $keyDayArray[$i]='#Click,'.date("Y-m-d",strtotime($aimDay));

                    $xData[$i]=date("Y-m-d",strtotime($aimDay));

                }

                for($j=0;$j<7;$j++)
                {

                    $hGetAll=$Redis->hGetAll($keyDayArray[$j]);

                    $hKeys = array_keys($hGetAll);

                    $count=count($hKeys);

                    for($k = 0; $k < $count; $k++)
                    {
                        if(strpos($hKeys[$k],"index,C1".$type))
                        {

                            $content=json_decode($hGetAll[$hKeys[$k]], true);

                            $path= $content['pathTo'];

                            $click= $content['click'];

                            if(in_array($content["pathTo"], $pathArray))
                            {
                                array_push($pathArray,$path);


                            }

                            $clickArray[$j]["pathTo"]=$content['pathTo'];

                            $clickArray[$j]["click"]=$content['click'];
                        }
                    }
                }

                $countC=count($clickArray);

                for($n=0;$n<$countC;$n++)
                {
                    if($clickArray[$n]);
                }


                response("Ok",1,$data);

            }
            else if($timeType==="Week")
            {
                $Redis=new WeChatRedis();

                $keyWeekArray = array();

                $year = date('Y');

                $data=array();

                for($i = 0; $i < 10; $i++)
                {

                    $keyWeekArray[$i]='#'.$type.','.$year.',month,'.date('W',strtotime("-".$i." week"));

                    $data[$i]["week"]=$year.',month,'.date('W',strtotime("-".$i." week"));

                }

                for($j=0;$j<10;$j++)
                {
                    $data[$j]["count"]=$Redis->hGetByCondition($keyWeekArray[$j],"totalSum");
                }

                response("Ok",1,$data);
            }

            else if($timeType==="Month")
            {
                $Redis=new WeChatRedis();

                $keyMonthArray = array();

                $year = date('Y');

                $data=array();

                for($i = 0; $i < 12; $i++)
                {

                    $keyMonthArray[$i]='#'.$type.','.$year.',month,'.date('m',strtotime("-".$i." month"));

                    $data[$i]["month"]=$year.',month,'.date('m',strtotime("-".$i." month"));

                }

                for($j=0;$j<12;$j++)
                {
                    $data[$j]["count"]=$Redis->hGetByCondition($keyMonthArray[$j],"totalSum");
                }

                response("Ok",1,$data);
            }
        }
    }

    /*
     * 根据$type拿最近七天的所有数据
     *
     * $type = PvUv || Click || WaitTime
     *
     * VanillaChocola
     */
    private function getDayDataByPath($path, $type, $count)
    {
        //拿最近七天的所有数据
        $Redis=new WeChatRedis();

        $res = array();

        for($i = 0; $i < $count; $i++)
        {
            $aimDay = "-" . ($i + 1) . " day";

            $key = '#' . $type . ',' . date("Y-m-d", strtotime($aimDay));

            $data = $Redis->hGetAll($key);

            if (array_key_exists($path, $data))
            {
                $tmp = array('date' => date("Y-m-d", strtotime($aimDay)),
                    'count' => $data[$path]);

                array_push($res, $tmp);
            }
        }
        return $res;
    }

    public function test(){
        $redis = new WeChatRedis();

        $redis->deleteZeroPvUv('#PvUv,2018-03-08');
        $redis->deleteZeroWaitTime('#WaitTime,2018-03-08');
        $redis->deleteZeroClick('#Click,2018-03-08');
        echo 555;
    }

}