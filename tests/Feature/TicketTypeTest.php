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

test('guests are redirected to login when opening the edit page', function () {
    $event = Event::factory()->create();

    $this->get(route('events.edit', $event))
        ->assertRedirect(route('login'));

    expect($event->ticketTypes()->count())->toBe(0);
});

test('other users cannot mount the edit component', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->create());

    Livewire::test('pages::events.edit', ['event' => $event])
        ->assertForbidden();

    expect($event->ticketTypes()->count())->toBe(0);
});

test('adding a ticket type is forbidden when the user is not the owner', function () {
    $event = Event::factory()->create();

    $this->actingAs($event->user);
    $component = Livewire::test('pages::events.edit', ['event' => $event])
        ->set(['ticketName' => '一般', 'ticketPrice' => '1000', 'ticketCapacity' => '10']);

    $this->actingAs(User::factory()->create());
    $component->call('addTicketType')->assertForbidden();

    expect($event->ticketTypes()->count())->toBe(0);
});

test('deleting a ticket type is forbidden when the user is not the owner', function () {
    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->for($event)->create();

    $this->actingAs($event->user);
    $component = Livewire::test('pages::events.edit', ['event' => $event]);

    $this->actingAs(User::factory()->create());
    $component->call('deleteTicketType', $ticketType->id)->assertForbidden();

    $this->assertModelExists($ticketType);
});

test('ticket type limits are enforced', function (array $input, string $field, string $rule) {
    $event = Event::factory()->create();

    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->set(array_merge(['ticketName' => '一般', 'ticketPrice' => '1000', 'ticketCapacity' => '10'], $input))
        ->call('addTicketType')
        ->assertHasErrors([$field => $rule]);

    expect($event->ticketTypes()->count())->toBe(0);
})->with([
    'name is 51 characters' => [['ticketName' => str_repeat('あ', 51)], 'ticketName', 'max'],
    'price is empty' => [['ticketPrice' => ''], 'ticketPrice', 'required'],
    'price is not an integer' => [['ticketPrice' => '12.5'], 'ticketPrice', 'integer'],
    'price is not numeric' => [['ticketPrice' => 'abc'], 'ticketPrice', 'integer'],
    'price exceeds 1000000' => [['ticketPrice' => '1000001'], 'ticketPrice', 'max'],
    'capacity is empty' => [['ticketCapacity' => ''], 'ticketCapacity', 'required'],
    'capacity is not an integer' => [['ticketCapacity' => '1.5'], 'ticketCapacity', 'integer'],
    'capacity exceeds 10000' => [['ticketCapacity' => '10001'], 'ticketCapacity', 'max'],
]);

test('ticket type boundary values are accepted', function () {
    $event = Event::factory()->create();

    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->set(['ticketName' => str_repeat('あ', 50), 'ticketPrice' => '1000000', 'ticketCapacity' => '10000'])
        ->call('addTicketType')
        ->assertHasNoErrors();

    expect($event->ticketTypes()->sole())
        ->price->toBe(1000000)
        ->capacity->toBe(10000);
});
