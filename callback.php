$result = @file_get_contents($token_url, false, $context);

if ($result === FALSE) {
    echo "<h2>Token request failed</h2>";

    // Show HTTP headers
    $error_headers = $http_response_header ?? [];
    echo "<h3>Headers:</h3><pre>";
    print_r($error_headers);
    echo "</pre>";

    // Attempt to read the body from the stream (more detailed error)
    $stream = fopen($token_url, 'r', false, $context);
    if ($stream) {
        $body = stream_get_contents($stream);
        fclose($stream);
        echo "<h3>Body:</h3><pre>";
        print_r($body);
        echo "</pre>";
    }
} else {
    $token_response = json_decode($result, true);
    echo "<h2>Token Response</h2><pre>";
    print_r($token_response);
    echo "</pre>";
}
