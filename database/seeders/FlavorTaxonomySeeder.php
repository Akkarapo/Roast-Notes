<?php

namespace Database\Seeders;

use App\Models\FlavorCategory;
use Illuminate\Database\Seeder;

/**
 * Flavor categories and notes based on the World Coffee Research Sensory Lexicon
 * (top-level category -> subcategory -> note). Top-level slugs are used by product filters and the Taste Quiz.
 */
class FlavorTaxonomySeeder extends Seeder
{
    public const TAXONOMY = [
        'fruity' => ['Fruity', [
            'berry' => ['Berry', ['Blueberry', 'Strawberry', 'Raspberry']],
            'dried-fruit' => ['Dried fruit', ['Raisin', 'Prune', 'Date']],
            'citrus' => ['Citrus', ['Lemon', 'Orange', 'Grapefruit', 'Bergamot']],
            'stone-fruit' => ['Stone fruit', ['Peach', 'Apricot', 'Cherry']],
            'tropical-fruit' => ['Tropical fruit', ['Mango', 'Pineapple', 'Passion fruit']],
        ]],
        'floral' => ['Floral', [
            'blossom' => ['Blossom', ['Jasmine', 'Rose', 'Orange blossom']],
            'black-tea' => ['Black tea', ['Black tea', 'Earl grey']],
        ]],
        'sweet' => ['Sweet', [
            'brown-sugar' => ['Brown sugar', ['Caramel', 'Brown sugar', 'Molasses', 'Maple syrup']],
            'honey' => ['Honey', ['Honey', 'Vanilla']],
        ]],
        'nutty-cocoa' => ['Nutty/Cocoa', [
            'nutty' => ['Nutty', ['Almond', 'Hazelnut', 'Peanut']],
            'cocoa' => ['Cocoa', ['Dark chocolate', 'Milk chocolate', 'Cocoa nib']],
        ]],
        'sour-fermented' => ['Sour/Fermented', [
            'fermented' => ['Fermented', ['Red wine', 'Whiskey', 'Rum']],
        ]],
    ];

    public function run(): void
    {
        foreach (self::TAXONOMY as $slug => [$name, $children]) {
            $top = FlavorCategory::create(['slug' => $slug, 'name' => $name]);

            foreach ($children as $childSlug => [$childName, $notes]) {
                $child = $top->children()->create(['slug' => $childSlug, 'name' => $childName]);

                foreach ($notes as $note) {
                    $child->notes()->create(['name' => $note]);
                }
            }
        }
    }
}
