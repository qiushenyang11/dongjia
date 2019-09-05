<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/3/19
 * Time: 18:02
 */

namespace Server;
use Operation\Model\BannerModel;
use WeChat\Model\GoodsModel;


class Banner
{
  public function addBanner($param){
        $title = $param['title'];
        $pic = $param['pic'];
        $urltype = $param['urltype'];
        $urlvalue = $param['urlvalue'];
        $frame = $param['frame'];
        $showstarttime = $param['showstarttime'];
        $showendtime = $param['showendtime'];
        $ishow = $param['ishow'];
        $created_date = $param['created_date'];
        $data=array();
        $data['title']=$title;
        $data['pic']=$pic;
        $data['urltype']=$urltype;
        $data['urlvalue']=$urlvalue;
        $data['frame']=$frame;
        $data['showstarttime']=$showstarttime;
        $data['showendtime']=$showendtime;
        $data['ishow']=$ishow;
        $data['created_date']=$created_date;
        $BannerModel = new BannerModel();
        $res=$BannerModel->addBanner($data);
        if($res){
            return $res;
        }else{
            return false;
        }
  }

    public function getShowBanner()
    {
        $bannerModel = new BannerModel();
        $res = $bannerModel->getShowBanner();
        $data = [];
        $productModel = new GoodsModel();
        foreach ($res as $row)
        {
            if ($row['urltype'] == 1) {
                $productid = $row['urltvalue'];
                $guanjiaid = $productModel->getGuanJiaIdByProductid($productid);
                $tempsrc = C('UPLOADURL').$row['pic'];
                $data[]=[
                    'url'=>getUrl().'/myWeb/WeChat/WeChatGuanJia/index?#/servicesDetail/'.$guanjiaid.'/'.$productid,
                    'src'=>$tempsrc,
                    'guanjiaid'=> $guanjiaid,
                    'productid'=>$productid,
                    'type'=>$row['urltype']
                ];
            } elseif ($row['urltype'] == 2) {
                $guanjiaid = $row['urltvalue'];
                $tempsrc = C('UPLOADURL').$row['pic'];
                $data[]=[
                    'url'=>getUrl().'/myWeb/WeChat/WeChatGuanJia/index?#/manager/'.$guanjiaid,
                    'src'=>$tempsrc,
                    'guanjiaid'=> $guanjiaid,
                    'productid'=>0,
                    'type'=>$row['urltype']
                ];
            } else {
                $url = $row['urltvalue'];
                $tempsrc = C('UPLOADURL').$row['pic'];
                $data[]=[
                    'url'=>$url,
                    'src'=>$tempsrc,
                    'type'=>$row['urltype']
                ];
            }
        }
        return $data;
    }
}