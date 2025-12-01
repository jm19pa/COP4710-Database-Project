<?php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

try {
    $pdo->beginTransaction();

    // Disable foreign key checks for truncate order safety
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach (['Picked_up','Purchased','Reserved','On_Sale','Plates','Members'] as $t) {
        $pdo->exec("DELETE FROM $t");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

    // Password hash for "Password123"
    $hash = password_hash("Password123", PASSWORD_DEFAULT);

    // Members (admin, 3 restaurants, 5 customers, 3 donors, 3 needy)
    $insM = $pdo->prepare('INSERT INTO Members (mid, username, email, pwd, m_type, named, phone) VALUES (?,?,?,?,?,?,?)');
    $insM->execute([1,'admin','admin@example.com',$hash,'admin','Admin User','555-0000']);
    $insM->execute([2,'resto1','resto1@example.com',$hash,'restaurant','Resto One','555-1001']);
    $insM->execute([6,'resto2','resto2@example.com',$hash,'restaurant','Resto Two','555-1002']);
    $insM->execute([11,'resto3','resto3@example.com',$hash,'restaurant','Resto Three','555-1003']);
    $insM->execute([3,'cust1','cust1@example.com',$hash,'customer','Customer One','555-2001']);
    $insM->execute([7,'cust2','cust2@example.com',$hash,'customer','Customer Two','555-2002']);
    $insM->execute([8,'cust3','cust3@example.com',$hash,'customer','Customer Three','555-2003']);
    $insM->execute([12,'cust4','cust4@example.com',$hash,'customer','Customer Four','555-2004']);
    $insM->execute([13,'cust5','cust5@example.com',$hash,'customer','Customer Five','555-2005']);
    $insM->execute([4,'donor1','donor1@example.com',$hash,'donor','Donor One','555-3001']);
    $insM->execute([9,'donor2','donor2@example.com',$hash,'donor','Donor Two','555-3002']);
    $insM->execute([14,'donor3','donor3@example.com',$hash,'donor','Donor Three','555-3003']);
    $insM->execute([5,'needy1','needy1@example.com',$hash,'needy','Needy One',null]);
    $insM->execute([10,'needy2','needy2@example.com',$hash,'needy','Needy Two',null]);
    $insM->execute([15,'needy3','needy3@example.com',$hash,'needy','Needy Three',null]);

    // Plates (for restaurants)
    $insP = $pdo->prepare('INSERT INTO Plates (pid, mid, named, price) VALUES (?,?,?,?)');
    $insP->execute([101,2,'Chicken Plate',8.50]);
    $insP->execute([102,2,'Veggie Plate',7.25]);
    $insP->execute([103,2,'Pasta Plate',9.00]);
    $insP->execute([201,6,'Burger Plate',8.75]);
    $insP->execute([202,6,'Salad Plate',6.50]);
    $insP->execute([203,6,'Fish Plate',10.25]);
    $insP->execute([301,11,'Taco Plate',7.75]);
    $insP->execute([302,11,'Sushi Plate',12.00]);

    $year = (int)date('Y');
    $prev = $year - 1; // 2024
    $prev2 = $year - 2; // 2023

    // On_Sale across months for restaurants (2025 current year)
    $insOS = $pdo->prepare('INSERT INTO On_Sale (pid, quantity, listed_at) VALUES (?,?,?)');
    $insOS->execute([101,50,sprintf('%d-01-10 09:00:00',$year)]);
    $insOS->execute([102,40,sprintf('%d-02-05 11:30:00',$year)]);
    $insOS->execute([103,30,sprintf('%d-03-12 15:45:00',$year)]);
    $insOS->execute([201,45,sprintf('%d-04-08 10:15:00',$year)]);
    $insOS->execute([202,35,sprintf('%d-05-14 12:20:00',$year)]);
    $insOS->execute([203,25,sprintf('%d-06-21 16:10:00',$year)]);
    $insOS->execute([301,20,sprintf('%d-07-02 10:00:00',$year)]);
    $insOS->execute([302,15,sprintf('%d-08-18 18:00:00',$year)]);

    // On_Sale in 2024
    $insOS->execute([101,40,sprintf('%d-11-10 09:00:00',$prev)]);
    $insOS->execute([201,30,sprintf('%d-09-08 10:15:00',$prev)]);
    $insOS->execute([301,25,sprintf('%d-06-02 10:00:00',$prev)]);

    // On_Sale late 2023
    $insOS->execute([102,20,sprintf('%d-12-05 11:30:00',$prev2)]);
    $insOS->execute([202,18,sprintf('%d-12-14 12:20:00',$prev2)]);

    // Reserved (2025)
    $insR = $pdo->prepare('INSERT INTO Reserved (pid, mid, quantity, reserved_at) VALUES (?,?,?,?)');
    $insR->execute([101,3,5,sprintf('%d-01-10 10:00:00',$year)]);
    $insR->execute([102,4,10,sprintf('%d-02-05 12:00:00',$year)]);
    $insR->execute([103,3,3,sprintf('%d-03-12 16:00:00',$year)]);
    $insR->execute([201,7,4,sprintf('%d-04-08 11:00:00',$year)]);
    $insR->execute([202,8,6,sprintf('%d-05-14 12:45:00',$year)]);
    $insR->execute([203,7,3,sprintf('%d-06-21 16:30:00',$year)]);
    $insR->execute([301,12,2,sprintf('%d-07-03 11:00:00',$year)]);
    $insR->execute([302,13,3,sprintf('%d-08-19 19:00:00',$year)]);

    // Reserved (2024)
    $insR->execute([101,3,2,sprintf('%d-11-11 10:00:00',$prev)]);
    $insR->execute([201,7,1,sprintf('%d-09-09 11:00:00',$prev)]);
    $insR->execute([301,12,2,sprintf('%d-06-03 11:00:00',$prev)]);

    // Reserved (late 2023)
    $insR->execute([102,4,3,sprintf('%d-12-06 12:00:00',$prev2)]);
    $insR->execute([202,8,2,sprintf('%d-12-15 12:45:00',$prev2)]);

    // Purchased spread across months and members (2025)
    $insPU = $pdo->prepare('INSERT INTO Purchased (pid, mid, quantity, purchased_at) VALUES (?,?,?,?)');
    $insPU->execute([101,3,4,sprintf('%d-01-11 09:15:00',$year)]);
    $insPU->execute([102,4,6,sprintf('%d-02-06 12:10:00',$year)]);
    $insPU->execute([103,3,2,sprintf('%d-03-13 17:05:00',$year)]);
    $insPU->execute([101,4,5,sprintf('%d-04-01 10:00:00',$year)]);
    $insPU->execute([201,7,3,sprintf('%d-05-03 11:20:00',$year)]);
    $insPU->execute([202,8,4,sprintf('%d-06-09 13:40:00',$year)]);
    $insPU->execute([203,9,5,sprintf('%d-07-15 15:10:00',$year)]);
    $insPU->execute([101,8,2,sprintf('%d-08-02 10:05:00',$year)]);
    $insPU->execute([102,3,3,sprintf('%d-09-18 18:25:00',$year)]);
    $insPU->execute([103,7,6,sprintf('%d-10-22 12:55:00',$year)]);
    $insPU->execute([202,9,2,sprintf('%d-11-05 14:35:00',$year)]);
    $insPU->execute([201,3,4,sprintf('%d-12-12 16:45:00',$year)]);
    $insPU->execute([301,12,3,sprintf('%d-07-04 12:10:00',$year)]);
    $insPU->execute([302,13,2,sprintf('%d-08-20 20:05:00',$year)]);

    // Purchased (2024)
    $insPU->execute([101,3,2,sprintf('%d-11-12 09:15:00',$prev)]);
    $insPU->execute([201,7,1,sprintf('%d-09-10 11:20:00',$prev)]);
    $insPU->execute([301,12,2,sprintf('%d-06-04 12:10:00',$prev)]);

    // Purchased (late 2023)
    $insPU->execute([102,4,3,sprintf('%d-12-07 12:10:00',$prev2)]);
    $insPU->execute([202,8,2,sprintf('%d-12-16 13:40:00',$prev2)]);

    // Picked_up (needy pickups with donor fulfillment) across months (2025)
    $insPK = $pdo->prepare('INSERT INTO Picked_up (pid, mid, donor_id, quantity, picked_up_at) VALUES (?,?,?,?,?)');
    $insPK->execute([101,5,4,7,sprintf('%d-01-12 14:00:00',$year)]);
    $insPK->execute([102,10,4,3,sprintf('%d-02-07 15:00:00',$year)]);
    $insPK->execute([103,5,9,5,sprintf('%d-03-14 16:30:00',$year)]);
    $insPK->execute([201,10,4,6,sprintf('%d-04-16 11:10:00',$year)]);
    $insPK->execute([202,5,9,4,sprintf('%d-05-20 13:50:00',$year)]);
    $insPK->execute([203,10,4,5,sprintf('%d-06-25 17:20:00',$year)]);
    $insPK->execute([301,15,14,4,sprintf('%d-07-06 12:00:00',$year)]);
    $insPK->execute([302,15,14,3,sprintf('%d-08-22 18:30:00',$year)]);

    // Picked_up (2024)
    $insPK->execute([101,5,4,2,sprintf('%d-11-13 14:00:00',$prev)]);
    $insPK->execute([201,10,4,1,sprintf('%d-09-11 11:10:00',$prev)]);
    $insPK->execute([301,15,14,2,sprintf('%d-06-05 12:00:00',$prev)]);

    // Picked_up (late 2023)
    $insPK->execute([102,10,4,2,sprintf('%d-12-08 15:00:00',$prev2)]);
    $insPK->execute([202,5,9,1,sprintf('%d-12-17 13:50:00',$prev2)]);

    $pdo->commit();
    echo json_encode(['status'=>'success','message'=>'Seeded database']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['status'=>'error','error'=>$e->getMessage()]);
}
