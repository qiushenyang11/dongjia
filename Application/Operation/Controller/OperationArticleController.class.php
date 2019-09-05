<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/12/5
 * Time: 12:05
 */

namespace Operation\Controller;


use Operation\Model\ArticleModel;
use Operation\Model\AuctionAllowUserModel;
use Think\Controller;
use WeChat\Model\GuanJiaModel;
use WeChat\Model\WeChatUserModel;

class OperationArticleController  extends OperationBaseController
{
    //文章列表
    public function  articleList(){
        $type=I("get.type",'');
        $condition=I("get.condition",'');
        $status=I("get.status",'');
        $p=I("get.p",'1');
        $where=array();
        if(!empty($status)){
            $where['status']=$status;
        }
        if($type==1 && $condition){
            $where['id']=$condition;
        } elseif ($type==2 && $condition) {
            $where['title']=['like',"%$condition%"];
        }
        $articleModel=new ArticleModel();
        $data=$articleModel->articleInfo($where);
        //dump($data);die;
        $res=$data['res'];
        $count=$data['count'];
        $Page=$data['Page'];
        $first=$Page->firstRow+1;
        $rest=($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
        $end = $first + $rest-1;
        $all=$first.'-'.$end;
        $this->assign('status', $status);
        $this->assign('type', $type);
        $this->assign('condition', $condition);
        $this->assign("Page",$Page->show());
        $this->assign('count',$count);
        $this->assign("total",$Page->totalPages);
        $this->assign("nowPage",$p);
        $this->assign("all",$all);
        $this->assign("res",$res);
        $this->display();
    }

 //删除文章
    public function  delArticle(){
        $articleid=I("post.id",'');
        if(!$articleid)response("参数错误");
        $articleModel=new ArticleModel();
        $res=$articleModel->del($articleid);
        $res?response("删除成功",1):response("删除失败",0);
    }

//添加文章
  public  function addNewArticle()
  {
      $id = I("get.id", '');
      $title = I("post.title", '');
      $pic = I("post.pic", '');
      $content = $_POST['content'];
      $status = I("post.status", '');
      $guanjiaid = I("post.guanjiaid", '');
      $addtime = time();
      if (!($title && $pic && $content && $status && $guanjiaid)) response("参数错误");
      $length  =mb_strlen($title,'utf8');
      if(!$title)response("文章名称不能为空");
      if($length>24)response("文章名称在24字以内");
      if(!$content)response("文章内容不能为空");
      if(!$guanjiaid)response("请选择管家信息");
      if(!$status)response("请选择文章可用状态");
      if(!$pic)response("请上传头图");
      $data = array();
      $data['title'] = $title;
      $data['content'] = htmlspecialchars($content);
      $data['pic'] = $pic;
      $data['status'] = $status;
      $data['guanjiaid'] = $guanjiaid;
      $data['addtime'] = $addtime;

      /*通过管家id获取管家头像和管家分类*/

      /*'__CATEGORY__ as c ON g.guanjialevelid = c.id')
            ->field('g.guanjiaid,g.guanjianame,c.name,g.avatarurl'*/


      $GuanjiaModel= new GuanJiaModel();

      $guanjiaInfo=$GuanjiaModel->getGuanjiaInfo($guanjiaid);

      $data["guanjianame"]=$guanjiaInfo["guanjianame"];

      $data["avatarurl"]=$guanjiaInfo["avatarurl"];

      $data["guanjiatype"]=$guanjiaInfo["name"];
      $articleModel = new ArticleModel();
      if (!$id) {
          if($articleModel->hasArticleName($title))response("该文章名已存在");
          $res = $articleModel->saveArticle($data);
          $res ? response("添加成功", 1) : response("添加失败", 0);
      }else{
          $res = $articleModel->editArticle($data,$id);
          $res ? response("修改文章成功",1):response("修改文章失败",0);
      }
    }


    //编辑文章
    public function editorArticle(){
        $id=I("get.id",'');
        $articleModel = new ArticleModel();
        $res=$articleModel->getOneArticle($id);
        $res['content'] = htmlspecialchars_decode($res['content']);
        $guanjia = new WeChatUserModel();
        $guanjiainfo=$guanjia->getAllGuanJia();
        $this->assign("guanjia",$guanjiainfo);
        $this->assign("res",$res);
        $this->display();

    }





}