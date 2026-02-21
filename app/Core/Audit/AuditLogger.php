<?php

namespace App\Core\Audit;

use App\Core\Auth;
use App\Core\Database;
use PDO;
use Exception;

/**
 * AuditLogger - Centralized system for auditing critical actions.
 * 
 * Rules:
 * 1. Action names must follow 'verbo_recurso' pattern.
 * 2. Context is optional and stored as JSON.
 * 3. Failures in logging NEVER break the application flow.
 */
class AuditLogger
{
    /**
     * Logs an action to the database.
     * 
     * @param string $action The action name (verbo_recurso).
     * @param array|null $context Additional metadata for the action.
     * @return void
     */
    public static function log(string $action, ?array $context = null): void
    {
        try {
            $db = Database::getInstance();

            $userId = null;
            if (session_status() === PHP_SESSION_ACTIVE) {
                $user = Auth::user();
                $userId = $user ? $user->id : null;
            }

            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            // Limit IP address length for safety (IPv6 can be long)
            if ($ipAddress && strlen($ipAddress) > 45) {
                $ipAddress = substr($ipAddress, 0, 45);
            }

            $sql = "INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent, created_at) 
                    VALUES (:user_id, :action, :details, :ip_address, :user_agent, NOW())";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':action' => $action,
                ':details' => $context ? json_encode($context) : null,
                ':ip_address' => $ipAddress,
                ':user_agent' => $userAgent
            ]);

        } catch (Exception $e) {
            // Silently fail to protect application resilience.
            // In a real-world scenario, we might log this to a file (error_log).
            error_log("AuditLogger Failure: " . $e->getMessage());
        }
    }
}
