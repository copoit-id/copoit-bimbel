<?php

namespace App\Models\Concerns;

trait HasIndividualPricing
{
    public function isIndividualAccessEnabled(): bool
    {
        return (bool) $this->is_for_sale;
    }

    public function priceType(): string
    {
        $type = (string) ($this->type_price ?? 'paid');

        return in_array($type, ['paid', 'free_unconditional', 'free_conditional'], true)
            ? $type
            : 'paid';
    }

    public function isPaidIndividualAccess(): bool
    {
        return $this->isIndividualAccessEnabled()
            && $this->priceType() === 'paid'
            && (int) $this->price > 0;
    }

    public function isFreeUnconditionalIndividualAccess(): bool
    {
        return $this->isIndividualAccessEnabled()
            && $this->priceType() === 'free_unconditional';
    }

    public function isFreeConditionalIndividualAccess(): bool
    {
        return $this->isIndividualAccessEnabled()
            && $this->priceType() === 'free_conditional';
    }

    public function isIndividuallyAvailable(): bool
    {
        return $this->isPaidIndividualAccess()
            || $this->isFreeUnconditionalIndividualAccess()
            || $this->isFreeConditionalIndividualAccess();
    }

    public function getPriceTypeLabelAttribute(): string
    {
        return match ($this->priceType()) {
            'free_unconditional' => 'Gratis',
            'free_conditional' => 'Gratis Bersyarat',
            default => 'Berbayar',
        };
    }
}
