<?php

namespace App\View\Components;

use Illuminate\View\Component;

class CardTable extends Component
{
    public $title;
    public $headers;
    public $viewAllLink;
    public $viewAllText;
    public $hasCheckbox;
    public $cardOptions;
    public $tableClass;

    /**
     * Create a new component instance.
     *
     * @param string $title
     * @param array $headers
     * @param string $viewAllLink
     * @param string $viewAllText
     * @param bool $hasCheckbox
     * @param bool $cardOptions
     * @param string $tableClass
     */
    public function __construct(
        $title = 'Application Sales',
        $headers = ['Application', 'Sales', 'Change', 'Avg Price', 'Total'],
        $viewAllLink = '#',
        $viewAllText = 'View all Projects',
        $hasCheckbox = true,
        $cardOptions = true,
        $tableClass = 'table-hover table-borderless'
    ) {
        $this->title = $title;
        $this->headers = is_array($headers) ? $headers : explode(',', $headers);
        $this->viewAllLink = $viewAllLink;
        $this->viewAllText = $viewAllText;
        $this->hasCheckbox = $hasCheckbox;
        $this->cardOptions = $cardOptions;
        $this->tableClass = $tableClass;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.card-table');
    }
}