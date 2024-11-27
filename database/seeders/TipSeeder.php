<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table("tips")->insert([
            [
                'title' => 'Cara menyimpan uang yang baik',
                'thumbnail' => 'nabung.jpg',
                'url' => 'https://www.banksaqu.co.id/blog/cara-menabung-yang-benar',
                'created_at' => now(),
                'updated_at'=> now(),
            ],
            [
                'title' => 'Cara berinvestasi emas',
                'thumbnail' => 'emas.jpg',
                'url' => 'https://www.moxa.id/blog/investasi/tips-investasi-emas/?gad_source=1&gclid=Cj0KCQiAo5u6BhDJARIsAAVoDWs6uhuvR6lhzPI-a8KTcZzrD7YcvkCjS6yNLVDlsCOzKOYpAoncOY0aAs3MEALw_wcB',
                'created_at' => now(),
                'updated_at'=> now(),
            ],[
                'title' => 'Cara menyimpan investasi saham',
                'thumbnail' => 'saham.jpg',
                'url' => 'https://market.bisnis.com/read/20240802/94/1787322/cara-investasi-bitcoin-untuk-pemula-semudah-investasi-saham',
                'created_at' => now(),
                'updated_at'=> now(),
            ],[
                'title' => 'belajar coding',
                'thumbnail' => 'bwa.png',
                'url' => 'https://buildwithangga.com/',
                'created_at' => now(),
                'updated_at'=> now(),
            ],
        ]);
    }
}
