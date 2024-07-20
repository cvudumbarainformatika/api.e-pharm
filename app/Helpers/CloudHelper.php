<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class CloudHelper
{
    public static function signature()
    {
        $xid = 'org';
        $secret_key = 'd4l4m';
        $signature = hash_hmac('sha256', $xid, $secret_key);
        return $signature;
    }
    public static function post_cloud($msg)
    {
        $xid = 'org';
        $secret_key = 'd4l4m';
        $signature = hash_hmac('sha256', $xid, $secret_key);
        $header = [
            'Accept' => 'application/json',
            'X-id' => 'org',
            'X-signature' => $signature,
        ];
        $response = Http::withHeaders($header)->post('http://103.23.199.134/api/send-message', $msg);
        return $response;
    }
    public static function get_unread($rc)
    {

        $msg = ['receiver' => $rc];
        $xid = 'org';
        $secret_key = 'd4l4m';
        $signature = hash_hmac('sha256', $xid, $secret_key);
        $header = [
            'Accept' => 'application/json',
            'X-id' => 'org',
            'X-signature' => $signature,
        ];
        $response = Http::withHeaders($header)->post('http://103.23.199.134/api/get-messages-not-read', $msg);
        return $response;
    }
    public static function post_readNotif($id)
    {
        $msg = ['id' => $id];
        $xid = 'org';
        $secret_key = 'd4l4m';
        $signature = hash_hmac('sha256', $xid, $secret_key);
        $header = [
            'Accept' => 'application/json',
            'X-id' => 'org',
            'X-signature' => $signature,
        ];
        $response = Http::withHeaders($header)->post('http://103.23.199.134/api/update-message-by-id', $msg);
        return $response;
    }
}
