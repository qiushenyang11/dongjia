<?php
/**
 * Created by PhpStorm.
 * User: xiyou
 * Date: 2017/10/26
 * Time: 15:23
 * 管家管理:管家列表，搜索查询,新建管家...
 * http://www.dservie.cn/myWeb/index.php/Operation/OperationGuanJia/
 *
 */

namespace Operation\Controller;
use Common\Model\AreaModel;
use Server\Area;
use Server\Category;
use Think\Controller;
use WeChat\Model\GuanJiaModel;
use WeChat\Model\WeChatUserModel;

class OperationGuanJiaController extends  OperationBaseController
{

    public function  index(){
        $p=(string)$_GET["p"]==0?1:$_GET["p"];
        $type=I("get.type",'');
        $condition=I("get.condition",'');
        $temp = $type;
        if($type==1){
            $where['guanjianame']=['like',"%$condition%"];
            $type="管家名称";
        }
        if($type==2){
            $where['guanjiaid']=$condition;
            $type="管家ID";
        }
        if($type==3){
            $where['guanjiaphone']=$condition;
            $type="联系方式";

        }
        if($type==4){
            $where['name']=['like',"%$condition%"];
            $type="负责BD";
        }
        if(!$type){
            $where='';
        }
        $guanjia = new GuanJiaModel();
        $data= $guanjia->guanJiaList($where, $p);
        //dump($data);exit;
        $result = $data['res'];
        $Page = $data['Page'];
        $count = $data['count'];
        $first=$Page->firstRow+1;
        $rest=($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
        $end = $first + $rest-1;
        $all=$first.'-'.$end;
        $this->assign("count",$count);
        $this->assign("type",$temp);
        $this->assign('page',$Page->show());
        $this->assign('condition', $condition);
        $this->assign('nowPage',$p);
        $this->assign('all',$all);
        $this->assign('totalPages',$Page->totalPages);
        $this->assign("result",$result);
        $this->display();
    }

    public function guanjiaedit()
    {
        $guanjiaid = I("get.id", 0);
        if (!$guanjiaid) $this->error('参数错误');
        $guanjiaModel = new GuanJiaModel();
        $res = $guanjiaModel->getOneGuanJia($guanjiaid);
        $res['guanjiadetails'] = htmlspecialchars_decode($res['guanjiadetails']);

        $this->assign('res', $res);
        $this->display();
    }


    /**
     * @breif 根据areaid获取所有省|市|区  areaid =0 为所有省
     */
    public function getAreas()
    {
        $areaid = I("get.areaid", 0);
        $areaClass = new Area();
        $province = $areaClass->getAreaListByAreaId($areaid);
        response("获取成功", 1, $province);
    }

    public function getCategory()
    {
        $id = I("get.id", 0);
        $type = I("get.type", 1);
        $cateClass = new Category();
        $res = $cateClass->getCategorys($type, $id, 1);
        response("获取成功", 1, $res);

    }

    public  function  getBD(){
        $BDModel = new WeChatUserModel();
        $res = $BDModel->BDList();
        response("获取成功",1,$res);
    }

    public function getAllBD()
    {
        $BDModel = new WeChatUserModel();
        $res = $BDModel->getAllBD();
        response("获取成功",1,$res);
    }

    //录入管家信息
    public function saveGuanjia()
    {
        $avatarurl = I("post.avatarurl", '');
        $guanjiaid = I("post.guanjiaid", 0);   //修改管家时候用到
        $name = I("post.name", '');
        $phone = I("post.phone", '');
        $userid = I("post.userid", '');
        $info = I("post.info", '');
        $guanjiadetails = $_POST['guanjiadetails'];
        $extra = I("post.extra", '');
        $title = I('post.title','');
        $moreinfo = I('post.moreinfo', '');
        if (!($avatarurl && $name && $phone && $userid && $info && $guanjiadetails&&$moreinfo&&$title))
            response("参数错误");

        $guanJiaData['avatarurl'] = $avatarurl;
        $guanJiaData['guanjianame'] = $name;
        $guanJiaData['guanjiaphone'] = $phone;
       // $guanJiaData['guanjialevelid'] = $guanjialevelid;
        $guanJiaData['userid'] = $userid;
       // $guanJiaData['guanjiatag'] = $guanjiatag;
        $guanJiaData['info'] = $info;
        $guanJiaData['guanjiadetails'] = htmlspecialchars($guanjiadetails);
        $guanJiaData['extra'] = $extra;
        //$guanJiaData['guanjiafenlei'] = $yiji . '-' . $erji;
        $guanJiaData['createtime'] = time();
        $guanJiaData['title'] = $title;
        //$guanJiaData['supplier'] = $supplier;
        //$guanJiaData['suppliershort'] = $suppliershort;
        //$guanJiaData['customertype'] = $customertype;
        //$guanJiaData['customerphone'] = $customerphone;
        $guanJiaData['moreinfo'] = $moreinfo;

        //$bankData['areaid'] = $areaid;
        //$bankData['bankbrance'] = $bankbrance;
        //$bankData['bank'] = $bank;
        //$bankData['bankaccount'] = $bankaccount;
        //$bankData['bankid'] = $banknumber;
        $guanJiaModel = new GuanJiaModel();
        if (!$guanjiaid) {
            if ($guanJiaModel->hasGanjia($name)) response("管家已存在");
            if ($guanJiaModel->hasGanjiaphone($phone)) response("联系电话已存在");
            $gunjiaId = $guanJiaModel->saveGuanJia($guanJiaData);
            $gunjiaId ? response("添加成功", 1) : response("添加失败");
        } else {
            $info = $guanJiaModel->getGuanJiaBaseInfo($guanjiaid);
            if ($info['guanjianame'] != $name) {
                if ($guanJiaModel->hasGanjia($name)) response("管家已存在");
            }
            if ($info['guanjiaphone'] != $phone) {
                if ($guanJiaModel->hasGanjiaphone($phone)) response("联系电话已存在");
            }
            $guanJiaModel->saveGuanJia($guanJiaData, $guanjiaid);

            response("修改成功", 1);
        }

    }

    public function getAllCity()
    {
        $param = I("get.param",'');
        $areaModel = new AreaModel();
        $res = $areaModel->getAllCity($param);
        echo json_encode($res);

    }

    public  function  sss(){

        $this->display();
    }

}