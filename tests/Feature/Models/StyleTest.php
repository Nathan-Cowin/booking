<?php

use App\Models\Style;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;

/** Database table */
it('exists', function () {
    expect(Schema::hasTable('styles'))->toBeTrue();
});

/** create */
it('can create a style', function () {
    /** @var Style $style */
    $style = Style::factory()->create();

    assertModelExists($style);
});

/** update */
it('can update a style', function () {
    /** @var Style $style */
    $style = Style::factory()->create();

    $data = Style::factory()->make()->toArray();

    $style->update($data);

    $data[$style->getKeyName()] = $style->getKey();
    assertDatabaseHas($style->getTable(), $data);
});

/** delete */
it('can delete a style', function () {
    /** @var Style $style */
    $style = Style::factory()->create();
    $style->delete();

    assertModelMissing($style);
});
