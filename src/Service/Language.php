<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\Service;

use ilObjUser;

class Language
{
    protected string $user_language;

    public function __construct(
        protected ilObjUser $user
    ) {
    }

    protected function getUserLanguage(): string
    {
        return $this->user_language ??= $this->user->getCurrentLanguage();
    }

    public function txt(string $key): string
    {
        return $this->lookupLangVar(
            $key,
            $this->getUserLanguage()
        );
    }

    public function txtFill(string $key, string ...$value): string
    {
        return sprintf($this->txt($key), ...$value);
    }

    protected function lookupLangVar(string $key, string $lang): string
    {
        if (!in_array($lang, ['en', 'de'])) {
            $lang = 'en';
        }
        return match ([$lang, $key]) {
            // english
            ['en', 'definition_values'] => 'Configuration of Selection Lists',
            ['en', 'cascading_type_name'] => 'Selection List (Cascading)',
            ['en', 'cascading_type_form_update'] => 'Update Selection List (Cascading)',
            ['en', 'cascading_incomplete'] => 'This input is incomplete. Please fill it out completely or not at all.',
            ['en', 'cascading_required'] => 'This input is required. Please fill it out completely.',
            ['en', 'cascading_no_opts'] => 'No options available.',
            //german
            ['de', 'definition_values'] => 'Konfiguration der Auswahlisten',
            ['de', 'cascading_type_name'] => 'Auswahlliste (kaskadierend)',
            ['de', 'cascading_type_form_update'] => 'Auswahlliste (kaskadierend) bearbeiten',
            ['de', 'cascading_incomplete'] => 'Dieses Feld muss entweder vollständig oder gar nicht ausgefüllt werden.',
            ['de', 'cascading_required'] => 'Dieses Feld muss vollständig ausgefüllt werden.',
            ['de', 'cascading_no_opts'] => 'Keine Optionen verfügbar.'
        };
    }
}
