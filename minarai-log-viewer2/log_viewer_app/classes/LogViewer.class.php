<?php

/**
 *
 */
class LogViewer {

  /** トークンチェック : 成功 */
  const VALIDATE_TOKEN_OK = 200;
  /** トークンチェックステータスコード */
  public $validateTokenStatus = null;
  /** トークンチェックエラーメッセージ */
  public $validateTokenError  = null;

  /**
   * トークンチェック
   */
  public function isValidToken( $uri, $token ){
    $url = sprintf('%s?token=%s', $uri, $token);

    $ch = curl_init();
    // オプション
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // APIアクセス実行
    $body = curl_exec($ch);
    // httpステータスコード取得
    $httpSts = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // 終了
    curl_close($ch);

    $this->validateTokenStatus = $httpSts;

    if( $httpSts === self::VALIDATE_TOKEN_OK ){
      return true;
    } else {
      $this->validateTokenError = $body;
      return false;
    }
  }
}
