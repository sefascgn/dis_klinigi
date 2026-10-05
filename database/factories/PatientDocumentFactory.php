<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PatientDocument>
 */
class PatientDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'type' => DocumentType::Xray,
            'title' => 'Panoramik röntgen',
            'path' => 'patient-documents/test/'.fake()->uuid().'.png',
            'original_name' => 'rontgen.png',
            'mime_type' => 'image/png',
            'size' => 204800,
            'uploaded_by' => null,
        ];
    }
}
