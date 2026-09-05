<?php

namespace Tests\Unit;

use App\Models\ParticipantDestinationCategory;
use App\Models\User;
use Tests\TestCase;

class UserLeaderboardMajorChoicesTest extends TestCase
{
    public function test_current_destination_choices_are_displayed_before_legacy_major_choices(): void
    {
        $institution = new ParticipantDestinationCategory(['name' => 'Universitas Indonesia']);
        $program = new ParticipantDestinationCategory(['name' => 'Ilmu Komputer']);
        $program->setRelation('parent', $institution);

        $user = new User([
            'major_choice_1' => 'Pilihan Lama',
            'major_choice_2' => 'Pilihan Lama Kedua',
        ]);
        $user->setRelation('participantDestinationCategory', $program);
        $user->setRelation('secondParticipantDestinationCategory', null);
        $user->setAttribute('second_participant_destination_institution_name', 'Institut Teknologi Bandung');
        $user->setAttribute('second_participant_destination_program_name', 'Teknik Informatika');

        $this->assertSame(
            'Universitas Indonesia - Ilmu Komputer / Institut Teknologi Bandung - Teknik Informatika',
            $user->leaderboard_major_choices_display
        );
    }

    public function test_legacy_major_choices_are_kept_when_no_destination_has_been_selected(): void
    {
        $user = new User([
            'major_choice_1' => 'Psikologi',
            'major_choice_2' => 'Manajemen',
        ]);
        $user->setRelation('participantDestinationCategory', null);
        $user->setRelation('secondParticipantDestinationCategory', null);

        $this->assertSame('Psikologi / Manajemen', $user->leaderboard_major_choices_display);
    }
}
