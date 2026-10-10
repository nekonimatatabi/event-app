<?php

use App\Models\Event;
use App\Models\TicketType;

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
