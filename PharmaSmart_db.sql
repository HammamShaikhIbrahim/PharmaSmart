CREATE DATABASE pharmasmart_db;
USE pharmasmart_db;

-- USERS
CREATE TABLE User (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Fname VARCHAR(50) NOT NULL,
    Lname VARCHAR(50) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Phone VARCHAR(20),
    RoleID INT NOT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- PATIENT
CREATE TABLE Patient (
    PatientID INT PRIMARY KEY,
    Address VARCHAR(255),
    Latitude DECIMAL(10,8),
    Longitude DECIMAL(11,8),
    DOB DATE,
    MedicalHistory TEXT,
    FOREIGN KEY (PatientID) REFERENCES User(UserID) ON DELETE CASCADE
);

-- PHARMACIST
CREATE TABLE Pharmacist (
    PharmacistID INT PRIMARY KEY,
    PharmacyName VARCHAR(100) NOT NULL,
    LicenseNumber VARCHAR(50) UNIQUE NOT NULL,
    Location VARCHAR(255),
    Latitude DECIMAL(10,8),
    Longitude DECIMAL(11,8),
    WorkingHours VARCHAR(100),
    Logo VARCHAR(255),
    IsApproved BOOLEAN DEFAULT 0,
    FOREIGN KEY (PharmacistID) REFERENCES User(UserID) ON DELETE CASCADE
);

-- ADMIN
CREATE TABLE Admin (
    AdminID INT PRIMARY KEY,
    Permissions TEXT,
    FOREIGN KEY (AdminID) REFERENCES User(UserID) ON DELETE CASCADE
);

-- CATEGORY
CREATE TABLE Category (
    CategoryID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL
);

-- MEDICINE
CREATE TABLE Medicine (
    MedicineID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Description TEXT,
    Price DECIMAL(10,2) NOT NULL,
    CostPrice DECIMAL(10,2),
    Stock INT DEFAULT 0,
    MinimumStock INT DEFAULT 10,
    Image VARCHAR(255),
    ExpiryDate DATE,
    IsControlled BOOLEAN DEFAULT 0,
    CategoryID INT,
    PharmacistID INT,
    FOREIGN KEY (CategoryID) REFERENCES Category(CategoryID) ON DELETE SET NULL,
    FOREIGN KEY (PharmacistID) REFERENCES Pharmacist(PharmacistID) ON DELETE CASCADE
);

-- ORDERS
CREATE TABLE `Order` (
    OrderID INT AUTO_INCREMENT PRIMARY KEY,
    OrderDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Status ENUM('Pending','Accepted','Rejected','Delivered') DEFAULT 'Pending',
    TotalAmount DECIMAL(10,2),
    PaymentMethod VARCHAR(50) DEFAULT 'COD',
    DeliveryAddress VARCHAR(255),
    DeliveryLatitude DECIMAL(10,8),
    DeliveryLongitude DECIMAL(11,8),
    PatientID INT,
    FOREIGN KEY (PatientID) REFERENCES Patient(PatientID) ON DELETE CASCADE
);

-- ORDER ITEMS
CREATE TABLE OrderItems (
    OrderItemID INT AUTO_INCREMENT PRIMARY KEY,
    OrderID INT,
    MedicineID INT,
    Quantity INT,
    SoldPrice DECIMAL(10,2),
    FOREIGN KEY (OrderID) REFERENCES `Order`(OrderID) ON DELETE CASCADE,
    FOREIGN KEY (MedicineID) REFERENCES Medicine(MedicineID) ON DELETE CASCADE
);

-- CHAT
CREATE TABLE Chat (
    ChatID INT AUTO_INCREMENT PRIMARY KEY,
    SenderID INT,
    ReceiverID INT,
    MessageContent TEXT NOT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (SenderID) REFERENCES User(UserID),
    FOREIGN KEY (ReceiverID) REFERENCES User(UserID)
);

-- PRESCRIPTION
CREATE TABLE Prescription (
    PrescriptionID INT AUTO_INCREMENT PRIMARY KEY,
    ImagePath VARCHAR(255) NOT NULL,
    IsVerified BOOLEAN DEFAULT 0,
    OrderID INT,
    FOREIGN KEY (OrderID) REFERENCES `Order`(OrderID) ON DELETE CASCADE
);