<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/5/22
 * Time: 13:42
 */

namespace Server;
use AjaxApi\Model\LogModel;


class Coin
{
    const GETCOINURL = '/myWeb/index.php/CoinApi/Coin/getUserCoinByJDAccountDJGJ';
    const LOCKCOIN = '/myWeb/index.php/CoinApi/Coin/lockCoin';
    const ADDCOINURL = '/myWeb/index.php/CoinApi/Coin/addCoin';
    const DELCOINURL = '/myWeb/index.php/CoinApi/Coin/delCoin';
    const BACKCOINURL = '/myWeb/index.php/CoinApi/Coin/backCoin';
    const COINPRICETRUE = '/myWeb/index.php/CoinApi/CoinOrder/coinTruePriceRecord';
    public $AesClass = '';
    private $password = 'woshinibaba';
    private $logmodel;

    public function __construct()
    {
        $this->AesClass = new GJAES();
        $this->logmodel = new LogModel();
    }

    public function getUserCoin($jdaccount = '')
    {
        if (!$jdaccount) return 0;
        $data['jdaccount'] = $jdaccount;
        $result = $this->postData($data,1);

        if ($result['state']) {
            $aes = new GJAES();
            $price = $aes->aes_decrypt($result['data']['price'], $result['data']['jdaccount']."woshinibaba");
            $coin = intval($price);
//            $coin = intval($result['data']);
        } else {
            $coin = 0;
        }
        return $coin;
    }

    public function lockcoin($jdaccount, $coin, $ordersn)
    {
        if (!$jdaccount) return false;
        $data['operationby'] = 'lockCoin';
        $data['jdaccount'] = $jdaccount;
        $data['price'] = $coin;
        $data['status'] = '0';
        $data['ordersn'] = $ordersn;
        $result = $this->postData($data,2);

        if ($result['state']) {
            return true;
        } else {
            return false;
        }
    }

    public function delcoin($jdaccount,$coin,$ordersn)
    {
        if (!$jdaccount) return false;
        $data['operationby'] = 'delCoin';
        $data['jdaccount'] = $jdaccount;
        $data['price'] = $coin;
        $data['ordersn'] = $ordersn;
        $data['status'] = 1;
        $result = $this->postData($data,3);
        if ($result['state']) {
            return true;
        } else {
            return false;
        }
    }

    public function backcoin($jdaccount,$coin,$ordersn)
    {
        if (!$jdaccount) return false;
        $data['operationby'] = 'backCoin';
        $data['jdaccount'] = $jdaccount;
        $data['price'] = $coin;
        $data['ordersn'] = $ordersn;
        $data['status'] = 2; // 退款
        $result = $this->postData($data,4);
        if ($result['state']) {
            return true;
        } else {
            return false;
        }
    }

    public function recordTrueCoin($jdaccount, $ordersn, $truepricecost, $coin)
    {
        if (!$jdaccount) return false;
        $data['ordersn'] = $ordersn;
        $data['jdaccount'] = $jdaccount;
        $data['truepricecost'] = $truepricecost;
        $data['coin'] = $coin;
        $result = $this->postData($data,4);
        if ($result['state']) {
            return true;
        } else {
            return false;
        }
    }

    private function jsEncode($msg,$type = 0, $extdata = '', $isreturn = false){
        if ($isreturn) {
            return $type >0 ? true : false;
        } else {
            $arr['state'] = $type;
            $arr['msg'] = $msg;
            $arr['data'] = $extdata;
            return json_encode($arr);
        }
    }

    /**
     * @param $data
     * @param int $type  1搜索  2抵扣 3核销 4解除币锁定/归还
     * @return mixed|string
     */
    private function postData($data, $type =1)
    {
        $url = '';
        if ($type == 1) {
//            $url = C("COINURL").self::GETCOINURL;
//            $result = http_post_data($url,json_encode($data));
            $returnInfo = A('CoinApi/Coin')->getUserCoinByJDAccountNew($data);
            $result = $this->jsEncode("Success",1,$returnInfo);
        } elseif ($type ==2) {
//            $url = C("COINURL").self::LOCKCOIN;
            $data = $this->encryptionData($data);
//            $result = http_post_data($url,json_encode(['data'=>$data]));
            $returnInfo = A('CoinApi/Coin')->lockCoinInner(json_encode(['data'=>$data]));
            $result = $this->jsEncode("Success Lock",1, $returnInfo);
        } elseif ($type == 3) {
//            $url = C("COINURL").self::DELCOINURL;
            $data = $this->encryptionData($data);
//            $result = http_post_data($url,json_encode(['data'=>$data]));
            $returnInfo = A('CoinApi/Coin')->delCoinInner(json_encode(['data'=>$data]));
            $result = $this->jsEncode("Success Del",1, $returnInfo);
        } elseif ($type == 4) {
//            $url = C("COINURL").self::BACKCOINURL;
            $data = $this->encryptionData($data);
//            $result = http_post_data($url,json_encode(['data'=>$data]));
            $returnInfo = A('CoinApi/Coin')->backCoinInner(json_encode(['data'=>$data]));
            $result = $this->jsEncode("Success Add",1, $returnInfo);
        }
        elseif ($type == 5) {
            $url = C("COINURL").self::COINPRICETRUE;
            $result = http_post_data($url,json_encode(['data'=>$data]));
        }

        $this->logmodel->addLog($result, time(), '0');

        return json_decode($result,true);
    }

    private function getData()
    {

    }

    private function encryptionData($data)
    {
        return $this->AesClass->aes_encrypt(json_encode($data),$this->password);
    }

    private function decryptionData($encryptionData)
    {

    }
}