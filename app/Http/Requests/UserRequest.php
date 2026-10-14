<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\SystemSecurity;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('admin');
        if (!$actor) {
            return false;
        }

        $target = $this->route('id_user')
            ? \App\Models\User::find($this->route('id_user'))
            : null;

        // Editing a privileged account also permits changing its password.
        if ((int) $target?->role_id === 1 && (int) $actor->role_id !== 1) {
            return false;
        }

        $changesRole = !$target || (string) $target->role_id !== (string) $this->input('role_id');
        if ($changesRole && !$actor->hasPermission('parametrage.roles.assign')) {
            return false;
        }

        return !$changesRole || (int) $this->input('role_id') !== 1 || (int) $actor->role_id === 1;
    }

    /** Préfixes des numéros ivoiriens à 10 chiffres (mobiles Moov, MTN, Orange et fixes). */
    public const PREFIXES_TELEPHONE_CI = ['01', '05', '07', '21', '25', '27'];

    /** Le formulaire affiche le téléphone par paires (« 07 00 00 00 00 ») : on retire les espaces avant validation. */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('telephone_principal'))) {
            $this->merge(['telephone_principal' => str_replace([' ', "\u{00A0}"], '', $this->input('telephone_principal'))]);
        }
    }

    public function rules(): array
    {
        $userId = $this->route('id_user');

        $passwordRules = $this->isMethod('POST')
            ? ['required', SystemSecurity::passwordRule()]
            : ['nullable', SystemSecurity::passwordRule()];

        return [
            'name'                => 'required|string|max:255',
            'genre_id'            => 'required|exists:genre,id',
            'telephone_principal' => ['required', 'string', 'regex:/\A[0-9]{10}\z/', function (string $attribute, mixed $value, \Closure $fail) {
                if (is_string($value) && preg_match('/\A[0-9]{10}\z/', $value)
                    && !in_array(substr($value, 0, 2), self::PREFIXES_TELEPHONE_CI, true)) {
                    $fail('Le téléphone doit être un numéro ivoirien commençant par '.implode(', ', self::PREFIXES_TELEPHONE_CI).'.');
                }
            }],
            'email'               => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId, 'id_user'),
            ],
            'role_id'             => 'required|exists:roles,id',
            'password'            => $passwordRules,
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                => 'Le nom est obligatoire.',
            'genre_id.required'            => 'La civilité est obligatoire.',
            'genre_id.exists'              => 'La civilité sélectionnée est invalide.',
            'telephone_principal.required' => 'Le téléphone principal est obligatoire.',
            'telephone_principal.regex'    => 'Le téléphone doit contenir exactement 10 chiffres.',
            'email.required'               => "L'adresse email est obligatoire.",
            'email.email'                  => "L'adresse email n'est pas valide.",
            'email.unique'                 => 'Cette adresse email est déjà utilisée.',
            'role_id.required'             => 'Le rôle est obligatoire.',
            'role_id.exists'               => 'Le rôle sélectionné est invalide.',
            'password.required'            => 'Le mot de passe est obligatoire.',
            'password.min'                 => 'Le mot de passe doit contenir au moins 6 caractères.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'                => 'nom',
            'genre_id'            => 'civilité',
            'telephone_principal' => 'téléphone principal',
            'email'               => 'adresse email',
            'role_id'             => 'rôle',
            'password'            => 'mot de passe',
        ];
    }
}
