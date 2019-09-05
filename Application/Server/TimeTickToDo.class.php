<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/9/29
 * Time: 10:11
 */

namespace Server;


use Redis;

class TimeTickToDo
{
    const REDISURL="172.25.44.101";

    const REDISPORT=6379;

    public $R;

    public $toDostring;


    public function __construct($toDoString)
    {

        $this->R =new Redis();
        if (APPENV == 'production')  {
            $url = 'r2m-proxy.jdfin.local';
            $port = 6379;
            $this->R->connect($url, $port);
            $this->R->auth('dongjia');
        } elseif (APPENV == 'present') {

            $url = '172.25.44.101';
            $port = 6379;
            $this->R->connect($url, $port);
            $this->R->auth('dongjia');
        } else {
            $url = '127.0.0.1';
            $port = 6379;
            $this->R->connect($url, $port);
        }
        if($toDoString)
        {
            $this->toDostring=$toDoString;
        }


    }

    public function setToDostring($toDoString)
    {
        $this->toDostring = $toDoString;
    }

    /*过期时间 过期类型 编号*/

    public function addOneTimeTick($actionTime)
    {
        $key="TimeTickToDo";
        $res = $this->R->zAdd($key,$actionTime,$this->toDostring);
        if ($res !== false) return true;
        return false;
    }

    public function getOneTimeTick()
    {

    }

    public function delAllTimeTick()
    {
        $key="TimeTickToDo";

        $this->R->del($key);
    }


    public function delOneTimeTick($delString, $needpush = true)
    {
        $key="TimeTickToDo";

        $res1 = $this->R->zRem($key,$delString);

        $res2 = true;

        if ($needpush) {
            $res2 = $this->transTimeTickToFinishDoneList($delString);
        }
        if ($res1 && $res2) {
            return true;
        } else {
            return false;
        }

    }
    public function findThisTimeAllNeedToDoNow()
    {
        $key="TimeTickToDo";
        return $this->R->zRangeByScore($key,0,time());

    }
    public function transTimeTickToFinishDoneList($string)
    {
        $key="FinishToDoList";
        return $this->R->lPush($key,$string);
    }

    public function findThisTimeAllNeedToDoBefore($beforeTime)
    {
        $key="TimeTickToDo";

        return $this->R->zRangeByScore($key,0,time()+$beforeTime);
    }

    public function addPushMessageSended($ordersn)
    {
        $key="pushHasSended";

        $this->R->sAdd($key,$ordersn);
    }


    public function findPushSendedOrNot($ordersn)
    {
        $key="pushHasSended";

        return $this->R->sIsMember($key,$ordersn);
    }

}