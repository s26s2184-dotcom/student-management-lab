-- student_management_lab : seed data
-- ALL data below is fictional and created for this lab only.
-- Weak/default passwords are intentional (see docs/vulnerabilities.md).

USE student_management_lab;

-- ---------------------------------------------------------------
-- users
-- plain_password column exists ONLY so the vulnerable app can show
-- "weak/default credential" findings without re-deriving them.
-- password_hash is a real bcrypt hash usable by password_verify().
-- ---------------------------------------------------------------
INSERT INTO users (id, username, password_hash, plain_password, role, email, is_active) VALUES
(1, 'admin',   '$2y$10$a8h03/lCEVsSwfXg5rk44u/uDKT6DabcGdwVkCsQesJCflWN62qaK', 'Admin@123',     'admin',   'admin@labuniversity.test', 1),
(2, 'jsmith',  '$2y$10$asyAWvtE.fXLcOy52CuwxOlQ.V8oAGYUiROZBN4Ch5SY6r7bIzxdS', 'password123',   'student', 'jsmith@labuniversity.test', 1),
(3, 'mwong',   '$2y$10$ytLVyWx5ZDIq9lgmAEIG.O0atWRb8tpSsPEoDjt8G/Acaf5kiNBHO', 'letmein',       'student', 'mwong@labuniversity.test', 1),
(4, 'achen',   '$2y$10$Hq06Bwfu.aSjN6QFvq64Su76PNIWL6rmi/ejYqhFonfCwK29Iu4vW', 'qwerty123',     'student', 'achen@labuniversity.test', 1),
(5, 'rpatel',  '$2y$10$fXT7r4MiqvYKgiVfe3d.oeNWIj6kDEuGFO7uLj154lKvbD.EZnPWi', 'Student#2024',  'student', 'rpatel@labuniversity.test', 1),
(6, 'klee',    '$2y$10$/E5HLLCPE7wcIcr2CJJ94u/c7BQPXo5j6jcgzL4tTWGQFpkRKq6OO', 'iloveyou',      'student', 'klee@labuniversity.test', 1),
(7, 'dgarcia', '$2y$10$JYBS8yCFYnpeZ6RjTfpDfubhZTMaBlvskX0dG3/k0bmhAZ9alhwdi', 'password',      'student', 'dgarcia@labuniversity.test', 1),
(8, 'tnguyen', '$2y$10$QhQmbjXhFdN7g/4my14vCu7Yqx5Wr8RF4r0m7cebzOgWTj35Ju7Oa', 'StudentPass1',  'student', 'tnguyen@labuniversity.test', 1);

-- ---------------------------------------------------------------
-- students (linked 1:1 to the student users above)
-- ---------------------------------------------------------------
INSERT INTO students (id, user_id, student_code, full_name, date_of_birth, gender, phone, address, major, enrollment_year, gpa) VALUES
(1, 2, 'STU-1001', 'John Smith',    '2002-03-14', 'Male',   '555-0101', '12 Maple St, Springfield',  'Computer Science',  2021, 3.40),
(2, 3, 'STU-1002', 'Mei Wong',      '2001-11-02', 'Female', '555-0102', '48 Oak Ave, Springfield',   'Information Systems', 2021, 3.75),
(3, 4, 'STU-1003', 'Amy Chen',      '2003-06-21', 'Female', '555-0103', '9 Birch Rd, Rivertown',     'Cybersecurity',     2022, 3.10),
(4, 5, 'STU-1004', 'Raj Patel',     '2002-01-09', 'Male',   '555-0104', '77 Elm St, Rivertown',      'Software Engineering', 2021, 2.95),
(5, 6, 'STU-1005', 'Kevin Lee',     '2003-09-30', 'Male',   '555-0105', '21 Pine St, Lakeside',      'Computer Science',  2022, 3.55),
(6, 7, 'STU-1006', 'Diana Garcia',  '2001-12-18', 'Female', '555-0106', '5 Cedar Ct, Lakeside',      'Data Science',      2020, 3.88),
(7, 8, 'STU-1007', 'Tam Nguyen',    '2002-07-04', 'Non-binary', '555-0107', '14 Willow Way, Hilltop','Information Systems', 2022, 3.22);

-- ---------------------------------------------------------------
-- courses
-- ---------------------------------------------------------------
INSERT INTO courses (id, course_code, course_name, credits, instructor) VALUES
(1, 'CSSY3202', 'Web Application Security',        3, 'Dr. Alan Brooks'),
(2, 'CS2101',   'Data Structures and Algorithms',  4, 'Dr. Linda Park'),
(3, 'CS3303',   'Database Systems',                3, 'Dr. Omar Haddad'),
(4, 'IS2050',   'Information Systems Design',      3, 'Dr. Sofia Rossi'),
(5, 'CS4010',   'Network Security',                3, 'Dr. Alan Brooks'),
(6, 'DS3100',   'Introduction to Data Science',    3, 'Dr. Priya Nair');

-- ---------------------------------------------------------------
-- enrollments
-- ---------------------------------------------------------------
INSERT INTO enrollments (student_id, course_id) VALUES
(1,1),(1,2),(1,3),
(2,1),(2,4),
(3,1),(3,5),
(4,2),(4,3),
(5,1),(5,2),(5,6),
(6,6),(6,3),
(7,4),(7,1);

-- ---------------------------------------------------------------
-- announcements (content left as plain text; stored-XSS lab
-- payloads are inserted at runtime through the vulnerable app,
-- not hardcoded here)
-- ---------------------------------------------------------------
INSERT INTO announcements (id, user_id, title, content) VALUES
(1, 1, 'Welcome to Semester 2026', 'Welcome back everyone! Please check your enrollment and course schedule on the dashboard.'),
(2, 1, 'Security Lab Reminder', 'CSSY3202 lab sessions are held every Tuesday. Bring your laptops.'),
(3, 1, 'Library Hours Updated', 'The library will now close at 9 PM on weekdays and 6 PM on weekends.');

-- ---------------------------------------------------------------
-- comments (a couple of harmless seed comments)
-- ---------------------------------------------------------------
INSERT INTO comments (announcement_id, user_id, content) VALUES
(1, 2, 'Thanks for the update!'),
(1, 3, 'Looking forward to this semester.'),
(2, 4, 'Will the lab be recorded for those who miss it?');

-- ---------------------------------------------------------------
-- audit_logs (sample entries)
-- ---------------------------------------------------------------
INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES
(1, 'login_success', 'Admin logged in', '127.0.0.1'),
(2, 'login_success', 'Student logged in', '127.0.0.1'),
(1, 'create_announcement', 'Created announcement #1', '127.0.0.1');
