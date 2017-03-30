<?php 
//阻塞代码
function multiple_threads_request($nodes){ 
        $mh = curl_multi_init(); 
        $curl_array = array(); 
        foreach($nodes as $i => $url) 
        { 
            $curl_array[$i] = curl_init($url); 
            curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, true); 
            curl_multi_add_handle($mh, $curl_array[$i]); 
        } 
        $running = NULL; 
        do { 
            usleep(10000); 
            curl_multi_exec($mh,$running); 
        } while($running > 0); 
         
        $res = array(); 
        foreach($nodes as $i => $url) 
        { 
            $res[$url] = curl_multi_getcontent($curl_array[$i]); 
        } 
         
        foreach($nodes as $i => $url){ 
            curl_multi_remove_handle($mh, $curl_array[$i]); 
        } 
        curl_multi_close($mh);        
        return $res; 
} 
print_r(muti_thread_request(array( 
    'http://www.example.com', 
    'http://www.example.net', 
)));
/*
 * @purpose: 使用curl并行处理url
 * @return: array 每个url获取的数据
 * @param: $urls array url列表
 * @param: $callback string 需要进行内容处理的回调函数。示例：func(array)
 */
function curl($urls = array(), $callback = '')
{
    $response = array();
    if (empty($urls)) {
        return $response;
    }
    $chs = curl_multi_init();
    $map = array();
    foreach($urls as $url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        curl_multi_add_handle($chs, $ch);
        $map[strval($ch)] = $url;
    }
    do{
        if (($status = curl_multi_exec($chs, $active)) != CURLM_CALL_MULTI_PERFORM) {
            if ($status != CURLM_OK) { break; } //如果没有准备就绪，就再次调用curl_multi_exec
            while ($done = curl_multi_info_read($chs)) {
                $info = curl_getinfo($done["handle"]);
                $error = curl_error($done["handle"]);
                $result = curl_multi_getcontent($done["handle"]);
                $url = $map[strval($done["handle"])];
                $rtn = compact('info', 'error', 'result', 'url');
                if (trim($callback)) {
                    $callback($rtn);
                }
                $response[$url] = $rtn;
                curl_multi_remove_handle($chs, $done['handle']);
                curl_close($done['handle']);
                //如果仍然有未处理完毕的句柄，那么就select
                if ($active > 0) {
                    curl_multi_select($chs, 0.5); //此处会导致阻塞大概0.5秒。
                }
            }
        }
    }
    while($active > 0); //还有句柄处理还在进行中
    curl_multi_close($chs);
    return $response;
}
 
//使用方法
function deal($data){
    if ($data["error"] == '') {
        echo $data["url"]." -- ".$data["info"]["http_code"]."\n";
    } else {
        echo $data["url"]." -- ".$data["error"]."\n";
    }
}
$urls = array();
for ($i = 0; $i < 10; $i++) {
    $urls[] = 'http://www.baidu.com/s?wd=etao_'.$i;
    $urls[] = 'http://www.so.com/s?q=etao_'.$i;
    $urls[] = 'http://www.soso.com/q?w=etao_'.$i;
}
curl($urls, "deal"); 
?>