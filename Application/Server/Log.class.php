<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/1/15
 * Time: 11:10
 */

namespace Server;


use AjaxApi\Model\LogModel;

class Log
{
    public function writeLog($message, $type = '')
    {
        if (!$type) return false;
        if (!$message) return false;
        $logModel = new LogModel();
        $res = $logModel->addLog($message, time(), $type);
        return $res;
    }

}