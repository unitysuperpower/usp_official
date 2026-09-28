<?php

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->create(['is_admin' => true]));
});

test('admin resources paginate and search records beyond the first page', function (string $kind, string $model, string $field) {
    $extra = [];
    if ($model === Service::class) {
        $extra = ['category_id' => ServiceCategory::create(['name' => 'Special category', 'slug' => 'special'])->id, 'description' => 'Content', 'price' => 100, 'price_unit' => 'project'];
    } elseif ($model === Blog::class) {
        $extra = ['category_id' => BlogCategory::create(['name' => 'Special category', 'slug' => 'special'])->id, 'user_id' => auth()->id(), 'content' => 'Content'];
    }
    $target = $model::create([$field => 'Needle', 'slug' => 'needle'] + $extra);
    for ($i = 0; $i < 24; $i++) {
        $model::create([$field => 'Other '.$i, 'slug' => 'other-'.$i] + $extra);
    }
    $key = str_contains($kind, 'categories') ? 'categories' : $kind;
    $this->get('/admin/'.$kind)->assertOk()->assertViewHas('props', fn ($p) => count($p[$key]['data']) === 20 && $p[$key]['total'] === 25);
    $this->get('/admin/'.$kind.'?search=Needle')->assertOk()->assertViewHas('props', fn ($p) => $p[$key]['total'] === 1 && $p[$key]['data'][0]['id'] === $target->id);
    $this->get('/admin/'.$kind.'?search=missing')->assertOk()->assertViewHas('props', fn ($p) => $p[$key]['total'] === 0);
    $this->getJson('/admin/'.$kind.'?search[]=invalid')->assertUnprocessable();
    if ($extra) {
        $this->get('/admin/'.$kind.'?search=Special')->assertOk()->assertViewHas('props', fn ($p) => $p[$key]['total'] === 25 && str_contains($p[$key]['next_page_url'], 'search=Special'));
    }
})->with([
    ['services', Service::class, 'title'],
    ['blogs', Blog::class, 'title'],
    ['categories', ServiceCategory::class, 'name'],
    ['blog-categories', BlogCategory::class, 'name'],
]);
