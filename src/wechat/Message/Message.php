<?php
// +----------------------------------------------------------------------
// | A3Mall
// +----------------------------------------------------------------------
// | Copyright (c) 2020 http://www.a3-mall.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: xzncit <158373108@qq.com>
// +----------------------------------------------------------------------

namespace xzncit\wechat\Message;

use xzncit\core\base\BaseWeChat;
use xzncit\core\base\Prpcrypt;
use xzncit\core\exception\ConfigNotFoundException;
use xzncit\core\http\Request;
use xzncit\core\http\Response;
use xzncit\core\Service;
use xzncit\core\Utils;


class Message extends BaseWeChat {

    protected $receive;
    protected $message;

    /**
     * Message constructor.
     * @param Service $app
     */
    public function __construct(Service $app){
        parent::__construct($app);
        $this->initialization();
    }

    protected function initialization(){
        switch(Request::getMethod()){
            case "POST":
                $data = file_get_contents("php://input");
                $this->receive = Response::xml2arr($data);
                if($this->isEncryptAES()){
                    if(empty($this->app->config["enaeskey"])){
                        throw new ConfigNotFoundException("wechat enaeskey cannot be empty!");
                    }

                    $prpcrypt = new Prpcrypt($this->app->config["enaeskey"]);
                    $array = $prpcrypt->decrypt($this->receive['Encrypt']);
                    if (intval($array[0]) > 0) {
                        throw new \Exception($array[1], $array[0]);
                    }

                    $this->receive = Response::xml2arr($array[1]);
                }
                break;
            case "GET":
                if(!$this->checkSignature()){
                    throw new \Exception("error verifying signature！",0);
                }

                @ob_clean();
                exit(Request::request("echostr"));
            default:
                throw new \Exception("Your current request is illegal！",0);
        }
    }

    /**
     * 回复文本消息
     * @param string $content 文本内容
     * @return $this
     */
    public function text($content = ''){
        $this->message = [
            'MsgType'      => 'text',
            'CreateTime'   => time(),
            'Content'      => $content,
            'ToUserName'   => $this->getOpenid(),
            'FromUserName' => $this->getToOpenid(),
        ];

        return $this;
    }

    /**
     * 回复图片消息
     * @param string $mediaId 图片媒体ID
     * @return $this
     */
    public function image($mediaId = ''){
        $this->message = [
            'MsgType'      => 'image',
            'CreateTime'   => time(),
            'ToUserName'   => $this->getOpenid(),
            'FromUserName' => $this->getToOpenid(),
            'Image'        => ['MediaId' => $mediaId],
        ];

        return $this;
    }

    /**
     * 回复语音消息
     * @param string $mediaId   通过素材管理中的接口上传多媒体文件，得到的id
     * @return $this
     */
    public function voice($mediaId = ''){
        $this->message = [
            'MsgType'      => 'image',
            'CreateTime'   => time(),
            'ToUserName'   => $this->getOpenid(),
            'FromUserName' => $this->getToOpenid(),
            'Voice'        => ['MediaId' => $mediaId],
        ];

        return $this;
    }

    /**
     * 回复视频消息
     * @param array $data
     * @return $this
     */
    public function video($data = []){
        $this->message = [
            'CreateTime'   => time(),
            'MsgType'      => 'video',
            'Video'     => $data,
            'ToUserName'   => $this->getOpenid(),
            'FromUserName' => $this->getToOpenid()
        ];

        return $this;
    }

    /**
     * 回复音乐消息
     * @param array $data
     * @return $this
     */
    public function music($data = []){
        $this->message = [
            'CreateTime'   => time(),
            'MsgType'      => 'music',
            'Music'     => $data,
            'ToUserName'   => $this->getOpenid(),
            'FromUserName' => $this->getToOpenid()
        ];

        return $this;
    }

    /**
     * 回复图文消息
     * @param array $newsData
     * @return $this
     */
    public function news($newsData = []){
        $this->message = [
            'CreateTime'   => time(),
            'MsgType'      => 'news',
            'Articles'     => $newsData,
            'ToUserName'   => $this->getOpenid(),
            'FromUserName' => $this->getToOpenid(),
            'ArticleCount' => count($newsData),
        ];

        return $this;
    }

    /**
     * 回复消息
     * @param array $data
     * @param bool $return
     * @param false $isEncrypt
     * @return string
     * @throws \Exception
     */
    public function reply(array $data = [], $return = true, $isEncrypt = false){
        $xml = Response::arr2xml(empty($data) ? $this->message : $data);
        if ($this->isEncryptAES() || $isEncrypt) {
            $prpcrypt = new Prpcrypt($this->app->config["enaeskey"]);
            $array = $prpcrypt->encrypt($xml, $this->app->config["appid"]);
            if ($array[0] > 0) {
                throw new \Exception('Encrypt Error.', '0');
            }

            list($timestamp, $encrypt) = [time(), $array[1]];
            $nonce = rand(77, 999) * rand(605, 888) * rand(11, 99);
            $tmpArr = [$this->app->config["token"], $timestamp, $nonce, $encrypt];
            sort($tmpArr, SORT_STRING);
            $signature = sha1(implode($tmpArr));
            $format = "<xml><Encrypt><![CDATA[%s]]></Encrypt><MsgSignature><![CDATA[%s]]></MsgSignature><TimeStamp>%s</TimeStamp><Nonce><![CDATA[%s]]></Nonce></xml>";
            $xml = sprintf($format, $encrypt, $signature, $timestamp, $nonce);
        }

        if ($return) {
            return $xml;
        }

        @ob_clean();
        echo $xml;
    }

    /**
     * 被动回复处理
     * @param \Closure $closure
     * @return mixed
     */
    public function push(\Closure $closure){
        $receive = Utils::lowerCase($this->getReceive());
        return $closure($receive['msgtype']);
    }

    /**
     * 获取公众号推送对象
     * @param null|string $field 指定获取字段
     * @return array
     */
    public function getReceive($field = null){
        return empty($field) ? $this->receive : $this->receive[$field];
    }

    /**
     * 获取当前微信OPENID
     * @return string
     */
    public function getOpenid(){
        return $this->receive["FromUserName"];
    }

    /**
     * 获取当前推送消息类型
     * @return string
     */
    public function getMsgType(){
        return $this->receive["MsgType"];
    }

    /**
     * 获取当前推送消息ID
     * @return string
     */
    public function getMsgId(){
        return $this->receive["MsgId"];
    }

    /**
     * 获取当前推送时间
     * @return integer
     */
    public function getMsgTime(){
        return $this->receive["CreateTime"];
    }

    /**
     * 获取当前推送公众号
     * @return string
     */
    public function getToOpenid(){
        return $this->receive["ToUserName"];
    }

    /**
     * 检查是否为加密模式
     * @return bool
     */
    protected function isEncryptAES(){
        return Request::request("encrypt_type","") == "aes";
    }

    /**
     * 验证来自微信服务器数据
     * @param string $str
     * @return bool
     */
    private function checkSignature($str = ''){
        $msg_signature = Request::request("msg_signature");
        $array = [$this->app->config["token"], Request::request("timestamp"), Request::request("nonce"), $str];
        sort($array, SORT_STRING);
        return sha1(implode($array)) === (empty($msg_signature) ? Request::request("signature") : $msg_signature);
    }

}