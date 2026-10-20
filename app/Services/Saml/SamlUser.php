<?php

namespace App\Services\Saml;

use Illuminate\Container\Container;

class SamlUser
{
    /**
     * @param array<string, array<int, string>> $attributes
     */
    public function __construct(
        public readonly string $nameId,
        public readonly array $attributes = [],
        public readonly string|null $sessionIndex = null,
        public readonly string|null $nameIdFormat = null,
    ) {
    }

    public function getNameId(): string
    {
        return $this->nameId;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<int, string>|null
     */
    public function getAttribute(string $name): array|null
    {
        return $this->attributes[$name] ?? null;
    }

    public function getFirstAttribute(string $name, string|null $default = null): string|null
    {
        $attribute = $this->getAttribute($name);

        if ($attribute !== null && count($attribute) > 0) {
            return $attribute[0];
        }

        return $default;
    }

    public function getEmail(string|null $attributeName = null): string
    {
        if ($attributeName === null && Container::getInstance()->bound('config')) {
            /** @var mixed $configured */
            $configured = config('saml.attributes.email', 'email');
            $attributeName = is_string($configured) ? $configured : 'email';
        }

        $attributeName = $attributeName ?? 'email';
        $email = $this->getFirstAttribute($attributeName);

        if ($email !== null && $email !== '') {
            return $email;
        }

        return $this->nameId;
    }

    public function getName(string|null $attributeName = null): string
    {
        if ($attributeName === null && Container::getInstance()->bound('config')) {
            /** @var mixed $configured */
            $configured = config('saml.attributes.name', 'name');
            $attributeName = is_string($configured) ? $configured : 'name';
        }

        $attributeName = $attributeName ?? 'name';
        $name = $this->getFirstAttribute($attributeName);

        if ($name !== null && $name !== '') {
            return $name;
        }

        return $this->getEmail();
    }

    public function getSessionIndex(): string|null
    {
        return $this->sessionIndex;
    }

    public function getNameIdFormat(): string|null
    {
        return $this->nameIdFormat;
    }
}
