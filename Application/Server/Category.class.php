<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/10/27
 * Time: 16:08
 */

namespace Server;


use Operation\Model\CategoryModel;

class Category
{
    /**
     * @breif 添加分类
     * @param string $name
     * @param int $pid
     * @param int $level
     * @param int $type
     * @param int $status
     * @return bool|mixed
     */
    public function addCategory($name = '', $pid = 0, $level = 1, $type =1, $status = 1,$picurl = '')
    {
        if (!$name) return false;
        $cateModel = new CategoryModel();
        $isCategory = $cateModel->isCategory($name, $type, $level);
        if ($isCategory) response("分类已存在");
        $data['name'] = $name;
        $data['level'] = $level;
        $data['pid'] = $pid;
        $data['type'] = $type;
        $data['status'] = $status;
        $data['addtime'] = time();
        $data['picurl'] = $picurl;
        $res = $cateModel->addCategory($data);
        return $res;
    }

    public function saveCategory($id,$name = '', $pid = 0, $level = 1, $type =1, $status = 1,$picurl = '')
    {
        if (!$name) return false;
        $cateModel = new CategoryModel();
        $isCategory = $cateModel->isCategory($name, $type,$level,$id);
        if ($isCategory) response("分类已存在");
        $data['name'] = $name;
        $data['level'] = $level;
        $data['pid'] = $pid;
        $data['type'] = $type;
        $data['status'] = $status;
        $data['picurl'] = $picurl;
        $where['id'] = $id;
        $res = M('category')->where($where)->save($data);
        return $res;
    }

    /**
     * @删除分类
     * @param int $id
     * @return bool
     */
    public function delCategory($id =0)
    {
        if (!$id) return false;
        $cateModel = new CategoryModel();
        return $cateModel->delCategory($id);
    }

    /**
     * @breif 获取pid下所有分类信
     * @param int $type
     * @param int $pid
     * @return bool
     */
    public function getCategorys($type = 1, $pid = 0,$status = 0)
    {
        $cateModel = new CategoryModel();
        return $cateModel->getCategorys($type,$pid,$status);
    }

    /**
     * @breif 获取所有分类
     * @param int $type
     * @param int $pid
     * @return bool
     */
    public function getAllCategorys()
    {
        $cateModel = new CategoryModel();
        return $cateModel->getAllCategorys();
    }
    /**
     * @breif 结果format  ['0'=>['id'=>1,'name'=>'健康'], '1'=>['id'=>4,'name'=>'跑步']]
     * @param int $id
     * @return array|bool
     */
    public function getCategorynameById($id = 0)
    {
        if (!$id) return false;
        $cateModel = new CategoryModel();
        $allCate = $cateModel->getAllCategorys();
        $arr = $this->getcates($allCate,$id);
        return $arr;
    }

    private function getcates($data, $id)
    {
        $temp = [];
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['id'] == $id) {
                $temp = $data[$i];
                break;
            }
        }
        if ($temp['pid']) {
            $arr = $this->getcates($data,$temp['pid']);
            $arr[] =['id'=>$temp['id'],'name'=>$temp['name']];
        } else {
            $arr[0] =['id'=>$temp['id'],'name'=>$temp['name']];

        }
        return $arr;
    }
}