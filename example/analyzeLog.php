<?php
    echo 'start-time:' . date('Y-m-d H:i:s') . "\n";
    $log_dir = "./log/";
    $regule = "/(\d{4}-\d{2}-\d{2}\s*\d{2}:\d{2}:\d{2})\s*\[INFO\]\s*Array\s*\(\s*\[token\]\s*=>\s*.*\s*\[userid\]\s*=>\s*(.*)\s*\[username\]\s*=>\s*.*\s*\)/";
    $files = scandir($log_dir);
    $log_arr = [];
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') {
            continue;
        }
        $log_path = $log_dir.$file;
        $log_content = file_get_contents($log_path);
        if (preg_match_all($regule, $log_content, $matches)) {
            $access_time_arr = $matches[1];
            $access_userid_arr = $matches[2];
            $access_array = [];
            foreach ($access_time_arr as $k => $v) {
                $temp_arr['time'] = $v;
                $temp_arr['userid'] = $access_userid_arr[$k];
                $access_array[] = $temp_arr;
            }
            unset($v);
            unset($temp_arr);
            $log_arr = array_merge($log_arr, $access_array);
        }
    }
    unset($file);
    
    // 生成sql
    $sql = "INSERT INTO user_access_log (user_id, access_time) VALUES ";
    $str = '';
    foreach ($log_arr as $v) {
        $time = $v['time'];
        $userid = $v['userid'];
        if (empty($time) || empty($userid)) {
            continue;
        }
        $str .= "($userid, '".$time."'),";
    }
    $str = rtrim($str, ',');
    $sql .= $str;
    var_dump($sql);
    unset($v);
    echo "\n end-time:" . date('Y-m-d H:i:s') . "\n";
?>
