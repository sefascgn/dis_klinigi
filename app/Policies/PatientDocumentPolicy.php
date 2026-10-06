<?php

namespace App\Policies;

use App\Models\PatientDocument;
use App\Models\User;

class PatientDocumentPolicy
{
    public function view(User $user, PatientDocument $document): bool
    {
        return $user->can('view', $document->patient);
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Belgeyi yalnızca yükleyen kişi veya yönetici silebilir.
     */
    public function delete(User $user, PatientDocument $document): bool
    {
        return $user->isAdmin()
            || ($document->uploaded_by === $user->id && $user->can('view', $document->patient));
    }
}
