<?php

namespace App\Exceptions;

use Exception;

class OccupationIncompatibleException extends Exception
{
    protected string $typeParent;
    protected string $typeEnfant;
    protected string $niveauParent; // 'cite' ou 'batiment'

    public function __construct(
        string $typeParent,
        string $typeEnfant,
        string $niveauParent = 'cite',
        string $message = '',
        int $code = 422
    ) {
        $this->typeParent   = $typeParent;
        $this->typeEnfant   = $typeEnfant;
        $this->niveauParent = $niveauParent;

        if (empty($message)) {
            $message = $this->buildMessage();
        }

        parent::__construct($message, $code);
    }

    private function buildMessage(): string
    {
        $labels = [
            'mixte'  => 'Mixte',
            'hommes' => 'Hommes uniquement',
            'femmes' => 'Femmes uniquement',
        ];

        $labelParent = $labels[$this->typeParent] ?? $this->typeParent;
        $labelEnfant = $labels[$this->typeEnfant] ?? $this->typeEnfant;

        if ($this->niveauParent === 'cite') {
            return "Incompatibilité d'occupation : la cité est de type « {$labelParent} », "
                . "le bâtiment ne peut pas être de type « {$labelEnfant} ». "
                . "Les types autorisés sont : " . implode(', ', $this->getAllowedTypes());
        }

        return "Incompatibilité d'occupation : le bâtiment est de type « {$labelParent} », "
            . "la chambre ne peut pas être de type « {$labelEnfant} ». "
            . "Les types autorisés sont : " . implode(', ', $this->getAllowedTypes());
    }

    private function getAllowedTypes(): array
    {
        if ($this->typeParent === 'mixte') {
            return ['mixte', 'hommes', 'femmes'];
        }
        return [$this->typeParent];
    }

    public function getTypeParent(): string  { return $this->typeParent; }
    public function getTypeEnfant(): string  { return $this->typeEnfant; }
    public function getNiveauParent(): string { return $this->niveauParent; }

    public function toArray(): array
    {
        return [
            'success'       => false,
            'error_type'    => 'occupation_incompatible',
            'niveau_parent' => $this->niveauParent,
            'type_parent'   => $this->typeParent,
            'type_enfant'   => $this->typeEnfant,
            'types_autorisés' => $this->getAllowedTypes(),
            'message'       => $this->getMessage(),
        ];
    }
}