<?php

namespace App\Services;

use App\Models\ParticipantDestinationCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ParticipantDestinationSelectionService
{
    public function validate(Request $request, bool $required): array
    {
        $validator = Validator::make($request->all(), [
            'participant_destination_category_id' => ['nullable', 'integer', 'exists:participant_destination_categories,id'],
            'participant_destination_source' => ['nullable', Rule::in(['db', 'snpmb'])],
            'participant_destination_external_id' => ['nullable', 'string', 'max:100'],
            'participant_destination_institution_name' => ['nullable', 'string', 'max:255'],
            'participant_destination_program_name' => ['nullable', 'string', 'max:255'],
        ]);

        $validator->after(function ($validator) use ($request, $required) {
            $hasDbSelection = $request->filled('participant_destination_category_id');
            $hasOfficialSelection = $request->input('participant_destination_source') === 'snpmb'
                && $request->filled('participant_destination_external_id')
                && $request->filled('participant_destination_institution_name');

            if ($required && ! $hasDbSelection && ! $hasOfficialSelection) {
                $validator->errors()->add(
                    'participant_destination_category_id',
                    'Pilih instansi/prodi tujuan dari data manual atau data resmi.'
                );
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if ($request->filled('participant_destination_category_id')) {
            return [
                'participant_destination_category_id' => $request->integer('participant_destination_category_id'),
                'participant_destination_source' => 'db',
                'participant_destination_external_id' => null,
                'participant_destination_institution_name' => null,
                'participant_destination_program_name' => null,
            ];
        }

        if ($request->input('participant_destination_source') === 'snpmb') {
            return [
                'participant_destination_category_id' => null,
                'participant_destination_source' => 'snpmb',
                'participant_destination_external_id' => trim((string) $request->input('participant_destination_external_id')),
                'participant_destination_institution_name' => trim((string) $request->input('participant_destination_institution_name')),
                'participant_destination_program_name' => trim((string) $request->input('participant_destination_program_name')),
            ];
        }

        return [
            'participant_destination_category_id' => null,
            'participant_destination_source' => null,
            'participant_destination_external_id' => null,
            'participant_destination_institution_name' => null,
            'participant_destination_program_name' => null,
        ];
    }

    public function isRequired(): bool
    {
        return ParticipantDestinationCategory::active()->exists();
    }
}
