<?php

/**********************************************
・API
ログビューワ用logテーブルデータ取得
**********************************************/

set_include_path( get_include_path() . PATH_SEPARATOR . '/app' );

include_once 'classes/LogViewer.class.php';
include_once 'classes/ParamCheckException.class.php';
include_once 'libs/log4php/Logger.php';
// ログ
Logger::configure( '/app/config/log4php.properties' );

// ロガー取得
$log = Logger::getLogger( getenv('LOGGER_TYPE') );

if( (string) $log->getLevel() === 'DEBUG' ){
  $paramLog = '';
  if( ! empty($_GET) ){
    $parGets = $_GET;
    foreach( $parGets as $parGKey => $parGVal ){
      $paramLog .= sprintf('%s=%s,', $parGKey, $parGVal);
    }
  }
  if( ! empty($_POST) ){
    $parPosts = $_POST;
    foreach( $parPosts as $parPKey => $parPVal ){
      $paramLog .= sprintf('%s=%s,', $parPKey, $parPVal);
    }
  }
  if( ! empty($paramLog) ) $log->debug("<USER PARAM> {$paramLog}");
}

/** APIレスポンス status に入れるコード : 成功 */
$API_CODE_SUCCESS = 200;
/** APIレスポンス status に入れるコード : パラメータエラー等 */
$API_CODE_ERR_EXTERNAL = 400;
/** APIレスポンス status に入れるコード : 内部エラー */
$API_CODE_ERR_INTERNAL = 500;

/**
 * レスポンスフォーマット
 *     個別のログデータ
 */
$data = [
    'log_id'          => '',
    'request_id'      => '',
    'application_id'  => '',
    'client_id'       => '',
    'user_id'         => '',
    'user_utterance'  => '',
    'bot_utterance'   => '',
    'operator_raw'    => '',
    'is_default'      => '',
    'datetime'        => '',
    'engine_name'     => '-',
];
/**
 * レスポンスフォーマット
 *     全データ
 */
$allData = [
    'status'  => $API_CODE_SUCCESS,
    'error'   => '',
    'logs'    => [
        'total'             => 0,
        'first_request_id'  => '',
        'last_request_id'   => '',
        'first_log_id'      => '',
        'last_log_id'       => '',
        'datas'             => [],  // 「$data」を複数入れる為の配列
    ],
];

/** データ取得総数のデフォルト値 */
$DATA_TOTAL_DEF = 100;

////////////////////////////
// パラメータ名一覧 : START
////////////////////////////
/** トークン */
$PN_TOKEN = 'token';
/** application_id */
$PN_APPLICATION_ID = 'appid';
/** log id */
$PN_LOG_ID = 'logid';
/** request_id */
$PN_REQUEST_ID = 'reqid';
/** client_id */
// $PN_CLIENT_ID = 'cliid';
/** user_id */
// $PN_USER_ID = 'usrid';
/** engine_type */
// $PN_ENGINE_TYPE = 'engtype';
/** engine_name */
// $PN_ENGINE_NAME = 'engname';
/** id_default */
// $PN_IS_DEFAULT = 'isdef';
/** 日時 */
$PN_DATETIME = 'datetime';
/** データ取得総数 */
$PN_DATA_TOTAL = 'total';
/** ページングタイプ request_idが [prev : 小さい方] [next : 大きい方] のデータを取得 */
$PN_PAGE_TYPE = 'pagetype';
////////////////////////////
// パラメータ名一覧 : END
////////////////////////////

/** 「$PN_PAGE_TYPE」のvalue値 : 次のデータ */
$VAL_PAGE_TYPE_NEXT = 'next';
/** 「$PN_PAGE_TYPE」のvalue値 : 前のデータ */
$VAL_PAGE_TYPE_PREV = 'prev';

/** （パラメータ文字列）日時のデリミタ */
$DATETIME_DELIM = ' ';
/** 日付のデリミタ */
$DATE_DELIM = '/';
/** 時刻のデリミタ */
$TIME_DELIM = ':';

// 「$PN_APPLICATION_ID」パラメータ値の取得
$token = ( ! empty($_REQUEST[$PN_TOKEN]) )
              ? $_REQUEST[$PN_TOKEN] : null;
// 「$PN_APPLICATION_ID」パラメータ値の取得
$appId = ( ! empty($_REQUEST[$PN_APPLICATION_ID]) )
              ? $_REQUEST[$PN_APPLICATION_ID] : null;
// 「$PN_LOG_ID」パラメータ値の取得
$logId = ( ! empty($_REQUEST[$PN_LOG_ID]) )
              ? (int) $_REQUEST[$PN_LOG_ID] : 0;
// 「$PN_REQUEST_ID」パラメータ値の取得
$reqId = ( ! empty($_REQUEST[$PN_REQUEST_ID]) )
              ? $_REQUEST[$PN_REQUEST_ID] : null;
// 「$PN_DATA_TOTAL」パラメータ値の取得
$dataTotal = ( ! empty($_REQUEST[$PN_DATA_TOTAL]) )
              ? (int) $_REQUEST[$PN_DATA_TOTAL] : $DATA_TOTAL_DEF;
// 「$PN_DATETIME」パラメータ値の取得
$dateTime = ( ! empty($_REQUEST[$PN_DATETIME]) )
              ? $_REQUEST[$PN_DATETIME] : null;
// 「$PN_PAGE_TYPE」パラメータ値の取得
$pageType = ( ! empty($_REQUEST[$PN_PAGE_TYPE]) )
              ? $_REQUEST[$PN_PAGE_TYPE] : $VAL_PAGE_TYPE_NEXT;

// 日時フォーマット文字列
$dateTimeFormat = sprintf(
  'Y%sm%sd%sH%si%ss',
  $DATE_DELIM, $DATE_DELIM, $DATETIME_DELIM, $TIME_DELIM, $TIME_DELIM
);

$lv = new LogViewer();

// DB関連情報
$dbh  = null;
$stmt = null;
// DBから取得したデータ一覧
$dbFetchDatas = [];
try {
  // パラメータチェック用エラーメッセージ格納
  $errMsgParamCheck = null;

  // SQL : 日時検索用の日時文字列
  $qDateTime = null;
  // SQL : 日時検索用のフォーマット文字列
  $qFormDateTime = null;

  if ( empty($token) ){
    //// トークンチェック
    $errMsgParamCheck = "トークン, {$PN_TOKEN}={$token}";

  } elseif( ! $lv->isValidToken(getenv('API_TOKEN_VALIDATE'), $token) ){
    //// トークンの有効性チェック
    $errMsgParamCheck = "[トークンが不正です] {$lv->validateTokenError}, {$PN_TOKEN}={$token}";

  } elseif ( empty($appId) ) {
    //// application_idチェック
    $errMsgParamCheck = "アプリケーションID, {$PN_APPLICATION_ID}={$appId}";

  } elseif( (empty($logId) && ! empty($reqId)) || (! empty($logId) && empty($reqId)) ) {
    //// log id, request_idチェック
    $errMsgParamCheck = "ログIDかリクエストIDがありません : {$PN_LOG_ID}={$logId}, {$PN_REQUEST_ID}={$reqId}";

  } elseif( ! empty($dateTime) ){
    if( ! empty($dateTime) ){
      // 日付と時刻にデリミタで分割
      list($pDate, $pTime) = explode($DATETIME_DELIM, $dateTime);
      // 日付をデリミタで分割
      list($pYear, $pMonth, $pDay) = explode($DATE_DELIM, $pDate);
      // 時刻をデリミタで分割
      list($pHour, $pMin, $pSec) = explode($TIME_DELIM, $pTime);
    }

    // 日時フォーマットチェック用文字列
    $tmpDateTimeCheckStr = $pYear;
    $qDateTime = $pYear;
    $qFormDateTime = '%Y';
    // パラメータ : 月
    if( ! isset($pMonth) ){
      $tmpDateTimeCheckStr .= $DATE_DELIM . '01';
    } else {
      if( strlen($pMonth) === 1 ) $pMonth = '0' . $pMonth;
      $tmpDateTimeCheckStr .= $DATE_DELIM . $pMonth;
      $qDateTime .= $DATE_DELIM . $pMonth;
      $qFormDateTime .= $DATE_DELIM . '%m';
    }
    // パラメータ : 日
    if( ! isset($pDay) ){
      $tmpDateTimeCheckStr .= $DATE_DELIM . '01';
    } else {
      if( strlen($pDay) === 1 ) $pDay = '0' . $pDay;
      $tmpDateTimeCheckStr .= $DATE_DELIM . $pDay;
      $qDateTime .= $DATE_DELIM . $pDay;
      $qFormDateTime .= $DATE_DELIM . '%d';
    }
    // パラメータ : 時
    if( ! isset($pHour) || $pHour === '' ){
      $tmpDateTimeCheckStr .= $DATETIME_DELIM . '00';
    } else {
      if( strlen($pHour) === 1 ) $pHour = '0' . $pHour;
      $tmpDateTimeCheckStr .= $DATETIME_DELIM . $pHour;
      $qDateTime .= $DATETIME_DELIM . $pHour;
      $qFormDateTime .= $DATETIME_DELIM . '%H';
    }
    // パラメータ : 分
    if( ! isset($pMin) ){
      $tmpDateTimeCheckStr .= $TIME_DELIM . '00';
    } else {
      if( strlen($pMin) === 1 ) $pMin = '0' . $pMin;
      $tmpDateTimeCheckStr .= $TIME_DELIM . $pMin;
      $qDateTime .= $TIME_DELIM . $pMin;
      $qFormDateTime .= $TIME_DELIM . '%i';
    }
    // パラメータ : 秒
    if( ! isset($pSec) ){
      $tmpDateTimeCheckStr .= $TIME_DELIM . '00';
    } else {
      if( strlen($pSec) === 1 ) $pSec = '0' . $pSec;
      $tmpDateTimeCheckStr .= $TIME_DELIM . $pSec;
      $qDateTime .= $TIME_DELIM . $pSec;
      $qFormDateTime .= $TIME_DELIM . '%s';
    }

    //// 日時フォーマットチェック
    if( $tmpDateTimeCheckStr !== date($dateTimeFormat, strtotime($tmpDateTimeCheckStr)) ){
      $errMsgParamCheck = "日時, {$PN_DATETIME}={$dateTime}";
    }

  } elseif( $pageType !== $VAL_PAGE_TYPE_NEXT && $pageType !== $VAL_PAGE_TYPE_PREV ){
    //// ページング用文字列チェック
      $errMsgParamCheck = "ページング文字列, {$PN_PAGE_TYPE}={$pageType}";
  }

  //// パラメータチェック用エラーメッセージがあるか?
  if( ! empty($errMsgParamCheck) ){
    throw new ParamCheckException( $errMsgParamCheck );
  }

  // SQL : WHERE(AND)句リスト
  $queryWhereAndStrs = [];

  // $queryWhereAndStrs["`application_id` = ?"] = PDO::PARAM_STR;
  $queryWhereAndStrs[] = ['qr' => '`application_id` = ?', 'val' => $appId, 'bi' => PDO::PARAM_STR];
  // request_id がある場合
  if( ! empty($logId) ){
    $qPageTypeArrowLog = ($pageType === $VAL_PAGE_TYPE_NEXT) ? '<' : '>';
    // $queryWhereAndStrs["? {$qPageTypeArrowLog} `id`"] = PDO::PARAM_INT;
    $queryWhereAndStrs[] = ['qr' => "? {$qPageTypeArrowLog} `id`", 'val' => $logId, 'bi' => PDO::PARAM_INT];
  }
  if( ! empty($reqId) ){
    $qPageTypeArrowReq = ($pageType === $VAL_PAGE_TYPE_NEXT) ? '<=' : '>=';
    // $queryWhereAndStrs["? {$qPageTypeArrowReq} `request_id`"] = PDO::PARAM_STR;
    $queryWhereAndStrs[] = ['qr' => "? {$qPageTypeArrowReq} `request_id`", 'val' => $reqId, 'bi' => PDO::PARAM_STR];
  }
  // // 日時指定 が無い場合
  // if( empty($qDateTime) ){
  //   // 現在日時
  //   $qDateTime = date( sprintf('Y%sm%sd', $DATE_DELIM, $DATE_DELIM) );
  //   $qFormDateTime = "%Y{$DATE_DELIM}%m{$DATE_DELIM}%d";
  // }
  // 日時指定 がある場合
  if( ! empty($qDateTime) ){
    $queryWhereAndStrs[] = ['qr' => "? <= DATE_FORMAT(`created_at`, '{$qFormDateTime}')", 'val' => $qDateTime, 'bi' => PDO::PARAM_STR];
  }

  // $qTable = "`logs`";
  // if( $pageType === $VAL_PAGE_TYPE_PREV && ! empty($logId) ){
  //   $qTable = "SELECT * FROM `logs` WHERE {$logId} > id ORDER BY id DESC LIMIT {$dataTotal};"
  // }

  // SQL : 全WHERE(AND)句
  $quertWhereAnd = implode( ' AND ', array_column($queryWhereAndStrs, 'qr') );

  $qOrderBy = 'ASC';
  if( $pageType === $VAL_PAGE_TYPE_PREV ){
    $qOrderBy = 'DESC';
  }

  //////////////////////////////
  // SQLクエリ文字列
  //////////////////////////////
  $query = 'SELECT * FROM `logs` WHERE ' . $quertWhereAnd . ' ORDER BY `request_id` ' . $qOrderBy . ', `id` ' . $qOrderBy . ' LIMIT ' . $dataTotal . ';' ;
echo "{$query}<br />\n";

  //////////////////////////////
  // DBアクセス
  //////////////////////////////
  // DBのインスタンス作成
  $dbh = new PDO(
    getenv('DATABASE_DSN'), getenv('DATABASE_USER'), getenv('DATABASE_PASSWD'),
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
  );
  // 静的プレースホルダを指定
  $dbh->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
  $stmt = $dbh->prepare( $query );
  $stmt->setFetchMode( PDO::FETCH_ASSOC );
  foreach( $queryWhereAndStrs as $qWheresIdx => $qWheres ){
    $stmt->bindParam( ($qWheresIdx + 1), $qWheres['val'], $qWheres['bi'] );
  }
  // SQL実行
  $stmt->execute();
  $dbFetchDatas = $stmt->fetchAll();

} catch( ParamCheckException $paEx ){
  $paExMsg = $paEx->getMessage();
  $paError = sprintf('[パラメータ値が不正です] %s', $paExMsg);

  $allData['error'] = $paError;
  $allData['status'] = $API_CODE_ERR_EXTERNAL;

  $log->warn( $paError );

} catch( PDOException $pdoEx) {
  $pdoExMsg = $pdoEx->getMessage();

  $allData['error'] = $pdoExMsg;
  $allData['status'] = $API_CODE_ERR_INTERNAL;

  $log->error( $pdoExMsg );

} catch( Exception $ex ){
  $exMsg = $ex->getMessage();
  $allData['error'] = $exMsg;
  $allData['status'] = $API_CODE_ERR_INTERNAL;

  $log->error( $exMsg );

} finally {
  // DB切断処理
  $stmt = null;
  $dbh  = null;
}

// 「$VAL_PAGE_TYPE_PREV」の場合は「DB : id」でソート
if( $pageType === $VAL_PAGE_TYPE_PREV && ! empty($dbFetchDatas) ){
  $sortFetchs = [];
  foreach ((array) $dbFetchDatas as $fKey => $fValue) {
      $sortFetchs[$fKey] = $fValue['id'];
  }
  array_multisort($sortFetchs, SORT_ASC, $dbFetchDatas);
}

$firstRequestId = '';
$lastRequestId  = '';
$firstLogId     = '';
$lastLogId      = '';
foreach( $dbFetchDatas as $dbFetchIdx => $dbFetch ){
  $fetchRequestId = $dbFetch['request_id'];
  $fetchLogId = $dbFetch['id'];
  if( $dbFetchIdx === 0 ){
    $firstRequestId = $fetchRequestId;
    $firstLogId = $fetchLogId;
  }
  $lastRequestId = $fetchRequestId;
  $lastLogId = $fetchLogId;

  $fetchs = $data;
  $fetchs['log_id']         = $fetchLogId;
  $fetchs['request_id']     = $fetchRequestId;
  $fetchs['application_id'] = $dbFetch['application_id'];
  $fetchs['client_id']      = $dbFetch['client_id'];
  $fetchs['user_id']        = $dbFetch['user_id'];
  $fetchs['user_utterance'] = $dbFetch['user_utterance'];
  $fetchs['bot_utterance']  = $dbFetch['bot_utterance'];
  $fetchs['operator_raw']   = $dbFetch['operator_raw'];
  $fetchs['is_default']     = $dbFetch['is_default'];
  $fetchs['datetime']       = $dbFetch['created_at'];

  $allData['logs']['datas'][] = $fetchs;
  unset( $fetchs );
}
$allData['logs']['total'] = count( $dbFetchDatas );
$allData['logs']['first_request_id'] = $firstRequestId;
$allData['logs']['last_request_id'] = $lastRequestId;
$allData['logs']['first_log_id'] = $firstLogId;
$allData['logs']['last_log_id'] = $lastLogId;

// http_response_code( $allData['status'] );
header('content-type: application/json; charset=utf-8');
echo json_encode($allData);

Logger::shutdown( $log );
