/**
 * Encryption的Javascript版本，与PHP的版本同步，实现互转的功能
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
 * @version $Id: Encryption.js 8915 2017-11-05 03:38:45Z alacner $
 */
function TiiSecurityEncryption()
{
    md5 = function (string) {

        function RotateLeft(lValue, iShiftBits) {
            return (lValue<<iShiftBits) | (lValue>>>(32-iShiftBits));
        }

        function AddUnsigned(lX,lY) {
            var lX4,lY4,lX8,lY8,lResult;
            lX8 = (lX & 0x80000000);
            lY8 = (lY & 0x80000000);
            lX4 = (lX & 0x40000000);
            lY4 = (lY & 0x40000000);
            lResult = (lX & 0x3FFFFFFF)+(lY & 0x3FFFFFFF);
            if (lX4 & lY4) {
                return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
            }
            if (lX4 | lY4) {
                if (lResult & 0x40000000) {
                    return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
                } else {
                    return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
                }
            } else {
                return (lResult ^ lX8 ^ lY8);
            }
        }

        function F(x,y,z) { return (x & y) | ((~x) & z); }
        function G(x,y,z) { return (x & z) | (y & (~z)); }
        function H(x,y,z) { return (x ^ y ^ z); }
        function I(x,y,z) { return (y ^ (x | (~z))); }

        function FF(a,b,c,d,x,s,ac) {
            a = AddUnsigned(a, AddUnsigned(AddUnsigned(F(b, c, d), x), ac));
            return AddUnsigned(RotateLeft(a, s), b);
        }

        function GG(a,b,c,d,x,s,ac) {
            a = AddUnsigned(a, AddUnsigned(AddUnsigned(G(b, c, d), x), ac));
            return AddUnsigned(RotateLeft(a, s), b);
        }

        function HH(a,b,c,d,x,s,ac) {
            a = AddUnsigned(a, AddUnsigned(AddUnsigned(H(b, c, d), x), ac));
            return AddUnsigned(RotateLeft(a, s), b);
        }

        function II(a,b,c,d,x,s,ac) {
            a = AddUnsigned(a, AddUnsigned(AddUnsigned(I(b, c, d), x), ac));
            return AddUnsigned(RotateLeft(a, s), b);
        }

        function ConvertToWordArray(string) {
            var lWordCount;
            var lMessageLength = string.length;
            var lNumberOfWords_temp1=lMessageLength + 8;
            var lNumberOfWords_temp2=(lNumberOfWords_temp1-(lNumberOfWords_temp1 % 64))/64;
            var lNumberOfWords = (lNumberOfWords_temp2+1)*16;
            var lWordArray=Array(lNumberOfWords-1);
            var lBytePosition = 0;
            var lByteCount = 0;
            while ( lByteCount < lMessageLength ) {
                lWordCount = (lByteCount-(lByteCount % 4))/4;
                lBytePosition = (lByteCount % 4)*8;
                lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount)<<lBytePosition));
                lByteCount++;
            }
            lWordCount = (lByteCount-(lByteCount % 4))/4;
            lBytePosition = (lByteCount % 4)*8;
            lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80<<lBytePosition);
            lWordArray[lNumberOfWords-2] = lMessageLength<<3;
            lWordArray[lNumberOfWords-1] = lMessageLength>>>29;
            return lWordArray;
        }

        function WordToHex(lValue) {
            var WordToHexValue="",WordToHexValue_temp="",lByte,lCount;
            for (lCount = 0;lCount<=3;lCount++) {
                lByte = (lValue>>>(lCount*8)) & 255;
                WordToHexValue_temp = "0" + lByte.toString(16);
                WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length-2,2);
            }
            return WordToHexValue;
        }

        function Utf8Encode(string) {
            string = string.replace(/\r\n/g,"\n");
            var utftext = "";

            for (var n = 0; n < string.length; n++) {

                var c = string.charCodeAt(n);

                if (c < 128) {
                    utftext += String.fromCharCode(c);
                }
                else if((c > 127) && (c < 2048)) {
                    utftext += String.fromCharCode((c >> 6) | 192);
                    utftext += String.fromCharCode((c & 63) | 128);
                }
                else {
                    utftext += String.fromCharCode((c >> 12) | 224);
                    utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                    utftext += String.fromCharCode((c & 63) | 128);
                }

            }

            return utftext;
        }

        var x=Array();
        var k,AA,BB,CC,DD,a,b,c,d;
        var S11=7, S12=12, S13=17, S14=22;
        var S21=5, S22=9 , S23=14, S24=20;
        var S31=4, S32=11, S33=16, S34=23;
        var S41=6, S42=10, S43=15, S44=21;

        string = Utf8Encode(String(string));

        x = ConvertToWordArray(string);

        a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;

        for (k=0;k<x.length;k+=16) {
            AA=a; BB=b; CC=c; DD=d;
            a=FF(a,b,c,d,x[k+0], S11,0xD76AA478);
            d=FF(d,a,b,c,x[k+1], S12,0xE8C7B756);
            c=FF(c,d,a,b,x[k+2], S13,0x242070DB);
            b=FF(b,c,d,a,x[k+3], S14,0xC1BDCEEE);
            a=FF(a,b,c,d,x[k+4], S11,0xF57C0FAF);
            d=FF(d,a,b,c,x[k+5], S12,0x4787C62A);
            c=FF(c,d,a,b,x[k+6], S13,0xA8304613);
            b=FF(b,c,d,a,x[k+7], S14,0xFD469501);
            a=FF(a,b,c,d,x[k+8], S11,0x698098D8);
            d=FF(d,a,b,c,x[k+9], S12,0x8B44F7AF);
            c=FF(c,d,a,b,x[k+10],S13,0xFFFF5BB1);
            b=FF(b,c,d,a,x[k+11],S14,0x895CD7BE);
            a=FF(a,b,c,d,x[k+12],S11,0x6B901122);
            d=FF(d,a,b,c,x[k+13],S12,0xFD987193);
            c=FF(c,d,a,b,x[k+14],S13,0xA679438E);
            b=FF(b,c,d,a,x[k+15],S14,0x49B40821);
            a=GG(a,b,c,d,x[k+1], S21,0xF61E2562);
            d=GG(d,a,b,c,x[k+6], S22,0xC040B340);
            c=GG(c,d,a,b,x[k+11],S23,0x265E5A51);
            b=GG(b,c,d,a,x[k+0], S24,0xE9B6C7AA);
            a=GG(a,b,c,d,x[k+5], S21,0xD62F105D);
            d=GG(d,a,b,c,x[k+10],S22,0x2441453);
            c=GG(c,d,a,b,x[k+15],S23,0xD8A1E681);
            b=GG(b,c,d,a,x[k+4], S24,0xE7D3FBC8);
            a=GG(a,b,c,d,x[k+9], S21,0x21E1CDE6);
            d=GG(d,a,b,c,x[k+14],S22,0xC33707D6);
            c=GG(c,d,a,b,x[k+3], S23,0xF4D50D87);
            b=GG(b,c,d,a,x[k+8], S24,0x455A14ED);
            a=GG(a,b,c,d,x[k+13],S21,0xA9E3E905);
            d=GG(d,a,b,c,x[k+2], S22,0xFCEFA3F8);
            c=GG(c,d,a,b,x[k+7], S23,0x676F02D9);
            b=GG(b,c,d,a,x[k+12],S24,0x8D2A4C8A);
            a=HH(a,b,c,d,x[k+5], S31,0xFFFA3942);
            d=HH(d,a,b,c,x[k+8], S32,0x8771F681);
            c=HH(c,d,a,b,x[k+11],S33,0x6D9D6122);
            b=HH(b,c,d,a,x[k+14],S34,0xFDE5380C);
            a=HH(a,b,c,d,x[k+1], S31,0xA4BEEA44);
            d=HH(d,a,b,c,x[k+4], S32,0x4BDECFA9);
            c=HH(c,d,a,b,x[k+7], S33,0xF6BB4B60);
            b=HH(b,c,d,a,x[k+10],S34,0xBEBFBC70);
            a=HH(a,b,c,d,x[k+13],S31,0x289B7EC6);
            d=HH(d,a,b,c,x[k+0], S32,0xEAA127FA);
            c=HH(c,d,a,b,x[k+3], S33,0xD4EF3085);
            b=HH(b,c,d,a,x[k+6], S34,0x4881D05);
            a=HH(a,b,c,d,x[k+9], S31,0xD9D4D039);
            d=HH(d,a,b,c,x[k+12],S32,0xE6DB99E5);
            c=HH(c,d,a,b,x[k+15],S33,0x1FA27CF8);
            b=HH(b,c,d,a,x[k+2], S34,0xC4AC5665);
            a=II(a,b,c,d,x[k+0], S41,0xF4292244);
            d=II(d,a,b,c,x[k+7], S42,0x432AFF97);
            c=II(c,d,a,b,x[k+14],S43,0xAB9423A7);
            b=II(b,c,d,a,x[k+5], S44,0xFC93A039);
            a=II(a,b,c,d,x[k+12],S41,0x655B59C3);
            d=II(d,a,b,c,x[k+3], S42,0x8F0CCC92);
            c=II(c,d,a,b,x[k+10],S43,0xFFEFF47D);
            b=II(b,c,d,a,x[k+1], S44,0x85845DD1);
            a=II(a,b,c,d,x[k+8], S41,0x6FA87E4F);
            d=II(d,a,b,c,x[k+15],S42,0xFE2CE6E0);
            c=II(c,d,a,b,x[k+6], S43,0xA3014314);
            b=II(b,c,d,a,x[k+13],S44,0x4E0811A1);
            a=II(a,b,c,d,x[k+4], S41,0xF7537E82);
            d=II(d,a,b,c,x[k+11],S42,0xBD3AF235);
            c=II(c,d,a,b,x[k+2], S43,0x2AD7D2BB);
            b=II(b,c,d,a,x[k+9], S44,0xEB86D391);
            a=AddUnsigned(a,AA);
            b=AddUnsigned(b,BB);
            c=AddUnsigned(c,CC);
            d=AddUnsigned(d,DD);
        }

        var temp = WordToHex(a)+WordToHex(b)+WordToHex(c)+WordToHex(d);

        return temp.toLowerCase();
    };

    base64_encode = function(str)
    {
        var c1, c2, c3;
        var base64EncodeChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
        var i = 0, len= str.length, string = '';

        while (i < len){
            c1 = str.charCodeAt(i++) & 0xff;
            if (i == len){
                string += base64EncodeChars.charAt(c1 >> 2);
                string += base64EncodeChars.charAt((c1 & 0x3) << 4);
                string += "==";
                break;
            }
            c2 = str.charCodeAt(i++);
            if (i == len){
                string += base64EncodeChars.charAt(c1 >> 2);
                string += base64EncodeChars.charAt(((c1 & 0x3) << 4) | ((c2 & 0xF0) >> 4));
                string += base64EncodeChars.charAt((c2 & 0xF) << 2);
                string += "=";
                break;
            }
            c3 = str.charCodeAt(i++);
            string += base64EncodeChars.charAt(c1 >> 2);
            string += base64EncodeChars.charAt(((c1 & 0x3) << 4) | ((c2 & 0xF0) >> 4));
            string += base64EncodeChars.charAt(((c2 & 0xF) << 2) | ((c3 & 0xC0) >> 6));
            string += base64EncodeChars.charAt(c3 & 0x3F);
        }
        return string;
    };

    base64_decode = function(str)
    {
        var c1, c2, c3, c4;
        var base64DecodeChars = new Array(
            -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
            -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
            -1, -1, -1, -1, -1, -1, -1, 62, -1, -1, -1, 63, 52, 53, 54, 55, 56, 57,
            58, 59, 60, 61, -1, -1, -1, -1, -1, -1, -1, 0,  1,  2,  3,  4,  5,  6,
            7,  8,  9,  10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24,
            25, -1, -1, -1, -1, -1, -1, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36,
            37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, -1, -1, -1,
            -1, -1
        );
        var i=0, len = str.length, string = '';

        while (i < len){
            do{
                c1 = base64DecodeChars[str.charCodeAt(i++) & 0xff];
            } while (i < len && c1 == -1);

            if (c1 == -1) break;

            do{
                c2 = base64DecodeChars[str.charCodeAt(i++) & 0xff];
            } while (i < len && c2 == -1);

            if (c2 == -1) break;

            string += String.fromCharCode((c1 << 2) | ((c2 & 0x30) >> 4));

            do{
                c3 = str.charCodeAt(i++) & 0xff;
                if (c3 == 61) return string;

                c3 = base64DecodeChars[c3];
            } while (i < len && c3 == -1);

            if (c3 == -1) break;

            string += String.fromCharCode(((c2 & 0XF) << 4) | ((c3 & 0x3C) >> 2));

            do{
                c4 = str.charCodeAt(i++) & 0xff;
                if (c4 == 61) return string;
                c4 = base64DecodeChars[c4];
            } while (i < len && c4 == -1);

            if (c4 == -1) break;

            string += String.fromCharCode(((c3 & 0x03) << 6) | c4);
        }
        return string;
    };

    //url safe encode base64 with replace / + =
    urlsafeBase64Encode = function (string)
    {
        return base64_encode(string).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    };

    //url safe decode base64
    urlsafeBase64Decode = function(string)
    {
        var data = string.replace(/\-/g, '+').replace(/_/g, '/');
        var mod4 = data.length % 4;
        if (mod4) {
            data += '===='.substring(mod4);
        }
        return base64_decode(data);
    };

    zeroPad = function(nr, num)
    {
        nrs = String(nr);
        if (nrs.length > num) return nrs;

        nra = nrs.split('').reverse();
        for(i = 0; i < num; i++) {
            nra.push('0');
        }
        return nra.join('').substring(0, num).split('').reverse().join('');
    };

    authcodeKey = "Please use setAuthcodeKey for assignment";

    this.setAuthcodeKey = function(ak)
    {
        authcodeKey = ak;
        return true;
    };

    /**
     * Get the default encryption and decryption key
     */
    getAuthcodeKey = function()
    {
        return authcodeKey;
    };

    getValue = function(a, def)
    {
        if (typeof(a) == "undefined") return def;
        return a;
    };

    readUTF8String = function(bytes) {
        var ix = 0;

        if( bytes.slice(0,3) == "\xEF\xBB\xBF") {
            ix = 3;
        }

        var string = "";
        for( ; ix < bytes.length; ix++ ) {
            var byte1 = bytes[ix].charCodeAt(0);
            if( byte1 < 0x80 ) {
                string += String.fromCharCode(byte1);
            } else if( byte1 >= 0xC2 && byte1 < 0xE0 ) {
                var byte2 = bytes[++ix].charCodeAt(0);
                string += String.fromCharCode(((byte1&0x1F)<<6) + (byte2&0x3F));
            } else if( byte1 >= 0xE0 && byte1 < 0xF0 ) {
                var byte2 = bytes[++ix].charCodeAt(0);
                var byte3 = bytes[++ix].charCodeAt(0);
                string += String.fromCharCode(((byte1&0xFF)<<12) + ((byte2&0x3F)<<6) + (byte3&0x3F));
            } else if( byte1 >= 0xF0 && byte1 < 0xF5) {
                var byte2 = bytes[++ix].charCodeAt(0);
                var byte3 = bytes[++ix].charCodeAt(0);
                var byte4 = bytes[++ix].charCodeAt(0);
                var codepoint = ((byte1&0x07)<<18) + ((byte2&0x3F)<<12)+ ((byte3&0x3F)<<6) + (byte4&0x3F);
                codepoint -= 0x10000;
                string += String.fromCharCode(
                    (codepoint>>10) + 0xD800,
                    (codepoint&0x3FF) + 0xDC00
                );
            }
        }

        return string;
    };

    authcode = function(string, operation, key, expiry, keyc_hash)
    {
        if (!string) return '';

        operation = getValue(operation, 'DECODE');
        key = getValue(key, '');
        expiry = getValue(expiry, 0);
        keyc_hash = getValue(keyc_hash, '');

        ckey_length = 4;
        key = md5(key != '' ? key : getAuthcodeKey());
        keya = md5(key.substring(0, 16));
        keyb = md5(key.substring(16, 32));
        keyc = ckey_length ? (operation == 'DECODE' ?  string.substring(0, ckey_length) : md5(keyc_hash).substring(32 - ckey_length)) : '';

        cryptkey = keya + md5(keya + keyc);
        key_length = cryptkey.length;

        time = Math.floor(new Date().getTime()/1000);

        string = operation == 'DECODE' ? urlsafeBase64Decode(string.substring(ckey_length)) : zeroPad((expiry ? expiry + time : 0), 10) + md5(string + keyb).substring(0, 16) + string;

        box = [];

        for (i = 0; i < 256; i++) {
            box[i] = i;
        }

        rndkey = [];
        for (i = 0; i < 256; i++) {
            rndkey[i] = cryptkey[i % key_length].charCodeAt();
        }

        for (j = i = 0; i < 256; i++) {
            j = (j + box[i] + rndkey[i]) % 256;
            tmp = box[i];
            box[i] = box[j];
            box[j] = tmp;
        }

        bytes = [];
        if (operation == 'DECODE') {
            for (i = 0; i < string.length; i++) {
                charCode = string[i].charCodeAt();
                bytes.push(charCode);
            }
        } else {
            for (i = 0; i < string.length; i++) {
                charCode = string[i].charCodeAt();
                if ((charCode >= 0x0) && (charCode <= 0x007F)) {
                    bytes.push(charCode);
                } else if ((charCode >= 0x0080) && (charCode <= 0x07FF)) {
                    bytes.push(0xc0 | ((charCode >> 6) & 0x1F));
                    bytes.push(0x80 | (charCode & 0x3F));
                } else if ((charCode >= 0x0800) && (charCode <= 0xFFFF)) {
                    bytes.push(0xe0 | (charCode >> 12));
                    bytes.push(0x80 | ((charCode >> 6) & 0x3F));
                    bytes.push(0x80 | (charCode & 0x3F));
                }
            }

        }

        result = [];
        for (a = j = i = 0; i < bytes.length; i++) {
            a = (a + 1) % 256;
            j = (j + box[a]) % 256;
            tmp = box[a];
            box[a] = box[j];
            box[j] = tmp;
            result.push(String.fromCharCode((((bytes[i]+ 256) % 256) ^ (box[(box[a] + box[j]) % 256]))));
        }

        if (operation == 'DECODE') {
            result = readUTF8String(result);
            time2 = parseInt(result.substring(0, 10));
            sign = result.substring(10, 26);
            data = result.substring(26);
            sign2 = md5(data + keyb).substring(0, 16);

            if ((time2 == 0 || time2 > time) && (sign2 == sign)) {
                return data;
            } else {
                return NULL;
            }
        } else {
            return keyc + urlsafeBase64Encode(result.join(''));
        }
    };


    /**
     * String encryption
     */
    this.encode = function(data, expiry, key)
    {
        expiry = getValue(expiry, 0);
        key = getValue(key, '');
        return authcode(data, 'ENCODE', key, expiry, Math.random());
    };

    /**
     * String encryption
     * WARNING: The result has been the same, may increase the risk of security
     * *** Deprecated ***
     */
    this.encodeWithoutHash = function(data, expiry, key)
    {
        expiry = getValue(expiry, 0);
        key = getValue(key, '');
        return authcode(data, 'ENCODE', key, expiry, md5(md5(getAuthcodeKey())));
    };

    /**
     * Decrypt the string
     *
     * @static
     * @param $string
     * @param string $key
     * @return string
     */
    this.decode = function(string, key)
    {
        key = getValue(key, '');
        return authcode(string, 'DECODE', key);
    };
}
