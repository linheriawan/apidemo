<?php
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
function vexp(...$x){
    foreach ($x as $i) {
        ob_start(); var_export($i);  $d=ob_get_contents(); ob_end_clean();
        $em= preg_replace_callback('/(\s*=>\s*\')(.+?)(\',\n\s+)/s',
        function ($m) { return "$m[1]<span style='color:blue'>".htmlspecialchars($m[2])."</span>$m[3]";}
        , $d);
        $em= preg_replace('/\s*=>\s*\n\s+/'," => ",$em);
        $em= preg_replace('/(\s+\(\n\s+\),)/',"(),",$em);
        $em= preg_replace('/(\'.*?\')(\s*=>\s*)/', "<b style='color:green'>".'$1'."</b>$2",$em);
        echo "<pre>$em</pre>";
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

class Rest{
	public $Attr=[];
  public function __construct(){
		$sv["method"]=$_SERVER["REQUEST_METHOD"];
		$sv["query"]=array_key_exists("QUERY_STRING",$_SERVER) ? $_SERVER["QUERY_STRING"] :[];
		$sv["issamesite"]=ISSET($_SERVER["HTTP_SEC_FETCH_SITE"])?$_SERVER["HTTP_SEC_FETCH_SITE"]: "none";
		$sv["extnl"]=ISSET($_SERVER["HTTP_SEC_FETCH_MODE"])?$_SERVER["HTTP_SEC_FETCH_MODE"]: "cors";
		$sv["req_cont_type"]=ISSET($_SERVER["CONTENT_TYPE"])?$_SERVER["CONTENT_TYPE"]:"";
		if(in_array('application/json',explode(';',$sv["req_cont_type"]))){
			$rd=file_get_contents('php://input');
			$_POST=empty($_POST)?json_decode($rd):$_POST;
			if($_POST==NULL && !empty($rd)){ parse_str(urldecode($rd), $_POST); }
		}
		$sv["post"]=$_POST;
		$sv["get"]=$_GET;
		$sv["uri"]=[];
		$sc=[];
		if(ISSET($_SERVER["PATH_INFO"])){  $sc=explode("/",$_SERVER["PATH_INFO"]);  }
		for($x=array_search("index.php",$sc)+1;$x<count($sc);$x++){ array_push($sv["uri"],$sc[$x]); }
		$this->Attr=$sv;
  }
	function proc(){ return implode("/",$this->Attr["uri"]).":".$this->Attr["method"]; }
  function get($v=""){
    if($v==""){ return $this->Attr["get"]==NULL?[]:$this->Attr["get"]; }
    else{ $x=(array)$this->Attr["get"];
        return array_key_exists($v,$x)?$x[$v]:false;
    }
  }
  function post($v=""){
    if($v==""){ return $this->Attr["post"]==NULL?[]:$this->Attr["post"]; }
    else{ $x=(array)$this->Attr["post"];
        return array_key_exists($v,$x)?$x[$v]:false;
    }
  }
	function fromPost(&$data, $full=true){
    foreach($data as $k=>$r){
      $val= ($this->post($r)==false) ? null : $this->post($r);
      if(is_int($k)){ unset($data[$k]); $k=$r;}
			if($full && $val==null){  $this->json(["'$k' is required"],400); }
			if($full==false && $val==null){  unset($data[$k]); }
			else{ $a=&$data[$k]; $a=$val; }
    }
  }
  function fromGet(&$data, $full=true){
    foreach($data as $k=>$r){
			$val=($this->get($r)==false) ? null : $this->get($r);
      if(is_int($k)){ unset($data[$k]); $k=$r;}
      else if($full && $val==null){ $this->json("'$k' is required",400); }
			if($full==false && $val==null){  unset($data[$k]); }
			else{ $a=&$data[$k]; $a=$val; }
    }
  }
  public function json($data,$code=200){
      if (ob_get_contents()) {ob_end_clean(); } http_response_code($code);
      ob_start();
			if(gettype($data)=="array"){
				$data=json_encode($data);
				header('Content-Type: application/json; charset=utf-8');
			}
			header('Content-Length: '.strlen($data));
			echo $data;
      ob_end_flush(); //die();
  }
  public function text($data,$code=200){
      if (ob_get_contents()) {ob_end_clean(); } http_response_code($code);
      ob_start();
      if(gettype($data)=="array"){ $data=json_encode($data); }
      header('Content-Type: text/plain; charset=utf-8');
      header('Content-Length: '.strlen($data));
      echo $data;
      ob_end_flush(); //die();
  }
}

class Mysql{
  private $link, $schema, $table, $colls, $query, $resp;
  function __construct($cf,$_tbl=NULL){
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $this->link = new \mysqli($cf->host, $cf->user, $cf->pass, $cf->name, $cf->port);
    $this->link->set_charset('utf8mb4');
    if (!$this->link) { die('Could not connect: ' . mysql_error()); }
    $this->table=$_tbl;
    $this->schema=$cf->name;
  }
  function __destruct() { $this->link->close(); }

  private function startsWith($haystack, $needle){
    return (substr($haystack, 0, strlen($needle) ) === $needle);
  }
  private function arr2sets($i){
    $r=[];
    foreach ($i as $k => $v) {
      $opr=" = ";
      array_push($r,gettype($v)=="string"?"$k $opr '$v'":"$k $opr $v");
    }
    $r=implode(", ",$r);
    return $r;
  }
  private function arr2filters($i){
    $r=[];
    foreach ($i as $k => $v) {
      $opr=" = ";
      if(strpos($v,"*")>-1){ $opr=" LIKE "; $v=str_replace("*","%",$v); }
      else if($this->startsWith($v,"!")){ $opr=" <> "; $v=str_replace("!","",$v); }
			else if($this->startsWith($v, ">=")){ $opr=" >= "; $v=str_replace(">=","",$v); }
      else if($this->startsWith($v, "<=")){ $opr=" <= "; $v=str_replace("<=","",$v); }
      else if($this->startsWith($v, "<")){ $opr=" < "; $v=str_replace("<","",$v); }
      else if($this->startsWith($v, ">")){ $opr=" > "; $v=str_replace(">","",$v); }
      array_push($r, gettype($v)=="string"?"$k $opr '$v'":"$k $opr $v");
    }
    $r=implode(" and ",$r);
    $r=str_replace(" and and"," and",$r);
    $r=str_replace(" and or"," or",$r);
    return $r;
  }
  private function get_fields(){
    $sqq=$this->link->query( "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_NAME='$this->table' and TABLE_SCHEMA='$this->schema';");
    $rows=[];
    while ($f = $sqq->fetch_array(MYSQLI_ASSOC)){ array_push($rows,$f["COLUMN_NAME"]); }
    return implode(", ",$rows);
  }
	private function parse($_res){
		$rows=[];
		if($_res){
			while ($row = $_res->fetch_object()){ array_push($rows,$row); }
			$_res->close();
		} return $rows;
	}

  function set_table($_tbl){$this->table=$_tbl; $this->colls=$this->get_fields(); return $this;}
	function colls($colls){
		$this->colls=gettype($colls)=="string" ? " $colls": implode(", ",$colls);
		return $this;
	}
  function selectOne($key=NULL ){
    $s=$this->select($colls,$key);
    if(count($s)==1){ return $s[0]; }
    return $s;
  }
  function select($key=NULL ){
    $this->query="SELECT $this->colls FROM $this->table";
    if($key!=NULL){
      $skey=gettype($key)=="string" ? " $key": $this->arr2filters($key);
      $this->query.=" WHERE $skey";
    }
    return $this;
  }
  function offset($off,$lim){ $this->query.=" limit $off,$lim"; return $this; }
	function delete($key){
    $stt=gettype($key)=="string" ? " $key;": $this->arr2filters($key);
		$this->query="DELETE FROM $this->table WHERE $stt;";
    return $this;
  }
	function query(){ return $this->query;}
	function result(){
    // try{
		$stmt = $this->link->prepare($this->query);
		switch(mb_strtolower(explode(" ",$this->query)[0])){
			case "select":
				$stmt->execute();
		    $this->resp=$this->parse($stmt->get_result());
			break;
			case "delete":
		    $this->resp=$stmt->execute();
			break;
			case "update":
				$this->resp=$stmt->execute();
			break;
			case "insert":
        $res=$stmt->execute();
        if($res){
          $spk=$this->parse($this->link->query("SHOW KEYS FROM $this->table WHERE Key_name = 'PRIMARY';"));
          $pk=[];
          foreach($spk as $k){ array_push($pk, $k->Column_name); }
          $gid=$this->link->query("SELECT LAST_INSERT_ID() as pk;");
          $rs=$this->parse($gid);
          $this->resp=$rs[0]->pk ? $rs[0]->pk :$res;
        }else{ $this->resp=$res; }
			break;
		} return $this->resp;
    // }catch(Exception $e){ return ["error",$e->getMessage()]; } 
	}

  function insert($data){
    $f=[]; $d=[];
    if(array_keys($data)[0]=="0"){
      foreach ($data as $key => $row) {
        $r=[];
        foreach ($row as $i => $j) {
          if($key==0){ array_push($f,$i); }
          array_push($r, gettype($j)=="string" ? "'$j'": $j );
        } array_push($d,"(".implode(", ",$r).")");
      }
      $field=implode(", ",$f);
      $vals=implode(", ",$d);
    }else{
      foreach($data as $k=>$v){
        array_push($f,$k);
        $v=str_replace("'","`",$v);
        array_push($d,gettype($v)=="string" ? "'$v'": $v);
      }
      $field=implode(", ",$f);
      $vals=implode(", ",$d);
      $vals="($vals)";
    }
    $this->query= "INSERT INTO $this->table ($field) VALUES $vals;";
    $stmt = $this->link->prepare($this->query);
		return $this;
  }
  function update($data,$key=NULL){
    $sdat=gettype($data)=="string" ? " $data;": $this->arr2sets($data);
    if($key==NULL){$skey="";}
    else{
        $skey=gettype($key)=="string" ? " $key;": $this->arr2filters($key);
        $skey=" WHERE $skey";
    }
    $this->query= "UPDATE $this->table SET $sdat$skey;";
		return $this;
  }

	function get_def($tbl=null){
		$T=ISSET($tbl)?$tbl:$this->table;
    $sqq=$this->link->query( "SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_KEY FROM information_schema.COLUMNS WHERE TABLE_NAME='$T' and TABLE_SCHEMA='$this->schema';");
    $rows=[];
    while ($f = $sqq->fetch_array(MYSQLI_ASSOC)){
      $d=["COLUMN_NAME"=>$f["COLUMN_NAME"],
      "COLUMN_TYPE"=>$f["COLUMN_TYPE"],
      "IS_NULLABLE"=>$f["IS_NULLABLE"],
      "COLUMN_KEY" =>$f["COLUMN_KEY"] ];
			array_push($rows,(object)$d);
    }
    return $rows;
  }
  function get_empty($tbl=null){
		$T=ISSET($tbl)?$tbl:$this->table;
    $sqq=$this->link->query( "SELECT COLUMN_NAME,DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_NAME='$T' and TABLE_SCHEMA='$this->schema';");
    $rows=[];
    while ($f = $sqq->fetch_array(MYSQLI_ASSOC)){
      switch($f["DATA_TYPE"]){
        case "varchar" : $emp=""; break;
        case "datetime" : $emp=Date('Y-m-d H:i:s'); break;
        case "timestamp" : $emp=Date('Y-m-d H:i:s'); break;
        default : $emp=0; break;
      } $rows[$f["COLUMN_NAME"]]=$emp;
    } return $rows;
  }
  static function run($sql){
    $res=[];
    foreach(explode(";\n",$sql) as $s){
      try{
        $stmt = $this->link->prepare("$s;");
        $r=$stmt->execute();
        array_push($res, $r);
      }catch(Exception $e){ array_push($res, $e->getMessage()); }
    } return $res;
  }
}

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

loadLib::from(__DIR__."/lib/Zippy")->as('whitemerry\phpkin');
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
    function __construct($server,$name,$host,$profile=Tracer::FRONTEND){
        $this->profile=$profile;
        $this->endpoint=self::endpoint($name,$host);
        $this->logger = new SimpleHttpLogger(['host' => $server, 'muteErrors' => false]);
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
    static function frontend($server,$name,$host){  return new self($server,$name,$host);  }
    static function backend($server,$name,$host){ return new self($server,$name,$host,$profile=Tracer::BACKEND);  }
    function setTracer($id){
        if($this->profile==Tracer::BACKEND){
            $this->detect();
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
?>