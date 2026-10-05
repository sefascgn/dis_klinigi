<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Formlardaki hasta seçici bileşeni için hızlı arama.
 */
class PatientSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Patient::class);

        $term = $request->string('q')->trim()->value();

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $patients = Patient::query()
            ->search($term)
            ->ordered()
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'phone', 'national_id'])
            ->map(fn (Patient $p) => [
                'id' => $p->id,
                'name' => $p->full_name,
                'phone' => $p->phone,
            ]);

        return response()->json($patients);
    }
}
