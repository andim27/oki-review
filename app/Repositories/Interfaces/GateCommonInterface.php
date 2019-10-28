<?php

namespace App\Repositories\Interfaces;


interface GateCommonInterface
{

    public function initGate();

    public function sendSms(String $gate_name);

    public function checkStatus();

    public function getResult();
}

interface StatusInterface
{
    const STATUS_NONE = -2;
    const STATUS_FAIL = -1;
    const STATUS_OK = 1;
    const STATUS_SENDED = 2;
    const STATUS_IN_QUEUE = 3;
    const STATUS_IN_MODILE_NET = 4;
    const STATUS_MESSAGE_ON_SERVER = 5;
}
