<?php
/**
 * Created by PhpStorm.
 * User: SamXiao
 * Date: 16/9/2
 * Time: 上午11:31
 */

namespace OSS;


class OssPostClient
{
    protected $accessKeyId;
    protected $accessKeySecret;


    /**
     * 回调路径
     * @var string
     */
    protected $callbackUrl;



    public $minFileSize = 0;
    public $maxFileSize = 1048576000;

    /**
     * OssPostClient constructor.
     *
     * @param string $accessKeyId
     * @param string $accessKeySecret
     * @param string $callbackUrl
     */
    public function __construct($accessKeyId, $accessKeySecret, $callbackUrl)
    {
        $this->accessKeyId = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
        $this->callbackUrl = $callbackUrl;
    }


    public function getClientPolicy()
    {

        $callback_param = array('callbackUrl' => $this->callbackUrl,
            'callbackBody' => 'object=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
            'callbackBodyType' => "application/x-www-form-urlencoded");
        $callback_string = json_encode($callback_param);

        $base64_callback_body = base64_encode($callback_string);
        $now = time();
        $expire = 300; //设置该policy超时时间是300s. 即这个policy过了这个有效时间，将不能访问
        $end = $now + $expire;
        $expiration = $this->gmtISO8601($end);

        $conditions = [
            ['content-length-range', $this->minFileSize, $this->maxFileSize],  //最大文件大小.用户可以自己设置
            //[ 'starts-with', '$key', $fullname], //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        ];

        $arr = array('expiration' => $expiration, 'conditions' => $conditions);
        $policy = json_encode($arr);
        $base64Policy = base64_encode($policy);
        $string2Sign = $base64Policy;

        $response = [
            'accessid' => $this->accessKeyId,

//            'host' => $this->utilities->getUploadEndPoint(),
            'policy' => $base64Policy,
            'signature' => base64_encode(hash_hmac('sha1', $string2Sign, $this->accessKeySecret, true)),
            'expire' => $end,
            'callback' => $base64_callback_body,
        ];

        //这个参数是设置用户上传指定的前缀
        return $response;
    }


    /**
     * @param $time
     * @return string
     */
    protected function gmtISO8601($time)
    {
        $dtStr = date("c", $time);
        $mydatetime = new \DateTime($dtStr);
        $expiration = $mydatetime->format(\DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration . "Z";
    }
}