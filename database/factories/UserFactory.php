<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    private static array $names = [
        'Ali Yılmaz', 'Ayşe Kaya', 'Mehmet Demir', 'Fatma Çelik', 'Ahmet Şahin',
        'Zeynep Arslan', 'Mustafa Kurt', 'Elif Aydın', 'Hasan Yıldız', 'Merve Doğan',
        'İbrahim Çetin', 'Selin Koç', 'Emre Öztürk', 'Büşra Erdoğan', 'Oğuz Polat',
        'Deniz Kılıç', 'Serkan Güneş', 'Tuğba Acar', 'Cem Yalçın', 'Pınar Bulut',
        'Kemal Özcan', 'Esra Güler', 'Volkan Kara', 'Sibel Uçar', 'Tarık Aslan',
        'Gül Tekin', 'Berk Çakır', 'Nalan Erdem', 'Uğur Sönmez', 'Ceren Akbaş',
        'Tolga Duman', 'Hatice Yavuz', 'Murat Keskin', 'Sevgi Bozkurt', 'Selim Güven',
        'Arzu Aksoy', 'Barış Şimşek', 'Dilek Kaplan', 'Koray Ateş', 'Nilüfer Avcı',
    ];

    private static array $emails = [
        'ali@test.com', 'ayse@test.com', 'mehmet@test.com', 'fatma@test.com',
        'ahmet@test.com', 'zeynep@test.com', 'mustafa@test.com', 'elif@test.com',
        'hasan@test.com', 'merve@test.com', 'ibrahim@test.com', 'selin@test.com',
        'emre@test.com', 'busra@test.com', 'oguz@test.com', 'deniz@test.com',
        'serkan@test.com', 'tugba@test.com', 'cem@test.com', 'pinar@test.com',
        'kemal@test.com', 'esra@test.com', 'volkan@test.com', 'sibel@test.com',
        'tarik@test.com', 'gul@test.com', 'berk@test.com', 'nalan@test.com',
        'ugur@test.com', 'ceren@test.com', 'tolga@test.com', 'hatice@test.com',
        'murat@test.com', 'sevgi@test.com', 'selim@test.com', 'arzu@test.com',
        'baris@test.com', 'dilek@test.com', 'koray@test.com', 'nilufer@test.com',
    ];

    private static int $fallback = 0;

    public function definition(): array
    {
        if (static::$names) {
            $nameIdx = array_rand(static::$names);
            $name    = static::$names[$nameIdx];
            array_splice(static::$names, $nameIdx, 1);
        } else {
            $name = 'User ' . (++static::$fallback);
        }

        if (static::$emails) {
            $emailIdx = array_rand(static::$emails);
            $email    = static::$emails[$emailIdx];
            array_splice(static::$emails, $emailIdx, 1);
        } else {
            $email = 'user' . static::$fallback . '@test.com';
        }

        return [
            'name'              => $name,
            'email'             => $email,
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'is_active'         => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
