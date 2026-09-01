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
        return $this->validateSelection($request, $required, 'participant_destination');
    }

    public function validateSecond(Request $request, bool $required = false): array
    {
        return $this->validateSelection($request, $required, 'second_participant_destination');
    }

    private function validateSelection(Request $request, bool $required, string $fieldPrefix): array
    {
        $categoryIdField = $fieldPrefix.'_category_id';
        $sourceField = $fieldPrefix.'_source';
        $externalIdField = $fieldPrefix.'_external_id';
        $institutionNameField = $fieldPrefix.'_institution_name';
        $programNameField = $fieldPrefix.'_program_name';

        $validator = Validator::make($request->all(), [
            $categoryIdField => ['nullable', 'integer', 'exists:participant_destination_categories,id'],
            $sourceField => ['nullable', Rule::in(['db', 'snpmb'])],
            $externalIdField => ['nullable', 'string', 'max:100'],
            $institutionNameField => ['nullable', 'string', 'max:255'],
            $programNameField => ['nullable', 'string', 'max:255'],
        ]);

        $validator->after(function ($validator) use ($request, $required, $categoryIdField, $sourceField, $externalIdField, $institutionNameField) {
            $hasDbSelection = $request->filled($categoryIdField);
            $hasOfficialSelection = $request->input($sourceField) === 'snpmb'
                && $request->filled($externalIdField)
                && $request->filled($institutionNameField);

            if ($required && ! $hasDbSelection && ! $hasOfficialSelection) {
                $validator->errors()->add(
                    $categoryIdField,
                    'Pilih instansi/prodi tujuan dari data manual atau data resmi.'
                );
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if ($request->filled($categoryIdField)) {
            return [
                $categoryIdField => $request->integer($categoryIdField),
                $sourceField => 'db',
                $externalIdField => null,
                $institutionNameField => null,
                $programNameField => null,
            ];
        }

        if ($request->input($sourceField) === 'snpmb') {
            return [
                $categoryIdField => null,
                $sourceField => 'snpmb',
                $externalIdField => trim((string) $request->input($externalIdField)),
                $institutionNameField => trim((string) $request->input($institutionNameField)),
                $programNameField => trim((string) $request->input($programNameField)),
            ];
        }

        return [
            $categoryIdField => null,
            $sourceField => null,
            $externalIdField => null,
            $institutionNameField => null,
            $programNameField => null,
        ];
    }

    public function isRequired(): bool
    {
        return ParticipantDestinationCategory::active()->exists();
    }
}
