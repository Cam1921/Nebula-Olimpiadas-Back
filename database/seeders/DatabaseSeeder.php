<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AreaSeeder::class,
            NivelSeeder::class,
            AreaNivelSeeder::class,
            InstitucionSeeder::class,
            TutorCompetidorSeeder::class,
            CompetidorSeeder::class,
            InscripcionSeeder::class,
            AdminUserSeeder::class]);
    }
}
