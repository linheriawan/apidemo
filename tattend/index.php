<?php
require_once __DIR__ ."/lib_d.php";
putenv("DBHOST=localhost");
putenv("DBNAME=pnmdb");
putenv("DBUSER=root");
putenv("DBPASS=pass@word1");
putenv("PERSON_URI=http://localhost/~heriawan/tperson/");
putenv("MEET_URI=http://localhost/~heriawan/tmeet/");

putenv("ZIPKIN_SERVER=http://127.0.0.1:9411");
$R=new Rest();
try{
	$dbtable="attendees";
	$PERSON=getenv("PERSON_URI");
	$MEET=getenv("MEET_URI");
	
	$conn=(object)[
		"host"=> getenv("DBHOST"),
		"name"=> getenv("DBNAME"),
		"user"=> getenv("DBUSER"),
		"pass"=> getenv("DBPASS"),
		"port"=> getenv("DBPORT")?getenv("DBPORT"):3306];
	$DB=new Mysql($conn);
	// $ZT=ZipTrace::backend(getenv("ZIPKIN_SERVER"),'Attendees', '172.16.103.51:80');
	switch($R->proc()){
		case ":GET":
			$d=[]; foreach($DB->get_def($dbtable) as $i) { array_push($d,$i->COLUMN_NAME); }
			$R->json($d);
		break;
		case "attend:GET":
			$rs=$DB->set_table($dbtable)->select( $R->get() )->result();
			if($rs[0]=="error"){$R->json($rs[1],400);}
			else{$R->json($rs);}
		break;
		case "attend:PUT":
			if($R->post("meetid")!=NULL){
				$param=["meetid","personid"];  $R->fromPost($param);
			}else{  $param=$R->post();	}// multidata input
			$rs=$DB->set_table($dbtable)->insert( $param )->result();
			// $ZT->setTracer('Attendee put /attend')
			// 	->block('jsonplaceholder API', '104.31.87.157:80')
			// 	->makeSpan('put /attend/1');
			if($rs[0]=="error"){$R->json($rs[1],400);}
			else{$R->json($rs);}
			break;
		case "attend:PATCH":
			$param=["meetid","personid"]; $R->fromPost($param, false);
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
