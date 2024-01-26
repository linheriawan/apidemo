<?php
putenv("RABBIT_HOST=localhost");
putenv("RABBIT_PORT=5672");
putenv("RABBIT_USER=guest");
putenv("RABBIT_PASS=guest");

require_once __DIR__ ."/lib_d.php";
$message=["dat"=>"hellow","yey"=>"welcome"];

try{
/* Topic */
Rabbit_pubs::pub(getenv("RABBIT_HOST"), getenv("RABBIT_PORT"), getenv("RABBIT_USER"), getenv("RABBIT_PASS"))
    	->exchange('hellow')->key('info.kan','topic')->send($message);

/* Routing */
// Rabbit_pubs::pub(getenv("RABBIT_HOST"), getenv("RABBIT_PORT"), getenv("RABBIT_USER"), getenv("RABBIT_PASS"))
    // 	->exchange('hellow')->key('info','routing')->send($message);

/* FanOut */
// Rabbit_pubs::pub(getenv("RABBIT_HOST"), getenv("RABBIT_PORT"), getenv("RABBIT_USER"), getenv("RABBIT_PASS"))
    // 	->exchange('hellow')->send($message);
}catch(Exception $e){ echo "Error:{$e->getMessage()}"; }
?>