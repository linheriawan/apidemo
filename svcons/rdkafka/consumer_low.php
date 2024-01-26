<?php
/** Low-level consumer: at least once consuming example
 * This example shows how to consume messages at least once. 
 * This is achieved by committing offsets only after a message has been successfully consumed.
*/
$topic = $rk->newTopic("test", $topicConf);

// ...

$message = $rk->consume(0, 120*1000);

handle_message($message);

// After successfully consuming the message, schedule offset store.
// Offset is actually committed after 'auto.commit.interval.ms'
// milliseconds.
$topic->offsetStore($message->partition, $message->offset);
/**Multiple topics/partitions
 * This examples show how to consumer from multiple topics and/or partitions when using the low-level consumer.
*/
// Consuming from multiple topics and/or partitions can be done by telling
// librdkafka to forward all messages from these topics/partitions to an
// internal queue, and then consuming from this queue.

$queue = $rk->newQueue();

$topicConf = new RdKafka\TopicConf();
$topicConf->set(...);

$topic1 = $rk->newTopic("topic1", $topicConf);
$topic1->consumeQueueStart(0, RD_KAFKA_OFFSET_BEGINNING, $queue);
$topic1->consumeQueueStart(1, RD_KAFKA_OFFSET_BEGINNING, $queue);

$topic2 = $rk->newTopic("topic2", $topicConf);
$topic2->consumeQueueStart(0, RD_KAFKA_OFFSET_BEGINNING, $queue);

// Now, consume from the queue instead of the topics:

while (true) {
    $message = $queue->consume(120*1000);
    // ...
}
/**  STD Low level 
 * Complete Example
*/
$conf = new RdKafka\Conf();

// Set the group id. This is required when storing offsets on the broker
$conf->set('group.id', 'myConsumerGroup');

// Emit EOF event when reaching the end of a partition
$conf->set('enable.partition.eof', 'true');

$rk = new RdKafka\Consumer($conf);
$rk->addBrokers("127.0.0.1");

$topicConf = new RdKafka\TopicConf();
$topicConf->set('auto.commit.interval.ms', 100);

// Set the offset store method to 'file'
$topicConf->set('offset.store.method', 'broker');

// Alternatively, set the offset store method to 'none'
// $topicConf->set('offset.store.method', 'none');

// Set where to start consuming messages when there is no initial offset in
// offset store or the desired offset is out of range.
// 'earliest': start from the beginning
$topicConf->set('auto.offset.reset', 'earliest');

$topic = $rk->newTopic("test", $topicConf);

// Start consuming partition 0
$topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);

while (true) {
    $message = $topic->consume(0, 120*10000);
    switch ($message->err) {
        case RD_KAFKA_RESP_ERR_NO_ERROR:
            var_dump($message);
            break;
        case RD_KAFKA_RESP_ERR__PARTITION_EOF:
            echo "No more messages; will wait for more\n";
            break;
        case RD_KAFKA_RESP_ERR__TIMED_OUT:
            echo "Timed out\n";
            break;
        default:
            throw new \Exception($message->errstr(), $message->err);
            break;
    }
}

?>