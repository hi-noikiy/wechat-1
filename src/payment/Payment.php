<?php
// +----------------------------------------------------------------------
// | A3Mall
// +----------------------------------------------------------------------
// | Copyright (c) 2020 http://www.a3-mall.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: xzncit <158373108@qq.com>
// +----------------------------------------------------------------------

namespace xzncit\payment;

use xzncit\core\Config;
use xzncit\core\exception\ConfigNotFoundException;
use xzncit\core\Service;

/**
 * class Payment
 *
 * @property \xzncit\mini\OAuth\OAuth                                         $wechat
 */
class Payment extends Service{

    /**
     * @var string[]
     */
    protected $providers = [
        "oauth"                  =>      OAuth\ProviderService::class,
    ];

    /**
     * Wechat constructor.
     * @param array $config
     * @throws ConfigNotFoundException
     */
    public function __construct(array $config){
        if(empty($config["appid"])){
            throw new ConfigNotFoundException("miniprogram appid cannot be empty!",0);
        }

        if(empty($config["appsecret"])){
            throw new ConfigNotFoundException("miniprogram appsecret cannot be empty!",0);
        }

        parent::__construct($config);
        Config::set($config);
    }

}