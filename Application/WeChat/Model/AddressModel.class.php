<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/11/2
 * Time: 17:56
 */
/*
 * 所有产品商品的数据库操作
 * */
namespace WeChat\Model;


use function GuzzleHttp\Psr7\rewind_body;
use Think\Page;

class AddressModel
{
    /*1	id主键	int(10)			否	无		AUTO_INCREMENT	 修改 修改	 删除 删除
	  2	userid	int(10)			否	无			 修改 修改	 删除 删除
      3	adress	varchar(500)	utf8_general_ci		否	无			 修改 修改	 删除 删除
	  4	default	int(1)			否	0	0是一般地址1是默认地址		 修改 修改	 删除 删除
	  5	createtime	timestamp
    */
    
    public function getAllAddress($jdaccount)
    {
        $Addrss=M("useraddress");

        $map=array();

        $orderBy=array();

        $map['jdaccount'] = $jdaccount;

        $orderBy["id"]='asc';

        $rawData=$Addrss->where($map)->order($orderBy)->select();
        return $rawData;

    }

    public function getAllAddressByJdAccount($jdaccount)
    {
        $Addrss=M("useraddress");

        $map=array();

        $orderBy=array();

        $map["jdaccount"]=$jdaccount;

        $orderBy["default"]="desc";

        $orderBy["id"]='desc';

        $rawData=$Addrss->where($map)->order($orderBy)->select();

        return $rawData;
    }

    public function setDefaultAddress($addressId,$jdaccount)
    {
        $Address=M("useraddress");

        $mapF=array();

        $mapC=array();

        $mapF["jdaccount"]=$jdaccount;

        $mapF["default"]=1;

        $mapC["id"]=$addressId;

        $Address->startTrans();

        $resultF=$Address->where($mapF)->setField("default",0);

        $resultC=$Address->where($mapC)->setField("default",1);
        if($resultC)
        {

            $Address->commit();

            return true;
        }
        else
        {
            $Address->rollback();

            return false;
        }

    }

    public function setDefaultAdressWithNoDefault($addressId,$jdaccount)
    {
        $Address=M("useraddress");

        $mapF=array();

        $mapF["jdaccount"]=$jdaccount;

        $mapF["id"]=$addressId;

        $rst=$Address->where($mapF)->setField("default",1);

        if($rst)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function addOneAddress($userid,$jdaccount,$addressText, $addresscode)
    {
        $Addrss=M("useraddress");

        $data=array();

        $data["userid"]=$userid;

        $data["adress"]=$addressText;

        $data['jdaccount'] = $jdaccount;

        $data['addresscode'] = $addresscode;

        $res = $Addrss->data($data)->add();

        return $res;

    }

    public function delOneAddress($addressId)
    {
        $Addrss=M("useraddress");

        $map=array();

        $map["id"]=$addressId;

        $rst=$Addrss->where($map)->delete();

        if($rst)
        {
           return true;
        }
        else
        {
            return false;
        }

    }

    public function checkInDefault($addressId)
    {
        $Addrss=M("useraddress");

        $map=array();

        $map["id"]=$addressId;

        $map["default"]=1;

        $rst=$Addrss->where($map)->limit(1)->find();

        if($rst)
        {
            return true;
        }
        else
        {
            return false;
        }

    }

    public function upadteOneAdress($addressId,$addressText, $addresscode)
    {
        $Addrss=M("useraddress");

        $map=array();

        $map["id"]=$addressId;

        $data=array();

        $data["adress"]=$addressText;

        $data['addresscode'] = $addresscode;

        $rst=$Addrss->where($map)->save($data);

        if($rst)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function getAllAddressCount($jdaccount)
    {
        $where['jdaccount'] = $jdaccount;
        $addressModel = M('useraddress');
        return $addressModel->where($where)->count();
    }

    public function getOneAddress($addressid,$jdaccount)
    {
        $where['id'] = $addressid;
        $where['jdaccount'] = $jdaccount;
        $model = M('useraddress');
        $addressInfo=$model->field('id,adress as address,default,addresscode')->where($where)->find();
        return $addressInfo;
    }
}