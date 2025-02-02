<?php
// проверка на запуск из консоли
if(php_sapi_name() != 'cli') {
    header("HTTP/1.1 404 Not Found");
    exit();
}

// форматируем в массив аргументы
$params = [];
foreach(array_slice($argv,1) as $param) {
    $temp = explode('=',$param);
    $params[$temp[0]] = $temp[1];
}

//проверяем токен
if(md5('istylespb.ru') !== $params['token']) die('token_denied');

//таймаут запуска
if(isset($params['time'])) sleep( (int)$params['time'] );

//для sheets 
$_SERVER['DOCUMENT_ROOT'] = '/var/www/istylespb/data/www/istylespb.ru';

//пабота самого скрипта
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once('/var/www/istylespb/data/www/istylespb.ru/config.php');
require_once('/var/www/istylespb/data/www/istylespb.ru/system/startup.php');
require_once('/var/www/istylespb/data/www/istylespb.ru/system/sheet.php');

//проверка работающих тасков и ожидание
while(checkTaskActive()) {
    sleep(29);
}

//Пишем таск в БД
$taskId = startTask();

//этап 1
try {
    $sheet = new sheet();

    $status = $sheet->get("Первый лист");
    $sheet->insertFour($params['sheet']);

    for ($i = 1; $i < count($status['values']); $i++) {

        if (!empty($status['values'][$i][0])) {

            $data_status = array(
                "order_id" => $status['values'][$i][0],
                "comment" => $status['values'][$i][15],
                "status" => !empty($status['values'][$i][20]) ? $status['values'][$i][20] : '',
            );

            updateStatus($data_status);
            if (!empty($data_status['status'])) {
                $sheet->updateStatusTable($data_status);
            }
            updateComment($data_status);
        }
    }
} catch(\Exception $e) {
    errorTask($taskId,[$e->getLine(),$e->getFile(),$e->getMessage()],$e->getTrace());
    die;
}

sleep(120);

//этап 2
try {
    $prod = $sheet->get("Лист2");

    if(count($prod['values']) > 0) {
        for($i = 1;$i < count($prod['values']);$i++){
            if(!empty($prod['values'][$i][1])){
                $data = array(
                    "sku" => !empty($prod['values'][$i][1]) ? $prod['values'][$i][1] : '',
                    "price" => !empty($prod['values'][$i][2]) ? $prod['values'][$i][2] : '',
                    "price_two" => !empty($prod['values'][$i][3]) ? $prod['values'][$i][3] : '',
                    "quantity" => !empty($prod['values'][$i][4]) ? $prod['values'][$i][4] : 0,
                    "quantity_two" => !empty($prod['values'][$i][5]) ? $prod['values'][$i][5] : 0,
                    "status_two" => !empty($prod['values'][$i][6])? $prod['values'][$i][6]: '',
                );
                
                update($data);
            }
        }
    } else {
        errorTask($taskId,[105,'script.php','из Лист2 не получили данных'],['msg'=>'из Лист2 не получили данных']);
        die;
    }
} catch(\Exception $e) {
    errorTask($taskId,[$e->getLine(),$e->getFile(),$e->getMessage()],$e->getTrace());
    die;
}

//последний этап от правка успешного выполнения таску
endTask($taskId);
die;


function update($data) {
    $db = db();
    $sql = "UPDATE " . DB_PREFIX . "product SET price = '" . (float) $data['price'] . "', price_two = '" . (float) $data['price_two'] . "', quantity = '" . (int) $data['quantity'] . "', quantity_two = '" . (int) $data['quantity_two'] . "', status_two = '" . (int) $data['status_two'] . "' WHERE `sku` = '" . $data['sku'] . "'";
    $db->query($sql);
}

function updateStatus($data) {
    $db = db();
    if (!empty($data['status'])) {
        $query = $db->query("SELECT order_status_id FROM " . DB_PREFIX . "order_status WHERE name='" . $data['status'] . "'");
        $query->row['order_status_id'];
        $db->query("UPDATE " . DB_PREFIX . "order SET order_status_id = '" . (int) $query->row['order_status_id'] . "' WHERE `order_id` = '" . (int) $data['order_id'] . "'");
    }
}

function updateComment($data) {
    $db = db();
    $query = $db->query("SELECT * FROM " . DB_PREFIX . "order_history WHERE order_id='" . (int) $data['order_id'] . "'");
    $order_history = end($query->rows);
    $db->query("UPDATE " . DB_PREFIX . "order_history SET order_status_id = '" . (int) $order_history['order_status_id'] . "', comment = '" . $data['comment'] . "' WHERE `order_id` = '" . (int) $data['order_id'] . "' AND order_history_id = '" . (int) $order_history['order_history_id'] . "'");
}

function db() {
    return $db = new db(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT, DB_PREFIX);
}

function startTask() {
    $db = db();
    $query = $db->query("INSERT INTO " . DB_PREFIX . "script_tasks (status,date_start) VALUES (1,CURRENT_TIME())");
    return $db->getLastId();
}

function errorTask($taskId, array $error=[],$file) {
    $db = db();
    errorToFile($file);
    $query = $db->query("UPDATE " . DB_PREFIX . "script_tasks SET status=2, error ='".implode(':',$error)."' WHERE id='".$taskId."'");
    return $query;
}

function endTask($taskId) {
    $db = db();
    $query = $db->query("UPDATE " . DB_PREFIX . "script_tasks SET status=0,date_end=CURRENT_TIME() WHERE id='".$taskId."'");
    return $query;
}

function errorToFile($file) {
    $error_file = '/var/www/istylespb/data/www/istylespb.ru/status/log.txt';
    $msg = date('d/m/Y H:i').":".json_encode($file).PHP_EOL;
    file_put_contents($error_file,$msg,FILE_APPEND);
}

function checkTaskActive() {
    $db = db();
    $query = $db->query("SELECT * FROM " . DB_PREFIX . "script_tasks WHERE status=1");
    return ($query->num_rows > 0) ? 1 : 0;
}

?>
