<?php
putenv("KAFKA_BROKER=localhost:9092");
putenv("KAFKA_TOPICS=test");


$broker=getenv("KAFKA_BROKER");
$topics=getenv("KAFKA_TOPICS");
$msg=json_encode(["hello"=>"hi there.. new you ddadada"]);
$key="setattend";
$r=shell_exec("php ./p_rd.php -b $broker -t $topics -m '$msg' -k $key");
[$rs,$msg]=explode(": ",$r);
if($rs=="Done"){
    echo "Great:$msg";
}else{ echo "Error:$msg"; }
?>