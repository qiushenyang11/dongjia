<?php

namespace BaoXian\Model;

class UserFDBModel extends BaoxianModel
{
    public function __construct()
    {
        $this->trueTableName="userfdb";

        parent::__construct();
    }


}