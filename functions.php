<?php
function expireOldMatches($pdo) {
    $expiryMinutes = 3;
    $threshold = date('Y-m-d H:i:s', strtotime("-{$expiryMinutes} minutes"));

    // Get only waiting matches where player2 hasn't joined
    $stmt = $pdo->prepare("
        SELECT id, player1_id, entry_fee 
        FROM matches 
        WHERE player2_id IS NULL 
          AND created_at < ? 
          AND status = 'waiting'
    ");
    $stmt->execute([$threshold]);
    $matches = $stmt->fetchAll();

    foreach ($matches as $match) {
        // Refund entry fee to player1
        $refund = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $refund->execute([$match['entry_fee'], $match['player1_id']]);

        // Delete the match
        $delete = $pdo->prepare("DELETE FROM matches WHERE id = ?");
        $delete->execute([$match['id']]);
    }
}
?>
