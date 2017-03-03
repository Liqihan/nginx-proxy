<?php
// +----------------------------------------------------------------------
// | PlayM3u8 播放器配置文件
// +----------------------------------------------------------------------
// | 本播放器采用的全部是m3u8模式播放支持PC端和手机端
// +----------------------------------------------------------------------
// | Licensed ( http://www.playm3u8com/ )
// +----------------------------------------------------------------------
// | Author: QQ:2621633663 <admin@playm3u8.com>
// +----------------------------------------------------------------------

// 绑定播放域名，不绑定就没法播放的哦！(子域名一样需要绑定)
// 播放器所在域名无需绑定
$auth_domain = array(
	"benditoutiao.com",
	"www.benditoutiao.com",
	"videoplayer.benditoutiao.com",
	"neo.benditoutiao.com",
	"videoplayer-test.benditoutiao.com",
	"videoserver.benditoutiao.com",
    "videoserver-test.benditoutiao.com",
);

// 115网盘云播登陆Cookie（不需要可以无视）注意账号必须是VIP哦
// 温馨提示：一般Cookie有效期为5-7天,建议如果更新上传115视频选择Cookie模式。
$y115_data = array(
	"y115_cookie" => "",
);

// 播清晰度 * 默认为3 [1,2,3标清|高清|超清](部分视频可能只有一种)
define("play_hd", 2);

// 播放器是否为自动播放
define("Auto_play", true);

// 资源解密密钥 (需要配合CMS里的密钥一样自行修改，目前只支持飞飞)
define("data_key", "*****************");

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
// 注意关闭调试模式了播放器地址就不能直接在浏览器地址栏输入打开了，只能在绑定的域名网页里打开
$debug = true;

// Apikey(没有的找客服获取QQ：2621633663)
define("apikey", "7ecdc64cde7a127f");

// 解析服务器（请勿修改）
define("api_host", "http://dns1.m3u8.cc");

// 解析官网地址（请勿修改）
define("gws_host", "http://www.playm3u8.com");

// 播放器页面标题
define("play_title", "PlayM3u8视频解析平台在线播放");
