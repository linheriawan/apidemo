<?php
putenv("KAFKA_BROKER=localhost:9092");
putenv("KAFKA_TOPICS=test");

require __DIR__."/lib_d.php";

try{
    $messages=[];
    $key='test ss';
    $val=json_encode(["hellow"=> "shit asd"]);

    array_push($messages, [ 'topic' => getenv("KAFKA_TOPICS"), 'value' => $val, 'key' => $key ]);
    $r=Kafka_pubs::init(getenv("KAFKA_BROKER"))->messages($messages)->send();
    vdum($r,$messages);
}catch(Exception $e){ echo "Error:{$e->getMessage()}"; }
?>