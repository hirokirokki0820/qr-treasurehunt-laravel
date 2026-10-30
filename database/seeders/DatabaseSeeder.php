<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * 動作確認用に、主催者 demo / password と景品入りのイベントを1つ作る。
     */
    public function run(): void
    {
        $organizer = User::factory()->create([
            'name' => 'demo',
            'email' => null,
        ]);

        $event = Event::factory()->for($organizer)->create([
            'name' => '秋の宝探し大会',
            'description' => '会場のどこかに隠されたQRコードを探そう！',
        ]);

        $prizes = [
            ['name' => 'お菓子の詰め合わせ', 'description' => '受付で交換してね'],
            ['name' => '図書カード 500円分', 'description' => null],
            ['name' => 'オリジナル缶バッジ', 'description' => null],
            ['name' => 'ハズレ', 'description' => '残念！また探してみよう', 'is_miss' => true],
            ['name' => '特賞 ぬいぐるみ', 'description' => 'おめでとう！大当たり！'],
            ['name' => 'ハズレ', 'description' => 'ここには何もなかった…', 'is_miss' => true],
        ];

        foreach ($prizes as $prize) {
            $event->prizes()->create($prize);
        }
    }
}
