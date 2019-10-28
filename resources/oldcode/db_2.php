#!/usr/bin/php
<?php
$path='/var/www/html/old/';
require_once($path."common/classes/Db.php");
$db = new Db('oki-toki.ua');
$conn = $db->do_connect('postgres', 'sms');
$query="select distinct(gate_id) from gate_queue 
where notified=0 and gate_id not in (select gate_id from working_gates)";
$result=pg_query($conn, $query);
for ($i=0; $i<pg_num_rows($result); $i++ ){
        $row=pg_fetch_array($result, $i);
        system("sudo -u root {$path}sms/gate_proccess.php ".intval($row['gate_id'])." & >/dev/null &");
        echo "sms/gate_proccess.php ".intval($row['gate_id'])." & >/dev/null &";
}
$query="select * from working_gates where (now()-'5 minute'::interval)>dtime";
$result=pg_query($conn, $query);
for ($i=0; $i<pg_num_rows($result); $i++ ){
    $row=pg_fetch_array($result, $i);
    $query="delete from working_gates where gate_id=".intval($row['gate_id']);
    $res=pg_query($conn, $query);
}
$query = "select * from sms_tmp 
	join (select id as gid, gate_login, gate_password from gates) a on (a.gid=gate_id)
	where tries<4 and check_time<now() order by gate_id asc limit 30";
$result = pg_query($conn, $query);
$current_gate = 0;
$has_auth = false;
if (pg_num_rows($result)>0){
	for ($i=0; $i<pg_num_rows($result); $i++ ){
    	$row=pg_fetch_array($result, $i);
    	if ($current_gate!=$row['gate_id']){
    		$client = new SoapClient ('http://turbosms.in.ua/api/wsdl.html');
    		$auth = Array (
		        'login' => $row['gate_login'],
		        'password' => $row['gate_password']
		    );
		    $res = $client->Auth ($auth);
		    if (strstr($res->AuthResult, 'успешно' ))
		    	$has_auth = true;
		   	else
		   		$has_auth = false;
    	}
    	if ($has_auth){
    		$res = $client->GetMessageStatus(Array ("MessageId" => $row['msg_id']));
    		$query = "update sms_log set sms_status='".$res->GetMessageStatusResult."' where id=".$row['log_id'];
    		pg_query($conn, $query);
    		if (intval($row['tries'])+1>3){
    			$query = "delete from sms_tmp where id=".$row['id'];
    			pg_query($conn, $query);
    		}
    		else{
    			$query = "delete from sms_tmp where id=".$row['id'];
    			$now = "now()+'18 hour'::interval";
    			if (intval($row['tries'])==0)
    				$now = "now()+'5 minute'::interval";
    			if (intval($row['tries'])==1)
    				$now = "now()+'1 hour'::interval";
    			if (intval($row['tries'])==2)
    				$now = "now()+'6 hour'::interval";
    			if (intval($row['tries'])==3)
    				$now = "now()+'18 hour'::interval";

    			if ($res->GetMessageStatusResult=="Отправлено"){
    				$query = "update sms_tmp set tries=tries+1, check_time=".$now." where id=".$row['id'];	
    			}
    			if ($res->GetMessageStatusResult=="В очереди"){
    				$query = "update sms_tmp set tries=tries+1, check_time=".$now." where id=".$row['id'];	
    			}
    			if ($res->GetMessageStatusResult=="Сообщение передано в мобильную сеть"){
    				$query = "update sms_tmp set tries=tries+1, check_time=".$now." where id=".$row['id'];	
    			}
    			if ($res->GetMessageStatusResult=="Сообщение доставлено на сервер"){
    				$query = "update sms_tmp set tries=tries+1, check_time=".$now." where id=".$row['id'];	
    			}
    			pg_query($conn, $query);	
    		}
    	}
    	else{
    		$query = "update sms_log set sms_status='Неудачная авторизация' where id=".$row['log_id'].";
    		delete from sms_tmp where id=".$row['id'];
    		pg_query($conn, $query);
    	}
    	$current_gate = $row['gate_id'];
    	sleep(1);
    }

}
pg_close($conn);
?>
