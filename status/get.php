<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once('/var/www/istylespb/data/www/istylespb.ru/config.php');
require_once('/var/www/istylespb/data/www/istylespb.ru/system/startup.php');

class getInfo {
    protected $tasks;
    protected $status = ['успешно','в процессе','ошибка'];
    protected $types = ['auto','client'];
    public function __construct()
    {
        return $this;
    }

	public function getTasks() {
		$db = $this->getDB();
	    $query = $db->query("SELECT * FROM " . DB_PREFIX . "script_tasks WHERE status > 0");
	    $this->tasks = $query->rows;
	    return $this;
	}

	public function getTasksAll() {
		$db = $this->getDB();
	    $query = $db->query("SELECT * FROM " . DB_PREFIX . "script_tasks");
	    $this->tasks = $query->rows;
	    return $this;
	}

    protected function getDB() {
    	return new db(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT, DB_PREFIX);
    }

    public function toJson() {
		// clear the old headers
	    header_remove();
	    // set the actual code
	    http_response_code(200);
	    // set the header to make sure cache is forced
	    header("Cache-Control: no-transform,public,max-age=300,s-maxage=900");
	    // treat this as json
	    header('Content-Type: application/json');
	    header('Status: 200');
    	return json_encode(get_object_vars($this));
    }
}

if($_SERVER['REQUEST_METHOD'] == 'GET') {
    $tasks = new getInfo();
    echo $tasks->getTasks()->toJson();
}