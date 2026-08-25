<?php
/* ═══════════════════════════════════════════════════════
   JOURNAL D'ACTIVITÉ — Gala Agro Admin
   ═══════════════════════════════════════════════════════
   USAGE — à appeler juste avant/après une modification
   sur une commande ou une candidature :

       require_once '../includes/log_activity.php';
       logActivity($pdo, 'modification_statut', 'commandes', $id,
           "Statut changé : '$ancien' → '$nouveau'");

   Actions suggérées : 'modification_statut', 'suppression', 'creation'
   Tables suggérées  : 'commandes', 'candidatures'
   ═══════════════════════════════════════════════════════ */

function logActivity(PDO $pdo, string $action, string $table, int $recordId, string $details = ''): void
{
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO activity_log (username, role, action, table_concernee, record_id, details, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $_SESSION['username'] ?? 'inconnu',
            $_SESSION['role']     ?? 'inconnu',
            $action,
            $table,
            $recordId,
            $details,
        ]);
    } catch (Exception $e) {
        // On n'interrompt jamais l'action principale si le journal échoue
        // (par ex. si la table n'a pas encore été créée)
    }
}
