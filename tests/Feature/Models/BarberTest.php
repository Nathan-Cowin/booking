<?php

use App\Models\Barber;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;

/** Database table */
it('exists', function () {
    expect(Schema::hasTable('barbers'))->toBeTrue();
});

/** create */
it('can create a barber', function () {
    /** @var Barber $barber */
    $barber = Barber::factory()->create();

    assertModelExists($barber);
});

/** update */
it('can update a barber', function () {
    /** @var Barber $barber */
    $barber = Barber::factory()->create();

    $data = Barber::factory()->make()->toArray();

    $barber->update($data);

    $data[$barber->getKeyName()] = $barber->getKey();
    assertDatabaseHas($barber->getTable(), $data);
});

/** delete */
it('can delete a barber', function () {
    /** @var Barber $barber */
    $barber = Barber::factory()->create();
    $barber->delete();

    assertModelMissing($barber);
});
