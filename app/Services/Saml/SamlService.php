<?php

namespace App\Services\Saml;

use OneLogin\Saml2\Auth;
use RuntimeException;

class SamlService
{
    protected Auth|null $auth = null;

    /**
     * @param array<string, mixed>|null $config
     */
    public function __construct(
        protected array|null $config = null,
        Auth|null $auth = null,
    ) {
        if ($auth !== null) {
            $this->auth = $auth;
        }
    }

    public function getAuth(): Auth
    {
        if ($this->auth === null) {
            /** @var array<string, mixed> $settings */
            $settings = $this->config ?? config('saml');
            $this->auth = new Auth($settings);
        }

        return $this->auth;
    }

    public function getMetadata(): string
    {
        $settings = $this->getAuth()->getSettings();
        $metadata = $settings->getSPMetadata();
        $errors = $settings->validateMetadata($metadata);

        if (!empty($errors)) {
            throw new RuntimeException('Invalid SP metadata: ' . implode(', ', $errors));
        }

        return $metadata;
    }

    /**
     * @param array<string, string> $parameters
     */
    public function login(
        string|null $returnTo = null,
        array $parameters = [],
        bool $forceAuthn = false,
        bool $isPassive = false,
        bool $stay = true,
        bool $setNameIdPolicy = true,
        string|null $nameIdValueReq = null,
    ): string|null {
        return $this->getAuth()->login(
            $returnTo,
            $parameters,
            $forceAuthn,
            $isPassive,
            $stay,
            $setNameIdPolicy,
            $nameIdValueReq,
        );
    }

    /**
     * @param array<string, string> $parameters
     */
    public function logout(
        string|null $returnTo = null,
        array $parameters = [],
        string|null $nameId = null,
        string|null $sessionIndex = null,
        bool $stay = true,
        string|null $nameIdFormat = null,
    ): string|null {
        return $this->getAuth()->logout(
            $returnTo,
            $parameters,
            $nameId,
            $sessionIndex,
            $stay,
            $nameIdFormat,
        );
    }

    public function processAcs(): SamlUser
    {
        $auth = $this->getAuth();
        $auth->processResponse();

        $errors = $auth->getErrors();
        if (!empty($errors)) {
            throw new RuntimeException($auth->getLastErrorReason() ?: implode(', ', $errors));
        }

        if (!$auth->isAuthenticated()) {
            throw new RuntimeException('SAML user is not authenticated.');
        }

        /** @var array<string, array<int, string>> $attributes */
        $attributes = $auth->getAttributes();

        return new SamlUser(
            nameId: $auth->getNameId(),
            attributes: $attributes,
            sessionIndex: $auth->getSessionIndex(),
            nameIdFormat: $auth->getNameIdFormat(),
        );
    }

    public function processSls(bool $keepLocalSession = false, bool $stay = true): string|null
    {
        $auth = $this->getAuth();
        $url = $auth->processSLO($keepLocalSession, null, false, null, $stay);

        $errors = $auth->getErrors();
        if (!empty($errors)) {
            throw new RuntimeException($auth->getLastErrorReason() ?: implode(', ', $errors));
        }

        return $url;
    }
}
