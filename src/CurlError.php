<?php

namespace Drewlabs\Curl;

class CurlError
{
    const CURLE_UNSUPPORTED_PROTOCOL = 1;
    const CURLE_FAILED_INIT = 2;
    const CURLE_URL_MALFORMAT = 3;
    const CURLE_URL_MALFORMAT_USER = 4;
    const CURLE_COULDNT_RESOLVE_PROXY = 5;
    const CURLE_COULDNT_RESOLVE_HOST = 6;
    const CURLE_COULDNT_CONNECT = 7;
    const CURLE_FTP_WEIRD_SERVER_REPLY = 8;
    const CURLE_REMOTE_ACCESS_DENIED = 9;
    const CURLE_FTP_WEIRD_PASS_REPLY = 11;
    const CURLE_FTP_WEIRD_PASV_REPLY = 13;
    const CURLE_FTP_WEIRD_227_FORMAT = 14;
    const CURLE_FTP_CANT_GET_HOST = 15;
    const CURLE_FTP_COULDNT_SET_TYPE = 17;
    const CURLE_PARTIAL_FILE = 18;
    const CURLE_FTP_COULDNT_RETR_FILE = 19;
    const CURLE_QUOTE_ERROR = 21;
    const CURLE_HTTP_RETURNED_ERROR = 22;
    const CURLE_WRITE_ERROR = 23;
    const CURLE_UPLOAD_FAILED = 25;
    const CURLE_READ_ERROR = 26;
    const CURLE_OUT_OF_MEMORY = 27;
    const CURLE_OPERATION_TIMEDOUT = 28;
    const CURLE_FTP_PORT_FAILED = 30;
    const CURLE_FTP_COULDNT_USE_REST = 31;
    const CURLE_RANGE_ERROR = 33;
    const CURLE_HTTP_POST_ERROR = 34;
    const CURLE_SSL_CONNECT_ERROR = 35;
    const CURLE_BAD_DOWNLOAD_RESUME = 36;
    const CURLE_FILE_COULDNT_READ_FILE = 37;
    const CURLE_LDAP_CANNOT_BIND = 38;
    const CURLE_LDAP_SEARCH_FAILED = 39;
    const CURLE_FUNCTION_NOT_FOUND = 41;
    const CURLE_ABORTED_BY_CALLBACK = 42;
    const CURLE_BAD_FUNCTION_ARGUMENT = 43;
    const CURLE_INTERFACE_FAILED = 45;
    const CURLE_TOO_MANY_REDIRECTS = 47;
    const CURLE_UNKNOWN_TELNET_OPTION = 48;
    const CURLE_TELNET_OPTION_SYNTAX = 49;
    const CURLE_PEER_FAILED_VERIFICATION = 51;
    const CURLE_GOT_NOTHING = 52;
    const CURLE_SSL_ENGINE_NOTFOUND = 53;
    const CURLE_SSL_ENGINE_SETFAILED = 54;
    const CURLE_SEND_ERROR = 55;
    const CURLE_RECV_ERROR = 56;
    const CURLE_SSL_CERTPROBLEM = 58;
    const CURLE_SSL_CIPHER = 59;
    const CURLE_SSL_CACERT = 60;
    const CURLE_BAD_CONTENT_ENCODING = 61;
    const CURLE_LDAP_INVALID_URL = 62;
    const CURLE_FILESIZE_EXCEEDED = 63;
    const CURLE_USE_SSL_FAILED = 64;
    const CURLE_SEND_FAIL_REWIND = 65;
    const CURLE_SSL_ENGINE_INITFAILED = 66;
    const CURLE_LOGIN_DENIED = 67;
    const CURLE_TFTP_NOTFOUND = 68;
    const CURLE_TFTP_PERM = 69;
    const CURLE_REMOTE_DISK_FULL = 70;
    const CURLE_TFTP_ILLEGAL = 71;
    const CURLE_TFTP_UNKNOWNID = 72;
    const CURLE_REMOTE_FILE_EXISTS = 73;
    const CURLE_TFTP_NOSUCHUSER = 74;
    const CURLE_CONV_FAILED = 75;
    const CURLE_CONV_REQD = 76;
    const CURLE_SSL_CACERT_BADFILE = 77;
    const CURLE_REMOTE_FILE_NOT_FOUND = 78;
    const CURLE_SSH = 79;
    const CURLE_SSL_SHUTDOWN_FAILED = 80;
    const CURLE_AGAIN = 81;
    const CURLE_SSL_CRL_BADFILE = 82;
    const CURLE_SSL_ISSUER_ERROR = 83;
    const CURLE_FTP_PRET_FAILED = 84;
    const CURLE_RTSP_CSEQ_ERROR = 85;
    const CURLE_RTSP_SESSION_ERROR = 86;
    const CURLE_FTP_BAD_FILE_LIST = 87;
    const CURLE_CHUNK_FAILED = 88;


    /**
     * Returns an http status code for a given curl error number
     * 
     * @param int $errorno 
     * @return int 
     */
    public static function toHTTPStatusCode(int $errorno)
    {
        switch ($errorno) {
            case self::CURLE_COULDNT_CONNECT:
            case self::CURLE_COULDNT_RESOLVE_HOST:
            case self::CURLE_COULDNT_RESOLVE_PROXY:
                return 502;
            case self::CURLE_REMOTE_ACCESS_DENIED:
                return 511;
            case self::CURLE_HTTP_RETURNED_ERROR:
                return 500;
            default:
                return 502;
        }
    }
}
