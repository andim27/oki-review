<?php
require_once "mobak/src/Mobak.php";
use mobak\Mobak;
function post_request( $url, $postdata, $port=80,  $useragent = 'PHPPost/1.0')
{
    $url_info = parse_url( $url );
    $senddata = '';
    /* post data must be an array */
    if( !is_array( $postdata ) )
        return false;

    /* open in secure socket layer or not */
    if( $url_info['scheme'] == 'https' )
        $fp = fsockopen( 'ssl://' . $url_info['host'], 443, $errno, $errstr, 30);
    else
        $fp = fsockopen( $url_info['host'], $port, $errno, $errstr, 30);

    /* make sure opened ok */
    if( !$fp )
        return false;

    /* loop postdata and convert it */
    foreach( $postdata as $name => $value )
    {
        /* add & if it isn't the first */
        if( !empty( $senddata ) )
            $senddata .= '&';

        if ($name=='Send') $senddata .= urlencode( $name ) . '=' .  $value ;
        else if ($name=='HEXTEXT') $senddata .= urlencode( $name ) . '=' . $value ;
        else $senddata .= urlencode( $name ) . '=' . urlencode( $value );
    }
    /* HTTP POST headers */
    $out = 'POST ' . (isset($url_info['path'])?$url_info['path']:'/') .
        (isset($url_info['query'])?'?' . $url_info['query']:'') . ' HTTP/1.0' . "\r\n";
    $out .= 'Host: ' . $url_info['host'] . "\r\n";
    $out .= 'User-Agent: ' .  $useragent . "\r\n";
    $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
    $out .= 'Content-Length: ' . strlen( $senddata ) . "\r\n";
    $out .= 'Connection: Close' . "\r\n\r\n";
    $out .= $senddata;
    echo $out;
    fwrite($fp, $out);
    /* read any response */
    for( ;!feof( $fp ); )
        $contents .= fgets($fp, 128);

    /* seperate content and headers */
    list($headers, $content) = explode( "\r\n\r\n", $contents, 2 );

    return $content;
}
function str_split_unicode($str, $l = 0) 
{
    return preg_split('/(.{'.$l.'})/us', $str, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
}
function send_sms_portech_1($ip, $phone, $message, $encode, $port=80){
    $post=array();
    $post["Encode"]=$encode;
    $post["DEST"]=$phone;
    $post["HEXTEXT"]=$message;
    $post["Send"]="Send Now";
    if (intval($port)>80) post_request("http://".$ip."/smsSendNow.cgi", $post, $port);
    else post_request("http://".$ip."/smsSendNow.cgi", $post);

}
function send_sms_portech_4($ip, $phone, $message, $encode, $port=80, $line=1){
    $post=array();
    $post1=array();
    if ($line>2) $post1['SlaveNum']=1;
    else $post1['SlaveNum']=0;
    if (intval($port)>80) post_request("http://".$ip."/smsAgentSlave.cgi", $post1, $port);
    else post_request("http://".$ip."/smsAgentSlave.cgi", $post1);
    sleep(1);
    $post["Encode"]=$encode;
    if ($line==1 or $line==3) $post["bMobile"]=0;
    if ($line==2 or $line==4) $post["bMobile"]=1;
    $post["DEST"]=$phone;
    $post["HEXTEXT"]=$message;
    $post["Send"]="Send Now";
    if (intval($port)>80) post_request("http://".$ip."/smsSendNow.cgi", $post, $port);
    else post_request("http://".$ip."/smsSendNow.cgi", $post);

}
function send_sms_okitoki($phone, $message, $gsm_id){
    global $db;
    $lvp_conn = $db->do_connect('postgres', 'lvp');
    $query = "select session_id, pid, server_pid, post_login from posts_routes 
    left join (select post_id as ppid, server_pid from asterisk_dongles) a on (a.ppid=id)
    where id=".intval($gsm_id);
    $result=pg_query($lvp_conn, $query);
    if (pg_num_rows($result)>0){
        $row=pg_fetch_array($result, 0);
        if (intval($row['session_id'])>0 and intval($row['pid'])>1){
            $query = "select * from ps_pymess('<root><type>lira</type><message>send_sms|".$row['session_id']."#</message></root>')";
            $result=pg_query($lvp_conn, $query);
            return 'true_1';
        }
        else if (intval($row['pid'])==1){
            if (strlen($message)>70){
                $tmp  = str_split_unicode($message, 70);
                foreach ($tmp as $key => $value) {
                    $msg = '<root><type>gsm_command</type><pid>'.str_replace("<", "", str_replace(">", "", $row['server_pid'])).'</pid><gsm_data>{"command_type":"ami","command":"dongle sms '.$row['post_login'].' +'.$phone.' '.str_replace("'", "", $value).'","device":"'.$row['post_login'].'"}</gsm_data></root>';
                    $query = "select * from ps_pymess('".$msg."')";
                    $result=pg_query($lvp_conn, $query);
                    sleep(1);
                }
            }
            else{
                $msg = '<root><type>gsm_command</type><pid>'.str_replace("<", "", str_replace(">", "", $row['server_pid'])).'</pid><gsm_data>{"command_type":"ami","command":"dongle sms '.$row['post_login'].' +'.$phone.' '.str_replace("'", "", $message).'","device":"'.$row['post_login'].'"}</gsm_data></root>';
                $query = "select * from ps_pymess('".$msg."')";
                $result=pg_query($lvp_conn, $query);
            }
            return 'true';
        }
        else
            return '';
    }
    else
        return '';
}
function send_sms_turbosms($login, $pass, $sender_name, $text, $phone){
    $client = new SoapClient ('http://turbosms.in.ua/api/wsdl.html');
    $id='empty answer' ;
    $auth = Array (
        'login' => $login,
        'password' => $pass
    );
    $result = $client->Auth ($auth);
    if (strstr($result->AuthResult, 'успешно' )){
        $result = $client->GetCreditBalance();
        if (intval($result->GetCreditBalanceResult)>0){
            $sms = Array (
                'sender' => $sender_name,
                'destination' => '+'.trim($phone),
                'text' => $text
            );
            $result = $client->SendSMS ($sms);
            if (is_array($result->SendSMSResult->ResultArray))
        	$id=$result->SendSMSResult->ResultArray[0]."|".$result->SendSMSResult->ResultArray[1];
            else
        	$id=$result->SendSMSResult->ResultArray;
        }
        else
            $id="Исчерпан лимит смс";
    }
    else
        $id=$result->AuthResult;
  return $id;
}

function send_sms_atom($login, $pass, $sender_name, $text, $phone){
    $src = '<?xml version="1.0" encoding="UTF-8"?>    
    <SMS> 
    <operations>  
    <operation>SEND</operation> 
    </operations> 
    <authentification>    
    <username>'.$login.'</username>   
    <password>'.$pass.'</password>   
    </authentification>   
    <message> 
    <sender>'.$sender_name.'</sender>    
    <text>'.$text.'</text>   
    </message>    
    <numbers> 
    <number>'.$phone.'</number> 
    </numbers>    
    </SMS>';  
    $id='empty answer' ;
    $Curl = curl_init();    
    $CurlOptions = array(   
        CURLOPT_URL=>'http://atompark.com/members/sms/xml.php',  
        CURLOPT_FOLLOWLOCATION=>false,   
        CURLOPT_POST=>true,  
        CURLOPT_HEADER=>false,   
        CURLOPT_RETURNTRANSFER=>true,    
        CURLOPT_CONNECTTIMEOUT=>15,  
        CURLOPT_TIMEOUT=>100,    
        CURLOPT_POSTFIELDS=>array('XML'=>$src),   
    );  
    curl_setopt_array($Curl, $CurlOptions); 
    if(false === ($Result = curl_exec($Curl))) {    
        throw new Exception('Http request failed'); 
    }   
    curl_close($Curl);  
         
   $id= $Result;   
   return $id;
}

$res=array();
function cmf_str($parser, $str) {
        global $res;
        if (strlen(trim($str))>0)
            $res[$res['current']]=$str;
}
function cmf_start_element($parser, $name, $attrs) {
    global $res;
    $res[$name]=$attrs['TYPE'];
    $res['current']=$name;
}

function cmf_end_element($parser, $name) {};

function send_sms_cmfcell($login, $pass, $sender_name, $text, $phone){
    global $res;
    $post_string = '<packet version="1.0"><auth username="'.$login.'" password="'.$pass.'"/><command name="SendMessage"><message type="UnicodeSms"><from>'.$sender_name.'</from><sendDate></sendDate><data>'.$text.'</data><recipients><recipient address="+'.$phone.'"></recipient></recipients></message></command></packet>';
    $fp = fsockopen('smsc.cmfcell.com.ua', 80, $err_num, $err_msg, 30) or die("Socket-openfailed--error: ".$err_num." ".$err_msg);
    fputs($fp, "POST /sections/service/xmlpost/v1/default.aspx HTTP/1.0\r\n");
    fputs($fp, "Host: oki-toki.ua\r\n");
    fputs($fp, "Content-type: text/xml \r\n");
    fputs($fp, "Content-length: ".strlen($post_string)." \r\n");
    fputs($fp, "Content-transfer-encoding: text \r\n");
    fputs($fp, "Connection: close\r\n\r\n");
    fputs($fp, $post_string);
    while(!feof($fp)) {
    $http_response .= fgets($fp, 128);
    }
    fclose($fp);
    list($headers, $content) = explode( "\r\n\r\n", $http_response, 2 );
    $XMLparser = xml_parser_create();
    xml_set_element_handler($XMLparser, 'cmf_start_element', 'cmf_end_element');
    xml_set_character_data_handler($XMLparser, 'cmf_str');
    xml_parse($XMLparser, $content);
    xml_parser_free($XMLparser);
   $id="RESULT - ".$res['RESULT']."; MessageID - ".$res['MESSAGEID'];
  return $id;
}
function send_sms_softline($login, $pass, $sender_name, $text, $phone){
    $client = new SoapClient ('WSI.xml');
    $id='empty answer' ;
    $sms = array(
        'alfaName' => $sender_name,
        'contacts' => array('phone'=>$phone, 'prop'=>''),
        'template' => $text,
        'user' => array('password' => $pass, 'userName' => $login)
    );
    $result = $client->sendMessages($sms);
    $id = 'accepted='.$result->return->accepted."; ID=".$result->return->notificationID;
    //echo $id;// public 'notificationID' => int 1289527 public 'accepted' => int 1
    return $id;
}
function send_sms_smsc($login, $pass, $sender_name, $text, $phone){
    $client = new SoapClient('https://smsc.ru/sys/soap.php?wsdl');
    $data = array(
        'login'=>$login,
        'psw'=>$pass,
        'phones' => $phone,
        'mes' => $text,
        'id'=>'',
        'time'=>0
    );
    if (strlen($sender_name)>0)
        $data["sender"] = $sender_name;
    $ret = $client->send_sms($data);
    $id='error' ;
    if ($ret->sendresult->error){
        $id='error № '.$ret->sendresult->error;
    }
    if (isset($ret->sendresult->id) and intval($ret->sendresult->id)>0)
        $id = $ret->sendresult->id;
    return $id;
}
function send_sms_mainsms($login, $pass, $sender_name, $text, $phone){
    require_once 'mainsms.class.php' ;
    $api = new MainSMS ( $login , $pass, false, false);
    $api->sendSMS ( $phone , $text , $sender_name);
    $response = $api->getResponse ();
    $id=$response["status"].".".$response["message"];
    return $id;
}
function send_sms_1000sms($login, $pass, $sender_name, $text, $phone){
    require_once "1000sms_ru.php";
    $res = smsapi_push_msg_nologin($login, $pass, $phone, $text);
    if (!is_null($res)){
        if (intval($res[0])==0)
            return "success";
        else
            return "error ".$res[0];
    }
    else{
        return 'error';
    }
}
function send_sms_fly($login, $pass, $sender_name, $text, $phone){
    $xml = '<?xml version="1.0" encoding="utf-8"?>
        <request>
            <operation>SENDSMS</operation>
            <message start_time=" AUTO " end_time=" AUTO " rate="120" lifetime="1" desc="" source="'.$sender_name.'">
                <body>'.$text.'</body>
                <recipient>'.$phone.'</recipient>
            </message>
        </request>';
    echo $xml;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERPWD , $login.':'.$pass);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_URL, 'http://sms-fly.com/api/api.php');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml", "Accept: text/xml"));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    $res = curl_exec($ch);
    curl_close($ch);
    $id = $res;
    return $id;
}

function send_sms_mobak($login, $pass, $sender_name, $text, $phone){
    $smsSender = new Mobak([
        'login' => $login,
        'password' => $pass,
    ]);

    $result = $smsSender->send([
        'message' => $text,
        'sender' => $sender_name,
        'phone' => $phone
    ]);
    $s = $result->asArray();
    if (isset($s['information']["@attributes"]['code']) and intval($s['information']["@attributes"]['code'])==0){
        $id='success';
    }
    else{
        $id='error';
    }
    return $id;
}
function send_sms_esputnik($login, $pass, $sender_name, $text, $phone){
    $user=$login;
    $password=$pass;
    $send_sms_url = 'https://esputnik.com/api/v1/message/sms';

    $from = $sender_name;
    $number = $phone;
    $json_value = new stdClass();
    $json_value->text = $text;
    $json_value->from = $from;
    $json_value->phoneNumbers = array($number);

    $res = send_sp_request($send_sms_url, $json_value, $user, $password);
    $json  = json_decode($res, true);
    $id = $json['results']['status'];
    return $id;
}
function devinotele_viber_sms($login, $pass, $sender_name, $text, $phone){
    /*$data = [
        "Login" => $login,
        "Password" => $pass,
        "DestinationAddress" => $phone,
        "Data" => $text,
        "SourceAddress" => $sender_name

    ];
    $res = areq("https://integrationapi.net/rest/v2/Viber/SendWithResend", $data);

    return $res;*/
    $data = [
        "resendSms" => true,
        "messages" => [
            [
                "type" => "viber",
                "subject" => $sender_name,
                "priority" => "high",
                "validityPeriodSec" => 900,
                "comment" => "comment",
                "contentType" => "text",
                "content" => [
                    "text" => "Здравствуйте! НПФ «ГАЗФОНД пенсионные накопления» признателен за Ваше доверие. Сообщаем, что при переходе в другой Фонд, Вами будет потерян инвестиционный доход. Перейдите по ссылке http://sohrani.gazfond-pn.ru/  и сохраните накопления в полном объеме."
                ],
                "address" => $phone,
                "smsText" => $text,
                "smsSrcAddress" => $sender_name,
                "smsValidityPeriodSec" => 3600
            ]
        ]
    ];
    $res = devino_curl("https://viber.devinotele.com:444/send", $data, $login, $pass);
    $json = json_decode($res, true);
    return $json['status'];
}

function kcell_sms($login, $pass, $sender_name, $text, $phone){
    $data = [
        "client_message_id" => time(),
        "sender" => $sender_name,
        "recipient" => $phone,
        "message_text" => $text,
	"time_bounds" => "ad99"
    ];
    $res = kcell_curl($data, $login, $pass);
    return $res;
}

function stream_telecom_sms($login, $pass, $sender_name, $text, $phone){
    $url = "http://gateway.api.sc/get/?user=".$login."&pwd=".$pass."&sadr=".$sender_name."&dadr=".$phone."&text=".urlencode($text);
    $answer = get_request($url);
    return $answer;
}
function devinotele_sms($login, $pass, $sender_name, $text, $phone){

    $data = [
        "Login" => $login,
        "Password" => $pass,
        "DestinationAddress" => $phone,
        "SourceAddress" => $sender_name,
        "Data" => $text,
        "Validity" => 0

    ];

    $res = devino_sms_curl("https://integrationapi.net/rest/v2/Sms/Send", $data);
    $json = json_decode($res, true);
    return $json[0];
}

function kcell_curl($data, $login, $pass) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.kcell.kz/app/smsgw/rest/v2/messages");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json;charset=utf-8'));
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, $login . ":" . $pass);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    /*curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data)))
    );*/
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function devino_sms_curl($url, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);

    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function devino_curl($url, $data, $login, $pass) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, $login . ":" . $pass);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data)))
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function send_sp_request($url, $json_value, $user, $password) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json_value));
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_USERPWD, $user.':'.$password);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}
function areq($url, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
function get_request( $url){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $data = curl_exec($curl);
    curl_close($curl);
    return $data;
}
?>
