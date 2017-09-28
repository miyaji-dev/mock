<?php

$DATA_TOTAL = 10;

$data = [
    'log_id' => 1,
    'request_id' => 1,
    'application_id' => 5,
    'client_id' => 3,
    'user_id' => 2,
    'user_utterance' => 'こんにちわ',
    'bot_utterance' => 'こんにちは！ランチは何を食べましたか？',
    'operator_raw' => 'オペレーターです',
    'is_default' => 1,
    'datetime' => '2017/09/22 05:28:05',
    'engine_name' => '-',
];

$allData = [
    'status' => 200,
    'error' => '',
    'logs' => [
        'total' => 0,
        'first_request_id' => 0,
        'last_request_id' => 0,
        'first_log_id' => 0,
        'last_log_id' => 0,
        'datas' => [],
    ],
];

$reqFirstId = 0;
$reqLastId = 0;
$logFirstId = 0;
$logLastId = 0;
$currentReqId = 121;
for( $dataI = 0; $dataI < $DATA_TOTAL; $dataI++ ){
    $currentLogId = $dataI + 1 + 80;

    if( $dataI !== 0 && $dataI % 2 === 0 ){
      $currentReqId++;
    }

    if( $dataI === 0 ){
        $reqFirstId = $currentReqId;
        $logFirstId = $currentLogId;
    }
    if( $dataI === $DATA_TOTAL - 1 ){
        $reqLastId = $currentReqId;
        $logLastId = $currentLogId;
    }

    $data['log_id'] = $currentLogId;
    $data['request_id'] = $currentReqId;

    $allData['logs']['datas'][$dataI] = $data;
}

$allData['logs']['total'] = count( $allData['logs']['datas'] );
$allData['logs']['first_request_id'] = $reqFirstId;
$allData['logs']['last_request_id'] = $reqLastId;
$allData['logs']['first_log_id'] = $logFirstId;
$allData['logs']['last_log_id'] = $logLastId;

//echo json_encode($allData, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . PHP_EOL;
$json = json_encode($allData);

echo $json;
