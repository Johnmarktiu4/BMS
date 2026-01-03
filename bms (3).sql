
--
-- Database: `bms`
--

-- --------------------------------------------------------

--
-- Table structure for table `blotters`
--

CREATE TABLE `blotters` (
  `id` int(11) NOT NULL,
  `case_id` varchar(50) NOT NULL,
  `complainant_id` int(11) NOT NULL,
  `defendant_first_name` varchar(100) NOT NULL,
  `defendant_middle_name` varchar(100) DEFAULT NULL,
  `defendant_last_name` varchar(100) NOT NULL,
  `defendant_contact` varchar(20) NOT NULL,
  `defendant_sex` enum('Male','Female','Rather not to say') NOT NULL,
  `defendant_address` varchar(500) NOT NULL,
  `nature_of_complaint` varchar(255) NOT NULL,
  `barangay_official_id` int(11) NOT NULL,
  `details` text NOT NULL,
  `date_filed` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `borrowers`
--

CREATE TABLE `borrowers` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `case_reports`
--

CREATE TABLE `case_reports` (
  `id` int(11) NOT NULL,
  `case_number` varchar(50) NOT NULL,
  `complainant` varchar(255) NOT NULL,
  `respondent` varchar(255) NOT NULL,
  `case_type` enum('Dispute','Complaint','Incident') NOT NULL,
  `date_filed` date NOT NULL,
  `status` enum('Pending','Resolved','Dismissed') NOT NULL DEFAULT 'Pending',
  `description` text NOT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(11) NOT NULL,
  `case_id` varchar(10) NOT NULL,
  `defendant_first_name` varchar(50) NOT NULL,
  `defendant_middle_name` varchar(50) DEFAULT NULL,
  `defendant_last_name` varchar(50) NOT NULL,
  `defendant_contact` varchar(20) NOT NULL,
  `defendant_sex` enum('Male','Female','Rather not to say') NOT NULL,
  `defendant_address` text NOT NULL,
  `nature_of_complaint` varchar(255) NOT NULL,
  `barangay_official_id` int(11) NOT NULL,
  `details` text NOT NULL,
  `date_of_hearing` date NOT NULL,
  `status` enum('Pending','Settled','Submitted to Police Station') NOT NULL,
  `date_settled` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `complaint_complainants`
--

CREATE TABLE `complaint_complainants` (
  `id` int(11) NOT NULL,
  `complaint_id` int(11) NOT NULL,
  `complainant_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `id` int(11) NOT NULL,
  `case_id` varchar(50) NOT NULL,
  `complainant_id` int(11) NOT NULL,
  `defendant_first_name` varchar(100) NOT NULL,
  `defendant_middle_name` varchar(100) DEFAULT NULL,
  `defendant_last_name` varchar(100) NOT NULL,
  `defendant_contact` varchar(20) NOT NULL,
  `defendant_sex` enum('Male','Female','Rather not to say') NOT NULL,
  `defendant_address` varchar(500) NOT NULL,
  `nature_of_incident` varchar(255) NOT NULL,
  `barangay_official_id` int(11) NOT NULL,
  `details` text NOT NULL,
  `date_filed` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `qty_on_hand` int(11) NOT NULL DEFAULT 0,
  `qty_received` int(11) NOT NULL DEFAULT 0,
  `qty_lost` int(11) NOT NULL DEFAULT 0,
  `qty_damaged` int(11) NOT NULL DEFAULT 0,
  `qty_replaced` int(11) NOT NULL DEFAULT 0,
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `status` enum('In Stock','Out of Stock') NOT NULL DEFAULT 'In Stock',
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `action_type` enum('Borrow','Return','Broken','Add','Replace') NOT NULL,
  `quantity` int(11) NOT NULL,
  `transacted_by` varchar(255) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `borrower_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `officials`
--

CREATE TABLE `officials` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `position` enum('Barangay Captain','Kagawad','Secretary','Treasurer','Other') NOT NULL,
  `duty_hours` varchar(100) NOT NULL,
  
  `contact` varchar(20) NOT NULL,
   `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `residents`
--

CREATE TABLE `residents` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(50) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `civil_status` enum('Single','Married','Divorced','Widowed','Separated') NOT NULL,
  `sex` enum('Male','Female','Rather not to say') NOT NULL,
  `date_of_birth` date NOT NULL,
  `age` int(11) NOT NULL,
  `place_of_birth` varchar(255) NOT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT 'Filipino',
  `house_number` varchar(50) NOT NULL,
  `street` varchar(255) NOT NULL,
  `province` varchar(100) NOT NULL DEFAULT 'Cavite',
  `municipality` varchar(100) NOT NULL DEFAULT 'Cavite City',
  `zip_code` varchar(10) NOT NULL DEFAULT '4100',
  `address` varchar(500) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `pwd_senior` enum('Yes','No') NOT NULL DEFAULT 'No',
  `pwd_senior_id` varchar(100) DEFAULT NULL,
  `solo_parent` enum('Yes','No') NOT NULL DEFAULT 'No',
  `is_head_of_family` tinyint(1) NOT NULL DEFAULT 0,
  `head_of_family_id` int(11) DEFAULT NULL,
  `emergency_name` varchar(255) DEFAULT NULL,
  `emergency_relationship` varchar(100) DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `registered` tinyint(1) NOT NULL DEFAULT 1,
  `profile_picture` varchar(255) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pwd` enum('Yes','No') NOT NULL DEFAULT 'No',
  `pwd_id` varchar(50) DEFAULT NULL,
  `disability_type` varchar(100) DEFAULT NULL,
  `senior` enum('Yes','No') NOT NULL DEFAULT 'No',
  `senior_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `residents`
--

INSERT INTO `residents` (`id`, `first_name`, `last_name`, `middle_name`, `suffix`, `full_name`, `civil_status`, `sex`, `date_of_birth`, `age`, `place_of_birth`, `religion`, `nationality`, `house_number`, `street`, `province`, `municipality`, `zip_code`, `address`, `contact_number`, `email_address`, `pwd_senior`, `pwd_senior_id`, `solo_parent`, `is_head_of_family`, `head_of_family_id`, `emergency_name`, `emergency_relationship`, `emergency_contact`, `registered`, `profile_picture`, `archived`, `created_at`, `updated_at`, `pwd`, `pwd_id`, `disability_type`, `senior`, `senior_id`) VALUES
(1, 'Juan', 'Dela Cruz', 'Santos', NULL, 'Juan Santos Dela Cruz', 'Married', 'Male', '1980-05-10', 45, 'Cavite City', 'Catholic', 'Filipino', '123', 'Burgos St.', 'Cavite', 'Cavite City', '4100', '123 Burgos St., Cavite City', '09171234567', 'juan.dc@example.com', 'No', NULL, 'No', 1, NULL, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:04', '2025-10-23 04:06:04', 'No', NULL, NULL, 'No', NULL),
(2, 'Maria', 'Lopez', 'Reyes', NULL, 'Maria Reyes Lopez', 'Married', 'Female', '1978-09-20', 47, 'Cavite City', 'Catholic', 'Filipino', '45', 'Magallanes St.', 'Cavite', 'Cavite City', '4100', '45 Magallanes St., Cavite City', '09181234568', 'maria.lopez@example.com', 'No', NULL, 'No', 1, NULL, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:04', '2025-10-23 04:06:04', 'No', NULL, NULL, 'No', NULL),
(3, 'Pedro', 'Garcia', 'Mendoza', NULL, 'Pedro Mendoza Garcia', 'Married', 'Male', '1985-02-14', 40, 'Cavite City', 'Iglesia ni Cristo', 'Filipino', '78', 'Rizal Ave.', 'Cavite', 'Cavite City', '4100', '78 Rizal Ave., Cavite City', '09191234569', 'pedro.garcia@example.com', 'No', NULL, 'No', 1, NULL, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:04', '2025-10-23 04:06:04', 'No', NULL, NULL, 'No', NULL),
(4, 'Rosa', 'Torres', 'Delos Santos', NULL, 'Rosa Delos Santos Torres', 'Widowed', 'Female', '1975-03-12', 50, 'Cavite City', 'Catholic', 'Filipino', '90', 'Bonifacio St.', 'Cavite', 'Cavite City', '4100', '90 Bonifacio St., Cavite City', '09201234570', 'rosa.torres@example.com', 'No', NULL, 'Yes', 1, NULL, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:04', '2025-10-23 04:06:04', 'No', NULL, NULL, 'No', NULL),
(5, 'Carlos', 'Navarro', 'Gutierrez', NULL, 'Carlos Gutierrez Navarro', 'Married', 'Male', '1979-11-25', 45, 'Cavite City', 'Born Again', 'Filipino', '33', 'Aguinaldo Blvd.', 'Cavite', 'Cavite City', '4100', '33 Aguinaldo Blvd., Cavite City', '09221234571', 'carlos.navarro@example.com', 'No', NULL, 'No', 1, NULL, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:04', '2025-10-23 04:06:04', 'No', NULL, NULL, 'No', NULL),
(6, 'Ana', 'Dela Cruz', 'Reyes', NULL, 'Ana Reyes Dela Cruz', 'Married', 'Female', '1982-08-15', 43, 'Cavite City', 'Catholic', 'Filipino', '123', 'Burgos St.', 'Cavite', 'Cavite City', '4100', '123 Burgos St., Cavite City', '09170001111', NULL, 'No', NULL, 'No', 0, 1, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:05', '2025-10-23 04:06:05', 'No', NULL, NULL, 'No', NULL),
(7, 'Jose', 'Dela Cruz', NULL, NULL, 'Jose Dela Cruz', 'Single', 'Male', '2005-06-12', 20, 'Cavite City', 'Catholic', 'Filipino', '123', 'Burgos St.', 'Cavite', 'Cavite City', '4100', '123 Burgos St., Cavite City', '09170002222', NULL, 'No', NULL, 'No', 0, 1, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:05', '2025-10-23 04:06:05', 'No', NULL, NULL, 'No', NULL),
(8, 'Liza', 'Dela Cruz', NULL, NULL, 'Liza Dela Cruz', 'Single', 'Female', '2008-09-30', 17, 'Cavite City', 'Catholic', 'Filipino', '123', 'Burgos St.', 'Cavite', 'Cavite City', '4100', '123 Burgos St., Cavite City', '09170003333', NULL, 'No', NULL, 'No', 0, 1, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:05', '2025-10-23 04:06:05', 'No', NULL, NULL, 'No', NULL),
(9, 'Jose', 'Lopez', 'Martinez', NULL, 'Jose Martinez Lopez', 'Married', 'Male', '1976-07-01', 49, 'Cavite City', 'Catholic', 'Filipino', '45', 'Magallanes St.', 'Cavite', 'Cavite City', '4100', '45 Magallanes St., Cavite City', '09170004444', NULL, 'No', NULL, 'No', 0, 2, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:05', '2025-10-23 04:06:05', 'No', NULL, NULL, 'No', NULL),
(10, 'Angela', 'Lopez', NULL, NULL, 'Angela Lopez', 'Single', 'Female', '2003-04-11', 22, 'Cavite City', 'Catholic', 'Filipino', '45', 'Magallanes St.', 'Cavite', 'Cavite City', '4100', '45 Magallanes St., Cavite City', '09170005555', NULL, 'No', NULL, 'No', 0, 2, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:05', '2025-10-23 04:06:05', 'No', NULL, NULL, 'No', NULL),
(11, 'Miguel', 'Lopez', NULL, NULL, 'Miguel Lopez', 'Single', 'Male', '2007-12-05', 17, 'Cavite City', 'Catholic', 'Filipino', '45', 'Magallanes St.', 'Cavite', 'Cavite City', '4100', '45 Magallanes St., Cavite City', '09170006666', NULL, 'No', NULL, 'No', 0, 2, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:05', '2025-10-23 04:06:05', 'No', NULL, NULL, 'No', NULL),
(12, 'Lucia', 'Garcia', 'Ramos', NULL, 'Lucia Ramos Garcia', 'Married', 'Female', '1987-03-10', 38, 'Cavite City', 'Iglesia ni Cristo', 'Filipino', '78', 'Rizal Ave.', 'Cavite', 'Cavite City', '4100', '78 Rizal Ave., Cavite City', '09170007777', NULL, 'No', NULL, 'No', 0, 3, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:05', '2025-10-23 04:06:05', 'No', NULL, NULL, 'No', NULL),
(13, 'Mark', 'Garcia', NULL, NULL, 'Mark Garcia', 'Single', 'Male', '2010-09-25', 15, 'Cavite City', 'Iglesia ni Cristo', 'Filipino', '78', 'Rizal Ave.', 'Cavite', 'Cavite City', '4100', '78 Rizal Ave., Cavite City', '09170008888', NULL, 'No', NULL, 'No', 0, 3, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:05', '2025-10-23 04:06:05', 'No', NULL, NULL, 'No', NULL),
(14, 'Ella', 'Garcia', NULL, NULL, 'Ella Garcia', 'Single', 'Female', '2012-11-05', 13, 'Cavite City', 'Iglesia ni Cristo', 'Filipino', '78', 'Rizal Ave.', 'Cavite', 'Cavite City', '4100', '78 Rizal Ave., Cavite City', '09170009999', NULL, 'No', NULL, 'No', 0, 3, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:05', '2025-10-23 04:06:05', 'No', NULL, NULL, 'No', NULL),
(15, 'Jun', 'Torres', NULL, NULL, 'Jun Torres', 'Single', 'Male', '2001-05-22', 24, 'Cavite City', 'Catholic', 'Filipino', '90', 'Bonifacio St.', 'Cavite', 'Cavite City', '4100', '90 Bonifacio St., Cavite City', '09170010010', NULL, 'No', NULL, 'No', 0, 4, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:05', '2025-10-23 04:06:05', 'No', NULL, NULL, 'No', NULL),
(16, 'Joy', 'Torres', NULL, NULL, 'Joy Torres', 'Single', 'Female', '2004-09-30', 21, 'Cavite City', 'Catholic', 'Filipino', '90', 'Bonifacio St.', 'Cavite', 'Cavite City', '4100', '90 Bonifacio St., Cavite City', '09170011011', NULL, 'No', NULL, 'No', 0, 4, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:05', '2025-10-23 04:06:05', 'No', NULL, NULL, 'No', NULL),
(17, 'Rico', 'Torres', NULL, NULL, 'Rico Torres', 'Single', 'Male', '2006-02-14', 19, 'Cavite City', 'Catholic', 'Filipino', '90', 'Bonifacio St.', 'Cavite', 'Cavite City', '4100', '90 Bonifacio St., Cavite City', '09170012012', NULL, 'No', NULL, 'No', 0, 4, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:05', '2025-10-23 04:06:05', 'No', NULL, NULL, 'No', NULL),
(18, 'Elena', 'Navarro', 'Dizon', NULL, 'Elena Dizon Navarro', 'Married', 'Female', '1981-04-12', 44, 'Cavite City', 'Born Again', 'Filipino', '33', 'Aguinaldo Blvd.', 'Cavite', 'Cavite City', '4100', '33 Aguinaldo Blvd., Cavite City', '09170013013', NULL, 'No', NULL, 'No', 0, 5, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:05', '2025-10-23 04:06:05', 'No', NULL, NULL, 'No', NULL),
(19, 'Patrick', 'Navarro', NULL, NULL, 'Patrick Navarro', 'Single', 'Male', '2009-10-19', 16, 'Cavite City', 'Born Again', 'Filipino', '33', 'Aguinaldo Blvd.', 'Cavite', 'Cavite City', '4100', '33 Aguinaldo Blvd., Cavite City', '09170014014', NULL, 'No', NULL, 'No', 0, 5, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:05', '2025-10-23 04:06:05', 'No', NULL, NULL, 'No', NULL),
(20, 'Hannah', 'Navarro', NULL, NULL, 'Hannah Navarro', 'Single', 'Female', '2012-03-02', 13, 'Cavite City', 'Born Again', 'Filipino', '33', 'Aguinaldo Blvd.', 'Cavite', 'Cavite City', '4100', '33 Aguinaldo Blvd., Cavite City', '09170015015', NULL, 'No', NULL, 'No', 0, 5, NULL, NULL, NULL, 1, NULL, 0, '2025-10-23 04:06:05', '2025-10-23 04:06:05', 'No', NULL, NULL, 'No', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vawc_reports`
--

CREATE TABLE `vawc_reports` (
  `id` int(11) NOT NULL,
  `victim_name` varchar(255) NOT NULL,
  `victim_dob` date NOT NULL,
  `victim_age` int(11) NOT NULL,
  `victim_address` text NOT NULL,
  `victim_contact` varchar(20) NOT NULL,
  `relationship_to_abuser` varchar(100) NOT NULL,
  `abuser_name` varchar(255) NOT NULL,
  `abuser_is_resident` enum('Yes','No') NOT NULL,
  `abuser_address` text NOT NULL,
  `incident_date` date NOT NULL,
  `incident_time` time NOT NULL,
  `incident_place` varchar(255) NOT NULL,
  `incident_description` text NOT NULL,
  `witnesses_evidence` text DEFAULT NULL,
  `status` enum('Pending','Reported to DILG') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




ALTER TABLE user_roles_official_accounts
ADD COLUMN sec_color      VARCHAR(100) DEFAULT '',
ADD COLUMN sec_animal     VARCHAR(100) DEFAULT '',
ADD COLUMN sec_bestfriend VARCHAR(100) DEFAULT '',
ADD COLUMN sec_food       VARCHAR(100) DEFAULT '',
ADD COLUMN sec_drink      VARCHAR(100) DEFAULT '';