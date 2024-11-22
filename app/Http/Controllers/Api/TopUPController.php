<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TransactionType;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TopUPController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->only('amount', 'pin', 'payment_method_code');

        $validator = Validator::make($data, [
            'amount' => 'required|integer|min:10000',
            'pin' => 'required|digits:6',
            'payment_method_code' => 'required|in:bni_va,bca_va,bri_va',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $pinChecker = pinChecker($request->pin);

        if (!$pinChecker) {
            return response()->json(['message' => 'Your Pin is wrong'], 400);
        }

        $transactionType = TransactionType::where('code', 'top_up')->first();
        $paymentMethod = PaymentMethod::where('code', $request->payment_method_code)->first();

        DB::beginTransaction();
        try {
            $transaction = Transaction::create([
                'user_id' => auth()->user()->id,
                'payment_method_id' => $paymentMethod->id,
                'transaction_type_id' => $transactionType->id,
                'amount' => $request->amount,
                'transaction_code' => strtoupper(Str::random(10)),
                'description' => 'Top Up via ' . $paymentMethod->name,
                'status' => 'pending',
            ]);

            $parms = $this->buildlMidtransParameter([
                'transaction_code' => $transaction->transaction_code,
                'amount' => $transaction->amount,
                'payment_method_code' => $paymentMethod->code,
            ]);

            $midtrans = $this->callMidtrans($parms);

            // call to midtrans
            DB::commit();

            return response()->json($midtrans, 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    private function callMidtrans(array $parms)
    {
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = (bool) env('MIDTRANS_IS_PRODUCTION');
        \Midtrans\Config::$isSanitized = (bool) env('MIDTRANS_IS_SANITIZED');
        \Midtrans\Config::$is3ds = (bool) env('MIDTRANS_IS_3DS');

        $createTransaction =  \Midtrans\Snap::createTransaction($parms);

        return [
            'redirect_url' => $createTransaction->redirect_url,
            'token' => $createTransaction->token,
        ];
    }

    private function buildlMidtransParameter(array $parms)
    {
        $transactionDetails = [
            'order_id' => $parms['transaction_code'],
            'gross_amount' => $parms['amount'],
        ];

        $user = auth()->user();
        $splitName = $this->splitName($user->name);
        $customerDetails = [
            'first_name' => $splitName['first_name'],
            'last_name' => $splitName['last_name'],
            'email' => $user->email,
        ];

        $enabled_payments = [
            $parms['payment_method_code'],
        ];

        return [
            'transaction_details' => $transactionDetails,
            'customer_details' => $customerDetails,
            'enabled_payments' => $enabled_payments,
        ];
    }

    private function splitName($fullName)
    {
        $name = explode(' ', $fullName);

        // 'Vincent Edwin Mangapul'
        // ['Vincent', 'Edwin', 'Mangapul']

        $lastName = count($name) > 1 ? array_pop($name) : $fullName;
        $firstName = implode(' ', $name);

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
        ];
    }
}
