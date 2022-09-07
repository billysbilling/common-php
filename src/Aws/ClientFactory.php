<?php

namespace Common\Aws;

use Aws\Credentials\CredentialProvider;
use Aws\S3\S3Client;
use Aws\MockHandler;

/**
 * Class ClientFactory
 * @package Common\Aws
 */
class ClientFactory
{
    public static function getS3Client(MockHandler $mockHandler = null): S3Client
    {
        return new S3Client(array_merge(self::defaultOptions($mockHandler), [
            'signature_version' => 'v4',
            'version' => '2006-03-01'
        ]));
    }

    public static function getSNSClient(MockHandler $mockHandler = null): SnsClient
    {
        return new SnsClient(array_merge(self::defaultOptions($mockHandler), [
            'version' => '2010-03-31'
        ]));
    }

    public static function getEventBridgeClient(MockHandler $mockHandler = null): EventBridgeClient
    {
        return new EventBridgeClient(array_merge(self::defaultOptions($mockHandler), [
            'version' => '2015-10-07'
        ]));
    }

    private static function defaultOptions(MockHandler $mockHandler = null): array
    {
        return [
            'credentials' => CredentialProvider::defaultProvider(),
            'region' => \getenv('AWS_REGION') ?: 'eu-north-1',
            'handler' => $mockHandler,
        ];
    }
}
