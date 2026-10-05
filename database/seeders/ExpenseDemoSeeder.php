<?php

namespace Database\Seeders;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Enums\TreatmentStatus;
use App\Models\Doctor;
use App\Models\Expense;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Treatment;
use App\Models\TreatmentType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Finans demosu (yalnızca local): son 12 aya gerçekçi ciro / tahsilat geçmişi ve
 * bununla orantılı giderler ekler. Yalnızca ekleme yapar; gider kaydı varsa çalışmaz.
 */
class ExpenseDemoSeeder extends Seeder
{
    /** Aylık hedef ciro çarpanı (ocak ve ağustos sakin, bahar ve sonbahar yoğun). */
    private const SEASON = [1 => 0.8, 2 => 0.9, 3 => 1.05, 4 => 1.1, 5 => 1.1, 6 => 1.0, 7 => 0.9, 8 => 0.75, 9 => 1.05, 10 => 1.15, 11 => 1.1, 12 => 0.95];

    public function run(): void
    {
        if (Expense::query()->exists() || Patient::query()->doesntExist()) {
            return;
        }

        $patients = Patient::query()->pluck('id');
        $doctors = Doctor::query()->pluck('id');
        $types = TreatmentType::query()->where('default_price', '>', 0)->get();
        $receiverId = User::query()->where('role', 'secretary')->value('id');
        $adminId = User::query()->where('role', 'admin')->value('id');

        for ($i = 11; $i >= 0; $i--) {
            $month = today()->startOfMonth()->subMonths($i);
            $lastDay = $i === 0 ? today()->day : $month->daysInMonth;

            $this->seedRevenue($month, $lastDay, $patients, $doctors, $types, $receiverId);
            $this->seedExpenses($month, $lastDay, $adminId);
        }
    }

    private function seedRevenue(Carbon $month, int $lastDay, $patients, $doctors, $types, ?int $receiverId): void
    {
        $target = 120000 * self::SEASON[$month->month] * fake()->randomFloat(2, 0.85, 1.15) * ($lastDay / $month->daysInMonth);

        for ($ciro = 0; $ciro < $target;) {
            $type = $types->random();
            $date = $month->copy()->day(fake()->numberBetween(1, $lastDay));
            $patientId = $patients->random();

            Treatment::factory()->create([
                'patient_id' => $patientId,
                'doctor_id' => $doctors->random(),
                'treatment_type_id' => $type->id,
                'price' => $type->default_price,
                'status' => TreatmentStatus::Completed,
                'treatment_date' => $date->toDateString(),
            ]);
            $ciro += (float) $type->default_price;

            // Çoğu tedavi aynı gün ya da birkaç gün içinde (tamamen veya kısmen) ödenir.
            if (fake()->boolean(88)) {
                $paidAt = $date->copy()->addDays(fake()->numberBetween(0, 7))->min(today());
                $payment = new Payment([
                    'patient_id' => $patientId,
                    'amount' => round($type->default_price * fake()->randomElement([1, 1, 1, 0.5]), 2),
                    'method' => fake()->randomElement([PaymentMethod::CreditCard, PaymentMethod::CreditCard, PaymentMethod::Cash, PaymentMethod::BankTransfer]),
                    'paid_at' => $paidAt->toDateString(),
                ]);
                $payment->received_by = $receiverId;
                $payment->save();
            }
        }
    }

    private function seedExpenses(Carbon $month, int $lastDay, ?int $adminId): void
    {
        $collected = (float) Payment::whereBetween('paid_at', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])->sum('amount');

        // Sabit giderler her ay; malzeme ve laboratuvar o ayın tahsilatıyla orantılı.
        $items = [
            [ExpenseCategory::Rent, 'Muayenehane kirası', 18000, 1],
            [ExpenseCategory::Salary, 'Personel maaşları', 42000, 1],
            [ExpenseCategory::Tax, 'SGK ve vergi ödemeleri', 11500, 15],
            [ExpenseCategory::Utilities, 'Elektrik, su, internet', fake()->numberBetween(2800, 4800), 20],
            [ExpenseCategory::Supplies, 'Sarf malzeme alımı', round($collected * fake()->randomFloat(2, 0.07, 0.12)), 10],
            [ExpenseCategory::Lab, 'Laboratuvar (protez / zirkonyum)', round($collected * fake()->randomFloat(2, 0.10, 0.16)), 25],
        ];
        if ($month->month % 4 === 1) {
            $items[] = [ExpenseCategory::Equipment, 'Cihaz bakım / kalibrasyon', fake()->numberBetween(6000, 14000), 12];
        }
        if ($month->month % 3 === 0) {
            $items[] = [ExpenseCategory::Marketing, 'Sosyal medya reklamı', fake()->numberBetween(2500, 5000), 5];
        }

        foreach ($items as [$category, $description, $amount, $day]) {
            if ($amount <= 0 || $day > $lastDay) {
                continue;
            }

            $expense = new Expense([
                'category' => $category,
                'description' => $description.' – '.$month->translatedFormat('F Y'),
                'amount' => $amount,
                'method' => $category === ExpenseCategory::Supplies ? PaymentMethod::CreditCard : PaymentMethod::BankTransfer,
                'spent_at' => $month->copy()->day($day)->toDateString(),
            ]);
            $expense->created_by = $adminId;
            $expense->save();
        }
    }
}
