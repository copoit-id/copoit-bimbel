<?php

namespace Tests\Unit;

use App\Http\Controllers\admin\TryoutController;
use App\Models\MaterialCategory;
use App\Models\Tryout;
use App\Models\TryoutDetail;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RunClassInSeparateProcess;
use Tests\TestCase;

#[RunClassInSeparateProcess]
class DynamicTryoutCategorySubtestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('material_categories', function (Blueprint $table): void {
            $table->id('category_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order_number')->default(0);
            $table->timestamps();
        });

        Schema::create('tryouts', function (Blueprint $table): void {
            $table->id('tryout_id');
            $table->string('name');
            $table->string('type_tryout');
            $table->timestamps();
        });

        Schema::create('tryout_details', function (Blueprint $table): void {
            $table->id('tryout_detail_id');
            $table->unsignedBigInteger('tryout_id');
            $table->string('type_subtest');
            $table->unsignedBigInteger('material_category_id')->nullable();
            $table->unsignedInteger('duration');
            $table->decimal('passing_score', 8, 2);
            $table->string('passing_type');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('tryout_details');
        Schema::dropIfExists('tryouts');
        Schema::dropIfExists('material_categories');
        \Illuminate\Database\Eloquent\Model::clearBootedModels();

        parent::tearDown();
    }

    public function test_custom_parent_category_creates_one_subtest_per_active_child(): void
    {
        $parent = MaterialCategory::create([
            'name' => 'Tes Kustom',
            'code' => 'tes_kustom',
            'is_active' => true,
        ]);

        $children = collect(['Subtest Satu', 'Subtest Dua', 'Subtest Tiga'])
            ->map(fn (string $name, int $index) => MaterialCategory::create([
                'parent_id' => $parent->category_id,
                'name' => $name,
                'code' => 'subtest_'.($index + 1),
                'is_active' => true,
                'order_number' => $index + 1,
            ]));

        $tryout = Tryout::create([
            'name' => 'Tryout Kustom',
            'type_tryout' => $parent->code,
        ]);
        $request = Request::create('/', 'POST', [
            'duration_general' => 45,
            'passing_score_general' => 70,
            'passing_type_general' => 'score',
        ]);

        $method = new \ReflectionMethod(TryoutController::class, 'createTryoutDetails');
        $method->invoke(app(TryoutController::class), $tryout, $request);

        $details = TryoutDetail::query()->orderBy('type_subtest')->get();

        $this->assertCount(3, $details);
        $this->assertSame($children->pluck('category_id')->sort()->values()->all(), $details->pluck('material_category_id')->sort()->values()->all());
        $this->assertTrue($details->every(fn (TryoutDetail $detail) => $detail->duration === 45));
    }
}
