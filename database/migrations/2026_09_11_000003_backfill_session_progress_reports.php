<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_progress_reports') || ! Schema::hasColumn('student_progress_reports', 'class_session_id')) {
            return;
        }

        $handledSessionIds = [];
        $legacyReports = DB::table('student_progress_reports')
            ->select(['id', 'tentor_id', 'study_group_id', 'class_session_id', 'period_start', 'period_end', 'summary', 'created_at', 'updated_at'])
            ->whereNotNull('class_session_id')
            ->whereNotNull('user_id')
            ->orderBy('class_session_id')
            ->orderByDesc('created_at')
            ->cursor();

        foreach ($legacyReports as $report) {
            if (isset($handledSessionIds[$report->class_session_id])) {
                continue;
            }
            $handledSessionIds[$report->class_session_id] = true;

            $exists = DB::table('student_progress_reports')
                ->where('class_session_id', $report->class_session_id)
                ->whereNull('user_id')
                ->exists();
            if ($exists) {
                continue;
            }

            DB::table('student_progress_reports')->insert([
                'tentor_id' => $report->tentor_id,
                'user_id' => null,
                'package_id' => null,
                'study_group_id' => $report->study_group_id,
                'user_package_access_id' => null,
                'class_session_id' => $report->class_session_id,
                'period_start' => $report->period_start,
                'period_end' => $report->period_end,
                'progress_percent' => null,
                'mastery_score' => null,
                'discipline_score' => null,
                'participation_score' => null,
                'summary' => $report->summary,
                'strengths' => null,
                'improvements' => null,
                'next_target' => null,
                'created_at' => $report->created_at,
                'updated_at' => $report->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('student_progress_reports')) {
            return;
        }

        DB::table('student_progress_reports')
            ->whereNotNull('class_session_id')
            ->whereNull('user_id')
            ->whereNull('package_id')
            ->delete();
    }
};
