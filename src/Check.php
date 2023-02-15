<?php

namespace OwowAgency\ProfanityValidation;

class Check
{
    const SEPARATOR_PLACEHOLDER = '{!!}';

    /**
     * Escaped separator characters
     */
    protected $escapedSeparatorCharacters = [
        '\s',
    ];

    /**
     * Unescaped separator characters.
     *
     * @var array
     */
    protected $separatorCharacters = [
        '@',
        '#',
        '%',
        '&',
        '_',
        ';',
        "'",
        '"',
        ',',
        '~',
        '`',
        '|',
        '!',
        '$',
        '^',
        '*',
        '(',
        ')',
        '-',
        '+',
        '=',
        '{',
        '}',
        '[',
        ']',
        ':',
        '<',
        '>',
        '?',
        '.',
        '/',
    ];

    /**
     * List of potential character substitutions as a regular expression.
     *
     * @var array
     */
    protected $characterSubstitutions = [
        '/a/' => [
            'a',
            '4',
            '@',
            'Á',
            'á',
            'À',
            'Â',
            'à',
            'Â',
            'â',
            'Ä',
            'ä',
            'Ã',
            'ã',
            'Å',
            'å',
            'æ',
            'Æ',
            'α',
            'Δ',
            'Λ',
            'λ',
        ],
        '/b/' => ['b', '8', '\\', '3', 'ß', 'Β', 'β'],
        '/c/' => ['c', 'Ç', 'ç', 'ć', 'Ć', 'č', 'Č', '¢', '€', '<', '(', '{', '©'],
        '/d/' => ['d', '\\', ')', 'Þ', 'þ', 'Ð', 'ð'],
        '/e/' => ['e', '3', '€', 'È', 'è', 'É', 'é', 'Ê', 'ê', 'ë', 'Ë', 'ē', 'Ē', 'ė', 'Ė', 'ę', 'Ę', '∑'],
        '/f/' => ['f', 'ƒ'],
        '/g/' => ['g', '6', '9'],
        '/h/' => ['h', 'Η'],
        '/i/' => ['i', '!', '|', ']', '[', '1', '∫', 'Ì', 'Í', 'Î', 'Ï', 'ì', 'í', 'î', 'ï', 'ī', 'Ī', 'į', 'Į'],
        '/j/' => ['j'],
        '/k/' => ['k', 'Κ', 'κ'],
        '/l/' => ['l', '!', '|', ']', '[', '£', '∫', 'Ì', 'Í', 'Î', 'Ï', 'ł', 'Ł'],
        '/m/' => ['m'],
        '/n/' => ['n', 'η', 'Ν', 'Π', 'ñ', 'Ñ', 'ń', 'Ń'],
        '/o/' => [
            'o',
            '0',
            'Ο',
            'ο',
            'Φ',
            '¤',
            '°',
            'ø',
            'ô',
            'Ô',
            'ö',
            'Ö',
            'ò',
            'Ò',
            'ó',
            'Ó',
            'œ',
            'Œ',
            'ø',
            'Ø',
            'ō',
            'Ō',
            'õ',
            'Õ',
        ],
        '/p/' => ['p', 'ρ', 'Ρ', '¶', 'þ'],
        '/q/' => ['q'],
        '/r/' => ['r', '®'],
        '/s/' => ['s', '5', '$', '§', 'ß', 'Ś', 'ś', 'Š', 'š'],
        '/t/' => ['t', 'Τ', 'τ'],
        '/u/' => ['u', 'υ', 'µ', 'û', 'ü', 'ù', 'ú', 'ū', 'Û', 'Ü', 'Ù', 'Ú', 'Ū'],
        '/v/' => ['v', 'υ', 'ν'],
        '/w/' => ['w', 'ω', 'ψ', 'Ψ'],
        '/x/' => ['x', 'Χ', 'χ'],
        '/y/' => ['y', '¥', 'γ', 'ÿ', 'ý', 'Ÿ', 'Ý'],
        '/z/' => ['z', 'Ζ', 'ž', 'Ž', 'ź', 'Ź', 'ż', 'Ż'],
    ];

    /**
     * List of profanities to test against.
     *
     * @var array
     */
    protected $profanities = [];

    protected $whitelist = [];

    protected $separatorExpression;

    protected $characterExpressions;

    /**
     * @param  null|array|string  $config
     * Can be an array or file name.
     */
    public function __construct(array|string $config = null)
    {
        if ($config === null) {
            $profanities = data_get(include __DIR__.'/../config/profanity.php', 'blacklist', []);

            $whitelist = data_get(include __DIR__.'/../config/profanity.php', 'whitelist', []);
        }

        if (is_array($config) && array_key_exists('blacklist', $config)) {
            $this->profanities = data_get($config, 'blacklist', []);
        } else {
            $this->profanities = data_get($this->loadProfanitiesFromFile($config), 'blacklist', []);
        }

        if (is_array($config) && array_key_exists('whitelist', $config)) {
            $this->whitelist = data_get($config, 'whitelist', []);
        } else {
            $this->whitelist = data_get($this->loadProfanitiesFromFile($config), 'whitelist', []);
        }

        $this->separatorExpression = $this->generateSeparatorExpression();
        $this->characterExpressions = $this->generateCharacterExpressions();
    }

    /**
     * Load 'profanities' from config file.
     *
     * @return array
     */
    private function loadProfanitiesFromFile($config)
    {
        return include $config;
    }

    /**
     * Generates the separator regular expression.
     */
    private function generateSeparatorExpression(): string
    {
        return $this->generateEscapedExpression($this->separatorCharacters, $this->escapedSeparatorCharacters);
    }

    /**
     * Generates the separator regex to test characters in between letters.
     *
     * @param  string  $quantifier
     * @return string
     */
    private function generateEscapedExpression(
        array $characters = [],
        array $escapedCharacters = [],
        $quantifier = '*?',
    ) {
        $regex = $escapedCharacters;
        foreach ($characters as $character) {
            $regex[] = preg_quote($character, '/');
        }

        return '['.implode('', $regex).']'.$quantifier;
    }

    /**
     * Generates a list of regular expressions for each character substitution.
     *
     * @return array
     */
    protected function generateCharacterExpressions()
    {
        $characterExpressions = [];
        foreach ($this->characterSubstitutions as $character => $substitutions) {
            $characterExpressions[$character] = $this->generateEscapedExpression(
                $substitutions,
                [],
                '+?',
            ).self::SEPARATOR_PLACEHOLDER;
        }

        return $characterExpressions;
    }

    /**
     * Obfuscates string that contains a 'profanity'.
     */
    public function obfuscateIfProfane($string): string
    {
        if ($this->hasProfanity($string)) {
            $string = str_repeat('*', strlen($string));
        }

        return $string;
    }

    /**
     * Checks string for profanities based on list 'profanities'
     */
    public function hasProfanity($string): bool
    {
        if (empty($string)) {
            return false;
        }

        $profanities = [];
        $profanityCount = count($this->profanities);

        for ($i = 0; $i < $profanityCount; $i++) {
            $profanities[$i] = $this->generateProfanityExpression(
                $this->profanities[$i],
                $this->characterExpressions,
                $this->separatorExpression,
            );
        }

        // Explode the string and preform
        // the profanity check for each word in the string.
        foreach (explode(' ', $string) as $word) {
            // Check if the 'word' has any profanity in it.
            // If the word has profanity return whether it is
            // present in the array or not.
            foreach ($profanities as $profanity) {
                if ($this->stringHasProfanity($word, $profanity)) {
                    return ! in_array($word, $this->whitelist);
                }
            }
        }

        return false;
    }

    /**
     * Generate a regular expression for a particular word
     */
    protected function generateProfanityExpression($word, $characterExpressions, $separatorExpression): array|string
    {
        $expression = '/'.preg_replace(
            array_keys($characterExpressions),
            array_values($characterExpressions),
            $word,
        ).'/i';

        return str_replace(self::SEPARATOR_PLACEHOLDER, $separatorExpression, $expression);
    }

    /**
     * Checks a string against a profanity.
     *
     *
     * @return bool
     */
    private function stringHasProfanity($string, $profanity)
    {
        return preg_match($profanity, $string) === 1;
    }
}
