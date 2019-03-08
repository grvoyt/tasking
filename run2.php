<?php
//для аякса
if( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH'] && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ) {
    require_once('status/get.php');
    $res = new getInfo();
    die($res->getTasks()->toJson());
}

//сам запуск скрипта
$message = '';
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    exec_bg_script('status/script.php', ['time'=>2,'token'=>md5('istylespb.ru')]);
    $message = 'Скрипт запущен';
}
//

function exec_bg_script($script, array $args = [], $escape = true)
{
    $script = str_replace('..', '', $script);
    $script = dirname(__FILE__).DIRECTORY_SEPARATOR.$script;
    
    if (($file = realpath($script)) === false) {
        print_r('[exec_bg_script] File ' . $script . ' not found!');
        return false;
    }
    array_walk($args, function(&$value, $key) use($escape) {
        $value = $escape ? $key . '=' . escapeshellarg($value) : $key . '=' . $value;
    });

    $command = sprintf('php %s %s', $file, implode(' ', $args)) . " > /dev/null &";
    exec($command);
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Document</title>

    <link rel="stylesheet" type="text/css" href="//bootswatch.com/4/united/bootstrap.min.css">
    <script
  src="//code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>
</head>
<body>
<section>
    <div class="container">
        <div class="row">
            <div class="col-12 col-md-8">
                <div class="jumbotron">
                    <div class="page-header"><h1>Запуск скрипта</h1></div>
                    
                    <form action="" method='post'>
                        <button class="btn btn-success btn-lg" type="submit">Запустить</button>
                    </form>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="messages">
                    <?php if ($message) { ?>
                        <div class="alert alert-dismissible alert-success">
                          <button type="button" class="close" data-dismiss="alert">&times;</button>
                          <strong><?= $message; ?></strong>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <table class="table table-hover">
  <thead>
    <tr>
      <th scope="col">Task ID</th>
      <th scope="col">Status</th>
      <th scope="col">Error</th>
      <th scope="col">Datetime</th>
    </tr>
  </thead>
  <tbody id="tbody">

    <tr>
      <th scope="row">Default</th>
      <td>Column content</td>
      <td>Column content</td>
      <td>Column content</td>
    </tr>
    <tr class="table-danger">
      <th scope="row">Danger</th>
      <td>Column content</td>
      <td>Column content</td>
      <td>Column content</td>
    </tr>
   
  </tbody>
</table> 
            </div>
        </div>
    </div>
</section>
<style type="text/css">
    section {
        padding:20px 0;
    }
</style>
<script>
$(document).on('click','.alert .close', function() {$(this).parent().fadeOut().remove()});

var checkStatus = function() {
    $.ajax({
        url: '',
        type:'post',
        success: function(res) {
            if(res.tasks && res.tasks.length ==0) {
                clearInterval(timer);
                return false;
            }
            var status = res.status;
            var html = '';
            res.tasks.forEach(function(it) {
                var classError = (it.status == 2) ? 'table-danger' : '';
                var textError = it.error ? it.error : '';
                html += '<tr class="'+classError+'">'
                  +'<th scope="row">'+it.id+'</th>'
                  +'<td>'+status[it.status]+'</td>'
                  +'<td>'+textError+'</td>'
                  +'<td>'+it.datetime+'</td>'
                +'</tr>';
            });
            $('#tbody').empty().append(html);
        }
    })
    $('.messages').show().append('<div class="alert alert-dismissible alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Обновлено</strong></div>');
    setTimeout(function() {
        $('.messages').fadeOut(500).empty();
    },10*1000);

}
var timer = setInterval(checkStatus,30*1000);


</script>
</body>
</html>