<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/11/23
 * Time: 9:59
 */

namespace BaoXian\Controller;



use BaoXian\Model\LifeModel;
use Think\Controller;

use BaoXian\Model\StaffModel;

class BaoXianDoorController extends Controller
{

    //https://www.dservie.cn/myWeb/index.php/BaoXian/BaoXianDoor/edit
    public function index()
    {
        $this->display();
    }

    public function expert()
    {
        $sample = $this->getExpertSample(session('adminuserid'));
        $DetailInfo = $this->getExpertDetailInfo($sample);
        $product = $this->getExpertProduct();
        $InsuredPeople = $this->getExpertInsuredPeople($sample);
        $productList = $this->getExpertProductList($product);

        $this->assign('sampleJson', json_encode($sample));
        $this->assign('sample', $sample);
        $this->assign('DetailInfo', $DetailInfo);
        $this->assign('product', $product);
        $this->assign('productJson', json_encode($product));
        $this->assign('productList', json_encode($productList));
        $this->assign('InsuredPeople', $InsuredPeople);

        $this->display();
    }

    private function getExpertSample($id = 0) {
        $SBModel = new BaoXianSBController();
        return $SBModel->currentSample($id);
    }

    private function getExpertDetailInfo($sample) {
        return [
            [
                'name'  => '样本版本' ,
                'value' => $sample['nowSample']['sampleversion'],
            ],
            [
                'name'  => '年龄' ,
                'value' => $sample['nowSample']['age'],
            ],
            [
                'name'  => '孩子' ,
                'value' => $sample['nowSample']['child'].'个孩子',
            ],
            [
                'name'  => '收入' ,
                'value' => $sample['nowSample']['income'].'万',
            ],
            [
                'name'  => '婚姻状况' ,
                'value' => $sample['nowSample']['marry'] == 1 ? '已婚' : '未婚',
            ],
            [
                'name'  => '性别' ,
                'value' => $sample['nowSample']['sex'] ? '男' : '女',
            ],
        ];
    }

    private function getExpertProduct() {
        $SBModel = new BaoXianSBController();
        return $SBModel->currentInsuranceProduct();
    }
    private function getExpertInsuredPeople($sample) {
        $nowSample = $sample['nowSample'];
        $insuredPeople = $nowSample['insuredPeople'];
        $inputList = [];
        foreach ($insuredPeople as $k => $v) {
            $inputList[] = $k;
        }
//        for ($i = 1; $i <= $nowSample['child']; $i++) {
//            $inputList[] = '孩子'.$i;
//        }
//
//        if ($nowSample['marry'] == 1) {
//            $inputList[] = '配偶';
//        }

//        dump($inputList);die;
        return $inputList;
    }
    private function getExpertProductList($product) {
        $list = [];
        foreach ($product as $k => $v) {
            $list[] = [
                'name' => $v['name'],
                'total' => 0
            ];
        }

        return $list;
    }

    public function admin()
    {
        var_dump(session('isadmin'));
    }

    public function test()
    {
//        $lifeModel = new LifeModel();
//        $life = $lifeModel->getLife();
//        var_dump($life[0]);die;
        $this->display();
//        var_dump(md5Password('18012345678', '123456'));
//        var_dump(md5Password('18019159738', 'woshinibaba'));
//        var_dump(md5Password('18018699131', 'woshinibaba'));
    }

    //https://www.dservie.cn/myWeb/index.php/BaoXian/BaoXianDoor/login
    public function login()
    {
        $phone=$_POST["phone"];

        $code=$_POST["code"];

        $Staff=new StaffModel();

        $where=array();

        $where["phone"]=$phone;

        $where["code"]=md5Password($phone, $code);

        $rst=$Staff->where($where)->limit(1)->find();

        if(!$rst)
        {
            response("Failure No Staff");
        }
        else
        {

            if($rst["level"]==1)
            {
                session('adminuserid',$rst['id']);
                session('username',$rst['name']);
                session('isadmin',0);
                response("Success ",1,"expert");
            }
            else if($rst["level"]==2)
            {
                session('adminuserid',$rst['id']);
                session('username',$rst['name']);
                session('isadmin',1);
                response("Success",1,"admin");
            }
            else if($rst["level"]==0)
            {
                session('adminuserid',$rst['id']);
                session('username',$rst['name']);
                session('isadmin',0);
                response("Success",1,"staff");
            }
            else
            {
                response("Failure Level Wrong");
            }
        }




    }

    //https://www.dservie.cn/myWeb/index.php/BaoXian/BaoXianDoor/loginOut
    public  function loginOut(){
        //$this->success('退出成功',U('OperationLogin/work'));
        session('[destroy]');
        $this->redirect("BaoXianDoor/index");
    }


}