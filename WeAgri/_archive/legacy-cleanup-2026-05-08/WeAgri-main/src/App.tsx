import { useEffect, useMemo, useState } from "react";
import { DashboardOverview } from "./components/DashboardOverview";
import { ExpertConsult } from "./components/ExpertConsult";
import { LoginPanel } from "./components/LoginPanel";
import { Sidebar } from "./components/Sidebar";
import { getDashboard, getSession, login, logout } from "./lib/api";
import type { AppView, AuthUser, DashboardPayload } from "./types";

function AgroWorkspace() {
  return (
    <section className="page-section">
      <div className="section-header">
        <div>
          <span className="eyebrow">AGROLLM</span>
          <h2>AI triage workspace</h2>
        </div>
      </div>

      <div className="dashboard-grid">
        <article className="content-card content-card-large">
          <span className="eyebrow">NEXT STEP</span>
          <h3>Use AI for the first pass, then escalate only when risk is real.</h3>
          <p>
            Start with symptoms, crop, and how long the issue has been visible.
            The consultant directory stays one click away when the case needs a
            human response.
          </p>
        </article>

        <article className="content-card">
          <span className="eyebrow">STARTER PROMPTS</span>
          <div className="tag-row">
            <span className="tag-pill">Leaf yellowing</span>
            <span className="tag-pill">Soil moisture</span>
            <span className="tag-pill">Fertilizer timing</span>
            <span className="tag-pill">Pest scouting</span>
          </div>
        </article>
      </div>
    </section>
  );
}

export default function App() {
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);
  const [isSidebarPinned, setIsSidebarPinned] = useState(false);
  const [activeView, setActiveView] = useState<AppView>("dashboard");
  const [token, setToken] = useState<string | null>(() => localStorage.getItem("weagri_token"));
  const [user, setUser] = useState<AuthUser | null>(null);
  const [authLoading, setAuthLoading] = useState<boolean>(Boolean(localStorage.getItem("weagri_token")));
  const [authError, setAuthError] = useState<string | null>(null);
  const [dashboard, setDashboard] = useState<DashboardPayload | null>(null);
  const [dashboardLoading, setDashboardLoading] = useState(false);

  useEffect(() => {
    if (!token) {
      setAuthLoading(false);
      return;
    }

    let mounted = true;

    async function restoreSession() {
      try {
        const payload = await getSession(token);
        if (!mounted) {
          return;
        }

        if (!payload.user || !payload.token) {
          throw new Error("Session could not be restored.");
        }

        setUser({
          ...payload.user,
          specialty_tags: payload.user.specialty_tags ?? null,
        });
        setToken(payload.token);
        localStorage.setItem("weagri_token", payload.token);
        setAuthError(null);
      } catch (requestError) {
        if (!mounted) {
          return;
        }
        localStorage.removeItem("weagri_token");
        setToken(null);
        setUser(null);
        setAuthError(
          requestError instanceof Error
            ? requestError.message
            : "Session restore failed.",
        );
      } finally {
        if (mounted) {
          setAuthLoading(false);
        }
      }
    }

    void restoreSession();

    return () => {
      mounted = false;
    };
  }, [token]);

  useEffect(() => {
    if (!user) {
      setDashboard(null);
      return;
    }

    let mounted = true;

    async function loadDashboard() {
      setDashboardLoading(true);
      try {
        const payload = await getDashboard(token);
        if (!mounted) {
          return;
        }
        setDashboard(payload);
      } catch {
        if (mounted) {
          setDashboard(null);
        }
      } finally {
        if (mounted) {
          setDashboardLoading(false);
        }
      }
    }

    void loadDashboard();
    const interval = window.setInterval(() => {
      void loadDashboard();
    }, 5000);

    return () => {
      mounted = false;
      window.clearInterval(interval);
    };
  }, [token, user]);

  const shellTitle = useMemo(() => {
    switch (activeView) {
      case "agrollm":
        return "AgroLLM";
      case "experts":
        return "Experts";
      default:
        return "Dashboard";
    }
  }, [activeView]);

  async function handleLogin(email: string, password: string) {
    setAuthLoading(true);
    setAuthError(null);

    try {
      const payload = await login(email, password);
      if (!payload.user || !payload.token) {
        throw new Error("Login response was incomplete.");
      }

      setUser({
        ...payload.user,
        specialty_tags: payload.user.specialty_tags ?? null,
      });
      setToken(payload.token);
      localStorage.setItem("weagri_token", payload.token);
      setActiveView("dashboard");
    } catch (requestError) {
      setAuthError(
        requestError instanceof Error ? requestError.message : "Login failed.",
      );
    } finally {
      setAuthLoading(false);
    }
  }

  async function handleLogout() {
    try {
      await logout(token);
    } catch {
      // Clear the local state even if the backend is unavailable.
    }

    localStorage.removeItem("weagri_token");
    setToken(null);
    setUser(null);
    setDashboard(null);
    setActiveView("dashboard");
    setIsSidebarOpen(false);
    setIsSidebarPinned(false);
  }

  if (!user) {
    return (
      <LoginPanel
        onSubmit={handleLogin}
        loading={authLoading}
        error={authError}
      />
    );
  }

  return (
    <div className="app-shell">
      <Sidebar
        activeView={activeView}
        isSidebarOpen={isSidebarOpen}
        isSidebarPinned={isSidebarPinned}
        onOpen={() => setIsSidebarOpen(true)}
        onClose={() => setIsSidebarOpen(false)}
        onTogglePin={() => {
          const nextPinned = !isSidebarPinned;
          setIsSidebarPinned(nextPinned);
          setIsSidebarOpen(nextPinned);
        }}
        onChangeView={setActiveView}
        onLogout={handleLogout}
        user={user}
      />

      <main className="app-main">
        <header className="mobile-page-header">
          <span className="eyebrow">WEAGRI</span>
          <strong>{shellTitle}</strong>
        </header>

        {activeView === "dashboard" ? (
          <DashboardOverview
            user={user}
            dashboard={dashboard}
            loading={dashboardLoading}
          />
        ) : null}

        {activeView === "agrollm" ? <AgroWorkspace /> : null}

        {activeView === "experts" ? (
          <ExpertConsult currentUser={user} token={token ?? ""} />
        ) : null}
      </main>
    </div>
  );
}
