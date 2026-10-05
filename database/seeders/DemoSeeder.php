<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\TreatmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Treatment;
use App\Models\TreatmentType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Geliştirme ortamı için örnek veri. Production'da çalıştırılmaz.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Patient::query()->exists()) {
            return; // Demo verisi bir kez eklenir.
        }

        User::factory()->secretary()->create(['name' => 'Selin Sekreter', 'email' => 'sekreter@klinik.test']);
        User::factory()->doctor()->create(['name' => 'Ayşe Yılmaz', 'email' => 'doktor@klinik.test']);
        Doctor::factory()->count(2)->create();

        $doctors = Doctor::all();
        $types = TreatmentType::all();
        $patients = Patient::factory()->count(40)->create();

        foreach ($patients as $patient) {
            // Geçmiş randevular ve bunlara bağlı tedaviler
            $count = fake()->numberBetween(0, 3);
            for ($i = 0; $i < $count; $i++) {
                $start = Carbon::instance(fake()->dateTimeBetween('-5 months', '-1 day'))->setTime(fake()->numberBetween(9, 16), fake()->randomElement([0, 30]));
                $this->appointment($patient, $doctors->random(), $start, AppointmentStatus::Completed, $types);
            }
        }

        // Bugün ve önümüzdeki günler için randevular (çakışmasız)
        $slots = collect();
        foreach (range(0, 10) as $day) {
            foreach ($doctors as $doctor) {
                foreach (fake()->randomElements([9, 10, 11, 13, 14, 15, 16], fake()->numberBetween(1, 4)) as $hour) {
                    $slots->push([$doctor, today()->addDays($day)->setTime($hour, 0)]);
                }
            }
        }

        foreach ($slots as [$doctor, $start]) {
            $status = $start->isToday() && $start->isPast()
                ? fake()->randomElement([AppointmentStatus::Completed, AppointmentStatus::Confirmed])
                : fake()->randomElement([AppointmentStatus::Pending, AppointmentStatus::Pending, AppointmentStatus::Confirmed, AppointmentStatus::Cancelled]);

            $this->appointment($patients->random(), $doctor, $start, $status, $types);
        }
    }

    private function appointment(Patient $patient, Doctor $doctor, Carbon $start, AppointmentStatus $status, $types): void
    {
        $appointment = Appointment::factory()->for($patient)->for($doctor)->at($start)->status($status)->create([
            'cancellation_reason' => $status === AppointmentStatus::Cancelled ? 'Hasta gelemeyeceğini bildirdi.' : null,
        ]);

        if ($status !== AppointmentStatus::Completed) {
            return;
        }

        $type = $types->random();
        Treatment::factory()->for($patient)->for($doctor)->create([
            'treatment_type_id' => $type->id,
            'appointment_id' => $appointment->id,
            'price' => $type->default_price,
            'status' => TreatmentStatus::Completed,
            'treatment_date' => $start->toDateString(),
        ]);

        // Hastaların çoğu ödemenin bir kısmını veya tamamını yapmış olsun.
        if (fake()->boolean(75)) {
            Payment::factory()->for($patient)->create([
                'amount' => round($type->default_price * fake()->randomElement([0.5, 1, 1]), 2),
                'paid_at' => $start->toDateString(),
                'received_by' => User::query()->where('email', 'sekreter@klinik.test')->value('id'),
            ]);
        }
    }
}
