<?php

namespace App\Services;

use App\Models\ClientProfile;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AiQuestionGeneratorService
{
    public function generate(array $input): array
    {
        $discussionUrl = rtrim((string) config('services.ai_gateway.url'), '/');
        $baseUrl = Str::beforeLast($discussionUrl, '/discussion');
        $gatewayKey = trim((string) config('services.ai_gateway.key'));
        $user = Auth::user();

        if ($baseUrl === '' || $gatewayKey === '' || ! $user) {
            throw new RuntimeException('Gateway AI Generator Soal belum dikonfigurasi.');
        }

        try {
            return Http::acceptJson()
                ->asJson()
                ->timeout((int) config('services.ai_gateway.timeout', 120))
                ->withHeaders(['X-AI-Gateway-Key' => $gatewayKey])
                ->post($baseUrl.'/question-generator/generate', [
                    ...$input,
                    'external_user_id' => (string) $user->getAuthIdentifier(),
                    'external_user_name' => (string) $user->name,
                    'external_user_email' => (string) $user->email,
                    'origin_base_url' => rtrim((string) config('app.url'), '/'),
                ])
                ->throw()
                ->json() ?? [];
        } catch (RequestException $exception) {
            $message = $exception->response?->json('message')
                ?: $exception->response?->body()
                ?: $exception->getMessage();

            throw new RuntimeException(Str::limit((string) $message, 300));
        }
    }

    /** Generate only inside the trusted AI Gateway. */
    public function generateDirect(array $input): array
    {
        $model = $input['model'] ?? config('services.openai.question_model', 'gpt-5.4-mini');
        if (! array_key_exists($model, $this->availableModels())) {
            throw new RuntimeException('Model AI tidak aktif atau belum tersedia di pengaturan.');
        }

        $response = str_starts_with($model, 'gemini-')
            ? $this->generateWithGemini($input, $model)
            : $this->generateWithOpenAi($input, $model);

        $decoded = json_decode($response['content'], true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Response AI tidak valid. Silakan coba generate ulang.');
        }

        $questions = $this->normalizeQuestions($decoded['questions'] ?? []);
        if (empty($questions)) {
            throw new RuntimeException('AI tidak mengembalikan soal yang bisa dipakai.');
        }

        return [
            'model' => $model,
            'provider' => $response['provider'],
            'topic' => $input['topic'],
            'subject' => $input['subject'],
            'difficulty' => $input['difficulty'],
            'question_type' => 'multiple_choice',
            'questions' => $questions,
            'usage' => $this->normalizeUsage($response['usage'] ?? []),
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    public function isEnabled(): bool
    {
        return (bool) config('client.branding.ai_question_generator_enabled', false);
    }

    public function availableModels(): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        $settings = $this->settings();
        $models = collect($settings['models'] ?? [])
            ->filter(fn ($model) => ($model['enabled'] ?? true) && filled($model['id'] ?? null))
            ->mapWithKeys(fn ($model) => [
                (string) $model['id'] => (string) ($model['label'] ?? $model['id']),
            ])
            ->all();

        if (! empty($models)) {
            return $models;
        }

        return array_merge(
            config('services.openai.question_models', []),
            config('services.gemini.question_models', [])
        );
    }

    public function defaultModel(): string
    {
        $settings = $this->settings();
        $defaultModel = (string) ($settings['default_model'] ?? config('services.openai.question_model', 'gpt-5.4-mini'));
        $models = $this->availableModels();

        return array_key_exists($defaultModel, $models)
            ? $defaultModel
            : (array_key_first($models) ?: $defaultModel);
    }

    private function generateWithOpenAi(array $input, string $model): array
    {
        $provider = $this->providerSettings('openai');
        $apiKey = $provider['api_key'] ?? config('services.openai.api_key');
        if (! $apiKey) {
            throw new RuntimeException('API key OpenAI belum diatur di pengaturan AI.');
        }

        $payload = $this->buildOpenAiPayload($input, $model);

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout((int) ($provider['timeout'] ?? config('services.openai.timeout', 90)))
                ->post(rtrim((string) ($provider['base_url'] ?? config('services.openai.base_url')), '/').'/responses', $payload)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $message = $exception->response?->json('error.message')
                ?: $exception->response?->body()
                ?: $exception->getMessage();

            throw new RuntimeException('Gagal menghubungi OpenAI: '.Str::limit((string) $message, 300));
        }

        return [
            'provider' => 'openai',
            'content' => $this->extractOpenAiOutputText($response),
            'usage' => $response['usage'] ?? null,
        ];
    }

    private function generateWithGemini(array $input, string $model): array
    {
        $provider = $this->providerSettings('gemini');
        $apiKey = $provider['api_key'] ?? config('services.gemini.api_key');
        if (! $apiKey) {
            throw new RuntimeException('API key Gemini belum diatur di pengaturan AI.');
        }

        $payload = $this->buildGeminiPayload($input);
        $endpoint = rtrim((string) ($provider['base_url'] ?? config('services.gemini.base_url')), '/')
            .'/models/'.rawurlencode($model).':generateContent';

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout((int) ($provider['timeout'] ?? config('services.gemini.timeout', 90)))
                ->withQueryParameters(['key' => $apiKey])
                ->post($endpoint, $payload)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $message = $exception->response?->json('error.message')
                ?: $exception->response?->body()
                ?: $exception->getMessage();

            throw new RuntimeException('Gagal menghubungi Gemini: '.Str::limit((string) $message, 300));
        }

        return [
            'provider' => 'gemini',
            'content' => $this->extractGeminiOutputText($response),
            'usage' => $response['usageMetadata'] ?? null,
        ];
    }

    private function buildOpenAiPayload(array $input, string $model): array
    {
        $count = (int) $input['question_count'];
        $optionCount = (int) $input['option_count'];

        return [
            'model' => $model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => 'Anda adalah pembuat soal bimbel yang teliti. Keluarkan JSON sesuai schema saja.',
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildQuestionPrompt($input),
                ],
            ],
            'max_output_tokens' => max(2500, $count * 900),
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'question_bank_generation',
                    'strict' => true,
                    'schema' => $this->buildQuestionSchema($count, $optionCount),
                ],
            ],
        ];
    }

    private function buildGeminiPayload(array $input): array
    {
        $count = (int) $input['question_count'];
        $optionCount = (int) $input['option_count'];

        return [
            'systemInstruction' => [
                'parts' => [
                    ['text' => 'Anda adalah pembuat soal bimbel yang teliti. Keluarkan JSON sesuai schema saja.'],
                ],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $this->buildQuestionPrompt($input)],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseJsonSchema' => $this->buildQuestionSchema($count, $optionCount),
                'maxOutputTokens' => max(2500, $count * 900),
            ],
        ];
    }

    private function buildQuestionPrompt(array $input): string
    {
        $count = (int) $input['question_count'];
        $optionCount = (int) $input['option_count'];
        $explanationStyle = $input['explanation_style'] ?? 'normal';
        $extraInstruction = trim((string) ($input['instruction'] ?? ''));

        $prompt = <<<PROMPT
Buat {$count} soal pilihan ganda berbahasa Indonesia untuk bank soal bimbel.

Konteks:
- Mata pelajaran/kategori: {$input['subject']}
- Topik: {$input['topic']}
- Level: {$input['difficulty']}
- Jumlah opsi per soal: {$optionCount}
- Gaya pembahasan: {$explanationStyle}

Aturan:
- Semua soal harus orisinal, jelas, dan siap direview admin.
- Setiap soal wajib punya tepat {$optionCount} opsi.
- Hanya satu opsi yang benar.
- Hindari "semua jawaban benar" atau "jawaban A dan B benar".
- Pembahasan harus menjelaskan alasan jawaban benar.
- Jangan memasukkan nomor soal di awal teks soal.
- Jangan gunakan markdown table.
PROMPT;

        if ($extraInstruction !== '') {
            $prompt .= "\n\nInstruksi tambahan dari admin:\n{$extraInstruction}";
        }

        $referenceExamples = is_array($input['reference_examples'] ?? null) ? $input['reference_examples'] : [];
        if ($referenceExamples !== []) {
            $referenceLabel = trim((string) ($input['reference_label'] ?? 'Referensi soal'));
            $prompt .= "\n\nGunakan referensi berikut hanya untuk memahami gaya, kedalaman, dan pola soal. Jangan menyalin kalimat, angka, atau pilihan jawaban secara identik.\nSumber: {$referenceLabel}";

            foreach (array_slice($referenceExamples, 0, 3) as $index => $example) {
                $question = trim((string) ($example['question'] ?? ''));
                $options = collect($example['options'] ?? [])->filter()->implode(' | ');
                if ($question !== '') {
                    $prompt .= "\nContoh ".($index + 1).": {$question}".($options !== '' ? "\nOpsi contoh: {$options}" : '');
                }
            }
        }

        $referenceNote = trim((string) ($input['reference_note'] ?? ''));
        if ($referenceNote !== '') {
            $prompt .= "\n\nArahan terhadap gaya referensi:\n{$referenceNote}";
        }

        return $prompt;
    }

    private function buildQuestionSchema(int $count, int $optionCount): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['questions'],
            'properties' => [
                'questions' => [
                    'type' => 'array',
                    'minItems' => $count,
                    'maxItems' => $count,
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['question_text', 'options', 'correct_option', 'explanation'],
                        'properties' => [
                            'question_text' => [
                                'type' => 'string',
                                'description' => 'Teks soal tanpa nomor soal.',
                            ],
                            'options' => [
                                'type' => 'array',
                                'minItems' => $optionCount,
                                'maxItems' => $optionCount,
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'required' => ['label', 'text'],
                                    'properties' => [
                                        'label' => [
                                            'type' => 'string',
                                            'enum' => array_slice(['A', 'B', 'C', 'D', 'E'], 0, $optionCount),
                                        ],
                                        'text' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                            'correct_option' => [
                                'type' => 'string',
                                'enum' => array_slice(['A', 'B', 'C', 'D', 'E'], 0, $optionCount),
                            ],
                            'explanation' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function extractOpenAiOutputText(array $response): string
    {
        if (isset($response['output_text']) && is_string($response['output_text'])) {
            return $response['output_text'];
        }

        foreach (($response['output'] ?? []) as $output) {
            foreach (($output['content'] ?? []) as $content) {
                if (($content['type'] ?? null) === 'output_text' && isset($content['text'])) {
                    return (string) $content['text'];
                }
            }
        }

        throw new RuntimeException('Response AI kosong atau tidak memiliki output teks.');
    }

    private function extractGeminiOutputText(array $response): string
    {
        foreach (($response['candidates'] ?? []) as $candidate) {
            foreach (($candidate['content']['parts'] ?? []) as $part) {
                if (isset($part['text']) && is_string($part['text'])) {
                    return $part['text'];
                }
            }
        }

        throw new RuntimeException('Response Gemini kosong atau tidak memiliki output teks.');
    }

    private function normalizeQuestions(array $questions): array
    {
        $letters = ['A', 'B', 'C', 'D', 'E'];

        return collect($questions)
            ->map(function ($question) use ($letters) {
                $options = collect($question['options'] ?? [])
                    ->values()
                    ->map(function ($option, $index) use ($letters) {
                        return [
                            'label' => strtoupper((string) ($option['label'] ?? $letters[$index] ?? '')),
                            'text' => trim((string) ($option['text'] ?? '')),
                        ];
                    })
                    ->filter(fn ($option) => $option['label'] !== '' && $option['text'] !== '')
                    ->values()
                    ->all();

                return [
                    'question_text' => trim((string) ($question['question_text'] ?? '')),
                    'options' => $options,
                    'correct_option' => strtoupper(trim((string) ($question['correct_option'] ?? ''))),
                    'explanation' => trim((string) ($question['explanation'] ?? '')),
                ];
            })
            ->filter(function ($question) {
                return $question['question_text'] !== ''
                    && count($question['options']) >= 2
                    && collect($question['options'])->contains('label', $question['correct_option']);
            })
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $usage */
    private function normalizeUsage(array $usage): array
    {
        $input = (int) ($usage['input_tokens'] ?? $usage['promptTokenCount'] ?? 0);
        $output = (int) ($usage['output_tokens'] ?? $usage['candidatesTokenCount'] ?? 0);
        $total = (int) ($usage['total_tokens'] ?? $usage['totalTokenCount'] ?? ($input + $output));

        return [
            'input' => max(0, $input),
            'output' => max(0, $output),
            'total' => max(0, $total),
        ];
    }

    private function settings(): array
    {
        $profile = ClientProfile::query()->first();
        $sharedSettings = config('client.branding.ai_question_generator_settings');
        $sharedSettings = is_array($sharedSettings) && ! empty($sharedSettings)
            ? $sharedSettings
            : (is_array($profile?->ai_question_generator_settings) ? $profile->ai_question_generator_settings : []);
        $discussionSettings = config('client.branding.ai_discussion_settings');
        $discussionSettings = is_array($discussionSettings) && ! empty($discussionSettings)
            ? $discussionSettings
            : (is_array($profile?->ai_discussion_settings) ? $profile->ai_discussion_settings : []);

        if (! empty($discussionSettings)) {
            $usesCustomCredentials = ($discussionSettings['credential_mode'] ?? 'shared') === 'custom';

            return [
                ...$sharedSettings,
                'default_model' => $discussionSettings['model'] ?? ($sharedSettings['default_model'] ?? null),
                'models' => $discussionSettings['models'] ?? ($sharedSettings['models'] ?? []),
                'providers' => $usesCustomCredentials
                    ? ($discussionSettings['providers'] ?? [])
                    : ($sharedSettings['providers'] ?? []),
            ];
        }

        return $sharedSettings;
    }

    private function providerSettings(string $provider): array
    {
        $settings = $this->settings();

        return is_array($settings['providers'][$provider] ?? null)
            ? $settings['providers'][$provider]
            : [];
    }
}
