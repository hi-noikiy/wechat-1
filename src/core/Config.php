<?php
// +----------------------------------------------------------------------
// | A3Mall
// +----------------------------------------------------------------------
// | Copyright (c) 2020 http://www.a3-mall.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: xzncit <158373108@qq.com>
// +----------------------------------------------------------------------

namespace xzncit\core;


use xzncit\Factory;

class Config {

    /**
     * @var array $config
     */
    private static $config = [
        'http' => [
            'base_uri' => 'https://api.weixin.qq.com/',
            'timeout' => 30.0
        ],
        "wechat"=>[
            "token"=>"",
            "appid"=>"",
            "appsecret"=>"",
            "enaeskey"=>"",
            // 支付
            "mch_id"=>"",
            "mch_key"=>"",
            "ssl_cer"=>"", // 证书pem
            "ssl_key"=>"" // 证书密钥
        ],
        "miniprogram"=>[
            "appid"=>"",
            "appsecret"=>"",
            // 支付
            "mch_id"=>"",
            "mch_key"=>"",
            "ssl_cer"=>"", // 证书pem
            "ssl_key"=>"" // 证书密钥
        ],
        "log"=>[
            "path"=>"",
            "name"=>"My",
            "type"=>"json", // line|json
            "filename" => "hash", // hash|date
        ],
        "cache"=>[
            "path"=>""
        ]
    ];

    /**
     * @param null|string $name
     * @return array|string|null
     */
    public static function get($name = null) {
        if (is_null($name)) {
            return self::$config;
        }

        $data = explode('.', $name);
        $count = count($data);
        $array = $temp = null;
        for ($i = 0; $i < $count; $i++) {
            if (empty($array)) {
                $array = !empty(self::$config[$data[$i]]) ? self::$config[$data[$i]] : null;
            } else {
                $temp = $array[$data[$i]];
                $array = $temp;
            }
        }

        return $array;
    }

    /**
     * @param string|array $name
     * @param null         $value
     */
    public static function set($name, $value=null){
        if(is_string($name)){
            self::$config[$name] = $value;
        }else{
            self::$config = self::merge(self::$config,$name);
        }
    }

    public static function isEmpty($name,$default=""){
        return empty(self::$config[$name]) ? $default : self::$config[$name];
    }

    /**
     * @param null|string $name
     */
    public static function unbind($name = null){
        if (is_null($name)) {
            self::$config = [];
        } else {
            unset(self::$config[$name]);
        }
    }

    /**
     * @param mixed ...$args
     * @return array
     */
    public static function merge(...$args){
        $array = [];
        while($args){
            $value = array_shift($args);
            if(!empty($value)){
                continue;
            }

            foreach($value as $key=>$item){
                if(is_string($key)){
                    if((is_array($item) && isset($array[$key])) && is_array($array[$key])){
                        $array[$key] = self::merge(...[$array[$key],$item]);
                    }else{
                        $array[$key] = $item;
                    }
                }else{
                    $array[] = $item;
                }
            }
        }

        return $array;
    }

    public static function init(){
        $path = rtrim(Factory::getRootPath(),"/") . "/rumtime/";

        if(empty(self::$config["log"]["path"])){
            $logPath = $path . "log/";
        }else{
            $logPath = rtrim(self::$config["log"]["path"],"/") . "/";
        }

        if(!is_dir($logPath)){
            mkdir($logPath,0777,true);
        }

        switch (self::$config["log"]['filename']){
            case "hash":
                $logPath .= md5(date("Ymd")) . ".log";
                break;
            case "date":
                $logPath .= date("YmdHis") . ".log";
                break;
        }

        self::$config["log"]["path"] = $logPath;

        if(!empty(self::$config["cache"]["path"])){
            $cachePath = rtrim(self::$config["cache"]["path"],"/") . "/";
        }else{
            $cachePath = $path . "cache/";
        }

        if(!file_exists($cachePath)){
            mkdir($cachePath,0777,true);
        }

        self::$config["cache"]["path"] = $cachePath;
    }
}