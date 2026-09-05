<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureTutorContentOwnership;
use App\Models\Material;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionBankQuestion;
use App\Models\Tryout;
use App\Models\TryoutDetail;
use App\Models\User;
use App\Services\TutorContentVisibilityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TutorContentVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['question_bank_questions', 'question_banks', 'questions', 'tryout_details', 'materials', 'tryouts', 'users', 'client_profile'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('client_profile', function (Blueprint $table): void {
            $table->id();
            $table->boolean('tutor_content_enabled')->default(false);
            $table->string('tutor_content_visibility', 20)->default('shared');
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('role');
        });
        Schema::create('tryouts', function (Blueprint $table): void {
            $table->id('tryout_id');
            $table->string('name');
            $table->unsignedBigInteger('created_by')->nullable();
        });
        Schema::create('materials', function (Blueprint $table): void {
            $table->id('material_id');
            $table->string('title');
            $table->unsignedBigInteger('created_by')->nullable();
        });
        Schema::create('tryout_details', function (Blueprint $table): void {
            $table->id('tryout_detail_id');
            $table->unsignedBigInteger('tryout_id');
        });
        Schema::create('questions', function (Blueprint $table): void {
            $table->id('question_id');
            $table->unsignedBigInteger('tryout_detail_id');
        });
        Schema::create('question_banks', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('created_by')->nullable();
        });
        Schema::create('question_bank_questions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('question_bank_id');
        });
    }

    public function test_tutor_isolation_shares_admin_content_but_hides_other_tutor_content(): void
    {
        DB::table('client_profile')->insert(['tutor_content_enabled' => true, 'tutor_content_visibility' => 'tutor_isolated']);
        DB::table('users')->insert([
            ['id' => 101, 'name' => 'Tutor Satu', 'role' => 'tutor'],
            ['id' => 102, 'name' => 'Tutor Dua', 'role' => 'tutor'],
            ['id' => 103, 'name' => 'Admin', 'role' => 'admin'],
        ]);
        DB::table('tryouts')->insert([
            ['tryout_id' => 1, 'name' => 'Tryout Tutor Satu', 'created_by' => 101],
            ['tryout_id' => 2, 'name' => 'Tryout Tutor Dua', 'created_by' => 102],
            ['tryout_id' => 3, 'name' => 'Tryout Admin', 'created_by' => 103],
        ]);
        DB::table('materials')->insert([
            ['material_id' => 1, 'title' => 'Materi Tutor Satu', 'created_by' => 101],
            ['material_id' => 2, 'title' => 'Materi Tutor Dua', 'created_by' => 102],
            ['material_id' => 3, 'title' => 'Materi Admin', 'created_by' => 103],
        ]);
        DB::table('question_banks')->insert([
            ['id' => 1, 'name' => 'Bank Tutor Satu', 'created_by' => 101],
            ['id' => 2, 'name' => 'Bank Tutor Dua', 'created_by' => 102],
            ['id' => 3, 'name' => 'Bank Admin', 'created_by' => 103],
        ]);
        DB::table('tryout_details')->insert([
            ['tryout_detail_id' => 1, 'tryout_id' => 1],
            ['tryout_detail_id' => 2, 'tryout_id' => 2],
            ['tryout_detail_id' => 3, 'tryout_id' => 3],
        ]);
        DB::table('questions')->insert([
            ['question_id' => 1, 'tryout_detail_id' => 1],
            ['question_id' => 2, 'tryout_detail_id' => 2],
            ['question_id' => 3, 'tryout_detail_id' => 3],
        ]);
        DB::table('question_bank_questions')->insert([
            ['id' => 1, 'question_bank_id' => 1],
            ['id' => 2, 'question_bank_id' => 2],
            ['id' => 3, 'question_bank_id' => 3],
        ]);

        $this->actingAs(User::findOrFail(101));

        $this->assertSame(['Tryout Tutor Satu', 'Tryout Admin'], Tryout::query()->orderBy('tryout_id')->pluck('name')->all());
        $this->assertSame(['Materi Tutor Satu', 'Materi Admin'], Material::query()->orderBy('material_id')->pluck('title')->all());
        $this->assertSame(['Bank Tutor Satu', 'Bank Admin'], QuestionBank::query()->orderBy('id')->pluck('name')->all());
        $this->assertSame([1, 3], TryoutDetail::query()->orderBy('tryout_detail_id')->pluck('tryout_detail_id')->all());
        $this->assertSame([1, 3], Question::query()->orderBy('question_id')->pluck('question_id')->all());
        $this->assertSame([1, 3], QuestionBankQuestion::query()->orderBy('id')->pluck('id')->all());

        $this->actingAs(User::findOrFail(103));

        $this->assertCount(3, Tryout::query()->get());
        $this->assertCount(3, Material::query()->get());
        $this->assertCount(3, QuestionBank::query()->get());
        $this->assertCount(3, TryoutDetail::query()->get());
        $this->assertCount(3, Question::query()->get());
        $this->assertCount(3, QuestionBankQuestion::query()->get());
    }

    public function test_isolation_is_disabled_until_super_admin_enables_the_feature(): void
    {
        DB::table('client_profile')->insert(['tutor_content_enabled' => false, 'tutor_content_visibility' => 'isolated']);
        DB::table('users')->insert([
            ['id' => 101, 'name' => 'Tutor Satu', 'role' => 'tutor'],
            ['id' => 102, 'name' => 'Tutor Dua', 'role' => 'tutor'],
        ]);
        DB::table('tryouts')->insert([
            ['tryout_id' => 1, 'name' => 'Tryout Tutor Satu', 'created_by' => 101],
            ['tryout_id' => 2, 'name' => 'Tryout Tutor Dua', 'created_by' => 102],
        ]);

        $this->actingAs(User::findOrFail(101));

        $this->assertCount(2, Tryout::query()->get());
    }

    public function test_full_isolation_hides_admin_and_other_tutor_content(): void
    {
        DB::table('client_profile')->insert(['tutor_content_enabled' => true, 'tutor_content_visibility' => 'isolated']);
        DB::table('users')->insert([
            ['id' => 101, 'name' => 'Tutor Satu', 'role' => 'tutor'],
            ['id' => 102, 'name' => 'Tutor Dua', 'role' => 'tutor'],
            ['id' => 103, 'name' => 'Admin', 'role' => 'admin'],
        ]);
        DB::table('question_banks')->insert([
            ['id' => 1, 'name' => 'Bank Tutor Satu', 'created_by' => 101],
            ['id' => 2, 'name' => 'Bank Tutor Dua', 'created_by' => 102],
            ['id' => 3, 'name' => 'Bank Admin', 'created_by' => 103],
        ]);

        $this->actingAs(User::findOrFail(101));

        $this->assertSame(['Bank Tutor Satu'], QuestionBank::query()->pluck('name')->all());
    }

    public function test_direct_content_route_is_rejected_when_the_content_belongs_to_another_tutor(): void
    {
        DB::table('client_profile')->insert(['tutor_content_enabled' => true, 'tutor_content_visibility' => 'tutor_isolated']);
        DB::table('users')->insert([
            ['id' => 101, 'name' => 'Tutor Satu', 'role' => 'tutor'],
            ['id' => 102, 'name' => 'Tutor Dua', 'role' => 'tutor'],
            ['id' => 103, 'name' => 'Admin', 'role' => 'admin'],
        ]);
        DB::table('tryouts')->insert([
            ['tryout_id' => 1, 'name' => 'Tryout Tutor Dua', 'created_by' => 102],
            ['tryout_id' => 2, 'name' => 'Tryout Admin', 'created_by' => 103],
        ]);

        $request = Request::create('/tutor/tryout/1/edit');
        $request->setUserResolver(fn (): User => User::findOrFail(101));
        $route = new Route(['GET'], '/tutor/tryout/{tryout}', []);
        $route->bind($request);
        $route->setParameter('tryout', Tryout::withoutGlobalScopes()->findOrFail(1));
        $request->setRouteResolver(fn (): Route => $route);

        $this->expectException(HttpException::class);

        app(EnsureTutorContentOwnership::class)->handle($request, fn () => response()->noContent());
    }

    public function test_direct_content_route_allows_content_owned_by_an_admin(): void
    {
        DB::table('client_profile')->insert(['tutor_content_enabled' => true, 'tutor_content_visibility' => 'tutor_isolated']);
        DB::table('users')->insert([
            ['id' => 101, 'name' => 'Tutor Satu', 'role' => 'tutor'],
            ['id' => 103, 'name' => 'Admin', 'role' => 'admin'],
        ]);
        DB::table('tryouts')->insert([
            ['tryout_id' => 2, 'name' => 'Tryout Admin', 'created_by' => 103],
        ]);

        $request = Request::create('/tutor/tryout/2/edit');
        $request->setUserResolver(fn (): User => User::findOrFail(101));
        $route = new Route(['GET'], '/tutor/tryout/{tryout}', []);
        $route->bind($request);
        $route->setParameter('tryout', Tryout::withoutGlobalScopes()->findOrFail(2));
        $request->setRouteResolver(fn (): Route => $route);

        $response = app(EnsureTutorContentOwnership::class)->handle($request, fn () => response()->noContent());

        $this->assertSame(204, $response->getStatusCode());
    }

    public function test_tutor_cannot_delete_an_admin_owned_question_bank_in_tutor_isolation_mode(): void
    {
        DB::table('client_profile')->insert(['tutor_content_enabled' => true, 'tutor_content_visibility' => 'tutor_isolated']);
        DB::table('users')->insert([
            ['id' => 101, 'name' => 'Tutor Satu', 'role' => 'tutor'],
            ['id' => 103, 'name' => 'Admin', 'role' => 'admin'],
        ]);

        $visibility = app(TutorContentVisibilityService::class);

        $this->assertTrue($visibility->canDeleteContentOwnedBy(101, User::findOrFail(101)));
        $this->assertFalse($visibility->canDeleteContentOwnedBy(103, User::findOrFail(101)));
    }
}
