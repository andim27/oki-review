<?php

return [
    [
        'name' => 'cmfcell',
        'host' => 'cmfcell.com.ua',
        'api_url' =>'/sections/service/xmlpost/v1/default.aspx',
        'protocol_type' => [
            'name'=>'socket','options' => []
        ],
        'login' =>'',
        'psw' =>''
    ],
    [
        'name' =>'softline',
        'host' =>'',
        'api_url' =>'',
        'protocol_type' =>[
            'name'=>'soap','options'=>[]
        ],
        'login' =>'',
        'psw' =>''
    ],
    [
        'name' =>'smsc',
        'host' =>'smsc.ru',
        'api_url' =>'https://smsc.ru/sys/soap.php?wsdl',
        'protocol_type' =>[
            'name'=>'soap','options'=>[]
        ],
        'login' =>'',
        'psw' =>''
    ],
    [
        'name' =>'1000sms',
        'host' =>'1000sms.ru',
        'api_url' =>'',
        'protocol_type' =>[
            'name'=>'json','options'=>[]
        ],
        'login' =>'',
        'psw' =>''
    ],
    [
        'name' =>'sms-fly',
        'host' =>'sms-fly.com',
        'api_url' =>'http://sms-fly.com/api/api.php',
        'protocol_type' =>[
            'name'=>'xml','options'=>[]
        ],
        'login' =>'',
        'psw' =>''
    ],
    [
        'name' =>'mobak',
        'host' =>'',
        'api_url' =>'',
        'protocol_type' =>[
            'name'=>'json','options'=>[]
        ],
        'login' =>'',
        'psw' =>''
    ],
    [
        'name' =>'esputnik',
        'host' =>'esputnik.com',
        'api_url' =>'https://esputnik.com/api/v1/message/sms',
        'protocol_type' =>[
            'name'=>'json','options'=>[]
        ],
        'login' =>'',
        'psw' =>''
    ],
    [
        'name' =>'devinotele',
        'host' =>'',
        'api_url' =>'https://viber.devinotele.com:444/send',
        'protocol_type' =>[
            'name'=>'json',
            'options'=>[
                "messages" => [
                    [
                        "type" => "viber",
                        "subject" => '',
                        "priority" => "high",
                        "validityPeriodSec" => 900,
                        "comment" => "comment",
                        "contentType" => "text",
                        "content" => [
                            "text" => "Здравствуйте! НПФ «ГАЗФОНД пенсионные накопления» признателен за Ваше доверие. Сообщаем, что при переходе в другой Фонд, Вами будет потерян инвестиционный доход. Перейдите по ссылке http://sohrani.gazfond-pn.ru/  и сохраните накопления в полном объеме."
                        ],
                        "address" => '',
                        "smsText" => '',
                        "smsSrcAddress" => '',
                        "smsValidityPeriodSec" => 3600
                    ]
            ]
        ],
        'login' =>'',
        'psw' =>''
       ]//--protocol_type
    ],
    [
        'name' =>'kcell',
        'host' =>'',
        'api_url' =>'',
        'protocol_type' =>[
            'name'=>'json',
            'options'=>[
                "client_message_id" => time(),
                "sender" => '',//--sender_name
                "recipient" => '',//--phone
                "message_text" => '',
                "time_bounds" => "ad99"
            ]
        ],
        'login' =>'',
        'psw' =>''
    ],
    [
        'name' =>'stream-telecom',
        'host' =>'',
        'api_url' =>'http://gateway.api.sc/get/',
        'protocol_type' =>[
            'name'=>'get',
            'options'=>[
                'get_param'=>[
                    'user' => '',//--login
                    'pwd' => '',
                    'sadr' => '',//--sender_name
                    'dadr' => '',//--phone
                    'text' => ''//--urlencode($text)
                ]
            ]
        ],
        'login' =>'',
        'psw' =>''
    ],
];
