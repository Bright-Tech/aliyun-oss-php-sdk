<?php
/**
 * Created by PhpStorm.
 * User: SamXiao
 * Date: 16/9/2
 * Time: 上午11:31
 */

namespace OSS;


class OssUtilities
{

    /**
     * 例如 bright-xyj.oss-cn-beijing.aliyuncs.com
     * @var string
     */
    protected $endpoint;


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
     * OssUtilities constructor.
     * @param string $endpoint
     * @param string $bucket
     * @param string $cdnEndpoint CDN域名,不使用CDN 不需设置
     * @param string $imgEndpoint 图片服务域名,不使用图片服务不需设置
     */
    public function __construct($endpoint, $bucket, $cdnEndpoint = null, $imgEndpoint = null)
    {
        $this->endpoint = $endpoint;
        $this->bucket = $bucket;
        $this->cdnEndpoint = $cdnEndpoint;
        $this->imgEndpoint = $imgEndpoint;
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


}