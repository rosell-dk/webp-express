### Running your own test
It is not to define your own test by extending the "AbstractTester" class. You can use the code in one of the provided testers as a template (ie `RequestHeaderTester.php`).

### Using another library for making the HTTP request
This library simply uses `file_get_contents` to make HTTP requests. It can however be set to use another library. Use the `setHttpRequestor` method for that. The requester must implement `HttpRequesterInterface` interface, which simply consists of a single method: `makeHttpRequest($url)`
