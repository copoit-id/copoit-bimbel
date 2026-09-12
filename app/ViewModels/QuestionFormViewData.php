<?php

namespace App\ViewModels;

use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionBankQuestion;
use App\Models\Tryout;
use App\Models\TryoutDetail;

final class QuestionFormViewData
{
    /** @return array<string, mixed> */
    public function forTryout(Tryout $tryout, TryoutDetail $tryoutDetail, ?Question $question = null): array
    {
        $isEditing = $question !== null;

        return [
            'tryout' => $tryout,
            'tryout_detail' => $tryoutDetail,
            'question' => $question,
            'questionOptions' => $question?->questionOptions ?? collect(),
            'questionForm' => [
                'layout' => 'admin.layout.admin',
                'title' => $isEditing ? 'Edit Soal' : 'Tambah Soal',
                'pageTitle' => sprintf('%s - %s', $isEditing ? 'Edit Soal' : 'Tambah Soal', $tryout->name),
                'description' => sprintf('Subtest: %s • Durasi: %s menit', $tryoutDetail->display_name, $tryoutDetail->duration),
                'breadcrumbs' => [
                    ['url' => route('admin.tryout.index'), 'title' => 'Manajemen Tryout'],
                    ['url' => route('admin.question.index', $tryoutDetail->tryout_detail_id), 'title' => 'Soal'],
                    ['url' => null, 'title' => $isEditing ? 'Edit Soal' : 'Tambah Soal'],
                ],
                'action' => $isEditing
                    ? route('admin.question.update', [$tryoutDetail->tryout_detail_id, $question->question_id])
                    : route('admin.question.store', $tryoutDetail->tryout_detail_id),
                'method' => $isEditing ? 'PUT' : 'POST',
                'cancelUrl' => route('admin.question.index', $tryoutDetail->tryout_detail_id),
                'importTarget' => null,
                'subtestType' => $tryoutDetail->type_subtest,
                'isToefl' => $tryout->is_toefl,
                'defaultWeight' => $tryoutDetail->default_weight ?? 1,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function forQuestionBank(QuestionBank $bank, ?QuestionBankQuestion $question, ?int $importTarget, bool $isTutor): array
    {
        $isEditing = $question !== null;

        return [
            'bank' => $bank,
            'question' => $question,
            'questionOptions' => $question?->options ?? collect(),
            'questionForm' => [
                'layout' => $isTutor ? 'tutor.question-bank-layout' : 'admin.layout.admin',
                'title' => $isEditing ? 'Edit Soal Bank' : 'Tambah Soal Bank',
                'pageTitle' => sprintf('%s - %s', $isEditing ? 'Edit Soal' : 'Tambah Soal', $bank->name),
                'description' => $isEditing
                    ? 'Perbarui soal agar tetap relevan sebelum digunakan di tryout.'
                    : 'Simpan soal ke bank agar bisa digunakan kembali saat menyusun tryout.',
                'breadcrumbs' => [
                    ['url' => route('admin.question-bank.index'), 'title' => 'Bank Soal'],
                    ['url' => route('admin.question-bank.show', $bank->id), 'title' => $bank->name],
                    ['url' => null, 'title' => $isEditing ? 'Edit Soal' : 'Tambah Soal'],
                ],
                'action' => $isEditing
                    ? route('admin.question-bank.questions.update', $question->id)
                    : route('admin.question-bank.questions.store', $bank->id),
                'method' => $isEditing ? 'PUT' : 'POST',
                'cancelUrl' => route('admin.question-bank.show', $bank->id),
                'importTarget' => $importTarget,
                'subtestType' => 'general',
                'isToefl' => false,
                'defaultWeight' => $question?->default_weight ?? 1,
            ],
        ];
    }
}
