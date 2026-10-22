<?php

namespace Database\Seeders;

use App\Enums\BrewMethod;
use App\Enums\RoastLevel;
use App\Models\FlavorCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo taste profiles: member1 and member2 have done the quiz, member3 has not (to demo the quiz prompts).
 */
class TasteProfileSeeder extends Seeder
{
    public const PROFILES = [
        'member1@roastnotes.test' => [['fruity', 'floral'], RoastLevel::Light, BrewMethod::PourOver],
        'member2@roastnotes.test' => [['nutty-cocoa', 'sweet'], RoastLevel::Dark, BrewMethod::Espresso],
    ];

    /** member1/2 + the community customers who took the quiz */
    public static function profiles(): array
    {
        $profiles = self::PROFILES;
        foreach (UserSeeder::COMMUNITY as $customer) {
            if ($customer['taste']) {
                [$slugs, $roast, $brew] = $customer['taste'];
                $profiles[$customer['email']] = [$slugs, RoastLevel::from($roast), BrewMethod::from($brew)];
            }
        }

        return $profiles;
    }

    public function run(): void
    {
        $categories = FlavorCategory::topLevel()->pluck('id', 'slug');

        foreach (self::profiles() as $email => [$slugs, $roast, $brew]) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                continue;
            }

            $user->tastePreferences()->sync($categories->only($slugs)->values());
            $user->forceFill([
                'preferred_roast_level' => $roast,
                'preferred_brew_method' => $brew,
                'taste_quiz_completed_at' => now(),
            ])->save();
        }
    }
}
