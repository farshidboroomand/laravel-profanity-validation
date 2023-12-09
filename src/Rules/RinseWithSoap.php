<?php

namespace Farshidboroomand\ProfanityValidation\Rules;

use Closure;
use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Farshidboroomand\ProfanityValidation\Check;

class RinseWithSoap implements InvokableRule
{
    /**
     * Run the validation rule.
     * Check if the given value is profane.
     *
     * @param  string  $attribute
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function __invoke($attribute, $value, $fail): void
    {
        $profanityFilter = new Check(config('profanity.blacklist'), config('profanity.whitelist'));

        if ($profanityFilter->hasProfanity($value)) {
            $fail(__('auth.validation.profanity'));
        }
    }
}
