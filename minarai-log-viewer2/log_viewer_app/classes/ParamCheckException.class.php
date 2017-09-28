<?php

/**
 * パラメータチェック用例外クラス
 *   インスタンス引数 string : 例外メッセージ
 */
class ParamCheckException extends Exception {
  public function __construct( $excMsg ){
    parent::__construct( $excMsg );
  }
}
