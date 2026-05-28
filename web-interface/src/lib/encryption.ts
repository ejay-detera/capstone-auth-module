import * as CryptoJS from 'crypto-js';

const KEY = import.meta.env.VITE_INTERNAL_ENCRYPTION_KEY;

if (!KEY || KEY.length !== 32) {
  throw new Error(
    'VITE_INTERNAL_ENCRYPTION_KEY is required and must be exactly 32 characters long. ' +
    'Please set it in your environment config.'
  );
}

/**
 * Encrypts a JSON object into a base64 string: base64(iv + ciphertext)
 * matching the PHP openssl_encrypt implementation.
 */
export const encryptPayload = (data: any): string => {
  const plaintext = JSON.stringify(data);
  
  // Generate a random 16-byte IV
  const iv = CryptoJS.lib.WordArray.random(16);
  const keyHex = CryptoJS.enc.Utf8.parse(KEY);

  const encrypted = CryptoJS.AES.encrypt(plaintext, keyHex, {
    iv: iv,
    mode: CryptoJS.mode.CBC,
    padding: CryptoJS.pad.Pkcs7
  });

  // Prepend IV to ciphertext (raw word arrays)
  const ivAndCiphertext = iv.concat(encrypted.ciphertext);

  // Return base64 encoded string
  return CryptoJS.enc.Base64.stringify(ivAndCiphertext);
};
