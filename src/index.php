<?php

require_once 'vendor/autoload.php';

use Appwrite\Client;
use Appwrite\Service\Databases;
use Appwrite\ID;

return function ($context) {
    $context->log('PHP function started.');

    // Initialize the Appwrite client with environment variables
    $client = (new Client())
        ->setEndpoint($context->env['APPWRITE_ENDPOINT'])
        ->setProject($context->env['APPWRITE_PROJECT_ID'])
        ->setKey($context->env['APPWRITE_API_KEY']);

    $databases = new Databases($client);

    // Check if the function is triggered by a users.create event
    if ($context->req->method === 'POST' && $context->req->headers['x-appwrite-event'] === 'users.create') {
        try {
            $payload = json_decode($context->req->body, true);
            $context->log('User created event received: ' . json_encode($payload));

            // Create a new profile document in your 'profiles' collection
            $databases->createDocument(
                $context->env['DB_ID'], // Your database ID (e.g., 'management')
                $context->env['COLLECTION_PROFILES'], // Your profiles collection ID
                ID::unique(), // Generate a unique ID for the document
                [
                    "userId" => $payload["\$id"],
                    "fullName" => $payload["name"] ?? "",
                    "plan" => "free", // Default plan
                    "onboardingDone" => false,
                    "avatarUrl" => "", // Default empty
                    "country" => "", // Default empty
                    "city" => "", // Default empty
                    "language" => "en", // Default English
                    "theme" => "light" // Default light theme
                ]
            );
            $context->log('User profile created successfully for userId: ' . $payload["\$id"]);
            return $context->res->send('Profile created successfully.');
        } catch (\Throwable $e) {
            $context->error('Error creating user profile: ' . $e->getMessage());
            return $context->res->status(500)->send('Error creating profile.');
        }
    }

    // If the event is not 'users.create', or it's not a POST request, respond accordingly
    return $context->res->send('Not a users.create event or invalid request method.');
};
