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
     * 例如 bright-xyj.oss-cn-beijing.aliyuncs.com
     * @var string
     */
    protected $endpoint;

    /**
     * 回调路径
     * @var string
     */
    protected $callbackUrl;

    /**
     * bucket名称
     * @var string
     */
    protected $bucket;

    /**
     * CDN域名,不使用CDN 不需设置
     * @var string
     */
    protected $cdnEndpoint;

    /**
     * 图片服务域名,不使用图片服务不需设置
     * 如果使用图片服务,则设置为图片服务的域名(使用CDN加速的请设置为图片CDN的域名)
     * @var string
     */
    protected $imgEndpoint;

    /**
     * OssPostClient constructor.
     * @param string $accessKeyId
     * @param string $accessKeySecret
     * @param string $endpoint
     * @param string $callbackUrl
     * @param string $bucket
     * @param string $cdnEndpoint CDN域名,不使用CDN 不需设置
     * @param string $imgEndpoint 图片服务域名,不使用图片服务不需设置
     */
    public function __construct($accessKeyId, $accessKeySecret, $endpoint, $callbackUrl, $bucket, $cdnEndpoint = null, $imgEndpoint = null)
    {
        $this->accessKeyId = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
        $this->endpoint = $endpoint;
        $this->callbackUrl = $callbackUrl;
        $this->bucket = $bucket;
        $this->cdnEndpoint = $cdnEndpoint;
        $this->imgEndpoint = $imgEndpoint;
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
            ['content-length-range', 0, 1048576000],  //最大文件大小.用户可以自己设置
            //[ 'starts-with', '$key', $fullname], //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        ];

        $arr = array('expiration' => $expiration, 'conditions' => $conditions);
        $policy = json_encode($arr);
        $base64Policy = base64_encode($policy);
        $string2Sign = $base64Policy;

        $response = [
            'accessid' => $this->accessKeyId,
            'host' => $this->getUploadEndPoint(),
            'policy' => $base64Policy,
            'signature' => base64_encode(hash_hmac('sha1', $string2Sign, $this->accessKeySecret, true)),
            'expire' => $end,
            'callback' => $base64_callback_body,
        ];

        //这个参数是设置用户上传指定的前缀
        return $response;
    }

    public function getUploadEndPoint()
    {
        return 'http://' . $this->bucket . '.' . $this->endpoint;
    }

    public function getFileViewEndpoint()
    {
        if ($this->cdnEndpoint) {
            return 'http://' . $this->cdnEndpoint;
        } else {
            return $this->getUploadEndPoint();
        }
    }

    public function getImageViewEndpoint()
    {
        if ($this->imgEndpoint) {
            return 'http://' . $this->imgEndpoint;
        } elseif ($this->cdnEndpoint) {
            return 'http://' . $this->cdnEndpoint;
        } else {
            return $this->getUploadEndPoint();
        }
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