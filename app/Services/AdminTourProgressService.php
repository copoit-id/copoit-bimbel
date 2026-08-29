<?php

namespace App\Services;

use App\Models\AdminTourProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AdminTourProgressService
{
    /**
     * @param  array<string, mixed>  $tour
     */
    public function start(User $user, array $tour): AdminTourProgress
    {
        return DB::transaction(function () use ($user, $tour): AdminTourProgress {
            $firstStep = $tour['steps'][0]['id'];
            $progress = AdminTourProgress::query()->firstOrNew([
                'user_id' => $user->id,
                'tour_key' => $tour['key'],
                'tour_version' => $tour['version'],
            ]);

            if (! $progress->exists || in_array($progress->status, ['completed', 'skipped', 'dismissed'], true)) {
                $progress->fill([
                    'status' => 'in_progress',
                    'current_step_id' => $firstStep,
                    'completed_at' => null,
                    'skipped_at' => null,
                    'metadata' => null,
                ])->save();
            }

            return $progress;
        });
    }

    /**
     * Explicitly start a fresh run from the first step.
     *
     * This is intentionally separate from start(), which is also used while
     * resuming and completing a step.
     *
     * @param  array<string, mixed>  $tour
     */
    public function restart(User $user, array $tour): AdminTourProgress
    {
        return DB::transaction(function () use ($user, $tour): AdminTourProgress {
            return AdminTourProgress::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'tour_key' => $tour['key'],
                    'tour_version' => $tour['version'],
                ],
                [
                    'status' => 'in_progress',
                    'current_step_id' => $tour['steps'][0]['id'],
                    'completed_at' => null,
                    'skipped_at' => null,
                    'metadata' => null,
                ],
            );
        });
    }

    /**
     * @param  array<string, mixed>  $tour
     */
    public function completeStep(User $user, array $tour, string $stepId): AdminTourProgress
    {
        return DB::transaction(function () use ($user, $tour, $stepId): AdminTourProgress {
            $progress = $this->start($user, $tour);

            if ($progress->current_step_id !== $stepId) {
                throw new ConflictHttpException('Langkah tutor tidak sesuai.');
            }

            $stepIndex = collect($tour['steps'])->search(
                fn (array $step): bool => $step['id'] === $stepId
            );
            if ($stepIndex === false) {
                throw new ConflictHttpException('Langkah tutor tidak ditemukan.');
            }

            $nextStep = $tour['steps'][$stepIndex + 1]['id'] ?? null;
            $progress->forceFill([
                'current_step_id' => $nextStep,
                'status' => $nextStep ? 'in_progress' : 'completed',
                'completed_at' => $nextStep ? null : now(),
                'metadata' => ['last_step_id' => $stepId],
            ])->save();

            return $progress;
        });
    }

    /**
     * @param  array<string, mixed>  $tour
     */
    public function close(User $user, array $tour, string $status): AdminTourProgress
    {
        return DB::transaction(function () use ($user, $tour, $status): AdminTourProgress {
            $progress = $this->start($user, $tour);
            $progress->forceFill([
                'status' => $status,
                'completed_at' => $status === 'completed' ? now() : null,
                'skipped_at' => $status === 'skipped' ? now() : null,
                'metadata' => ['last_step_id' => $progress->current_step_id],
            ])->save();

            return $progress;
        });
    }
}
