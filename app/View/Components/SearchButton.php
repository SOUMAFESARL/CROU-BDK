<?php

namespace App\View\Components;

use Illuminate\View\Component;

class SearchButton extends Component
{
    public $label;
    public $type;
    public $colWidth;
    public $btnClasses;
    public $icon;
    public $id;
    public $name;
    public $disabled;
    public $onclick;
    public $form;
    public $tooltip;

    /**
     * Create a new component instance.
     */
    public function __construct(
        $label = 'Recherche',
        $type = 'submit',
        $colWidth = 'col-sm-6 col-xl-3 mt-4',
        $btnClasses = 'btn btn-primary btn-round',
        $icon = null,
        $id = null,
        $name = null,
        $disabled = false,
        $onclick = null,
        $form = null,
        $tooltip = null
    ) {
        $this->label = $label;
        $this->type = $type;
        $this->colWidth = $colWidth;
        $this->btnClasses = $btnClasses;
        $this->icon = $icon;
        $this->id = $id ?? 'search-btn-' . uniqid();
        $this->name = $name;
        $this->disabled = $disabled;
        $this->onclick = $onclick;
        $this->form = $form;
        $this->tooltip = $tooltip;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.search-button');
    }
}
