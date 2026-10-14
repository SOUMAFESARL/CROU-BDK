<?php

namespace App\View\Components;

use Illuminate\View\Component;

class SocialCard extends Component
{
    public $color;
    public $icon;
    public $title;
    public $value;
    public $description;
    public $iconSize;
    public $badge;
    public $badgeColor;
    public $iconBorder;
    public $borderWidth;
    public $iconBg;
    public $iconShape;
    public $pulseEffect;
    public $doubleBorder;

    public function __construct(
        $color = 'blue',
        $icon = 'feather icon-mail',
        $title = 'Mail',
        $value = '',
        $description = '',
        $iconSize = 'f-34',
        $badge = null,
        $badgeColor = 'warning',
        $iconBorder = true,
        $borderWidth = '2px',
        $iconBg = 'transparent',
        $iconShape = 'circle',
        $pulseEffect = false,
        $doubleBorder = false
    ) {
        $this->color = $color;
        $this->icon = $icon;
        $this->title = $title;
        $this->value = $value;
        $this->description = $description;
        $this->iconSize = $iconSize;
        $this->badge = $badge;
        $this->badgeColor = $badgeColor;
        $this->iconBorder = $iconBorder;
        $this->borderWidth = $borderWidth;
        $this->iconBg = $iconBg;
        $this->iconShape = $iconShape;
        $this->pulseEffect = $pulseEffect;
        $this->doubleBorder = $doubleBorder;
    }

    public function render()
    {
        return view('components.social-card');
    }
}