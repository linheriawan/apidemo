<?php // define('AMQP_DEBUG', true);
putenv("RABBIT_HOST=localhost");
putenv("RABBIT_PORT=5672");
putenv("RABBIT_USER=guest");
putenv("RABBIT_PASS=guest");
putenv("RABBIT_EXC=guest");
putenv("RABBIT_KEYS=setattend info.kan");
putenv("ATTD_URI=http://localhost/~heriawan/tattend");

require_once __DIR__."/lib_d.php";
$W=new Console();

$EX='hellow';
$keys=explode(" ", getenv("RABBIT_KEYS"));
if(count($argv)>1){ $EX=$argv[1]; }
$errcount=0;
begin:
echo ($errcount>0?"re-":"")."Listeing Rabbit exchange '".$W->color("red")->text($EX)."'\n";
echo "with keys '".$W->color("magenta")->text(json_encode($keys))."'\n\n";

try{

$CALLBACK=function ($M) {
  $W=new Console();
  
  $ATTD=getenv("ATTD_URI");
  switch($M->getRoutingKey()){
    case "setattend":
      $fattd=(object) FETT::to( "$ATTD/attend" )->put(json_decode($M->getBody()));
	    echo "the data is: ".$W->color("blue")->text($M->getBody())." is send\n";
      echo "and response($fattd->status) is: ".$W->color("green")->text(json_encode($fattd->body))."\n\n";
    break;
    default:
      echo "receive message with topic: ".$W->color("orange")->text($M->getRoutingKey())."\n";
      echo "the data is: ".$W->color("blue")->text($M->getBody())."\n\n";
    break;
  }
  
};

Rabbit_subs::subs(getenv("RABBIT_HOST"), getenv("RABBIT_PORT"), getenv("RABBIT_USER"), getenv("RABBIT_PASS"))
  ->exchange($EX) // fanout
  ->key($keys,'topic') // topic
  // ->key($keys,'routing') //routing
  ->start($CALLBACK);
}catch(Exception $e){ 
  $errcount=$errcount+1;
  $em=$e->getMessage();
  echo "ERROR:".$W->color("red")->text($em)."\n\n"; 
  if(strpos("PRECONDITION_FAILED - inequivalent arg 'type' for exchange",$em)==0){
    Crabbit::subs(getenv("RABBIT_HOST"), getenv("RABBIT_PORT"), getenv("RABBIT_USER"), getenv("RABBIT_PASS"))
      ->rem_exchange($EX);
  }
  if($errcount<3){ goto begin; }
}
?>
