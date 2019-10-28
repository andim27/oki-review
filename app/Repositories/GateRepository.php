<?php namespace App\Repositories;

use \App\Repositories\Interfaces\GateCommonInterface;
use \App\Repositories\Interfaces\StatusInterfaceInterface;

class GateRepository implements GateCommonInterface, StatusInterfaceInterface
{

    protected $gate_models_arr;
    protected $gate_model;
    protected $gate_result;
    protected $gate_status = STATUS_NONE;

    public function initGate()
    {
        $this->gate_models_arr = config('gates');
    }

    public function sendSms($gate_name)
    {
        $gate_item = $this->getGateByName($gate_name);
        if ($gate_item) {
            $this->gate_model = new GateModel();
            $this->gate_model->initOptions($gate_item);
            $res = $this->gate_model->makeRequest();
            return $this->checkStatus($res);
        } else {
            return self::STATUS_FAIL;
        }
    }

    public function checkStatus($res)
    {
        // TODO: Implement checkStatus() method.
        return $res;
    }

    public function getResult()
    {
        // TODO: Implement getResult() method.
    }
    //----------------- private ------------------
    private function getGateByName($gate_name)
    {
        foreach ($this->gate_models_arr as $gate_item) {
            if ($gate_item['name'] == $gate_name) {
                return $gate_item;
            }
        }
        return null;
    }
}
