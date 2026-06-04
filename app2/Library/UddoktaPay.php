<?php

namespace App\Library;

use Exception;

class UddoktaPay
{
    /**
     * Generate API Headers
     *
     * @return array
     */
    private static function apiHeaders()
    {
        return [
            "RT-UDDOKTAPAY-API-KEY: " . gs()->uddoktapay_api_key,
            "accept: application/json",
            "content-type: application/json"
        ];
    }

    /**
     * Send payment request
     *
     * @param array $requestData
     * @return string
     * @throws Exception
     */
    public static function init_payment($requestData)
    {

        $host = gs()->uddoktapay_api_url;
        if (!$host) {
            throw new Exception("UddoktaPay API URL not configured.");
        }

        // Use the correct API endpoint according to UddoktaPay documentation
        $apiUrl = "https://{$host}/api/checkout-v2";

        // Convert parameter names to match UddoktaPay API documentation
        $apiRequestData = [
            'full_name' => $requestData['cus_name'] ?? $requestData['full_name'] ?? 'Guest User',
            'email' => $requestData['cus_email'] ?? $requestData['email'] ?? 'noemail@example.com',
            'amount' => (string) ($requestData['amount'] ?? '0'), // মূল অ্যামাউন্ট সরাসরি পাঠানো হচ্ছে
            'metadata' => $requestData['metadata'] ?? [],
            'redirect_url' => $requestData['success_url'] ?? $requestData['redirect_url'] ?? '',
            'cancel_url' => $requestData['cancel_url'] ?? '',
        ];

        // Add optional webhook_url if provided
        if (isset($requestData['webhook_url'])) {
            $apiRequestData['webhook_url'] = $requestData['webhook_url'];
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($apiRequestData),
            CURLOPT_HTTPHEADER => self::apiHeaders(),
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            throw new Exception("cURL Error: " . $err);
        }

        $result = json_decode($response, true);

        // UddoktaPay API returns status as boolean true/false
        if (!isset($result['status']) || $result['status'] !== true) {
            $errorMessage = $result['message'] ?? 'Unexpected API response';
            throw new Exception("Payment creation failed: " . $errorMessage);
        }

        if (!isset($result['payment_url'])) {
            throw new Exception("Payment URL not found in response.");
        }

        return $result['payment_url'];
    }

    /**
     * Verify payment status
     *
     * @param string $invoiceId
     * @return array
     * @throws Exception
     */
    public static function verify_payment($invoiceId)
    {
        $host = gs()->uddoktapay_api_url;
        if (!$host) {
            throw new Exception("UddoktaPay API URL not configured.");
        }

        $verifyUrl = "https://{$host}/api/verify-payment";

        $payload = [
            'invoice_id' => $invoiceId
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $verifyUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => self::apiHeaders(),
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            throw new Exception("cURL Error: " . $err);
        }

        $result = json_decode($response, true);

        if (!isset($result['status'])) {
            throw new Exception("Invalid response from verification.");
        }

        return $result;
    }
}