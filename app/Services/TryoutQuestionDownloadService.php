<?php

namespace App\Services;

use App\Models\Tryout;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TryoutQuestionDownloadService
{
    /**
     * Generate a PDF containing questions from one or more tryout subtests.
     *
     * @param Collection<int, \App\Models\Question> $questions
     */
    public function download(Tryout $tryout, Collection $questions): Response
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('admin.pages.tryout.question-download', [
            'tryout' => $tryout,
            'questions' => $questions,
        ])->render());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.Str::slug($tryout->name).'-soal.pdf"',
        ]);
    }
}
