<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Repositories\GateRepository;

class ProcessGate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $gate;
    protected $gate_name;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GateRepository $gate)
    {
        $this->gate = $gate;

    }

    public function setGateName($gate_name)
    {
        $this->gate_name = $gate_name;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->gate->initGate();
        $this->gate->sendSms($this->gate_name);
    }
    /*
     * for call from artisan
     */
    public function processQueue()
    {
        $gate_arr = config('gates');
        foreach ($gate_arr as $gate_item) {
            $this->sendSmsOne($gate_item['name']);
        }
    }
}
