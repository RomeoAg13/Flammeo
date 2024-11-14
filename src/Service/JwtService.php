<?php

// src/Service/JwtService.php

namespace App\Service;

use App\Entity\User;
use DateTimeZone;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use DateTimeImmutable;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;

class JwtService
{
    private Configuration $config;

    public function __construct()
    {
        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText('xsHMVa861Avtr++3qdL47ACzFB3LKBYD95Vr8DKo7iE=') // Replace with a secure key
        );
    }

    public function createToken(User $user): Plain
    {
        $timezone = new DateTimeZone('Europe/Paris');
        $now = new DateTimeImmutable('now', $timezone);
        $expiresAt = $now->modify('+1 hour');

        return $this->config->builder()
            ->issuedBy('auth-web-app') // Configures the issuer (iss claim)
            ->permittedFor('auth-web-app') // Configures the audience (aud claim)
            ->identifiedBy(bin2hex(random_bytes(16)), true) // Configures the id (jti claim)
            ->issuedAt($now) // Configures the time that the token was issued (iat claim)
            ->canOnlyBeUsedAfter($now) // Configures the time before which the token cannot be accepted (nbf claim)
            ->expiresAt($expiresAt) // Configures the expiration time (exp claim)
            ->withClaim('uid', $user->getId()) // Configures a new claim, called "uid"
            ->getToken($this->config->signer(), $this->config->signingKey());
    }

    public function parseToken(string $token): ?Plain
    {
        try {
            $token = $this->config->parser()->parse($token);
            assert($token instanceof Plain);

            return $token;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return null;
        }
    }
}