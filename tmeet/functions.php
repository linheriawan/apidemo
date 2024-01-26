<?php
function write(...$w){echo "<pre>";foreach($w as $p){var_dump($p);}echo "</pre>";}
function zipkin_timestamp(){ return intval(microtime(true) * 1000 * 1000); }
function is_zipkin_timestamp($timestamp){ return ctype_digit((string) $timestamp) && strlen($timestamp) === 16; }
function is_zipkin_trace_identifier($identifier){
    return ctype_xdigit((string) $identifier) &&
        (strlen((string) $identifier) === 16 || strlen((string) $identifier) === 32);
}
function is_zipkin_span_identifier($identifier){
    return ctype_xdigit((string) $identifier) && strlen((string) $identifier) === 16;
}

loadLib::from(__DIR__."/Zippy")->as('whitemerry\phpkin');

putenv("ZIPKIN_SERVER=http://127.0.0.1:9411");

use whitemerry\phpkin\Logger\SimpleHttpLogger;
use whitemerry\phpkin\Endpoint;
use whitemerry\phpkin\Tracer;
use whitemerry\phpkin\AnnotationBlock;
use whitemerry\phpkin\Span;
use whitemerry\phpkin\Identifier\SpanIdentifier;
use whitemerry\phpkin\TracerInfo;

class ZipTrace{
    private $endpoint, $logger, $tracer, $annotBlock, $profile, $spanid,
        $traceId, $traceSpanId, $isSampled;
    public $hdr;
    /*** Create logger to Zipkin, host is Zipkin's ip **/
    function __construct($name,$host,$profile=Tracer::FRONTEND){
        $this->profile=$profile;
        $this->endpoint=self::endpoint($name,$host);
        $this->logger = new SimpleHttpLogger(['host' => getenv("ZIPKIN_SERVER"), 'muteErrors' => false]);
    }
    private function detect(){
        if (!empty($_SERVER['HTTP_X_B3_TRACEID'])) {
            $this->traceId = new TraceIdentifier($_SERVER['HTTP_X_B3_TRACEID']);
        }
        if (!empty($_SERVER['HTTP_X_B3_SPANID'])) {
            $this->traceSpanId = new SpanIdentifier($_SERVER['HTTP_X_B3_SPANID']);
        }
        if (!empty($_SERVER['HTTP_X_B3_SAMPLED'])) {
            $this->isSampled = (bool) $_SERVER['HTTP_X_B3_SAMPLED'];
        }
    }
    static function endpoint($name,$host){
        [$ip,$port]=explode(":",$host);
        return new Endpoint($name, $ip, $port);
    }
    static function frontend($name,$host){  return new self($name,$host);  }
    static function backend($name,$host){ return new self($name,$host,$profile=Tracer::BACKEND);  }
    function setTracer($id){
        if($this->profile==Tracer::BACKEND){
            $this->detect();
            write($this->isSampled, $this->traceId, $this->traceSpanId);
            $this->tracer = new Tracer($id, $this->endpoint, $this->logger, $this->isSampled, $this->traceId, $this->traceSpanId);
            $this->tracer->setProfile($this->profile); 
        }else{
            $this->tracer = new Tracer($id, $this->endpoint, $this->logger);
        }
        $this->spanid= new SpanIdentifier();
        $this->hdr=["X-B3-TraceId"=>TracerInfo::getTraceId(),
        "X-B3-SpanId"=>(string) $this->spanid,
        "X-B3-ParentSpanId"=>TracerInfo::getTraceSpanId(),
        "X-B3-Sampled"=>(int) TracerInfo::isSampled()];
        return $this;
    }
    function block($name,$host){
        $endpoint = ZipTrace::endpoint($name,$host);
        $requestStart = zipkin_timestamp();
        $this->annotBlock = new AnnotationBlock($endpoint, $requestStart );
        return $this;
    }
    /*** Add span to Zipkin */ 
    function makeSpan($desc){
        $span = new Span($this->spanid, $desc, $this->annotBlock);
        $this->tracer->addSpan($span);
        return $this;
    }
    /**** You're done : Send data to Zipkin! :) */
    function end(){ $this->tracer->trace(); }
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
        $this->OPTION=["timeout"=>30];
    }
    static function to($url) { return new self($url); }
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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->URL);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->OPTION["timeout"]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $ex = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($this->getHeaderValue("Accept")=="application/json"){$ex=$this->jsonify($ex);}
        return (object)["status"=>$httpCode,"body"=>$ex];
    }
    public function post($data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->URL);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->OPTION["timeout"]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $ex = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($this->getHeaderValue("Accept")=="application/json"){$ex=$this->jsonify($ex);}
        return ["status"=>$httpCode,"body"=>$ex];
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
        $ex = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($this->getHeaderValue("Accept")=="application/json"){$ex=$this->jsonify($ex);}
        return ["status"=>$httpCode,"body"=>$ex];
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
        $ex = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($this->getHeaderValue("Accept")=="application/json"){$ex=$this->jsonify($ex);}
        return ["status"=>$httpCode,"body"=>$ex];
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
        $ex = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($this->getHeaderValue("Accept")=="application/json"){$ex=$this->jsonify($ex);}
        return ["status"=>$httpCode,"body"=>$ex];
    }
}