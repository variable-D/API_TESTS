<?php
date_default_timezone_set("Asia/Seoul");

// 로그 파일 경로 설정 (절대 경로 사용)
$logFile = '/var/www/html8443/mallapi/extern/joytel/Joytel_api_logfile.txt';

// 로그 파일 디렉토리가 존재하는지 확인하고, 없으면 생성
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// 로그 작성 함수
function writeLog($message) {
    global $logFile;
    $logMessage = date("Y-m-d H:i:s") . " - " . $message . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// API 요청에 필요한 정보
$customerCode = "secret";
$customerAuth = "secret";
$orderTid = $customerCode . date("YmdHis") . rand(100000, 999999);  // 요구 사항에 맞는 orderTid 생성
$type = 3;
$warehouse = ""; // warehouse가 비어 있는 경우 빈 문자열로 설정
$receiveName = "변동환";
$phone = "01066663333";
$timestamp = round(microtime(true) * 1000);
$email = "prepia.hwan@gmail.com";
$remark = "test";
$itemList = [
    [
        "productCode" => "eSIM-test",
        "quantity" => 1
    ]
];

// autoGraph 생성
$originalString = $customerCode . $customerAuth . $warehouse . $type . $orderTid . $receiveName . $phone . $timestamp;
foreach ($itemList as $item) {
    $originalString .= $item['productCode'] . $item['quantity'];
}
$autoGraph = sha1($originalString);

// 로그 작성 (autoGraph 값)
writeLog("Original String: " . $originalString);
writeLog("Generated autoGraph: " . $autoGraph);

// 요청 데이터 생성
$postData = [
    "customerCode" => $customerCode,
    "orderTid" => $orderTid,  // orderTid 추가
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
$postDataJson = json_encode($postData, JSON_UNESCAPED_UNICODE);

// 로그 작성 (요청 데이터)
writeLog("Request Data: " . $postDataJson);

// cURL 초기화
$ch = curl_init();

// URL 설정
$url = "https://api.joytelshop.com/customerApi/customerOrder";

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

// cURL 오류 확인
if(curl_errno($ch)) {
    $curlError = 'Curl error: ' . curl_error($ch);
    writeLog($curlError);
    echo $curlError;
}

// cURL 종료
curl_close($ch);

// 응답 처리
$responseData = json_decode($response, true);

// 로그 작성 (응답 데이터)
writeLog("Response Data: " . json_encode($responseData));

// 응답 데이터를 출력하여 확인
print_r($responseData);

if (isset($responseData['code']) && $responseData['code'] == 0) {
    $successMessage = "Operation Success\nOrder TID: " . $responseData['data']['orderTid'] . "\nOrder Code: " . $responseData['data']['orderCode'];
    writeLog($successMessage);
    echo $successMessage;
} else {
    $errorMessage = "Error: " . (isset($responseData['message']) ? $responseData['message'] : 'Unknown error');
    writeLog($errorMessage);
    echo $errorMessage;
}

// 콜백 엔드포인트 설정 (예: /api/joytel/callback)
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/api/joytel/callback') !== false) {
    // 콜백 데이터를 저장할 로그 파일 경로
    $callbackLogFile = '/var/www/html8443/mallapi/Joytel_callback_logfile.txt';

    // 콜백 로그 작성 함수
    function writeCallbackLog($message)
    {
        global $callbackLogFile;
        $logMessage = date("Y-m-d H:i:s") . " - " . $message . PHP_EOL;
        file_put_contents($callbackLogFile, $logMessage, FILE_APPEND);
    }

    // JSON 형식의 요청 데이터를 받기
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // 로그 작성 (수신한 콜백 데이터)
    writeCallbackLog("Received Callback Data: " . $input);

    // 필수 필드 확인
    $requiredFields = ['orderCode', 'orderTid', 'phone', 'receiveName', 'email', 'status', 'itemList'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            $errorMessage = "Missing required field: $field";
            writeCallbackLog($errorMessage);
            http_response_code(400);
            echo json_encode(['error' => $errorMessage]);
            exit;
        }
    }

    // snList 필드 확인
    foreach ($data['itemList'] as $item) {
        if (!isset($item['snList'])) {
            $errorMessage = "Missing required field: snList in itemList";
            writeCallbackLog($errorMessage);
            http_response_code(400);
            echo json_encode(['error' => $errorMessage]);
            exit;
        }
        foreach ($item['snList'] as $sn) {
            $snRequiredFields = ['snCode', 'snPin', 'productExpireDate'];
            foreach ($snRequiredFields as $snField) {
                if (!isset($sn[$snField])) {
                    $errorMessage = "Missing required field: $snField in snList";
                    writeCallbackLog($errorMessage);
                    http_response_code(400);
                    echo json_encode(['error' => $errorMessage]);
                    exit;
                }
            }
        }
    }

    // 수신한 데이터를 처리 (여기서는 로그에 저장하는 예시만 포함)
    writeCallbackLog("Processed Callback Data: " . json_encode($data));

    // snPin을 사용하여 QR 코드를 요청하는 함수 호출
    foreach ($data['itemList'] as $item) {
        foreach ($item['snList'] as $sn) {
            requestQRCode($sn['snPin']);
        }
    }

    // 성공 응답 전송
    http_response_code(200);
    echo json_encode(['status' => 'success']);
}
?>
