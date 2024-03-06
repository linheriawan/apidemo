<?php
class loadLib{
    private $_Ext, $_NSseparator, $_NameSpace, $_Path;
    public function __construct($includePath){
        $this->_Ext = '.php';
        $this->_NSseparator = '\\';
        $this->_Path = $includePath;
        $this->loadnonclass($includePath);
    }
    private function loadnonclass($dir){
        foreach(scandir($dir) as $r){
            if(in_array($r,['.DS_Store',]) ){ unlink("$dir/$r"); }
            else if(!in_array($r,['..', '.','index.html']) ){
                 if( is_dir("$dir/$r") ){ $this->loadnonclass("$dir/$r"); }    
                 else{  $fn=explode(".",$r);
                    if(count($fn)==2 && $fn[1]=="php"){
                        $fc=file_get_contents("$dir/$r");
                        $p=strpos($fc,"class $fn[0]")==false;
                        if (strpos($fc,"class $fn[0]") ==false
                        && strpos($fc,"interface $fn[0]") ==false
                        && strpos($fc,"trait $fn[0]") ==false ){
                            require "$dir/$r"; 
                        }
                    }
                }
            }
        }
    }
    static function from($includePath){ return new self($includePath);}
    public function as($ns=NULL){
        $this->_NameSpace = $ns==NULL ? "" : $ns;
        $this->register();
    }
    private function register(){ spl_autoload_register(array($this, 'require_class')); }
    private function unregister() { spl_autoload_unregister(array($this, 'require_class')); }
    private function require_class($className){
        $NSwithSeparator=$this->_NameSpace.$this->_NSseparator;
        $ClassNS=substr( $className, 0, strlen($NSwithSeparator));
        if( $this->_NameSpace === null || $ClassNS === $NSwithSeparator ) {
            $className=str_replace($this->_NameSpace,"",$className);
            $namespace=explode($this->_NSseparator, $className);
            $file=array_pop($namespace).$this->_Ext;
            $_NSpath=implode(DIRECTORY_SEPARATOR, $namespace).DIRECTORY_SEPARATOR;            
            $filePath = $this->_Path.$_NSpath.$file;
            $load=file_exists($filePath) ? $filePath : $this->_Path.$file;
            require $load; 
        }
    }
}
function vdum(...$x){
    foreach ($x as $i) {
      ob_start(); var_dump($i); $d=ob_get_contents(); ob_end_clean();
      $em= preg_replace_callback('/(\]=>\n\s*string.*?\s*")(.+?)("\n\s*\[|"\n\s+})/s',
        function ($m) {
          if(strpos($m[2],"=>")){
            $mx= preg_replace('/=>\n\s+/'," => ",$m[2]);
            $r="$m[1]$mx$m[3]";
            return preg_replace('/ (".*?")/', " <span style='color:blue'>$1</span>", $r);
          } else{ return "$m[1]<span style='color:blue'>$m[2]</span>$m[3]"; }
        }, $d);
      $em= preg_replace('/=>\n\s+/'," => ",$em);
      $em= preg_replace('/=>\n\t*/'," => ",$em);
      $em= preg_replace('/\] => \"\"\n/'," => ",$em);
      $em= preg_replace('/(\{\n\s+\})/'," {}\n",$em);
      $em= preg_replace('/(\[".*?":*.*\])/', "<b style='color:green'>".'$1'."</b>",$em);
      echo "<pre>$em</pre>";
    }
}
class Console{
    private $CLR=[
        'reset'     => [0,0],
        'red'       => [91, 101],
        'green'     => [92, 101],
        'orange'    => [93, 103],
        'blue'      => [94, 104],
        'magenta'   => [95, 105],
        'cyan'      => [96, 106],
        'white'     => [97, 107],
    ];
    private $bgcolor,$color,$bold;
    function __construct(){  $this->reset(); }
    function reset(){ 
        $this->bgcolor=$this->CLR['reset'][1];
        $this->color=$this->CLR['reset'][0];
        $this->bold=false;
    }
    function bold($b){$this->bold=$b;return $this;}
    function color($c){$this->color=$this->CLR[$c][0];return $this;}
    function setbg($c){$this->bgcolor=$this->CLR[$c][1];return $this;}
    
    function bg($i){ return "\e[$this->bgcolor."."m$i\033[0m";}
    function text($i){ 
        $i=gettype($i)=="string" ? $i : json_encode($i);
        if($this->bold){
            return "\e[1;$this->color"."m$i\033[0m";
        }else{
            return "\e[0;$this->color"."m$i\033[0m";
        }
    }
}
class Fett{
    private $URL,$HEADER,$OPTION;
    function __construct($url){
        $this->URL=$url;
        $this->HEADER=[
            "Cache-Control"=>"no-cache",
            "Content-Type"=>"application/json",
            "Accept"=>"application/json",
        ];
        $this->OPTION=["timeout"=>30,"checkssl"=>0];
    }
    static function to($url) { 
      $url=explode("?",$url);
      $q=ISSET($url[1])? "?".str_replace(" ","+",$url[1]): ""; 
      return new self("$url[0]$q"); 
    }
    private function getHeaderValue($p){ return $this->HEADER[$p]; }
    private function getHeader(){
        $t=[];
        foreach($this->HEADER as $k=>$v){ array_push($t,"$k: $v"); }
        return $t;
    }
    private function jsonify($j){ return !empty(json_decode($j)) ? json_decode($j) : $j; }
    public function header($header){
        $this->HEADER=array_merge($this->HEADER,$header); return $this;
    }
    public function options($opts){
        $this->OPTION=array_merge($this->OPTION,$opts); return $this;
    }
    public function get(){
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->URL);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->OPTION["timeout"]);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->OPTION["checkssl"]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->OPTION["checkssl"]);
            $ex = curl_exec($ch);
            // var_dump($ex );
            if ($ex === false) { throw new Exception(curl_error($ch), curl_errno($ch)); }
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if($this->getHeaderValue("Accept")=="application/json"){$ex=$this->jsonify($ex);}
            return (object)["status"=>$httpCode,"body"=>$ex];
        } catch(Exception $e) {
            trigger_error( sprintf( 'Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()), E_USER_ERROR);
        } finally {
            if (is_resource($ch)) { curl_close($ch); }
        }
    }
    public function post($data){
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->URL);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            if(gettype($data)=="array" && $this->getHeaderValue("Content-Type")=="application/json"){
                $data=json_encode($data);
            }else{
                $data=http_build_query($data);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->OPTION["timeout"]);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->OPTION["checkssl"]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->OPTION["checkssl"]);
            $ex = curl_exec($ch);
            if ($ex === false) { throw new Exception(curl_error($ch), curl_errno($ch)); }
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if($this->getHeaderValue("Accept")=="application/json"){$ex=$this->jsonify($ex);}
            return (object)["status"=>$httpCode,"body"=>$ex];
        } catch(Exception $e) {
            trigger_error( sprintf( 'Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()), E_USER_ERROR);
        } finally {
            if (is_resource($ch)) { curl_close($ch); }
        }
    }
    public function put($data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->URL);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->OPTION["timeout"]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->OPTION["checkssl"]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->OPTION["checkssl"]);
        $ex = curl_exec($ch);
        if ($ex === false) { throw new Exception(curl_error($ch), curl_errno($ch)); }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($this->getHeaderValue("Accept")=="application/json"){$ex=$this->jsonify($ex);}
        return (object)["status"=>$httpCode,"body"=>$ex];
    }
    public function patch($data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->URL);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->OPTION["timeout"]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->OPTION["checkssl"]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->OPTION["checkssl"]);
        $ex = curl_exec($ch);
        if ($ex === false) { throw new Exception(curl_error($ch), curl_errno($ch)); }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($this->getHeaderValue("Accept")=="application/json"){$ex=$this->jsonify($ex);}
        return (object)["status"=>$httpCode,"body"=>$ex];
    }
    public function DELETE(){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->URL);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->OPTION["timeout"]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        // curl_setopt($ch, CURLOPT_HEADER, TRUE);
        // curl_setopt($ch, CURLOPT_NOBODY, TRUE); // remove body
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->OPTION["checkssl"]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->OPTION["checkssl"]);
        $ex = curl_exec($ch);
        if ($ex === false) { throw new Exception(curl_error($ch), curl_errno($ch)); }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($this->getHeaderValue("Accept")=="application/json"){$ex=$this->jsonify($ex);}
        return (object)["status"=>$httpCode,"body"=>$ex];
    }
}

loadLib::from(__DIR__."/lib/PhpAmqpLib")->as('PhpAmqpLib');
// define('AMQP_DEBUG', true);
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
class Rabbit_subs{
    private $connection, $channel, $EXNAME, $MODE,$ROUTING_KEY;
    function __construct($host, $port, $user, $pass){
        $this->connection = new AMQPStreamConnection($host, $port, $user, $pass, '/');
        $this->channel = $this->connection->channel();
        $this->MODE='basic';
    }
    static function subs($host, $port, $user, $pass){return new self($host, $port, $user, $pass);}
    function rem_exchange($EXNAME){$this->channel->exchange_delete($EXNAME);}
    function exchange($EXNAME, ){ $this->EXNAME=$EXNAME;  return $this;  }
    function key($keys,$m='basic'){
        $this->MODE=$m; 
        $this->ROUTING_KEY=$keys; 
        return $this; 
    }
    function start($CALLBACK){
        // try{
            switch($this->MODE){
                case 'topic': 
                    $this->channel->exchange_declare($this->EXNAME, 'topic', false, false, false);
                    if($this->ROUTING_KEY==NULL){$this->ROUTING_KEY=["info.*"];}
                break;
                case 'routing':
                    $this->channel->exchange_declare($this->EXNAME, 'direct', false, false, false);
                    if($this->ROUTING_KEY==NULL){$this->ROUTING_KEY=["info"];}
                break;
                default: // fanout
                    $this->channel->queue_declare($this->EXNAME, 'fanout', false, false, false);
                break;
            }

            list($queue_name, ,) = $this->channel->queue_declare("", false, false, true, false);

            if(in_array($this->MODE,['topic', 'routing'])){
                foreach ($this->ROUTING_KEY as $K) {
                    $this->channel->queue_bind($queue_name, $this->EXNAME, $K);
                }
            }else{
                $this->channel->queue_bind($queue_name, $this->EXNAME);
            }
            
            $this->channel->basic_consume($queue_name, '', false, true, false, false, $CALLBACK);
            while ($this->channel->is_open()) { $this->channel->wait(); }
            $this->channel->close();
            $this->connection->close();
        // }catch(Exception $e){ 
        //     $em=$e->getMessage();
        //     echo "Error $em"; 
        //     if(strpos("PRECONDITION_FAILED - inequivalent arg 'type' for exchange",$em)==0){
        //         $this->rem_exchange($this->EXNAME);
        //         $this->exchange($this->EXNAME)->start($CALLBACK);
        //     }
        // }
    }
}
class Rabbit_pubs{
    private $connection, $channel, $exchange, $mode, $routekey;

    function __construct($host, $port, $user, $pass){
        $this->connection = new AMQPStreamConnection($host, $port, $user, $pass,'/');
        $this->channel = $this->connection->channel();
        $this->mode='direct';
    }
    static function pub($host, $port, $user, $pass){ return new self($host, $port, $user, $pass); }
    private function setMessage($hello){
        $msg=$hello=="string" ? $hello : json_encode($hello);
        return new AMQPMessage( $msg );
    }
    private function done($msg=""){
        $this->channel->close();
        $this->connection->close();
        $msg=$msg=="string" ? $msg : json_encode($msg);
        vdum( "msg: $msg is sent" );
    }
    function exchange($ex){$this->exchange=$ex;  return $this; }
    function key($k,$m='basic'){$this->mode=$m; $this->routekey=$k; return $this; }
    function send($hello){ 
        try{
        $msg = $this->setMessage($hello);
        switch($this->mode){
            case 'topic':
            $this->channel->exchange_declare($this->exchange, 'topic', false, false, false);
            if($this->routekey==NULL){$this->routekey="info.else";}
            $this->channel->basic_publish($msg, $this->exchange, $this->routekey);
            break;
            case 'routing':
            $this->channel->exchange_declare($this->exchange, 'direct', false, false, false);    
            if($this->routekey==NULL){$this->routekey="info";}
            $this->channel->basic_publish($msg, $this->exchange, $this->routekey);
            break;
            default: //fanout
            $this->channel->queue_declare($this->exchange, 'fanout', false, false, false);
            $this->channel->basic_publish($msg, '', $this->exchange);
            break;
        } $this->done($hello);
        } catch(Exception $e){ throw new Exception( "Error(Rabbit): {$e->getMessage()}");}
    }
    
}
class RDKafka_pubs{
    private $broker,$topics,$key;
    function __construct($b){ $this->broker=$b; }
    static function broker($b){ return new self($b);}
    function topic($t){ $this->topics=$t; return $this;}
    function key($k){ $this->key=$k; return $this;}
    function send($msg){
        $r=shell_exec("php ./lib/rdkafka_produce.php -b $this->broker -t $this->topics -m '$msg' -k $this->key");
        [$rs,$msg]=explode(": ",$r);
        if($rs=="Done"){
            return true;
        }else{ echo "Error:$msg"; return false;}
    }
}
class RDKafka_subs{
    private $donerebal,$conf,$consumer,$message,$onconnect;
    function __construct($broker){
        $this->conf = new RdKafka\Conf();
        $this->conf->setRebalanceCb(function (RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
            switch ($err) {
                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                    echo "Assign: ". json_encode($partitions)."\n";
                    $kafka->assign($partitions);
                break;
                case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                    echo "Revoke: ". json_encode($partitions)."\n";
                    $kafka->assign(NULL);
                break;
                default: throw new \Exception($err);
            }
            $this->donerebal=true;
            if($this->onconnect!=NULL){ call_user_func($this->onconnect); }
        });
        $this->conf->set('metadata.broker.list',$broker);
        $this->conf->set('auto.offset.reset', 'earliest');
    }
    static function init($broker){return new self($broker);}
    function gid($id){ $this->conf->set('group.id', $id); return $this;}
    function topics($t){
        $this->consumer = new RdKafka\KafkaConsumer($this->conf);
        $this->consumer->subscribe($t);
        return $this;
    }
    function onConnect($callback){ $this->onconnect=$callback; return $this; }
    function start($callback){
        while (true) {  
            // if($this->donerebal==true){ 
                $W=new Console();
                $this->message = $this->consumer->consume(120*1000);
                switch ($this->message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        call_user_func($callback, $this->message); 
                        break;
                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                        // echo $W->color("cyan")->text("...waiting for more messages\n");
                        break;
                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        echo $W->color("red")->text("Timed out\n");
                        // $e=$this->message->errstr();
                        // throw new Exception("$e:Timed out");
                        break;
                    default:
                        // echo $W->color("red")->text("ERROR:".json_encode($message)."\n");
                        $e=$this->message->errstr();
                        throw new Exception("$e, \nmsgErr:{$message->err}");
                        break;
                }
            // } 
        }
    }
}
loadLib::from(__DIR__."/lib/Kafka")->as('Kafka');
loadLib::from(__DIR__."/lib/Log")->as('Psr\Log');
loadLib::from(__DIR__."/lib/Amp")->as('Amp');
use \Kafka\ConsumerConfig;
use \Kafka\Consumer;
class Kafka_cons{
    private $config;
    function __construct($broker){
        $this->config = ConsumerConfig::getInstance();
        $this->config->setMetadataRefreshIntervalMs(10000);
        $this->config->setMetadataBrokerList($broker);
        $this->config->setBrokerVersion('1.0.0');
        // $this->config->setOffsetReset('earliest');
        $this->config->setOffsetReset('latest');
    }
    static function init($broker){return new self($broker);}
    function gid($id){ $this->config->setGroupId($id); return $this;}
    function topics($t){$this->config->setTopics($t); return $this;}
    function start($CALLBACK){ 
        $consumer = new Consumer();
        $consumer->start($CALLBACK);
    }
}
use \Kafka\ProducerConfig;
use \Kafka\Producer;
class Kafka_pubs{
    private $messages;
    function __construct($b){
        $config = ProducerConfig::getInstance();
        $config->setMetadataRefreshIntervalMs(10000);
        $config->setMetadataBrokerList($b);
        $config->setBrokerVersion('1.0.0');
        $config->setRequiredAck(1);
        $config->setIsAsyn(false);
        $config->setProduceInterval(500);        
    }
    static function init($b){return new self($b);}
    function messages($msg){$this->messages=$msg; return $this;}
    function send(){
        $producer = new Producer(function(){return $this->messages;});
        $producer->success( function($res) { vdum("Success",$res); });
        $producer->error( function($err){ 
            vdum("Error",$err); 
            $e=json_encode($err);
            throw new Exception( "Error(Kafka Publish): {$e}");
        });
        $producer->send(true);
    }
}
?>