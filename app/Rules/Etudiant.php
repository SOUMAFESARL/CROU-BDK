<?php
namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class Etudiant implements Rule
{
    protected $formatsAcceptes;
    protected $tailleMax;

    public function __construct(array $formatsAcceptes, int $tailleMax = 2048)
    {
        $this->formatsAcceptes = $formatsAcceptes;
        $this->tailleMax = $tailleMax; // en KB
    }

    public function passes($attribute, $value)
    {
        if (!$value) {
            return true; // Si le champ est optionnel
        }

        // Vérification du format
        $extension = strtolower($value->getClientOriginalExtension());
        if (!in_array($extension, $this->formatsAcceptes)) {
            return false;
        }

        // Vérification de la taille (en KB)
        if ($value->getSize() > $this->tailleMax * 1024) {
            return false;
        }

        return true;
    }

    public function message()
    {
        return 'Le fichier :attribute doit être au format : ' . implode(', ', $this->formatsAcceptes) .
            ' et ne pas dépasser ' . ($this->tailleMax / 1024) . ' Mo.';
    }
}