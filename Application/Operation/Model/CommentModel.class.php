<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/5/4
 * Time: 11:37
 */

namespace Operation\Model;


class CommentModel
{
    public function comeentList($where, $p)
    {
        $where['isdelete'] = 0;
        $count=$this->getTotal($where);
        $Page=new \Think\Page($count,20);
        $Page->nowPageage=$p;
        $commentModel = M("comment");
        $res = $commentModel->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
        $data['Page'] = $Page;
        $data['res'] = $res;
        $data['count']= $count;
        return $data;
    }

    public function getTotal($where)
    {
        $commentModel = M("comment");
        return $commentModel->where($where)->count();
    }

    public function getCommentByProductid($productid)
    {
        $where['productid'] = $productid;
        $where['isshow'] = 1;
        $res = M('comment')->where($where)->order('commentlevel desc,id desc')->select();
        return $res;
    }

    public function getManyiComment($productid)
    {
        $where['isshow'] = 1;
        $where['productid'] = $productid;
        $res = M('comment')->where($where)->order('commentlevel desc')->limit(1)->find();
        return $res;
    }

    public function getWeChatCommentList($where,$p = 1)
    {
        if ($p ==1) {
            $start = 0;
            $limit = 9;
        } else {
            $start = ($p-2)*10+9;
            $limit = 10;
        }
        $where['isshow'] = 1;
        return M('comment')->where($where)->order('addtime desc')->limit($start,$limit)->select();
    }

}