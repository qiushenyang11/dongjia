<?php

namespace WeChat\Model;



class GuanJiaSupportModel
{
    public function getAllguanJiaSupport($guanjiaId)
    {

        //id	guanjiaid	userid	username	useravatar

        $GSupport=M("guanjiasupport");

        $map=array();

        $rst=$GSupport->where($map)->select();

        return $rst;
    }

    public function addOneSupportForOneGuanJia($guanjiaId,$userid,$username,$useravatar)
    {
        $GSupport=M("guanjiasupport");

        $data=array();

        $data["guanjiaid"]=$guanjiaId;
        $data["userid"]=$userid;
        $data["username"]=$username;
        $data["useravatar"]=$useravatar;

        $rst=$GSupport->data($data)->add();

        return $rst;
    }

}