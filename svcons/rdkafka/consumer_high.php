<?php // High-level consumer
putenv("KAF_BROKER=127.0.0.1");

require_once __DIR__."/../lib_d.php";

$TOPIC='test';
if(count($argv)>1){ $TOPIC=$argv[1]; }

// Subscribe to topic 
$TOPIC=explode(" ",$TOPIC);
$W=new Console();
echo "starting kafka topic:'".$W->color("red")->text(json_encode($TOPIC))."' \n";
echo "Waiting for partition assignment... (make take some time when quickly re-joining the group after leaving it.)\n";
$donerebal=false;

// try{
$conf = new RdKafka\Conf();
// Set a rebalance callback to log partition assignments (optional)
$conf->setRebalanceCb(function (RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
    switch ($err) {
        case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
            echo "Assign: ". json_encode($partitions)."\n";
            $kafka->assign($partitions);
            
        break;
        case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
            echo "Revoke: ". json_encode($partitions)."\n";
            $kafka->assign(NULL);
        break;
        default:
            throw new \Exception($err);
    }
    $donerebal=true;
    
});
// Configure the group.id. All consumer with the same group.id will consume different partitions.
$conf->set('group.id', 'myConsumerGroup');
$conf->set('metadata.broker.list',getenv("KAF_BROKER"));
$conf->set('auto.offset.reset', 'earliest');
// Emit EOF event when reaching the end of a partition
$conf->set('enable.partition.eof', 'true');

$consumer = new RdKafka\KafkaConsumer($conf);
$consumer->subscribe($TOPIC);

function listen($consumer){
    $W=new Console();
    $message = $consumer->consume(120*1000);
    switch ($message->err) {
        case RD_KAFKA_RESP_ERR_NO_ERROR:
            echo "receive topic:{$W->color("green")->text($message->topic_name)}".
                "\npayload:".$W->color("green")->text($message->payload)."\n";
            break;
        case RD_KAFKA_RESP_ERR__PARTITION_EOF:
            echo $W->color("cyan")->text("...waiting for more messages\n");
            break;
        case RD_KAFKA_RESP_ERR__TIMED_OUT:
            echo $W->color("red")->text("Timed out\n");
            break;
        default:
            echo $W->color("red")->text("ERROR:".json_encode($message)."\n");
            // throw new Exception($message->errstr(), $message->err);
            break;
    }
}

while (true) {  
    // if($donerebal==true){ 
        listen($consumer); 
    // } 
}
?>