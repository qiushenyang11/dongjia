<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/1/15
 * Time: 10:30
 */

namespace Server;


use AjaxApi\Model\LogModel;
use Operation\Model\OrderModel;
use WeChat\Model\GoodsModel;

class Crontab extends TimeTickToDo
{
    /**
     * @brief 添加自动下线
     * @param string $offlineTime
     * @return bool
     */
    public function addProductOfflineTick($offlineTime = '', $message = '')
    {
        if (!$offlineTime) return false;
        if ($offlineTime <= time()) return true;
        $model = M();
        $model->startTrans();
        $res = $this->addOneTimeTick($offlineTime);
        $res1 = true;
        if ($message) {
            $logModel = new LogModel();
            $res1 = $logModel->addLog($message, time());
        }
        if ($res1 && $res) {
            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;
        }
    }

    public function addSpecOfflineTikc($offlineTime,$message = '')
    {
        if (!$offlineTime) return false;
        if ($offlineTime <= time()) return true;
        $model = M();
        $model->startTrans();
        $res = $this->addOneTimeTick($offlineTime);
        $res1 = true;
        if ($message) {
            $logModel = new LogModel();
            $res1 = $logModel->addLog($message, time());
        }
        if ($res1 && $res) {
            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;
        }
    }

    /**
     * @breif 添加订单自动取消时间
     * @param string $cancelTime
     * @return bool
     */
    public function addCancelOrder($cancelTime = '',$message = '')
    {
        if (!$cancelTime) return false;
        if ($cancelTime <= time()) return false;
        $model = M();
        $model->startTrans();
        $res = $this->addOneTimeTick($cancelTime);
        $res1 = true;
        if ($message) {
            $logModel = new LogModel();
            $res1 = $logModel->addLog($message, time());
        }
        if ($res1 && $res) {
            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;
        }
    }

    /**
     * @breif 添加订单自动完成时间
     * @param string $finishTime
     * @return bool
     */
    public function addFinishOrder($finishTime = '', $message = '')
    {
        if (!$finishTime) return false;
        if ($finishTime <= time()) return false;
        $model = M();
        $model->startTrans();
        $res = $this->addOneTimeTick($finishTime);
        $res1 = true;
        if ($message) {
            $logModel = new LogModel();
            $res1 = $logModel->addLog($message, time());
        }
        if ($res1 && $res) {
            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;
        }
    }

    /**
     * @breif 执行自动下线
     * @param int $productid
     * @return bool
     */
    public function OperationOfflineTick($productid = 0)
    {
        if (!$productid) return false;
        $productModel = new GoodsModel();
        $isoffline = $productModel->isProductOffline($productid);
        $log = new Log();
        $delstring = json_encode(['id'=>$productid,'type'=>'product']);
        $model = M();
        $model->startTrans();
        $res1 = true;
        $res2 = true;
        $res3 = true;
        if (!$isoffline) {
            $res2 = $log->writeLog('产品id:'.$productid.'产品已被手动下线', 'product');
        }
        $res1 = $productModel->productOffline($productid);

        $res3 = $this->delOneTimeTick($delstring);
        $res2 = $log->writeLog('产品id:'.$productid.'产品已被自动下线', 'product');
        if ($res1 && $res2 && $res3) {
            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;
        }
    }

    /**
     * 订单未支付情况下的短信提醒
     */
    public function OperationPushPayOrder($ordersn = '')
    {
        if (!$ordersn) return false;

        $orderClass = new Order();
        //获取订单信息

//        echo "OperationPushPayOrder/n";
//
//        echo "<br/>";

        $orderModel = new OrderModel();

        $orderinfo = $orderModel->getOrderInfo($ordersn);

        $res = $orderClass->findCanPushOrder($ordersn);

        if($res)
        {

//            echo "find res";
//
//            echo "<br/>";

            if($this->findPushSendedOrNot($ordersn))
            {
//                 echo "has sended";
//
//                 echo "<br/>";

            }
            else
            {

//                echo "no sended";
//
//                echo "<br/>";
//
//                echo "push sendinging";
//
//                echo "<br/>";


                $log = new Log();

                $Sms=new SmsMessage();

                $phone=$orderinfo["mobile"];

                $productname=$orderinfo["productname"];

                if($Sms->sendPayPush($phone,$productname))
                {
                    $this->addPushMessageSended($ordersn);

                    $log->writeLog('订单push ordersn:'.$ordersn.'push', 'push');
                }
            }

        }
        else
        {
            //echo "no find res";
        }

    }

    /**
     * @breif 15分钟未操作取消订单操作
     * @param string $ordersn
     * @return bool
     */
    public function OperationCancelOrder($ordersn = '')
    {
        if (!$ordersn) return false;
        $orderClass = new Order();
        $res = $orderClass->cancelOrder($ordersn, 0, '系统', 6);
        $log = new Log();
        $delstring = json_encode(['id'=>$ordersn,'type'=>'cancelorder']);
        if (!$res) {
            $log->writeLog('ordersn:'.$ordersn.',订单取消异常或已被支付', 'cancelorder');
        } else {
            $log->writeLog('ordersn:'.$ordersn.',系统自动取消24小时未支付订单', 'cancelorder');
        }
        $res = $this->delOneTimeTick($delstring);
        return $res ? true : false;
    }

    public function OperationFinishOrder($ordersn = '')
    {
        if (!$ordersn) return false;
        $orderClass = new Order();
        $res = $orderClass->finishOrder($ordersn);
        $log = new Log();
        $delstring = json_encode(['id'=>$ordersn,'type'=>'finishorder']);
        if (!$res) {
            $log->writeLog('ordersn:'.$ordersn.',自动完成订单状态异常', 'finishorder');
        } else {
            $log->writeLog('ordersn:'.$ordersn.',系统自动完成7天已签收订单', 'finishorder');
        }
        $res = $this->delOneTimeTick($delstring);
        return $res ? true : false;
    }

    public function sendMarketing($ordersn = '')
    {
        if (!$ordersn) return false;
        $orderModel = new OrderModel();
        $orderinfo = $orderModel->getOrderInfo($ordersn);
        $smsClass = new SmsMessage();
        $log = new Log();
        $delstring = json_encode(['id'=>$ordersn,'type'=>'sendmarketing']);
        $res = $smsClass->sendmarketing($orderinfo['mobile'], $orderinfo['productname']);
        if (!$res) {
            $log->writeLog('ordersn:'.$ordersn.',营销短息发送失败', 'sendmarketing');
        } else {
            $log->writeLog('ordersn:'.$ordersn.',营销短息发送成功', 'sendmarketing');
        }
        $res = $this->delOneTimeTick($delstring);
        return $res ? true : false;
    }

    public function offlineSpec($specid)
    {
        if (!$specid) return false;
        $delstring = json_encode(['id'=>$specid,'type'=>'offlinespec']);
        $goodsModel = new GoodsModel();
        $res = $goodsModel->saveSpec($specid,['status'=>2]);
        $log = new Log();
        if (!$res) {
            $log->writeLog('specid:'.$specid.'下线失败,已下线', 'offlinespec');
        } else {
            $log->writeLog('specid:'.$specid.',下线成功,时间为:'.Date("Y-m-d G:i:s"), 'offlinespec');
        }
        $res = $this->delOneTimeTick($delstring);
        return $res ? true : false;
    }
}