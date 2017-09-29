var myApp = angular.module('myApp', ['ngRoute']);

myApp.config(function($routeProvider) {
  $routeProvider

    .when('/', {
      templateUrl : 'pages/login.html',
      controller  : 'mainController'
    })

    .when('/login', {
      templateUrl : 'pages/login.html',
      controller  : 'mainController'
    })

    .when('/select', {
      templateUrl : 'pages/select.html',
      controller  : 'selectController'
    })

    .when('/logviewer', {
      templateUrl : 'pages/logviewer.html',
      controller  : 'logviewerController'
    })
});

myApp.controller('mainController', function($scope, $http, $location) {

  $scope.login = function(){
    $http({
      method: 'POST',
      url: 'http://stg-dialogue-hub.minarai.io:3003/operator/login',
      data: { organization_name: $scope.org,
              email: $scope.email,
              password: $scope.password
            }
    })
    .success(function(data, status, headers, config){
      window.sessionStorage.setItem('token', data.token);
      $location.path('/select');
    })
    .error(function(data, status, headers, config){
      alert("ログインに失敗しました。組織名、メールアドレス、パスワードをご確認ください。");
    });

  }
});

myApp.controller('selectController', function($scope, $http, $location) {

  $http({
    method: 'GET',
    url: 'http://stg-dialogue-hub.minarai.io:3003/operator/applications',
    params: { token: window.sessionStorage.getItem('token')}
  })
  .success(function(data, status, headers, config){
    $scope.apps = data;
    $scope.apps.appid = data[0].application_id;
  })
  .error(function(data, status, headers, config){
    alert("アプリケーション一覧の取得に失敗しました。ログインからやり直してください。")
    $location.path('/login');
  });

  $scope.select = function(){
    window.sessionStorage.setItem('appid', $scope.apps.appid);
    $location.path('/logviewer');
  }

});


myApp.controller('logviewerController', function($scope, $http, $location, $interval) {
  $scope.is_autoupdate = false;
  $scope.is_loading = true;

  var DISPLAY_LIMIT = 200;      //最大表示件数(これ以上はページング)
  var PAGEING_LIMIT = 100;      //ページング時の取得件数
  var UPDATE_TIME   = 7;        //アップデートの間隔(秒)

  //初期表示
  show_list();

  //自動更新
  var t = $interval(auto_update, UPDATE_TIME * 1000);

  //アプリケーション選択へ
  $scope.select_app = function(){
    $location.path('./select');
  };

  //検索ボタン押下
  $scope.search = function(){
    var param_data = {
      token: window.sessionStorage.getItem('token'),
      appid: window.sessionStorage.getItem('appid'),
      logid: '',
      reqid: '',
      total: PAGEING_LIMIT,
      pagetype: 'next',
      datetime: $scope.searchdate != null ? $scope.searchdate : ''
    }
    //表示中のログを初期化
    $scope.logs = null;
    get_page(param_data);
  };

  //次へボタン押下
  $scope.next = function(){
    var param_data = {
      token: window.sessionStorage.getItem('token'),
      appid: window.sessionStorage.getItem('appid'),
      logid: $scope.logs.length > 0 ? $scope.logs[$scope.logs.length - 1].log_id : '',
      reqid: $scope.logs.length > 0 ? $scope.logs[$scope.logs.length - 1].request_id : '',
      total: PAGEING_LIMIT,
      pagetype: 'next',
      datetime: $scope.searchdate != null ? $scope.searchdate : ''
   }
    get_page(param_data);
  };

  //前へボタン押下
  $scope.prev = function(){
    var param_data = {
      token: window.sessionStorage.getItem('token'),
      appid: window.sessionStorage.getItem('appid'),
      logid: $scope.logs.length > 0 ? $scope.logs[0].log_id : '',
      reqid: $scope.logs.length > 0 ? $scope.logs[0].request_id : '',
      total: PAGEING_LIMIT,
      pagetype: 'prev',
      datetime: $scope.searchdate != null ? $scope.searchdate : ''
    }
    get_page(param_data);
  };

  $scope.toggle_detail = function(index){
    if(!$scope.logs[index].is_log){
      return;
    }
    if(!$scope.logs[index].is_show_detail){
      show_detail(index);
      $scope.logs[index].is_show_detail = true;
    }else {
      hide_detail(index);
      $scope.logs[index].is_show_detail = false;
    }
  };

  //初期表示
  function show_list(){
    var param_data = {
       token: window.sessionStorage.getItem('token'),
       appid: window.sessionStorage.getItem('appid'),
       logid: '',
       reqid: '',
       total: DISPLAY_LIMIT,
       pagetype: 'next',
       datetime: ''
    };
    get_page(param_data);
  };

  function get_page(param_data){
    $scope.is_loading = true;

    $http({
      method: 'GET',
      url: '../api/v2/viewer/log',
      params: param_data
    })
    .success(function(data, status, headers, config){
      if(data.logs == null){
        return;
      }
      $scope.is_loading = false;

      for(var i=0; i<data.logs.datas.length; i++){
        data.logs.datas[i].is_show_detail = false;
        data.logs.datas[i].is_log = true;
      }

      if($scope.logs != null){

        //次ページの場合、取得結果を末尾に追加
        if(param_data.pagetype == 'next'){
          $scope.logs = $scope.logs.concat(data.logs.datas);

          //表示上限を超える場合、超過件数分のログを先頭から削除
          if($scope.logs.length > DISPLAY_LIMIT){
            $scope.logs.splice(0, $scope.logs.length - DISPLAY_LIMIT);

            //エンジン情報を表示していた場合の考慮
            while($scope.logs[0].log_id == '-'){
              $scope.logs.shift();
            }
          }
        //前ページの場合、取得結果を先頭に追加
        }else if(param_data.pagetype == 'prev'){
          $scope.logs = data.logs.datas.concat($scope.logs)

          //表示上限を超える場合、超過件数分のログを末尾から削除
          if($scope.logs.length > DISPLAY_LIMIT){
            for(var i=0; i < ($scope.logs.length - DISPLAY_LIMIT); i++ ){
              $scope.logs.pop();
            }
            //エンジンの情報を表示していた場合の考慮
            while($scope.logs[$scope.logs.length - 1].log_id == '-'){
              $scope.logs.pop();
            }
          }
        }
      //現時点で表示データが存在しない場合、結果をそのまま表示
      }else{
        $scope.logs = data.logs.datas;
      }
    })
    .error(function(data, status, headers, config){
      $scope.is_loading = false;
      alert("ログの取得に失敗しました。");
    })
  };

  function show_detail(index){
    $scope.is_loading = true;

    $http({
      method: 'GET',
      url: '../api/v2/viewer/engine/',
      params: { token: window.sessionStorage.getItem('token'),
                logid: $scope.logs[index].log_id
              }
    })
    .success(function(data, status, headers, config){
      $scope.is_loading = false;

      for (var i=0; i<data.engines.total; i++){
        var tmp = {
          log_id : '-',
          engine_name : data.engines.datas[i].engine_name,
          bot_utterance : data.engines.datas[i].raw_response
        }
        $scope.logs.splice(index + 1, 0, tmp);
      }

    })
    .error(function(data, status, headers, config){
      $scope.is_loading = false;
      alert("エンジン情報の取得に失敗しました。")
    });
  };

  function hide_detail(index){
    var i = index + 1;
    while($scope.logs[i].log_id == '-'){
      console.log($scope.logs[i]);
      $scope.logs.splice(i, 1);
    }
  };

  //自動差分更新
  function auto_update(){
    if($scope.is_autoupdate){
        $scope.next();
        var target = $(".bottom_row");
        $(window).scrollTop(target.offset().top);
    }
  };

  var is_search_result = false; //検索結果を表示しているか
  var is_pageup_event = true;   //スクロールイベント処理を重複させないための処理フラグ
  var is_pagedown_event = true; //スクロールイベント処理を重複させないための処理フラグ

  //無限スクロール
  //TODO ディレクティブで書き直す
  $(window).bind("scroll", function() {
    var bottom_height = $('.bottom_row').offset().top;
    var scroll_height = $(document).scrollTop() + (window.innerHeight);
    var top_height = $(window).height();
    var scroll_height2 = $(window).scrollTop() + $(window).height();

    //ページダウン
    if(scroll_height > bottom_height && !is_pagedown_event){
      console.log("page down");
      is_pagedown_event = true;
      //next_page();
      $scope.next();

    }else if (scroll_height < bottom_height){
      is_pagedown_event = false;
    }

    //ページアップ
    if (top_height == scroll_height2 && !is_pageup_event){
      console.log("page up");
      is_pageup_event = true;
      //prev_page();
      $scope.prev();

      //TODO 上スクロール時に前ページがあった場合は、スクロール位置を基準値（top-rowの位置）に戻す
      //$(window).scrollTop($(".top-row").offset().top);

    }else if (top_height < scroll_height2) {
      is_pageup_event = false;
    }
  });
});
