<?php

namespace App\View\Components;

use Illuminate\View\Component;

class SelectMultiple extends Component
{
       public $label;
    public $id;
    public $name;
    public $options;
    public $selected;
    public $placeholder;
    public $required;
    public $disabled;
    public $colWidth; // Assurez-vous que cette propriété existe
    public $selectClasses;
    public $helpText;
    public $allowClear;
    public $closeOnSelect;
    public $multiple;

    /**
     * Create a new component instance.
     */
    public function __construct(
        $label,
        $id,
        $options = [],
        $selected = [],
        $name = null,
        $placeholder = 'Sélectionnez des options',
        $required = false,
        $disabled = false,
        $colWidth = 'col-sm-12 col-xl-4 m-b-30', // Valeur par défaut
        $selectClasses = 'form-control',
        $helpText = null,
        $allowClear = true,
        $closeOnSelect = false,
        $multiple= false
    ) {
        $this->label = $label;
        $this->id = $id;
        $this->name = $name ?? $id . '[]';
        $this->options = $this->formatOptions($options);
        $this->selected = is_array($selected) ? $selected : [$selected];
        $this->placeholder = $placeholder;
        $this->required = $required;
        $this->disabled = $disabled;
        $this->colWidth = $colWidth; // Initialisation de la propriété
        $this->selectClasses = $selectClasses;
        $this->helpText = $helpText;
        $this->allowClear = $allowClear;
        $this->closeOnSelect = $closeOnSelect;
        $this->multiple = $multiple;
    }

    /**
     * Format options array
     */
    private function formatOptions($options)
    {
        if (empty($options)) {
            return [
                ['value' => 'cheese', 'text' => 'Cheese'],
                ['value' => 'h', 'text' => 'Hanry Die'],
                ['value' => 'c', 'text' => 'Come Leo'],
                ['value' => 'h2', 'text' => 'Hampri Catlin'],
            ];
        }

        $formatted = [];
        foreach ($options as $key => $value) {
            if (is_array($value)) {
                $formatted[] = $value;
            } else {
                $formatted[] = ['value' => $key, 'text' => $value];
            }
        }
        return $formatted;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.select-multiple');
    }
}
