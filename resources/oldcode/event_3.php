#!/usr/bin/php
<?php
$path='/var/www/html/old/';
include("sms_driver.php");
//require_once("data_func.inc");
require_once($path."common/classes/Event.php");
set_time_limit(0);
$gate_id=intval($argv[1]);
require_once($path."common/classes/Db.php");
$db = new Db('oki-toki.ua');
function sanitize_string($str) {
    return Db::sanitize_string($str);
}
function prepare_message($mes, $params, $cid, $schema){
    global $db;
    $pars=explode(",", $params);
    $msg=$mes;
    for ($i=0; $i<=count($pars); $i++){
        if (strlen($pars[$i])>0) $msg=str_replace("param".($i+1), $pars[$i], $msg);
    }
    $conn = $db->do_connect("postgres", "crm");
    $contact_fields=array("contact_name"=>"##Имя", "mob_phones"=>"##МТ", "work_phones"=>"##РТ", "home_phones"=>"##ДТ", "email"=>"##E-mail",
    "companie"=>"##Компания", "address"=>"##Адрес", "_comment"=>"##Комментарий");
    $query="select * from ".$schema.".user_fields";
    $result=pg_query($conn, $query);
    for ($i=0; $i<pg_num_rows($result); $i++){
        $row=pg_fetch_array($result, $i);
        $contact_fields['f'.$row['id']]="##".stripslashes($row['field_name']);
    }
    $query="select * from ".$schema.".contacts where id=".intval($cid);
    $result=pg_query($conn, $query);
    $row=pg_fetch_array($result, 0);
    foreach ($contact_fields as $fld=>$name){
        $msg=str_ireplace($name, $row[$fld], $msg);
    }
    pg_close($conn);
    return $msg;
}
function store_log($conn, $message, $phone, $cid, $comp){
    $query="insert into ".$comp.".storage_data (message, notify_to, contact_id, dtime, event) values('".pg_escape_string($message)."', '".$phone."', ".intval($cid).", 
    now(), 'sms')";
    pg_query($conn, $query);
}
$conn = $db->do_connect("postgres", "sms");
//$store_conn = $db->do_connect("postgres", "store");
/*$query="select nspname from pg_namespace where nspname like 'comp%'";
$result = pg_query($conn, $query);
for ($i=0; $i<pg_num_rows($result); $i++){
    $row=pg_fetch_array($result, $i);
    $query1="select * from ".$row['nspname'].".camps where enabled=1";
    $result1 = pg_query($conn, $query1);
    for ($j=0; $j<pg_num_rows($result1); $j++){
        $row1=pg_fetch_array($result1, $j);
        $agate_id=$row1['gate_id'];
        $query2="select * from ".sanitize_string($row['nspname']).".contacts where sent=0 and camp_id=".intval($row1['id']);
        $result2=pg_query($conn, $query2);
        for ($q=0; $q<pg_num_rows($result2); $q++ ){
            $row2=pg_fetch_array($result2, $q);
            $query3="insert into gate_queue values (default, ".intval($row['camp_id']).", ".intval($row2['id']).", '".sanitize_string($row['nspname'])."', null, ".intval($agate_id).", null, null, ".(intval($row2['contact_id'])>0?intval($row2['contact_id']):"null").")";
            $res3=pg_query($conn, $query3);
        }
    }
}
*/

$query="insert into working_gates values (".intval($gate_id).", now())";
$result=pg_query($conn, $query);
$query="select * from gate_queue where sms_text is not null and gate_id=".intval($gate_id)." limit 25";
$result=pg_query($conn, $query);
for ($i=0; $i<pg_num_rows($result); $i++){
    $row=pg_fetch_array($result, $i);
    $event = new Event($row['schema_name'], 'oki-toki.ua', '/srv/www/htdocs/okitoki');
    $qp="select price from gate_link_to_company where company='".$row['schema_name']."' and gate_id=".intval($gate_id);
    $rp=pg_query($conn, $qp);
    if (pg_num_rows($rp)>0){
        $rw=pg_fetch_array($rp, 0);
        $cprice=floatval($rw['price']);
    }
    else $cprice=0;
    $query1="select * from gates
    join (select id as tid, type_abbr from gate_types) a on (gate_type=a.tid)
    where id=".intval($gate_id)." and enabled=1 and has_sms>0";
    $result1=pg_query($conn, $query1);
    if (pg_num_rows($result1)>0){
        $row1=pg_fetch_array($result1, 0);
        $pprice=floatval($row1['price']);
        $id='';
        if ($row1['type_abbr']=='turbosms.ua')
            $id=send_sms_turbosms($row1['gate_login'], $row1['gate_password'], $row1['signation'], $row['sms_text'], trim($row['phone']));
        if ($row1['type_abbr']=='stream_telecom')
            $id=stream_telecom_sms($row1['gate_login'], $row1['gate_password'], $row1['signation'], $row['sms_text'], trim($row['phone']));
        if ($row1['type_abbr']=='smsc.ru')
            $id=send_sms_smsc($row1['gate_login'], $row1['gate_password'], $row1['signation'], $row['sms_text'], trim($row['phone']));
        if ($row1['type_abbr']=='mainsms')
            $id=send_sms_mainsms($row1['gate_login'], $row1['gate_password'], $row1['signation'], $row['sms_text'], trim($row['phone']));
        if ($row1['type_abbr']=='mobak')
            $id=send_sms_mobak($row1['gate_login'], $row1['gate_password'], $row1['signation'], $row['sms_text'], trim($row['phone']));
        if ($row1['type_abbr']=='1000sms')
            $id=send_sms_1000sms($row1['gate_login'], $row1['gate_password'], $row1['signation'], $row['sms_text'], trim($row['phone']));
        if ($row1['type_abbr']=='esputnik')
            $id=send_sms_esputnik($row1['gate_login'], $row1['gate_password'], $row1['signation'], $row['sms_text'], trim($row['phone']));
        if ($row1['type_abbr']=='devinotele_viber_sms')
            $id=devinotele_viber_sms($row1['gate_login'], $row1['gate_password'], $row1['signation'], $row['sms_text'], trim($row['phone']));
        if ($row1['type_abbr']=='devinotele_sms')
            $id=devinotele_sms($row1['gate_login'], $row1['gate_password'], $row1['signation'], $row['sms_text'], trim($row['phone']));
        if ($row1['type_abbr']=='softline')
            $id=send_sms_softline($row1['gate_login'], $row1['gate_password'], $row1['signation'], $row['sms_text'], trim($row['phone']));
        if ($row1['type_abbr']=='atom')
            $id=send_sms_atom($row1['gate_login'], $row1['gate_password'], $row1['signation'], $row['sms_text'], trim($row['phone']));
	    if ($row1['type_abbr']=='kcell')
            $id=kcell_sms($row1['gate_login'], $row1['gate_password'], $row1['signation'], $row['sms_text'], trim($row['phone']));
        if ($row1['type_abbr']=='sms_fly'){
            $id=send_sms_fly($row1['gate_login'], $row1['gate_password'], $row1['signation'], $row['sms_text'], trim($row['phone']));
	}
        if ($row1['type_abbr']=='cmfcell.com.ua')
            $id=send_sms_cmfcell($row1['gate_login'], $row1['gate_password'], $row1['signation'], $row['sms_text'], trim($row['phone']));
        if ($row1['type_abbr']=='okitoki')
            $id=send_sms_okitoki($row['phone'], $row['sms_text'], $row1['gsm_id']);
        if (strlen($id)>0){
           // if ($row1['type_abbr']=='okitoki'){
           //     $query = "update gate_queue set notified=1 where id=".$row['id'];
           //     $res5=pg_query($conn, $query);
          //  }
           // else{
                $status = "";
                if (strstr($id, "|")){
                    $tmp = explode("|", $id);
                    $id = $tmp[1];
                    $status = $tmp[0];                    
                }
                $query_log ="insert into sms_log values (default, 0, 0, '".sanitize_string($row['sms_text'])."',
                    ".intval($gate_id).", 0, now(), ".floatval($pprice).", ".floatval($cprice).", '".sanitize_string($row['phone'])."', '".sanitize_string($row['schema_name'])."', ".intval($row['crm_id']).", '".$id."', ".intval($row['user_id']).", '".$status."') returning id;";
                $res_log=pg_query($conn, $query_log);
                $row_log=pg_fetch_array($res_log, 0);
                $lid=$row_log['id'];
                if ($id=='true_1'){
            	    $query = "update gate_queue set notified=1 where id=".$row['id'];
            	    $res5=pg_query($conn, $query);
                }
                else{
            	    $q2="delete from gate_queue where id=".$row['id'];
            	    $r2=pg_query($conn, $q2);
                }
                //store_log($store_conn, $row['sms_text'], $row['phone'], intval($row['contact_id']), $row['schema_name']);
           // }
            sleep(1);
        }
    }
}

$query="delete from working_gates where gate_id=".intval($gate_id);
$result=pg_query($conn, $query);
pg_close($conn);
?>
