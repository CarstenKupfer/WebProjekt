<?php

require_once __DIR__ . "/dbUtil.php";

// Erstellt ein Item in der DB ($imagePath kann null sein, wenn kein Bild hochgeladen wurde).
function createItemInDB(
    string $type, 
    string $title, 
    string $description, 
    string $location, 
    string $eventDate, 
    ?string $imagePath, 
    int $userId
    ){
        $db = getDb();

        $sql = "INSERT INTO items (user_id, type, title, description, location, event_date, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $db->prepare($sql);
        if(!$stmt){
            throw new RuntimeException($db->error);
        }

        $stmt->bind_param("issssss", $userId, $type, $title, $description, $location, $eventDate, $imagePath);

        if(!$stmt->execute()){
            $stmt->close();
            throw new RuntimeException("Insert fehlgeschlagen: " . $stmt->error);
        }

        $stmt->close();

}