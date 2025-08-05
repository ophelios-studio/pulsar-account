<?php namespace Pulsar\Account\Api;

use Zephyrus\Exceptions\HttpRequesterException;
use Zephyrus\Network\HttpRequester;

class PwnedApi
{
    /**
     * To protect the value of the source password being searched for, Pwned Passwords also implements a
     * k-Anonymity model that allows a password to be searched for by partial hash. When a password hash with the same
     * first 5 characters is found in the Pwned Passwords repository, the API will respond with an HTTP 200 and include
     * the suffix of every hash beginning with the specified prefix, followed by a count of how many times it appears
     * in the data set.
     *
     * @param string $password
     * @return int
     * @throws HttpRequesterException
     */
    public static function findBreachCount(string $password): int
    {
        $sha1Hash = strtoupper(sha1($password));
        $sha1Prefix = substr($sha1Hash, 0, 5);
        $httpRequester = new HttpRequester("get", "https://api.pwnedpasswords.com/range/$sha1Prefix");
        $httpResponse = $httpRequester->execute();
        if ($httpResponse->getHttpCode() == 404) {
            return 0;
        }
        $breaches = self::formatBreachResponse($httpResponse->getResponse(), $sha1Prefix);
        if (array_key_exists($sha1Hash, $breaches)) {
            return $breaches[$sha1Hash];
        }
        return 0;
    }

    private static function formatBreachResponse(string $rawResponse, string $sha1Prefix): array
    {
        $input = trim($rawResponse);
        if (empty($input)) {
            return [];
        }
        $results = explode("\n", trim($input));
        $breaches = [];
        foreach ($results as $result) {
            list($hashSuffix, $count) = explode(":", $result);
            $breaches[$sha1Prefix . $hashSuffix] = intval($count);
        }
        return $breaches;
    }
}
