<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/10
 * Time: 15:52
 */

namespace Operation\Model;


class MessageModel
{
    public function addMessage($message)
    {
        $data['message'] = $message;
        $data['created_date'] = time();
        return M('message')->data($data)->add();
    }

    public function saveMessage($id, $message)
    {
        $where['id'] = $id;
        $data['message'] = $message;
        return M('message')->where($where)->save($data);
    }

    public function delMessage($id)
    {
        $where['id'] = $id;
        $data['isdelete'] = 1;
        return M('message')->where($where)->save($data);
    }

    public function getMessageByProductid($id)
    {
        $where['p.id'] = $id;
        $where['m.isdelete'] = 0;
        return M('product as p')
            ->join('__MESSAGE__ as m ON p.messageid = m.id','left')
            ->where($where)
            ->getField('m.message');
    }
    public function getMessageByid($id)
    {
        $where['id'] = $id;
        $where['isdelete'] = 0;
        return M('message')->where($where)->getField('message');
    }
}