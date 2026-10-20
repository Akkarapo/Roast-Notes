<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Demo accounts — every password is "password".
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'ผู้ดูแลร้าน',
            'email' => 'admin@roastnotes.test',
            'phone' => '0800000000',
            'address' => 'Roast & Notes, เชียงใหม่',
        ]);

        $members = [
            ['name' => 'สมชาย ใจดี', 'phone' => '0811111111', 'address' => '12 ถ.นิมมานเหมินท์ ต.สุเทพ อ.เมือง จ.เชียงใหม่ 50200'],
            ['name' => 'สมหญิง รักกาแฟ', 'phone' => '0822222222', 'address' => '88 ถ.สุขุมวิท แขวงคลองตันเหนือ เขตวัฒนา กรุงเทพฯ 10110'],
            ['name' => 'ปิติ ชอบดริป', 'phone' => '0833333333', 'address' => '5 ถ.มิตรภาพ ต.ในเมือง อ.เมือง จ.ขอนแก่น 40000'],
        ];

        foreach ($members as $i => $member) {
            User::factory()->create($member + ['email' => 'member'.($i + 1).'@roastnotes.test']);
        }
    }
}
