<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $patients = [
            ['first_name' => 'Ion', 'last_name' => 'Marin', 'date_of_birth' => '1985-03-15', 'gender' => 'male', 'phone' => '0721000001', 'email' => 'ion.marin@email.com', 'city' => 'Bucharest', 'county' => 'Ilfov'],
            ['first_name' => 'Elena', 'last_name' => 'Stanescu', 'date_of_birth' => '1990-07-22', 'gender' => 'female', 'phone' => '0721000002', 'email' => 'elena.s@email.com', 'city' => 'Cluj-Napoca', 'county' => 'Cluj'],
            ['first_name' => 'Gheorghe', 'last_name' => 'Popa', 'date_of_birth' => '1978-11-30', 'gender' => 'male', 'phone' => '0721000003', 'email' => 'gheorghe.p@email.com', 'city' => 'Timisoara', 'county' => 'Timis'],
            ['first_name' => 'Maria', 'last_name' => 'Dumitru', 'date_of_birth' => '1995-01-10', 'gender' => 'female', 'phone' => '0721000004', 'email' => 'maria.d@email.com', 'city' => 'Bucharest', 'county' => 'Ilfov'],
            ['first_name' => 'Andrei', 'last_name' => 'Radu', 'date_of_birth' => '1982-06-18', 'gender' => 'male', 'phone' => '0721000005', 'email' => 'andrei.r@email.com', 'city' => 'Iasi', 'county' => 'Iasi'],
            ['first_name' => 'Cristina', 'last_name' => 'Popescu', 'date_of_birth' => '1988-09-25', 'gender' => 'female', 'phone' => '0721000006', 'email' => 'cristina.p@email.com', 'city' => 'Brasov', 'county' => 'Brasov'],
            ['first_name' => 'Alexandru', 'last_name' => 'Ionescu', 'date_of_birth' => '1973-04-05', 'gender' => 'male', 'phone' => '0721000007', 'email' => 'alex.i@email.com', 'city' => 'Constanta', 'county' => 'Constanta'],
            ['first_name' => 'Daniela', 'last_name' => 'Vasilescu', 'date_of_birth' => '1992-12-08', 'gender' => 'female', 'phone' => '0721000008', 'email' => 'daniela.v@email.com', 'city' => 'Sibiu', 'county' => 'Sibiu'],
            ['first_name' => 'Mihai', 'last_name' => 'Georgescu', 'date_of_birth' => '1980-08-14', 'gender' => 'male', 'phone' => '0721000009', 'email' => 'mihai.g@email.com', 'city' => 'Bucharest', 'county' => 'Ilfov'],
            ['first_name' => 'Ioana', 'last_name' => 'Constantinescu', 'date_of_birth' => '1997-02-28', 'gender' => 'female', 'phone' => '0721000010', 'email' => 'ioana.c@email.com', 'city' => 'Craiova', 'county' => 'Dolj'],
        ];

        foreach ($patients as $data) {
            Patient::create($data);
        }
    }
}
