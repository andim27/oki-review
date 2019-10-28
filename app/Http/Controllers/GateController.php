<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Jobs\ProcessGate;

class GateController extends Controller
{
    //
    private $res = ['status'=>200,'message'=>'done!'];
    private $gate_arr = [];
    public function sendSmsOne($gate_name = '')
    {
        if (empty($this->gate_arr)) {
            $this->initGates();
        }

        $gate_one = Arr::where($this->gate_arr, function ($value, $key) use ($gate_name) {
            if ($value['name'] == $gate_name) {
                return $value;
            }

            return null;
        });
        if (empty($gate_one)) {
            $this->res['message'] = 'Empty gate name';
        } else {//--ok
            //--create job by gate_name
            $gateJob = new ProcessGate();
            $gateJob->setGateName($gate_name);
            dispatch($gateJob);
        }
        return $this->res;
    }
    public function sendSmsAll()
    {
        $this->initGates();
        foreach ($this->gate_arr as $gate_item) {
            $this->sendSmsOne($gate_item['name']);
        }
        return $this->res;
    }
    //----------------------------------
    private function initGates()
    {
        $this->gate_arr = config('gates');
    }
}
