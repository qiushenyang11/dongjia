<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/10/27
 * Time: 16:02
 */

namespace Operation\Model;


class CategoryModel
{
    public function addCategory($data = '')
    {
        if (!$data) return false;
        $category = M("category");
        $res = $category->data($data)->add();
        return $res;
    }

    public function getCategory($id)
    {
        $where['id'] = $id;
        $where['type'] = 2;
        $category = M("category");
        $res = $category->field('id,name,level,status,pid,picurl')->where($where)->limit(1)->find();
        return $res;
    }

    public function isCategory($name, $type = 1, $level = 1,$id = 0)
    {
        $where['name'] = $name;
        $where['type'] = $type;
        $where['level'] = $level;
        if ($id) $where['id'] = ['neq',$id];
        $category = M("category");
        return $category->where($where)->limit(1)->find();
    }

    public function delCategory($id)
    {
        $where['id'] = $id;
        $saveData['status'] = 0;
        $category = M("category");
        return $category->where($where)->save($saveData);
    }

    public function getCategorys($type,$pid,$status)
    {
        $where['pid'] = $pid;
        $where['type'] = $type;
        if ($status) $where['status'] = $status;
        $category = M("category");
        return $category->field('id,name,picurl')->where($where)->select();
    }

    public function getAllCategorys()
    {
        $category = M("category");
        return $category->field('id,name,pid,type')->select();
    }

    public function getTotal($where)
    {
        $category = M("category as c1");
        $total = $category->join('__CATEGORY__ as c2 ON c1.pid = c2.id','left')
            ->where($where)
            ->count();
        return $total;
    }

    public function getAllProductCategoryList($where,$p)
    {
        $where['c1.type'] = 2;
        $count=$this->getTotal($where);
        $Page=new \Think\Page($count,20);
        $Page->nowPageage=$p;
        $category = M("category as c1");
        $cateList = $category->join('__CATEGORY__ as c2 ON c1.pid = c2.id','left')
            ->field('c1.id,c1.name,c1.level,c2.name as pname,c1.status')
            ->where($where)
            ->order('id desc')
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();

        $data['Page'] = $Page;
        $data['res'] = $cateList;
        $data['count']= $count;
        return $data;
    }

    public function getParentCategory($type,$id,$status = '')
    {
        $where['c1.id'] = $id;
        $where['c1.type'] = $type;
        if ($status) $where['c1.status'] = $status;
        return M('category as c1')
            ->join('__CATEGORY__ as c2 ON c1.pid = c2.id')
            ->field('c2.id, c2.name')
            ->where($where)
            ->limit(1)
            ->find();
    }
}