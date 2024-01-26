<?php
putenv("KAFKA_BROKER=localhost:9092");
putenv("KAFKA_GID=testing");
putenv("KAFKA_TOPICS=test");
putenv("ATTD_URI=http://localhost/~heriawan/tattend");

require __DIR__."/lib_d.php";
$W=new Console();
try{
    $topics=explode(' ',getenv("KAFKA_TOPICS"));

    $CALLBACK=function($topic, $part, $message) {
        $W=new Console();
        $M=(object)$message["message"];
        echo date('h:i:s')."::receive topic:{$W->color("green")->text($topic)} with key: {$W->color("green")->text($M->key)}, on part: {$W->color("green")->text($part)}".
                "\n payload:".$W->color("cyan")->text($M->value)."\n\n";
        // var_export($message);
        // $ATTD=getenv("ATTD_URI");
        // switch($M->key){
        //     case "setattend":
        //         // $fattd=(object) FETT::to( "$ATTD/attend" )->put(json_decode($M->value));
        //         echo "the data is: ".$W->color("blue")->text($M->value)." is send\n";
        //         echo "and response($fattd->status) is: ".$W->color("green")->text(json_encode($fattd->body))."\n\n";
        //     break;
        //     default:
        //         echo date('h:i:s')."::receive topic:{$W->color("green")->text($topic)} with key: {$W->color("green")->text($M->key)}, on part: {$W->color("green")->text($part)}".
        //         "\n payload:".$W->color("cyan")->text($M->value)."\n\n";
        //     break;
        // }
    };
    echo "Listening KAFKA for '".$W->color("magenta")->text(json_encode($topics))."' topics\n";
    Kafka_cons::init(getenv("KAFKA_BROKER"))->gid(getenv("KAFKA_GID"))->topics($topics)->start( $CALLBACK );
}catch(Exception $e){ echo $W->color("red")->text("Error:{$e->getMessage()}"); }
?>