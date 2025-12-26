<?php

declare(strict_types=1);

namespace App\DTOs\Log;

use App\DTOs\AbstractDTO;

readonly class MiddlewareMetricHistoryDTO extends AbstractDTO
{
    public function __construct(
        public string $middleware_name,
        public string $endpoint,
        public string $method,
        public float $response_time,
        public int $memory_usage,
        public int $status_code,
        public ?float $cpu_usage = null,
        public ?string $error_message = null,
        public ?int $user_id = null,
        public ?string $ip_address = null,
        public ?string $user_agent = null,
        public ?int $request_size = null,
        public ?int $response_size = null,
        public ?int $database_queries = null,
        public ?int $cache_hits = null,
        public ?int $cache_misses = null,
        public ?int $tenant_id = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            middleware_name: $data['middleware_name'],
            endpoint: $data['endpoint'],
            method: $data['method'],
            response_time: (float) $data['response_time'],
            memory_usage: (int) $data['memory_usage'],
            status_code: (int) $data['status_code'],
            cpu_usage: isset($data['cpu_usage']) ? (float) $data['cpu_usage'] : null,
            error_message: $data['error_message'] ?? null,
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            ip_address: $data['ip_address'] ?? null,
            user_agent: $data['user_agent'] ?? null,
            request_size: isset($data['request_size']) ? (int) $data['request_size'] : null,
            response_size: isset($data['response_size']) ? (int) $data['response_size'] : null,
            database_queries: isset($data['database_queries']) ? (int) $data['database_queries'] : null,
            cache_hits: isset($data['cache_hits']) ? (int) $data['cache_hits'] : null,
            cache_misses: isset($data['cache_misses']) ? (int) $data['cache_misses'] : null,
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'middleware_name'  => $this->middleware_name,
            'endpoint'         => $this->endpoint,
            'method'           => $this->method,
            'response_time'    => $this->response_time,
            'memory_usage'     => $this->memory_usage,
            'status_code'      => $this->status_code,
            'cpu_usage'        => $this->cpu_usage,
            'error_message'    => $this->error_message,
            'user_id'          => $this->user_id,
            'ip_address'       => $this->ip_address,
            'user_agent'       => $this->user_agent,
            'request_size'     => $this->request_size,
            'response_size'    => $this->response_size,
            'database_queries' => $this->database_queries,
            'cache_hits'       => $this->cache_hits,
            'cache_misses'     => $this->cache_misses,
            'tenant_id'        => $this->tenant_id,
        ];
    }
}
