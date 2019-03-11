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
if(md5('istylespb.ru') !== $params['token']) die('token_denied');

//таймаут запуска
if(isset($params['time'])) sleep( (int)$params['time'] );

if(isset($params['other'])) {
	$session = json_decode($params['other'],true);
}

//проверка работающих тасков и ожидание
while(Task::checkTaskActive()) {
    sleep(29);
}

//Запускаем и пишем таск в БД
$taskId = Task::startTask();

//first step
try{
	$sheet = new sheet();
	$models = new ModelJobs();

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

//second step

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
            "order_id"          => (int)$this->session->data['order_id'],
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

//завершаем таск
Task::endTask($taskId);
die;