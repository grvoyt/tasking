<?php
$script = '/var/www/istylespb/data/www/istylespb.ru/system/script.php';
if (($file = realpath($script)) === false) {
    print_r('[exec_bg_script] File ' . $script . ' not found!');
    return false;
}
$escape = true;
$params = ['time'=>1,'token'=>md5('istylespb.ru')];
array_walk($params, function(&$value, $key) use($escape) {
    $value = $escape ? $key . '=' . escapeshellarg($value) : $key . '=' . $value;
});

$command = sprintf('php %s %s', $file, implode(' ', $params)) . " > /dev/null &";
exec($command);
?>