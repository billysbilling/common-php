<?php

namespace Common\Aws;

use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;

/**
 * Class ClientFactory
 * @package Common\Aws
 */
class ClientFactory
{
    public static function getS3Client(): S3Client
    {
        return new S3Client(array_merge(['signature_version' => 'v4', self::defaultOptions()]));
    }

    public static function getSNSClient(): SnsClient
    {
        return new SnsClient(self::defaultOptions());
    }

    public static function getEventBridgeClient(): EventBridgeClient
    {
        return new EventBridgeClient(self::defaultOptions());
    }

    private static function defaultOptions(): array
    {
        return [
            'credentials' => CredentialProvider::defaultProvider(),
            'region' => \getenv('AWS_REGION'),
            'version' => '2010-03-31'
        ];
    }
}
