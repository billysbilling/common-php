<?php

namespace Common\Aws;

use Aws\Credentials\Credentials;
use Aws\EventBridge\EventBridgeClient;
use Aws\S3\S3Client;

class ClientFactory
{
    /**
     * @return S3Client
     */
    public static function getS3Client(): S3Client
    {
        return new S3Client(array_merge(['signature_version' => 'v4', self::defaultOptions()]));
    }

    /**
     * @return SnsClient
     */
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
            'credentials' => self::getCredentials(),
            'region' => \getenv('AWS_REGION'),
            'version' => '2010-03-31'
        ];
    }

    /**
     * @return Credentials
     */
    private static function getCredentials(): Credentials
    {
        return new Credentials(\getenv('AWS_API_KEY'), \getenv('AWS_SECRET_KEY'));
    }
}
