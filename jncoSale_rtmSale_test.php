<?php
date_default_timezone_set('Asia/Seoul');

function getAuthToken($clientId, $clientSecret) {
    $url = 'https://sbx-openapi.lguplus.co.kr/uplus/extuser/oauth2/token';
    $data = [
        'grant_type' => 'client_credentials',
        'scope' => 'BL',
        'client_id' => $clientId,
        'client_secret' => $clientSecret
    ];

    $headers = [
        'Content-Type: application/x-www-form-urlencoded',
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        logMessage('Error: ' . curl_error($ch));
        return null;
    }

    curl_close($ch);

    $responseData = json_decode($response, true);
    if (isset($responseData['access_token'])) {
        return $responseData['access_token'];
    } else {
        logMessage('Failed to get access token');
        return null;
    }
}

function sendSaleRequest($data, $authToken, $clientId, $clientSecret) {
    $baseUrl = 'https://sbx-openapi.lguplus.co.kr/uplus/extuser/pv/bl/cp/or/jncoSaleMgmt/v1';
    $endpoint = '/rtmSale';
    $url = $baseUrl . $endpoint;

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $authToken,
        'X-IBM-Client-Id: ' . $clientId,
        'X-IBM-Client-Secret: ' . $clientSecret
    ];

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        logMessage('Error: ' . curl_error($ch));
        return null;
    }

    curl_close($ch);

    $responseData = json_decode($response, true);
    if ($responseData !== null) {
        logMessage('Sale request sent successfully. Response: ' . print_r($responseData, true));
        return $responseData;
    } else {
        logMessage('Failed to send sale request');
        return null;
    }
}

function logMessage($message) {
    $logFile = 'jncoSaleMgmt_rtmSale.txt';
    $currentTime = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$currentTime] $message\n", FILE_APPEND);
}

$clientId = 'c196c8d3f2917825a850370866d1a40b';
$clientSecret = 'e2c9bfd2cf525de8f8cad04c47cb82a3';
$authToken = getAuthToken($clientId, $clientSecret);

if ($authToken) {
    echo "Auth Token: $authToken\n";
    logMessage("Auth Token: $authToken");

    $data = [
        "userId" => base64_encode("koreasim"),
        "psno" => base64_encode("1234567878"),
        "jncoCd" => "039",
        "custNm" => base64_encode("변동환"),
        "bday" => base64_encode("19960513"),
        "countryCd" => "KOR",
        "sex" => "F",
        "loclCtplc" => base64_encode("01066503018"),
        "cprtChnlNm" => null,
        "dsReSaleProdInfo" => [
            [
                "devPpCd" => "ESIM000003",
                "saleCnt" => 1,
            ]
        ]
    ];

    $response = sendSaleRequest($data, $authToken, $clientId, $clientSecret);
    if ($response) {
        print_r($response);
    } else {
        echo 'Failed to send sale request';
    }
} else {
    echo 'Failed to get auth token';
}
?>
