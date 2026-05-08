<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

$initialState = weagri_bootstrap_state();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeAgri</title>
    <meta
        name="description"
        content="WeAgri is a simple consultation workspace for farmers, consultants, and administrators."
    >
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=20260506-feedback-market-source">
</head>
<body>
    <header class="site-header" id="home">
        <div class="container nav-row">
            <a class="brand" href="#home">
                <span class="brand-mark">WA</span>
                <span class="brand-copy">
                    <strong>WeAgri</strong>
                    <small>Consultation workspace</small>
                </span>
            </a>

            <nav class="site-nav" id="site-nav">
                <a href="#dashboard">Dashboard</a>
                <a href="#experts">Experts</a>
                <a href="#knowledge">Knowledge</a>
                <a href="#feedback">Feedback</a>
                <a href="#contact">Contact</a>
                <button type="button" class="button button-secondary nav-cta" data-open-assistant>
                    Ask AI
                </button>
                <div class="nav-user-shell">
                    <span class="source-pill" id="nav-user-chip">Guest</span>
                    <button type="button" class="text-button is-hidden" id="nav-logout-button">Logout</button>
                </div>
            </nav>

            <div class="header-actions">
                <div class="nav-notification-shell" id="notification-shell">
                    <button
                        class="nav-notification-button"
                        id="notification-bell"
                        type="button"
                        aria-expanded="false"
                        aria-controls="notification-dropdown"
                        aria-label="Open notifications"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M12 3a4 4 0 0 0-4 4v2.2c0 .8-.2 1.6-.6 2.3L6 14.2V16h12v-1.8l-1.4-2.7a5.1 5.1 0 0 1-.6-2.3V7a4 4 0 0 0-4-4Zm0 18a2.5 2.5 0 0 0 2.4-2h-4.8A2.5 2.5 0 0 0 12 21Z"></path>
                        </svg>
                        <span class="notification-badge" id="notification-badge">0</span>
                    </button>

                    <div class="notification-dropdown" id="notification-dropdown" hidden>
                        <div class="notification-dropdown-header">
                            <div>
                                <span class="panel-kicker">Notifications</span>
                                <h3>Recent updates</h3>
                            </div>
                        </div>
                        <div class="notification-dropdown-list" id="notification-list"></div>
                    </div>
                </div>

                <button class="nav-toggle" id="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
                    Menu
                </button>
            </div>
        </div>
    </header>

    <main>
        <section class="hero section landing-section" id="landing">
            <div class="container hero-grid">
                <div class="hero-copy reveal">
                    <span class="eyebrow">Welcome to WeAgri</span>
                    <h1>Practical farm help, without the confusion.</h1>
                    <p class="hero-text">
                        Ask AgroLLM for quick guidance, review important updates, and talk to agricultural experts when your crop needs a closer look.
                    </p>

                    <div class="hero-actions">
                        <button type="button" class="button" data-auth-view="login">Login</button>
                        <button type="button" class="button button-secondary" data-auth-view="register">Sign Up</button>
                        <button type="button" class="button button-secondary" data-open-assistant>
                            Ask AI
                        </button>
                    </div>
                </div>

                <aside class="hero-panel reveal">
                    <div class="landing-auth-grid">
                        <section class="panel auth-summary-panel">
                            <div class="panel-heading-row">
                                <div>
                                    <span class="panel-kicker">Account access</span>
                                    <h2 id="auth-summary-title">Continue as guest</h2>
                                </div>
                                <span class="count-pill" id="auth-role-badge">Guest</span>
                            </div>

                            <p class="hero-text" id="auth-summary-copy">
                                Log in to create consultations, respond as a consultant, or manage assignments as an administrator.
                            </p>

                            <div class="profile-card" id="auth-profile-grid">
                                <div class="profile-avatar" id="auth-initials">G</div>
                                <div class="profile-details">
                                    <div class="profile-row">
                                        <span>Name</span>
                                        <strong id="auth-name">Guest user</strong>
                                    </div>
                                    <div class="profile-row">
                                        <span>Email</span>
                                        <strong id="auth-email">Not signed in</strong>
                                    </div>
                                    <div class="profile-row">
                                        <span>Profile</span>
                                        <strong id="auth-role-detail">Public access</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="stack-list is-hidden" id="auth-capabilities"></div>

                            <button type="button" class="button is-hidden" id="logout-button">Log out</button>
                        </section>

                        <section class="panel auth-form-panel">
                            <div class="auth-switch">
                                <button type="button" class="text-button is-active" id="show-login">Log in</button>
                                <button type="button" class="text-button" id="show-register">Sign up</button>
                            </div>

                            <form id="login-form" class="auth-form">
                                <label class="field">
                                    <span>Email</span>
                                    <input type="email" id="login-email" placeholder="email@example.com">
                                </label>
                                <label class="field">
                                    <span>Password</span>
                                    <input type="password" id="login-password" placeholder="Enter your password">
                                </label>
                                <button class="button" id="login-submit" type="submit">Log in</button>
                            </form>

                            <form id="register-form" class="auth-form is-hidden">
                                <div class="field-row">
                                    <label class="field">
                                        <span>Full name</span>
                                        <input type="text" id="register-name" placeholder="Your full name">
                                    </label>
                                    <label class="field">
                                        <span>Role</span>
                                        <select id="register-role">
                                            <option value="farmer">Farmer</option>
                                            <option value="consultant">Consultant</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </label>
                                </div>

                                <div class="field-row">
                                    <label class="field">
                                        <span>Email</span>
                                        <input type="email" id="register-email" placeholder="email@example.com">
                                    </label>
                                    <label class="field">
                                        <span>Password</span>
                                        <input type="password" id="register-password" placeholder="At least 6 characters">
                                    </label>
                                </div>

                                <div id="register-farmer-fields">
                                    <div class="field-row">
                                        <label class="field">
                                            <span>Farm location</span>
                                            <input type="text" id="register-location" placeholder="Municipality, province, or sitio">
                                        </label>
                                        <label class="field">
                                            <span>Primary crop</span>
                                            <input type="text" id="register-primary-crop" placeholder="Rice, Corn, Tomato, etc.">
                                        </label>
                                    </div>
                                    <div class="field-row">
                                        <label class="field">
                                            <span>Soil type</span>
                                            <input type="text" id="register-soil-type" placeholder="Clay, sandy, loam, volcanic, etc.">
                                        </label>
                                        <label class="field">
                                            <span>Farm scale</span>
                                            <select id="register-farm-scale">
                                                <option value="smallholder">Smallholder</option>
                                                <option value="commercial">Commercial</option>
                                                <option value="backyard">Backyard</option>
                                                <option value="cooperative">Cooperative</option>
                                            </select>
                                        </label>
                                    </div>
                                    <label class="field">
                                        <span>Common pests or diseases</span>
                                        <input type="text" id="register-common-issues" placeholder="Armyworms, blight, snails, weeds, etc.">
                                    </label>
                                </div>

                                <div id="register-consultant-fields" class="is-hidden">
                                    <div class="field-row">
                                        <label class="field">
                                            <span>Specialty</span>
                                            <input type="text" id="register-specialty" placeholder="Pest Management, Soil Health, etc.">
                                        </label>
                                        <label class="field">
                                            <span>Bio</span>
                                            <input type="text" id="register-bio" placeholder="Short description of expertise">
                                        </label>
                                    </div>
                                </div>

                                <button class="button" id="register-submit" type="submit">Create account</button>
                            </form>
                        </section>
                    </div>
                </aside>
            </div>
        </section>

        <section class="section section-soft dashboard-section" id="dashboard">
            <div class="container dashboard-shell">
                <section class="panel dashboard-greeting-card reveal">
                    <span class="eyebrow">Welcome to WeAgri</span>
                    <h2 id="dashboard-greeting-title">Hello, Farmer.</h2>
                    <p>
                        Here are your daily field insights, important updates, and market movement in one calm workspace.
                    </p>
                    <div class="dashboard-quick-actions">
                        <a class="button" href="#experts">Start Consultation</a>
                        <button type="button" class="button button-secondary" data-open-assistant>Ask AI</button>
                        <a class="button button-secondary" href="#knowledge">View Knowledge</a>
                    </div>
                </section>

                <section class="dashboard-block reveal" aria-label="Key metrics">
                    <div class="dashboard-section-title">
                        <span class="panel-kicker">Dashboard</span>
                        <h2>Key Metrics</h2>
                    </div>
                    <div class="dashboard-metric-grid">
                        <article class="dashboard-metric-card">
                            <span>Open Queries</span>
                            <strong id="dashboard-active-metric">0</strong>
                            <p>Farmer questions needing attention</p>
                        </article>
                        <article class="dashboard-metric-card">
                            <span>Weather</span>
                            <strong id="dashboard-weather-metric">-- C</strong>
                            <p>Current temperature from your farm area</p>
                        </article>
                        <article class="dashboard-metric-card">
                            <span>Soil moisture</span>
                            <strong id="dashboard-soil-metric">--%</strong>
                            <p>Surface soil estimate for today</p>
                        </article>
                        <article class="dashboard-metric-card">
                            <span>Rain chance</span>
                            <strong id="dashboard-health-metric">--%</strong>
                            <p>Chance of rainfall for today</p>
                        </article>
                    </div>
                </section>

                <section class="dashboard-block reveal" aria-label="Weather forecast">
                    <div class="dashboard-section-title">
                        <span class="panel-kicker">Forecast</span>
                        <h2>Weather Calendar</h2>
                    </div>
                    <article class="panel dashboard-weather-panel">
                        <div class="dashboard-weather-panel-head">
                            <div>
                                <span class="panel-kicker">Daily outlook</span>
                                <h3>Seven-day field forecast</h3>
                            </div>
                            <p class="dashboard-weather-meta" id="dashboard-weather-meta">
                                Forecast loading...
                            </p>
                        </div>
                        <div class="dashboard-weather-calendar" id="dashboard-weather-calendar">
                            <article class="empty-state">Forecast loading...</article>
                        </div>
                    </article>
                </section>

                <section class="dashboard-insight-grid reveal" aria-label="AI insights and market data">
                    <article class="panel ai-insight-panel">
                        <span class="panel-kicker">AI Insights</span>
                        <h3>Today&apos;s field note</h3>
                        <p id="dashboard-ai-insight">
                            Waiting for dashboard data. AgroLLM will summarize the main action to watch.
                        </p>
                    </article>

                    <article class="panel market-data-panel">
                        <div class="panel-heading-row">
                            <div>
                                <span class="panel-kicker">Market data</span>
                                <h3>Current prices</h3>
                                <p class="market-source-note" id="dashboard-market-source">
                                    Market source loading...
                                </p>
                            </div>
                        </div>
                        <div class="market-table-wrap">
                            <table class="market-table">
                                <thead>
                                    <tr>
                                        <th>Crop</th>
                                        <th>Price / kg</th>
                                        <th>Trend</th>
                                    </tr>
                                </thead>
                                <tbody id="dashboard-market-table">
                                    <tr>
                                        <td colspan="3">Market prices loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </article>
                </section>

            </div>
        </section>

        <section class="section consultant-section" id="experts">
            <div class="container consultant-shell">
                <div class="section-heading reveal">
                    <div>
                        <span class="eyebrow">Consult Experts</span>
                        <h2 id="consultant-section-title">Talk to a human agricultural consultant</h2>
                    </div>
                    <p id="consultant-section-copy">
                        Choose an adviser by specialty and start a direct chat for field concerns that need human judgment.
                    </p>
                </div>

                <div class="consultant-live-layout">
                    <section class="consultant-directory reveal" id="consultant-directory" aria-label="Consultant directory">
                        <article class="empty-state">Consultants loading...</article>
                    </section>

                    <section class="panel consultant-chat-panel reveal" id="consultant-chat-panel" aria-label="Human consultant chat">
                        <div class="consultant-chat-header">
                            <div class="consultant-avatar" id="consultant-chat-avatar">WA</div>
                            <div>
                                <span class="panel-kicker" id="consultant-chat-status">Select an expert</span>
                                <h3 id="consultant-chat-name">Human consultant chat</h3>
                            </div>
                        </div>

                        <div class="consultant-message-thread" id="consultant-message-thread">
                            <article class="empty-state">Choose a consultant card to begin a direct chat.</article>
                        </div>

                        <form class="consultant-chat-form" id="consultant-chat-form">
                            <input id="consultant-chat-input" type="text" placeholder="Type your message to the consultant">
                            <button class="button" id="consultant-chat-send" type="submit">Send</button>
                        </form>
                    </section>
                </div>
            </div>
        </section>

        <section class="section legacy-consultations-shell" id="consultations" hidden aria-hidden="true">
            <div class="container">
                <div class="section-heading reveal">
                    <div>
                        <span class="eyebrow">Workspace</span>
                        <h2>Consultations</h2>
                    </div>
                    <p id="workspace-copy">
                        Create a case, open the thread, and manage the next action.
                    </p>
                </div>

                <div class="workspace-grid">
                    <form class="panel form-panel reveal" id="consultation-form">
                        <div class="panel-heading-row">
                            <div>
                                <span class="panel-kicker">New case</span>
                                <h3>Create consultation</h3>
                            </div>
                        </div>

                        <p class="field-hint" id="consultation-access-note">
                            Sign in as a farmer to create a consultation.
                        </p>

                        <label class="field">
                            <span>Concern title</span>
                            <input id="title-input" name="title" type="text" placeholder="Example: Rice leaves with spreading spots">
                        </label>

                        <div class="field-row">
                            <label class="field">
                                <span>Crop</span>
                                <input id="crop-input" name="crop" type="text" placeholder="Rice, Corn, Tomato, Pechay">
                            </label>
                            <label class="field">
                                <span>Urgency</span>
                                <select id="urgency-input" name="urgency">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </label>
                        </div>

                        <label class="field">
                            <span>Farm location</span>
                            <input id="location-input" name="location" type="text" placeholder="Municipality, province, or sitio">
                        </label>

                        <label class="field">
                            <span>Describe the issue</span>
                            <textarea id="concern-input" name="concern" rows="6" placeholder="Describe what you see in the field."></textarea>
                        </label>

                        <div class="form-footer">
                            <div class="button-row">
                                <button class="button" id="consultation-submit" type="submit">Submit concern</button>
                                <button type="button" class="button button-secondary" data-open-assistant>Ask AI first</button>
                            </div>
                        </div>
                    </form>

                    <section class="panel queue-panel reveal">
                        <div class="panel-heading-row">
                            <div>
                                <span class="panel-kicker">Queue</span>
                                <h3>Consultations</h3>
                            </div>
                            <span class="count-pill" id="consultation-count-badge">0</span>
                        </div>
                        <div class="field-hint" id="consultation-queue-note">
                            Log in to view role-specific consultations.
                        </div>
                        <div class="stack-list" id="consultation-list"></div>
                    </section>

                    <section class="panel thread-panel reveal">
                        <div class="thread-toolbar">
                            <div>
                                <span class="panel-kicker">Thread</span>
                                <h3 id="thread-title">Select a consultation</h3>
                            </div>
                            <button type="button" class="text-button" id="scroll-thread-bottom">Latest</button>
                        </div>

                        <div class="role-activity-banner" id="thread-role-banner">
                            Select a consultation.
                        </div>

                        <div class="admin-controls is-hidden" id="admin-controls">
                            <div class="field-row">
                                <label class="field">
                                    <span>Assign consultant</span>
                                    <select id="assign-consultant-select"></select>
                                </label>
                                <label class="field">
                                    <span>Update status</span>
                                    <select id="status-select">
                                        <option value="ai_triage">AI triage</option>
                                        <option value="expert_assigned">Expert assigned</option>
                                        <option value="monitoring">Monitoring</option>
                                        <option value="resolved">Resolved</option>
                                    </select>
                                </label>
                            </div>
                            <div class="button-row">
                                <button type="button" class="button button-secondary" id="assign-consultant-button">Save assignment</button>
                                <button type="button" class="button button-secondary" id="update-status-button">Save status</button>
                            </div>
                        </div>

                        <div class="thread-meta" id="thread-meta"></div>
                        <div class="message-stream" id="thread-messages"></div>
                        <form class="feedback-form is-hidden" id="feedback-form">
                            <div>
                                <span class="panel-kicker">Feedback</span>
                                <h4>Rate this advice</h4>
                            </div>
                            <div class="field-row">
                                <label class="field">
                                    <span>Helpfulness</span>
                                    <select id="feedback-helpfulness">
                                        <option value="5">5 - Very helpful</option>
                                        <option value="4">4 - Helpful</option>
                                        <option value="3">3 - Okay</option>
                                        <option value="2">2 - Not very helpful</option>
                                        <option value="1">1 - Not helpful</option>
                                    </select>
                                </label>
                                <label class="field">
                                    <span>Accuracy</span>
                                    <select id="feedback-accuracy">
                                        <option value="5">5 - Very accurate</option>
                                        <option value="4">4 - Accurate</option>
                                        <option value="3">3 - Unsure</option>
                                        <option value="2">2 - Somewhat inaccurate</option>
                                        <option value="1">1 - Inaccurate</option>
                                    </select>
                                </label>
                            </div>
                            <label class="field">
                                <span>Comment</span>
                                <textarea id="feedback-comment" rows="3" placeholder="What helped? What should AgroLLM or the advisor improve?"></textarea>
                            </label>
                            <button class="button button-secondary" id="feedback-submit" type="submit">Submit feedback</button>
                        </form>
                        <form class="thread-form" id="thread-form">
                            <textarea id="thread-input" rows="3" placeholder="Send another update or response."></textarea>
                            <div class="button-row thread-form-actions">
                                <p class="field-hint" id="thread-role-hint">
                                    Log in to send a role-based message in this consultation.
                                </p>
                                <button class="button" id="thread-submit" type="submit">Send update</button>
                            </div>
                        </form>
                    </section>

                    <aside class="sidebar-column reveal">
                        <section class="panel sidebar-panel">
                            <div class="panel-heading-row">
                                <div>
                                    <span class="panel-kicker">Advisers</span>
                                    <h3>Available</h3>
                                </div>
                            </div>
                            <div class="sidebar-list" id="expert-list"></div>
                        </section>

                        <section class="panel sidebar-panel">
                            <div class="panel-heading-row">
                                <div>
                                    <span class="panel-kicker">Admin</span>
                                    <h3>Users</h3>
                                </div>
                            </div>
                            <div class="sidebar-list" id="admin-user-list"></div>
                        </section>
                    </aside>
                </div>
            </div>
        </section>

        <section class="section section-soft" id="knowledge">
            <div class="container">
                <div class="section-heading reveal">
                    <div>
                        <span class="eyebrow">Knowledge base</span>
                        <h2>Field reference</h2>
                    </div>
                    <p>
                        Search crop, pest, soil, fertilizer, weather, and sustainable farming guidance used by AgroLLM.
                    </p>
                    <div class="button-row section-actions">
                        <button type="button" class="button button-secondary" data-open-assistant>Ask AI</button>
                        <a class="button button-secondary" href="#experts">Talk to Expert</a>
                    </div>
                </div>

                <div class="knowledge-layout">
                    <section class="panel reveal">
                        <label class="field">
                            <span>Search knowledge</span>
                            <input id="knowledge-search" type="search" placeholder="Search rice, corn pests, soil, fertilizer, weather...">
                        </label>
                        <div class="pill-row" id="knowledge-topic-filters"></div>
                    </section>

                    <div class="knowledge-grid" id="knowledge-list"></div>
                </div>
            </div>
        </section>

        <section class="section" id="feedback">
            <div class="container feedback-section-grid">
                <div class="section-heading reveal">
                    <div>
                        <span class="eyebrow">Feedback</span>
                        <h2>Help improve WeAgri</h2>
                    </div>
                    <p>
                        Tell us if the advice was useful. Your feedback helps AgroLLM and our advisers support farmers better.
                    </p>
                </div>

                <section class="feedback-review-block reveal" aria-label="Farmer reviews">
                    <div class="panel-heading-row">
                        <div>
                            <span class="panel-kicker">Reviews</span>
                            <h3>What farmers are saying</h3>
                        </div>
                        <button type="button" class="text-button" id="reviews-toggle" hidden>See more</button>
                    </div>
                    <div class="review-card-row" id="reviews-list">
                        <article class="empty-state">Reviews will appear after farmers submit feedback.</article>
                    </div>
                </section>

                <section class="panel admin-rating-panel reveal is-hidden" id="admin-rating-panel" aria-label="Admin rating summary">
                    <div class="panel-heading-row">
                        <div>
                            <span class="panel-kicker">Admin only</span>
                            <h3>Rating scale</h3>
                        </div>
                        <span class="count-pill" id="admin-rating-total">0 reviews</span>
                    </div>
                    <div class="rating-scale-row" id="admin-rating-scale"></div>
                </section>

                <form class="panel platform-feedback-form reveal" id="platform-feedback-form">
                    <div class="field-row">
                        <label class="field">
                            <span>Rating</span>
                            <select id="platform-feedback-rating">
                                <option value="5">5 - Very helpful</option>
                                <option value="4">4 - Helpful</option>
                                <option value="3">3 - Okay</option>
                                <option value="2">2 - Needs improvement</option>
                                <option value="1">1 - Not helpful</option>
                            </select>
                        </label>
                    </div>
                    <label class="field">
                        <span>Comment</span>
                        <textarea id="platform-feedback-comment" rows="4" placeholder="What worked well? What should be clearer?"></textarea>
                    </label>
                    <div class="button-row">
                        <button class="button" type="submit">Submit feedback</button>
                        <button type="button" class="button button-secondary" data-open-assistant>Ask AI</button>
                    </div>
                    <p class="feedback-confirmation is-hidden" id="platform-feedback-confirmation">
                        Thank you. Your feedback has been received.
                    </p>
                </form>
            </div>
        </section>

        <section class="section section-soft" id="contact">
            <div class="container contact-grid">
                <div class="section-heading reveal">
                    <div>
                        <span class="eyebrow">Contact</span>
                        <h2>Need human help?</h2>
                    </div>
                    <p>
                        Reach the WeAgri team for adviser support, platform concerns, or consultation follow-ups.
                    </p>
                </div>

                <div class="contact-card-grid reveal">
                    <article class="panel contact-card">
                        <span class="panel-kicker">Email</span>
                        <h3><a href="mailto:support@weagri.ph">support@weagri.ph</a></h3>
                        <p>For account help, consultation follow-ups, adviser coordination, and platform concerns.</p>
                    </article>
                    <article class="panel contact-card">
                        <span class="panel-kicker">Phone</span>
                        <h3><a href="tel:+639054072174">+63 905 407 2174</a></h3>
                        <p>Available Monday to Saturday, 8:00 AM to 5:00 PM for urgent support requests.</p>
                    </article>
                    <article class="panel contact-card">
                        <span class="panel-kicker">Operations</span>
                        <h3>Iloilo City, Philippines</h3>
                        <p>WeAgri coordinates remote farmer support, AI triage, and adviser handoffs from the Western Visayas region.</p>
                    </article>
                    <article class="panel contact-card contact-action-card">
                        <span class="panel-kicker">Experts</span>
                        <h3>Talk to an agricultural adviser</h3>
                        <p>Start with AgroLLM or open an expert chat so an adviser can review the field details with you.</p>
                        <a class="button" href="#experts">Contact Expert</a>
                    </article>
                </div>
            </div>
        </section>

    </main>

    <footer class="site-footer">
        <div class="container footer-row">
            <div>
                <p class="footer-brand">WeAgri</p>
                <p>Simple agricultural consultation workspace.</p>
            </div>
            <div class="footer-links">
                <a href="#dashboard">Dashboard</a>
                <a href="#experts">Experts</a>
                <a href="#knowledge">Knowledge</a>
                <a href="#contact">Contact</a>
            </div>
            <p class="footer-copy">&copy; <?= date('Y'); ?> WeAgri.</p>
        </div>
    </footer>

    <button type="button" class="assistant-fab" id="assistant-fab" data-open-assistant>
        Ask AI
    </button>

    <div class="assistant-shell" id="assistant-shell" aria-hidden="true">
        <button class="assistant-backdrop" type="button" data-close-assistant></button>
        <aside class="assistant-drawer" aria-label="AI assistant">
            <div class="assistant-header">
                <div>
                    <span class="panel-kicker">Assistant</span>
                    <h2>AI Help</h2>
                </div>
                <button type="button" class="icon-button" data-close-assistant>Close</button>
            </div>

            <div class="assistant-status">
                AgroLLM gives practical first steps and routes complex concerns to a human consultant.
            </div>

            <div class="assistant-messages" id="assistant-messages"></div>

            <form class="assistant-form" id="assistant-form">
                <textarea id="assistant-input" rows="3" placeholder="Ask about pests, crop symptoms, soil issues, fertilizer, or irrigation."></textarea>
                <div class="button-row assistant-actions">
                    <button class="button" id="assistant-submit" type="submit">Send</button>
                </div>
            </form>
        </aside>
    </div>

    <div class="toast-stack" id="toast-stack" aria-live="polite" aria-atomic="true"></div>

    <script>
        window.WEAGRI_INITIAL_STATE = <?= json_encode(
            $initialState,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        ); ?>;
    </script>
    <script src="script.js?v=20260506-feedback-market-source"></script>
</body>
</html>
