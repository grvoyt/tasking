<?php
// проверка на запуск из консоли
if(php_sapi_name() != 'cli') {
    header("HTTP/1.1 404 Not Found");
    exit();
}

//Настройки скрипта
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once('/var/www/istylespb/data/www/istylespb.ru/config.php');
require_once('/var/www/istylespb/data/www/istylespb.ru/system/startup.php');
require_once('/var/www/istylespb/data/www/istylespb.ru/system/sheet.php');
require_once('/var/www/istylespb/data/www/istylespb.ru/system/scr_libs.php');
//для sheets 
$_SERVER['DOCUMENT_ROOT'] = '/var/www/istylespb/data/www/istylespb.ru';

// форматируем в массив аргументы
$params = [];
foreach(array_slice($argv,1) as $param) {
    $temp = explode('=',$param);
    $params[$temp[0]] = $temp[1];
}

//проверяем токен
if(md5('istylespb.ru') !== $params['token']) {
    Task::errorToFile(['msg'=>'token denied','params' => $params]);
    die();
}

//таймаут запуска
if(isset($params['time'])) sleep( (int)$params['time'] );

if(isset($params['other'])) {
	$session = json_decode($params['other'],true);
} else {
    Task::errorToFile(['msg'=>'Нету параметров','params' => $params]);
    die;
}

//проверка работающих тасков и ожидание
while(Task::checkTaskActive()) {
    sleep(29);
}

//Запускаем и пишем таск в БД
$taskId = Task::startTask();

//add order to sheet
$sheet = new sheet();
$models = new ModelJobs();

try {
    $product = $models->getSheetOrderProduct($params['order_id']);

    foreach ($product as $key => $value) {
        $product_info[$key] = $models->getProduct($value['product_id']);
        $product_info[$key]['quantity_int'] = $value['quantity'];
        $product_info[$key]['total'] = $value['total'];
        $product_info[$key]['order_status_id'] = $value['order_status_id'];
        $product_info[$key]['status_name'] = $value['name'];
        $product_info[$key]['price'] = $value['price'];
    }
                        
    foreach ($product_info as $v) {
        
        if($v['quantity'] <= 0 && $v['quantity_two'] <= 0){
            $stock = "1";
        }elseif($v['quantity'] > 0){
            $stock = "1";
        }elseif($v['quantity'] <= 0 && $v['quantity_two'] > 0){
            $stock = "2";
        }elseif($v['quantity'] <= 0 && $v['quantity_two'] <= 0 && $v['status_two'] == true){
            $stock = "2";
        }
        
        $data_order = array(
            "order_id"          => (int)$params['order_id'],
            "date_added"        => date('d.m.Y'),
            "name"              => $v['name'],
            "sku"               => $v['sku'],
            "model"             => $v['model'],
            "quantity"          => (int)$v['quantity'],
            "quantity_two"      => (int)$v['quantity_two'],
            "quantity_int"      => (int)$v['quantity_int'],
            "status_two"        => "пох",
            "telephone"         => $session['telephone'],
            "email"             => $session['email'],
            "comment"           => $session['comment'],
            "order_status"      => $v['status_name'],
            "firstname"         => $session['firstname'],
            "shipping_address_1"   => $session['shipping_address_1'],
            "shipping"          => $session['shipping'],
            "total"             => $v['total'],
            "price"             => (int)$v['price'],
            "stock"             => (int)$stock,
        );

        $sheet->insert($data_order);
    }
} catch(\Exception $e) {
    Task::errorTask($taskId,[$e->getLine(),$e->getFile(),$e->getMessage()],$e->getTrace());
    die;
}

//first step
try{
	

	$products = $models->getProducts();
                
    foreach ($products as $key => $value) {

        if($value['quantity'] <= 0 && $value['quantity_two'] <= 0){
            $price = $value['price'];
        }elseif($value['quantity'] > 0){
            $price = $value['price'];
        }elseif($value['quantity'] <= 0 && $value['quantity_two'] > 0){
            $price = $value['price_two'];
        }elseif($value['quantity'] <= 0 && $value['quantity_two'] <= 0 && $value['status_two'] == true){
            $price = $value['price'];
        }

        $data[] = array(
            $value['name'], $value['sku'], (int)$price
        );
    }

    $sheet->clear();
    $sheet->insertThree($data);
} catch(\Exception $e) {
    Task::errorTask($taskId,[$e->getLine(),$e->getFile(),$e->getMessage()],$e->getTrace());
    die;
}

//этап 2
try {
   $status = $sheet->get("Первый лист");

    for($i = 1;$i < count($status['values']);$i++){
        if(!empty($status['values'][$i][0])){
            
            $data_status = array(
                "order_id"  => $status['values'][$i][0],
                "comment"   => $status['values'][$i][15],
                "status"    => !empty($status['values'][$i][20]) ? $status['values'][$i][20] : '',
            );
            
            updateStatus($data_status);
    //        if(!empty($data_status['status'])){
    //            $sheet->updateStatusTable($data_status);
    //        }
            updateComment($data_status);
        }
    } 
} catch(\Exception $e) {
    Task::errorTask($taskId,[$e->getLine(),$e->getFile(),$e->getMessage()],$e->getTrace());
    die;
}

sleep(120);

//этап 3
try {
    $prod = $sheet->get("Лист2");

    for($i = 1;$i < count($prod['values']);$i++){
        if(!empty($prod['values'][$i][1])){
            $data = array(
                "sku" => !empty($prod['values'][$i][1]) ? $prod['values'][$i][1] : '',
                "price" => !empty($prod['values'][$i][2]) ? $prod['values'][$i][2] : '',
                "price_two" => !empty($prod['values'][$i][3]) ? $prod['values'][$i][3] : '',
                "quantity" => !empty($prod['values'][$i][4]) ? $prod['values'][$i][4] : '',
                "quantity_two" => !empty($prod['values'][$i][5]) ? $prod['values'][$i][5] : '',
                "status_two" => !empty($prod['values'][$i][6])? $prod['values'][$i][6]: '',
            );
            
            update($data);
        }
    }
} catch(\Exception $e) {
    Task::errorTask($taskId,[$e->getLine(),$e->getFile(),$e->getMessage()],$e->getTrace());
    die;
}

//завершаем таск
Task::endTask($taskId);

function db(){
    return $db = new db(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT, DB_PREFIX);
}

function updateStatus($data){
    $db = db();
    if(!empty($data['status'])){
        $query = $db->query("SELECT order_status_id FROM " . DB_PREFIX . "order_status WHERE name='". $data['status'] ."'");
        $db->query("UPDATE " . DB_PREFIX . "order SET order_status_id = '" . (int)$query->row['order_status_id'] . "' WHERE `order_id` = '" . (int)$data['order_id'] . "'");
    }
}

function updateComment($data){
    $db = db();
    $query = $db->query("SELECT * FROM " . DB_PREFIX . "order_history WHERE order_id='". (int)$data['order_id'] ."'");
    $order_history = end($query->rows);
    $db->query("UPDATE " . DB_PREFIX . "order_history SET order_status_id = '" . (int)$order_history['order_status_id'] . "', comment = '" . $data['comment'] . "' WHERE `order_id` = '" . (int)$data['order_id'] . "' AND order_history_id = '". (int)$order_history['order_history_id'] ."'");
}

function update($data){
    $db = db();
    $sql = "UPDATE " . DB_PREFIX . "product SET price = '" . (float)$data['price'] . "', price_two = '" . (float)$data['price_two'] . "', quantity = '" . $data['quantity'] . "', quantity_two = '" . $data['quantity_two'] . "', status_two = '" . (int)$data['status_two'] . "' WHERE `sku` = '" . $data['sku'] . "'";
    $db->query($sql);
}

die;