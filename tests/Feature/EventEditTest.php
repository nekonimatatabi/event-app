<?php

use App\Models\Event;
use App\Models\User;
use Livewire\Livewire;

test('organizer can update their event', function () {
    $event = Event::factory()->create();

    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->set('title', '更新後のタイトル')
        ->set('description', '更新後の説明')
        ->set('venue', '更新後の会場')
        ->set('starts_at', '2031-06-01T10:00')
        ->set('ends_at', '2031-06-01T12:00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('events.index'))
        ->assertSessionHas('status', 'イベントを更新しました。');

    expect($event->fresh())
        ->title->toBe('更新後のタイトル')
        ->description->toBe('更新後の説明')
        ->venue->toBe('更新後の会場')
        ->starts_at->format('Y-m-d H:i')->toBe('2031-06-01 10:00')
        ->ends_at->format('Y-m-d H:i')->toBe('2031-06-01 12:00');
});

test('other users cannot update an event', function () {
    $event = Event::factory()->create(['title' => '元のタイトル']);

    $this->actingAs(User::factory()->create());

    Livewire::test('pages::events.edit', ['event' => $event])
        ->assertForbidden();

    expect($event->fresh()->title)->toBe('元のタイトル');
});

test('save is forbidden when the user is not the owner', function () {
    $event = Event::factory()->create(['title' => '元のタイトル']);

    $this->actingAs($event->user);
    $component = Livewire::test('pages::events.edit', ['event' => $event])
        ->set('title', '乗っ取り');

    $this->actingAs(User::factory()->create());
    $component->call('save')->assertForbidden();

    expect($event->fresh()->title)->toBe('元のタイトル');
});

test('guests are redirected to login when opening the edit page', function () {
    $event = Event::factory()->create();

    $this->get(route('events.edit', $event))
        ->assertRedirect(route('login'));
});

test('event update input is validated', function (array $input, string $field, string $rule) {
    $event = Event::factory()->create([
        'title' => '元のタイトル',
        'starts_at' => '2030-05-01 05:00',
        'ends_at' => '2030-05-01 11:00',
    ]);

    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->set($input)
        ->call('save')
        ->assertHasErrors([$field => $rule])
        ->assertNoRedirect();

    expect($event->fresh())
        ->title->toBe('元のタイトル')
        ->starts_at->format('Y-m-d H:i')->toBe('2030-05-01 05:00')
        ->ends_at->format('Y-m-d H:i')->toBe('2030-05-01 11:00');
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

test('organizer can delete their event', function () {
    $event = Event::factory()->create();

    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->call('delete')
        ->assertRedirect(route('events.index'))
        ->assertSessionHas('status', 'イベントを削除しました。');

    $this->assertModelMissing($event);
});

test('other users cannot delete an event', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->create());

    Livewire::test('pages::events.edit', ['event' => $event])
        ->assertForbidden();

    $this->assertModelExists($event);
});

test('delete is forbidden when the user is not the owner', function () {
    $event = Event::factory()->create();

    $this->actingAs($event->user);
    $component = Livewire::test('pages::events.edit', ['event' => $event]);

    $this->actingAs(User::factory()->create());
    $component->call('delete')->assertForbidden();

    $this->assertModelExists($event);
});
