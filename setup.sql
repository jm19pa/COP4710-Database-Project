#DROP TABLE MEMBERS;
CREATE TABLE Members ( 
mid       INT PRIMARY KEY AUTO_INCREMENT,
username  VARCHAR(50) NOT NULL UNIQUE,
email     VARCHAR(100) NOT NULL UNIQUE,
pwd       VARCHAR(255) NOT NULL,
m_type ENUM('admin', 'restaurant', 'customer', 'donor', 'needy') NOT NULL,
named VARCHAR(100),
phone VARCHAR(20),
CONSTRAINT chk_phone_required_for_non_needy
  CHECK(
    m_type = 'needy'
    OR (phone IS NOT NULL AND phone <> '')
  )
);
#DROP TABLE CARDS;
CREATE TABLE Cards (
mid INT NOT NULL,
FOREIGN KEY (mid) REFERENCES Members(mid)
  ON DELETE CASCADE ON UPDATE CASCADE,
cnumber VARCHAR(17) UNIQUE,
expdate DATE,
zipcode INT,
csv VARCHAR(6)
);
#DROP TABLE PLATES;
CREATE TABLE Plates(
pid           INT PRIMARY KEY AUTO_INCREMENT,
display_order INT,
mid           INT,
price         FLOAT,
named         VARCHAR(100),
plate_type    VARCHAR(100),
described      text,
CONSTRAINT fk_plates_creator
  FOREIGN KEY (mid) REFERENCES Members(mid)
    ON DELETE RESTRICT ON UPDATE CASCADE
);
#DROP TABLE IMAGES;
CREATE TABLE Images (
pid           INT NOT NULL,
display_order INT,
image         LONGBLOB NOT NULL,
CONSTRAINT fk_images_plate
  FOREIGN KEY (pid) REFERENCES Plates(pid)
    ON DELETE CASCADE ON UPDATE RESTRICT,
PRIMARY KEY (pid, display_order)
);
#DROP TABLE ON_SALE;
CREATE TABLE On_Sale (
  pid         INT,
  quantity    INT NOT NULL,
  listed_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  available_from DATETIME NOT NULL,
  available_until DATETIME NOT NULL,
  CONSTRAINT fk_on_sale_plate
    FOREIGN KEY (pid) REFERENCES Plates(pid)
      ON DELETE RESTRICT ON UPDATE RESTRICT,
  PRIMARY KEY (pid, listed_at)
);
#DROP TABLE RESERVED;
CREATE TABLE Reserved (
  pid         INT NOT NULL,
  mid         INT NOT NULL,
  quantity    INT NOT NULL,
  reserved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reserved_plate
    FOREIGN KEY (pid) REFERENCES Plates(pid)
      ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_reserved_member
    FOREIGN KEY (mid) REFERENCES Members(mid)
      ON DELETE RESTRICT ON UPDATE RESTRICT,
  PRIMARY KEY (pid, mid, reserved_at)
);
#DROP TABLE PURCHASED;
CREATE TABLE Purchased (
  pid         INT NOT NULL,
  mid         INT NOT NULL,
  quantity    INT NOT NULL,
  purchased_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_purchased_plate
    FOREIGN KEY (pid) REFERENCES Plates(pid)
      ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_purchased_member
    FOREIGN KEY (mid) REFERENCES Members(mid)
      ON DELETE RESTRICT ON UPDATE RESTRICT,
  PRIMARY KEY (pid, mid, purchased_at)
);
#DROP TABLE PICKED_UP;
CREATE TABLE Picked_up (
  pid         INT NOT NULL,
  mid         INT NOT NULL,
  donor_id    INT NULL,
  quantity    INT NOT NULL,
  picked_up_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_picked_plate
    FOREIGN KEY (pid) REFERENCES Plates(pid)
      ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_picked_member
    FOREIGN KEY (mid) REFERENCES Members(mid)
      ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_picked_donor
    FOREIGN KEY (donor_id) REFERENCES Members(mid)
      ON DELETE SET NULL ON UPDATE RESTRICT,
  PRIMARY KEY (pid, mid, picked_up_at)
);

DELIMITER $$
CREATE PROCEDURE Plates_Owned(mid INT)
BEGIN
    SELECT *
    FROM Plates P
    WHERE mid=P.mid;
END;
$$

DELIMITER $$
CREATE PROCEDURE Plates_On_Sale()
BEGIN
	SELECT *
    FROM Plates P, On_Sale OS
    WHERE OS.quantity>0 AND P.pid = OS.pid;
END;
$$

DELIMITER $$
CREATE PROCEDURE Plates_Reserved(mid int)
BEGIN
	SELECT *
    FROM Plates P, Reserved B
    WHERE OS.quantity>0 AND P.pid = B.pid AND mid = B.mid;
END;
$$

DELIMITER $$
CREATE PROCEDURE Plates_Purchased()
BEGIN
	SELECT *
    FROM Plates P, Purchased B
    WHERE OS.quantity>0 AND P.pid = B.pid;
END;
$$

DELIMITER $$
#DROP PROCEDURE RESERVE;
CREATE PROCEDURE Reserve(mid_in INT, pid_in INT)
BEGIN
	IF (EXISTS (SELECT * FROM Members M WHERE mid_in = M.mid AND M.m_type='customer'))
		IF(EXISTS (SELECT * FROM On_Sale OS WHERE pid_in = OS.pid AND OS.quantity>0))
			UPDATE On_Sale
            SET quantity = quantity-1
            WHERE pid_in = pid;
			
            INSERT INTO Reserved (pid, mid, quantity)
            VALUES (pid_in, mid_in, 1)
			ON CONFLICT (pid, mid) DO UPDATE
			SET quantity = quantity+1;
		END IF;
    END IF;
END;
$$

DELIMITER $$
CREATE PROCEDURE Customer_Pick_Up(mid_in INT, pid_in INT)
here:BEGIN
	IF (EXISTS (SELECT * FROM Members M WHERE mid_in = M.mid AND M.m_type='customer')) THEN
		IF (NOT ValidatePaymentMethodExists(mid_in)) THEN
            LEAVE here;
		ELSEIF(EXISTS (SELECT * FROM Reserved R WHERE pid_in = R.pid AND mid_in = R.mid AND R.quantity>0)) THEN
			UPDATE Reserved
            SET quantity = quantity-1
            WHERE pid_in = pid;
            
            INSERT INTO Picked_up (pid, mid, quantity)
            VALUES (pid_in, mid_in, 1);
		END IF;
	ELSE
	SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Member is not a customer!';
    END IF;
END;
$$

DELIMITER $$
CREATE PROCEDURE Needy_Pick_Up(mid_in INT, pid_in INT)
here:BEGIN
	IF ((SELECT COUNT(*) FROM Members M WHERE mid = M.mid AND M.m_type='needy')>1) then
		IF(NOT StillNeedy(mid_in)) then
			SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Maximum plates (2) reached!';
            LEAVE here;
		ELSEIF(EXISTS (SELECT * FROM Purchased P WHERE pid_in = P.pid AND P.quantity>0)) then
			UPDATE Purchased
            SET quantity = quantity-1
            WHERE pid_in = pid;

            UPDATE Reserved
			SET quantity = quantity+1
            WHERE pid_in = pid;
		end if;
    END IF;
END;
$$
DELIMITER $$
CREATE PROCEDURE Donate(mid_in INT, pid_in INT)
BEGIN
	IF (EXISTS (SELECT * FROM Members M WHERE mid_in = M.mid AND M.m_type='donor')) then
		IF(EXISTS (SELECT * FROM On_Sale OS WHERE pid_in = OS.pid AND OS.quantity>0)) then
			UPDATE On_Sale
            SET quantity = quantity-1
            WHERE pid_in = pid;
			
            INSERT INTO Purchased (pid, mid, quantity)
            VALUES (pid_in, mid_in, 1)
			ON DUPLICATE KEY UPDATE
			quantity = quantity+1;
		
		END IF;
    END IF;
END;
$$
DELIMITER $$
CREATE function ValidatePaymentMethodExists(mid INT)
returns bool
BEGIN
    DECLARE total_cards INT;
    SELECT COUNT(*) INTO total_cards FROM Cards WHERE Cards.mid = mid;

    IF total_cards <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No payment card provided for member!';
        RETURN FALSE;
	ELSE RETURN TRUE;
    END IF;
END;
$$

DELIMITER $$
CREATE function StillNeedy(mid INT)
returns bool
BEGIN
    DECLARE total_reserved INT;
    DECLARE total_picked_up INT;
    SELECT SUM(R.quantity) INTO total_reserved FROM Reserved R WHERE R.mid = mid;
    SELECT SUM(P.quantity) INTO total_picked_up FROM Picked_Up P WHERE P.mid = mid;
    IF (total_reserved + total_picked_up) <= 1 THEN
        RETURN TRUE;
	ELSE
        RETURN FALSE;
    END IF;
END;
$$

DELIMITER $$
CREATE TRIGGER On_Sale_Guard
BEFORE INSERT ON On_Sale
FOR EACH ROW
BEGIN
  -- Must be positive quantity
  IF NEW.quantity IS NULL OR NEW.quantity <= 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'On_Sale.quantity must be > 0';
  END IF;

  -- Must not be in any other state
  IF EXISTS (SELECT 1 FROM Reserved  WHERE pid = NEW.pid LIMIT 1)
     OR EXISTS (SELECT 1 FROM Purchased WHERE pid = NEW.pid LIMIT 1)
     OR EXISTS (SELECT 1 FROM Picked_up WHERE pid = NEW.pid LIMIT 1)
  THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Plate is already in another state';
  END IF;
END
$$
DELIMITER $$
CREATE TRIGGER Reserved_Guard
BEFORE INSERT ON Reserved
FOR EACH ROW
BEGIN
  -- Must be positive quantity
  IF NEW.quantity IS NULL OR NEW.quantity <= 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Reserved.quantity must be > 0';
  END IF;

  -- Must not be in conflicting states
  IF EXISTS (SELECT 1 FROM Purchased WHERE pid = NEW.pid LIMIT 1)
     OR EXISTS (SELECT 1 FROM Picked_up WHERE pid = NEW.pid LIMIT 1)
  THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Plate is already purchased or picked up';
  END IF;

  -- Must be currently on sale
  IF NOT EXISTS (SELECT 1 FROM On_Sale WHERE pid = NEW.pid LIMIT 1) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot reserve a plate that is not On_Sale';
  END IF;
END
$$
DELIMITER $$
CREATE TRIGGER Purchased_Guard
BEFORE INSERT ON Purchased
FOR EACH ROW
BEGIN
  -- Must be positive quantity
  IF NEW.quantity IS NULL OR NEW.quantity <= 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Purchased.quantity must be > 0';
  END IF;

  -- Must not be picked up already
  IF EXISTS (SELECT 1 FROM Picked_up WHERE pid = NEW.pid LIMIT 1) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Plate already picked up';
  END IF;
END
$$
DELIMITER $$
CREATE TRIGGER Picked_up_Guard
BEFORE INSERT ON Picked_up
FOR EACH ROW
BEGIN
  -- Must be positive quantity
  IF NEW.quantity IS NULL OR NEW.quantity <= 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Picked_up.quantity must be > 0';
  END IF;

  -- Must not conflict with existing final states, also prevents double pickup entries for the same plate
  IF EXISTS (SELECT 1 FROM Picked_up WHERE pid = NEW.pid LIMIT 1) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Plate already picked up';
  END IF;
END
$$
DELIMITER ;