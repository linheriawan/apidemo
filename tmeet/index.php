<?php
require_once __DIR__ ."/lib_d.php";
putenv("DBHOST=localhost");
putenv("DBNAME=pnmdb");
putenv("DBUSER=root");
putenv("DBPASS=pass@word1");
putenv("PERSON_URI=http://localhost/~heriawan/tperson");
putenv("ATTD_URI=http://localhost/~heriawan/tattend");

putenv("RABBIT_HOST=localhost");
putenv("RABBIT_PORT=5672");
putenv("RABBIT_USER=guest");
putenv("RABBIT_PASS=guest");

putenv("KAFKA_BROKER=localhost:9092");
putenv("KAFKA_TOPICS=test");

putenv("ZIPKIN_SERVER=http://127.0.0.1:9411");

$R=new Rest();
function randomdate(){
	$Y=date('Y'); $m=mt_rand(1, 12); $d=mt_rand(1, 31); $h=mt_rand(0, 23); $i=mt_rand(0, 59);$s=mt_rand(0, 59);
	$d=checkdate($m,$d,$Y)?$d:$d-1;
	$m=substr("0$m",-2); $d=substr("0$d",-2); $h=substr("0$h",-2); $i=substr("0$i",-2); $s=substr("0$s",-2);
	return "$Y-$m-$d $h:$i:$s";
}
function rand_ids(){
	$r=[];
	for($x=0; $x<mt_rand(3,5);$x++){ array_push($r,mt_rand(1,10000));}
	return $r;
}
try{
	$dbtable="meets";
	$PERSON=getenv("PERSON_URI");
	$ATTD=getenv("ATTD_URI");

	$conn=(object)[
		"host"=> getenv("DBHOST"),
		"name"=> getenv("DBNAME"),
		"user"=> getenv("DBUSER"),
		"pass"=> getenv("DBPASS"),
		"port"=> getenv("DBPORT")?getenv("DBPORT"):3306];
	$DB=new Mysql($conn);

	// $ZT=ZipTrace::backend(getenv("ZIPKIN_SERVER"),'Meet', '172.16.103.51:80');
	switch($R->proc()){
		case ":GET":
			$d=[]; foreach($DB->get_def($dbtable) as $i) { array_push($d,$i->COLUMN_NAME); }
			$R->json($d);
		break;
		case "meet:GET":
			$keys=["id"]; $R->fromGet($keys);
			$rs=$DB->set_table($dbtable)->select( $keys )->result();
			if($rs[0]=="error"){$R->json($rs[1],400);}
			else{
				// $ZT->setTracer('meet get /meet');
				
				$fattd=FETT::to( "$ATTD/attend?meetid=".$keys["id"] )
					// ->header($ZT->hdr)
					->get();
				// $ZT->block('Attend API', '104.31.87.157:80')
				// 	->makeSpan("get /meet?id=".$keys["id"]);
				
				$attd=[];
				// vdum($fattd);die();
				foreach($fattd->body as $a){ array_push($attd,$a->personid); }
				$attd=implode(",",$attd);
				
				$fdata=FETT::to( "$PERSON/person?id in ($attd)" )->header($ZT->hdr)->get();
				// $ZT->block('Person API', '104.31.87.157:80')
				// 	->makeSpan("get /person?id in ($attd)");
				
				$R->json(["header"=>$rs[0],"attendees"=>$fdata->body]);
				
				// $ZT->end();
			}
		break;
		case "meets:GET":
			$p=$R->get();
			if(count($p)==1){
				$k=array_keys($p)[0];
				if($p[$k]==""){ $p=str_replace("_"," ",$k); }
			}
			$rs=$DB->set_table($dbtable)->select( $p )->result();
			if($rs[0]=="error"){$R->json($rs[1],400);}
			else{$R->json($rs);}
		break;
		case "meet:PUT":
			// init meet Data
			$param["title"]=$R->post("title");
			// Get Rand Attendees
			$rand_ids=implode(",",rand_ids());
			$fdata=FETT::to( "$PERSON/person?id in ($rand_ids)" )->get();
			$rand_attendes=$fdata->body;
			
			$param["date"]=randomdate();
			$param["star"]=$rand_attendes[mt_rand(0,count($rand_attendes)-1)]->id;
			$param["attendee"]=count($rand_attendes);
			// create meet
			$rs=$DB->set_table($dbtable)->insert( $param )->result();
			if($rs[0]=="error"){$R->json($rs[1],400);}
			else{
				$drs="";
				if(ctype_digit($rs)){
					// insert attendees
					$data=[];
					foreach($rand_attendes as $p){
						array_push($data,["meetid"=>$rs,"personid"=>$p->id]);
					}
					/* by Rabbit*/
					// Rabbit_pubs::pub(getenv("RABBIT_HOST"), getenv("RABBIT_PORT"), getenv("RABBIT_USER"), getenv("RABBIT_PASS"))
        			// 	->exchange('hellow')->key("setattend",'topic')->send($data);

					/* by KAFKA */
					RDKafka_pubs::broker(getenv("KAFKA_BROKER"))->topic(getenv("KAFKA_TOPICS"))
						->key('setattend')->send(json_encode($data));
					
					/* GoodOld CURL */
					// $fattd=FETT::to( "$ATTD/attend" )->put($data);
					// $drs=$fattd->body;
				}
				$R->json(["header"=>$rs,"detail"=>$drs]);
			}
			break;
		case "meet:PATCH":
			$param=["title","date","inluck"]; $R->fromPost($param, false);
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
	
}catch(Exception $e){ $R->text( $e->getMessage(), 500 ); }
?>
