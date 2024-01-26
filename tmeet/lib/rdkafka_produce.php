<?php
$p = getopt("b:t:m:k:");
$BROKER=array_key_exists("b",$p)?$p["b"]:"localhost:9092";
$TOPIC=array_key_exists("t",$p)?$p["t"]:"test";
$MSG=array_key_exists("m",$p)?$p["m"]:"hi...";
$MKEY=array_key_exists("k",$p)?$p["k"]:NULL;

$conf = new RdKafka\Conf();
// $conf->set('log_level', (string) LOG_DEBUG);
// $conf->set('debug', 'all');
$conf->set('metadata.broker.list', $BROKER);
$conf->set('bootstrap.servers', $BROKER);
$conf->set('socket.timeout.ms', (string) 50);
$conf->set('queue.buffering.max.messages', (string) 1000);
$conf->set('max.in.flight.requests.per.connection', (string) 1);

$conf->setErrorCb(function (Producer $producer, int $errorCode, string $errorMsg) {
    $pp=json_encode($producer);
    echo "Error: CB[producer:$pp, errorMsg: $errorMsg, errorCode: $errorCode]\n";
    // throw new \RuntimeException($errorMsg, $errorCode);
});

$producer = new RdKafka\Producer($conf);
$topic = $producer->newTopic($TOPIC);
$topic->produce(RD_KAFKA_PARTITION_UA, 0, $MSG, $MKEY);
// $producer->poll(0);

$result = $producer->flush(10000);
if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
    echo "Done: message '$MSG' is send\n";
}else {
    echo "Error: '$result', Was unable to flush, messages might be lost!\n";
    // throw new \RuntimeException('Was unable to flush, messages might be lost!');
}
// $producer->purge(RD_KAFKA_PURGE_F_QUEUE);
// $producer->purge(RD_KAFKA_PURGE_F_INFLIGHT);
?>