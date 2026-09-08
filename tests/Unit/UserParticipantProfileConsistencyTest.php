<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserParticipantProfileConsistencyTest extends TestCase
{
    public function test_date_of_birth_alias_reads_and_writes_the_admin_birthday_column(): void
    {
        $user = new User(['date_of_birth' => '2004-02-12']);

        $this->assertSame('2004-02-12', $user->birthday?->format('Y-m-d'));
        $this->assertSame('2004-02-12', $user->date_of_birth?->format('Y-m-d'));
    }

    public function test_two_legacy_major_choices_are_available_to_every_portal_display(): void
    {
        $user = new User;
        $user->setRawAttributes([
            'major_choice_1' => 'Teknik Informatika',
            'major_choice_2' => 'Sistem Informasi',
        ]);
        $user->setRelation('participantDestinationCategory', null);
        $user->setRelation('secondParticipantDestinationCategory', null);

        $this->assertSame([
            ['label' => 'Pilihan 1', 'value' => 'Teknik Informatika'],
            ['label' => 'Pilihan 2', 'value' => 'Sistem Informasi'],
        ], $user->leaderboard_major_choices);
    }
}
