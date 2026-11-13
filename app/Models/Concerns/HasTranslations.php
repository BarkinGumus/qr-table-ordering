<?php

namespace App\Models\Concerns;

/**
 * For columns stored as {"tr": "...", "en": "..."}.
 *
 * $product->translate('name') returns the text in the current interface
 * language, falling back to the app's fallback language and then to
 * whichever language has a value.
 */
trait HasTranslations
{
    public function translate(string $attribute, ?string $locale = null): string
    {
        $values = array_filter((array) $this->getAttribute($attribute), fn ($value) => filled($value));

        foreach ([$locale ?? app()->getLocale(), config('app.locale'), config('app.fallback_locale')] as $candidate) {
            if (isset($values[$candidate])) {
                return $values[$candidate];
            }
        }

        return (string) (reset($values) ?: '');
    }
}
