<?php

/**
 * WebAuthn (Passkey) server-side implementation.
 *
 * Supports ES256 (P-256 ECDSA) and RS256 (RSA PKCS#1 v1.5).
 * No external dependencies — uses PHP's built-in openssl_verify().
 */
class WebAuthn
{
    private string $rpId;
    private string $rpName;
    private string $origin;

    public function __construct(string $rpId, string $rpName, string $origin)
    {
        $this->rpId   = $rpId;
        $this->rpName = $rpName;
        $this->origin = rtrim($origin, '/');
    }

    /**
     * Derive rpId and origin from config.php globals, falling back to the HTTP request.
     *
     * Set $webauthn_origin and $webauthn_rpid in config.php when running behind a
     * reverse proxy that terminates TLS (PHP cannot detect the scheme reliably).
     */
    public static function from_request(string $rpName): self
    {
        global $webauthn_origin, $webauthn_rpid;

        if (!empty($webauthn_origin) && !empty($webauthn_rpid)) {
            return new self($webauthn_rpid, $rpName, $webauthn_origin);
        }

        $host   = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $rpId   = preg_replace('/:\d+$/', '', $host);
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $origin = $scheme . '://' . $host;
        return new self($rpId, $rpName, $origin);
    }

    // ─── Challenge ────────────────────────────────────────────────────────────

    public function create_challenge(): string
    {
        $bytes = random_bytes(32);
        $_SESSION['webauthn_challenge'] = base64_encode($bytes);
        return $this->b64url_encode($bytes);
    }

    private function verify_challenge(string $received_b64url): void
    {
        $expected = $_SESSION['webauthn_challenge'] ?? null;
        unset($_SESSION['webauthn_challenge']);
        if (!$expected) {
            throw new RuntimeException('No challenge in session');
        }
        $received = base64_encode($this->b64url_decode($received_b64url));
        if (!hash_equals($expected, $received)) {
            throw new RuntimeException('Challenge mismatch');
        }
    }

    // ─── Registration options ─────────────────────────────────────────────────

    public function get_registration_options(int $user_id, string $email, string $display_name): array
    {
        return [
            'challenge'              => $this->create_challenge(),
            'rp'                     => ['id' => $this->rpId, 'name' => $this->rpName],
            'user'                   => [
                'id'          => $this->b64url_encode(pack('N', $user_id)),
                'name'        => $email,
                'displayName' => $display_name,
            ],
            'pubKeyCredParams'       => [
                ['type' => 'public-key', 'alg' => -7],   // ES256
                ['type' => 'public-key', 'alg' => -257],  // RS256
            ],
            'timeout'                => 60000,
            'attestation'            => 'none',
            'authenticatorSelection' => [
                'residentKey'        => 'preferred',
                'requireResidentKey' => false,
                'userVerification'   => 'preferred',
            ],
        ];
    }

    /**
     * Verify registration response and extract credential data.
     * Returns array with credential_id, public_key (PEM), sign_count.
     */
    public function verify_registration(array $credential): array
    {
        $client_data_raw = $this->b64url_decode($credential['response']['clientDataJSON']);
        $client_data     = json_decode($client_data_raw, true);

        if (($client_data['type'] ?? '') !== 'webauthn.create') {
            throw new RuntimeException('Invalid clientData type');
        }
        if (rtrim($client_data['origin'] ?? '', '/') !== $this->origin) {
            throw new RuntimeException('Origin mismatch: got "' . ($client_data['origin'] ?? '') . '"');
        }
        $this->verify_challenge($client_data['challenge'] ?? '');

        $att_obj   = $this->cbor_decode($this->b64url_decode($credential['response']['attestationObject']));
        $auth_data = $att_obj['authData'];
        $parsed    = $this->parse_auth_data($auth_data, true);

        if ($parsed['rpIdHash'] !== hash('sha256', $this->rpId, true)) {
            throw new RuntimeException('rpIdHash mismatch');
        }
        if (!($parsed['flags'] & 0x01)) {
            throw new RuntimeException('User not present');
        }
        if (empty($parsed['credentialId']) || $parsed['credentialPublicKey'] === null) {
            throw new RuntimeException('No attested credential data');
        }

        return [
            'credential_id' => $this->b64url_encode($parsed['credentialId']),
            'public_key'    => $this->cose_to_pem($parsed['credentialPublicKey']),
            'sign_count'    => $parsed['signCount'],
        ];
    }

    // ─── Authentication options ───────────────────────────────────────────────

    /**
     * @param array $passkeys  Array of rows from passkeys table (each with 'credential_id').
     *                         Pass empty array for discoverable-credential (resident-key) flow.
     */
    public function get_authentication_options(array $passkeys = []): array
    {
        $opts = [
            'challenge'        => $this->create_challenge(),
            'timeout'          => 60000,
            'rpId'             => $this->rpId,
            'userVerification' => 'preferred',
        ];
        if (!empty($passkeys)) {
            $opts['allowCredentials'] = array_map(fn($pk) => [
                'type'       => 'public-key',
                'id'         => $pk['credential_id'],
                'transports' => ['internal', 'hybrid', 'usb', 'nfc', 'ble'],
            ], $passkeys);
        }
        return $opts;
    }

    /**
     * Verify authentication assertion.
     * @param array $assertion          Decoded JSON from browser
     * @param array $stored_credentials Array of passkey rows (credential_id, public_key, sign_count)
     * @return array  The matched stored credential row
     */
    public function verify_authentication(array $assertion, array $stored_credentials): array
    {
        $credential_id = $assertion['id'] ?? '';
        $stored        = null;
        foreach ($stored_credentials as $c) {
            if ($c['credential_id'] === $credential_id) {
                $stored = $c;
                break;
            }
        }
        if (!$stored) {
            throw new RuntimeException('Passkey not found');
        }

        $client_data_raw = $this->b64url_decode($assertion['response']['clientDataJSON']);
        $client_data     = json_decode($client_data_raw, true);

        if (($client_data['type'] ?? '') !== 'webauthn.get') {
            throw new RuntimeException('Invalid clientData type');
        }
        if (rtrim($client_data['origin'] ?? '', '/') !== $this->origin) {
            throw new RuntimeException('Origin mismatch');
        }
        $this->verify_challenge($client_data['challenge'] ?? '');

        $auth_data_raw = $this->b64url_decode($assertion['response']['authenticatorData']);
        $parsed        = $this->parse_auth_data($auth_data_raw, false);

        if ($parsed['rpIdHash'] !== hash('sha256', $this->rpId, true)) {
            throw new RuntimeException('rpIdHash mismatch');
        }
        if (!($parsed['flags'] & 0x01)) {
            throw new RuntimeException('User not present');
        }

        // Verification data = authData || SHA-256(clientDataJSON)
        $verification_data = $auth_data_raw . hash('sha256', $client_data_raw, true);
        $signature         = $this->b64url_decode($assertion['response']['signature']);

        $result = openssl_verify($verification_data, $signature, $stored['public_key'], OPENSSL_ALGO_SHA256);
        if ($result !== 1) {
            throw new RuntimeException('Signature verification failed');
        }

        return $stored;
    }

    // ─── authData parsing ─────────────────────────────────────────────────────

    private function parse_auth_data(string $data, bool $expect_attested): array
    {
        $offset = 0;

        $rp_id_hash = substr($data, $offset, 32); $offset += 32;
        $flags      = ord($data[$offset]);          $offset += 1;
        $sign_count = unpack('N', substr($data, $offset, 4))[1]; $offset += 4;

        $credential_id = '';
        $public_key    = null;

        // Attested credential data present (bit 6)
        if ($expect_attested && ($flags & 0x40)) {
            $offset       += 16; // aaguid
            $cred_id_len   = unpack('n', substr($data, $offset, 2))[1]; $offset += 2;
            $credential_id = substr($data, $offset, $cred_id_len);       $offset += $cred_id_len;
            $public_key    = $this->cbor_decode(substr($data, $offset));
        }

        return [
            'rpIdHash'            => $rp_id_hash,
            'flags'               => $flags,
            'signCount'           => $sign_count,
            'credentialId'        => $credential_id,
            'credentialPublicKey' => $public_key,
        ];
    }

    // ─── COSE key → PEM ──────────────────────────────────────────────────────

    private function cose_to_pem(array $cose): string
    {
        $kty = $cose[1]  ?? null;
        $alg = $cose[3]  ?? null;

        // EC2 / ES256 (kty=2, alg=-7, crv=1 = P-256)
        if ($kty === 2 || $alg === -7) {
            $x = $cose[-2] ?? '';
            $y = $cose[-3] ?? '';
            if (strlen($x) !== 32 || strlen($y) !== 32) {
                throw new RuntimeException('Invalid EC key coordinates');
            }
            // Uncompressed EC point
            $point = "\x04" . $x . $y;
            // SubjectPublicKeyInfo for P-256
            // SEQUENCE { AlgorithmIdentifier { OID ecPublicKey, OID P-256 }, BIT STRING(point) }
            $spki = "\x30\x59"
                  . "\x30\x13"
                  . "\x06\x07\x2a\x86\x48\xce\x3d\x02\x01"      // OID ecPublicKey
                  . "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07"  // OID P-256
                  . "\x03\x42\x00"                                // BIT STRING, unused=0
                  . $point;
            return "-----BEGIN PUBLIC KEY-----\n"
                 . chunk_split(base64_encode($spki), 64, "\n")
                 . "-----END PUBLIC KEY-----\n";
        }

        // RSA / RS256 (kty=3, alg=-257)
        if ($kty === 3 || $alg === -257) {
            $n = $cose[-1] ?? '';
            $e = $cose[-2] ?? '';
            $rsa_seq = "\x30" . $this->der_len(strlen($this->der_int($n)) + strlen($this->der_int($e)))
                     . $this->der_int($n) . $this->der_int($e);
            $bit_str = "\x03" . $this->der_len(1 + strlen($rsa_seq)) . "\x00" . $rsa_seq;
            $alg_id  = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";
            $spki    = "\x30" . $this->der_len(strlen($alg_id) + strlen($bit_str)) . $alg_id . $bit_str;
            return "-----BEGIN PUBLIC KEY-----\n"
                 . chunk_split(base64_encode($spki), 64, "\n")
                 . "-----END PUBLIC KEY-----\n";
        }

        throw new RuntimeException('Unsupported COSE key type kty=' . $kty . ' alg=' . $alg);
    }

    private function der_int(string $bytes): string
    {
        // Prepend 0x00 if high bit set (ensure unsigned interpretation)
        if (ord($bytes[0]) & 0x80) {
            $bytes = "\x00" . $bytes;
        }
        return "\x02" . $this->der_len(strlen($bytes)) . $bytes;
    }

    private function der_len(int $len): string
    {
        if ($len < 128) {
            return chr($len);
        }
        $enc = '';
        $tmp = $len;
        while ($tmp > 0) {
            $enc = chr($tmp & 0xff) . $enc;
            $tmp >>= 8;
        }
        return chr(0x80 | strlen($enc)) . $enc;
    }

    // ─── Minimal CBOR decoder ─────────────────────────────────────────────────

    private function cbor_decode(string $data)
    {
        $offset = 0;
        return $this->cbor_read($data, $offset);
    }

    private function cbor_read(string $data, int &$offset)
    {
        if ($offset >= strlen($data)) {
            throw new RuntimeException('CBOR: unexpected end of data at offset ' . $offset);
        }
        $initial = ord($data[$offset++]);
        $major   = ($initial >> 5) & 0x07;
        $info    = $initial & 0x1f;
        $value   = $this->cbor_arg($data, $offset, $info);

        switch ($major) {
            case 0: return $value;                        // unsigned int
            case 1: return -1 - $value;                   // negative int
            case 2:                                        // byte string
                $bytes = substr($data, $offset, $value);
                $offset += $value;
                return $bytes;
            case 3:                                        // text string
                $str = substr($data, $offset, $value);
                $offset += $value;
                return $str;
            case 4:                                        // array
                $arr = [];
                for ($i = 0; $i < $value; $i++) {
                    $arr[] = $this->cbor_read($data, $offset);
                }
                return $arr;
            case 5:                                        // map
                $map = [];
                for ($i = 0; $i < $value; $i++) {
                    $k       = $this->cbor_read($data, $offset);
                    $v       = $this->cbor_read($data, $offset);
                    $map[$k] = $v;
                }
                return $map;
            case 7:                                        // simple/float — not needed
                return null;
            default:
                throw new RuntimeException('CBOR: unsupported major type ' . $major);
        }
    }

    private function cbor_arg(string $data, int &$offset, int $info): int
    {
        if ($info <= 23) { return $info; }
        if ($info === 24) { return ord($data[$offset++]); }
        if ($info === 25) { $v = unpack('n', substr($data, $offset, 2))[1]; $offset += 2; return $v; }
        if ($info === 26) { $v = unpack('N', substr($data, $offset, 4))[1]; $offset += 4; return $v; }
        if ($info === 27) {
            $hi = unpack('N', substr($data, $offset, 4))[1]; $offset += 4;
            $lo = unpack('N', substr($data, $offset, 4))[1]; $offset += 4;
            return ($hi << 32) | $lo;
        }
        throw new RuntimeException('CBOR: unsupported additional info ' . $info);
    }

    // ─── Base64URL helpers ────────────────────────────────────────────────────

    public function b64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function b64url_decode(string $data): string
    {
        $pad = (4 - strlen($data) % 4) % 4;
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', $pad));
    }
}
