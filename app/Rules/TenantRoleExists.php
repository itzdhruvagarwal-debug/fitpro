<?php

namespace App\Rules;

use App\Support\Tenancy\TenantRoleQuery;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Spatie\Permission\Models\Role;

/**
 * Validate role ids against the active tenant.
 *
 * Global roles are allowed, but roles owned by another gym are rejected.
 */
final class TenantRoleExists implements ValidationRule
{
    /**
     * @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_scalar($value)) {
            $fail(__('validation.exists', ['attribute' => $attribute]));

            return;
        }

        $exists = TenantRoleQuery::apply(Role::query())
            ->whereKey($value)
            ->exists();

        if (! $exists) {
            $fail(__('validation.exists', ['attribute' => $attribute]));
        }
    }
}
