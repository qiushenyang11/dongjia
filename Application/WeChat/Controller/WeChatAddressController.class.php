<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/11/23
 * Time: 9:59
 */

namespace WeChat\Controller;

/****
 *
 *
 * 微信公众号地址管理
 *
 */
use Server\JdApi;
use Think\Controller;
use WeChat\Controller\WeChatBaseController;
use WeChat\Model\AddressModel;

class WeChatAddressController extends Controller//WeChatBaseController
{
    public function _initialize(){
        needAuth('jdLogin');
        header('Content-Type:application/json; charset=utf-8');

    }
    //获得所有的地址
    //http://www.dservie.cn/myWeb/index.php/WeChat/WeChatAddress/getAllMyAddress
    public function getAllMyAddress()
    {
        $jdaccount=session("jdaccount");
        /*获得所有的地址*/
        //$userid=11;

        $Address=new AddressModel();

        $result=$Address->getAllAddress($jdaccount);

        if($result)
        {
            response('获取成功', 1, $result);
        }
        else
        {
            response('获取失败没有地址', 0);
        }

    }

    //设为默认的地址
    //http://www.dservie.cn/myWeb/index.php/WeChat/WeChatAddress/setDefaultAddress
    public function setDefaultAddress()
    {
        $jdaccount=session("jdaccount");

        $addressId = I('post.addressid', 0);

        /*测试数据*/
//        $userid=11;
//
//        $addressId=3;

        $Address=new AddressModel();

        $rst=$Address->setDefaultAddress($addressId,$jdaccount);

        if($rst)
        {
            response('获取成功', 1);
        }
        else
        {
            response('获取失败', 0);
        }
    }

    //新增一条地址 （包括设置成默认）
    //http://www.dservie.cn/myWeb/index.php/WeChat/WeChatAddress/addNewOneAddress
    public function addNewOneAddress()
    {
        $jdaccount=session("jdaccount");

        $userid = session('userid');

        $adressText=I('post.address','');

        $setDefaultOrNot=I('setdefaultornot','');

        $addresscode = I('post.addresscode','');

        /*测试数据*/

//        $userid=11;
//
//        $adressText="leemanrnrnrnnrnrnrnrnnsnssc";
//
//        $setDefaultOrNot=1;

        $Address=new AddressModel();

        $result=$Address->getAllAddress($jdaccount);

        if(count($result)===0)
        {
            $setDefaultOrNot=1;
        }

        $rst=$Address->addOneAddress($userid,$jdaccount, $adressText, $addresscode);

        if(!$rst)
        {
            response('添加失败', 0);
        }
        else
        {
            if($setDefaultOrNot==1)
            {
                $rstD=$Address->setDefaultAddress($rst,$jdaccount);

                if($rstD)
                {
                    response('添加成功', 1,$rst);
                }
                else
                {
                    response('添加失败', 0);
                }
            }
            else
            {
                response('添加成功', 1,$rst);
            }
        }

    }

    //删除一条地址 (包括设置成默认）
    //http://www.dservie.cn/myWeb/index.php/WeChat/WeChatAddress/delOneAddress
    public function delOneAddress()
    {
        $jdaccount = session('jdaccount');

        $addressId= I('post.addressid', 0);

        /*测试数据*/

//        $userid=11;
//
//        $addressId=5;

        /*判断是否是default*/

        $Address=new AddressModel();

        $rst=$Address->checkInDefault($addressId);

        if($rst)
        {
            //默认地址

            $result=$Address->getAllAddress($jdaccount);

            if(count($result)===1)
            {

                $rstDel=$Address->delOneAddress($addressId);

                if($rstDel)
                {
                    response('删除成功', 1);
                }
                else
                {
                    response('删除失败', 0);
                }
            }
            else
            {

                $nextDefaultId=$result[1]["id"];

                $rstDel=$Address->delOneAddress($addressId);

                if($rstDel)
                {
                    $Address->setDefaultAdressWithNoDefault($nextDefaultId,$jdaccount);

                    response('删除成功', 1,$nextDefaultId);
                }
                else
                {
                    response('删除失败', 0);
                }

            }
        }
        else
        {

            $rstDel=$Address->delOneAddress($addressId);

            if($rstDel)
            {
                response('删除成功', 1);
            }
            else
            {
                response('删除失败', 0);
            }

        }

    }


    //更新一条地址 (包括设置成默认）
    //http://www.dservie.cn/myWeb/index.php/WeChat/WeChatAddress/updateOneAddress
    public function updateOneAddress()
    {
        $jdaccount = session('jdaccount');

        $adressText=I('post.address');

        $setDefaultOrNot=I('post.setdefaultornot');

        $addressId=I('post.addressid');

        $addresscode = I("post.addresscode", '');
        /*测试数据*/
//        $userid=11;
//
//        $addressId=5;
//
//        $adressText="iwantruning";
//
//        $setDefaultOrNot=1;

        $Address=new AddressModel();

        $result=$Address->getAllAddress($jdaccount);

        if(count($result)===1)
        {
            $setDefaultOrNot=1;
        }

        $rst=$Address->upadteOneAdress($addressId,$adressText, $addresscode);

        if(!$rst)
        {
            response('更新失败', 0);
        }
        else
        {

            $result=$Address->getAllAddress($jdaccount);

            if(count($result)===1)
            {
                response('更新成功', 1,$addressId);
            }
            else
            {
                if($setDefaultOrNot==1)
                {
                    $rstD=$Address->setDefaultAddress($addressId,$jdaccount);

                    if($rstD)
                    {
                        response('更新成功', 1,$addressId);
                    }
                    else
                    {
                        response('更新失败', 0);
                    }
                }
                else
                {
                    response('更新成功', 1,$addressId);
                }
            }

        }

    }

    public function getOneAddrss()
    {
        $addressid = I('get.id', 0);
        if (!$addressid) response('异常');
        $jdaccount = session('jdaccount');
        $addressModel = new AddressModel();
        $addressinfo = $addressModel->getOneAddress($addressid, $jdaccount);
        $address = $addressinfo['address'];
        $address =explode(',',$address);
        unset($address['address']);
        $addressinfo['addressname'] = $address[0];
        $addressinfo['mobile'] = $address[1];
        $addressinfo['province'] = $address[2];
        $addressinfo['address'] = $address[3];
        response('获取成功', 1, $addressinfo);
    }


}