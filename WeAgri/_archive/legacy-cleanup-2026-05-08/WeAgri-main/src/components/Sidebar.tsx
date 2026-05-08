import type { AppView, AuthUser } from "../types";

interface SidebarProps {
  activeView: AppView;
  isSidebarOpen: boolean;
  isSidebarPinned: boolean;
  onOpen(): void;
  onClose(): void;
  onTogglePin(): void;
  onChangeView(view: AppView): void;
  onLogout(): void;
  user: AuthUser;
}

const NAV_ITEMS: Array<{ key: AppView; icon: string; label: string }> = [
  { key: "dashboard", icon: "D", label: "Dashboard" },
  { key: "agrollm", icon: "A", label: "AgroLLM" },
  { key: "experts", icon: "E", label: "Experts" },
];

export function Sidebar({
  activeView,
  isSidebarOpen,
  isSidebarPinned,
  onOpen,
  onClose,
  onTogglePin,
  onChangeView,
  onLogout,
  user,
}: SidebarProps) {
  return (
    <>
      <aside
        className={`sidebar ${isSidebarOpen ? "sidebar-open" : "sidebar-slim"}`}
        onMouseEnter={onOpen}
        onMouseLeave={() => {
          if (!isSidebarPinned) {
            onClose();
          }
        }}
      >
        <div className="sidebar-top">
          <button
            className="icon-toggle"
            type="button"
            onClick={onTogglePin}
            aria-label={isSidebarPinned ? "Collapse sidebar" : "Expand sidebar"}
          >
            ≡
          </button>
          <div className={`brand-lockup ${isSidebarOpen ? "brand-visible" : "brand-hidden"}`}>
            <span className="brand-kicker">WEAGRI</span>
            <strong>Field Console</strong>
          </div>
        </div>

        <nav className="sidebar-nav">
          {NAV_ITEMS.map((item) => (
            <button
              key={item.key}
              type="button"
              className={`sidebar-link ${activeView === item.key ? "sidebar-link-active" : ""}`}
              onClick={() => onChangeView(item.key)}
            >
              <span className="sidebar-icon">{item.icon}</span>
              <span className={`sidebar-label ${isSidebarOpen ? "label-visible" : "label-hidden"}`}>
                {item.label}
              </span>
            </button>
          ))}
        </nav>

        <div className="sidebar-footer">
          <div className={`sidebar-user ${isSidebarOpen ? "user-visible" : "user-hidden"}`}>
            <span className="brand-kicker">SIGNED IN</span>
            <strong>{user.full_name}</strong>
            <span>{user.role === "consultant" ? "Consultant" : "Farmer"}</span>
          </div>
          <button className="ghost-button sidebar-logout" type="button" onClick={onLogout}>
            <span className="sidebar-icon">O</span>
            <span className={`sidebar-label ${isSidebarOpen ? "label-visible" : "label-hidden"}`}>
              Logout
            </span>
          </button>
        </div>
      </aside>

      <nav className="mobile-tabbar">
        {NAV_ITEMS.map((item) => (
          <button
            key={item.key}
            type="button"
            className={`mobile-tab ${activeView === item.key ? "mobile-tab-active" : ""}`}
            onClick={() => onChangeView(item.key)}
          >
            <span className="sidebar-icon">{item.icon}</span>
            <span>{item.label}</span>
          </button>
        ))}
      </nav>
    </>
  );
}
