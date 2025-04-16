create database hotel;
use hotel;


CREATE TABLE Role (
    RoleID int AUTO_INCREMENT primary key,
    RoleName varchar(20)   -- guest,admin
);

CREATE TABLE User (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(100),
    LastName VARCHAR(100),
    Email VARCHAR(100),
    Phone VARCHAR(20),
    Address VARCHAR(255),
    UserRole INT,
    FOREIGN KEY (UserRole) REFERENCES Role(RoleID) ON UPDATE CASCADE ON DELETE CASCADE
);


CREATE TABLE Profile (
    ProfileID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT UNIQUE,
    Username varchar(50),
    Password VARCHAR(255) NOT NULL,   
    FOREIGN KEY (UserID) REFERENCES User(UserID) ON UPDATE CASCADE ON DELETE CASCADE
);


CREATE TABLE RoomType (
  TypeID INT PRIMARY KEY AUTO_INCREMENT,
  TypeName VARCHAR(50) NOT NULL,
  Descrip varchar(100),
  Room_Rate int,
  Tax_Rate int
);

CREATE TABLE RoomStatus (
  StatusID INT PRIMARY KEY AUTO_INCREMENT,
  StatusName VARCHAR(50) NOT NULL
);

CREATE TABLE Room (
  RoomNumber INT PRIMARY KEY,
  RoomTypeId INT NOT NULL,
  RoomStatusId INT NOT NULL,
  LastCleanedDate DATE,
  FOREIGN KEY (RoomTypeId) REFERENCES RoomType(TypeID) ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (RoomStatusId) REFERENCES RoomStatus(StatusID) ON UPDATE CASCADE ON DELETE CASCADE
);



CREATE TABLE BillingStatus (
  StatusID INT PRIMARY KEY AUTO_INCREMENT,
  StatusName VARCHAR(50) NOT NULL
);

CREATE TABLE Billing (
  BillingID INT PRIMARY KEY AUTO_INCREMENT,
  BillingStatusId INT NOT NULL,
  SubTotal decimal(10,2) not null,
  Taxes decimal(10,2) not null,
  TotalCost decimal(10,2) not null,
  Date DATE,
  RefundBillingID INT Default null,
  FOREIGN KEY (BillingStatusId) REFERENCES BillingStatus(StatusID)
);

ALter table Billing
add constraint fk_billing_refund
foreign key (RefundBillingID) references Billing(BillingID);


CREATE TABLE CancellationStatus (
  StatusID INT PRIMARY KEY AUTO_INCREMENT,
  StatusName VARCHAR(50) NOT NULL
);

CREATE TABLE CancellationPolicy (
  PolicyID INT PRIMARY KEY AUTO_INCREMENT,
  PolicyName VARCHAR(100) NOT NULL,
  RefundPercentage int not null
);


CREATE TABLE ReservationStatus (
  StatusID INT PRIMARY KEY AUTO_INCREMENT,
  StatusName VARCHAR(50) NOT NULL
);

CREATE TABLE Reservations (
  ReservationID INT PRIMARY KEY AUTO_INCREMENT,
  RoomNum INT NOT NULL,
  ReservationStatusID INT NOT NULL,
  CancellationStatusID INT DEFAULT NULL,
  BillingID int,
  FOREIGN KEY (RoomNum) REFERENCES Room(RoomNumber),
  FOREIGN KEY (UserID) REFERENCES User(UserID) ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (ReservationStatusID) REFERENCES ReservationStatus(StatusID),
  FOREIGN KEY (CancellationStatusID) REFERENCES CancellationStatus(StatusID),
  FOREIGN KEY (BillingID) REFERENCES Billing(BillingID)
);

CREATE TABLE Housekeeping (
    HousekeepingID INT PRIMARY KEY AUTO_INCREMENT,
    RoomNumber INT, -- Foreign key referencing Rooms table
    CleaningDate DATE,
    FOREIGN KEY (RoomNumber) REFERENCES Room(RoomNumber)
);

CREATE TABLE Feedback (
    FeedbackID INT PRIMARY KEY AUTO_INCREMENT,
    ReservationID INT, -- Foreign key referencing the Reservations table
    FeedbackText TEXT,
    Rating tinyINT CHECK (Rating BETWEEN 1 AND 5),
    FOREIGN KEY (ReservationID) REFERENCES Reservations(ReservationID)
);




-----------------------------------------------------------views--------------------------------------------------------------------------

CREATE VIEW ViewRoomBillingInfo AS
SELECT 
    r.ReservationID,
    rt.TypeName,
    rt.Room_Rate,
    r.CheckInDate,
    r.CheckOutDate,
    b.SubTotal,
    b.Taxes,
    b.TotalCost,
    b.Date AS BillingDate
FROM 
    Reservations r
JOIN 
    Room r2 ON r.RoomNum = r2.RoomNumber
JOIN 
    RoomType rt ON r2.RoomTypeId = rt.TypeID
JOIN 
    Billing b ON r.BillingID = b.BillingID;



-----


CREATE VIEW ViewUserReservations AS
SELECT 
    r.ReservationID,
    r.UserID,
    p.Username,
    rt.TypeName AS RoomType,
    rt.Room_Rate,
    rt.Descrip AS RoomDescription,
    r.CheckInDate,
    r.CheckOutDate,
    rs.StatusName AS ReservationStatus,
    b.SubTotal,
    b.Taxes,
    b.TotalCost,
    b.Date AS BillingDate
FROM 
    Reservations r
JOIN 
    ReservationStatus rs ON r.ReservationStatusID = rs.StatusID
JOIN 
    Room rm ON r.RoomNum = rm.RoomNumber
JOIN 
    RoomType rt ON rm.RoomTypeId = rt.TypeID
JOIN 
    User u ON r.UserID = u.UserID
JOIN
    Profile p ON u.UserID=p.UserID
LEFT JOIN 
    Billing b ON r.BillingID = b.BillingID;



------

CREATE VIEW RoomInfo AS
SELECT
    R.RoomNumber AS RoomNumber,
    RT.TypeName AS TypeName,
    RT.Descrip AS Descrip,
    RT.Room_Rate AS Room_Rate,
    RT.Tax_Rate AS Tax_Rate
FROM 
    Room R
JOIN
    RoomType RT ON R.RoomTypeId = RT.TypeID;




-- ----------------------------------------------------stored procedures-----------------------------------------------------------------------


DELIMITER //

CREATE PROCEDURE RegisterUser(
    IN p_Username VARCHAR(50),
    IN p_FirstName VARCHAR(100),
    IN p_LastName VARCHAR(100),
    IN p_Email VARCHAR(100),
    IN p_Phone VARCHAR(20),
    IN p_Address VARCHAR(255),
    IN p_Password VARCHAR(255),
    OUT p_UserID INT
)
BEGIN
    
    DECLARE defaultRoleID INT DEFAULT 1;

    -- Insert into the User table
    INSERT INTO User (Username, FirstName, LastName, Email, Phone, Address, UserRole)
    VALUES (p_Username, p_FirstName, p_LastName, p_Email, p_Phone, p_Address, defaultRoleID);

    -- Get the last inserted ID
    SET p_UserID = LAST_INSERT_ID();

    -- Insert into the Profile table
    INSERT INTO Profile (UserID, Username, Password)
    VALUES (p_UserID, p_Username, p_Password);
END //

DELIMITER ;

-----

DELIMITER $$

CREATE PROCEDURE MakeReservation(
    IN p_UserID INT,
    IN p_RoomNumber INT,
    IN p_CheckInDate DATE,
    IN p_NumOfNights INT,
    IN p_RoomRate DECIMAL(10,2),
    IN p_TaxRate INT,
    OUT reservation_Id INT
)
BEGIN
    DECLARE v_CheckOutDate DATE;
    DECLARE v_ReservationStatusID INT DEFAULT 1; -- Assuming 1 is 'Confirmed'
    DECLARE v_BillingID INT;

    -- Calculate the Check-Out Date
    SET v_CheckOutDate = DATE_ADD(p_CheckInDate, INTERVAL p_NumOfNights DAY);

    CALL CreateBillingRecord(p_RoomRate, p_NumOfNights, p_TaxRate, v_BillingID);

    -- Insert into Reservations table
    INSERT INTO Reservations (
        RoomNum,
        UserID,
        CheckInDate,
        CheckOutDate,
        ReservationStatusID,
        BillingID,
        CancellationStatusID
    )
    VALUES (
        p_RoomNumber,
        p_UserID,
        p_CheckInDate,
        v_CheckOutDate,
        v_ReservationStatusID,
        v_BillingID,
        NULL
    );

    SET reservation_Id = LAST_INSERT_ID();

END$$

DELIMITER ;


-----

DELIMITER $$

CREATE PROCEDURE CreateBillingRecord(
    IN p_RoomRate DECIMAL(10,2),
    IN p_NumOfNights INT,
    IN p_TaxRate INT,
    OUT p_BillingID INT
)
BEGIN
    DECLARE v_SubTotal DECIMAL(10,2);
    DECLARE v_Taxes DECIMAL(10,2);
    DECLARE v_TotalCost DECIMAL(10,2);

    -- Calculate the billing amounts
    SET v_SubTotal = p_RoomRate * p_NumOfNights;
    SET v_Taxes = v_SubTotal * p_TaxRate / 100;
    SET v_TotalCost = v_SubTotal + v_Taxes;

    -- Insert the billing record into the Billing table
    INSERT INTO Billing (BillingStatusId, SubTotal, Taxes, TotalCost, Date)
    VALUES (1, v_SubTotal, v_Taxes, v_TotalCost, CURDATE()); -- Assuming BillingStatusId 1 is 'Confirmed'

    -- Set the output parameter to the last inserted ID
    SET p_BillingID = LAST_INSERT_ID();
END$$

DELIMITER ;


