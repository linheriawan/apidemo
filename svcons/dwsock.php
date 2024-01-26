<?php 
require __DIR__."/lib_d.php";

loadLib::from(__DIR__."/websoc")->as('wsserver');
use wsserver\Server;

$server = new Server('ws://127.0.0.1:8080');
$server->onMessage(function ($sender, $message, $server) {
    $v=json_encode($server);
    $s=json_encode($sender);
    $m=json_encode($message);
    $W=new Console();
    echo "Server:".$W->color("green")->text($v)."\n".
    "Sender:".$W->color("green")->text($s)."\n".
    "Message:".$W->color("green")->text($m)."\n\n";
    
    foreach ($server->getClients() as $client) {
        if ($client !== $sender) {
            $client->send($message);
            echo  "Message:".$W->color("green")->text(json_encode($message))."\n".
            "send to:".$W->color("green")->text(json_encode($client))."\n\n";
        }
    }
});
$server->run();
?>