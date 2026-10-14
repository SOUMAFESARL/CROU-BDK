<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Alert extends Component
{
    public string $type;
    public ?string $message;
    public bool $dismissible;

    /**
     * Create a new component instance.
     */
    public function __construct(string $type = 'success', ?string $message = null, bool $dismissible = true)
    {
        $this->type = $type;
        $this->message = $message;
        $this->dismissible = $dismissible;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.alert');
    }

    /**
     * Get the icon for the alert type.
     */
    public function icon(): string
    {
        return match ($this->type) {
            'success' => 'check-circle',
            'danger', 'error','errors' => 'times-circle',
            'warning' => 'exclamation-triangle',
            'info' => 'info-circle',
            default => 'bell',
        };
    }

    /**
     * Get the background color class.
     */
    public function bgClass(): string
    {
        return match ($this->type) {
            'success' => 'alert-success',
            'danger', 'error','errors' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info',
            default => 'alert-primary',
        };
    }
}