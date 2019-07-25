<?php
// 发送钉钉群机器人通知
$url_array = [
    "test_robot" => "https://oapi.dingtalk.com/robot/send?access_token=6d926cdd173e02dfd84adac6cc90b92405f05b68c1f5fa3c8032e0727387f8a1",
    "git_robot" => "https://oapi.dingtalk.com/robot/send?access_token=23c83e958f8401747acc2af776bfede348a39e91a9809a6411582d6c1c019cd3",
];

if (count($argv) < 3) {
    echo "Lack of necessary parameters\n";
    exit(1);
}
$file_name = $argv[0];
$url_name = $argv[1];
$message = $argv[2];

if (!array_key_exists($url_name, $url_array)) {
    echo "invalid url_name\n";
    exit(1);
}
if (empty($message)) {
    echo "invalid message\n";
    exit(1);
}
$url = $url_array[$url_name];
$message = [
    "msgtype" => "text",
    "text" => [
        "content" => "{$message}",
    ],
];

$result = sendDingTalkRobotMessage($url, $message);
echo $result['status'];
echo $result['message'];
if ($result['status'] == 'error') {
    exit(1);
}
exit(0);
// 发送post请求
function sendDingTalkRobotMessage($url, $data)
{
    $curl = curl_init();
    $data = json_encode($data);
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "cache-control: no-cache"
        ),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
        return ["status" => "error", "message" => $err];
    } else {
        return ["status" => "success", "message" => $response];
    }
}
