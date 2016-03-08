<?php
require_once 'WechatSDKUtil.php';

$wxAppId = 'your_wechat_appId';
$wxAppSecret = 'your_wechat_appSecret';

$runtimePath = './runtime/';
$expireAheadTime = 30;

WechatSDKUtil::initial($wxAppId, $wxAppSecret, $runtimePath);

$accessToken = WechatSDKUtil::getAccessToken();
echo "accessToken: {$accessToken}<br/>\r\n";

$jsapiTicket = WechatSDKUtil::getJsapiTicket();
echo "jsapiTicket: {$jsapiTicket}<br/>\r\n";

$jssdkConfig = WechatSDKUtil::getJsSdkConfig($_REQUEST['url']);
echo "jssdkConfig: ".json_encode($jssdkConfig)."<br/>\r\n";