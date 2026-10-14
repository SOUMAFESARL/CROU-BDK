<?php

namespace App\View\Components;

use Illuminate\View\Component;

class TextareaField extends Component
{
    public $label;
    public $id;
    public $name;
    public $value;
    public $placeholder;
    public $required;
    public $rows;
    public $maxlength;
    public $colWidth;
    public $classes;
    public $disabled;
    public $readonly;
    public $helpText;

    /**
     * Create a new component instance.
     */
    public function __construct(
        $label,
        $id,
        $name = null,
        $value = null,
        $placeholder = null,
        $required = false,
        $rows = 4,
        $maxlength = null,
        $colWidth = 'col-sm-12 col-xl-8',
        $classes = 'form-control max-textarea',
        $disabled = false,
        $readonly = false,
        $helpText = null
    ) {
        $this->label = $label;
        $this->id = $id;
        $this->name = $name ?? $id;
        $this->value = old($id, $value ?? '');
        $this->placeholder = $placeholder ?? "Saisir la " . strtolower($label);
        $this->required = $required;
        $this->rows = $rows;
        $this->maxlength = $maxlength;
        $this->colWidth = $colWidth;
        $this->classes = $classes;
        $this->disabled = $disabled;
        $this->readonly = $readonly;
        $this->helpText = $helpText;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.textarea-field');
    }
}