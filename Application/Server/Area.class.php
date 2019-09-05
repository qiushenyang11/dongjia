<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/10/27
 * Time: 10:56
 */

namespace Server;


use Common\Model\AreaModel;

class Area
{
    private $zhixiashi = ['北京', '上海', '天津', '重庆'];

    public function getAreaListByAreaId($areaid = '')
    {
        if ($areaid === '') return false;
        if ($areaid < 0) return false;
        $areaModel = new AreaModel();
        $res = $areaModel->getAreaListByAreaId($areaid);
        return $res;
    }

    public function getAddressByAreaId($areaid = '')
    {
        if (!$areaid) return false;
        $length = strlen($areaid);
        $areaModle = new AreaModel();
        $address = '';
        if ($length < 4) {
            $address = $areaModle->getAreaNameByAreaId($areaid);
            if (in_array($address, $this->zhixiashi)) {
                $address .= '市';
            } else {
                $address .= '省';
            }
        } else {
            $temp = substr($areaid, -8);
            $provinceid = substr($areaid, 0, $length - 8);
            $blockid = substr($temp, -6);
            $cityid = substr($temp, 0, 2);
            if ($blockid == '000000') {   //areaid 属于二级
                $areaids = [$provinceid, $temp];
            } else {                      //areaid属于三级
                $areaids = [$provinceid, $provinceid . $cityid . '000000', $areaid];
            }
            $address = $areaModle->getAddressByAreaid($areaids);
            $temp = '';
            if (in_array($address[0], $this->zhixiashi)) {
                $temp .= $address[0] . '市';
            } else {
                $temp .= $address[0] . '省';
            }
            $temp .= $address[1] . $address[2];
            $address = $temp;
        }
        return $address;
    }

    public function getAreaIdAndAreanameByAreaId($areaid = '')
    {
        if (!$areaid) return false;
        $length = strlen($areaid);
        $areaModle = new AreaModel();
        $address = '';
        if ($length < 4) {
            $address = $areaModle->getAreaidAndAreanameByAreaId($areaid);
            if (in_array($address['areaname'], $this->zhixiashi)) {
                $address['areaname'] .= '市';
            } else {
                $address['areaname'] .= '省';
            }
        } else {
            $temp = substr($areaid, -8);
            $provinceid = substr($areaid, 0, $length - 8);
            $blockid = substr($temp, -6);
            $cityid = substr($temp, 0, 2);
            if ($blockid == '000000') {   //areaid 属于二级
                $areaids = [$provinceid, $areaid];
            } else {                      //areaid属于三级
                $areaids = [$provinceid, $provinceid . $cityid . '000000', $areaid];
            }
            $address = $areaModle->getAreadIdAndAreanameByAreaid($areaids);
            if (in_array($address[0]['areaname'], $this->zhixiashi)) {
                $address[0]['areaname'] = $address[0]['areaname'] . '市';
            } else {
                $address[0]['areaname'] = $address[0]['areaname'] . '省';
            }
        }
        return $address;
    }

    public function getIdsByAreaid($areaid = '')
    {
        if (!$areaid) return false;
        $length = strlen($areaid);
        $result = [];
        if ($length <4) {
            $result['provinceid'] = $areaid;
        } else {
            $temp = substr($areaid, -8);
            $provinceid = substr($areaid, 0, $length - 8);
            $blockid = substr($temp, -6);
            $cityid = substr($temp, 0, 2);
            if ($blockid == '000000') {   //areaid 属于二级
                $result['provinceid'] = $provinceid;
                $result['cityid'] = $areaid;
            } else {                      //areaid属于三级
                $result['provinceid'] = $provinceid;
                $result['cityid'] =  $provinceid . $cityid . '000000';
                $result['blockid'] = $areaid;
            }
        }
        return $result;
    }


}