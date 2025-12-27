<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Queue;
use App\Models\Player;
use App\Models\MatchRecords;

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
    $maxCourt = $request->max_courts ?? 4;

    // Get waiting players for the date
    $queues = Queue::with('player')
        ->where('date', $date)
        ->where('status', 'waiting')
        ->get();

    if ($queues->count() === 0) {
        return redirect()->route('queues.index', ['date' => $date])
                         ->with('error', 'No players available to generate matchups.');
    }

    $courtNumber = 1;

    // Get last match_id used (persistent across generations)
    $lastMatchId = Queue::max('match_id') ?? 0;
    $matchId = $lastMatchId + 1;

    // Shuffle / sort players based on strategy
    if ($strategy === 'random') {
        // Male+Female pairing
        $males = $queues->filter(fn($q) => $q->player->sex === 'Male')->shuffle();
        $females = $queues->filter(fn($q) => $q->player->sex === 'Female')->shuffle();

        $queues = collect();

        while ($males->count() >= 2 && $females->count() >= 2) {
            $team1 = $males->shift();
            $team2 = $females->shift();
            $team3 = $males->shift();
            $team4 = $females->shift();

            $group = collect([$team1, $team2, $team3, $team4]);

            // Assign match_id, court, and status
            foreach ($group as $queue) {
                $queue->match_id = $matchId;
                $queue->court = $courtNumber;
                $queue->status = 'assigned';
                $queue->save();
            }

            $matchId++;
            $courtNumber++;
            if ($courtNumber > $maxCourt) $courtNumber = 1;

            $queues = $queues->concat($group);
        }

        // Remaining players (<4) stay waiting
        $remaining = $males->concat($females);
        foreach ($remaining as $queue) {
            $queue->status = 'waiting';
            $queue->save();
        }

    } elseif ($strategy === 'skill') {
        $bracket1 = ['A', 'B'];
        $bracket2 = ['C', 'D'];

        $bracketGroups = collect();

        // Bracket 1
        $bracket1Players = $queues->filter(fn($q) => in_array($q->player->skill_level, $bracket1))->shuffle();
        $bracketGroups = $bracketGroups->concat($bracket1Players->chunk(4));

        // Bracket 2
        $bracket2Players = $queues->filter(fn($q) => in_array($q->player->skill_level, $bracket2))->shuffle();
        $bracketGroups = $bracketGroups->concat($bracket2Players->chunk(4));

        // Assign matches
        foreach ($bracketGroups as $group) {
            if ($group->count() < 4) continue; // leave incomplete group waiting

            foreach ($group as $queue) {
                $queue->match_id = $matchId;
                $queue->court = $courtNumber;
                $queue->status = 'assigned';
                $queue->save();
            }

            $matchId++;
            $courtNumber++;
            if ($courtNumber > $maxCourt) $courtNumber = 1;
        }

    } elseif ($strategy === 'gender') {
        $sorted = $queues->sortBy('player.sex')->values();

        $sorted->chunk(4)->each(function ($group) use (&$matchId, &$courtNumber, $maxCourt) {
            if ($group->count() < 4) return; // incomplete, stay waiting

            foreach ($group as $queue) {
                $queue->match_id = $matchId;
                $queue->court = $courtNumber;
                $queue->status = 'assigned';
                $queue->save();
            }

            $matchId++;
            $courtNumber++;
            if ($courtNumber > $maxCourt) $courtNumber = 1;
        });
    }

    return redirect()->route('queues.index', ['date' => $date])
                     ->with('success', "Matchups generated using '$strategy' strategy! Incomplete groups remain waiting.");
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

    public function replace(Request $request)
    {
        $request->validate([
            'out_queue_id' => 'required|exists:queues,id',
            'in_queue_id'  => 'required|exists:queues,id',
        ]);

        DB::transaction(function () use ($request) {
            $out = Queue::lockForUpdate()->find($request->out_queue_id);
            $in  = Queue::lockForUpdate()->find($request->in_queue_id);

            // Assign the in player to out player's match
            $in->match_id  = $out->match_id;
            $in->court     = $out->court;
            $in->position  = $out->position;
            $in->status    = $out->status; // e.g., 'assigned' or 'playing'
            $in->save();

            // Move out player to waiting
            $nextPosition = Queue::where('date', $out->date)
                                ->where('status', 'waiting')
                                ->max('position') ?? 0;

            $out->match_id = null;
            $out->court    = null;
            $out->position = $nextPosition + 1; // assign next waiting position
            $out->status   = 'waiting';
            $out->save();
        });

        return redirect()->back()->with('success', 'Player replaced successfully!');
    }


    public function markMatchDone($matchId)
    {
        // Get all players for this match
        $queues = Queue::with('player')
                    ->where('match_id', $matchId)
                    ->get();
        if ($queues->count() !== 4) {
            return redirect()->back()->with('error', 'Only complete matches with 4 players can be recorded.');
        }

        // Extract only the 4 player names
        $playerNames = $queues->pluck('player.name')->toArray();

        // Save to MatchRecords
        MatchRecords::create([
            'match_id' => $matchId,
            'date' => $queues->first()->date,
            'court' => $queues->first()->court,
            'players' => $playerNames,
        ]);

        // Reset the players to waiting
        foreach ($queues as $queue) {
            $queue->status = 'waiting';
            $queue->match_id = null;
            $queue->done = true;
            $queue->court = null;
            $nextPosition = Queue::where('date', $queue->date)
                                ->where('status', 'waiting')
                                ->max('position') ?? 0;
            $queue->position = $nextPosition + 1;
            $queue->save();
        }

        return redirect()->back()->with('success', 'Match marked as done and recorded!');
    }

    public function matchRecords()
    {
        $records = MatchRecords::orderByDesc('date')->get();
        return view('queues.records', compact('records'));
    }
}