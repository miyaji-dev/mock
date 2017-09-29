<?php

/**********************************************
・API
ログビューワ用engineテーブルデータ取得
**********************************************/

set_include_path( get_include_path() . PATH_SEPARATOR . '/app' );

include_once 'classes/LogViewer.class.php';
include_once 'classes/ParamCheckException.class.php';
include_once 'libs/vendor/autoload.php';
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
 *     個別のデータ
 */
 $data = [
     'log_id' => 0,
     'engine_type' => '',
     'engine_name' => '',
     'raw_response' => '',
     'is_default' => 0,
 ];
 /**
  * レスポンスフォーマット
  *     全データ
  */
$allData = [
  'status' => $API_CODE_SUCCESS,
  'error' => '',
  'engines' => [
    'total' => 0,
    'datas' => [],  // 「$data」を複数入れる為の配列
  ],
];

// /** データ取得総数のデフォルト値 */
// $DATA_TOTAL_DEF = 100;

////////////////////////////
// パラメータ名一覧 : START
////////////////////////////
/** トークン */
$PN_TOKEN = 'token';
/** log id */
$PN_LOG_ID = 'logid';
////////////////////////////
// パラメータ名一覧 : END
////////////////////////////

// 「$PN_APPLICATION_ID」パラメータ値の取得
$token = ( ! empty($_REQUEST[$PN_TOKEN]) )
              ? $_REQUEST[$PN_TOKEN] : null;
// 「$PN_LOG_ID」パラメータ値の取得
$logId = ( ! empty($_REQUEST[$PN_LOG_ID]) )
              ? (int) $_REQUEST[$PN_LOG_ID] : 0;

$lv = new LogViewer();

// DB関連情報
$dbh  = null;
$stmt = null;
// DBから取得したデータ一覧
$dbFetchDatas = [];
try {
  // パラメータチェック用エラーメッセージ格納
  $errMsgParamCheck = null;

  if( empty($logId) ) {
    //// log id, request_idチェック
    $errMsgParamCheck = "ログIDがありません";

  } elseif ( empty($token) ){
    //// トークンチェック
    $errMsgParamCheck = "トークンがありません";

  } elseif( ! $lv->isValidToken(getenv('API_TOKEN_VALIDATE'), $token) ){
    //// トークンの有効性チェック
    $errMsgParamCheck = "[トークンが不正です] {$lv->validateTokenError}, {$PN_TOKEN}={$token}";
  }

  //// パラメータチェック用エラーメッセージがあるか?
  if( ! empty($errMsgParamCheck) ){
    throw new ParamCheckException( $errMsgParamCheck );
  }

  //////////////////////////////
  // SQLクエリ文字列
  //////////////////////////////
  $query = 'SELECT * FROM `engines` WHERE `log_id` = ?;' ;

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
  $stmt->bindParam( 1, $logId, PDO::PARAM_INT );
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

foreach( $dbFetchDatas as $dbFetchIdx => $dbFetch ){
  $fetchs = $data;
  $fetchs['log_id']       = $dbFetch['log_id'];
  $fetchs['engine_type']  = $dbFetch['engine_type'];
  $fetchs['engine_name']  = $dbFetch['engine_name'];
  $fetchs['raw_response'] = $dbFetch['raw_response'];
  $fetchs['is_default']   = $dbFetch['is_default'];

  $allData['engines']['datas'][] = $fetchs;
  unset( $fetchs );
}
$allData['engines']['total'] = count( $dbFetchDatas );

// http_response_code( $allData['status'] );
header('content-type: application/json; charset=utf-8');
echo json_encode($allData);

Logger::shutdown( $log );
