<?php

namespace core\library;

class Response
{
    /**
     * @param array<string, mixed> $headers
     */
    public function __construct(
        protected mixed $body,
        protected int $statusCode = 200,
        protected array $headers = [],
    ) {}

    public function send(): void
    {
        http_response_code( $this->statusCode );

        if ( !empty( $this->headers ) ) {

            foreach ( $this->headers as $index => $header ) {
                if ( is_scalar( $header ) ) {
                    header( sprintf( "%s:%s", $index, $header ) );
                }
            }
        }

        if ( in_array( 'application/json', $this->headers ) ) {
            $json = json_encode( $this->body );
            if ( $json === false ) {
                error_log( "Erro ao codificar JSON em send(): " . json_last_error_msg() );
                $json = json_encode( [ 'error' => 'Failed to encode JSON' ] );
            }
            echo $json;
        } else {
            echo $this->body;
        }
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): mixed
    {
        return $this->body;
    }

    public function getContent(): string
    {
        if ( in_array( 'application/json', $this->headers ) ) {
            $json = json_encode( $this->body );
            if ( $json === false ) {
                error_log( "Erro ao codificar JSON: " . json_last_error_msg() );
                return json_encode( [ 'error' => 'Failed to encode JSON' ] );
            }
            return $json;
        }
        return $this->body;
    }

}
