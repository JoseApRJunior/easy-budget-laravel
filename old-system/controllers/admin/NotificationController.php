<?php

declare(strict_types=1);

namespace app\controllers\admin;

use app\controllers\AbstractController;
use core\library\Response;
use core\library\Twig;
use core\services\NotificationService;

/**
 * Controller para configuração de notificações
 */
class NotificationController extends AbstractController
{
    private NotificationService $notificationService;

    public function __construct(protected Twig $twig)
    {
        $this->notificationService = new NotificationService();
    }

    /**
     * Exibe página de configuração de notificações
     */
    public function index(): Response
    {
        return new Response(
            $this->twig->env->render('pages/admin/notifications/index.twig', [
                'title' => 'Configurações de Notificação'
            ])
        );
    }

    /**
     * Testa configuração de email
     */
    public function testEmail(): Response
    {
        $success = $this->notificationService->testEmailConfiguration();
        
        $data = [
            'success' => $success,
            'message' => $success ? 'Email de teste enviado com sucesso!' : 'Erro ao enviar email de teste'
        ];

        return new Response(
            json_encode($data),
            200,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Salva configurações de notificação
     */
    public function save(): Response
    {
        $emails = $_POST['emails'] ?? [];
        $phones = $_POST['phones'] ?? [];
        
        // Validar emails
        $validEmails = array_filter($emails, 'filter_var', FILTER_VALIDATE_EMAIL);
        
        if (empty($validEmails)) {
            return new Response(
                json_encode(['success' => false, 'message' => 'Pelo menos um email válido é obrigatório']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $this->notificationService->setNotificationRecipients($validEmails, $phones);
        
        return new Response(
            json_encode(['success' => true, 'message' => 'Configurações salvas com sucesso']),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}