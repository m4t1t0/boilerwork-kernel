#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Support;

use Boilerwork\Support\ValueObjects\Language\Language;
use Boilerwork\Validation\Assert;

use function json_encode;
use function json_decode;
use function array_merge;

readonly class MultiLingualText
{
    private function __construct(private array $texts)
    {
    }

    /**
     * Creates an empty ValueObject from a text and a language.
     *
     * @throws CustomAssertionFailedException
     */
    public static function fromSingleLanguageString(string $text, string $language = Language::FALLBACK): self
    {
        Assert::lazy()
            ->tryAll()
            ->that($language, 'language.invalidIso3166Alpha2')
            ->inArray(Language::ACCEPTED_LANGUAGES)
            ->that($text, 'text.invalidText')
            ->notEmpty('Text must not be empty')
            ->verifyNow();

        return new self([$language => $text]);
    }

    /**
     * Creates a ValueObject from an array.
     */
    public static function fromArray(array $texts): self
    {
        return new self($texts);
    }

    /**
     * Creates a ValueObject from a JSON string.
     *
     * @throws CustomAssertionFailedException
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        Assert::lazy()
            ->tryAll()
            ->that($data, 'texts.invalidJson')
            ->isArray('Invalid JSON format')
            ->verifyNow();

        return new self($data);
    }

    /**
     * Adds a text in a specific language.
     *
     * @throws CustomAssertionFailedException
     */
    public function addText(string $text, string $language = Language::FALLBACK): self
    {
        Assert::lazy()
            ->tryAll()
            ->that($language, 'language.invalidIso3166Alpha2')
            ->inArray(Language::ACCEPTED_LANGUAGES)
            ->that($text, 'text.invalidText')
            ->notEmpty('Text must not be empty')
            ->verifyNow();

        $newTexts = $this->texts;
        $newTexts[$language] = $text;

        return new self($newTexts);
    }

    /**
     * Adds or replaces values from an array.
     *
     * @throws CustomAssertionFailedException
     */
    public function addOrReplaceFromArray(array $texts): self
    {
        $newTexts = array_merge($this->texts, $texts);

        return new self($newTexts);
    }

    /**
     * Returns a new instance of MultiLingualText ensuring that the default language is present in the texts array.
     *
     * @param string $defaultLanguage The default language code (e.g., 'ES')
     * @return self
     */
    public function withDefaultLanguage(string $defaultLanguage = Language::FALLBACK): self
    {
        if (isset($this->texts[$defaultLanguage])) {
            return $this;
        }

        $firstText = $this->getDefaultText();
        if ($firstText === null) {
            return $this;
        }

        $newTexts = $this->texts;
        $newTexts[$defaultLanguage] = $firstText;

        return new self($newTexts);
    }

    /**
     * Returns a new instance of MultiLingualText ensuring that all accepted languages are present in the texts array.
     * If a language is not present in the texts array, the default text will be added for that language.
     *
     * @param array $acceptedLanguages The array of accepted language codes (e.g., ['ES', 'EN', 'FR'])
     * @return self
     */
    public function withAcceptedLanguages(array $acceptedLanguages = Language::ACCEPTED_LANGUAGES): self
    {
        $newTexts = $this->texts;
        $defaultText = $this->getDefaultText();

        foreach ($acceptedLanguages as $language) {
            if (!isset($newTexts[$language])) {
                $newTexts[$language] = $defaultText;
            }
        }

        return new self($newTexts);
    }

    /**
     * Returns the array with all values.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->texts;
    }

    /**
     * Returns a string with fallback language
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Returns a string with fallback language
     */
    public function toString(): string
    {
        return $this->getTextByLanguage();
    }

    /**
     * Returns the text in the required language
     *
     * @throws CustomAssertionFailedException
     */
    public function getTextByLanguage(string $language = Language::FALLBACK): ?string
    {
        Assert::lazy()
            ->tryAll()
            ->that($language, 'language.invalidIso3166Alpha2')
            ->inArray(Language::ACCEPTED_LANGUAGES)
            ->verifyNow();

        return $this->texts[$language] ?? $this->texts[Language::FALLBACK] ?? null;
    }

    /**
     * Returns the default text, which is the first available text in the array.
     *
     * @return string|null The default text or null if no texts are available
     */
    public function getDefaultText(): ?string
    {
        return current(array_values($this->texts)) ?: null;
    }

    /**
     * Returns the object in JSON format.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->texts);
    }

    /**
     * Returns the text and the required language in JSON format.
     * @example: { 'ES': 'Text Localised' }
     *
     * @throws CustomAssertionFailedException
     */
    public function getJsonTextByLanguage(string $language = Language::FALLBACK): ?string
    {
        $text = $this->getTextByLanguage($language);

        if ($text === null) {
            return null;
        }

        return json_encode([$language => $text], JSON_THROW_ON_ERROR);
    }
}
