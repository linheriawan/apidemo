<?php
require_once __DIR__ ."/lib_d.php";
putenv("DBHOST=localhost");
putenv("DBNAME=pnmdb");
putenv("DBUSER=root");
putenv("DBPASS=pass@word1");

putenv("ZIPKIN_SERVER=http://127.0.0.1:9411");

$R=new Rest();
try{
	$dbtable="person";
	
	$conn=(object)[
		"host"=> getenv("DBHOST"),
		"name"=> getenv("DBNAME"),
		"user"=> getenv("DBUSER"),
		"pass"=> getenv("DBPASS"),
		"port"=> getenv("DBPORT")?getenv("DBPORT"):3306];
	$DB=new Mysql($conn);
	// $ZT=ZipTrace::backend(getenv("ZIPKIN_SERVER"),'Person', '172.16.103.51:80');
	switch($R->proc()){
		case ":GET":
			$d=[]; foreach($DB->get_def($dbtable) as $i) { array_push($d,$i->COLUMN_NAME); }
			$R->json($d);
		break;
		case "person:GET":
			$p=$R->get();
			if(count($p)==1){
				$k=array_keys($p)[0];
				if($p[$k]==""){ $p=str_replace("_"," ",$k); }
			}
			$rs=$DB->set_table($dbtable)->select( $p )->result();
			// $ZT->setTracer('Person get /index')
			// 	->block('Person Table', '104.31.87.157:80')
			// 	->makeSpan('get /person');
			if($rs[0]=="error"){$R->json($rs[1],400);}
			else{$R->json($rs);}
		break;
		case "person:PUT":
			$param=[]; foreach($DB->get_def($dbtable) as $i) {
				if($i->COLUMN_KEY!=="PRI"){array_push($param, $i->COLUMN_NAME);}
			}
			$R->fromPost($param);
			$rs=$DB->set_table($dbtable)->insert( $param )->result();
			if($rs[0]=="error"){$R->json($rs[1],400);}
			else{$R->json($rs);}
			break;
		case "person:PATCH":
			$param=["gender","name","address","age","hobby","height"]; $R->fromPost($param, false);
			$keys=["id"]; $R->fromGet($keys);
			$rs=$DB->set_table($dbtable)->update( $param, $keys)->result();
			if($rs[0]=="error"){$R->json($rs[1],400);}
			else{$R->json($rs);}
		break;
		default:
			[$m,$t]=explode(":",$R->proc());
			$R->json( "'$m' not found on $t method", 404);
		break;
	}
}catch(Exception $e){ $R->text( $e->getMessage() ); }
?>
