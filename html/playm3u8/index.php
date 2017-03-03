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

// 提示：此文件无需修改，以免导致出错。
error_reporting(0);
require_once 'config.php';
$Locat = false; $error = false; $ext = Null;
$hd    = isset($_GET['hd']) ? addslashes($_GET['hd']) : play_hd;
$h5    = isset($_GET['h5']) ? addslashes($_GET['h5']) : "";
$vid   = isset($_GET['vid']) ? addslashes($_GET['vid']) : "";
$url   = isset($_GET['url']) ? addslashes($_GET['url']) : "";
$vid_authcode = authcode($vid,'DECODE');
if(!empty($vid_authcode)){$vid = $vid_authcode;}
$url_authcode = authcode($url,'DECODE');
if(!empty($url_authcode)){$url = $url_authcode;}
$type  = isset($_GET['type']) ? addslashes($_GET['type']) : "";
$mode  = isset($_GET['mode']) ? addslashes($_GET['mode']) : "";
$index = isset($_GET['index']) ? addslashes($_GET['index']) : 1;
$def   = isset($_GET['def']) ? addslashes($_GET['def']) : 0;
$refee = isset($_SERVER["HTTP_REFERER"]) ? addslashes($_SERVER["HTTP_REFERER"]) : '';
$auth_domain[count($auth_domain)] = (empty($_SERVER['HTTP_HOST'])? "" : $_SERVER['HTTP_HOST']);
$USER_AGENT = isset($_SERVER['HTTP_USER_AGENT']) ? addslashes($_SERVER['HTTP_USER_AGENT']) : '';
define ("__HOSTURL__", (is_ssl()?'https://':'http://').$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
$ctype = ((strstr($USER_AGENT,'MicroMessenger'))? 10 : 0);
$refee = parse_url($refee);
if(!empty($refee['host'])){
	if(!in_array($refee['host'], $auth_domain)){
		$error   = true;
		$Locat   = true;
		$message = '播放器被盗用';
	}
}else{
	if(!$debug){
		$error   = true;
		$Locat   = false;
		$message = '播放器禁止在浏览器直接打开';
	}
}

function playm3u8($host, $data, $index = 0, $def = 0, $h5 = 'iphone'){
	if($data['iframe'] != '1'){
		$e = isset($_GET['e']) ? addslashes($_GET['e']) : "";
		$u = empty($data['url'])? $data['vid'] : $data['url'];
		$d = mcrypt_decrypt(MCRYPT_RIJNDAEL_128,md5(md5($u)),base64_decode(str_replace(" ",'+',$e)), MCRYPT_MODE_CBC,'5922911245582354');
		if(md5($u) != $d) exit('no e!');
	}
	$json = json_decode(juhecurl($host.'/api?'.merge_string($data)),true);

	if($json['code'] == 405){
		// 切换为VIP视频解析
		$data['type'] = $json['source'].'_vip';
		$json = json_decode(juhecurl($host.'/api?'.merge_string($data)),true);
	}elseif($json['code'] == 406){
		// 切换为普通视频解析
		$data['type'] = strzuo($json['source'],'_vip');
		$json = json_decode(juhecurl($host.'/api?'.merge_string($data)),true);
	}elseif($json['code'] == 407){
		// 切换视频解析
		$data['url'] = "";
		$data['type'] = $json['result']['to'];
		$data['vid'] = $json['result']['vid'];
		$json = json_decode(juhecurl($host.'/api?'.merge_string($data)),true);
	}
	if($json){
		if($json['code'] != 200){
			exit($json['message']);
		}else{
			if($json['result']['filelist']){
				if($json['result']['play_type'] == 'm3u8'){
					// M3U8模式播放。
					if($index == 0) $index = 1;
					$play_url = $json['result']['filelist'][$index-1]['url'];
					if(empty($play_url)){
						exit('啊噢~没有找到播放文件');
					}
					if($h5 == 'android' || is_mobile() == true){
						$def = 1;
					}
					if($def != '1'){
						echo_m3u8_xml($play_url);
					}else{
						if($h5 == 'android'){
							if(!empty($json['result']['filelist'][$index-1]['html_play'])){
								header('Location: '.$json['result']['filelist'][$index-1]['html_play']); die; 
							}
						}
						header('Location: '.$play_url); die; 
					}
				}else{
					// 非M3U8类型播放，是分段模式。
					echo_pc_xml($json['result']['filelist'],$data['hd']);
				}
			}else{
				if(is_mobile()){
					if($h5 == 'android'){
						if(!empty($json['result']['html_play'])){
							header('Location: '.$json['result']['html_play']); die; 
						}
					}
					header('Location: '.$json['result']['files']); die; 
				}
				if($data['iframe'] == '1'){
					return $json['result']['files'];
				}
				// M3U8模式播放。
				$play_url = $json['result']['files'];
				if($json['result']['play_type'] == 'm3u8'){
					if($def != '1'){
						echo_m3u8_xml($play_url);
					}else{
						header('Location: '.$play_url); die; 
					}
				}else{
					// 非M3U8类型播放，是分段模式。
					$pc_data[0]['url'] = $play_url;
					echo_pc_xml($pc_data,$data['hd']);
				}
			}
		}
	}else{
		exit('啊噢~解析失败了,请刷新页面重试。');
	}
}
function is_mobile(){
    static $a;
    if (isset($a)) {
    } elseif (empty($_SERVER['HTTP_USER_AGENT'])) {
        $a = false;
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Silk/') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Kindle') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi') !== false) {
        $a = true;
    } else {
        $a = false;
    }
    return $a;
}

function authcode($string, $operation = 'DECODE', $key = '', $expiry = 3600) {
    $ystr = array(
            "-",
            "*",
        );
    $hstr = array(
            "+",
            "/",
        );
    $string = str_replace($ystr,$hstr,$string);
    $ckey_length = 4;
    $key = md5($key ? $key : data_key);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
    $cryptkey = $keya.md5($keya.$keyc);
    $key_length = strlen($cryptkey);
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);
    $rndkey = array();
    for($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    for($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    for($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if($operation == 'DECODE') {
        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        $keystr = $keyc.str_replace('=', '', base64_encode($result));
        $ystr = array(
                "+",
                "/",
            );
        $hstr = array(
                "-",
                "*",
            );
        $keystr = str_replace($ystr,$hstr,$keystr);
        return $keystr;
    }
}

function p($data){
    echo "<pre>";
    print_r($data); die;
}

function juhecurl($url,$params=false,$ispost=0){
    $httpInfo = array();
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
    curl_setopt( $ch, CURLOPT_USERAGENT , 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 30 );
    curl_setopt( $ch, CURLOPT_TIMEOUT , 30);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
	$headers = array(
		'X-Forwarded-For: '.$_SERVER['REMOTE_ADDR'], 
		'Client-IP: '.$_SERVER['REMOTE_ADDR'],
	);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if (substr($url, 0, 5) == 'https'); {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    if( $ispost ) {
        curl_setopt( $ch , CURLOPT_POST , true );
        curl_setopt( $ch , CURLOPT_POSTFIELDS , $params );
        curl_setopt( $ch , CURLOPT_URL , $url );
    } else{
        if($params){
            curl_setopt( $ch , CURLOPT_URL , $url.'?'.$params );
        }else{
            curl_setopt( $ch , CURLOPT_URL , $url);
        }
    }
    $response = curl_exec( $ch );
    if ($response === FALSE) {
        return false;
    }
    $httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
    $httpInfo = array_merge( $httpInfo , curl_getinfo( $ch ) );
    curl_close( $ch );
    return $response;
}

function is_ssl() {
    if(isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))){
        return true;
    }elseif(isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'] )) {
        return true;
    }elseif(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && ('https' == $_SERVER['HTTP_X_FORWARDED_PROTO'])){
    	return true;
    }
    return false;
}

function current_url() {
    static $url;
    if (empty($url)) {
		if(is_mobile()){
			// 移动端采用http,不然会导跨域无法播放。
			$url = 'http://';
		}else{
			$url = is_ssl() ? 'https://' : 'http://';
		}
        $url .= $_SERVER['HTTP_HOST'];
        $url .= isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/');
        $url = parse_url($url);
        $url['query'] = empty($url['query']) ? Array() : parse_string($url['query']);
        $url['query'] = merge_string($url['query']);
        $url = merge_url($url);
    }
    return $url;
}

function parse_string($s) {
    if (is_array($s)) {
        return $s;
    }
    parse_str($s, $r);
    return $r;
}

function merge_string($a) {
    if (!is_array($a) && !is_object($a)) {
        return (string) $a;
    }
    return http_build_query(to_array($a));
}

function merge_url($parse = array()) {
    $url = '';
    if (isset($parse['scheme'])) {
        $url .= $parse['scheme'] . '://';
    }
    if (isset($parse['user'])) {
        $url .= $parse['user'];
    }
    if (isset($parse['pass'])) {
        $url .= ':' . $parse['pass'];
    }
    if (isset($parse['user']) || isset($parse['pass'])) {
        $url .= '@';
    }
    if (isset($parse['host'])) {
        $url .= $parse['host'];
    }
    if (isset($parse['port'])) {
        $url .= ':'. $parse['port'];
    }
    if (isset($parse['path'])) {
        $url .= $parse['path'];
    } else {
        $url .= '/';
    }
    if (isset($parse['query']) && $parse['query'] !== '') {
        $url .= '?'. $parse['query'];
    }

    if (isset($parse['fragment'])) {
        $url .= '#'. $parse['fragment'];
    }
    return $url;
}

function to_array($a) {
    $a = (array) $a;
    foreach ($a as &$v) {
        if (is_array($v) || is_object($v)) {
            $v = to_array($v);
        }
    }
    return $a;
}

function host_path(){
    @list($hosturl, $end) = explode('?', $_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"]);
    return (is_ssl()?'https://':'http://').$hosturl;
}

function strzuo( $str , $zuo ){
    $wz = strpos( $str , $zuo);
    if(empty($wz)){
        return null;
    }
    if ( !$text = substr( $str , 0 , $wz )){
        return null;
    }else{
        return $text;
    }
}
if(!$error){
	if(!empty($type)) $data['type'] = $type;
	if(!empty($vid)){
		$data['vid'] = $vid;
		$data['type'] = $type;
	}elseif(!empty($url)){
		if(empty($data['type'])){
			if(strstr($url,'cctv.com')){
				$data['type'] = 'cntv';
			}elseif(strstr($url,'agnet:?xt=urn:')){
				$data['type'] = 'magnet';
			}elseif(strstr($url,'www.tudou.com')){
				$data['type'] = 'tudou';
			}elseif(strstr($url,'www.iqiyi.com')){
				$data['type'] = 'iqiyi';
			}elseif(strstr($url,'v.youku.com')){
				$data['type'] = 'youku';
			}elseif(strstr($url,'v.pptvyun.com')){
				$data['type'] = 'pptvyun';
			}elseif(strstr($url,'.m3u8')){
				$data['type'] = 'playm3u8';
			}elseif(strstr($url,'www.acfun.tv')){
				$data['type'] = 'acfun';
			}elseif(strstr($url,'qq.com')){
				$data['type'] = 'qq';
			}elseif(strstr($url,'bilibili.com')){
				$data['type'] = 'bilibili';
			}
		}
		$data['url'] = $url;
	}
	$aes_str = empty($data['url'])? $data['vid'] : $data['url'];
	if(!$error){
		$data['hd'] = $hd;
		$data['ct'] = $ctype;
		$data['apikey'] = apikey;
		if($data['type'] == 'y115'){
			$data['y115_cookie'] = base64_encode($y115_data['y115_cookie']);
		}
		if(empty($mode)){
			if($data['type'] == 'magnet' || $data['type'] == 'y115'){
				if(!is_ssl()){
					$error = true;
					$message = '啊噢~['.$data['type'].']云播请配合(https)ssl证书使用';
				}
			}		
		}
		if(!is_mobile()){
			if($data['type'] == 'pptvyun'){
				$data['iframe'] = '1';
				$play_url = playm3u8(api_host,$data);
			}else{
				if($mode == 'ckplay' || $def == '1'){
					$play_mode = json_decode(juhecurl(api_host.'/player_mode'),true);
					if(!in_array($data['type'],$play_mode)){
						$data['mode'] = 'iphone';
					}
					playm3u8(api_host,$data,$index,$def);
				}else{
					if($mode == 'ckplaym3u8') exit('no iphone!');
					$play_url = current_url().'&mode=ckplay';
				}
			}
		}else{
			if($mode == 'ckplaym3u8' || $h5 == 'android'){
				$data['mode'] = 'iphone';
				playm3u8(api_host,$data,$index,null,$h5);
			}else{
				$play_url = current_url(true).'&mode=ckplaym3u8';
			}
		}
	}
}?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo play_title?></title>
	<style type="text/css">body,html{background-color:black;padding: 0;margin: 0;width:100%;height:100%;}</style>
	<link rel="shortcut icon" href="extend/playm3u8.png">
	<script src="extend/layer/jquery.min.js"></script>
	<script src="extend/layer/layer.js"></script>
</head>
<body>
	<div id="a1" style="height:100%;position: relative;"></div>
	<script type="text/javascript" src="extend/js/crypto-js/rollups/aes.js" charset="utf-8"></script>
	<script type="text/javascript" src="extend/js/crypto-js/components/pad-zeropadding.js" charset="utf-8"></script>
	<script type="text/javascript" src="ckplayer/ckplayer.js" charset="utf-8"></script>
	<script type="text/javascript">
		layer.config({
		    extend: 'extend/layer.ext.js'
		});
		var type  = "<?php echo $data['type'] ?>";
		var error = "<?php echo $error ?>";
		var Locat = "<?php echo $Locat ?>";
		var refee = "<?php echo $refee['host'] ?>";
		
		var token    = token("<?php echo md5($aes_str)?>",'5922911245582354');
		var Android  = navigator.userAgent.match(/Android/i) != null;
		var isiPad   = navigator.userAgent.match(/iPad|iPhone|Linux|Android|iPod/i) != null;
		if(isiPad){
			var html_mp4 = '<?php echo $play_url?>'+'&h5=android&e='+token;
		}
		var play_url = "<?php echo $play_url?>"+'&e='+token;
		if (error) {
		    if (Locat) {
		        window.location.href = "<?php echo gws_host ?>";
		    } else {
		        layer.alert("<?php echo $message ?>");
		    }
		} else {
		    if (refee == "") layer.alert('警告：当前播放器为调试状态，部署到网站请关闭调试。', {
		       area: ['420px']
		    });
			if("<?php echo $data['iframe']?>" == '1'){
				var html = "<embed src='"+play_url+"' quality='high' width='100%' height='100%' align='middle' allowScriptAccess='always' allownetworking='all' allowfullscreen='true' type='application/x-shockwave-flash' wmode='window'></embed>";
				document.getElementById('a1').innerHTML = html;
			}else{
				if (isiPad) {
					if(type == 'magnet' || type == 'y115'){
						if (!Android) {
							var video=[play_url];
						}else{
							if(html_mp4 == ""){
								var video=[play_url];
							}else{
								var video=[html_mp4];
							}
						}
					}else{
						var video=[play_url];
					}
					var autoplay = '';	
					if('<?php echo Auto_play?>' == '1'){
						var autoplay = 'autoplay="autoplay"';			
					}
					var html5 = '<video src="' + video[0] + '" controls="controls" '+autoplay+' width="100%" height="99%"></video>';
					document.getElementById('a1').innerHTML = html5;
				} else {
					var flashvars = {
						f: play_url,
						s: 2,
						p: '<?php echo Auto_play?>',
						c: 0,
					};
					var params = {bgcolor: '#FFF',allowFullScreen: true,allowScriptAccess: 'always',wmode: 'transparent'};
					CKobject.embedSWF('ckplayer/ckplayer.swf', 'a1', 'ckplayer_a1', '100%', '100%', flashvars, params);
				}
			}
		}
	</script>
	<script type="text/javascript">var cnzz_protocol = (("https:" == document.location.protocol) ? " https://" : " http://");document.write(unescape("%3Cspan style='display:none;' id='cnzz_stat_icon_1260138220'%3E%3C/span%3E%3Cscript src='" + cnzz_protocol + "s11.cnzz.com/z_stat.php%3Fid%3D1260138220' type='text/javascript'%3E%3C/script%3E"));</script>
</body>
</html>

<?php
function echo_m3u8_xml($play_url){
	header( 'Content-Type: text/xml;charset=utf-8 ');
	$swf_path = strstr(host_path(),'index.php')? strzuo(host_path(),'index.php') : host_path();
	$m3u8_xml  = '<?xml version="1.0" encoding="UTF-8"?>';
	$m3u8_xml .= '<ckplayer>';
	$m3u8_xml .= '<flashvars>';
	$m3u8_xml .= '{f->'.$swf_path.'extend/swf/m3u8.swf}{s->4}{a-><![CDATA['.$play_url.']]>}{defa-><![CDATA['.current_url().'&def=1&hd=1]]>|<![CDATA['.current_url().'&def=1&hd=2]]>|<![CDATA['.current_url().'&def=1&hd=3]]>}';
	$m3u8_xml .= '</flashvars>';
	$m3u8_xml .= '<video><file><![CDATA[]]></file></video>';
	$m3u8_xml .= '</ckplayer>';
	exit($m3u8_xml);
}
function echo_pc_xml($data,$hd){
	header("Content-Type: text/xml");
	$xml .= '<?xml version="1.0" encoding="utf-8"?>'.chr(13);
	$xml .= '<ckplayer>'.chr(13);
	$xml .= '<flashvars>{f-><![CDATA['.str_replace(array('&hd=1','&hd=2','&hd=3'),'',current_url()).'&hd=[$pat]]]>}{h->3}{a->'.$hd.'}{defa->1|2|3}</flashvars>'.chr(13);
	foreach ($data as $v) {
		$xml .= '<video>'.chr(13);
		$xml .= '<file><![CDATA['.$v['url'].']]></file>'.chr(13);
		if(!empty($v['size']) && !empty($v['seconds'])){
			$xml .= '<size><![CDATA['.$v['size'].']]></size>'.chr(13);
			$xml .= '<seconds><![CDATA['.$v['seconds'].']]></seconds>'.chr(13);		
		}
		$xml .= '</video>'.chr(13);
	}
	$xml .= '</ckplayer>'.chr(13);
	exit($xml);
}
?>
