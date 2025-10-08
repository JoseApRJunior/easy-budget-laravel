<?php

namespace core\request;

use core\library\Session;
use http\Request;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

abstract class AbstractFormRequest
{
    protected Request $request;

    public function isValidated(Validator $validate)
    {
        try {
            // Obtém todos os dados da requisição
            $data = array_merge(
                $this->request->all(),
                $this->request->allFiles(),
            );

            // Valida os dados com o Validator
            $validate->assert($data);

            // Verifica se é uma requisição que precisa de CSRF token
            if ($this->shouldCheckCSRF()) {
                $token = $this->getCSRFToken();

                if (!$this->validateCSRFToken($token)) {
                    Session::flashs([ 'CSRF token inválido ou expirado' ]);

                    return false;
                }
            }

            return true;

        } catch (NestedValidationException $e) {
            Session::flashs($e->getMessages());

            return false;
        }
    }

    private function shouldCheckCSRF(): bool
    {
        // Lista de métodos que requerem validação CSRF
        $methodsRequiringCSRF = [ 'POST', 'PUT', 'DELETE', 'PATCH' ];

        // Obtém o método HTTP atual
        $method = strtoupper($this->request->server[ 'REQUEST_METHOD' ] ?? 'GET');

        // Verifica se é uma requisição AJAX
        $isAjax = isset($this->request->server[ 'HTTP_X_REQUESTED_WITH' ]) &&
            strtolower($this->request->server[ 'HTTP_X_REQUESTED_WITH' ]) === 'xmlhttprequest';

        // Não valida CSRF para GET e HEAD
        if (!in_array($method, $methodsRequiringCSRF)) {
            return false;
        }

        // Para requisições AJAX, verifica o header X-CSRF-TOKEN
        if ($isAjax) {
            return true;
        }

        return true;
    }

    private function getCSRFToken(): ?string
    {
        // Verifica primeiro no header (para requisições AJAX)
        $token = $this->request->server[ 'HTTP_X_CSRF_TOKEN' ] ?? null;

        if ($token) {
            return $token;
        }

        // Verifica nos dados POST
        if (isset($this->request->post[ 'csrf_token' ])) {
            return $this->request->post[ 'csrf_token' ];
        }

        // Por último, verifica nos dados GET
        return $this->request->get[ 'csrf_token' ] ?? null;
    }

    private function validateCSRFToken(?string $token): bool
    {
        if ($token === null) {
            return false;
        }

        // Obtém o token da sessão
        $sessionToken = Session::get('csrf_token');

        if ($sessionToken === null) {
            return false;
        }

        // Verifica se o token é válido e não expirou
        return hash_equals($sessionToken, $token);
    }

    protected function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    abstract protected function execute();

    public static function validate(Request $request)
    {
        /** @phpstan-ignore-next-line */
        return (new static())->setRequest($request)->execute();
    }

    public function getErrorMessage($entity, $method, $parameter, $validator): string
    {
        return getTranslatedMessage($entity, $method, $parameter, $validator);
    }

    public function messages($entity, $method): array
    {
        return loadMessages($entity, $method);
    }

}
