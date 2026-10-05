<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PatientDocument extends Model
{
    /** @use HasFactory<\Database\Factories\PatientDocumentFactory> */
    use HasFactory;

    public const DISK = 'local';

    protected $fillable = [
        'type',
        'title',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Kayıt silinince dosya da diskten kaldırılır.
        static::deleted(fn (self $document) => Storage::disk(self::DISK)->delete($document->path));
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function humanSize(): string
    {
        return $this->size >= 1048576
            ? number_format($this->size / 1048576, 1, ',', '.').' MB'
            : number_format($this->size / 1024, 0, ',', '.').' KB';
    }
}
