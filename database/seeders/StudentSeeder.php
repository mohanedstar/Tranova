<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $students = [
            [
                'name' => 'أحمد محمد',
                'email' => 'ahmed@student.com',
                'phone' => '0591111111',
                'student_data' => [
                    'student_id' => '20240001',
                    'major' => 'تكنولوجيا المعلومات',
                    'university' => 'جامعة فلسطين',
                    'year_of_study' => '4',
                    'gpa' => 3.5,
                    'bio' => 'طالب متحمس لتعلم تطوير الويب',
                    'skills' => ['PHP', 'Laravel', 'MySQL'],
                ]
            ],
            [
                'name' => 'سارة علي',
                'email' => 'sara@student.com',
                'phone' => '0592222222',
                'student_data' => [
                    'student_id' => '20240002',
                    'major' => 'علوم الحاسوب',
                    'university' => 'جامعة النجاح',
                    'year_of_study' => '3',
                    'gpa' => 3.8,
                    'bio' => 'مهتمة بالذكاء الاصطناعي',
                    'skills' => ['Python', 'Machine Learning', 'Data Analysis'],
                ]
            ],
            [
                'name' => 'محمد خالد',
                'email' => 'mohammed@student.com',
                'phone' => '0593333333',
                'student_data' => [
                    'student_id' => '20240003',
                    'major' => 'هندسة البرمجيات',
                    'university' => 'الجامعة الإسلامية',
                    'year_of_study' => '4',
                    'gpa' => 3.2,
                    'bio' => 'مطور تطبيقات موبايل',
                    'skills' => ['Flutter', 'Dart', 'Firebase'],
                ]
            ],
        ];

        foreach ($students as $studentData) {
            $user = User::create([
                'name' => $studentData['name'],
                'email' => $studentData['email'],
                'password' => Hash::make('password123'),
                'phone' => $studentData['phone'],
                'role' => 'student',
            ]);

            Student::create([
                'user_id' => $user->id,
                'student_id' => $studentData['student_data']['student_id'],
                'major' => $studentData['student_data']['major'],
                'university' => $studentData['student_data']['university'],
                'year_of_study' => $studentData['student_data']['year_of_study'],
                'gpa' => $studentData['student_data']['gpa'],
                'bio' => $studentData['student_data']['bio'],
                'skills' => $studentData['student_data']['skills'],
            ]);
        }
    }
}
