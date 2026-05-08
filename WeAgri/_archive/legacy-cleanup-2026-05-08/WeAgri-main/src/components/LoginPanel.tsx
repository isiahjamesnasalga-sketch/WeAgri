import { type FormEvent, useState } from "react";

interface LoginPanelProps {
  onSubmit(email: string, password: string): Promise<void>;
  loading: boolean;
  error: string | null;
}

export function LoginPanel({ onSubmit, loading, error }: LoginPanelProps) {
  const [email, setEmail] = useState("farmer@weagri.local");
  const [password, setPassword] = useState("weagri123");

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    await onSubmit(email, password);
  }

  return (
    <main className="login-shell">
      <section className="login-card">
        <span className="eyebrow">WELCOME TO WEAGRI</span>
        <h1>Login to the farmer and consultant workspace.</h1>
        <p>
          Use the live dashboard, reach authenticated consultants, and keep one
          clear thread for field advice.
        </p>

        <form className="login-form" onSubmit={handleSubmit}>
          <label>
            <span>Email</span>
            <input
              type="email"
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              placeholder="farmer@weagri.local"
            />
          </label>

          <label>
            <span>Password</span>
            <input
              type="password"
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              placeholder="Enter password"
            />
          </label>

          {error ? <div className="form-error">{error}</div> : null}

          <button className="primary-button" type="submit" disabled={loading}>
            {loading ? "Signing in..." : "Login"}
          </button>
        </form>

        <div className="login-hint">
          <span className="eyebrow">LOCAL TEST ACCOUNT</span>
          <strong>farmer@weagri.local / weagri123</strong>
        </div>
      </section>
    </main>
  );
}
