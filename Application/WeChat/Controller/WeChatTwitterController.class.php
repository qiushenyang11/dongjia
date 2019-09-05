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
use Think\Controller;
use WeChat\Controller\WeChatBaseController;
use WeChat\Model\TwitterModel;


class WeChatTwitterController extends Controller//Controller//
{
    //https://www.dservie.cn/myWeb/index.php/WeChat/WeChatTwitter/recommendTwitter
    //推荐相关也在 推文首页-----
    public function recommendTwitter()
    {
        $page=I('get.page', 1);

        $userId=session_encode("userid");

        //$page=1;

        $intrestKey=$this->getIntrestKey($userId);

        if($intrestKey==="")
        {
            $Twitter=new TwitterModel();

            $res=$Twitter->getTwiiterList($page);
            foreach ($res as $key => $row) {
                $res[$key]['pic'] = C("UPLOADURL").$row['pic'];
                $res[$key]['avatarurl'] = C("UPLOADURL").$row['avatarurl'];
            }
            response("获取成功",1,$res);

        }
        else
        {

        }
    }

    //http://www.dservie.cn/myWeb/index.php/WeChat/WeChatTwitter/getOneTwitter
    //推文详细
    public function getOneTwitter()
    {
        $twitterId = I('get.id', 0);
        //测试数据
        //$twitterId=4;
        $Twitter=new TwitterModel();
        $res=$Twitter->getOneTwitter($twitterId);
        $res['pic'] = C('UPLOADURL').$res['pic'];
        $res['avatarurl'] = C('UPLOADURL').$res['avatarurl'];
        $res['content'] = htmlspecialchars_decode($res['content']);
        if($res)
        {
            response("获取成功",1,$res);
        }
        else
        {
           response("获取失败",0);
        }

    }

    //http://www.dservie.cn/myWeb/index.php/WeChat/WeChatTwitter/zhuanFaCallBack
    //转发callback 统计
    public function zhuanFaCallBack()
    {
        $json = file_get_contents('php://input');

        $obj = json_decode($json);

        $userId=session_encode("userid");

        $twitterId=$obj->id;

        $channel=$obj->zhuanfa;

        $data=array();

        $data["app"]=0;

        if($this->checkWeXinWebView())
        {
            $data["app"]=0;
        }
        if($this->checkJDFApp())
        {
            $data["app"]=1;
        }

        $data["zhuanfa"]=$channel;

        $data["articleid"]=$twitterId;

        $data["userid"]=$userId;

        $ArticleS=M("articlestatics");

        $res=$ArticleS->data($data)->add();

        if($res)
        {
           response("statics ok",1);
        }
        else
        {
            response("statics failure",0);
        }
    }












    public function checkJDFApp()
    {
        return false;
    }

    public function checkWeXinWebView()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false )
        {

            return true;

        }

        return false;
    }




    public function getIntrestKey($userid)
    {
        return "";
    }
}