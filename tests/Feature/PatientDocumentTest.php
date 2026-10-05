<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PatientDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(PatientDocument::DISK);
    }

    public function test_staff_can_upload_xray_and_view_it(): void
    {
        $patient = Patient::factory()->create();
        $user = $this->secretary();

        $this->actingAs($user)->post("/patients/{$patient->id}/documents", [
            'type' => 'xray',
            'title' => 'Panoramik',
            // GD gerektirmeyen gerçek 1x1 PNG içeriği
            'file' => UploadedFile::fake()->createWithContent('pano.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==')),
        ])->assertRedirect(route('patients.show', [$patient, 'tab' => 'documents']));

        $document = PatientDocument::firstOrFail();
        $this->assertSame('Panoramik', $document->title);
        $this->assertSame($user->id, $document->uploaded_by);
        Storage::disk(PatientDocument::DISK)->assertExists($document->path);

        $this->actingAs($this->doctorUser())->get("/documents/{$document->id}")->assertOk();
        $this->actingAs($user)->get("/patients/{$patient->id}?tab=documents")->assertSee('Panoramik');
    }

    public function test_only_images_and_pdfs_are_accepted(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($this->secretary())->post("/patients/{$patient->id}/documents", [
            'type' => 'other',
            'file' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('patient_documents', 0);
    }

    public function test_guests_cannot_download_documents(): void
    {
        $document = PatientDocument::factory()->create();

        $this->get("/documents/{$document->id}")->assertRedirect('/login');
    }

    public function test_only_uploader_or_admin_can_delete_and_file_is_removed(): void
    {
        $uploader = $this->secretary();
        $patient = Patient::factory()->create();
        $this->actingAs($uploader)->post("/patients/{$patient->id}/documents", [
            'type' => 'consent',
            'file' => UploadedFile::fake()->create('onam.pdf', 100, 'application/pdf'),
        ]);
        $document = PatientDocument::firstOrFail();
        $this->assertSame('onam', $document->title, 'Başlık boşsa dosya adı kullanılır.');

        $this->actingAs($this->secretary())->delete("/documents/{$document->id}")->assertForbidden();
        $this->actingAs($uploader)->delete("/documents/{$document->id}")->assertRedirect();

        $this->assertModelMissing($document);
        Storage::disk(PatientDocument::DISK)->assertMissing($document->path);
    }
}
