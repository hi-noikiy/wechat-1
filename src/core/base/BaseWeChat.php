<?php
// +----------------------------------------------------------------------
// | A3Mall
// +----------------------------------------------------------------------
// | Copyright (c) 2020 http://www.a3-mall.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: xzncit <158373108@qq.com>
// +----------------------------------------------------------------------

namespace xzncit\core\base;

use xzncit\core\Service;

class BaseWeChat {

    protected $app;

    public function __construct(Service $app){
        $this->app = $app;
    }

}