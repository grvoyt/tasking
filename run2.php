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
    $params = [
        'time'=>1,
        'token'=>md5('istylespb.ru'),
        'type' => 1,
    ];

    if(isset($_POST['check'])){
        $params['type'] = 2;
        $params['check'] = $_POST['check'];
    }
    exec_bg_script('status/script.php', $params);
    $message = 'Скрипт запущен';
    if(isset($_POST['check'])) {
        header('Content-Type: application/json');
        header('Status: 200');
        die(json_encode(['status' => 1]));
    } else {
        header('Location: https://istylespb.ru/run2.php');
    }
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

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
                        <th scope="col">id</th>
                        <th scope="col">Статус</th>
                        <th scope="col">Тип</th>
                        <th scope="col">Время начала</th>
                        <th scope="col">Время конца</th>
                        <th scope="col">Текст Ошибки</th>
                    </tr>
                </thead>
                <tbody id="tbody2">
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

$.ajax({
    url:'/status/get.php?history=1&time='+Date.now(),
    type:'get',
    success: render
})

function render(res) {
    if(res.tasks && res.tasks.length ==0) {
        clearInterval(timer);
        $('#tbody').empty();
        showMessage('Импорт завершен','alert-success');
        return false;
    }
    var status = res.status;
    var type = res.types;
    var html = '';
    var currTime = new Date().toLocaleString();
    res.tasks.forEach(function(it) {
        var classError = (it.status == 2) ? 'border-danger' : '';
        var errorLabel = (it.status == 2) ? 'danger' : 'success';
        var textError = it.error ? it.error : '';
        var date_start = new Date(it.date_start);
        date_start.setHours(date_start.getHours()+1);
        var date_end = !it.date_end ? '' : new Date(it.date_end);
        if(date_end!=='') date_end.setHours(date_end.getHours()+1);
        html += '<tr class="'+classError+'"><th scope="row">'+it.id+'</th>'
            +'<td>'+status[it.status]+'</td>'
            +'<td class="text-'+errorLabel+'">'+type[it.type]+'</td>'
            +'<td>'+date_start.toLocaleString()+'</td>'
            +'<td>'+date_end.toLocaleString()+'</td>'
            +'<td>'+textError+'</td>'
            +'</tr>';
    });
    var tabBody = res.history ? '#tbody2' : '#tbody';
    $(tabBody).empty().append(html);
    showMessage('Обновлено');
}

var checkStatus = function() {
    $.ajax({
        url:'/status/get.php?history=1&time='+Date.now(),
        type:'get',
        success: render
    })
}

checkStatus();
var timer = setInterval(checkStatus,15*1000);

function showMessage(text,alert='alert-info') {
    $('.messages').append('<div class="alert alert-dismissible '+alert+'"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>'+text+'</strong></div>').fadeIn(500);
    setTimeout(function() {
        $('.messages').fadeOut(500).empty();
    },10*1000);
}
</script>
</body>
</html>