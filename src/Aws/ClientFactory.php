<?php

namespace Common\Aws;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;

class ClientFactory
{
    /**
     * @return S3Client
     */
    public static function getS3Client(): S3Client
    {
        return new S3Client([
            'credentials' => self::getCredentials(),
            'region' => \getenv('S3_REGION') ?: \getenv('AWS_REGION'),
            'signature_version' => 'v4',
            'version' => '2006-03-01'
        ]);
    }

    /**
     * @return SnsClient
     */
    public static function getSNSClient(): SnsClient
    {
        return new SnsClient([
            'credentials' => self::getCredentials(),
            'region' => \getenv('SNS_REGION') ?: \getenv('AWS_REGION'),
            'version' => '2010-03-31'
        ]);
    }

    /**
     * @return Credentials
     */
    public static function getCredentials(): Credentials
    {
        return new Credentials(\getenv('AWS_API_KEY'), \getenv('AWS_SECRET_KEY'));
    }
}
