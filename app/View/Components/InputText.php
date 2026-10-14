<?php

namespace App\View\Components;

use Illuminate\View\Component;

class InputText extends Component
{
    public $label;
    public $id;
    public $name;
    public $value;
    public $placeholder;
    public $required;
    public $colWidth;
    public $classes;
    public $type;
    public $maxlength;

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
        $colWidth = 'col-sm-12 col-xl-4 m-b-30',
        $classes = 'form-control',
         $type ='text',
         $maxlength =""
    ) {
        $this->label = $label;
        $this->id = $id;
        $this->name = $name ?? $id;
        $this->value = old($id, $value ?? '');
        $this->placeholder = $placeholder ?? "Saisir le $label";
        $this->required = $required;
        $this->colWidth = $colWidth;
        $this->classes = $classes;
        $this->type = $type;
         $this->maxlength = $maxlength;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.input-text');
    }
}