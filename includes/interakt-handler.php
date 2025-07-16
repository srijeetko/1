<?php
/**
 * Interakt WhatsApp API Handler
 * Handles all interactions with Interakt WhatsApp Business API
 * Alpha Nutrition Customer Support System
 */

require_once 'db_connection.php';

class InteraktHandler {
    private $pdo;
    private $apiKey;
    private $baseUrl;
    private $rateLimitDelay = 200000; // 200ms delay between requests (microseconds)

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadSettings();
    }

    /**
     * Load Interakt settings from database
     */
    private function loadSettings() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT setting_key, setting_value
                FROM support_settings
                WHERE setting_key IN ('interakt_api_key', 'interakt_base_url')
            ");
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $this->apiKey = $settings['interakt_api_key'] ?? '';
            $this->baseUrl = $settings['interakt_base_url'] ?? 'https://api.interakt.ai/v1/public';

            if (empty($this->apiKey)) {
                throw new Exception('Interakt API key not configured');
            }
        } catch (Exception $e) {
            error_log("Interakt settings load error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate UUID for database records
     */
    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Make HTTP request to Interakt API
     */
    private function makeRequest($endpoint, $method = 'POST', $data = null) {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'Authorization: Basic ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);

        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL error: " . $error);
        }

        $decodedResponse = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMessage = $decodedResponse['message'] ?? 'Unknown API error';
            throw new Exception("API error (HTTP $httpCode): " . $errorMessage);
        }

        // Add rate limiting delay
        usleep($this->rateLimitDelay);

        return $decodedResponse;
    }

    /**
     * Send template message via Interakt API
     */
    public function sendTemplateMessage($phoneNumber, $templateName, $templateData = []) {
        try {
            // Validate phone number format
            $phoneNumber = $this->formatPhoneNumber($phoneNumber);

            $payload = [
                'countryCode' => '+91', // Default to India, can be made configurable
                'phoneNumber' => $phoneNumber,
                'type' => 'Template',
                'template' => [
                    'name' => $templateName,
                    'languageCode' => $templateData['languageCode'] ?? 'en'
                ]
            ];

            // Add template variables if provided
            if (!empty($templateData['headerValues'])) {
                $payload['template']['headerValues'] = $templateData['headerValues'];
            }

            if (!empty($templateData['bodyValues'])) {
                $payload['template']['bodyValues'] = $templateData['bodyValues'];
            }

            if (!empty($templateData['buttonValues'])) {
                $payload['template']['buttonValues'] = $templateData['buttonValues'];
            }

            if (!empty($templateData['fileName'])) {
                $payload['template']['fileName'] = $templateData['fileName'];
            }

            // Add callback data for tracking
            if (!empty($templateData['callbackData'])) {
                $payload['callbackData'] = $templateData['callbackData'];
            }

            $response = $this->makeRequest('/message/', 'POST', $payload);

            // Log the message in database
            $this->logOutgoingMessage($phoneNumber, $templateName, $payload, $response);

            return [
                'success' => true,
                'message_id' => $response['id'] ?? null,
                'interakt_response' => $response
            ];

        } catch (Exception $e) {
            error_log("Interakt send template error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Add or update user in Interakt
     */
    public function trackUser($phoneNumber, $userData = []) {
        try {
            $phoneNumber = $this->formatPhoneNumber($phoneNumber);

            $payload = [
                'phoneNumber' => $phoneNumber,
                'countryCode' => '+91',
                'traits' => $userData
            ];

            $response = $this->makeRequest('/track/users/', 'POST', $payload);

            return [
                'success' => true,
                'interakt_response' => $response
            ];

        } catch (Exception $e) {
            error_log("Interakt track user error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Track event in Interakt (can trigger automated campaigns)
     */
    public function trackEvent($phoneNumber, $eventName, $eventData = []) {
        try {
            $phoneNumber = $this->formatPhoneNumber($phoneNumber);

            $payload = [
                'phoneNumber' => $phoneNumber,
                'countryCode' => '+91',
                'event' => $eventName,
                'traits' => $eventData
            ];

            $response = $this->makeRequest('/track/events/', 'POST', $payload);

            return [
                'success' => true,
                'interakt_response' => $response
            ];

        } catch (Exception $e) {
            error_log("Interakt track event error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format phone number for Interakt API
     */
    private function formatPhoneNumber($phoneNumber) {
        // Remove all non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Remove leading country code if present
        if (substr($phoneNumber, 0, 2) === '91' && strlen($phoneNumber) === 12) {
            $phoneNumber = substr($phoneNumber, 2);
        }

        // Remove leading zero if present
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = substr($phoneNumber, 1);
        }

        // Validate Indian mobile number format (10 digits starting with 6-9)
        if (!preg_match('/^[6-9][0-9]{9}$/', $phoneNumber)) {
            throw new Exception('Invalid phone number format: ' . $phoneNumber);
        }

        return $phoneNumber;
    }

    /**
     * Log outgoing message to database
     */
    private function logOutgoingMessage($phoneNumber, $templateName, $payload, $response) {
        try {
            // Find or create conversation
            $conversationId = $this->findOrCreateConversation($phoneNumber);

            $messageId = $this->generateUUID();
            $interaktMessageId = $response['id'] ?? null;

            $stmt = $this->pdo->prepare("
                INSERT INTO whatsapp_messages (
                    message_id, conversation_id, interakt_message_id, sender_type,
                    message_type, content, template_name, delivery_status,
                    is_automated, callback_data, created_at
                ) VALUES (?, ?, ?, 'system', 'template', ?, ?, 'sent', TRUE, ?, NOW())
            ");

            $stmt->execute([
                $messageId,
                $conversationId,
                $interaktMessageId,
                json_encode($payload),
                $templateName,
                $payload['callbackData'] ?? null
            ]);

        } catch (Exception $e) {
            error_log("Error logging outgoing message: " . $e->getMessage());
        }
    }

    /**
     * Find existing conversation or create new one
     */
    private function findOrCreateConversation($phoneNumber) {
        try {
            // First, try to find existing active conversation
            $stmt = $this->pdo->prepare("
                SELECT conversation_id
                FROM whatsapp_conversations
                WHERE customer_phone = ? AND status = 'active'
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$phoneNumber]);
            $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($conversation) {
                // Update last message time
                $updateStmt = $this->pdo->prepare("
                    UPDATE whatsapp_conversations
                    SET last_message_at = NOW()
                    WHERE conversation_id = ?
                ");
                $updateStmt->execute([$conversation['conversation_id']]);

                return $conversation['conversation_id'];
            }

            // Create new conversation and ticket
            $conversationId = $this->generateUUID();
            $ticketId = $this->createSupportTicket($phoneNumber);

            $stmt = $this->pdo->prepare("
                INSERT INTO whatsapp_conversations (
                    conversation_id, ticket_id, customer_phone, status,
                    last_message_at, created_at
                ) VALUES (?, ?, ?, 'active', NOW(), NOW())
            ");

            $stmt->execute([$conversationId, $ticketId, $phoneNumber]);

            return $conversationId;

        } catch (Exception $e) {
            error_log("Error finding/creating conversation: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create support ticket for new conversation
     */
    private function createSupportTicket($phoneNumber) {
        try {
            $ticketId = $this->generateUUID();
            $ticketNumber = 'TKT-' . date('Ymd') . '-' . strtoupper(substr($ticketId, 0, 6));

            // Try to find existing user by phone
            $userId = $this->findUserByPhone($phoneNumber);

            $stmt = $this->pdo->prepare("
                INSERT INTO support_tickets (
                    ticket_id, ticket_number, user_id, customer_phone,
                    subject, category, priority, status, source, created_at
                ) VALUES (?, ?, ?, ?, 'WhatsApp Support Request', 'general', 'medium', 'open', 'whatsapp', NOW())
            ");

            $stmt->execute([$ticketId, $ticketNumber, $userId, $phoneNumber]);

            // Auto-assign ticket if enabled
            $this->autoAssignTicket($ticketId);

            return $ticketId;

        } catch (Exception $e) {
            error_log("Error creating support ticket: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Find user by phone number
     */
    private function findUserByPhone($phoneNumber) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT user_id
                FROM users
                WHERE phone = ? OR phone = ? OR phone = ?
            ");

            // Try different phone formats
            $formats = [
                $phoneNumber,
                '+91' . $phoneNumber,
                '0' . $phoneNumber
            ];

            $stmt->execute($formats);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user ? $user['user_id'] : null;

        } catch (Exception $e) {
            error_log("Error finding user by phone: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Auto-assign ticket to available agent
     */
    private function autoAssignTicket($ticketId) {
        try {
            // Check if auto-assignment is enabled
            $stmt = $this->pdo->prepare("
                SELECT setting_value
                FROM support_settings
                WHERE setting_key = 'auto_assign_tickets'
            ");
            $stmt->execute();
            $setting = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$setting || $setting['setting_value'] !== 'true') {
                return false;
            }

            // Find available agent with least active tickets
            $stmt = $this->pdo->prepare("
                SELECT sa.agent_id, COUNT(st.ticket_id) as active_tickets
                FROM support_agents sa
                LEFT JOIN support_tickets st ON sa.agent_id = st.assigned_agent_id
                    AND st.status IN ('open', 'in_progress')
                WHERE sa.status = 'active'
                GROUP BY sa.agent_id
                HAVING active_tickets < sa.max_concurrent_chats
                ORDER BY active_tickets ASC, sa.created_at ASC
                LIMIT 1
            ");
            $stmt->execute();
            $agent = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($agent) {
                // Assign ticket to agent
                $updateStmt = $this->pdo->prepare("
                    UPDATE support_tickets
                    SET assigned_agent_id = ?, status = 'in_progress', updated_at = NOW()
                    WHERE ticket_id = ?
                ");
                $updateStmt->execute([$agent['agent_id'], $ticketId]);

                // Log assignment
                $assignmentId = $this->generateUUID();
                $logStmt = $this->pdo->prepare("
                    INSERT INTO agent_assignments (
                        assignment_id, ticket_id, agent_id, assigned_at, status
                    ) VALUES (?, ?, ?, NOW(), 'active')
                ");
                $logStmt->execute([$assignmentId, $ticketId, $agent['agent_id']]);

                return $agent['agent_id'];
            }

            return false;

        } catch (Exception $e) {
            error_log("Error auto-assigning ticket: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process incoming webhook from Interakt
     */
    public function processWebhook($webhookData) {
        try {
            $logId = $this->generateUUID();

            // Log webhook for debugging
            $stmt = $this->pdo->prepare("
                INSERT INTO interakt_webhook_logs (
                    log_id, webhook_type, payload, processed, created_at
                ) VALUES (?, ?, ?, FALSE, NOW())
            ");

            $webhookType = $this->determineWebhookType($webhookData);
            $stmt->execute([$logId, $webhookType, json_encode($webhookData)]);

            // Process based on webhook type
            switch ($webhookType) {
                case 'incoming_message':
                    $result = $this->processIncomingMessage($webhookData);
                    break;

                case 'message_status':
                    $result = $this->processMessageStatus($webhookData);
                    break;

                default:
                    $result = ['success' => false, 'error' => 'Unknown webhook type'];
            }

            // Update webhook log
            $updateStmt = $this->pdo->prepare("
                UPDATE interakt_webhook_logs
                SET processed = TRUE, processed_at = NOW(), error_message = ?
                WHERE log_id = ?
            ");
            $updateStmt->execute([
                $result['success'] ? null : $result['error'],
                $logId
            ]);

            return $result;

        } catch (Exception $e) {
            error_log("Webhook processing error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Determine webhook type from payload
     */
    private function determineWebhookType($webhookData) {
        if (isset($webhookData['type']) && $webhookData['type'] === 'message') {
            return 'incoming_message';
        }

        if (isset($webhookData['status'])) {
            return 'message_status';
        }

        return 'unknown';
    }

    /**
     * Process incoming message webhook
     */
    private function processIncomingMessage($webhookData) {
        try {
            $phoneNumber = $this->formatPhoneNumber($webhookData['phoneNumber'] ?? '');
            $messageContent = $webhookData['message'] ?? '';
            $messageType = $webhookData['messageType'] ?? 'text';

            // Find or create conversation
            $conversationId = $this->findOrCreateConversation($phoneNumber);

            // Save incoming message
            $messageId = $this->generateUUID();
            $stmt = $this->pdo->prepare("
                INSERT INTO whatsapp_messages (
                    message_id, conversation_id, sender_type, message_type,
                    content, delivery_status, is_automated, created_at
                ) VALUES (?, ?, 'customer', ?, ?, 'delivered', FALSE, NOW())
            ");

            $stmt->execute([$messageId, $conversationId, $messageType, $messageContent]);

            // Update conversation last message time
            $updateStmt = $this->pdo->prepare("
                UPDATE whatsapp_conversations
                SET last_message_at = NOW()
                WHERE conversation_id = ?
            ");
            $updateStmt->execute([$conversationId]);

            // Check for automated responses
            $this->checkAutomatedResponse($conversationId, $messageContent);

            return ['success' => true, 'message_id' => $messageId];

        } catch (Exception $e) {
            error_log("Error processing incoming message: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process message status webhook
     */
    private function processMessageStatus($webhookData) {
        try {
            $interaktMessageId = $webhookData['messageId'] ?? '';
            $status = strtolower($webhookData['status'] ?? '');

            if (empty($interaktMessageId)) {
                return ['success' => false, 'error' => 'Missing message ID'];
            }

            // Update message status
            $stmt = $this->pdo->prepare("
                UPDATE whatsapp_messages
                SET delivery_status = ?,
                    delivered_at = CASE WHEN ? = 'delivered' THEN NOW() ELSE delivered_at END,
                    read_at = CASE WHEN ? = 'read' THEN NOW() ELSE read_at END
                WHERE interakt_message_id = ?
            ");

            $stmt->execute([$status, $status, $status, $interaktMessageId]);

            return ['success' => true, 'updated_status' => $status];

        } catch (Exception $e) {
            error_log("Error processing message status: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check and send automated response if applicable
     */
    private function checkAutomatedResponse($conversationId, $messageContent) {
        try {
            // Check if automated responses are enabled
            $stmt = $this->pdo->prepare("
                SELECT setting_value
                FROM support_settings
                WHERE setting_key = 'auto_response_enabled'
            ");
            $stmt->execute();
            $setting = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$setting || $setting['setting_value'] !== 'true') {
                return false;
            }

            // Find matching automated response
            $stmt = $this->pdo->prepare("
                SELECT ar.*, st.interakt_template_name, st.content as template_content
                FROM automated_responses ar
                LEFT JOIN support_templates st ON ar.template_id = st.template_id
                WHERE ar.is_active = TRUE
                AND LOWER(?) LIKE CONCAT('%', LOWER(ar.trigger_keyword), '%')
                ORDER BY ar.priority DESC, ar.created_at ASC
                LIMIT 1
            ");
            $stmt->execute([$messageContent]);
            $response = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($response) {
                // Get conversation details
                $convStmt = $this->pdo->prepare("
                    SELECT customer_phone
                    FROM whatsapp_conversations
                    WHERE conversation_id = ?
                ");
                $convStmt->execute([$conversationId]);
                $conversation = $convStmt->fetch(PDO::FETCH_ASSOC);

                if ($conversation) {
                    if ($response['response_type'] === 'template' && $response['interakt_template_name']) {
                        // Send template message
                        $this->sendTemplateMessage(
                            $conversation['customer_phone'],
                            $response['interakt_template_name'],
                            ['callbackData' => 'auto_response']
                        );
                    } else {
                        // For text responses, we'd need a different API endpoint
                        // This is a placeholder for future implementation
                        error_log("Text auto-responses not yet implemented");
                    }

                    // Update usage count
                    $updateStmt = $this->pdo->prepare("
                        UPDATE automated_responses
                        SET usage_count = usage_count + 1
                        WHERE response_id = ?
                    ");
                    $updateStmt->execute([$response['response_id']]);
                }
            }

            return true;

        } catch (Exception $e) {
            error_log("Error checking automated response: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get conversation history
     */
    public function getConversationHistory($conversationId, $limit = 50) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    wm.*,
                    sa.name as agent_name,
                    CASE
                        WHEN wm.sender_type = 'customer' THEN wc.customer_phone
                        WHEN wm.sender_type = 'agent' THEN sa.name
                        ELSE 'System'
                    END as sender_name
                FROM whatsapp_messages wm
                LEFT JOIN whatsapp_conversations wc ON wm.conversation_id = wc.conversation_id
                LEFT JOIN support_agents sa ON wm.sender_id = sa.agent_id
                WHERE wm.conversation_id = ?
                ORDER BY wm.created_at ASC
                LIMIT ?
            ");

            $stmt->execute([$conversationId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Error getting conversation history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Send agent message (for manual responses)
     */
    public function sendAgentMessage($conversationId, $agentId, $message) {
        try {
            // Get conversation details
            $stmt = $this->pdo->prepare("
                SELECT customer_phone
                FROM whatsapp_conversations
                WHERE conversation_id = ?
            ");
            $stmt->execute([$conversationId]);
            $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$conversation) {
                throw new Exception('Conversation not found');
            }

            // For now, we'll use a generic template for agent messages
            // In production, you'd want to create specific templates or use a different approach
            $result = $this->sendTemplateMessage(
                $conversation['customer_phone'],
                'agent_message', // This template would need to be created in Interakt
                [
                    'bodyValues' => [$message],
                    'callbackData' => json_encode(['agent_id' => $agentId, 'conversation_id' => $conversationId])
                ]
            );

            if ($result['success']) {
                // Log the message
                $messageId = $this->generateUUID();
                $stmt = $this->pdo->prepare("
                    INSERT INTO whatsapp_messages (
                        message_id, conversation_id, interakt_message_id, sender_type,
                        sender_id, message_type, content, delivery_status,
                        is_automated, created_at
                    ) VALUES (?, ?, ?, 'agent', ?, 'text', ?, 'sent', FALSE, NOW())
                ");

                $stmt->execute([
                    $messageId,
                    $conversationId,
                    $result['message_id'],
                    $agentId,
                    $message
                ]);
            }

            return $result;

        } catch (Exception $e) {
            error_log("Error sending agent message: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
}