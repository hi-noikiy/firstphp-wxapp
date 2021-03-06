<?php

declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: 狂奔的螞蟻 <www.firstphp.com>
 * Date: 2019/9/10
 * Time: 下午16:20
 */

namespace Firstphp\FirstphpWxapp;


use Firstphp\FirstphpWxapp\Bridge\Http;
use Psr\Container\ContainerInterface;

class WxappClient implements WxappInterface
{

    const OK = 0;
    const ILLEGAL_AES_KEY = -40001;
    const ILLEGAL_IV = -40002;
    const ILLEGAL_BUFFER = -40003;
    const DECODE_BASE64_ERROR = -40004;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $appid;

    /**
     * @var string
     */
    protected $appsecret;

    /**
     * @var string
     */
    protected $appkey;

    /**
     * @var object
     */
    protected $http;


    /**
     * @var ContainerInterface
     */
    protected $container;


    public function __construct(array $config = [], ContainerInterface $container)
    {
        $config = $config ? $config : config('wxapp');
        if ($config) {
            $this->url = $config['url'];
            $this->appid = $config['appid'];
            $this->appsecret = $config['appsecret'];
            $this->appkey = $config['wxapp_key'];
        }
        $this->http = $container->make(Http::class, compact('config'));
    }


    /**
     * @param string $code
     * @return mixed
     */
    public function login(string $code)
    {
        return $this->http->get('sns/jscode2session', [
            'query' => [
                'appid' => $this->appid,
                'secret' => $this->appsecret,
                'js_code' => $code,
                'grant_type' => 'authorization_code'
            ]
        ]);
    }


    /**
     * @return mixed
     */
    public function getAccessToken()
    {
        return $this->http->get('cgi-bin/token', [
            'query' => [
                'appid' => $this->appid,
                'secret' => $this->appsecret,
                'grant_type' => 'client_credential'
            ]
        ]);
    }


    /**
     * @param string $path
     * @param string $accessToken
     * @param int $width
     * @return array
     */
    public function createWxaQrcode(string $path = '/', string $accessToken = '', int $width = 430)
    {
        return $this->http->post('cgi-bin/wxaapp/createwxaqrcode?access_token=' . $accessToken, [
            'json' => [
                'path' => $path,
                'width' => $width
            ]
        ]);
    }


    /**
     * @param string $path
     * @param int $width
     * @param string $accessToken
     * @param bool|false $is_hyaline
     */
    public function getWxacode(string $path = '/', string $accessToken = '', int $width = 430, bool $is_hyaline = false)
    {
        return $this->http->post('wxa/getwxacode?access_token=' . $accessToken, [
            'json' => [
                'path' => $path,
                'width' => $width,
                'is_hyaline' => $is_hyaline
            ]
        ]);
    }


    /**
     * 生成小程序二维码
     */
    public function getWxacodeunlimi11t($path = '/', $accessToken = '') {
        $params = [
            'scene' => 'id=1',
            'path' => $path,
            'width' => 430,
        ];
        $res = $this->httpPostJson('https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$accessToken, json_encode($params));
        $decodeRes = json_decode($res[1], true);
        if (isset($decodeRes['errcode'])) {
            return ['code' => $decodeRes['errcode'], 'msg' =>$decodeRes['errmsg']];
        } else {
            return ['code' => 200, 'data' => $res[1]];
        }
    }


    /**
     * @param string $scene
     * @param string $page
     * @param string $accessToken
     * @param int $width
     * @param bool|false $is_hyaline
     */
    public function getWxacodeunlimit(string $scene='id=1', string $page='', string $accessToken = '', int $width = 280, bool $is_hyaline = false)
    {
        return $this->http->post('wxa/getwxacodeunlimit?access_token=' . $accessToken, [
            'form_params' => [
                'scene' => $scene,
                'path' => $page,
                'width' => $width,
            ]
        ]);
    }


    /**
     * @param array $params
     * @param string $accessToken
     */
    public function sendTemplateMessage(array $params, string $accessToken)
    {
        return $this->http->post('cgi-bin/message/wxopen/template/send?access_token=' . $accessToken, [
            'form_params' => $params
        ]);
    }


    /**
     * @param int $page
     * @param int $page_rows
     * @param string $accessToken
     */
    public function getNearbypoilist(int $page, int $page_rows, string $accessToken)
    {
        return $this->http->get('wxa/getnearbypoilist?access_token=' . $accessToken, [
            'query' => [
                'page' => $page,
                'page_rows' => $page_rows
            ]
        ]);
    }


    /**
     * @param string $media
     * @param string $accessToken
     */
    public function imgSecCheck(string $media, string $accessToken = '')
    {
        return $this->http->post('wxa/img_sec_check?access_token=' . $accessToken, [
            'form_params' => [
                'media' => $media
            ]
        ]);
    }


    /**
     * 组合模板消息
     *
     * @param array $params
     * @return array
     */
    public function templateMsg(array $params = [])
    {
        // $params 格式
        /**
         * $params = [
         * 'touser' => $openid,
         * 'template_id' => $templateId,
         * 'pagepath' => "pages/partner/main?target=2&voteId=1",
         * 'keynote' => 1,
         * 'data' => [
         * ['#343434', $firstData],
         * ['#458cad', $taskInfo['title']],
         * ['#343434', date('Y年m月d日 H:i', $voteInfo['created_at'])],
         * ['#343434', $taskInfo['content']."\n\n-->点击查看该任务"],
         * ]
         * ];
         */
        $tempMsg = [
            'touser' => $params['touser'],
            'template_id' => $params['template_id'],
            'miniprogram' => [
                'appid' => $this->appid,
                'pagepath' => $params['pagepath'],
            ]
        ];
        $data = [];
        for ($i = 0; $i < count($params['data']); $i++) {
            if ($i == 0) {
                $keyName = 'first';
            } else if ($i == count($params['data']) - 1) {
                $keyName = 'remark';
            } else {
                if ($params['keynote'] == 0) {
                    $keyName = 'keyword' . $i;
                } else {
                    $keyName = 'keynote' . $i;
                }
            }
            $data[$keyName] = [
                'value' => $params['data'][$i][1],
                'color' => $params['data'][$i][0]
            ];
        }

        $tempMsg['data'] = $data;

        return $tempMsg;

    }


    /**
     * 获取签名
     *
     * @param array $params
     * @param string $key
     * @return string
     */
    public function getSign(array $params, string $key)
    {
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) {
            $str .= $k . '=' . $v . '&';
        }

        $str .= 'key=' . $key;
        $sign = strtoupper(md5($str));
        return $sign;
    }


}