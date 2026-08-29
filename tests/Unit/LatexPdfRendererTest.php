<?php

namespace Tests\Unit;

use App\Services\LatexPdfRenderer;
use Dompdf\Dompdf;
use Tests\TestCase;

class LatexPdfRendererTest extends TestCase
{
    public function test_it_replaces_math_tex_markup_with_an_embedded_png(): void
    {
        $rendered = app(LatexPdfRenderer::class)->renderMany([
            'question' => '<p>Nilai <span class="math-tex">\\( \\frac{a}{b} \\)</span></p>',
        ]);

        $this->assertStringContainsString('data:image/png;base64,', $rendered['question']);
        $this->assertStringNotContainsString('\\frac{a}{b}', $rendered['question']);
        $this->assertMatchesRegularExpression('/width: [\d.]+pt; height: [\d.]+pt; vertical-align: -?[\d.]+pt;/', $rendered['question']);
    }

    public function test_embedded_math_png_can_be_rendered_by_dompdf(): void
    {
        $rendered = app(LatexPdfRenderer::class)->renderMany([
            'question' => '<span class="math-tex">\\( x^2 + y^2 \\)</span>',
        ]);
        $dompdf = new Dompdf;
        $dompdf->loadHtml('<html><body>'.$rendered['question'].'</body></html>');
        $dompdf->render();

        $this->assertStringStartsWith('%PDF', $dompdf->output());
    }

    public function test_it_keeps_an_excessively_long_formula_as_text(): void
    {
        $formula = '\\('.str_repeat('x', 1_001).'\\)';
        $rendered = app(LatexPdfRenderer::class)->renderMany([
            'question' => $formula,
        ]);

        $this->assertSame($formula, $rendered['question']);
    }
}
