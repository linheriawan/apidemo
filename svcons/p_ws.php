<?php
require __DIR__."/lib_d.php";
loadLib::from(__DIR__."/lib/websoc")->as('wsserver');
use wsserver\Client;

$client = new Client('ws://127.0.0.1:8080');
$client->sendOnce("just send from PHP");
// $client->onMessage(function ($message, $client) {
//     echo $message . "\r\n";
// });
// $client->connect();
?>