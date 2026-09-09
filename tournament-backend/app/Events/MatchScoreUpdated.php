<?php

namespace App\Events;

use App\Models\GameMatch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchScoreUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GameMatch $match)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('tournament.'.$this->match->tournament_id);
    }

    public function broadcastAs(): string
    {
        return 'match.score.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'match_id' => $this->match->id,
            'tournament_id' => $this->match->tournament_id,
            'team1_id' => $this->match->team1_id,
            'team2_id' => $this->match->team2_id,
            'team1_score' => $this->match->team1_score,
            'team2_score' => $this->match->team2_score,
            'winner_id' => $this->match->winner_id,
            'status' => $this->match->status,
        ];
    }
}
