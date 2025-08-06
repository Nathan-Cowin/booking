<?php

use App\Models\Service;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;

/** Database table */
it('exists', function () {
    expect(Schema::hasTable('services'))->toBeTrue();
});

/** create */
it('can create a service', function () {
    /** @var Service $service */
    $service = Service::factory()->create();

    assertModelExists($service);
});

/** update */
it('can update a service', function () {
    /** @var Service $service */
    $service = Service::factory()->create();

    $data = Service::factory()->make()->toArray();

    $service->update($data);

    $data[$service->getKeyName()] = $service->getKey();
    assertDatabaseHas($service->getTable(), $data);
});

/** delete */
it('can delete a service', function () {
    /** @var Service $service */
    $service = Service::factory()->create();
    $service->delete();

    assertModelMissing($service);
});
