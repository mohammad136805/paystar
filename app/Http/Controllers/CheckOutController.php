<?php

namespace App\Http\Controllers;

use App\Models\CheckOut;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;


use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;

class CheckOutController extends Controller
{

    public $encryption_key = "9A3EC03483556C73714510C507529DF70A1228C83477D1455E0511BD72C5AAB8A6715A414AA48B7C905FCEF45868BD26DA58196EF29C77C194C9F14A4B47456CC6454E9D50B388D6FC5AC91BB08B234A8060FDC85B1CEC32CA036DC907F8A4A635D9CBB9CAA31B42549B8D70B2CE5EDE8274FFB55DABFE92D76BC42D91696FAF";
    public $GATEWAY_ID = "0yovdk2l6e143";

    public $card_number = "6037991327465278";

    public $product = [
        "name" => "کتاب دیوان حافظ",
        "amount" => 5000,
        "author" => "حافظ",
        "publisher" => "پی استار"
    ];
    public $order_id = "myuniqid1234";
    public $user_id = 1;

    public $payment_url = 'https://core.paystar.ir/api/pardakht/payment';
    public $create_url = 'https://core.paystar.ir/api/pardakht/create';
    public $verify_url = 'https://core.paystar.ir/api/pardakht/verify';

    public function checkOut()
    {

        $response = $this->call_api_create();

        //todo: check unrequire field for api

        $response_status = $response['status'];
        if ($response_status == 1) {
            $data['token'] = $response['data']['token'];

            //dont need to send to view
            $data['ref_num'] = $response['data']['ref_num'];
            $data['final_amount'] = $response['data']['payment_amount'];
            $data['order_id'] = $response['data']['order_id'];

            $check_out = new CheckOut();
            $check_out->user_id = $this->user_id;
            $check_out->order_id = $this->order_id;
            $check_out->ref_num = $response['data']['ref_num'];
            $check_out->payment_amount = $response['data']['payment_amount'];
            $check_out->save();


            $data['payment_action'] = $this->payment_url;

        }
        else{
            $data['check_out'] = $response;
        }


        $data['product'] = $this->product;
        $data['card_number'] = preg_replace('/(\d{4})(\d{4})(\d{4})(\d{4})/i', '$1-$2-$3-$4', $this->card_number);


        return view('check_out', $data);
    }

    public function callBack(Request $request)
    {

        $errors = array();

        $errors['status'] = $this->checkStatusCode($request->status);
        $errors['ref_num'] = $this->checkRefNumber($request->ref_num, $request->order_id) ;
        $errors['card_number'] = $this->checkCardNumber($request->card_number) ;

        $data['error_message'] = array_filter($errors, function ($item) {
            return !is_null($item);
        });


        $check_out = CheckOut::query()->where('ref_num', $request->ref_num)->first();
        $payment = new Payment();
        $payment->user_id = $this->user_id;
        $payment->check_out_id = $check_out->id;
        $payment->tracking_code = $request->tracking_code ?? null;
        $payment->transaction_id = $request->transaction_id;
        $payment->card_number = empty($data['error_message']) ? $this->card_number : $request->card_number  ;
        $payment->success = (empty($data['error_message']) && $request->status == 1) ? 1  : 0 ;
        $payment->save();

        $data['payment_id'] = $payment->id;
        $data['payment_amount'] = $check_out->payment_amount;
        $data['payment_data'] = $request->all();
        $data['product'] = $this->product;

        return view('call_back', $data);

    }

    public function verifyPayment(Request $request)
    {
        $sign_card_number = preg_replace('/(\d{6})(\d{6})(\d{4})/i', '$1******$3', $this->card_number);
        $signString = $request->payment_amount . '#' . $request->ref_num . '#' . $sign_card_number . '#' . $request->tracking_code;
        $sign = hash_hmac('sha512', $signString, $this->encryption_key);
        $verify_data = $this->call_api_verify($request->payment_amount, $request->ref_num, $sign);


        $verify_payment = Payment::find($request->payment_id);
        if (is_null($verify_payment->is_verify)) {
            $verify_payment->is_verify = ($verify_data['status'] == 1) ? 1 : 0;
            $verify_payment->save();
        }


        if ($verify_data['status'] == 1)
        {
            return redirect()->route('checkOutUrl');
        }
        else
        {
            $data['verify'] = $verify_data;
            return view('verify_payment',$data);
        }
    }

    private function call_api_create()
    {


        $amount = $this->product['amount'];
        $order_id = $this->order_id;
        $callback = route('callBackUrl');
        $signString = $amount . '#' . $this->order_id . '#' . $callback;
        $sign = hash_hmac('sha512', $signString, $this->encryption_key);


        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->GATEWAY_ID
        ];
        $body = [
            "amount" => $amount,
            "order_id" => $order_id,
            "callback" => $callback,
            "sign" => $sign
        ];

        $response = Http::withoutVerifying()
            ->withHeaders($headers)
            ->withOptions(['verify' => false])
            ->post($this->create_url, $body);


        return $response->json();
//        dd($response->json());

    }

    private function call_api_verify($payment_amount, $ref_num, $sign)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->GATEWAY_ID
        ];
        $body = [
            "amount" => $payment_amount,
            "ref_num" => $ref_num,
            "sign" => $sign
        ];

        $response = Http::withoutVerifying()
            ->withHeaders($headers)
            ->withOptions(['verify' => false])
            ->post($this->verify_url, $body);


        return $response->json();


    }

    private function checkStatusCode($status)
    {
        $message = $status == 1 ? null : __('paystar.status.' . $status);
        return $message;
    }

    private function checkRefNumber($ref_num, $order_id)
    {
        $message = null;
        $check_out = CheckOut::query()->where('ref_num', $ref_num)->first();
        if (!is_null($check_out)) {
            $message = $check_out->order_id == $order_id ? null : __('paystar.diffrent_orderId');
        } else {
            $message = __('paystar.diffrent_orderId');

        }

        return $message;
    }

    private function checkCardNumber($card_number)
    {
        $card_number_replace = preg_replace('/(\d{6})(\d{6})(\d{4})/i', '$1******$3', $this->card_number);
        $message = strcmp($card_number, $card_number_replace) != 0
            ? __('paystar.diffrent_cardNumber')
            : null;


        return $message;
    }





}
