<?php
// src/Service/JwtService.php

namespace App\Service;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\Clock\SystemClock;
use DateTimeImmutable;
use Exception;

class JwtService
{
    private Configuration $config;

    public function __construct()
    {
        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText('$2y$10$Q/JcqI92gnjwkKq9F/02neMXwHaFtRijvbHwLkknkv1muvaRohAKK')
        );
    }

    public function createToken(User $user): Plain
    {
        $now = new DateTimeImmutable();
        $expiresAt = $now->modify('+1 hour');

        return $this->config->builder()
            ->issuedBy('http://localhost')
            ->permittedFor('http://localhost')
            ->identifiedBy(bin2hex(random_bytes(16)), true)
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($expiresAt)
            ->withClaim('uid', $user->getId())
            ->getToken($this->config->signer(), $this->config->signingKey());
    }

    public function parseToken(string $token): ?Plain
    {
        try {
            $parsedToken = $this->config->parser()->parse($token);
            if (!$parsedToken instanceof Plain) {
                return null;
            }

            $constraints = [
                new IssuedBy('http://localhost'),
                new LooseValidAt(SystemClock::fromUTC()),
            ];

            if (!$this->config->validator()->validate($parsedToken, ...$constraints)) {
                return null;
            }

            return $parsedToken;
        } catch (Exception $e) {
            return null;
        }
    }
}