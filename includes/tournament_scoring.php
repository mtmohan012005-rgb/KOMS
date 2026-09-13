<?php
/**
 * KOMS - Kobudo Online Management System
 * Official Tournament Scoring Engine (Section 21 Specification)
 *
 * Rules:
 * - 5 judges enter scores between 1.0 and 10.0
 * - System removes the single highest score
 * - System removes the single lowest score
 * - Calculates the sum of the remaining 3 scores
 * - Calculates the average of the remaining 3 scores
 */

class TournamentScoringEngine {

    /**
     * Calculate Kata / Kobudo / Form score from 5 judges
     *
     * @param array $scores Array of 5 numeric scores (e.g., [8.2, 8.5, 9.0, 8.7, 8.3])
     * @return array Calculated result breakdown
     * @throws InvalidArgumentException
     */
    public static function calculateScore(array $scores): array {
        if (count($scores) !== 5) {
            throw new InvalidArgumentException("Tournament scoring requires exactly 5 judge scores.");
        }

        $validatedScores = [];
        foreach ($scores as $idx => $score) {
            if (!is_numeric($score)) {
                throw new InvalidArgumentException("Judge " . ($idx + 1) . " score must be numeric.");
            }
            $val = round((float)$score, 2);
            if ($val < 1.0 || $val > 10.0) {
                throw new InvalidArgumentException("Judge " . ($idx + 1) . " score ($val) must be between 1.0 and 10.0.");
            }
            $validatedScores[] = $val;
        }

        // Sort ascending to easily extract min and max
        $sorted = $validatedScores;
        sort($sorted, SORT_NUMERIC);

        $lowest = $sorted[0];
        $highest = $sorted[4];

        // The remaining three scores
        $countedScores = array_slice($sorted, 1, 3);
        $totalScore = round(array_sum($countedScores), 2);
        $averageScore = round($totalScore / 3, 2);

        return [
            'original_scores' => $validatedScores,
            'sorted_scores' => $sorted,
            'dropped_lowest' => $lowest,
            'dropped_highest' => $highest,
            'counted_scores' => $countedScores,
            'total_score' => $totalScore,
            'average_score' => $averageScore,
            'formula' => "Sum of " . implode(" + ", $countedScores) . " = " . $totalScore
        ];
    }
}
