<?php

$DATA_TOTAL = 10;

$data_1 = [
    'log_id' => 21,
    'engine_type' => 'hrime1',
    'engine_name' => 'hrime_name1',
    'raw_response' => '111あいうえおアイウエオ',
    'is_default' => 1,
];
$data_2 = [
    'log_id' => 21,
    'engine_type' => 'hrime2',
    'engine_name' => 'hrime_name2',
    'raw_response' => '222あいうえおアイウエオ',
    'is_default' => 1,
];
$data_3 = [
    'log_id' => 21,
    'engine_type' => 'hrime3',
    'engine_name' => 'hrime_name3',
    'raw_response' => '333あいうえおアイウエオ',
    'is_default' => 1,
];

$allData = [
    'status' => 200,
    'error' => '',
    'engines' => [
        'total' => 0,
        'datas' => [$data_1, $data_2, $data_3],
    ],
];

$allData['engines']['total'] = count( $allData['engines']['datas'] );

//echo json_encode($allData, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . PHP_EOL;
$json = json_encode($allData);

echo $json;
