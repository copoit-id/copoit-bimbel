<?php

namespace Tests\Unit;

use App\Models\MaterialCategory;
use App\Models\TryoutDetail;
use Tests\TestCase;

class TryoutDetailDisplayNameTest extends TestCase
{
    public function test_category_name_is_used_as_the_subtest_display_name(): void
    {
        $detail = new TryoutDetail(['type_subtest' => 'kemampuan_verbal']);
        $detail->setRelation('materialCategory', new MaterialCategory([
            'name' => 'Kemampuan Verbal Lanjutan',
        ]));

        $this->assertSame('Kemampuan Verbal Lanjutan', $detail->display_name);
        $this->assertSame('Kemampuan Verbal Lanjutan', $detail->short_display_name);
        $this->assertSame('KVL', $detail->display_abbreviation);
    }

    public function test_legacy_subtest_code_has_a_readable_fallback_name(): void
    {
        $detail = new TryoutDetail(['type_subtest' => 'penalaran_umum']);
        $detail->setRelation('materialCategory', null);

        $this->assertSame('Penalaran Umum', $detail->display_name);
    }

    public function test_abbreviation_is_generated_from_the_current_category_name(): void
    {
        $detail = new TryoutDetail(['type_subtest' => 'tes_wawasan_kebangsaan_test']);
        $detail->setRelation('materialCategory', new MaterialCategory([
            'name' => 'Tes Wawasan Kebangsaan Test',
        ]));

        $this->assertSame('TWKT', $detail->display_abbreviation);
    }
}
