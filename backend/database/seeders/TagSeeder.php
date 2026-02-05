<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            // Genres
            ['name' => 'Фентезі', 'category' => 'genre'],
            ['name' => 'Наукова фантастика', 'category' => 'genre'],
            ['name' => 'Романтика', 'category' => 'genre'],
            ['name' => 'Пригоди', 'category' => 'genre'],
            ['name' => 'Детектив', 'category' => 'genre'],
            ['name' => 'Жахи', 'category' => 'genre'],
            ['name' => 'Комедія', 'category' => 'genre'],
            ['name' => 'Драма', 'category' => 'genre'],
            ['name' => 'Трилер', 'category' => 'genre'],
            ['name' => 'Історичний', 'category' => 'genre'],

            // Content warnings
            ['name' => 'Насильство', 'category' => 'warning'],
            ['name' => '18+', 'category' => 'warning'],
            ['name' => 'Смерть персонажів', 'category' => 'warning'],
            ['name' => 'Темні теми', 'category' => 'warning'],

            // Themes
            ['name' => 'Друзі-вороги', 'category' => 'theme'],
            ['name' => 'Обрані один одного', 'category' => 'theme'],
            ['name' => 'Повільний розвиток', 'category' => 'theme'],
            ['name' => 'Знайдена сім\'я', 'category' => 'theme'],
            ['name' => 'Подорож героя', 'category' => 'theme'],
            ['name' => 'Альтернативний всесвіт', 'category' => 'theme'],
            ['name' => 'Кросовер', 'category' => 'theme'],
            ['name' => 'Фікс-іт', 'category' => 'theme'],

            // Length
            ['name' => 'Оповідання', 'category' => 'length'],
            ['name' => 'Повість', 'category' => 'length'],
            ['name' => 'Роман', 'category' => 'length'],
            ['name' => 'Серія', 'category' => 'length'],
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(
                ['slug' => Str::slug($tag['name'])],
                [
                    'name' => $tag['name'],
                    'slug' => Str::slug($tag['name']),
                    'category' => $tag['category'],
                ]
            );
        }
    }
}
