<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Http;

class TradisiBaliController extends Controller
{
    public function tanya(Request $request)
    {
        $question = $request->input('question');
        $context = $request->input('context');

        // Hugging Face Model Hub API endpoint
        $apiEndpoint = 'https://api-inference.huggingface.co/models/SwastyMaharani/fine-tuned-tradisi-bali';

        // Prepare the request payload
        $payload = [
            'inputs' => [
                'question' => $question,
                'context' => $context,
            ],
        ];

        // Set a maximum number of retries
        $maxRetries = 3;

        // Make a POST request to the Hugging Face Model Hub API with retry logic
        $client = new Client();
        $retryCount = 0;

        do {
            try {
                $response = $client->post($apiEndpoint, [
                    'headers' => [
                        // 'Authorization' => 'Bearer ' . $apiToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $payload,
                ]);

                // Decode the JSON response from the API
                $responseData = json_decode($response->getBody(), true);

                // Retrieve the model's answer from the API response
                $bertAnswer = $responseData['answer'];

                // Combine answers
                $result = [
                    'question' => $question,
                    'bert_answer' => $bertAnswer,
                ];

                return response()->json(['message' => 'Pertanyaan dan Jawaban Berhasil Ditemukan', 'data' => $result, 'answer' => $bertAnswer], 200);

            } catch (ServerException $e) {
                // Retry if the server returns a 503 Service Unavailable status
                if ($e->getResponse()->getStatusCode() == 503) {
                    $retryCount++;
                    sleep(5); // Wait for 5 seconds before retrying
                } else {
                    // If it's not a 503 error, rethrow the exception
                    throw $e;
                }
            }
        } while ($retryCount < $maxRetries);

        // If all retries fail, return an error response
        return response()->json(['error' => 'Model is still loading. Please try again later.'], 503);
    }
}