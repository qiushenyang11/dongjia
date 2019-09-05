<?php
/**
 * Created by PhpStorm.
 * User: QiuShenYang
 * Date: 2018/9/13
 * Time: 12:45
 */

namespace Server;


use AjaxApi\Model\LogModel;

class KerperApi
{
    public  $app_key    = '44abddb48fef4f6da0eaad39192ffa38' ;
    public  $app_secret = '71f6687ab4a44204afd2a91840c2f506';
    public  $username   = 'shdwx2018'     ;
    public  $password   = 'jd123456' ;
    //发送参数 
    public function send( $url  )  //
    {

        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // 跳过证书检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        $data = curl_exec( $curl ) ;      //返回api的json对象
        curl_close($curl); //关闭URL请求
        //  print_r( $data ) ;
        if ($data === false) {
            if (curl_errno($curl) == CURLE_OPERATION_TIMEDOUT) {
                return array( 'err'=>1 , 'data'=>$data ,'istimeout'=>1 ) ;
            }
        }
        $rr = json_decode ( $data ,  true )  ;
        if( is_array($rr) ){
            return array( 'err'=>0 , 'data'=> $rr ,'istimeout'=>0  ) ;
        }else{
            return array( 'err'=>1 , 'data'=>$data,'istimeout'=>0    ) ;
        }
    }

    //获取 access_token //https://www.dservie.cn/myWeb/index.php/KeplerApi/KeplerApi/token
    public function token( $opt='new'   )  //
    {

        if( $opt =='new' ){
            $grant_type='password' ;
        }else{
            $grant_type='refresh_token' ;
        }

        $url = 'https://kploauth.jd.com/oauth/token?grant_type='.$grant_type.'&app_key='.$this->app_key.
            '&app_secret='.$this->app_secret.'&state=0&username='.$this->username.'&password='.md5($this->password) ;  //  echo $url ;  exit ;
        $kpl = self::send( $url );

        if( $kpl['err']==0 )
        {
            if( $kpl['data']['code'] == 0 )
            { //
                $data =  $kpl['data'] ;
                $db = M("keplertoken"); // 实例化User对象
                // 要修改的数据对象属性赋值
                $data['id']       = 1;
                $data['out_time'] = date('Y-m-d H:i:s' , intval( $data['time']/1000) + $data['expires_in'] ) ;
                $data['utime']    = date('Y-m-d H:i:s' ) ;
                $read = $db->where("id=1")->find() ;  //print_r( $read ) ; exit ;
                if( $read )
                {
                    $db->where('id=1')->save($data); // 根据条件保存修改的数据
                }
                else
                {
                    $db->add($data);
                }
                return $data ;
            }
            else
            {
                $errtxt =  $kpl['data']['code'].':'.self::sys_code( $kpl['data']['code'] ) ;
            }
        }
        else
        {
            $errtxt = "获取token失败，请检查您的 appkey 等参数是否正确！" ;
        }
        return $errtxt ;
    }


    public function  access_token(){

        $db = M("keplertoken"); // 实例化User对象
        $data = $db->where("id=1")->find(); // 根据条件保存修改的数据
        if( is_array( $data ) ){  //echo ' kpl token--> ' ; print_r( $data ) ;
            //已有token数据

            //    $nowtime = time() ;
            //    $outtime = intval( $data['time']/1000) + $data['expires_in'] - 600  ;   //       echo time(). ' now --->>  out '.$outtime.'   '.date( 'Y-m-d H:i:s' , $nowtime ).'  now --> out '.date( 'Y-m-d H:i:s' , $outtime );
            //   if( $nowtime > $outtime ){    //已过期 或 将要过期
            //        return self::token('ref') ;
            // }else{
            return $data ;
            // }
        }else{
            //没有token 重新获取
            return  self::token() ;
        }

    }

    public function  api( $apiname , $param_json='{}' ,$sing='' ){

        $doii = 0 ;
        do{
            $is_err = 0 ; //是否有错误
            $token = self::access_token() ;
            if( ! is_array( $token ) ){  //出错了
                exit( $token )  ;
            }
            $timestamp = date( 'Y-m-dH:i:s') ;
            $x_sing = '' ;
            if( !empty($sing) ) {
                $x_sing = '&sing='.$sing ;
            }
            $apiurl ='https://router.jd.com/api?method='.$apiname.'&app_key='.$this->app_key.'&access_token='.$token['access_token'].
                '&timestamp='.$timestamp.'&v=1.0&format=json&param_json='.$param_json.$x_sing ;      //     echo '【 ' .$apiurl.'】' ;
            $tk = self::send( $apiurl ) ;

            if( isset( $tk['data']['errorResponse']['code'] )){
                $recode=$tk['data']['errorResponse']['code'] ;
                if(  in_array( $recode ,array( 1003 , 1004 ,2011 ,2012 ) )   ){

                    self::token() ;
                    $is_err = 1 ; //token 有问题 已更新token
                }

                if( $recode > 0 ){
                    $log_model = new LogModel();
                    $log_model->addLog( $recode.':'.$tk['data']['errorResponse']['msg'].'  '.date('Y-m-d H:i:s')   , time() ,'kpl' );
                }
            }
            $doii++ ;

            if( $doii>6) break;

        } while( $is_err ) ;
        $res = $tk;
        $responedata = str_replace('.','_',$apiname).'_response';
        if (isset($res['data'][$responedata]['result'])) return $res['data'][$responedata]['result'];
        $errlog['resultCode'] = $res['data'][$responedata]['resultCode'];
        $errlog['resultMessage'] = $res['data'][$responedata]['resultMessage'];
        $errlog['param'] = $param_json;
        $logClass = new Log();
        $logClass->writeLog(json_encode($errlog), $apiname);
        if ($res['err'] && $res['istimeout']) return 0;   //请求超时
    }


    public function getJdAddress($level = 1, $parentid = 0)
    {
        if ($level == 1) {
            $apiname = 'biz.address.allProvinces.query';
        } elseif($level == 2) {
            $apiname = 'biz.address.citysByProvinceId.query';
        } elseif ($level == 3) {
            $apiname = 'biz.address.countysByCityId.query';
        } elseif ($level == 4) {
            $apiname = 'biz.address.townsByCountyId.query';
        }
        if ($parentid) {
            $data['id'] = $parentid;
            $data = json_encode($data);
            $address = $this->api($apiname,$data);
        } else {
            $address = $this->api($apiname);
        }
        if (!$address) $address = [];
        return $address;
    }

    public function verifyJdAddress($provinceId, $cityId,$countyId = 0,$townId = 0)
    {
        $apiname = 'jd.kpl.open.area.checkarea';
        $data['provinceId'] = $provinceId;
        $data['cityId'] = $cityId;
        $data['countyId'] = $countyId;
        $data['townId'] = $townId;
        $data = json_encode($data);
        $res = $this->api($apiname,$data);
        if ($res) return true;
        return false;
    }

    /**
     * @breif 验证商品是否可售
     * @param int $skuid
     * @return bool
     */
    public function skuCheck($skuid = '')
    {
        if (!$skuid) return false;
        $data['skuIds'] = $skuid;
        $data = json_encode($data);
        $apiname = 'biz.product.sku.check';
        $res = $this->api($apiname,$data);
        if (!$res) return false;
        if ($res[0]['saleState'] != 1) return false;
        return true;
    }

    /**
     * @beif 获取商品实时价格
     * @param int $skuid
     * @return bool
     */
    public function getSkuPrice($skuid = 0)
    {
        if (!$skuid) return false;
        $data['sku'] = $skuid;
        $data = json_encode($data);
        $apiname = 'biz.price.sellPrice.get';
        $res = $this->api($apiname,$data);
        if ($res) {
            return $res[0]['price'];
        } else {
            return false;
        }
    }

    public function hasSkuNumCanSale($skuid, $area,$num)
    {
        if (!$skuid) return false;
        $data['skuNums'][] =[
            'skuId'=>$skuid,
            'num'=>$num
        ];
        $data['area'] = $area;
        $data = json_encode($data);
        $apiname = 'biz.stock.fororder.batget';
        $res = $this->api($apiname,$data);
        if (!$res) return false;
        if ($res[0]['stockStateId'] == 34 ||$res[0]['stockStateId'] == 36 ) return false;
        return true;
    }

    public function submitKerperOrder($param)
    {
        $data = json_encode($param);
        $apiname = 'biz.order.unite.submit';
        $res = $this->api($apiname,$data);
        return $res;
    }

    public function confirmKerperOrder($jdkerperorder)
    {
        $data['jdOrderId'] = $jdkerperorder;
        $apiname = 'biz.order.occupyStock.confirm';
        $data = json_encode($data);
        $res = $this->api($apiname,$data);
        return $res;
    }

    public function orderTrack($jdkerperorder)
    {
        $data['jdOrderId'] = $jdkerperorder;
        $apiname = 'biz.order.orderTrack.query';
        $data = json_encode($data);
        $res = $this->api($apiname,$data);
        return $res;
    }

    /**
     * @breif 支持的服务类型
     * @param $jdkerperorder
     * @param $skuid
     * @return bool|int     //可返回的数量
     */
    public function afterSaleAvailableNumber($jdkerperorder, $skuid)
    {
        $data['param']['jdOrderId'] = $jdkerperorder;
        $data['param']['skuId'] = $skuid;
        $data = json_encode($data);
        $apiname = 'biz.afterSale.availableNumberComp.query';
        $res = $this->api($apiname,$data);
        return $res;

    }

    /**
     * @breif 可支持的售后 退货(10)、换货(20)、维修(30)
     * @param $jdkerperorder
     * @param $skuid
     * @return bool|int
     */
    public function afterSaleAvailableRefund($jdkerperorder, $skuid)
    {
        $data['param']['jdOrderId'] = $jdkerperorder;
        $data['param']['skuId'] = $skuid;
        $data = json_encode($data);
        $apiname = 'biz.afterSale.customerExpectComp.query';
        $res = $this->api($apiname,$data);
        return $res;

    }

    public function afterSaleReturnJdWay($jdkerperorder, $skuid)
    {
        $data['param']['jdOrderId'] = $jdkerperorder;
        $data['param']['skuId'] = $skuid;
        $data = json_encode($data);
        $apiname = 'biz.afterSale.wareReturnJdComp.query';
        $res = $this->api($apiname,$data);
        return $res;
    }

    public function afterSaleApply($param)
    {
        $apiname = 'biz.afterSale.afsApply.create';
        $data = json_encode($param);
        $res = $this->api($apiname,$data);
        return $res;
    }

    public function jdOrderQuery($jdOrderId)
    {
        $apiname = 'biz.order.jdOrder.query';
        $param['jdOrderId'] = $jdOrderId;
        $data = json_encode($param);
        $res = $this->api($apiname,$data);
        return $res;
    }

    public function jdOrderQueryByOrderSn($ordersn)
    {
        $apiname = 'biz.order.jdOrderIDByThridOrderID.query';
        $param['thirdOrder'] = $ordersn;
        $data = json_encode($param);
        $res = $this->api($apiname,$data);
        return $res;
    }

    public function cancelKerperOrder($jdorder)
    {
        $apiname = 'biz.order.cancelorder';
        $param['jdOrderId'] = $jdorder;
        $data = json_encode($param);
        $res = $this->api($apiname,$data);
        return $res;
    }

    public function getbalanceprice()
    {
        $apiname = 'biz.price.balance.get';
        $param['payType'] = 4;
        $data = json_encode($param);
        $res = $this->api($apiname,$data);
        return $res;
    }

    public function selectjdorderquery($jdorder)
    {
        $apiname = 'jd.kpl.open.selectjdorder.query';
        $param['jdOrderId'] = $jdorder;
        $param['queryExts'] = 'jdOrderState';
        $data = json_encode($param);
        $res = $this->api($apiname,$data);
        return $res;
    }

    public function getjdKerperPushData($type)
    {
        $apiname = 'biz.message.get';
        $param['type'] = $type;
        $data = json_encode($param);
        $res = $this->api($apiname,$data);
        return $res;
    }
}