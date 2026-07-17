<?php

namespace Tests\Unit;

use App\Http\Controllers\user\AiLearningToolController;
use App\Models\AiLearningArtifact;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class AiLearningArtifactAuthorizationTest extends TestCase
{
    public function test_user_cannot_save_another_users_note(): void
    {
        $user = new User;
        $user->id = 10;

        $artifact = new AiLearningArtifact;
        $artifact->forceFill([
            'id' => 99,
            'user_id' => 20,
            'tool' => 'note',
        ]);

        $request = Request::create('/user/catatan-ai/99/save', 'POST');
        $request->setUserResolver(fn () => $user);

        $this->expectException(NotFoundHttpException::class);

        (new AiLearningToolController)->save($request, $artifact);
    }
}
