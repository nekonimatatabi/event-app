<?php

use App\Models\Event;
use App\Models\User;
use Livewire\Livewire;

test('organizer can create an event and is redirected to the list', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::events.create')
        ->set('title', '朝焼けトレッキング')
        ->set('description', '岩手山に登ります。')
        ->set('venue', '馬返し登山口')
        ->set('starts_at', '2030-05-01T05:00')
        ->set('ends_at', '2030-05-01T11:00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('events.index'))
        ->assertSessionHas('status', 'イベントを登録しました。');

    expect(Event::sole())
        ->user_id->toBe($user->id)
        ->title->toBe('朝焼けトレッキング')
        ->description->toBe('岩手山に登ります。')
        ->venue->toBe('馬返し登山口')
        ->starts_at->format('Y-m-d H:i')->toBe('2030-05-01 05:00')
        ->ends_at->format('Y-m-d H:i')->toBe('2030-05-01 11:00');
});

test('guests are redirected to login when opening the create page', function () {
    $this->get(route('events.create'))
        ->assertRedirect(route('login'));

    expect(Event::count())->toBe(0);
});

test('unverified users cannot open the create page', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('events.create'))
        ->assertRedirect(route('verification.notice'));

    expect(Event::count())->toBe(0);
});

test('event creation input is validated', function (array $input, string $field, string $rule) {
    $this->actingAs(User::factory()->create());

    $valid = [
        'title' => 'タイトル',
        'description' => '説明',
        'venue' => '会場',
        'starts_at' => '2030-05-01T05:00',
        'ends_at' => '2030-05-01T11:00',
    ];

    Livewire::test('pages::events.create')
        ->set(array_merge($valid, $input))
        ->call('save')
        ->assertHasErrors([$field => $rule])
        ->assertNoRedirect();

    expect(Event::count())->toBe(0);
})->with([
    'title is empty' => [['title' => ''], 'title', 'required'],
    'title is 101 characters' => [['title' => str_repeat('あ', 101)], 'title', 'max'],
    'description is empty' => [['description' => ''], 'description', 'required'],
    'description is 2001 characters' => [['description' => str_repeat('あ', 2001)], 'description', 'max'],
    'venue is empty' => [['venue' => ''], 'venue', 'required'],
    'venue is 101 characters' => [['venue' => str_repeat('あ', 101)], 'venue', 'max'],
    'starts_at is empty' => [['starts_at' => ''], 'starts_at', 'required'],
    'starts_at is not a date' => [['starts_at' => 'abc'], 'starts_at', 'date'],
    'ends_at is empty' => [['ends_at' => ''], 'ends_at', 'required'],
    'ends_at is before starts_at' => [['ends_at' => '2030-05-01T04:00'], 'ends_at', 'after'],
]);
