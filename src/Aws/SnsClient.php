<?php

namespace Common\Aws;

use Aws\Sns\SnsClient as AWSSnsClient;

/**
 * Class SnsClient
 * @package Common\Aws
 */
class SnsClient extends AWSSnsClient
{
    public function publish(array $args = [])
    {
        if (class_exists('DDTrace\\GlobalTracer')) {
            $args['MessageAttributes'][] = [
                'DD_TRACE_ID' => [
                    'DataType' => 'String',
                    'StringValue' => \DDTrace\trace_id(),
                ],
                'DD_SPAN_ID' => [
                    'DataType' => 'String',
                    'StringValue' => \dd_trace_peek_span_id(),
                ],
            ];
        }
        parent::publish($args);
    }
}
