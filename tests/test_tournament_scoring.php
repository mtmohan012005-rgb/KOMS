<?php
require_once __DIR__ . '/../includes/tournament_scoring.php';

echo "Testing Tournament Scoring Engine (Section 21 Example)...\n";
$exampleScores = [8.2, 8.5, 9.0, 8.7, 8.3];
$result = TournamentScoringEngine::calculateScore($exampleScores);

print_r($result);

assert($result['dropped_lowest'] == 8.2, "Lowest should be 8.2");
assert($result['dropped_highest'] == 9.0, "Highest should be 9.0");
assert($result['total_score'] == 25.5, "Total score should be 25.5");
assert($result['average_score'] == 8.5, "Average score should be 8.5");

echo "\nAll Tournament Scoring Tests Passed Successfully!\n";
