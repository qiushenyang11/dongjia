<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/3/16
 * Time: 16:35
 * Url: http://test.dservie.cn/myWeb/Operation/OperationBanner
 */
namespace Operation\Controller;
use Server\Banner;
use WeChat\Model\GuanJiaModel;
use Operation\Model\BannerModel;
use WeChat\Model\GoodsModel;

class OperationBannerController extends OperationBaseController
{

//  落地页相关数据搜索

    public function serachValeByType()
    {
        $type = I("get.type",1);            //type 1产品 2管家 3链接 4二级分类
        $value = I('get.value','');
        if (!$value) return false;
        $where = [];
        $data = [];
        if ($type == 4) {
            $where['type'] = 2;
            $where['level'] = 2;
            if (is_numeric($value)) {
                $where['id'] = $value;
            } else {
                $where['name'] = ['like','%'.$value.'%'];
            }
            $result = M('category')->field('id,name')->where($where)->select();
            foreach ($result as $row) {
                $data[]=[
                    'id'=>$row['id'].','.$row['name'].','.$type,
                    'text'=>$row['name']
                ];
            }
        } elseif ($type ==2) {
            if (is_numeric($value)) {
                $where['id'] = $value;
            } else {
                $where['guanjianame'] = ['like','%'.$value.'%'];
            }
            $result = M('guanjia')->field('id,guanjianame')->where($where)->select();
            foreach ($result as $row) {
                $data[]=[
                    'id'=>$row['id'].','.$row['guanjianame'].','.$type,
                    'text'=>$row['guanjianame']
                ];
            }
        } elseif ($type == 1) {
            if (is_numeric($value)) {
                $where['id'] = $value;
            } else {
                $where['name'] = ['like','%'.$value.'%'];
            }
            $result = M('product')->field('id,name')->where($where)->select();
            foreach ($result as $row) {
                $data[]=[
                    'id'=>$row['id'].','.$row['name'].','.$type,
                    'text'=>$row['name']
                ];
            }
        } elseif ($type == 5) {             //供应商
            if (is_numeric($value)) {
                $where['id'] = $value;
            } else {
                $where['suppliershort'] = ['like','%'.$value.'%'];
            }
            $result = M('supplier')->field('id,suppliershort')->where($where)->select();
            foreach ($result as $row) {
                $data[]=[
                    'id'=>$row['id'].','.$row['suppliershort'].','.$type,
                    'text'=>$row['suppliershort']
                ];
            }
        }
        echo json_encode($data);

    }




    //banner 搜索

    public function bannerSearch()
    {

        $p=(String)$_GET["p"]==0?1:$_GET["p"];

             $type= intval( I("post.type",'') );

        $condition= I("post.condition",'');

//        $title=I("post.title",'');
//
//        $id=I("post.id",'');

        $timesearch=I("post.timesearch",'');

        $ishow=intval(I("post.ishow",''));

        if($type||$condition||$timesearch||$ishow)
        {
               $where = array() ; 
               
        if( $condition  ) {
            if($type==1)
            {
              // $whereArray["title"]=['like',"%$condition%"];
                $where[] = ' title like "%'.htmlspecialchars( $condition ).'%" ' ; 
            }
            if($type==2)
            {
              //  $whereArray["id"]=$condition;
               $where[] = ' id='.intval( $condition ) ; 
            }
            //城市
            if($type==3)
            {
             //   $whereArray["id"]=$condition;   
               $keyw = htmlspecialchars( $condition) ; 
               $where[] = ' ((city_type=1 AND servicecity LIKE "%'.$keyw.'%" ) OR ( city_type=2 AND servicecity NOT LIKE "%'.$keyw.'%" ))  ' ;  
            }
        }    
            
            
            if($ishow>0)
            {
               // $whereArray["ishow"]= intval( $ishow ) ;
                $where[] = 'ishow='. $ishow ;
            }

            if($timesearch)
            {
                $aimTime=strtotime($timesearch);
              //  $whereArray["showstarttime"]=array("elt",$aimTime);
              //  $whereArray["showendtime"]=array("gt",$aimTime);
               $where[] = 'showstarttime<='.$aimTime ; 
               $where[] =   'showendtime>='.$aimTime ; 
               
            }
            //$whereArray['isdelete'] = 0;
               $where[] = 'isdelete=0' ; 
           
              $whereSql = implode(  ' AND ',$where ) ;  //echo ' where --> '.$whereSql; 
          //-------------------------------------          
            
            $Banner= new BannerModel();

            $count=$Banner->where($whereSql)->count();

            $Page=new \Think\Page($count,20);

            $show = $Page->show();

            $result=$Banner->where($whereSql)->limit($Page->firstRow.','.$Page->listRows)->select();


            foreach ($result as $key => $row) {
                $result[$key]['pic'] = C("UPLOADURL").$row['pic'];
            }
            $Page->nowPageage=$p;
            $first=$Page->firstRow+1;
            $rest=($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
            $end = $first + $rest-1;
            $all=$first.'-'.$end;


            $this->assign('page',$show);

            $this->assign('count',$count);

            $this->assign('all',$all);

            $this->assign('totalPages',$Page->totalPages);

            $this->assign('nowPage',$Page->nowPage);

            $this->assign('result',$result);

            $this->assign('type',$type);

            $this->assign('condition',$condition);

            $this->assign('ishow',$ishow);

            $this->assign('timesearch',$timesearch);

            $this->display('OperationBanner/index');

        }
        else
        {
            response("参数不能为空",0);
        }


    }

    //添加Banner
     public function addBannerInfo()
     {


         $title=I("post.title",'');

         $pic=I("post.pic",'');

         $urltype=I("post.urltype",'');

         $urltvalue=I("post.urltvalue",'');

         $urltname=I("post.urltname",'');

         $frame=I("post.frame",'');

         $showstarttime=strtotime(I("post.showstarttime",''));

         $showendtime=strtotime(I("post.showendtime",''));

         $ishow=I("post.ishow",'');

         $servicecity = I('post.servicecity', '');
         $city_type   = I('post.city_type'  , '');
         

         if($frame&&$title&&$pic&&$urltvalue&&$urltype&&$showendtime&&$showstarttime&&$ishow&&$urltname && $city_type)
         {
             $Banner= new BannerModel();

      //          $fBack=$this->checkTimeIsOK($showstarttime,$showendtime,$frame);//
      //       if($fBack["status"]==1)
      //       {
                 $data["title"]=$title;

                 $data["pic"]=$pic;

                 $data["urltype"]=$urltype;

                 $data["frame"]=$frame;

                 $data["showstarttime"]=$showstarttime;

                 $data["showendtime"]=$showendtime;

                 $data["urltvalue"]=$urltvalue;

                 $data["urltname"]=$urltname;

                 $data["ishow"]= $ishow;

                 $data['servicecity'] = $servicecity;
                 $data['city_type']   = $city_type; 
                 $data['modified_date'] = time() ; 


                 $res=$Banner->addOneBanner($data);

                 if($res)
                 {
                     response("添加banner成功",1);
                 }
                 else
                 {
                     response("添加banner失败",0);
                 }
    //         }
    //         else
    //         {
    //             response("帧位与ID为".$fBack["conflictId"]."的banner冲突,请重新设置",0);
    //         }


         }
         else
         {
             response("参数不能为空",0);
         }



     }


     public function editBanner()
     {
         $id=I("get.id",'');
         if($id)
         {
             $Banner= new BannerModel();

             $rst=$Banner->findBannerOne($id);
             if($rst)
             {
//                 dump($rst);
                 $this->assign("rst",$rst);

             }
             else
             {
                 response("获取失败",0);
             }
         }
         else
         {
             response("参数不能为空",0);
         }
         $this->display();
     }

     //修改banner
     public function updateBanner()
     {
//         $json = file_get_contents('php://input');
//
//         $array = json_decode($json,true);

         $id=I("post.id",'');

         $title=I("post.title",'');

         $pic=I("post.pic",'');

         $urltype=I("post.urltype",'');

         $urltname=I("post.urltname",'');

         $urltvalue=I("post.urltvalue",'');

         $urlname = I('post.urlname', '');

         $frame=I("post.frame",'');

         $showstarttime=I("post.showstarttime",'');

         $showendtime=I("post.showendtime",'');

         $ishow=I("post.ishow",'');

         $servicecity = I('post.servicecity','');
         $city_type   = I('post.city_type','');

         $data=array();

         if($title)
         {
            $data["title"]=$title;
         }
         if($pic)
         {
             $data["pic"]=$pic;
         }
         if($urltype)
         {
             $data["urltype"]=$urltype;
         }
         if($urltvalue)
         {
             $data["urltvalue"]=$urltvalue;
         }if($urltname){

            $data["urltname"]=$urltname;
         }
         if($frame)
         {
             $data["frame"]=$frame;
         }
         if($showstarttime)
         {
             $showstarttime = strtotime($showstarttime);
             $data["showstarttime"]=$showstarttime;
         }
         if($showendtime)
         {
             $showendtime = strtotime($showendtime);
             $data["showendtime"]=$showendtime;
         }
         if($ishow)
         {
             $data["ishow"]=$ishow;
         }
         if ($urlname)
         {
             $data['urlname'] = $urlname;
         }

         $data['servicecity'] = $servicecity;
         $data['city_type']   = $city_type;
         
         $data['modified_date'] = time() ; 
/*
         if((array_key_exists("showstarttime",$data))||(array_key_exists("showendtime"))||$frame)
         {
             $fBack=$this->checkTimeIsOK($showstarttime,$showendtime,$frame,$id);//


             if($fBack["status"]==1)
             {

             }
             else
             {
                 response("帧位与ID为".$fBack["conflictId"]."的banner冲突,请重新设置",0);
             }
         }
*/
         if($id)
         {
             $Banner= new BannerModel();

             $rst=$Banner->updateBanner($id,$data);
                 response("修改banner成功",1);

         }
         else
         {
             response("参数不能为空",0);
         }
     }
     //Banner 下线
     public function delBanner()
     {
         $id=I("post.id",'');

         if($id)
         {
             $data=array();

             $data["id"]=$id;

             $Banner= new BannerModel();

             $res=$Banner->downLine($data);

             if($res)
             {
                 response("删除banner成功",1);
             }
             else
             {
                 response("删除banner失败",0);
             }

         }
         else
         {
             response("参数不能为空",0);
         }


     }
     //显示banner
     public function index()
     {
         $p=(String)$_GET["p"]==0?1:$_GET["p"];

         $Banner = new BannerModel();

         $where['isdelete'] = 0;

         $count=$Banner->where($where)->count();

         $Page=new \Think\Page($count,20);

         $show = $Page->show();

         $Page->nowPageage=$p;
         $first=$Page->firstRow+1;
         $rest=($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
         $end = $first + $rest-1;
         $all=$first.'-'.$end;



         $result=$Banner->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
         foreach ($result as $key => $row) {
             $result[$key]['pic'] = C("UPLOADURL").$row['pic'];
             if ($row['urltype'] == 1) {
                 $id = $row['urltvalue'];
                 $result[$key]['urltvalue'] = M('product')->where(['id'=>$row['urltvalue']])->getField('name');
                 $result[$key]['urltvalue'] = $result[$key]['urltvalue']."(".$id.")";
             } elseif ($row['urltype'] == 2) {
                 $id = $row['urltvalue'];
                 $result[$key]['urltvalue'] = M('guanjia')->where(['id'=>$row['urltvalue']])->getField('guanjianame');
                 $result[$key]['urltvalue'] = $result[$key]['urltvalue']."(".$id.")";
             } elseif ($row['urltype'] == 4) {
                 $id = $row['urltvalue'];
                 $result[$key]['urltvalue'] = M('category')->where(['id'=>$row['urltvalue']])->getField('name');
                 $result[$key]['urltvalue'] = $result[$key]['urltvalue']."(".$id.")";
             }
         }
         $this->assign('page',$show);
         $this->assign('count',$count);
         $this->assign('all',$all);
         $this->assign('totalPages',$Page->totalPages);
         $this->assign('nowPage',$Page->nowPage);
         $this->assign('result',$result);
         $this->display();
     }

     //判断是不是重复--------包含前置情况
     private function checkTimeIsOK($showstartime,$showendtime,$frame,$nowid = 0)
     {
         //搜寻有没有这一frame
         $Banner = new BannerModel();

         //$Banner=$stdModle;

         $where=array();

         $where["frame"]=$frame;

         $where["ishow"]=1;

         $where['isdelete'] = 0;

         if ($nowid) {
             $where['id'] = ['neq', $nowid];
         }

         $rst=$Banner->searchBanner($where);

         $feedBack=array();

         $feedBack["status"]=0;

         $feedBack["message"]="";

         if($rst)
         {
              //判断时间有没有重复
             $count=count($rst);

             for($i=0;$i<$count;$i++)
             {
                 $stime=$rst[$i]["showstarttime"];

                 $etime=$rst[$i]["showendtime"];

                 if ($stime - $showstartime>0) {
                     if ($stime - $showendtime <= 0) {
                         $feedBack["status"]=0;

                         $feedBack["conflictId"]=$rst[$i]["id"];

                         return $feedBack;
                     }
                 } else {
                     if ($etime - $showstartime >= 0) {
                         $feedBack["status"]=0;

                         $feedBack["conflictId"]=$rst[$i]["id"];

                         return $feedBack;
                     }
                 }


             }

             $feedBack["status"]=1;

             $feedBack["message"]="ok";

             return $feedBack;

         }
         else
         {
             $feedBack["status"]=1;

             $feedBack["message"]="ok";

             return $feedBack;
         }


     }






}