<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;
use Illuminate\View\View;

class Button extends Component
{
    public function __construct(
        public string $variant = 'primary',
        public string $size = 'md',
        public string $type = 'button',
        public ?string $href = null,
        public ?string $icon = null,
        public string $iconPosition = 'left',
        public bool $disabled = false,
        public bool $loading = false,
        public bool $fullWidth = false,
    ) {}

    public function render(): View
    {
        return view('components.ui.Button.index');
    }

    public function shouldRender(): bool
    {
        return true;
    }
}