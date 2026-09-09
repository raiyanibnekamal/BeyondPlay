<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BracketService
{
    public function __construct(
        protected SoloTeamService $soloTeams
    ) {
    }

    /**
     * Generate bracket based on tournament format.
     */
    public function generateBracket(Tournament $tournament): array
    {
        $teamIds = $this->resolveBracketTeamIds($tournament);

        return match ($tournament->format) {
            'round_robin' => $this->generateRoundRobin($tournament, $teamIds),
            'double_elimination' => $this->generateDoubleElimination($tournament, $teamIds),
            default => $this->generateSingleElimination($tournament, $teamIds),
        };
    }

    /**
     * Collect confirmed team IDs including auto-created solo teams.
     *
     * @return array<int, int>
     */
    public function resolveBracketTeamIds(Tournament $tournament): array
    {
        $registrations = $tournament->registrations()
            ->where('status', 'confirmed')
            ->with('user:id,username')
            ->get();

        $teamIds = [];

        foreach ($registrations as $reg) {
            if ($reg->team_id) {
                $teamIds[] = (int) $reg->team_id;

                continue;
            }

            if ($reg->user) {
                $solo = $this->soloTeams->ensureSoloTeam($reg->user);
                if (! $reg->team_id) {
                    $reg->update(['team_id' => $solo->id]);
                }
                $teamIds[] = $solo->id;
            }
        }

        $teamIds = array_values(array_unique($teamIds));

        if (count($teamIds) < 2) {
            throw new \RuntimeException('At least 2 confirmed participants are required to generate a bracket.');
        }

        return $teamIds;
    }

    /**
     * @param  array<int, int>  $teamIds
     */
    protected function generateSingleElimination(Tournament $tournament, array $teamIds): array
    {
        $size = 1;
        while ($size < count($teamIds)) {
            $size *= 2;
        }
        $totalRounds = (int) log($size, 2);

        return DB::transaction(function () use ($tournament, $teamIds, $size, $totalRounds) {
            $tournament->matches()->delete();

            $slots = $teamIds;
            while (count($slots) < $size) {
                $slots[] = null;
            }

            $created = $this->createSingleElimSlots($tournament, $slots, $size, $totalRounds);
            $this->processByes($created);

            return $this->getBracketData($tournament->fresh());
        });
    }

    /**
     * @param  array<int, int>  $teamIds
     */
    protected function generateDoubleElimination(Tournament $tournament, array $teamIds): array
    {
        return DB::transaction(function () use ($tournament, $teamIds) {
            $tournament->matches()->delete();

            // Winners bracket (rounds 1..N)
            $size = 1;
            while ($size < count($teamIds)) {
                $size *= 2;
            }
            $winRounds = (int) log($size, 2);

            $slots = $teamIds;
            while (count($slots) < $size) {
                $slots[] = null;
            }

            $winCreated = $this->createSingleElimSlots($tournament, $slots, $size, $winRounds, 1);
            $this->processByes($winCreated);

            // Losers bracket — simplified second bracket starting at round 100
            $losersRoundBase = 100;
            $matchesInLosers = max(1, (int) ($size / 2));
            for ($m = 1; $m <= $matchesInLosers; $m++) {
                GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'round' => $losersRoundBase,
                    'match_number' => $m,
                    'status' => 'scheduled',
                ]);
            }

            return $this->getBracketData($tournament->fresh());
        });
    }

    /**
     * @param  array<int, int>  $teamIds
     */
    protected function generateRoundRobin(Tournament $tournament, array $teamIds): array
    {
        return DB::transaction(function () use ($tournament, $teamIds) {
            $tournament->matches()->delete();

            $n = count($teamIds);
            $round = 1;
            $matchNum = 1;

            for ($i = 0; $i < $n; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    GameMatch::create([
                        'tournament_id' => $tournament->id,
                        'round' => $round,
                        'match_number' => $matchNum++,
                        'team1_id' => $teamIds[$i],
                        'team2_id' => $teamIds[$j],
                        'status' => 'scheduled',
                    ]);
                    if ($matchNum > (int) ceil($n / 2)) {
                        $round++;
                        $matchNum = 1;
                    }
                }
            }

            return $this->getBracketData($tournament->fresh());
        });
    }

    /**
     * @param  array<int, int|null>  $slots
     * @return array<int, GameMatch>
     */
    protected function createSingleElimSlots(
        Tournament $tournament,
        array $slots,
        int $size,
        int $totalRounds,
        int $roundOffset = 0
    ): array {
        $created = [];
        $matchNumber = 1;

        for ($i = 0; $i < $size; $i += 2) {
            $created[] = GameMatch::create([
                'tournament_id' => $tournament->id,
                'round' => 1 + $roundOffset,
                'match_number' => $matchNumber++,
                'team1_id' => $slots[$i],
                'team2_id' => $slots[$i + 1],
                'status' => 'scheduled',
            ]);
        }

        $matchesInRound = $size / 2;
        for ($round = 2; $round <= $totalRounds; $round++) {
            $matchesInRound = (int) ($matchesInRound / 2);
            for ($m = 1; $m <= $matchesInRound; $m++) {
                $created[] = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'round' => $round + $roundOffset,
                    'match_number' => $m,
                    'status' => 'scheduled',
                ]);
            }
        }

        return $created;
    }

    /**
     * @param  array<int, GameMatch>  $created
     */
    protected function processByes(array $created): void
    {
        foreach ($created as $match) {
            if ($match->round === 1) {
                if ($match->team1_id && ! $match->team2_id) {
                    $this->advanceWinner($match, $match->team1_id);
                } elseif ($match->team2_id && ! $match->team1_id) {
                    $this->advanceWinner($match, $match->team2_id);
                }
            }
        }
    }

    public function advanceWinner(GameMatch $match, int $winnerId): void
    {
        $match->winner_id = $winnerId;
        $match->save();

        $nextRound = $match->round + 1;
        $nextNumber = (int) ceil($match->match_number / 2);

        $nextMatch = GameMatch::where('tournament_id', $match->tournament_id)
            ->where('round', $nextRound)
            ->where('match_number', $nextNumber)
            ->first();

        if (! $nextMatch) {
            return;
        }

        if ($match->match_number % 2 === 1) {
            $nextMatch->team1_id = $winnerId;
        } else {
            $nextMatch->team2_id = $winnerId;
        }
        $nextMatch->save();
    }

    public function getBracketData(Tournament $tournament): array
    {
        $matches = $tournament->matches()
            ->with(['team1:id,name,logo', 'team2:id,name,logo', 'winner:id,name'])
            ->orderBy('round')
            ->orderBy('match_number')
            ->get();

        $rounds = [];
        foreach ($matches as $match) {
            $rounds[$match->round][] = [
                'id' => $match->id,
                'match_number' => $match->match_number,
                'status' => $match->status,
                'scheduled_at' => $match->scheduled_at,
                'team1' => $match->team1 ? ['id' => $match->team1->id, 'name' => $match->team1->name, 'logo' => $match->team1->logo] : null,
                'team2' => $match->team2 ? ['id' => $match->team2->id, 'name' => $match->team2->name, 'logo' => $match->team2->logo] : null,
                'team1_score' => $match->team1_score,
                'team2_score' => $match->team2_score,
                'winner_id' => $match->winner_id,
            ];
        }

        $result = [];
        foreach ($rounds as $round => $roundMatches) {
            $label = $round >= 100 ? 'Losers '.($round - 99) : 'Round '.$round;
            $result[] = [
                'round' => $round,
                'label' => $label,
                'matches' => $roundMatches,
            ];
        }

        return [
            'tournament_id' => $tournament->id,
            'format' => $tournament->format,
            'rounds' => $result,
        ];
    }
}
