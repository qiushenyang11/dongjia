<?php
/**
 * Created by PhpStorm.
 * User: vanillachocola
 * Date: 2018/3/19
 * Time: 下午3:43
 */

namespace WeChat\Controller;




use Server\Order;
use Think\Controller;
use Server\WeChatRedis;
use WeChat\Model\GoodsModel;

class ExportController extends Controller{

    //e.g: ?key=2018,03,05
    public function dailyProduct(){
        $get = I('get.key');
        $temp = explode(',',$get);
        $key = '#PvUv,'.$temp[0].'-'.$temp[1].'-'.$temp[2];
        $startTime = strtotime($temp[0].'/'.$temp[1].'/'.$temp[2])-1;
        $endTime = $startTime+24*3600;

        $redis = new WeChatRedis();
        $data = $redis->r->hGetAll($key);

        $this->excelProduct($data, $startTime, $endTime);
    }

    //e.g: ?key=2018,03,05
    public function dailyChannel(){
        $get = I('get.key');
        $temp = explode(',',$get);
        $key = '#Channel,'.$temp[0].'-'.$temp[1].'-'.$temp[2];
        $redis = new WeChatRedis();
        $data = $redis->r->hGetAll($key);

        $this->excelChannel($data);
    }

    //e.g: ?key=2018,03,05
    public function dailyScene(){
        $get = I('get.key');
        $temp = explode(',',$get);
        $key = '#SceneId,'.$temp[0].'-'.$temp[1].'-'.$temp[2];
        $redis = new WeChatRedis();
        $data = $redis->r->hGetAll($key);

        $this->excelScene($data);
    }

    // e.g: ?key=2018,11(周数)
    public function weeklyProduct(){
        $get = I('get.key');
        $temp = explode(',',$get);
        $key = '#PvUv,'.$temp[0].',week,'.$temp[1];

        $weekStart = $this->getWeekStart($temp[0], $temp[1]);
        $startTime = $weekStart - 1;
        $endTime = $startTime + 7*24*3600;

        $redis = new WeChatRedis();
        $data = $redis->r->hGetAll($key);

        $this->excelProduct($data, $startTime, $endTime);
    }

    // e.g: ?key=2018,11(周数)
    public function weeklyChannel(){
        $get = I('get.key');
        $temp = explode(',',$get);
        $key = '#Channel,'.$temp[0].',week,'.$temp[1];
        $redis = new WeChatRedis();
        $data = $redis->r->hGetAll($key);

        $this->excelChannel($data);
    }
    // e.g: ?key=2018,11(周数)
    public function weeklyScene(){

        $get = I('get.key');
        $temp = explode(',',$get);
        $key = '#SceneId,'.$temp[0].',week,'.$temp[1];
        $redis = new WeChatRedis();
        $data = $redis->r->hGetAll($key);

        $this->excelScene($data);

    }

    public function getOthers(){
        $get = I('get.key');
        $temp = explode(',',$get);
        $key = '#PvUv,'.$temp[0].'-'.$temp[1].'-'.$temp[2];
        $redis = new WeChatRedis();
        $res = $redis->r->hGet($key, 'totalSum');

        echo $res;
    }

    private function excelProduct($data, $startTime, $endTime){
        $count = 0;
        $productIds = [];
        $model = new GoodsModel();
        foreach ($data as $key => $row) {
            $end = stripos($key, '&');
            if ($end > 0) {
                $key = substr($key, 0, $end);
            }
            $tmp = explode('/',$key);
            $type = $tmp[1];
            $guanjiaid = $tmp[2];
            $id = $tmp[3];
            if ($type == 'servicesDetail' || $type == 'productDetail')
            {
                if ($guanjiaid == 'undifined') {
                    $info = $model->getOneProduct($id);
                    $guanjiaid = $info['guanjiaid'];
                    $key = '/'.$type.'/'.$guanjiaid.'/'.$id;
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

        //合并相同产品数据
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

        //拿到产品名称
        unset($excelData);
        $excelData = [];
        $j = 0;
        foreach ($temp as $id=>$row) {
            $excelData[$j][0] = $model->getOneProduct($id)['name'];
            $excelData[$j][1] = $row['path'];
            $excelData[$j][2] = $row['pv'];
            $excelData[$j][3] = $row['uv'];
            $excelData[$j][8] = $id;
            $j++;
            $productIds[] = $id;
        }

        //拿到订单信息
        $orderClass = new Order();
        $excelData = $orderClass->staticsOrdersByProductids($productIds, $startTime, $endTime,$excelData);

        //统计合计数据
        $totalpv = 0;
        $totaluv = 0;
        $totalOrder = 0;
        $totalOrderUser = 0;
        foreach ($excelData as $row) {
            $totalpv += $row[2];
            $totaluv += $row[3];
            $totalOrder +=$row[4];
            $totalOrderUser +=$row[5];
        }
        $excelData[]=[
            '',
            '合计',
            $totalpv,
            $totaluv,
            $totalOrder,
            $totalOrderUser
        ];
        $arr=["产品名称","产品路径","PV","UV","订单数量","订单用户数"];
        array_unshift($excelData, $arr);
        $this->excelExport($excelData,'单个产品PVUV统计');
    }

    private function excelChannel($data){
//        $get = I('get.key');
//        $temp = explode(',',$get);
//        $key = '#Channel,'.$temp[0].'-'.$temp[1].'-'.$temp[2];
//        $redis = new WeChatRedis();
//        $data = $redis->r->hGetAll($key);
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

    private function excelScene($data){
//        $get = I('get.key');
//        $temp = explode(',',$get);
//        $key = '#SceneId,'.$temp[0].'-'.$temp[1].'-'.$temp[2];
//        $redis = new WeChatRedis();
//        $data = $redis->r->hGetAll($key);
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

    /**
     * 获取某年第几周的开始日期和结束日期
     * @param int $year, int $week = 1
     * @return array $weekday
     */
    public function getWeekStart($year, $week = 1){
        $year_start = mktime(0,0,0,1,1,$year);
//        $year_end = mktime(0,0,0,12,31,$year);

        // 判断第一天是否为第一周的开始
        if (intval(date('W',$year_start))===1){
            $start = $year_start;//把第一天做为第一周的开始
        }else{
            $week++;
            $start = strtotime('+1 monday',$year_start);//把第一个周一作为开始
        }

        // 第几周的开始时间
        if ($week===1){
            $weekStart = $start;
        }else{
            $weekStart = strtotime('+'.($week-0).' monday',$start);
        }

        // 第几周的结束时间
//        $weekday['end'] = strtotime('+1 sunday',$weekday['start']);
//        if (date('Y',$weekday['end'])!=$year){
//            $weekday['end'] = $year_end;
//        }
        return $weekStart;
    }


    private function excelExport($data=[],$filename="默认列表"){
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
}