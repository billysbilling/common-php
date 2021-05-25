<?php

namespace Common\Aws;

use Aws\EventBridge\EventBridgeClient as AWSEventBridgeClient;

/**
 * Class EventBridgeClient
 * @package Common\Aws
 */
class EventBridgeClient extends AWSEventBridgeClient
{
    public function publishEvent(string $type, array $data, array $entryParams = [])
    {
        $meta = [];
        if (class_exists('DDTrace\\GlobalTracer')) {
            $meta = [
                'meta' => [
                    'DD_TRACE_ID' => \DDTrace\trace_id(),
                    'DD_SPAN_ID' => \dd_trace_peek_span_id(),
                ]
            ];
        }

        $event = [
            'Entries' => [
                array_merge([
                    'DetailType' => $type,
                    'Detail' => json_encode(array_merge($data, $meta)),
                    'EventBusName' => getenv('AWS_EVENT_BUS'),
                    'Source' => getenv('DD_SERVICE'),
                    'Time' => new \DateTime(),
                ], $entryParams)
            ]
        ];
        return $this->putEvents($event);
    }

}
