<?php

// API 요청에 필요한 정보
$customerCode = "test001";
$customerAuth = "LGUw5Q1J";
$orderTid = $customerCode . date("YmdHis") . rand(100000, 999999);
$type = 3;
$warehouse = ""; // warehouse가 비어 있는 경우 빈 문자열로 설정
$receiveName = "test";
$phone = "15666666666";
$timestamp = round(microtime(true) * 1000);
$email = "test@qq.com";
$remark = "test";
$itemList = [
    [
        "productCode" => "esim615xxxx1",
        "quantity" => 1
    ],
    [
        "productCode" => "esim615xxxx2",
        "quantity" => 1
    ],
];

// autoGraph 생성
$originalString = $customerCode . $customerAuth . $warehouse . $type . $orderTid . $receiveName . $phone . $timestamp;
foreach ($itemList as $item) {
    $originalString .= $item['productCode'] . $item['quantity'];
}
$autoGraph = sha1($originalString);

// 요청 데이터 생성
$postData = [
    "customerCode" => $customerCode,
    "type" => $type,
    "receiveName" => $receiveName,
    "phone" => $phone,
    "timestamp" => $timestamp,
    "autoGraph" => $autoGraph,
    "remark" => $remark,
    "itemList" => $itemList,
    "email" => $email
];

// JSON 형식으로 인코딩
$postDataJson = json_encode($postData);

// cURL 초기화
$ch = curl_init();

// URL 설정 (중국 외부 서버의 경우)
$url = "https://api.joytelshop.com/customer/pi/customerOrder";

// cURL 옵션 설정
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataJson);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);

// API 요청 실행 및 응답 받기
$response = curl_exec($ch);

// cURL 종료
curl_close($ch);

// 응답 처리
$responseData = json_decode($response, true);

// 응답 데이터를 출력하여 확인
print_r($responseData);

if (isset($responseData['code']) && $responseData['code'] == 0) {
    echo "Operation Success\n";
    echo "Order TID: " . $responseData['data']['orderTid'] . "\n";
    echo "Order Code: " . $responseData['data']['orderCode'] . "\n";
} else {
    echo "Error: " . $responseData['message'] . "\n";
}
