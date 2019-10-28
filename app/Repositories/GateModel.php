<?php
/**
 * Created by PhpStorm.
 * User: andrey
 * Date: 10/28/19
 * Time: 2:39 PM
 */

namespace App\Repositories;

use \App\Repositories\Interfaces\GateProtocolInterface;
use \GuzzleHttp;

class GateModel implements GateProtocolInterface
{
    private $gate_protocol_type;
    private $options;

    public function initOptions($option_item)
    {
        $this->options['api_url']       = $option_item['api_url'];
        $this->options['protocol_type'] = $option_item['protocol_type']['name'];
        $this->options['body_params']   = $option_item['protocol_type']['body_params'];
    }

    public function makeRequest()
    {
        $res = null;
        if ($this->options['protocol_type'] == self::PROTOCOL_GET) {
            $res = self::$this->makeRequestGet();
        }
        if ($this->options['protocol_type'] == self::PROTOCOL_POST) {
            $res = self::$this->makeRequestPOST();
        }
        if ($this->options['protocol_type'] == self::PROTOCOL_SOAP) {
            $res = self::$this->makeRequestSOAP();
        }
        return $res;
    }

    public function makeRequestPost()
    {
        try {
            $client = new GuzzleHttp\Client();
            $request = $client->post($this->options->api_url,  ['body'=>$$this->options['body_params']]);
            $response = $request->send();
        } catch (\Exception $err) {
           return null;//-- 'error'
        }

        return $response;
    }
    public function makeRequestGet()
    {
        try {
            $client = new GuzzleHttp\Client();
            $request = $client->get($this->options->api_url,  ['body'=>$$this->options['body_params']]);
            $response = $request->send();
        } catch (\Exception $err) {
            return null;//-- 'error'
        }

        return $response;
    }
    public function makeRequestSoap()
    {
        try {
            $client = new GuzzleHttp\Client();
            $response = null;

            // -- SOAP init;


            //$request = $client->post($this->options->api_url,  ['body'=>$$this->options['body_params']]);
            //$response = $request->send();
        } catch (\Exception $err) {
            return null;//-- 'error'
        }

        return $response;
    }
}
