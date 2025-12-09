<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Player;

class PlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $names = [
            'Alice', 'Bob', 'Charlie', 'David', 'Eve', 'Frank', 'Grace', 'Hannah',
            'Ian', 'Jack', 'Karen', 'Leo', 'Mona', 'Nina', 'Oscar', 'Paul',
            'Quincy', 'Rachel', 'Sam', 'Tina', 'Uma', 'Victor', 'Wendy', 'Xander',
            'Yara', 'Zane', 'Liam', 'Sophia', 'Noah', 'Emma', 'Olivia', 'Ava', 'Robin',
            'Aizel','Keanne','Jay','Cham','Zam','Miguel','Rap Rap'
        ];

        $sexes = ['male', 'female'];
        $skills = ['A', 'B', 'C', 'D'];

        foreach ($names as $name) {
            Player::create([
                'name' => $name,
                'age' => rand(18, 40),
                'sex' => $sexes[array_rand($sexes)],
                'skill_level' => $skills[array_rand($skills)],
            ]);
        }
    }
}
