<?php
/**
 * Created by PhpStorm.
 * User: xiyou
 * Date: 2017/10/26
 * Time: 15:23
 * 管家管理:管家列表，搜索查询,新建管家...
 * http://www.dservie.cn/myWeb/index.php/Operation/OperationGuanJia/
 *
 */

namespace Operation\Controller;
use Common\Model\AreaModel;
use Operation\Model\CategoryModel;
use Server\Area;
use Server\Category;
use Think\Controller;
use WeChat\Model\GuanJiaModel;
use WeChat\Model\WeChatUserModel;

class OperationCategoryController extends  OperationBaseController
{

    public function index()
    {
        $type = I('get.type',1);
        $condition = I('get.condition','');
        $level = I('get.level',0);
        $status = I('get.status',0);
        $p = I("get.p",1);
        $where = [];
        if ($condition) {
            if ($type == 1) {
                $where['c1.name'] = ['like','%'.$condition.'%'];
            } elseif ($type == 2) {
                $where['c1.id'] = $condition;
            }
        }
        if ($level) {
            $where['c1.level'] = $level;
        }
        if ($status) {
            $where['c1.status'] = $status;
        }
        $cateModel = new CategoryModel();
        $data = $cateModel->getAllProductCategoryList($where,$p);
        $result = $data['res'];
        $Page = $data['Page'];
        $count = $data['count'];
        $first=$Page->firstRow+1;
        $rest=($count-$Page->listRows*($p-1))>$Page->listRows ? $Page->listRows:($count-$Page->listRows*($p-1));
        $end = $first + $rest-1;
        $all=$first.'-'.$end;
        $this->assign('all', $all);
        $this->assign("condition",$condition);
        $this->assign("type",$type);
        $this->assign("count",$count);
        $this->assign('page',$Page->show());
        $this->assign('nowPage',$p);
        $this->assign('status', $status);
        $this->assign('level', $level);
        $this->assign('totalPages',$Page->totalPages);
        $this->assign("result",$result);
        $this->display();
    }

    public function saveCategory()
    {
        $name = I('post.name','');
        $id = I('post.id', 0);
        $level = I('post.level', 1);
        $pid = I('post.pid',0);
        $status = I('post.status', 1);
        $picurl = I('post.picurl', '');
        if (!$name) response('请输入分类名称');
        if ($level != 1 && $level != 2) response('请选择正确的等级');
        $cateClass = new Category();
        if (!$id) {
            $res = $cateClass->addCategory($name, $pid, $level, 2, $status,$picurl);
            $res ? response('添加成功', 1) : response('添加失败');
        } else {
            $res = $cateClass->saveCategory($id, $name, $pid, $level, 2, $status,$picurl);
            response('修改成功', 1);
        }
    }

    public function editCategory()
    {
        $id = I('get.id',0);
        if (!$id) response('参数错误');
        $cateModel = new CategoryModel();
        $res = $cateModel->getCategory($id);
        $cateClass = new Category();
        $category = $cateClass->getCategorys(2,0);
        $this->assign('category',$category);
        $this->assign('res', $res);
//        dump($res);
        $this->display();

    }

    public function addCategory()
    {
        $cateClass = new Category();
        $category = $cateClass->getCategorys(2,0);
        $this->assign('category',$category);
        $this->display();
    }

}