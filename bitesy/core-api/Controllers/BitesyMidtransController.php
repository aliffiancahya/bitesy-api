<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Transaksi;
use App\Models\Produk;
use App\Models\DetailTransaksi;
use App\Services\MidtransService;

class BitesyMidtransController extends Controller
{
    protected $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.*.id' => 'required|exists:produk,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $transaksi = DB::transaction(function () use ($request) {
            $transaksi = Transaksi::create([
                'customer_id' => Auth::user()->id,
                'metode_transaksi' => 'midtrans',
                'is_done' => false,
            ]);

            $total = 0;
            foreach ($request->input('products') as $product) {
                $produk = Produk::findOrFail($product['id']);
                DetailTransaksi::create([
                    'transaksi_id' => $transaksi->id,
                    'produk_id' => $produk->id,
                    'harga' => $produk->harga,
                    'jumlah' => $product['quantity'],
                ]);
                $total += $produk->harga * $product['quantity'];
            }

            $transaksi->total_pembayaran = $total;
            $transaksi->save();

            return $transaksi;
        });

        return response()->json([
            'transaction_id' => $transaksi->id,
            'snap_token' => $this->midtransService->getSnapToken($transaksi),
        ]);
    }

    public function initiatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:transaksi,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $transaksi = Transaksi::findOrFail($request->input('transaction_id'));

        return response()->json([
            'snap_token' => $this->midtransService->getSnapToken($transaksi),
        ]);
    }

    public function handleNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:transaksi,id',
            'json' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $transaksi = Transaksi::findOrFail($request->input('transaction_id'));

        $notificationResponse = $this->midtransService->validateNotification($request->input('json'));

        if ($notificationResponse->isRedirect()) {
            return response()->json([
                'redirect_url' => $notificationResponse->getRedirectUrl(),
            ]);
        } elseif ($notificationResponse->isSuccess()) {
            $transaksi->is_done = true;
            $transaksi->save();

            return response()->json([
                'message' => 'Payment successful',
            ]);
        } elseif ($notificationResponse->isExpired()) {
            return response()->json([
                'message' => 'Payment expired',
            ], 400);
        } else {
            return response()->json([
                'message' => 'Payment failed',
            ], 400);
        }
    }
}
