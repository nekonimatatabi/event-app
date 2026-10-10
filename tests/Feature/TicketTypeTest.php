<?php

use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Livewire\Livewire;

test('organizer can add a ticket type to their event', function () {
    $event = Event::factory()->create();

    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->set('ticketName', '一般')
        ->set('ticketPrice', '3000')
        ->set('ticketCapacity', '50')
        ->call('addTicketType')
        ->assertHasNoErrors()
        ->assertSet('ticketName', '')
        ->assertSee('3,000円');

    expect($event->ticketTypes()->sole())
        ->name->toBe('一般')
        ->price->toBe(3000)
        ->capacity->toBe(50);
});

test('edit page lists the ticket types of the event', function () {
    $event = Event::factory()
        ->has(TicketType::factory()->state(['name' => '学生', 'price' => 1500]))
        ->create();

    $this->actingAs($event->user)
        ->get(route('events.edit', $event))
        ->assertOk()
        ->assertSee('学生')
        ->assertSee('1,500円');
});

test('ticket type input is validated', function (array $input, string $field, string $rule) {
    $event = Event::factory()->create();

    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->set(array_merge(['ticketName' => '一般', 'ticketPrice' => '1000', 'ticketCapacity' => '10'], $input))
        ->call('addTicketType')
        ->assertHasErrors([$field => $rule]);

    expect($event->ticketTypes()->count())->toBe(0);
})->with([
    'name is empty' => [['ticketName' => ''], 'ticketName', 'required'],
    'price is negative' => [['ticketPrice' => '-1'], 'ticketPrice', 'min'],
    'capacity is zero' => [['ticketCapacity' => '0'], 'ticketCapacity', 'min'],
]);

test('free ticket type with price 0 is allowed', function () {
    $event = Event::factory()->create();

    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->set(['ticketName' => '招待', 'ticketPrice' => '0', 'ticketCapacity' => '5'])
        ->call('addTicketType')
        ->assertHasNoErrors();

    expect($event->ticketTypes()->sole()->price)->toBe(0);
});

test('other users cannot open the edit page to add ticket types', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('events.edit', $event))
        ->assertForbidden();

    expect($event->ticketTypes()->count())->toBe(0);
});

test('organizer can delete a ticket type of their event', function () {
    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->for($event)->create(['name' => '学生']);

    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->call('deleteTicketType', $ticketType->id)
        ->assertDontSee('学生');

    $this->assertModelMissing($ticketType);
});

test('ticket types of another event cannot be deleted', function () {
    $event = Event::factory()->create();
    $otherTicketType = TicketType::factory()->create();

    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->call('deleteTicketType', $otherTicketType->id)
        ->assertNotFound();

    $this->assertModelExists($otherTicketType);
});

test('deleting an event deletes its ticket types', function () {
    $event = Event::factory()->has(TicketType::factory()->count(2))->create();

    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->call('delete');

    expect(TicketType::count())->toBe(0);
});
