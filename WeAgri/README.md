# WeAgri

WeAgri is a PHP/XAMPP agricultural consultation platform for farmers, consultants, and admins.

It includes:

- AgroLLM-style AI farming assistant
- farmer-to-consultant live chat using polling
- role-based login for farmers, consultants, and admins
- dashboard with weather, soil, market, and query metrics
- notification bell and clickable notification dropdown
- searchable field reference / knowledge base
- feedback, reviews, and admin rating analytics

## Tech Stack

- Frontend: HTML5, CSS, vanilla JavaScript
- Backend: PHP
- Database: MySQL through XAMPP/phpMyAdmin
- Local server: XAMPP Apache
- Storage fallback: `storage/data.json`

## Clean Directory Structure

```text
WeAgri/
  api/                 PHP API endpoints
  api/v1/              dashboard, auth, consultant, chat endpoints
  config/              app and database configuration
  database/            SQL schema files
  docs/                demo and presentation documents
  lib/                 core PHP application classes
  storage/             local JSON fallback data and sessions
  _archive/            old/legacy files moved out of the active app
  index.php            main application entry
  script.js            frontend behavior
  style.css            frontend styling
  README.md            project documentation
```

## Run Locally With XAMPP

1. Put this folder at:
   `C:\Users\User\OneDrive\Documents\XAMPP\htdocs\WeAgri`

2. Start XAMPP.

3. Start:
   - Apache
   - MySQL

4. Open phpMyAdmin:
   `http://localhost/phpmyadmin`

5. Create/import the database using:
   `database/schema.sql`

   Optional dashboard-specific seed/schema:
   `database/dashboard_schema.sql`

6. Check database config:
   `config/database.php`

7. Open the app:
   `http://localhost/WeAgri/index.php`

## Demo Script

The full system walkthrough is here:

`docs/FULL_SYSTEM_DEMO_SCRIPT.md`

Use it to present:

- guest landing
- farmer login
- dashboard
- AgroLLM assistant
- consultant chat
- notifications
- feedback and reviews
- admin rating analytics
- knowledge base
- contact section

## Main User Roles

Farmer:

- asks AgroLLM questions
- starts direct consultant chats
- views dashboard/weather/market data
- submits feedback and ratings

Consultant:

- sees previous farmer conversations
- replies to farmer direct chats
- appears in the farmer consultant directory after registering/logging in as consultant

Admin:

- receives feedback notifications
- views rating distribution from 5 to 1
- reviews farmer feedback trends
- monitors users and platform activity

## Data Behavior

The app prefers MySQL when XAMPP/MySQL is available.

If MySQL is not available, it falls back to:

`storage/data.json`

Weather:

- Uses live forecast data when the local PHP server can reach the external weather endpoint.
- Falls back to sample forecast data if the server cannot connect.

Market prices:

- Attempts official Department of Agriculture price monitoring first.
- Uses MySQL market rows when available.
- Falls back to sample prices only when live/DB data is unavailable.
- The dashboard displays the market source label so users can see whether prices are live, database-backed, or fallback.

## Important Files

- `lib/AgroAssistant.php` - AgroLLM response behavior
- `lib/AgroRagEngine.php` - local retrieval/knowledge support
- `lib/WeAgriDataStore.php` - main data access and business logic
- `api/bootstrap.php` - initial app state
- `api/feedback.php` - platform feedback endpoint
- `api/v1/get_dashboard.php` - dashboard metrics, weather, market data
- `api/v1/consultants.php` - consultant/farmer chat directory
- `api/v1/get_messages.php` - direct chat message history
- `api/v1/send_message.php` - direct chat message sending

## Cleanup Notes

Legacy files were moved to:

`_archive/legacy-cleanup-2026-05-08`

Archived items:

- old redirect file `index (1).html`
- old backend proxy folder `weagri-backend`
- old React/Vite scaffold `WeAgri-main`

These are kept for recovery/reference but are not part of the active PHP app.

