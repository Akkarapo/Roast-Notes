<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Extra demo customers with a few small orders each (OrderSeeder) so products get reviews from
     * many people. Taste profile: [flavor categories, roast, brew] — null = has not taken the quiz.
     */
    public const COMMUNITY = [
        ['email' => 'nattaya@roastnotes.test', 'name' => 'ณัฐธยา วงศ์ใหญ่', 'phone' => '0841000001', 'address' => '45 ถ.ห้วยแก้ว ต.สุเทพ อ.เมือง จ.เชียงใหม่ 50200', 'taste' => [['fruity', 'floral'], 'light', 'aeropress']],
        ['email' => 'thanakorn@roastnotes.test', 'name' => 'ธนกร ศรีสุข', 'phone' => '0841000002', 'address' => '19 ถ.พหลโยธิน แขวงสามเสนใน เขตพญาไท กรุงเทพฯ 10400', 'taste' => [['nutty-cocoa'], 'dark', 'moka_pot']],
        ['email' => 'pimchanok@roastnotes.test', 'name' => 'พิมพ์ชนก แก้วมณี', 'phone' => '0841000003', 'address' => '7 ถ.ราชดำเนิน ต.ในเมือง อ.เมือง จ.นครศรีธรรมราช 80000', 'taste' => [['sweet', 'fruity'], 'medium', 'pour_over']],
        ['email' => 'kittipong@roastnotes.test', 'name' => 'กิตติพงษ์ ทองดี', 'phone' => '0841000004', 'address' => '120 ถ.มิตรภาพ ต.ในเมือง อ.เมือง จ.นครราชสีมา 30000', 'taste' => [['nutty-cocoa', 'sweet'], 'medium', 'espresso']],
        ['email' => 'siriporn@roastnotes.test', 'name' => 'ศิริพร บุญมา', 'phone' => '0841000005', 'address' => '33 ถ.เจริญเมือง ต.ท่าอิฐ อ.เมือง จ.อุตรดิตถ์ 53000', 'taste' => [['floral'], 'light', 'pour_over']],
        ['email' => 'wuttichai@roastnotes.test', 'name' => 'วุฒิชัย จันทร์เพ็ญ', 'phone' => '0841000006', 'address' => '88/2 ถ.เพชรเกษม ต.หาดใหญ่ อ.หาดใหญ่ จ.สงขลา 90110', 'taste' => [['sour-fermented', 'fruity'], 'light', 'cold_brew']],
        ['email' => 'chanida@roastnotes.test', 'name' => 'ชนิดา พรหมวงศ์', 'phone' => '0841000007', 'address' => '5 ซ.อารีย์ 2 แขวงพญาไท เขตพญาไท กรุงเทพฯ 10400', 'taste' => [['sweet'], 'medium', 'french_press']],
        ['email' => 'apisit@roastnotes.test', 'name' => 'อภิสิทธิ์ แสงทอง', 'phone' => '0841000008', 'address' => '61 ถ.ศรีจันทร์ ต.ในเมือง อ.เมือง จ.ขอนแก่น 40000', 'taste' => [['nutty-cocoa'], 'dark', 'espresso']],
        ['email' => 'kanokwan@roastnotes.test', 'name' => 'กนกวรรณ มีสุข', 'phone' => '0841000009', 'address' => '14 ถ.นิมมานเหมินท์ ซ.13 ต.สุเทพ อ.เมือง จ.เชียงใหม่ 50200', 'taste' => [['fruity'], 'light', 'drip_bag']],
        ['email' => 'teerapat@roastnotes.test', 'name' => 'ธีรภัทร อินทร์แก้ว', 'phone' => '0841000010', 'address' => '200 ถ.สุขุมวิท ต.ปากน้ำ อ.เมือง จ.สมุทรปราการ 10270', 'taste' => [['sweet', 'nutty-cocoa'], 'medium', 'aeropress']],
        ['email' => 'jirapa@roastnotes.test', 'name' => 'จิราภา สายสุวรรณ', 'phone' => '0841000011', 'address' => '9 ถ.ธนาลัย ต.เวียง อ.เมือง จ.เชียงราย 57000', 'taste' => [['floral', 'sweet'], 'light', 'pour_over']],
        ['email' => 'nopparat@roastnotes.test', 'name' => 'นพรัตน์ คำแสง', 'phone' => '0841000012', 'address' => '17 ถ.โพศรี ต.หมากแข้ง อ.เมือง จ.อุดรธานี 41000', 'taste' => [['nutty-cocoa', 'sour-fermented'], 'dark', 'french_press']],
        ['email' => 'pattama@roastnotes.test', 'name' => 'ปัทมา รุ่งเรือง', 'phone' => '0841000013', 'address' => '42 ถ.ราษฎร์อุทิศ ต.บ่อผุด อ.เกาะสมุย จ.สุราษฎร์ธานี 84320', 'taste' => null],
        ['email' => 'surasak@roastnotes.test', 'name' => 'สุรศักดิ์ พึ่งบุญ', 'phone' => '0841000014', 'address' => '3 ถ.บรมไตรโลกนาถ ต.ในเมือง อ.เมือง จ.พิษณุโลก 65000', 'taste' => [['fruity', 'sweet'], 'medium', 'espresso']],
        ['email' => 'warunee@roastnotes.test', 'name' => 'วารุณี ชัยมงคล', 'phone' => '0841000015', 'address' => '56 ถ.ตลาดใหม่ ต.ท่าประดู่ อ.เมือง จ.ระยอง 21000', 'taste' => null],
    ];

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

        foreach (self::COMMUNITY as $i => $customer) {
            unset($customer['taste']);
            // signed up at different times over the last ~4 months
            User::factory()->create($customer + ['created_at' => now()->subDays(120 - $i * 7)]);
        }
    }
}
