<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Notification;
use Midtrans\Snap;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class MidtransService
{
    public function getSnapToken($transaksi)
    {
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$clientKey = env('MIDTRANS_CLIENT_KEY');
        Config::$isProduction = env('APP_ENV') === 'production';

        $transactionDetails = [
            'order_id' => 'ORDER-ID-' . Str::uuid()->toString(),
            'gross_amount' => $transaksi->total_pembayaran,
        ];

        $customerDetails = [
            'first_name' => Auth::user()->name,
            'last_name' => '',
            'email' => Auth::user()->email,
            'phone' => Auth::user()->no_hp,
        ];

        return Snap::getSnapToken([
            'transaction_details' => $transactionDetails,
            'customer_details' => $customerDetails,
        ]);
    }

    public function validateNotification($json)
    {
        $notification = new Notification();
        return $notification($json);
    }
}
