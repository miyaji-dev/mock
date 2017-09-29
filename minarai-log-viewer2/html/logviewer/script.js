// create the module and name it myApp
var myApp = angular.module('myApp', ['ngRoute']);


// configure our routes
myApp.config(function($routeProvider) {
  $routeProvider

    // route for the home page
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

myApp.factory("SharedStateService", function() {
    return {
        token: '',
        appid: ''
    };
});

myApp.controller('mainController', function($scope, $http, $location, SharedStateService) {
  $scope.data = SharedStateService;

  $scope.login = function(){
    $http({
      method: 'POST',
      url: 'http://stg-dialogue-hub.minarai.io:3003/operator/login',
      data: { organization_name: $scope.org,
              email: $scope.email,
              password: $scope.password
            }
    })
    // 成功時の処理
    .success(function(data, status, headers, config){
      //SharedStateService.token = data.token;
      console.log(data);
      $scope.data.token = data.token;

      if( ('sessionStorage' in window) && (window.sessionStorage !== null) ) {
        window.sessionStorage.setItem('token', data.token);
      }

      $location.path('/select');
    })
    // 失敗時の処理（ページにエラーメッセージを反映）
    .error(function(data, status, headers, config){
      alert("ログインに失敗しました。組織名、メールアドレス及びパスワードをご確認ください。");
    });

  }
});

myApp.controller('selectController', function($scope, $http, $location, SharedStateService) {
  $scope.data = SharedStateService

  $http({
    method: 'GET',
    url: 'http://stg-dialogue-hub.minarai.io:3003/operator/applications',
    params: { token: window.sessionStorage.getItem('token')}
  })
  // 成功時の処理
  .success(function(data, status, headers, config){
    $scope.apps = data;
    $scope.apps.appid = data[0].application_id;
    window.sessionStorage.setItem('appid', data[0].application_id);
    console.log(data);

    //$location.path('/select');
  })
  // 失敗時の処理（ページにエラーメッセージを反映）
  .error(function(data, status, headers, config){
    alert("アプリケーション一覧の取得に失敗しました。ログインからやり直してください。")
    $location.path('/login');
  });

  $scope.select = function(){
    $location.path('/logviewer');
  }

});


myApp.controller('logviewerController', function($scope, $http, $location, $interval, SharedStateService) {
  $scope.data = SharedStateService;
  $scope.is_autoupdate = true;

  var DISPLAY_LIMIT = 250;      //最大表示件数(これ以上はページング)
  var PAGEING_LIMIT = 100;      //取得件数
  var UPDATE_TIME   = 3;        //アップデートの間隔(秒)

  show_list();

  var t = $interval(auto_update, UPDATE_TIME * 1000);

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
  }
  $scope.search = function(){
    console.log("search");
    var param_data = {
      token: window.sessionStorage.getItem('token'),
      appid: window.sessionStorage.getItem('appid'),
      logid: $scope.logs.length > 0 ? $scope.logs[$scope.logs.length - 1].log_id : '',
      reqid: $scope.logs.length > 0 ? $scope.logs[$scope.logs.length - 1].request_id : '',
      total: DISPLAY_LIMIT,
      pagetype: 'next',
      datetime: $scope.searchdate != null ? $scope.searchdate : ''
    }

   $scope.logs = null;
    get_page(param_data);
  }

  $scope.next = function(){
    console.log($scope.logs);
    var param_data = {
      token: window.sessionStorage.getItem('token'),
      appid: window.sessionStorage.getItem('appid'),
      logid: $scope.logs.length > 0 ? $scope.logs[$scope.logs.length - 1].log_id : '',
      reqid: $scope.logs.length > 0 ? $scope.logs[$scope.logs.length - 1].request_id : '',
      total: DISPLAY_LIMIT,
      pagetype: 'next',
      datetime: $scope.searchdate != null ? $scope.searchdate : ''
   }
    get_page(param_data);
  };

  $scope.prev = function(){
    var param_data = {
      token: window.sessionStorage.getItem('token'),
      appid: window.sessionStorage.getItem('appid'),
      logid: $scope.logs.length > 0 ? $scope.logs[0].log_id : '',
      reqid: $scope.logs.length > 0 ? $scope.logs[0].request_id : '',
      total: DISPLAY_LIMIT,
      pagetype: 'prev',
      datetime: $scope.searchdate != null ? $scope.searchdate : ''
    }
    get_page(param_data);
  };

  function get_page(param_data){
    $http({
      method: 'GET',
      url: '../api/v2/viewer/log',
      params: param_data
    })
    // 成功時の処理
    .success(function(data, status, headers, config){
      if(data.logs == null){
        return;
      }
      for(var i=0; i<data.logs.datas.length; i++){
        data.logs.datas[i].is_show_detail = false;
        data.logs.datas[i].is_log = true;
      }
      if($scope.logs != null){

        if(param_data.pagetype == 'next'){
          $scope.logs = $scope.logs.concat(data.logs.datas);

          if($scope.logs.length > DISPLAY_LIMIT){
            $scope.logs.splice(0, $scope.logs.length - DISPLAY_LIMIT);

            while($scope.logs[0].log_id == '-'){
              $scope.logs.shift();
            }
          }

        }else if(param_data.pagetype == 'prev'){
          $scope.logs = data.logs.datas.concat($scope.logs)

          if($scope.logs.length > DISPLAY_LIMIT){
            var count = $scope.logs.length - DISPLAY_LIMIT;
            console.log(count);
            for(var i=0; i < count; i++ ){
              $scope.logs.pop();
            }
            while($scope.logs[$scope.logs.length - 1].log_id == '-'){
              $scope.logs.pop();
            }
          }
        }
      }else{
        $scope.logs = data.logs.datas;
      }
      console.log(data.logs.datas);
      console.log($scope.logs.length);

    })
    // 失敗時の処理（ページにエラーメッセージを反映）
    .error(function(data, status, headers, config){
      alert("ログの取得に失敗しました。")
    });
  }

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

  function show_detail(index){
    $http({
      method: 'GET',
      url: '../api/v2/viewer/engine/',
      params: { token: window.sessionStorage.getItem('token'),
                logid: $scope.logs[index].log_id
              }
    })
    // 成功時の処理
    .success(function(data, status, headers, config){
      //$scope.logs = data.logs.datas;
      console.log(data.engines.datas);

      for (var i=0; i<data.engines.total; i++){
        var tmp = {log_id : '-',
                   engine_name : data.engines.datas[i].engine_name,
                   bot_utterance : data.engines.datas[i].raw_response
                  }
        $scope.logs.splice(index + 1, 0, tmp);
      }
      //$scope.logs[index].engines = data.engines.datas

    })
    // 失敗時の処理（ページにエラーメッセージを反映）
    .error(function(data, status, headers, config){
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
    }
  };


  var is_search_result = false; //検索結果を表示しているか
  var is_pageup_event = true;   //スクロールイベント処理を重複させないための処理フラグ
  var is_pagedown_event = true; //スクロールイベント処理を重複させないための処理フラグ

  //無限スクロールの実装
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
