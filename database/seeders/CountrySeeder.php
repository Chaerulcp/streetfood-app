<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Constraint\Count;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $usaStates = [
            "AC" => "Aceh",
            "SU" => "Sumatera Utara",
            "SB" => "Sumatera Barat",
            "RI" => "Riau",
            "JA" => "Jambi",
            "SS" => "Sumatera Selatan",
            "BB" => "Bangka Belitung",
            "BE" => "Bengkulu",
            "LA" => "Lampung",
            "JK" => "DKI Jakarta",
            "JB" => "Jawa Barat",
            "BT" => "Banten",
            "JT" => "Jawa Tengah",
            "JI" => "Jawa Timur",
            "YO" => "Yogyakarta",
            "BA" => "Bali",
            "NB" => "Nusa Tenggara Barat",
            "NT" => "Nusa Tenggara Timur",
            "KB" => "Kalimantan Barat",
            "KT" => "Kalimantan Tengah",
            "KI" => "Kalimantan Timur",
            "KS" => "Kalimantan Selatan",
            "KU" => "Kalimantan Utara",
            "SA" => "Sulawesi Utara",
            "ST" => "Sulawesi Tengah",
            "SG" => "Sulawesi Tenggara",
            "SR" => "Sulawesi Barat",
            "SN" => "Sulawesi Selatan",
            "GO" => "Gorontalo",
            "MA" => "Maluku",
            "MU" => "Maluku Utara",
            "PA" => "Papua",
            "PB" => "Papua Barat",
        ];
        $countries = [
            ['code' => 'id', 'name' => 'Indonesia', 'states' => json_encode($usaStates)],
            
        ];
        Country::insert($countries);
    }
}
