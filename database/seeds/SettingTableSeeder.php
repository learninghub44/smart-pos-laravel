<?php

use Illuminate\Database\Seeder;

class SettingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      DB::table('settings')->insert([
          'company_name' => 'Zetu POS',
          'phone_number' => '',
          'address' => "Kenya",
          'currency' => 'KES',
          'default_vat' => 16,
          'logo' => 'defaultcompanylogo.png',
          'created_at' => \Carbon\Carbon::now()->toDateTimeString(),
          'time_zone' => 'Africa/Nairobi',
          'delivery_charge' => 0,
      ]);
    }
}
