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
            <div class="col-xs-12 col-md-8">
                <div class="jumbotron">
                    <div class="page-header"><h1>Запуск скрипта</h1></div>
                    
                    <form action="" method='post'>
                        <button class="btn btn-success btn-lg" type="submit">Запустить</button>
                    </form>
                </div>
            </div>
            <div class="col-xs-12 col-md-4">
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
    </div>
</section>
<style type="text/css">
    section {
        padding:20px 0;
    }
</style>
<script>
$(document).on('click','.alert .close', function() {$(this).parent().fadeOut().remove()});
var count = 0;
var checkStatus = function() {
    $.ajax({
        url: '',
        type:'post',
        success: function(res) {
            console.log(res,res.id);
        }
    })
    $('.messages').append('<div class="alert alert-dismissible alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Сообщение '+count+'</strong></div>');
    count++;
    if(count>2) clearInterval(timer);

}
var timer = setInterval(checkStatus,2000);


</script>
</body>
</html>