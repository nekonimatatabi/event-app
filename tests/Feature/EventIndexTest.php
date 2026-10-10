<?php

use App\Models\Event;
use App\Models\TicketType;
use Livewire\Livewire;

test('event list shows the price label of the cheapest ticket type', function (array $prices, string $expectedLabel) {
    $event = Event::factory()
        ->has(TicketType::factory()->count(count($prices))->sequence(...array_map(fn (int $price) => ['price' => $price], $prices)))
        ->create();

    $this->get(route('events.index'))
        ->assertOk()
        ->assertSeeInOrder([$event->title, $expectedLabel]);
})->with([
    'lowest price is shown with 〜' => [[3000, 1500], '1,500円〜'],
    'free when the cheapest is 0 yen' => [[0, 1000], '無料'],
    'dash when there are no ticket types' => [[], '─'],
]);

test('price label is computed even when the cheapest price was not preloaded', function (array $prices, string $expectedLabel) {
    $event = Event::factory()
        ->has(TicketType::factory()->count(count($prices))->sequence(...array_map(fn (int $price) => ['price' => $price], $prices)))
        ->create();

    expect(Event::query()->findOrFail($event->id)->priceLabel())->toBe($expectedLabel);
})->with([
    'lowest price is shown with 〜' => [[3000, 1500], '1,500円〜'],
    'free when the cheapest is 0 yen' => [[0, 1000], '無料'],
    'dash when there are no ticket types' => [[], '─'],
]);

test('event list can be searched by title or venue', function (string $search, array $visibleTitles, array $hiddenTitles) {
    Event::factory()->create(['title' => '朝焼けトレッキング', 'venue' => '岩手山 馬返し登山口']);
    Event::factory()->create(['title' => '雪上ヨガ体験', 'venue' => '八幡平 森のホール']);

    Livewire::test('pages::events.index')
        ->set('search', $search)
        ->assertSee($visibleTitles)
        ->assertDontSee($hiddenTitles);
})->with([
    'matches the title' => ['トレッキング', ['朝焼けトレッキング'], ['雪上ヨガ体験']],
    'matches the venue' => ['森のホール', ['雪上ヨガ体験'], ['朝焼けトレッキング']],
    'ignores surrounding spaces' => ['  ヨガ  ', ['雪上ヨガ体験'], ['朝焼けトレッキング']],
    'shows everything when empty' => ['', ['朝焼けトレッキング', '雪上ヨガ体験'], []],
]);

test('event list explains when no event matches the search', function () {
    Event::factory()->create(['title' => '朝焼けトレッキング']);

    Livewire::test('pages::events.index')
        ->set('search', '存在しない語')
        ->assertDontSee('朝焼けトレッキング')
        ->assertSee('条件に合うイベントはありません。');
});

test('event list treats wildcard characters in the search as plain text', function (string $search) {
    Event::factory()->create(['title' => '朝焼けトレッキング']);

    Livewire::test('pages::events.index')
        ->set('search', $search)
        ->assertDontSee('朝焼けトレッキング');
})->with([
    'percent sign' => ['%'],
    'underscore' => ['_'],
]);

test('event list can be searched by guests', function () {
    Event::factory()->create(['title' => '朝焼けトレッキング']);

    $this->get(route('events.index'))->assertOk();

    Livewire::test('pages::events.index')
        ->set('search', 'トレッキング')
        ->assertSee('朝焼けトレッキング');
});
