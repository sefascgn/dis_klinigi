<?php

namespace App\Http\Requests;

use App\Enums\DocumentType;
use App\Models\PatientDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PatientDocument::class)
            && $this->user()->can('view', $this->route('patient'));
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(DocumentType::class)],
            'title' => ['nullable', 'string', 'max:150'],
            // Yalnızca görüntü ve PDF; en fazla 10 MB.
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return ['file' => 'dosya', 'type' => 'belge türü', 'title' => 'başlık'];
    }
}
