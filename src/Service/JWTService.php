<?php
namespace App\Service;

use DateTimeImmutable;

class JWTService 
{
    //création du token 
    /***
     * 
     */
    public function generate(array $header, array $payload, string $secret, int $validity = 10800):string
    {
        if($validity > 0)
        {
            $now = new DateTimeImmutable();
            $exp = $now->getTimestamp() + $validity;
             
            $payload['iat'] = $now->getTimestamp();
            $payload['exp'] = $exp;
        }

       

        //encodage en base 64
        $base64Header = base64_encode(json_encode($header));
        $base64Payload = base64_encode(json_encode($payload));

        //nettoyage des valeurs encodées ( + / et = qui ne peuvent pas être utilisés dans les json web token)
        $base64Header = str_replace(['+', '/', '='],['-', '_', ''],$base64Header);
        $base64Payload = str_replace(['+', '/', '='],['-', '_', ''],$base64Payload);

        //Secret
        $secret = base64_encode($secret);
        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret, true);
        $base64Signature = base64_encode($signature);
        $base64Signature = str_replace(['+', '/', '='],['-', '_', ''],$base64Signature );

        $jwt = $base64Header . '.' . $base64Payload . '.' . $base64Signature;
        return $jwt;
    }

    // VERIF Token est valide en terme de forme avec exp reguliere
    public function isValid(string $token)
    {
        return preg_match('/^[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+$/', $token) === 1;
    }

    // Recup le header
    public function getHeader(string $token):array
    {
        //on sépare le token en 3 parties
        $array = explode('.', $token);

        // on decode le payload (2eme partie du token)
        $header = json_decode(base64_decode($array[0]), true);

        return $header;
    }

    // Recup le payload
    public function getPayload(string $token):array
    {
        //on sépare le token en 3 parties
        $array = explode('.', $token);

        // on decode le payload (2eme partie du token)
        $payload = json_decode(base64_decode($array[1]), true);

        return $payload;
    }

    // verif si le token n'a pas expiré
    public function isExpired(string $token): bool
    {
        $payload = $this->getPayload($token);
        $now = new DateTimeImmutable();

        return $payload['exp'] < $now->getTimestamp();
    }

    // VERIF la signature du token, pas de modif

    public function check(string $token, string $secret)
    {
        //on recup le header et le payload

        $header = $this->getHeader($token);
        $payload = $this->getPayload($token);

        //on regenere un token (0 pour ne pas regener de date d'expiration)
        $verifToken = $this->generate($header, $payload, $secret, 0);

        return $token === $verifToken;
    }


}