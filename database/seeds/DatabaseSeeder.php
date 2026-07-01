<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(SettingTableSeeder::class);
        $this->call(CustomerTableSeeder::class);
        $this->call(StoreStatusSeeder::class);
        $this->call(Expense_categoriesSeeder::class);

        // UserTableSeeder (default admin@admin.com / password) intentionally
        // NOT run automatically. Create your own admin instead, e.g. via:
        // php artisan tinker
        // \App\User::create(['name'=>'Your Name','email'=>'you@yourdomain.com','password'=>bcrypt('your-strong-password'),'role'=>'admin']);
    }
}
