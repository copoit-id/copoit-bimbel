<?php

namespace App\Services;

class ToeflScoringService
{
    /**
     * TOEFL ITP Conversion Tables based on official ETS data
     */
    private static $conversionTables = [
        'section1' => [ // Listening
            50 => 68,
            49 => 67,
            48 => 66,
            47 => 65,
            46 => 63,
            45 => 62,
            44 => 61,
            43 => 60,
            42 => 59,
            41 => 58,
            40 => 57,
            39 => 57,
            38 => 56,
            37 => 55,
            36 => 54,
            35 => 54,
            34 => 53,
            33 => 53,
            32 => 52,
            31 => 52,
            30 => 51,
            29 => 50,
            28 => 49,
            27 => 49,
            26 => 48,
            25 => 48,
            24 => 47,
            23 => 47,
            22 => 46,
            21 => 45,
            20 => 45,
            19 => 44,
            18 => 43,
            17 => 42,
            16 => 41,
            15 => 41,
            14 => 39,
            13 => 38,
            12 => 37,
            11 => 35,
            10 => 33,
            9 => 32,
            8 => 32,
            7 => 31,
            6 => 30,
            5 => 29,
            4 => 28,
            3 => 27,
            2 => 26,
            1 => 25,
            0 => 24
        ],
        'section2' => [ // Writing (formerly Structure & Written Expression)
            40 => 68,
            39 => 68,
            38 => 65,
            37 => 63,
            36 => 61,
            35 => 60,
            34 => 58,
            33 => 57,
            32 => 56,
            31 => 55,
            30 => 54,
            29 => 53,
            28 => 52,
            27 => 51,
            26 => 50,
            25 => 49,
            24 => 47,
            23 => 47,
            22 => 46,
            21 => 45,
            20 => 44,
            19 => 43,
            18 => 42,
            17 => 41,
            16 => 40,
            15 => 40,
            14 => 38,
            13 => 37,
            12 => 36,
            11 => 35,
            10 => 33,
            9 => 31,
            8 => 29,
            7 => 27,
            6 => 26,
            5 => 25,
            4 => 23,
            3 => 22,
            2 => 21,
            1 => 20,
            0 => 20
        ],
        'section3' => [ // Reading Comprehension
            50 => 67,
            49 => 66,
            48 => 65,
            47 => 63,
            46 => 61,
            45 => 60,
            44 => 59,
            43 => 58,
            42 => 57,
            41 => 56,
            40 => 55,
            39 => 54,
            38 => 54,
            37 => 53,
            36 => 52,
            35 => 52,
            34 => 51,
            33 => 50,
            32 => 49,
            31 => 48,
            30 => 48,
            29 => 47,
            28 => 46,
            27 => 46,
            26 => 45,
            25 => 44,
            24 => 43,
            23 => 43,
            22 => 42,
            21 => 41,
            20 => 40,
            19 => 39,
            18 => 38,
            17 => 37,
            16 => 36,
            15 => 35,
            14 => 34,
            13 => 33,
            12 => 32,
            11 => 31,
            10 => 30,
            9 => 29,
            8 => 28,
            7 => 28,
            6 => 27,
            5 => 26,
            4 => 25,
            3 => 23,
            2 => 23,
            1 => 22,
            0 => 21
        ]
    ];

    /**
     * Calculate TOEFL ITP score based on correct answers in each section
     *
     * @param int $correct1 Number of correct answers in Section 1 (Listening)
     * @param int $correct2 Number of correct answers in Section 2 (Writing)
     * @param int $correct3 Number of correct answers in Section 3 (Reading)
     * @return int Final TOEFL score (217-677)
     */
    public static function calculateToeflScore($correct1, $correct2, $correct3)
    {
        // Convert raw scores to scaled scores using conversion tables
        $skor1 = self::$conversionTables['section1'][$correct1] ?? 24; // Minimum score for section 1
        $skor2 = self::$conversionTables['section2'][$correct2] ?? 20; // Minimum score for section 2
        $skor3 = self::$conversionTables['section3'][$correct3] ?? 21; // Minimum score for section 3

        // Calculate total conversion score
        $totalKonversi = $skor1 + $skor2 + $skor3;

        // Calculate temporary score
        $nilaiSementara = $totalKonversi * 10;

        // Calculate final TOEFL score
        $skorAkhir = $nilaiSementara / 3;

        // Round to nearest integer
        $skorAkhir = round($skorAkhir);

        // Ensure score is within valid range (217-677)
        $skorAkhir = max(217, min(677, $skorAkhir));

        return $skorAkhir;
    }

    /**
     * Get section scores for detailed reporting
     *
     * @param int $correct1 Number of correct answers in Section 1
     * @param int $correct2 Number of correct answers in Section 2
     * @param int $correct3 Number of correct answers in Section 3
     * @return array Section scores and final score
     */
    public static function getToeflSectionScores($correct1, $correct2, $correct3)
    {
        $skor1 = self::$conversionTables['section1'][$correct1] ?? 24;
        $skor2 = self::$conversionTables['section2'][$correct2] ?? 20;
        $skor3 = self::$conversionTables['section3'][$correct3] ?? 21;

        $finalScore = self::calculateToeflScore($correct1, $correct2, $correct3);

        return [
            'section1' => [
                'raw_score' => $correct1,
                'scaled_score' => $skor1,
                'section_name' => 'Listening Comprehension'
            ],
            'section2' => [
                'raw_score' => $correct2,
                'scaled_score' => $skor2,
                'section_name' => 'Writing Test'
            ],
            'section3' => [
                'raw_score' => $correct3,
                'scaled_score' => $skor3,
                'section_name' => 'Reading Comprehension'
            ],
            'total_score' => $finalScore,
            'score_interpretation' => self::getScoreInterpretation($finalScore)
        ];
    }

    /**
     * Get TOEFL score interpretation
     *
     * @param int $score TOEFL score
     * @return array Level and description
     */
    public static function getScoreInterpretation($score)
    {
        if ($score >= 677) {
            return ['level' => 'Excellent', 'description' => 'Kemampuan bahasa Inggris sangat tinggi'];
        } elseif ($score >= 600) {
            return ['level' => 'Very Good', 'description' => 'Kemampuan bahasa Inggris sangat baik'];
        } elseif ($score >= 550) {
            return ['level' => 'Good', 'description' => 'Kemampuan bahasa Inggris baik'];
        } elseif ($score >= 500) {
            return ['level' => 'Fair', 'description' => 'Kemampuan bahasa Inggris cukup'];
        } elseif ($score >= 450) {
            return ['level' => 'Limited', 'description' => 'Kemampuan bahasa Inggris terbatas'];
        } else {
            return ['level' => 'Weak', 'description' => 'Kemampuan bahasa Inggris lemah'];
        }
    }

    /**
     * Process TOEFL scoring for user answers
     *
     * @param array $userAnswers Array of UserAnswer models
     * @return array TOEFL scoring results
     */
    public static function processToeflScoring($userAnswers)
    {
        $sectionScores = [
            'listening' => 0,
            'writing' => 0,
            'reading' => 0
        ];

        // Extract correct answers for each section
        foreach ($userAnswers as $userAnswer) {
            $sectionType = $userAnswer->tryoutDetail->type_subtest;
            $correctAnswers = $userAnswer->correct_answers ?? 0;

            switch ($sectionType) {
                case 'listening':
                    $sectionScores['listening'] = $correctAnswers;
                    break;
                case 'writing':
                    $sectionScores['writing'] = $correctAnswers;
                    break;
                case 'reading':
                    $sectionScores['reading'] = $correctAnswers;
                    break;
            }
        }

        // Calculate TOEFL scores
        return self::getToeflSectionScores(
            $sectionScores['listening'],
            $sectionScores['writing'],
            $sectionScores['reading']
        );
    }
}
