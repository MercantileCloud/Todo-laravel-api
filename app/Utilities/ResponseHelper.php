<?php

use GrahamCampbell\ResultType\Success;

function customResponse($success, $status, $message, $data = null)
{
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data
    ];
    return response($response, $status);
}
