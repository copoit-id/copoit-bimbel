<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $feature = 'ai_question_generator';
        $label = 'Generate Soal AI';
        $actions = array_keys(config('permissions.actions', []));

        foreach ($actions as $action) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $feature . '.' . $action],
                [
                    'name' => $label . ' - ' . Str::headline($action),
                    'feature' => $feature,
                    'action' => $action,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->where('feature', 'ai_question_generator')
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
};
