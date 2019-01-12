/**
 * Encryption的Java版本，与PHP的版本同步，实现互转的功能
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2005 - 2017, Fitz Zhang <alacner@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author Fitz Zhang <alacner@gmail.com>
 * @version $Id: Encryption.java 2022 2015-08-20 08:39:58Z alacner $
 */
import java.io.UnsupportedEncodingException;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;

class Base64Util {

    private final static char[] ALPHABET = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/"
                                                 .toCharArray();

    private static int[]        toInt    = new int[128];

    static {
        for (int i = 0; i < ALPHABET.length; i++) {
            toInt[ALPHABET[i]] = i;
        }
    }

    /**
     * Translates the specified byte array into Base64 string.
     * 
     * @param buf the byte array (not null)
     * @return the translated Base64 string (not null)
     */
    public static String encode(byte[] buf) {
        int size = buf.length;
        char[] ar = new char[((size + 2) / 3) * 4];
        int a = 0;
        int i = 0;
        while (i < size) {
            byte b0 = buf[i++];
            byte b1 = (i < size) ? buf[i++] : 0;
            byte b2 = (i < size) ? buf[i++] : 0;

            int mask = 0x3F;
            ar[a++] = ALPHABET[(b0 >> 2) & mask];
            ar[a++] = ALPHABET[((b0 << 4) | ((b1 & 0xFF) >> 4)) & mask];
            ar[a++] = ALPHABET[((b1 << 2) | ((b2 & 0xFF) >> 6)) & mask];
            ar[a++] = ALPHABET[b2 & mask];
        }
        switch (size % 3) {
            case 1:
                ar[--a] = '=';
            case 2:
                ar[--a] = '=';
        }
        return new String(ar);
    }

    /**
     * Translates the specified Base64 string into a byte array.
     * 
     * @param s the Base64 string (not null)
     * @return the byte array (not null)
     */
    public static byte[] decode(String s) {
        int delta = s.endsWith("==") ? 2 : s.endsWith("=") ? 1 : 0;
        byte[] buffer = new byte[s.length() * 3 / 4 - delta];
        int mask = 0xFF;
        int index = 0;
        for (int i = 0; i < s.length(); i += 4) {
            int c0 = toInt[s.charAt(i)];
            int c1 = toInt[s.charAt(i + 1)];
            buffer[index++] = (byte) (((c0 << 2) | (c1 >> 4)) & mask);
            if (index >= buffer.length) {
                return buffer;
            }
            int c2 = toInt[s.charAt(i + 2)];
            buffer[index++] = (byte) (((c1 << 4) | (c2 >> 2)) & mask);
            if (index >= buffer.length) {
                return buffer;
            }
            int c3 = toInt[s.charAt(i + 3)];
            buffer[index++] = (byte) (((c2 << 6) | c3) & mask);
        }
        return buffer;
    }

}

/**
 * @author Fitz Zhang <alacner@gmail.com>
 */
public class TiiSecurityEncryption {
    private final String defaultAuthcodeKey = "Please use setAuthcodeKey for assignment";
    private String       authcodeKey;

    /**
     * Set the default encryption and decryption key
     */
    public boolean setAuthcodeKey(String authcodeKey) {
        this.authcodeKey = authcodeKey;
        return true;
    }

    /**
     * Get the default encryption and decryption key
     */
    private String getAuthcodeKey() {
        return (authcodeKey == null || authcodeKey.isEmpty()) ? defaultAuthcodeKey : authcodeKey;
    }

    /**
     * auth code url safe
     * 
     * @param string
     * @param operation
     * @param key
     * @param expiry
     * @param keyc_hash
     * @return
     * @throws Exception
     */
    private String authcode(String string, String operation, String key, Integer expiry, String keyc_hash)
            throws Exception {
        int ckey_length = 4;
        String key2 = MD5(key.isEmpty() ? this.getAuthcodeKey() : key);
        String keya = MD5(key2.substring(0, 16));
        String keyb = MD5(key2.substring(16, 32));
        String keyc = ckey_length > 0 ? ("DECODE".equals(operation) ? string.substring(0, ckey_length) : MD5(keyc_hash)
                .substring(32 - ckey_length)) : "";

        String cryptkey = keya + MD5(keya + keyc);
        int key_length = cryptkey.length();

        long time = System.currentTimeMillis() / 1000;

        byte[] strings = "DECODE".equals(operation) ? this.urlsafeBase64DecodeFast(string.substring(ckey_length))
                : (String.format("%010d", expiry > 0 ? expiry + time : 0) + MD5(string + keyb).substring(0, 16) + string)
                        .getBytes();

        int[] box = new int[256];

        for (int i = 0; i < 256; i++) {
            box[i] = i;
        }

        int[] rndkey = new int[256];

        byte[] cryptkeyBytes = cryptkey.getBytes();
        for (int i = 0; i < 256; i++) {
            rndkey[i] = cryptkeyBytes[i % key_length];
        }

        for (int j = 0, i = 0; i < 256; i++) {
            j = (j + box[i] + rndkey[i]) % 256;
            int tmp = box[i];
            box[i] = box[j];
            box[j] = tmp;
        }

        byte[] output = new byte[strings.length];
        for (int a = 0, j = 0, i = 0; i < strings.length; i++) {
            a = (a + 1) % 256;
            j = (j + box[a]) % 256;
            int tmp = box[a];
            box[a] = box[j];
            box[j] = tmp;
            output[i] = (byte) (toInt(strings[i]) ^ (box[(box[a] + box[j]) % 256]));
        }

        try {
            if ("DECODE".equals(operation)) {
                String result = new String(output, "UTF-8");
                Integer time2 = Integer.valueOf(result.substring(0, 10));
                String sign = result.substring(10, 26);
                String data = result.substring(26);
                String sign2 = MD5(data + keyb).substring(0, 16);

                if ((time2 == 0 || time2 > time) && sign2.equals(sign)) {
                    return data;
                } else {
                    return null;
                }
            } else {
                return keyc + this.urlsafeBase64Encode(output);
            }
        } catch (Exception e) {
            System.out.println(e.getMessage());
            return null;
        }
    }

    private String authcode(String string, String operation, String key, Integer expiry) throws Exception {
        return this.authcode(string, operation, key, expiry, "");
    }

    private String authcode(String string, String operation, String key) throws Exception {
        return this.authcode(string, operation, key, 0);
    }

    /**
     * String encryption
     *
     * @param data
     * @param expiry
     * @param key
     * @return
     * @throws Exception
     */
    public String encode(String data, Integer expiry, String key) throws Exception {
        return this.authcode(data, "ENCODE", key, expiry, Long.toString(System.currentTimeMillis()));
    }

    public String encode(String data, Integer expiry) throws Exception {
        return this.encode(data, expiry, "");
    }

    public String encode(String data) throws Exception {
        return this.encode(data, 0);
    }

    /**
     * String encryption WARNING: The result has been the same, may increase the
     * risk of security *** Deprecated ***
     * 
     * @param data
     * @param expiry
     * @param key
     * @return
     * @throws Exception
     */
    public String encodeWithoutHash(String data, Integer expiry, String key) throws Exception {
        String keycHash = MD5(MD5(this.getAuthcodeKey()));
        return this.authcode(data, "ENCODE", key, expiry, keycHash);
    }

    public String encodeWithoutHash(String data, Integer expiry) throws Exception {
        return this.encodeWithoutHash(data, expiry, "");
    }

    public String encodeWithoutHash(String data) throws Exception {
        return this.encodeWithoutHash(data, 0);
    }

    /**
     * Decrypt the string
     *
     * @param string
     * @param key
     * @return
     * @throws Exception
     */
    public String decode(String string, String key) throws Exception {
        return this.authcode(string, "DECODE", key);
    }

    public String decode(String string) throws Exception {
        return this.decode(string, "");
    }

    private static int toInt(byte b) {
        return (b + 256) % 256;
    }

    /**
     * url safe encode base64 with replace / + =
     * 
     * @param output
     * @return
     */
    private String urlsafeBase64Encode(byte[] output) {
        return Base64Util.encode(output).replaceAll("\\\\+", "-").replaceAll("/", "_").replaceAll("=", "");
    }

    private byte[] urlsafeBase64DecodeFast(String string) throws UnsupportedEncodingException {
        String data = string.replaceAll("\\\\-", "+").replaceAll("_", "/");
        int mod4 = data.length() % 4;
        return Base64Util.decode((mod4 > 0) ? (data + "====".substring(0, mod4)) : data);
    }

    private String MD5(String MD5) {
        StringBuffer sb = new StringBuffer();
        String part = null;
        try {
            MessageDigest md = MessageDigest.getInstance("MD5");
            byte[] md5 = md.digest(MD5.getBytes());

            for (int i = 0; i < md5.length; i++) {
                part = Integer.toHexString(md5[i] & 0xFF);
                if (part.length() == 1) {
                    part = "0" + part;
                }
                sb.append(part);
            }

        } catch (NoSuchAlgorithmException ex) {
        }
        return sb.toString();

    }

    public static void main(String[] args) throws Exception {

        TiiSecurityEncryption se = new TiiSecurityEncryption();
        se.setAuthcodeKey("abc");
        System.out.println("===");
        String s2 = se.encodeWithoutHash("dat中文a123", 2, "key");
        System.out.println(s2);
        System.out.println("===");
        String s = se.encode("dat中文a123", 2, "key");
        System.out.println(s);
        System.out.println("===");
        System.out.println(se.decode(s, "key"));
        Thread.sleep(3000);
        System.out.println("=expired=");
        System.out.println(se.decode(s, "key"));
        System.out.println("===");
        System.out.println(se.decode("614d705nvuMdDhp4ZszpseYF4d1sk4XfOt4oBE3NHX0-ChtSVUs4BRcr", "key"));

    }
}