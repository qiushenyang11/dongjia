<?php
namespace WeChat\Model;
//use Think\Model;
class WeChatUserModel
{
   /* protected $trueTableName = 'user';

    protected $autoCheckFields = true;*/
   public function getUserInfo($userid)
   {
       if(!$userid)return false;
       $userModel = M("user");
       $where['id']=$userid;
       $where['type'] = 1;
       $res = $userModel->field('id,name,jdaccount,openid,phone,nickname,avatarurl')->where($where)->limit(1)->find();
       return $res;
   }

    public function getUserInfoByJdaccount($jdAccount)
    {
        if(!$jdAccount)return false;
        $userModel = M("user");
        $where['jdaccount']=$jdAccount;
        $where['type'] = 1;
        $res = $userModel->field('id,name,jdaccount,openid,phone,nickname,avatarurl')->where($where)->limit(1)->find();
        return $res;
    }

   public function getUserInfoByOpenid($openid)
   {
       if(!$openid)return false;
       $userModel = M("user");
       $where['openid']=$openid;
       $where['type'] = 1;
       $res = $userModel->field('id,name,jdaccount,openid,phone,nickname')->where($where)->limit(1)->find();
       return $res;
   }

   public function getTotal($where){
       $BDModel = M("user");
       $where['type']=3;
       $total = $BDModel->field("id,name,phone")->where($where)->count();
       return $total;
   }


    public function BDList($where='',$p)
    {
     $count=$this->getTotal($where);
     $Page=new \Think\Page($count,20);
     $Page->nowPageage=$p;
     $BDModel = M("user");
     $where['type']=3;
     $res = $BDModel->field("id,name,phone")->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
     $data['Page'] = $Page;
     $data['res'] = $res;
     $data['count']= $count;
     return $data;

    }

    public  function addBDinfo($dataBD=''){
        $infoModel = M("user");
        $res=$infoModel->data($dataBD)->add();
        return $res;
    }

    public function hasBDname($name,$type,$id =0){
       $where['name']=$name;
       $where['type']=$type;
       if ($id)
           $where['id']=['neq',$id];
       $BDModel = M("user");
       return $BDModel->where($where)->limit(1)->find();
    }

    public function hasBDphone($phone,$type,$id = 0){
       $where['phone']=$phone;
       $where['type']=$type;
        if ($id)
            $where['id']=['neq',$id];
       $BDModel = M("user");
       return $BDModel->where($where)->limit(1)->find();
    }

    public  function  saveBD($id,$data){
        if(!$id) return false;
        $where['id']=$id;
        $BDModel = M("user");
        $res=$BDModel->where($where)->save($data);
        return $res;
    }

    public  function  getOneBD($id){
        if(!$id)return false;
        $BDModel = M("user");
        $where['id']=$id;
        $where['type'] = 3;
        $res=$BDModel->field('id,name,phone')->where($where)->limit(1)->find();
        return $res;
    }

    public function getOneGuanJia($id)
    {
        if(!$id)return false;
        $BDModel = M("guanjia");
        $where['id']=$id;
        $res=$BDModel->field('id as guanjiaid,guanjianame,guanjiaphone')->where($where)->limit(1)->find();
        return $res;
    }



    public function getAllBD()
    {
        $BD = M("user");
        $where['type'] = 3;
        $where['isdelete'] = 0;
        return $BD->field('id,name')->where($where)->select();
    }

    public function getAllGuanJia()
    {
        $guanjiaModel = M("guanjia");
        $where['isdelete'] = 0 ;
        return $guanjiaModel->field('id as guanjiaid,guanjianame')->where($where)->select();
    }

    public function addUser($param)
    {
        $userModel = M('user');
        return $userModel->data($param)->add();
    }

    public function userBindJdaccountByOpenid($openid,$jdaccount,$otherPram)
    {
        $where['openid'] = $openid;
        $saveData['jdaccount'] = $jdaccount;
        $saveData = array_merge($saveData, $otherPram);
        $userModel = M('user');
        return $userModel->where($where)->save($saveData);
    }

    public function addJdAccount($jdaccount, $otherPram)
    {
        $saveData['jdaccount'] = $jdaccount;
        $saveData = array_merge($saveData, $otherPram);
        $saveData['addtime'] = time();
        $userModel = M('user');
        return $userModel->data($saveData)->add();
    }

    public function userBindJdaccountByUserid($userid,$otherPram)
    {
        $where['id'] = $userid;
        $userModel = M('user');
        return $userModel->where($where)->save($otherPram);
    }

    public function userBindJdAccount($openid, $jdaccount, $otherPram)
    {
        $where['openid'] = $openid;
        $saveData['jdaccount'] = $jdaccount;
        $saveData = array_merge($saveData, $otherPram);
        $userModel = M('user');
        return $userModel->where($where)->save($saveData);
    }

    public function getUserBaseInfoByJdaccount($jdaccount)
    {
        $where['jdaccount'] = $jdaccount;
        $userModel = M('user');
        return $userModel->field('id,openid,jdaccount')->where($where)->limit(1)->find();
    }

    public function userUnBindJdAccount($useid)
    {
        $where['id'] = $useid;
        $saveData['jdaccount'] = '';
        $userModel = M('user');
        return $userModel->where($where)->save($saveData);
    }
}