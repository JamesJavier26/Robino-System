<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Queue;
use App\Models\Player;

class QueueController extends Controller
{
    // Show the queue for today or filtered date
    public function index(Request $request)
    {
        $date = $request->date ?? now()->toDateString();

        $queues = Queue::with('player')
            ->where('date', $date)
            ->orderBy('position')
            ->get();

        // All players for selecting
        $players = Player::orderBy('name')->get();

        return view('queues.index', compact('queues', 'players', 'date'));
    }

    // Add a player to the queue
    public function store(Request $request)
    {
        $request->validate([
            'player_ids' => 'required|array',
            'date' => 'required|date',
        ]);

        $date = $request->date;

        foreach ($request->player_ids as $playerId) {
            // Check if player is already in queue for this date
            $exists = Queue::where('player_id', $playerId)
                            ->where('date', $date)
                            ->exists();

            if (!$exists) {
                Queue::create([
                    'player_id' => $playerId,
                    'date' => $date,
                    'status' => 'waiting',
                ]);
            }
        }

        return redirect()->back()->with('success', 'Selected players added to the queue!');
    }


    // Remove a player from the queue
    public function remove(Queue $queue)
    {
        $queue->delete();
        return redirect()->back()->with('success', 'Removed from queue');
    }

    public function matchup(Request $request)
    {
        $date = $request->date ?? now()->toDateString();
        $strategy = $request->strategy ?? 'random';
        $maxCourt = $request->max_courts ?? null;

        // Get waiting players for the date
        $queues = Queue::with('player')
            ->where('date', $date)
            ->where('status', 'waiting')
            ->get();

        $playerCount = $queues->count();

        // Check exact number of players
        if (!in_array($playerCount, [40, 60])) {
            return redirect()->route('queues.index', ['date' => $date])
                ->with('error', 'You can only generate matchups for exactly 40 or 60 players. Currently: '.$playerCount);
        }

        // Default max courts if not provided
        $maxCourt = $maxCourt ?? ($playerCount === 40 ? 4 : 6);

        $matchId = 1;
        $courtNumber = 1;

        if ($strategy === 'random') {
            $queues = $queues->shuffle();
        } elseif ($strategy === 'skill') {
            // Bracket groups
            $bracket1 = ['A', 'B']; // top bracket
            $bracket2 = ['C', 'D']; // bottom bracket
            $bracketGroups = collect();

            // Bracket 1: A & B
            $bracket1Players = $queues->filter(fn($q) => in_array($q->player->skill_level, $bracket1))->shuffle();
            $bracketGroups = $bracketGroups->concat($bracket1Players->chunk(4));

            // Bracket 2: C & D
            $bracket2Players = $queues->filter(fn($q) => in_array($q->player->skill_level, $bracket2))->shuffle();
            $bracketGroups = $bracketGroups->concat($bracket2Players->chunk(4));

            // Assign matches for skill strategy
            foreach ($bracketGroups as $group) {
                if ($group->count() === 4) {
                    foreach ($group as $queue) {
                        $queue->status = 'assigned';
                        $queue->match_id = $matchId;
                        $queue->court = $courtNumber;
                        $queue->save();
                    }

                    $matchId++;
                    $courtNumber++;
                    if ($courtNumber > $maxCourt) $courtNumber = 1;
                }
            }

            return redirect()->route('queues.index', ['date' => $date])
                            ->with('success', "Doubles matchups generated for $playerCount players using 'Skill Bracket' strategy!");
        } elseif ($strategy === 'gender') {
            $queues = $queues->sortBy('player.sex');
        }

        // Generate doubles matchups for non-skill strategies (random, gender)
        $queues->chunk(4)->each(function ($group) use (&$matchId, &$courtNumber, $maxCourt) {
            if ($group->count() === 4) {
                foreach ($group as $queue) {
                    $queue->status = 'assigned';
                    $queue->match_id = $matchId;
                    $queue->court = $courtNumber;
                    $queue->save();
                }

                $matchId++;
                $courtNumber++;
                if ($courtNumber > $maxCourt) $courtNumber = 1;
            }
        });

        return redirect()->route('queues.index', ['date' => $date])
                        ->with('success', "Doubles matchups generated for $playerCount players using '$strategy' strategy!");
    }

    public function resetMatchups(Request $request)
    {
        $date = $request->date ?? now()->toDateString();

        Queue::where('date', $date)
            ->update([
                'status' => 'waiting',
                'match_id' => null,
                'court' => null,
            ]);

        return redirect()->route('queues.index', ['date' => $date])
                        ->with('success', 'Matchups have been reset!');
    }

}