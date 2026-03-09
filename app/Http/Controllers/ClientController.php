<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = Client::withCount('transactions')
            ->orderBy('name')
            ->paginate(20);

        return response()->json($clients);
    }

    public function show(Client $client)
    {
        $client->load([
            'transactions' => function ($query) {
                $query->with(['gateway', 'products'])
                    ->orderBy('created_at', 'desc');
            }
        ]);

        return response()->json($client);
    }
}
