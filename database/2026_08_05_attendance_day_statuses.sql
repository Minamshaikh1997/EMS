ALTER TABLE attendance
    MODIFY status ENUM('Present','Absent','Late','Half Day','Early Out','Off Day','NH') DEFAULT 'Present';
