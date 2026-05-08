# WeAgri Full System Demonstration Script

Use this as a spoken walkthrough plus click-by-click demo guide.

## Pre-Demo Setup

1. Start XAMPP and make sure Apache and MySQL are running.
2. Open `http://localhost/WeAgri/index.php`.
3. Prepare three browser sessions if possible:
   - Farmer account
   - Consultant account
   - Admin account
4. Keep phpMyAdmin open only if you want to show the database tables.

## 1. Opening

Say:

"This is WeAgri, an agricultural consultation platform built for beginner farmers. The goal is simple: farmers can get immediate AI guidance, talk to real agricultural consultants, check weather and market signals, and leave feedback that helps improve the system."

Point out:

- Clean green and white interface
- Notification bell in the navigation
- Dashboard-first layout
- Ask AI floating button

## 2. Guest Landing Flow

Action:

- Open the site while logged out.
- Show the landing/greeting section.
- Click or point to `Login`, `Sign Up`, and `Ask AI`.

Say:

"Before login, the platform gives a simple entry point. A farmer can log in, create an account, or ask AgroLLM immediately without being forced through a complicated workflow."

## 3. Farmer Login And Dashboard

Action:

- Log in as a farmer.
- Show that the landing section disappears.
- Go to the Dashboard.

Say:

"After login, WeAgri moves directly into the farmer workspace. The dashboard shows open queries, weather, soil moisture, rain chance, AI insights, and current market price information."

Point out:

- Welcome greeting with farmer name
- Key metrics
- Weather calendar
- AI field note
- Market price table
- Market source label

Important note:

"Weather data updates from the forecast API when the server can reach it. Market prices now show their source: official DA source when readable, local MySQL data when available, or clearly marked fallback data if live data cannot be reached."

## 4. AgroLLM AI Assistant

Action:

- Click `Ask AI`.
- Ask: `What are common pests in corn fields?`

Expected result:

- AgroLLM answers directly instead of escalating.

Say:

"For general farming questions, AgroLLM answers immediately. It does not escalate simple educational questions to consultants."

Action:

- Ask: `My tomato has yellow leaves with brown spots.`

Expected result:

- AgroLLM gives likely causes and practical first steps.

Say:

"For common symptoms, AgroLLM gives a practical diagnosis-style response first. It may suggest checking with an expert only when the issue is severe or uncertain."

## 5. Live Consultant Directory

Action:

- Scroll to the Experts or Consult Experts section.
- Show consultant cards.

Say:

"This section no longer depends on fake consultant data. Consultant profiles are loaded from registered consultant accounts in the backend."

Point out:

- Consultant name
- Specialty
- Online/offline status
- Chat button

## 6. Farmer To Consultant Chat

Action:

- From the farmer session, choose a consultant.
- Click `CHAT`.
- Send a message such as: `Good morning, my tomato leaves are yellowing with brown spots. What should I check first?`

Say:

"The farmer can start a direct chat with a real consultant account. Messages are saved through the backend and shown in a live chat panel."

Expected result:

- Farmer's own message appears on the right.
- Consultant replies appear on the left.

## 7. Consultant View

Action:

- Open another browser/session and log in as a consultant.
- Go to the consultant chat section.

Say:

"On the consultant side, the system shows previous farmers they have talked with instead of showing other consultants. This makes the consultant workspace focused on active farmer conversations."

Action:

- Open the farmer conversation.
- Reply: `Please check the lower leaves first, avoid overhead watering, and remove badly infected leaves.`

Expected result:

- Consultant's own message appears on the right for the consultant.
- It appears on the left when viewed by the farmer.

## 8. Notifications

Action:

- Return to the farmer session.
- Show the bell notification.
- Open the dropdown.

Say:

"Notifications are shown in the header bell. The number stays until the user opens or marks the notification as read, so important updates are not lost after logout."

Point out:

- New message notification
- Clickable notification dropdown
- Notification links take the user to the relevant area when possible

## 9. Feedback And Reviews

Action:

- Scroll to Feedback.
- Show the reviews section above the feedback form.
- Submit a rating and comment.

Say:

"Farmers can rate the advice and leave comments. The latest three reviews are shown first, with a See more button for additional reviews."

Expected result:

- Review appears in the reviews section.
- Confirmation message appears after submission.

## 10. Admin Feedback Analytics

Action:

- Log in as admin.
- Open the notification bell.
- Show the new feedback notification.
- Go to Feedback.

Say:

"Admins receive a notification when a farmer submits feedback. Admins also get a rating scale breakdown from 5 to 1, which helps identify satisfaction trends and improvement areas."

Point out:

- Admin-only rating scale
- Total review count
- Review list
- Feedback analytics in admin area

## 11. Knowledge Base

Action:

- Go to Knowledge.
- Search for a topic such as `soil`, `pest`, or `tomato`.

Say:

"The knowledge base works as a searchable field reference. It supports AgroLLM and also gives farmers and advisors quick access to practical agricultural guidance."

## 12. Contact Section

Action:

- Scroll to Contact.
- Show company-style email and phone details.

Say:

"The contact section gives farmers a realistic support channel if they need help outside the AI or consultant chat."

## 13. Closing

Say:

"To summarize, WeAgri reduces unnecessary consultant workload by letting AgroLLM answer basic and beginner questions immediately. It escalates through real human conversations when needed, keeps farmers updated through notifications, and collects feedback so admins can improve both consultant quality and AI knowledge over time."

## Demo Backup Lines

Use these if live data is unavailable:

- "The system is designed to use live weather and official market sources when the local server can reach them. In this demo environment, if live access fails, WeAgri clearly labels the data as local database or fallback instead of pretending it is live."
- "The important part is that the dashboard now exposes the data source, so users know whether they are seeing official, database, or fallback information."

