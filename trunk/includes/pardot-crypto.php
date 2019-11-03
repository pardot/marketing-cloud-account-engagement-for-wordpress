<?php


class PardotCrypto
{
    public function encrypt($plaintext)
    {


        /* First determine if libsodium is available. If it is use that */
        if (function_exists('sodium_crypto_aead_chacha20poly1305_keygen')) {
            try {
                $nonce = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_NPUBBYTES);
            } catch (Exception $e) {
                return false;
            }

            $ciphertext = sodium_crypto_aead_chacha20poly1305_encrypt($plaintext, '', $nonce, self::get_sodium_key());
            if ($ciphertext === false) return false;

            return "NACL::" . base64_encode($nonce) . "::" . base64_encode($ciphertext);


            /* If not, then determine if OpenSSL with AES-256-GCM is available. If it is, use that. */
        } else if (function_exists('openssl_get_cipher_methods') && in_array('aes-256-gcm', openssl_get_cipher_methods())) {
            try {
                $iv = random_bytes(openssl_cipher_iv_length('aes-256-gcm'));
            } catch (Exception $e) {
                return false;
            }

            $key = self::get_aes256_key();
            if ($key === null)
            {
                return false;
            }

            $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
            if ($ciphertext === false || $tag === false) return false;

            return "OGCM::" . base64_encode($iv) . "::" . base64_encode($tag) . "::" . base64_encode($ciphertext);


            /* If not, then determine if OpenSSL with AES-256-CBC is available. If it is we'll use PBKDF2 to determine a MAC key from the
             * key returned by "getKey" and we'll do ETM ourselves.
             */
        } else if (function_exists('openssl_get_cipher_methods') && in_array('aes-256-cbc', openssl_get_cipher_methods())) {
            try {
                $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
            } catch (Exception $e) {
                return false;
            }

            $key = self::get_aes256_key();
            if ($key === null)
            {
                return false;
            }

            $hk = hash_pbkdf2('sha256', $key, $iv, 10000);
            $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            if ($ciphertext === false) return false;

            $mac = hash_hmac('sha256', $ciphertext, $hk);
            if ($mac === false) return false;

            return "OETM::" . base64_encode($iv) . "::" . $mac . "::" . base64_encode($ciphertext);
        }


        /* If we got here there's insufficient crypto on the system, return false */
        return false;
    }


    public function decrypt($ciphertext)
    {
        $data = explode('::', $ciphertext);

        /* First determine the method used to encrypt the password */
        switch($data[0])
        {
            case 'NACL':
                return self::decrypt_sodium(base64_decode($data[1]), base64_decode($data[2]));
            case 'OGCM':
                return self::decrypt_openssl_aes256gcm(base64_decode($data[1]), base64_decode($data[2]), base64_decode($data[3]));
            case 'OETM':
                return self::decrypt_openssl_aes256cbc_with_hmacsha256(base64_decode($data[1]), $data[2], base64_decode($data[3]));
            default:
                return false;
        }
    }



    private function decrypt_sodium($nonce, $ciphertext)
    {
        return sodium_crypto_aead_chacha20poly1305_decrypt($ciphertext, '', $nonce, self::get_sodium_key());
    }



    private function decrypt_openssl_aes256gcm($iv, $tag, $ciphertext)
    {
        $key = self::get_aes256_key();
        if ($key === null)
        {
            return false;
        }

        return openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    }



    private function decrypt_openssl_aes256cbc_with_hmacsha256($iv, $mac, $ciphertext)
    {
        $key = self::get_aes256_key();
        if ($key === null)
        {
            return false;
        }

        $hk = hash_pbkdf2('sha256', $key, $iv, 10000);
        if ($hk === false) return false;

        $cmac = hash_hmac('sha256', $ciphertext, $hk);
        if ($cmac === false) return false;

        if (hash_equals($cmac, $mac))
        {
            return openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        }

        return false;
    }


    private function set_sodium_key()
    {
        $sodium_key = base64_encode(sodium_crypto_aead_chacha20poly1305_keygen());
        Pardot_Settings::set_setting('sodium_key', $sodium_key);
    }


    private function get_sodium_key()
    {
        $sodium_key = Pardot_Settings::get_setting('sodium_key');
        if ($sodium_key === false) {
            self::set_sodium_key();
            $sodium_key = Pardot_Settings::get_setting('sodium_key');
        }

        return $sodium_key;
    }


    private function set_aes256_key()
    {
        $aes256_key = null;

        /* AES256 keys are 32 bytes long, as 32*8 == 256 */
        try {
            $aes256_key = base64_encode(random_bytes(32));
        } catch (Exception $e) {
            return;
        }
        Pardot_Settings::set_setting('aes256_key', $aes256_key);
    }


    private function get_aes256_key()
    {
        $aes256_key = Pardot_Settings::get_setting('aes256_key');
        if ($aes256_key === false) {
            self::set_aes256_key();
            $aes256_key = Pardot_Settings::get_setting('aes256_key');
        }

        return $aes256_key;
    }
}