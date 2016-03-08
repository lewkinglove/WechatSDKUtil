<?php

/**
 * 微信SDK工具类<br>
 * <pre>
 *  目前仅提供以下功能:
 *      access_token的获取和缓存
 *      jsapi_ticket的获取和缓存
 *      针对微信JS-SDK的页面进行签名
 * </pre>
 * @author liujing(lewkinglove@gmail.com)
 */
class WechatSDKUtil
{

    /**
     * 微信开放平台AppId
     *
     * @var string
     */
    private static $wxAppId;

    /**
     * 微信开放平台AppSecret
     *
     * @var string
     */
    private static $wxAppSecret;

    /**
     * 提前过期时间, 单位: 秒
     *
     * @var integer
     */
    private static $expireAheadTime;

    /**
     * access_token文件缓存路径
     *
     * @var string
     */
    private static $accessTokenCachePath;

    /**
     * jsapi_ticket文件缓存路径
     *
     * @var string
     */
    private static $jsapiTicketCachePath;

    /**
     * 初始化方法<br>
     * 请在使用其他方法之前执行本方法进行初始化
     *
     * @param string $_wxAppId
     *            微信开放平台AppId
     * @param string $_wxAppSecret
     *            微信开放平台AppSecret
     * @param string $_runtimePath
     *            运行时文件缓存路径, 要求可读可写, 如不存在, 则会自动创建
     * @param integer $_expireAheadTime
     *            已缓存数据提前过期时间, 单位: 秒
     */
    public static function initial($_wxAppId, $_wxAppSecret, $_runtimePath = '.', $_expireAheadTime = 30)
    {
        self::$wxAppId = $_wxAppId;
        self::$wxAppSecret = $_wxAppSecret;
        
        self::$expireAheadTime = intval($_expireAheadTime);
        
        if ($_runtimePath != '.' && file_exists($_runtimePath) == false)
            mkdir($_runtimePath, 0775);
        
        self::$accessTokenCachePath = "{$_runtimePath}/WX_ACCESS_TOKEN.php";
        self::$jsapiTicketCachePath = "{$_runtimePath}/WX_JSAPI_TICKET.php";
    }

    /**
     * 获取用于JS-SDK的wx.config()方法的签名等参数
     *
     * @param string $url
     *            要签名的网页地址, 参见: http://mp.weixin.qq.com/wiki/7/aaa137b55fb2e0456bf8dd9148dd613f.html#.E9.99.84.E5.BD.951-JS-SDK.E4.BD.BF.E7.94.A8.E6.9D.83.E9.99.90.E7.AD.BE.E5.90.8D.E7.AE.97.E6.B3.95
     * @return array
     */
    public static function getJsSdkConfig($url)
    {
        $jsTicket = self::getJsapiTicket();
        $result = array(
            'appId' => wxAppId,
            'nonceStr' => 'noncestrnoncestr',
            'timestamp' => $_SERVER['REQUEST_TIME']
        );
        
        $str = "jsapi_ticket={$jsTicket}&noncestr={$result['nonceStr']}&timestamp={$result['timestamp']}&url={$url}";
        $result['signature'] = sha1($str);
        return $result;
    }

    /**
     * 获取jsapi_ticket<br>
     * 如果缓存的jsapi_ticket已过期, 则会强制刷新jsapi_ticket.
     *
     * @return string
     */
    public static function getJsapiTicket()
    {
        $data = include self::$jsapiTicketCachePath;
        if ($data === false || is_array($data) == false || $data['expire_at'] < time() + self::$expireAheadTime) {
            return self::reloadJsapiTicket();
        }
        return $data['ticket'];
    }

    /**
     * 获取access_token<br>
     * 如果缓存的access_token已过期, 则会强制刷新access_token.
     *
     * @return string
     */
    public static function getAccessToken()
    {
        $data = include self::$accessTokenCachePath;
        if ($data === false || is_array($data) == false || $data['expire_at'] < time() + self::$expireAheadTime) {
            return self::reloadAccessToken();
        }
        return $data['access_token'];
    }

    /**
     * 重新加载新的jsapi_ticket
     *
     * @return string
     */
    private static function reloadJsapiTicket()
    {
        $accessToken = self::getAccessToken();
        // 获取jsapi_ticket
        // https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=ACCESS_TOKEN&type=jsapi
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={$accessToken}&type=jsapi";
        $data = file_get_contents($url);
        
        if (empty($data))
            throw new \Exception("获取JsAPITicket接口请求失败, 收到数据为空.");
        
        $data = json_decode($data, true);
        
        if (isset($data['errcode']) && $data['errcode'] != '0')
            throw new \Exception('获取JsAPITicket接口未返回有效数据, 参考: ' . var_export($data, true));
        
        $data['get_at'] = time();
        $data['expire_at'] = $data['get_at'] + intval($data['expires_in']);
        
        file_put_contents(self::$jsapiTicketCachePath, '<?php return ' . var_export($data, true) . ';');
        return $data['ticket'];
    }

    /**
     * 重新加载新的access_token
     *
     * @return string
     */
    private static function reloadAccessToken()
    {
        // 获取AccessToken
        // https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . self::$wxAppId . '&secret=' . self::$wxAppSecret;
        $data = file_get_contents($url);
        
        if (empty($data))
            throw new \Exception("获取AccessToken接口请求失败, 收到数据为空.");
        
        $data = json_decode($data, true);
        
        if (isset($data['errcode']) && $data['errcode'] != '0')
            throw new \Exception('获取AccessToken接口未返回有效数据, 参考: ' . var_export($data, true));
        
        $data['get_at'] = time();
        $data['expire_at'] = $data['get_at'] + intval($data['expires_in']);
        
        file_put_contents(self::$accessTokenCachePath, '<?php return ' . var_export($data, true) . ';');
        
        return $data['access_token'];
    }
}