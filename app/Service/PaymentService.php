<?php

namespace App\Service;


use App\Models\Payment;

class PaymentService
{
    public $method;
    protected $class;
    protected $config;
    protected $payment;

    public function __construct($method, $id = NULL, $uuid = NULL)
    {
        $this->method = $method;
        $this->class = '\\App\\Payments\\' . $this->method;
        if (!class_exists($this->class)) abort(500, 'gate is not found');
        if ($id) $payment = Payment::find($id)->toArray();
        if ($uuid) $payment = Payment::where('uuid', $uuid)->first()->toArray();
        $this->config = [];
        if (isset($payment)) {
            $this->config = $payment['config'];
            $this->config['enable'] = $payment['enable'];
            $this->config['id'] = $payment['id'];
            $this->config['uuid'] = $payment['uuid'];
            $this->config['notify_domain'] = $payment['notify_domain'];
        };
        $this->payment = new $this->class($this->config);
    }

    /**
     * 回调
     * Payment callback
     * @param array $params
     * @return array
     */
    public function notify($params)
    {
        if (!$this->config['enable']) abort(500, 'gate is not enable');
        return $this->payment->notify($params);
    }


    /**
     * Payment
     * @param array $order 订单信息
     * @return array 支付信息
     */
    public function pay($order,$return_url="")
    {
        // 对于余额支付，需要特殊处理
        if ($this->method === 'BalancePay') {
            return $this->payment->pay([
                'trade_no' => $order['trade_no'],
                'order_sn' => $order['order_sn'],
                'total_amount' => $order['total_amount'],
                'user_id' => $order['user_id'] ?? 0,
            ]);
        }
        
        // custom notify domain name
        $notifyUrl = url("/pay/notify/{$this->method}/{$this->config['uuid']}");
        if ($this->config['notify_domain']) {
            $parseUrl = parse_url($notifyUrl);
            $notifyUrl = $this->config['notify_domain'] . $parseUrl['path'];
        }

        return $this->payment->pay([
            'notify_url' => $notifyUrl,
            'return_url' => $return_url ?: url("bill",[$order['trade_no']]) ,
            'trade_no' => $order['trade_no'],
            'total_amount' => $order['total_amount'],
            'user_id' => 0,//$order['user_id'],
//            'stripe_token' => $order['stripe_token']
        ]);
    }

    public function recharge($order,$return_url=""){
        // custom notify domain name
        $notifyUrl = url("/pay/recharge/notify/{$this->method}/{$this->config['uuid']}");
        if ($this->config['notify_domain']) {
            $parseUrl = parse_url($notifyUrl);
            $notifyUrl = $this->config['notify_domain'] . $parseUrl['path'];
        }

        return $this->payment->pay([
            'notify_url' => $notifyUrl,
            'return_url' => $return_url??url("recharge/bill",[$order['trade_no']]),
            'trade_no' => $order['trade_no'],
            'total_amount' => $order['total_amount'],
            'user_id' => 0,
        ]);
    }

    public function form()
    {
        $form = $this->payment->form();
        $keys = array_keys($form);
        foreach ($keys as $key) {
            if (isset($this->config[$key])) $form[$key]['value'] = $this->config[$key];
        }
        return $form;
    }
}
