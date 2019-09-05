<?php
/**
 * Created by PhpStorm.
 * User: xiyou
 * Date: 2017/12/5
 * 文章管理数据库操作
 * Time: 14:56
 */
namespace Operation\Model;


class ArticleModel
{
  //文章列表页
     public  function  getCount($where){
         $articleModel=M("article");
         $count=$articleModel->where($where)->count();
         return $count;
     }

     public function getAllArticle($guanjiaid)
     {
        $where['guanjiaid'] = $guanjiaid;
        $where['status'] = 1;
         $articleModel=M("article");
         return $articleModel->field('id,title,pic')->where($where)->order('id desc')->select();
     }

    public  function articleInfo($where){
         $count=$this->getCount($where);
         $Page=new \Think\Page($count,20);
        $articleModel=M("article");
        $res=$articleModel->where($where)
            ->field('article.id,article.title,article.content,article.status,guanjia.guanjianame,guanjia.guanjiaphone')
            ->join('guanjia on guanjia.id=article.guanjiaid','left')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $data['res']=$res;
        $data['count']=$count;
        $data['Page']=$Page;
        return $data;
    }

    public  function  del($articleid){
     $articleModel=M("article");
     $where['id']=$articleid;
     $res=$articleModel->where($where)->delete();
     return $res;


    }

  public  function  saveArticle($data){

        $articleModel=M("article");
        $res=$articleModel->data($data)->add();
        return $res;
  }


  public function editArticle($data,$id){
      $articleModel=M("article");
      $where['id']=$id;
       return $articleModel->where($where)->save($data);

  }

  public function getOneArticle($id){
      $articleModel=M("article");
      $where['id']=$id;
      $res=$articleModel->where($where)->limit(1)->find();
      return $res;

  }


  //判断产品名称是否已存在

    public function hasArticleName($title){
     $where['title']=$title;
     /*$where['id']=$id;*/
     $articleModel=M("article");
     return $articleModel->where($where)->limit(1)->find();
    }




}