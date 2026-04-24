<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MpesaService
{
    protected string $baseUrl;
    protected string $consumerKey;
    protected string $consumerSecret;
    protected string $shortcode;
    protected string $passkey;
    protected string $callbackUrl;

    public function __construct()
    {
        $this->baseUrl        = config('mpesa.env') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
        $this->consumerKey    = config('mpesa.consumer_key');
        $this->consumerSecret = config('mpesa.consumer_secret');
        $this->shortcode      = config('mpesa.shortcode');
        $this->passkey        = config('mpesa.passkey');
        $this->callbackUrl    = config('mpesa.callback_url');
    }

    // ── Shared HTTP options ────────────────────────────────────────────────────
    private function httpOptions(): array
    {
        return ['verify' => false];
    }

    // ── Get OAuth token ────────────────────────────────────────────────────────
    public function getAccessToken(): string
    {
        $response = Http::withOptions($this->httpOptions())
            ->withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->get("{$this->baseUrl}/oauth/v1/generate?grant_type=client_credentials");

        if ($response->failed()) {
            Log::error('M-Pesa token error', ['response' => $response->body()]);
            throw new \Exception('Failed to get M-Pesa access token: ' . $response->body());
        }

        return $response->json('access_token');
    }

    // ── STK Push ───────────────────────────────────────────────────────────────
    public function stkPush(string $phone, int $amount, string $reference, string $description): array
    {
        $token     = $this->getAccessToken();
        $timestamp = Carbon::now()->format('YmdHis');
        $password  = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $phone = $this->normalizePhone($phone);

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => (int) ceil($amount),
            'PartyA'            => $phone,
            'PartyB'            => $this->shortcode,
            'PhoneNumber'       => $phone,
            'CallBackURL'       => $this->callbackUrl,
            'AccountReference'  => $reference,
            'TransactionDesc'   => $description,
        ];

        $response = Http::withOptions($this->httpOptions())
            ->withToken($token)
            ->post("{$this->baseUrl}/mpesa/stkpush/v1/processrequest", $payload);

        Log::info('M-Pesa STK Push', [
            'phone'    => $phone,
            'amount'   => $amount,
            'response' => $response->json(),
        ]);

        if ($response->failed()) {
            throw new \Exception('STK Push failed: ' . $response->body());
        }

        return $response->json();
    }

    // ── Query STK Push status ──────────────────────────────────────────────────
    public function stkQuery(string $checkoutRequestId): array
    {
        $token     = $this->getAccessToken();
        $timestamp = Carbon::now()->format('YmdHis');
        $password  = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $response = Http::withOptions($this->httpOptions())
            ->withToken($token)
            ->post("{$this->baseUrl}/mpesa/stkpushquery/v1/query", [
                'BusinessShortCode' => $this->shortcode,
                'Password'          => $password,
                'Timestamp'         => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId,
            ]);

        return $response->json();
    }

    // ── Normalize phone ────────────────────────────────────────────────────────
    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        } elseif (str_starts_with($phone, '+')) {
            $phone = ltrim($phone, '+');
        } elseif (!str_starts_with($phone, '254')) {
            $phone = '254' . $phone;
        }

        return $phone;
    }
}