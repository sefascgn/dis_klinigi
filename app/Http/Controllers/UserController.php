<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\UserRequest;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $role = UserRole::tryFrom((string) $request->query('role'));

        $users = User::query()
            ->with('doctor')
            ->when($role, fn ($q) => $q->where('role', $role))
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where(
                fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")
            ))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('users.create', ['user' => new User(['is_active' => true, 'role' => UserRole::Secretary])]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = new User($request->safe()->only(['name', 'email', 'phone', 'password']));
        $user->role = UserRole::from($request->validated('role'));
        $user->is_active = $request->validated('is_active');
        $user->email_verified_at = now();
        $user->save();

        $message = 'Kullanıcı oluşturuldu.';
        if ($user->isDoctor()) {
            $message .= ' Doktor ekranından bu hesabı bir doktor kaydına bağlamayı unutmayın.';
        }

        return redirect()->route('users.index')->with('success', $message);
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('users.edit', compact('user'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user) {
            $user->fill($request->safe()->only(['name', 'email', 'phone']));
            $user->role = UserRole::from($request->validated('role'));
            $user->is_active = $request->validated('is_active');

            if ($request->filled('password')) {
                $user->password = $request->validated('password');
            }

            // Doktor rolünden çıkan kullanıcının doktor kaydıyla bağı kaldırılır.
            if (! $user->isDoctor()) {
                $user->doctor()->update(['user_id' => null]);
            }

            $user->save();
        });

        return redirect()->route('users.index')->with('success', 'Kullanıcı güncellendi.');
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        // Silinirse ödeme/giderlerdeki "kim aldı / kim girdi" bilgisi boşalır.
        $hasFinancialRecords = Payment::withTrashed()->where('received_by', $user->id)->exists()
            || Expense::withTrashed()->where('created_by', $user->id)->exists();

        if ($hasFinancialRecords) {
            return back()->with('error', 'Bu kullanıcının kayıtlı ödeme veya gider işlemleri var. Silmek yerine hesabı pasifleştirin.');
        }

        $user->delete();

        return back()->with('success', 'Kullanıcı silindi.');
    }
}
