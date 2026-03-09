<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GatewaySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('gateways')->insert([
            [
                'name' => 'Gateway 1',
                'is_active' => true,
                'priority' => 1,
                'base_url' => 'http://gateway1:3001',
                'auth_config' => json_encode([
                    'type' => 'bearer',
                    'login' => [
                        'email' => 'dev@betalent.tech',
                        'token' => 'FEC9BB078BF338F464F96B48089EB498'
                    ]
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gateway 2',
                'is_active' => true,
                'priority' => 2,
                'base_url' => 'http://gateway2:3002',
                'auth_config' => json_encode([
                    'type' => 'headers',
                    'headers' => [
                        'Gateway-Auth-Token' => 'tk_f2198cc671b5289fa856',
                        'Gateway-Auth-Secret' => '3d15e8ed6131446ea7e3456728b1211f'
                    ]
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
