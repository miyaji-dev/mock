<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="https://fezvrasta.github.io/bootstrap-material-design/favicon.ico">

    <title>Minarai D-Hub Log Viewer</title>

    <!-- Bootstrap core CSS -->
    <link href="https://fezvrasta.github.io/bootstrap-material-design/dist/css/bootstrap-material-design.min.css" rel="stylesheet">
    <link href="https://mdbootstrap.com/live/_MDB/css/mdb.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.0/css/font-awesome.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="/css/dashboard.css" rel="stylesheet">
  </head>

  <body>
    <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
      <a class="navbar-brand" href="#">D-HUB LogViewer</a>
      <button class="navbar-toggler d-lg-none" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarsExampleDefault">
        <ul class="navbar-nav mr-auto">
          <li class="nav-item">
            <a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="./select.html">Settings</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">About</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Help</a>
          </li>
        </ul>
        <div class="pull-right switch">
          <label style="color:white;">Off
            <input type="checkbox" id="check-auto-update" checked="checked">
            <span class="lever"></span> On
          </label>
        </div>
      </div>
    </nav>

    <div class="container-fluid">
      <div class="row">
        <main class="col-sm-12 pt-3" role="main">
          <div class="row text-xs-left">
            <form class="col-md-8 form-inline mt-2 mt-md-0">
              <input class="form-control mr-sm-2" id="search-date" style="width:500px;" type="text" placeholder="Search" aria-label="Search">
              <button class="btn btn-outline-success my-2 my-sm-0" id="btn-search" type="button">Search</button>
            </form>
            <div class="col-md-4">
              <div class="pull-right">
                <a class="btn-floating btn-small" id="btn-prev"><i class="fa fa-chevron-left"></i></a>
                <a class="btn-floating btn-small" id="btn-next"><i class="fa fa-chevron-right"></i></a>
              </div>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-fit" id="data-table">
              <thead>
                <tr bgcolor="slategray">
                  <th>LogId</th>
                  <th>RequestID</th>
                  <th>Timestamp</th>
                  <th>UserUtterance</th>
                  <th>BotUtterance</th>
                  <th>EnginName</th>
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
          <input type="hidden" id="appid" value="<?php echo $_REQUEST['appid'] ?>" />
          <input type="hidden" id="token" value="<?php echo $_REQUEST['token'] ?>" />
        </main>
      </div>
    </div>
  </body>
  <!-- Bootstrap core JavaScript
  ================================================== -->
  <!-- Placed at the end of the document so the pages load faster -->
  <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
  <script src="https://fezvrasta.github.io/bootstrap-material-design/assets/js/vendor/popper.min.js"></script>
  <script src="https://fezvrasta.github.io/bootstrap-material-design/dist/js/bootstrap-material-design.min.js"></script>
  <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
  <script src="https://fezvrasta.github.io/bootstrap-material-design/assets/js/ie10-viewport-bug-workaround.js"></script>

  <!-- JavaScript
  ================================================== -->
  <script type="text/javascript">

    var DISPLAY_LIMIT = 50;     //最大表示件数(これ以上はページング)
    var UPDATE_TIME   = 3;        //アップデートの間隔(秒)

    var is_autoupdate = true;
    var is_search_result = false; //検索結果を表示しているか
    var is_pageup_event = true;   //スクロールイベント処理を重複させないための処理フラグ
    var is_pagedown_event = true; //スクロールイベント処理を重複させないための処理フラグ

    var first_request_id = '';    //改ページ用
    var last_request_id = '';     //改ページ用
    var first_log_id = ''
    var last_log_id = '';

    var table = document.getElementById("data-table");

    $(function(){
      //初期表示
      show_datalist();

      //ページ読み込み後からページングを有効にする考慮
      is_pageup_event = false;
      is_pagedown_event = false;

      //自動更新処理
      setInterval("auto_update()", 1000 * UPDATE_TIME);
    });

    //スクロールイベントの検知
    $(window).bind("scroll", function() {
      var bottom_height = $('.bottom-row').offset().top;
      var scroll_height = $(document).scrollTop() + (window.innerHeight);
      var top_height = $(window).height();
      var scroll_height2 = $(window).scrollTop() + $(window).height();

      //ページダウン
      if(scroll_height > bottom_height && !is_pagedown_event){
        console.log("page down");
        is_pagedown_event = true;
        next_page();

      }else if (scroll_height < bottom_height){
        is_pagedown_event = false;
      }

      //ページアップ
      if (top_height == scroll_height2 && !is_pageup_event){
        console.log("page up");
        is_pageup_event = true;
        prev_page();

      }else if (top_height < scroll_height2) {
        is_pageup_event = false;
      }
    });

    //次ページボタン押下
    $("#btn-prev").click(function(){
      prev_page();
    });

    //前ページボタン押下
    $("#btn-next").click(function(){
      next_page();
    });

    //検索ボタン押下
    $("#btn-search").click(function(){
      var date = $("#search-date").val();

      is_autoupdate = false;
      $("#check-auto-update").prop('checked', is_autoupdate);

    });

    $("#check-auto-update").change(function(){
      is_autoupdate = $(this).prop('checked');

    });

    //詳細の表示
    $(document).on("click", "tr.btn-show-detail", function(){
      var tr = $(this)[0];
      var row_index = tr.rowIndex;

      var tbl = $("#data-table")[0];
      var request_id = tbl.rows[row_index].cells[0].innerHTML;
      //console.log("%s行", row_index);
      //console.log(request_id);
      if(row_index > 0){
        if($(this).hasClass("is_showed")){
          delete_detail(request_id);
          $(this).removeClass("is_showed");
        }else{
          show_detail(row_index + 1, request_id);
          $(this).addClass("is_showed");
        }
      }
    });

    function prev_page(){
      var result = get_prev();
      if(result["status"] != "200"){
        alert(result["error"]);
        return;
      };
      //１行目に追加
      show_prev_list(result);
    };

    function next_page(){
      var result = get_next();
      if(result["status"] != "200"){
        alert(result["error"]);
        return;
      };
      //末尾の行に追加
      show_next_list(result);
    };

    function show_detail(row_index, request_id){
      var result = get_detail();
      if(result["status"] != "200"){
        alert(result["error"]);
        return;
      }
      var logs = result["engines"];
      var datas = logs["datas"];

      for(var i=0; i<logs["total"]; i++){

        var tr = table.insertRow(row_index);
        tr.classList.add('detail-' + request_id);
        var td1 = tr.insertCell(0),
            td2 = tr.insertCell(1),
            td3 = tr.insertCell(2),
            td4 = tr.insertCell(3),
            td5 = tr.insertCell(4);
            td6 = tr.insertCell(5);
        td1.innerHTML = "-";
        td2.innerHTML = "-";
        td5.innerHTML = datas[i].raw_response;
        td6.innerHTML = datas[i].engine_name;
      }
    };

    function show_datalist(){
      var result = get_datalist();
      if(result["status"] != "200"){
        alert(result["error"]);
        return;
      }
      show_next_list(result);
    }

    function show_next_list(result){
      var logs = result["logs"];
      var datas = logs["datas"];

      if(logs["last_request_id"] != ''){
        last_request_id = logs["last_request_id"];
        last_log_id = logs["last_log_id"];
      }
      //初期表示時の考慮
      if(first_request_id == '' && logs["first_request_id"] != ''){
        first_request_id = logs["first_request_id"];
        first_log_id = logs["first_log_id"];
      }

      for(var i=0; i<logs["total"]; i++){

        var tr = table.insertRow(-1);

        if(datas[i].request_id == logs["last_request_id"]){
          //下限行を更新するために消すのを忘れずに
          $(".bottom-row").removeClass('bottom-row');
          tr.classList.add("bottom-row");
        }
        edit_cell(tr, datas[i]);
      }
      excess_log_delete("next");
    }

    function show_prev_list(result){
      var logs = result["logs"];
      var datas = logs["datas"];

      if(logs["first_request_id"] != ''){
        first_request_id = logs['first_request_id'];
        first_log_id = logs['first_log_id'];
      }

      for(var i=0; i<logs["total"]; i++){
        //console.log(datas[i]);

        var tr = table.insertRow(i+1);
        edit_cell(tr, datas[i]);
      }
      excess_log_delete("prev");
    }

    function edit_cell(tr, data){
      tr.classList.add("btn-show-detail");
      var td1 = tr.insertCell(0),
          td2 = tr.insertCell(1),
          td3 = tr.insertCell(2),
          td4 = tr.insertCell(3),
          td5 = tr.insertCell(4);
          td6 = tr.insertCell(5);
      td1.innerHTML = data.log_id;
      td2.innerHTML = data.request_id;
      td3.innerHTML = data.datetime;
      td4.innerHTML = data.user_utterance
      td5.innerHTML = data.bot_utterance
      td6.innerHTML = "-";
    }

    function delete_detail(request_id){
      $(".detail-" + request_id).remove();
    }

    //自動差分更新
    function auto_update(){
      if(is_autoupdate){
        var len = $("#data-table tr").length - 1;

          console.log("auto_updated! len=" + len);
          next_page();
      }
    };

    //初期表示
    function get_datalist(){
      /*
      // 各フィールドから値を取得してJSONデータを作成
      var data = {
        reqid: '',
        total: 100,
        pagetype: 'next',
        datetime: '',
        appid: $('#appid').val(),
      };

      $.ajax({
        type : "post",                    // method = "POST"
        url : "/log.php",                 // POST送信先のURL
        data : JSON.stringify(data),      // JSONデータ本体
        contentType : 'application/json', // リクエストの Content-Type
        dataType : "json",                // レスポンスをJSONとしてパースする
        success : function(json_data) {   // 200 OK時
          // JSON Arrayの先頭が成功フラグ、失敗の場合2番目がエラーメッセージ
          return json_data;
        },
        error: function() {         // HTTPエラー時
            alert("Server Error. Pleasy try again later.");
        },
        complete: function() {      // 成功・失敗に関わらず通信が終了した際の処理
        }
      });
      */

      //json文字列
      var json_data ='{"status":200,"error":"","logs":{"total":10,"first_request_id":121,"last_request_id":130,"datas":[{"log_id":81,"request_id":121,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":82,"request_id":122,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":83,"request_id":123,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":84,"request_id":124,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":85,"request_id":125,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":86,"request_id":126,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":87,"request_id":127,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":88,"request_id":128,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":89,"request_id":129,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":90,"request_id":130,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"}]}}';

      //JSONをパース
      var data = JSON.parse(json_data);
      return data;
    };

    //詳細取得
    function get_detail(){

      /*
      // 各フィールドから値を取得してJSONデータを作成
      var data = {
        reqid: '',
      };

      $.ajax({
        type : "post",                    // method = "POST"
        url : "/engine.php",                 // POST送信先のURL
        data : JSON.stringify(data),      // JSONデータ本体
        contentType : 'application/json', // リクエストの Content-Type
        dataType : "json",                // レスポンスをJSONとしてパースする
        success : function(json_data) {   // 200 OK時
          // JSON Arrayの先頭が成功フラグ、失敗の場合2番目がエラーメッセージ
          return json_data;
        },
        error: function() {         // HTTPエラー時
            alert("Server Error. Pleasy try again later.");
        },
        complete: function() {      // 成功・失敗に関わらず通信が終了した際の処理
        }
      });
      */

      var json_data = '{"status":200,"error":"","engines":{"total":3,"datas":[{"log_id":21,"engine_type":"hrime1","engine_name":"hrime_name1","raw_response":"111\u3042\u3044\u3046\u3048\u304a\u30a2\u30a4\u30a6\u30a8\u30aa","is_default":1},{"log_id":21,"engine_type":"hrime2","engine_name":"hrime_name2","raw_response":"222\u3042\u3044\u3046\u3048\u304a\u30a2\u30a4\u30a6\u30a8\u30aa","is_default":1},{"log_id":21,"engine_type":"hrime3","engine_name":"hrime_name3","raw_response":"333\u3042\u3044\u3046\u3048\u304a\u30a2\u30a4\u30a6\u30a8\u30aa","is_default":1}]}}'

      //JSONをパース
      var data = JSON.parse(json_data);
      return data;
    };

    //前ページ取得
    function get_prev(){
      /*
      // 各フィールドから値を取得してJSONデータを作成
      var data = {
        reqid: first_request_id,
        total: 100,
        pagetype: 'prev',
        datetime: '',
        appid: $('#appid').val(),
      };

      $.ajax({
        type : "post",                    // method = "POST"
        url : "/log.php",                 // POST送信先のURL
        data : JSON.stringify(data),      // JSONデータ本体
        contentType : 'application/json', // リクエストの Content-Type
        dataType : "json",                // レスポンスをJSONとしてパースする
        success : function(json_data) {   // 200 OK時
          // JSON Arrayの先頭が成功フラグ、失敗の場合2番目がエラーメッセージ
          return json_data;
        },
        error: function() {         // HTTPエラー時
            alert("Server Error. Pleasy try again later.");
        },
        complete: function() {      // 成功・失敗に関わらず通信が終了した際の処理
        }
      });
      */

      var json_data ='{"status":200,"error":"","logs":{"total":10,"first_request_id":121,"last_request_id":125,"first_log_id":81,"last_log_id":90,"datas":[{"log_id":81,"request_id":121,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":82,"request_id":121,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":83,"request_id":122,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":84,"request_id":122,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":85,"request_id":123,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":86,"request_id":123,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":87,"request_id":124,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":88,"request_id":124,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":89,"request_id":125,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":90,"request_id":125,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"}]}}';

      //JSONをパース
      var data = JSON.parse(json_data);
      //console.log(data);
      return data;

    };
    //次ページ取得
    function get_next(){
      /*
      // 各フィールドから値を取得してJSONデータを作成
      var data = {
        reqid: last_request_id,
        total: 100,
        pagetype: 'next',
        datetime: '',
        appid: $('#appid').val(),
      };

      $.ajax({
        type : "post",                    // method = "POST"
        url : "/log.php",                 // POST送信先のURL
        data : JSON.stringify(data),      // JSONデータ本体
        contentType : 'application/json', // リクエストの Content-Type
        dataType : "json",                // レスポンスをJSONとしてパースする
        success : function(json_data) {   // 200 OK時
          // JSON Arrayの先頭が成功フラグ、失敗の場合2番目がエラーメッセージ
          return json_data;
        },
        error: function() {         // HTTPエラー時
            alert("Server Error. Pleasy try again later.");
        },
        complete: function() {      // 成功・失敗に関わらず通信が終了した際の処理
        }
      });
      */
      //json文字列
      var json_data ='{"status":200,"error":"","logs":{"total":10,"first_request_id":121,"last_request_id":125,"first_log_id":81,"last_log_id":90,"datas":[{"log_id":81,"request_id":121,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":82,"request_id":121,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":83,"request_id":122,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":84,"request_id":122,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":85,"request_id":123,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":86,"request_id":123,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":87,"request_id":124,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":88,"request_id":124,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":89,"request_id":125,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"},{"log_id":90,"request_id":125,"application_id":5,"client_id":3,"user_id":2,"user_utterance":"\u3053\u3093\u306b\u3061\u308f","bot_utterance":"\u3053\u3093\u306b\u3061\u306f\uff01\u30e9\u30f3\u30c1\u306f\u4f55\u3092\u98df\u3079\u307e\u3057\u305f\u304b\uff1f","operator_raw":"\u30aa\u30da\u30ec\u30fc\u30bf\u30fc\u3067\u3059","is_default":1,"datetime":"2017\/09\/22 05:28:05"}]}}';

      //JSONをパース
      var data = JSON.parse(json_data);
      //console.log(data);
      return data;
    };

    //ページング処理（行が表示リミットを超えた場合、超過した件数分の行を削除していく）
    function excess_log_delete(control){
      var len = $("#data-table tr").length - 1;
      if(len > DISPLAY_LIMIT){
        for (var i = 0; i < (len - DISPLAY_LIMIT); i++){
          if(control == 'next'){
            table.rows[1].remove();
          }else{
            table.rows[table.rows.length - 1].remove();
          }
        }
        //詳細明細だった場合の考慮
        if(control == 'next'){
          while(table.rows[1].cells[0].innerHTML == '-'){
            table.rows[1].remove();
          }
        }else{
          while(table.rows[table.rows.length - 1].cells[0].innerHTML == '-'){
            table.rows[table.rows.length - 1].remove();
          }
          table.rows[table.rows.length - 1].classList.add("bottom-row");
        }
        first_log_id = table.rows[1].cells[0].innerHTML;
        first_request_id = table.rows[1].cells[1].innerHTML;

        last_log_id = table.rows[table.rows.length - 1].cells[0].innerHTML;
        last_request_id = table.rows[table.rows.length - 1].cells[1].innerHTML;
      }
    }

  </script>
  <!-- Placed at the end of the document so the pages load faster -->
</html>
