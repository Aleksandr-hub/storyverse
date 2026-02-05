<?php

namespace Database\Seeders;

use App\Models\Universe;
use Illuminate\Database\Seeder;

class UniverseSeeder extends Seeder
{
    public function run(): void
    {
        $universes = [
            [
                'name' => 'Marvel',
                'slug' => 'marvel',
                'description' => 'Всесвіт Marvel Comics з супергероями, такими як Залізна Людина, Капітан Америка, Тор та багато інших.',
                'is_official' => true,
            ],
            [
                'name' => 'Harry Potter',
                'slug' => 'harry-potter',
                'description' => 'Магічний світ Гаррі Поттера — Гоґвортс, чарівники, магічні істоти та темні сили.',
                'is_official' => true,
            ],
            [
                'name' => 'Відьмак',
                'slug' => 'witcher',
                'description' => 'Темне фентезі світ Відьмака Ґеральта з Рівії — монстри, магія, політичні інтриги.',
                'is_official' => true,
            ],
            [
                'name' => 'Star Wars',
                'slug' => 'star-wars',
                'description' => 'Далека-далека галактика — джедаї, ситхи, імперія та повстанці.',
                'is_official' => true,
            ],
            [
                'name' => 'Dungeons & Dragons',
                'slug' => 'dnd',
                'description' => 'Класичний світ D&D — драконів, підземель, магії та пригод.',
                'is_official' => true,
            ],
            [
                'name' => 'DC Comics',
                'slug' => 'dc-comics',
                'description' => 'Всесвіт DC — Бетмен, Супермен, Диво-Жінка та Ліга Справедливості.',
                'is_official' => true,
            ],
            [
                'name' => 'Оригінальний світ',
                'slug' => 'original',
                'description' => 'Створюй власні унікальні світи та історії без обмежень.',
                'is_official' => true,
            ],
        ];

        foreach ($universes as $universe) {
            Universe::firstOrCreate(['slug' => $universe['slug']], $universe);
        }
    }
}
