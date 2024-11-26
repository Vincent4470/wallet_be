<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->query("limit") ? $request->query("limit") : 10;

        $user = auth()->user();

        $relations = [
            'paymentMethod:id,name,code,thumbnail',
            'transactionType:id,name,code,action,thumbnail',
        ];

        $transactions = Transaction::with($relations)
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->orderBy('id', 'desc')
            ->paginate($limit);

        $transactions->getCollection()->transform(function ($item) {
            $paymentMethod = $item->paymentMethod;
            $item->paymentMethod->thumbnail = $paymentMethod->thumbnail ?
                url('banks/' . $paymentMethod->thumbnail) : "";

            $transactionsType = $item->transactionType;
            $item->transactionType->thumbnail = $transactionsType->thumbnail ?
                url('transaction-type/' . $transactionsType->thumbnail) : "";

            return $item;
        });

        return response()->json($transactions);
    }
}
