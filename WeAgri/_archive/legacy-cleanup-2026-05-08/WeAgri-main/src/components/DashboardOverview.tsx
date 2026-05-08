import type { AuthUser, DashboardPayload } from "../types";

interface DashboardOverviewProps {
  user: AuthUser;
  dashboard: DashboardPayload | null;
  loading: boolean;
}

function metricLabel(value: number, suffix: string): string {
  return `${value.toFixed(1)}${suffix}`;
}

export function DashboardOverview({
  user,
  dashboard,
  loading,
}: DashboardOverviewProps) {
  return (
    <section className="page-section">
      <div className="hero-card">
        <span className="eyebrow">WELCOME TO WEAGRI</span>
        <h1>Hello, {user.full_name.split(" ")[0]}.</h1>
        <p>
          Here is the latest field picture, market movement, and open message
          load so you can act without digging through the whole system.
        </p>
      </div>

      <div className="section-header">
        <div>
          <span className="eyebrow">DAILY OVERVIEW</span>
          <h2>Key Metrics</h2>
        </div>
      </div>

      <div className="metric-grid">
        <article className="metric-card">
          <span className="eyebrow">Weather</span>
          <strong>{dashboard ? metricLabel(dashboard.metrics.temperature, "C") : "--"}</strong>
          <p>Latest temperature reading from the active field node.</p>
        </article>
        <article className="metric-card">
          <span className="eyebrow">Soil Moisture</span>
          <strong>{dashboard ? metricLabel(dashboard.metrics.soil_moisture, "%") : "--"}</strong>
          <p>Track the root-zone moisture before irrigation decisions.</p>
        </article>
        <article className="metric-card">
          <span className="eyebrow">Crop Health</span>
          <strong>{dashboard ? metricLabel(dashboard.metrics.crop_health, "%") : "--"}</strong>
          <p>Use this as a quick signal before walking the field.</p>
        </article>
        <article className="metric-card">
          <span className="eyebrow">Open Queries</span>
          <strong>{dashboard ? dashboard.metrics.open_queries : "--"}</strong>
          <p>Unread chat load waiting for the next action.</p>
        </article>
      </div>

      <div className="dashboard-grid">
        <article className="content-card content-card-large">
          <span className="eyebrow">AI INSIGHT</span>
          <h3>What stands out today</h3>
          <p>
            {loading && !dashboard
              ? "Loading the latest dashboard readings."
              : dashboard?.insight ??
                "Dashboard data will appear here when the PHP API responds."}
          </p>
          <div className="timestamp-row">
            <span>Updated</span>
            <strong>{dashboard?.metrics.timestamp ?? "--"}</strong>
          </div>
        </article>

        <article className="content-card">
          <span className="eyebrow">MARKET DATA</span>
          <h3>Current crop prices</h3>
          <div className="market-table-wrap">
            <table className="market-table">
              <thead>
                <tr>
                  <th>Crop</th>
                  <th>Price/kg</th>
                  <th>Trend</th>
                </tr>
              </thead>
              <tbody>
                {(dashboard?.market_prices ?? []).map((row) => (
                  <tr key={row.id}>
                    <td>{row.crop_name}</td>
                    <td>{row.price.toFixed(2)}</td>
                    <td>
                      <span className={`trend-pill trend-${row.trend}`}>
                        {row.trend}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </article>
      </div>
    </section>
  );
}
