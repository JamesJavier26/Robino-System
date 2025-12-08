<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    public function index()
    {
        $players = Player::orderBy('name')->get();
        return view('players.index', compact('players'));
    }

    public function create()
    {
        return view('players.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'nullable|integer|min:1',
            'sex' => 'required|in:Male,Female',
            'skill_level' => 'required|in:Beginner,Intermediate,Advanced,Professional'
        ]);

        Player::create($validated);

        return redirect()->route('players.index')->with('success', 'Player created successfully.');
    }

    public function edit(Player $player)
    {
        return view('players.edit', compact('player'));
    }

    public function update(Request $request, Player $player)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'nullable|integer|min:1',
            'sex' => 'required|in:Male,Female',
            'skill_level' => 'required|in:Beginner,Intermediate,Advanced,Professional'
        ]);

        $player->update($validated);

        return redirect()->route('players.index')->with('success', 'Player updated successfully.');
    }

    public function destroy(Player $player)
    {
        $player->delete();
        return redirect()->route('players.index')->with('success', 'Player deleted.');
    }
}
