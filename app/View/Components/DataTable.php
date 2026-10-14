<?php

namespace App\View\Components;

use Illuminate\View\Component;

class DataTable extends Component
{
       public $title;
    public $id;
    public $columns;
    public $data;
    public $ajaxUrl;
    public $colWidth;
    public $showSearch;
    public $showActions;
    public $actions;
    public $showFooter;
    public $responsive;
    public $striped;
    public $exportable;

    /**
     * Create a new component instance.
     */
    public function __construct(
        $title = 'Zero Configuration',
        $id = 'simpletable',
        $columns = [],
        $data = [],
        $ajaxUrl = null,
        $colWidth = 'col-sm-12',
        $showSearch = true,
        $showActions = true,
        $actions = ['view', 'edit', 'delete'],
        $showFooter = true,
        $responsive = true,
        $striped = true,
        $exportable = false
    ) {
        $this->title = $title;
        $this->id = $id . '-' . uniqid();
        $this->columns = $this->formatColumns($columns);
        $this->data = $data;
        $this->ajaxUrl = $ajaxUrl;
        $this->colWidth = $colWidth;
        $this->showSearch = $showSearch;
        $this->showActions = $showActions;
        $this->actions = $actions;
        $this->showFooter = $showFooter;
        $this->responsive = $responsive;
        $this->striped = $striped;
        $this->exportable = $exportable;
    }

    /**
     * Format columns array
     */
    private function formatColumns($columns)
    {
        if (empty($columns)) {
            return [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'position', 'label' => 'Position'],
                ['key' => 'office', 'label' => 'Office'],
                ['key' => 'age', 'label' => 'Age'],
                ['key' => 'start_date', 'label' => 'Start date'],
                ['key' => 'salary', 'label' => 'Salary'],
            ];
        }

        $formatted = [];
        foreach ($columns as $column) {
            if (is_array($column)) {
                $formatted[] = $column;
            } else {
                $formatted[] = ['key' => $column, 'label' => ucfirst(str_replace('_', ' ', $column))];
            }
        }
        return $formatted;
    }


    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.data-table');
    }
}