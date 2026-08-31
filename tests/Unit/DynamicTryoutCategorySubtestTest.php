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

        Schema::create('questions', function (Blueprint $table): void {
            $table->id('question_id');
            $table->unsignedBigInteger('tryout_detail_id');
            $table->timestamps();
        });

        Schema::create('user_answers', function (Blueprint $table): void {
            $table->id('user_answer_id');
            $table->unsignedBigInteger('tryout_detail_id');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('user_answers');
        Schema::dropIfExists('questions');
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
            'duration_subtest_1' => 35,
            'duration_subtest_2' => 45,
            'duration_subtest_3' => 55,
            'passing_score_subtest_1' => 65,
            'passing_score_subtest_2' => 70,
            'passing_score_subtest_3' => 75,
            'passing_type_subtest_1' => 'score',
            'passing_type_subtest_2' => 'percentage',
            'passing_type_subtest_3' => 'score',
        ]);

        $method = new \ReflectionMethod(TryoutController::class, 'createTryoutDetails');
        $method->invoke(app(TryoutController::class), $tryout, $request);

        $details = TryoutDetail::query()->orderBy('type_subtest')->get();

        $this->assertCount(3, $details);
        $this->assertSame($children->pluck('category_id')->sort()->values()->all(), $details->pluck('material_category_id')->sort()->values()->all());
        $this->assertSame([35, 45, 55], $details->pluck('duration')->all());
        $this->assertSame(['score', 'percentage', 'score'], $details->pluck('passing_type')->all());

        $updateRequest = Request::create('/', 'POST', [
            'duration_subtest_1' => 40,
            'duration_subtest_2' => 50,
            'duration_subtest_3' => 60,
            'passing_score_subtest_1' => 66,
            'passing_score_subtest_2' => 71,
            'passing_score_subtest_3' => 76,
            'passing_type_subtest_1' => 'percentage',
            'passing_type_subtest_2' => 'score',
            'passing_type_subtest_3' => 'percentage',
        ]);
        $updateMethod = new \ReflectionMethod(TryoutController::class, 'updateTryoutDetails');
        $updateMethod->invoke(app(TryoutController::class), $tryout, $updateRequest);

        $updatedDetails = TryoutDetail::query()->orderBy('type_subtest')->get();

        $this->assertSame([40, 50, 60], $updatedDetails->pluck('duration')->all());
        $this->assertSame(['percentage', 'score', 'percentage'], $updatedDetails->pluck('passing_type')->all());
    }

    public function test_tbi_uses_its_active_material_categories_as_dynamic_subtests(): void
    {
        $parent = MaterialCategory::create([
            'name' => 'TBI',
            'code' => 'tbi',
            'is_active' => true,
        ]);

        $firstChild = MaterialCategory::create([
            'parent_id' => $parent->category_id,
            'name' => 'Structure and Written Expression',
            'code' => 'structure_written_expression',
            'is_active' => true,
            'order_number' => 1,
        ]);
        $secondChild = MaterialCategory::create([
            'parent_id' => $parent->category_id,
            'name' => 'Reading Comprehension',
            'code' => 'reading_comprehension',
            'is_active' => true,
            'order_number' => 2,
        ]);

        $controller = app(TryoutController::class);
        $optionsMethod = new \ReflectionMethod(TryoutController::class, 'getTryoutTypeOptions');
        $options = $optionsMethod->invoke($controller);

        $this->assertSame('TBI (2 Subtest)', $options['tbi']['label']);
        $this->assertSame([
            ['code' => $firstChild->code, 'name' => $firstChild->name],
            ['code' => $secondChild->code, 'name' => $secondChild->name],
        ], $options['tbi']['subtests']);
        $this->assertSame('TBI - Structure and Written Expression', $options[$firstChild->code]['label']);
        $this->assertSame('TBI - Reading Comprehension', $options[$secondChild->code]['label']);
        $this->assertSame('full', $options['tbi']['group']);
        $this->assertSame('single', $options[$firstChild->code]['group']);
        $this->assertSame('single', $options[$secondChild->code]['group']);

        $tryout = Tryout::create([
            'name' => 'Tryout TBI',
            'type_tryout' => 'tbi',
        ]);
        $request = Request::create('/', 'POST', [
            'duration_structure_written_expression' => 40,
            'duration_reading_comprehension' => 50,
            'passing_score_structure_written_expression' => 70,
            'passing_score_reading_comprehension' => 75,
        ]);

        $createMethod = new \ReflectionMethod(TryoutController::class, 'createTryoutDetails');
        $createMethod->invoke($controller, $tryout, $request);

        $details = TryoutDetail::query()->orderBy('type_subtest')->get();

        $this->assertCount(2, $details);
        $this->assertSame([
            'reading_comprehension',
            'structure_written_expression',
        ], $details->pluck('type_subtest')->all());
        $this->assertSame([50, 40], $details->pluck('duration')->all());
    }

    public function test_changing_tryout_type_preserves_questions_and_answers_in_the_new_subtest_structure(): void
    {
        $tryout = Tryout::create([
            'name' => 'Tryout Lama',
            'type_tryout' => 'general',
        ]);
        $oldDetail = TryoutDetail::create([
            'tryout_id' => $tryout->tryout_id,
            'type_subtest' => 'general',
            'duration' => 60,
            'passing_score' => 60,
            'passing_type' => 'score',
        ]);

        \DB::table('questions')->insert([
            'tryout_detail_id' => $oldDetail->tryout_detail_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \DB::table('user_answers')->insert([
            'tryout_detail_id' => $oldDetail->tryout_detail_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tryout->update(['type_tryout' => 'skd_full']);

        $method = new \ReflectionMethod(TryoutController::class, 'rebuildTryoutDetailsPreservingContent');
        $method->invoke(app(TryoutController::class), $tryout, Request::create('/', 'POST', [
            'duration_twk' => 35,
            'passing_score_twk' => 65,
            'passing_type_twk' => 'score',
            'duration_tiu' => 90,
            'passing_score_tiu' => 80,
            'passing_type_tiu' => 'score',
            'duration_tkp' => 45,
            'passing_score_tkp' => 166,
            'passing_type_tkp' => 'score',
        ]));

        $newDetail = TryoutDetail::query()->where('type_subtest', 'twk')->sole();

        $this->assertSame('twk', $newDetail->type_subtest);
        $this->assertSame(['tiu', 'tkp', 'twk'], TryoutDetail::query()->orderBy('type_subtest')->pluck('type_subtest')->all());
        $this->assertSame($newDetail->tryout_detail_id, \DB::table('questions')->value('tryout_detail_id'));
        $this->assertSame($newDetail->tryout_detail_id, \DB::table('user_answers')->value('tryout_detail_id'));
    }
}
