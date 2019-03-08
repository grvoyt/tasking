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


//пабота самого скрипта
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$config = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config.php';

if (is_file($config)) {
    require_once($config);
}
require_once(DIR_SYSTEM . 'startup.php');
require_once DIR_SYSTEM . '/sheet.php';

//Пишем таск в БД
$taskId = startTask();


// этап 1
try {
    $sheet = new sheet();

    $products = getProducts();

    foreach ($products as $key => $values) {
        
        if($values['quantity'] <= 0 && $values['quantity_two'] <= 0){
            $price = $values['price'];
        }elseif($values['quantity'] > 0){
            $price = $values['price'];
        }elseif($values['quantity'] <= 0 && $values['quantity_two'] > 0){
            $price = $values['price_two'];
        }elseif($values['quantity'] <= 0 && $values['quantity_two'] <= 0 && $values['status_two'] == true){
            $price = $values['price'];
        }
        
        $datas[] = array(
            $values['name'], $values['sku'], (int)$price
        );
    }

    $sheet->clear();
    $sheet->insertThree($datas);
} catch(\Exception $e) {
    errorTask($taskId,$e->getMessage());
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
    errorTask($taskId,$e->getMessage());
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
    errorTask($taskId,$e->getMessage());
    die;
}

//последний этап от правка успешного выполнения таску
endTask($taskId);
die;


function getProducts(){
    $db = db();
    $query = $db->query("SELECT * FROM " . DB_PREFIX . "product p LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.product_id = pd.product_id)");
    return $query->rows;
}

function update($data){
    $db = db();
    $sql = "UPDATE " . DB_PREFIX . "product SET price = '" . (float)$data['price'] . "', price_two = '" . (float)$data['price_two'] . "', quantity = '" . $data['quantity'] . "', quantity_two = '" . $data['quantity_two'] . "', status_two = '" . (int)$data['status_two'] . "' WHERE `sku` = '" . $data['sku'] . "'";
    $db->query($sql);
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

function db(){
    return $db = new db(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT, DB_PREFIX);
}

function startTask() {
    $db = db();
    $query = $db->query("INSERT INTO " . DB_PREFIX . "script_tasks (`status`) VALUES (1)");
    return $db->getLastId();
}

function errorTask($taskId, string $error='') {
    $db = db();
    $query = $db->query("UPDATE " . DB_PREFIX . "script_tasks SET status=2, error ='".(string)$error."' WHERE id='".$taskId."'");
    return $query;
}

function endTask($taskId) {
    $db = db();
    $query = $db->query("UPDATE " . DB_PREFIX . "script_tasks SET status=0 WHERE id='".$taskId."'");
    return $query;
}

?>

