<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/9/29
 * Time: 11:29
 */

namespace WeChat\Controller;



use Server\Goods;
use Server\JdApi;
use Server\Order;
use Server\Scene;
use Server\SmsMessage;
use Swoole\Redis;
use Think\Controller;
use Server\WeChatRedis;
use WeChat\Model\GoodsModel;

class TestController extends Controller
{
    public function excel(){
        $get = I('get.key');
        $temp = explode(',',$get);
        $key = '#PvUv,'.$temp[0].'-'.$temp[1].'-'.$temp[2];
        $startTime = strtotime($temp[0].'/'.$temp[1].'/'.$temp[2])-1;
        $endTime = $startTime+24*3600;
        $redis = new WeChatRedis();
        $data = $redis->r->hGetAll($key);
        $count = 0;
        $productIds = [];
        foreach ($data as $key => $row) {
            $type = explode('/',$key)[1];
            $id = explode('/',$key)[3];
            if ($type == 'servicesDetail' || $type == 'productDetail')
            {
                $json = $row;
                $obj = json_decode($json);
                $pv = $obj->Pv;
                $uv = $obj->Uv;
                $path = $key;
                $model = new GoodsModel();
                $info = $model->getOneProduct($id);
                $excelData[$count][0] = $info['name'];
                $excelData[$count][1] = $path;
                $excelData[$count][2] = $pv;
                $excelData[$count][3] = $uv;
                $excelData[$count][8] = $id;
                $count ++;
                $productIds[] = $id;
            }
        }
        $orderClass = new Order();
        $excelData = $orderClass->staticsOrdersByProductids($productIds, $startTime, $endTime,$excelData);
        $arr=["产品名称","产品路径","PV","UV","订单数量","订单用户数"];
        array_unshift($excelData, $arr);
        $this->excelExport($excelData,'PVUV统计');
    }

    public function excelNew(){
        $get = I('get.key');
        $duandian = I('get.duandian');
        $temp = explode(',',$get);
        $key = '#PvUv,'.$temp[0].'-'.$temp[1].'-'.$temp[2];
        $startTime = strtotime($temp[0].'/'.$temp[1].'/'.$temp[2])-1;
        $endTime = $startTime+24*3600;
        $redis = new WeChatRedis();
        $data = $redis->r->hGetAll($key);
        $count = 0;
        $productIds = [];
        if ($duandian == 1) {
            dump($data);die;
        }
        $model = new GoodsModel();
        foreach ($data as $key => $row) {
            $end = stripos($key, '&');
            if ($end>0) {
                $key = substr($key,0,$end);
            }
            $type = explode('/',$key)[1];
            $id = explode('/',$key)[3];
            if ($type == 'servicesDetail' || $type == 'productDetail')
            {
                if (stripos($key,'undefined')) {
                    $info = $model->getOneProduct($id);
                    $guanjiaid = $info['guanjiaid'];
                    $key = str_replace('undefined',$guanjiaid,$key);
                 }
                $json = $row;
                $obj = json_decode($json);
                $pv = $obj->Pv;
                $uv = $obj->Uv;
                $path = $key;
                $excelData[$count][1] = $path;
                $excelData[$count][2] = $pv;
                $excelData[$count][3] = $uv;
                $excelData[$count][8] = $id;
                $count ++;
            }
        }
        if ($duandian == 2) {
            dump($excelData);die;
        }
        $temp= [];

        foreach ($excelData as $row) {
            $id = $row[8];
            if (!isset($temp[$id])) {
                $temp[$id]['pv'] = $row[2];
                $temp[$id]['uv'] = $row[3];
                $temp[$id]['path'] = $row[1];
            } else {
                $temp[$id]['pv'] = $temp[$id]['pv']+$row[2];
                $temp[$id]['uv'] = $temp[$id]['uv']+$row[3];
            }
        }

        unset($excelData);
        $excelData = [];
        $j = 0;
        foreach ($temp as $id=>$row) {
            $excelData[$j][0]=$model->getOneProduct($id)['name'];
            $excelData[$j][1] = $row['path'];
            $excelData[$j][2] = $row['pv'];
            $excelData[$j][3] = $row['uv'];
            $excelData[$j][8] = $id;
            $j++;
            $productIds[] = $id;
        }

        $orderClass = new Order();
        $excelData = $orderClass->staticsOrdersByProductids($productIds, $startTime, $endTime,$excelData);
        $totalpv = 0;
        $totaluv = 0;
        $totalorder = 0;
        $totaloruserorder = 0;
        foreach ($excelData as $row) {
            $totalpv+= $row[2];
            $totaluv+= $row[3];
            $totalorder+=$row[4];
            $totaloruserorder+=$row[5];
        }
        $excelData[]=[
            '',
            '',
            $totalpv,
            $totaluv,
            $totalorder,
            $totaloruserorder
        ];
        $arr=["产品名称","产品路径","PV","UV","订单数量","订单用户数"];
        array_unshift($excelData, $arr);
        $this->excelExport($excelData,'PVUV统计');
    }

    public function excelChannel(){
        $get = I('get.key');
        $temp = explode(',',$get);
        $key = '#Channel,'.$temp[0].'-'.$temp[1].'-'.$temp[2];
        $redis = new WeChatRedis();
        $data = $redis->r->hGetAll($key);
        $count = 0;
        foreach ($data as $key => $row) {
            $json = $row;
            $obj = json_decode($json);

            $channelName = $key;
            $pv = $obj->PV;
            $order = $obj->Order;
            $free = $obj->Free;

            $excelData[$count][0] = $channelName;
            $excelData[$count][1] = $pv;
            $excelData[$count][2] = $order;
            $excelData[$count][3] = $free;
            $count ++;
        }
        $arr=["渠道名称","PV","付费订单数","免费订单数"];
        array_unshift($excelData, $arr);
        $this->excelExport($excelData,'渠道统计');
    }

    public function excelScene(){
        $get = I('get.key');
        $temp = explode(',',$get);
        $key = '#SceneId,'.$temp[0].'-'.$temp[1].'-'.$temp[2];
        $redis = new WeChatRedis();
        $data = $redis->r->hGetAll($key);
        $count = 0;
        foreach ($data as $key => $row) {
            $json = $row;
            $obj = json_decode($json);

            $sceneName = $key;
            $pv = $obj->PV;
            $order = $obj->Order;
            $free = $obj->Free;

            $excelData[$count][0] = $sceneName;
            $excelData[$count][1] = $pv;
            $excelData[$count][2] = $order;
            $excelData[$count][3] = $free;
            $count ++;
        }
        $arr=["场景名称","PV","付费订单数","免费订单数"];
        array_unshift($excelData, $arr);
        $this->excelExport($excelData,'场景统计');
    }

    public function getOthers(){
        $get = I('get.key');
        $temp = explode(',',$get);
        $key = '#PvUv,'.$temp[0].'-'.$temp[1].'-'.$temp[2];
        $redis = new WeChatRedis();
        $res = $redis->r->hGet($key, 'totalSum');

        echo $res;
    }

    public function excelExport($data=[],$filename="默认列表"){
        vendor("excel.PHPExcel");
        $objPHPExcel=new \PHPExcel();
        if(empty($data)){
            return;
        }
        $pColumnIndex = 0;

        foreach ($data as $key=>$row){
            $num=$key+1;

            foreach ($row as $row1){
                if($pColumnIndex<26){
                    $charIndex=chr(65+$pColumnIndex);
                }else if($pColumnIndex<702){
                    $charIndex=chr(64 + ($pColumnIndex / 26)) . chr(65 + $pColumnIndex % 26);
                }else{
                    $charIndex=chr(64 + (($pColumnIndex - 26) / 676)) . chr(65 + ((($pColumnIndex - 26) % 676) / 26)) . chr(65 + $pColumnIndex % 26);
                }
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue($charIndex.$num," ".$row1);
                $pColumnIndex++;
            }
            $pColumnIndex=0;

        }
        $filename=$filename."(".Date("Y-m-d").")".".xls";
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.$filename.'');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        die;
    }
    public function tts(){
        $this->display('aa');
    }
}