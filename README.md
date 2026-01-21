# Group 8 
  *names*                  *id*
    julas mohammednur id ugr/189777/16
    mekbb leul id ugr/188422/16
    netsanet haile ugr/188571/16
    tsgabu measho ugr/188767/16
    faniel negasi ugr/188099/16
# JobLaunch - Recruitment Portal Prototype

JobLaunch is a job portal application developed by Computer Science students at Mekelle University. It connects job seekers with employers, allowing companies to post job openings and candidates to apply for them seamlessly.

Current Version: **Core PHP Prototype** (Transitioning to Full Stack)

## Features

- **For Job Seekers:**
  - Browse and search for jobs by title or keyword.
  - View detailed job descriptions.
  - Apply for jobs with a cover letter.
  - Track application status via the dashboard.
  - Manage personal profile and skills.

- **For Employers (Companies):**
  - Post new job openings.
  - Manage job listings (Edit/Settings).
  - View applicant profiles and cover letters.
  - Update application status (Pending, Reviewed, Accepted, Rejected).
  - View platform statistics (Total Applicants, Jobs Posted).

## 🚀 Instructor Guide: How to Navigate

To test the application properly, please use the following pre-configured credentials representing different user roles.

### 1. Job Seeker Account
Log in here to test the "Apply for Job" flow.
- **Login Page:** [Login Here](login.html)
- **Role Selection:** Select "Job Seeker" if prompted (though the system auto-detects based on email).
- **Username:** 	tsgabu123@gmail.com
- **Password:** 	tsgabuis123

**Actions to test:**
1. Log in.
2. Go to Home page.
3. Click on a Job Card.
4. Click "Apply Now".
5. Check My Applications in the dashboard to see the status.

### 2. Employer (Company) Accounts
Log in here to test the "Post Job" and "Review Application" flows.

**Company A (Julas)**
- **Username:** julas1234@gmail.com
- **Password:** julasis123

**Company B (Faniel)**
- **Username:** fanielnegasi1234@gmail.com
- **Password:** fanielnewpass

**Actions to test:**
1. Log in as a Company.
2. Click "Post a Job" from the sidebar.
3. Fill out the form to create a new listing.
4. Go to "My Postings" to see your active jobs.
5. Go to "View Applications" to accept/reject candidates.

---

## 🛠️ Installation & Setup

1. **Environment Requirements:**
   - PHP 7.4 or higher.
   - MySQL Database.
   - A local server environment like XAMPP, WAMP, or Laragon.

2. **Database Setup:**
   - Open PHPMyAdmin (or your preferred SQL tool).
   - Create a database named job_launch.
   - Import the schema file located at: database_schema/job_launch.sql (or backendwithphp/databasescema.sql if available).

3. **Configuration:**
   - Verify database credentials in backendwithphp/db_conection.php. Default is usually:
     `php
     \ = "localhost";
     \ = "root";
     \ = "";
     \ = "job_launch";
     `

4. **Running the App:**
   - Place the project folder in htdocs (XAMPP) or www (WAMP).
   - Open your browser and navigate to: http://localhost/job-web(prototype)/index.php

## Project Structure

- **/ Root:** Contains public pages (index.php, about.php, contact.php).
- **backendwithphp/:** Handles all server-side logic (Auth, DB Connections, CRUD operations).
- **Users/:** Contains user-specific dashboards and protected pages (seeker_dashboard.php, employer_dashboard.php).
- **assets/ & style.css:** Frontend styling and scripts. Responsive design included for mobile devices (iPhone 14 optimized).

## Tech Stack
- **Frontend:** HTML5, CSS3 (Responsive), JavaScript.
- **Backend:** Core PHP.
- **Database:** MySQL.

---
*Developed by Computer Science Students, Mekelle University.*
