<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TransferHistory;
use App\Models\TransactionType;
use App\Models\Wallet;
use App\Models\User;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;



class TransferController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->only('amount', 'pin', 'send_to');

        // validation input
        $validator = Validator::make($data, [
            'amount' => 'required|integer|min:10000',
            'pin' => 'required|digits:6',
            'send_to' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        // ambil user pengirim
        $sender = auth()->user();

        // cari user penerima
        $receiver = User::select('users.id', 'users.username')
            ->join('wallets', 'wallets.user_id', 'users.id')
            ->where('users.username', $request->send_to)
            ->orWhere('wallets.card_number', $request->send_to)
            ->first();

            // cek validitas pin
        $pinChecker = pinChecker($request->pin);

        if (!$pinChecker) {
            return response()->json(['message' => 'Your Pin is wrong'], 400);
        }

        // cek apakah penerima ada
        if (!$receiver) {
            return response()->json(['message' => 'User receiver not found'], 400);
        }

        // cek apakah pengirim dan penerima sama
        if ($sender->id == $receiver->id) {
            return response()->json(['message' => 'You can not send money to yourself'], 400);
        }

        // ambil wallet pengirim
        $senderWallet = Wallet::where('user_id', $sender->id)->first();

        // ambil wallet penerima
        if ($senderWallet->balance < $request->amount) {
            return response()->json(['message' => 'Your balance is not enough'], 400);
        }

        DB::beginTransaction();

        try {
            // ambil semua tipe transaksi
            $transactionType = TransactionType::whereIn('code', ['receive', 'transfer'])
                ->orderBy('code', 'asc')
                ->get();

            $receiveTransactionType = $transactionType->first();
            $transferTransactionType = $transactionType->last();

            // generate kode transaction 
            $transactionCode = strtoupper(Str::random(10));
            $paymentMethod = PaymentMethod::where('code', 'bwa')->first();

            // Ambil wallet pembayaran
            $transferTransactionType = Transaction::created([
                'user_id' => $sender->id,
                'transaction_type_id' => $transferTransactionType->id,
                'description' => 'Transfer funds to ' . $receiver->username,
                'amount' => $request->amount,
                'transaction_code' => $transactionCode,
                'status' => 'success',
                'payment_method_id' => $paymentMethod,
            ]);

            // kurangin saldo pengirim
            $senderWallet->decrement('balance', $request->amount);

            // simpan transaksi penerima
            $transferTransactionType = Transaction::created([
                'user_id' => $receiver->id,
                'transaction_type_id' => $receiveTransactionType->id,
                'description' => 'Receive funds from ' . $sender->username,
                'amount' => $request->amount,
                'transaction_code' => $transactionCode,
                'status' => 'success',
                'payment_method_id' => $paymentMethod,
            ]);

            // tambah saldo penerima
            Wallet::where('user_id', $receiver->id)->increment('balance', $request->amount);

            // simpan riwayat transfer
            TransferHistory::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'transaction_code' => $transactionCode,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Transaction successful',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
