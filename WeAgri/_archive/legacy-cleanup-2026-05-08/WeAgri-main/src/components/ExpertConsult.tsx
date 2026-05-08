import { useEffect, useState } from "react";
import { getActiveConsultants } from "../lib/api";
import type { AuthUser, Consultant } from "../types";
import { ConsultantChatPanel } from "./ConsultantChatPanel";

interface ExpertConsultProps {
  currentUser: AuthUser;
  token: string;
}

export function ExpertConsult({ currentUser, token }: ExpertConsultProps) {
  const [consultants, setConsultants] = useState<Consultant[]>([]);
  const [selectedConsultant, setSelectedConsultant] = useState<Consultant | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let mounted = true;

    async function loadConsultants() {
      setLoading(true);
      try {
        const rows = (await getActiveConsultants(token)).filter(
          (consultant) => consultant.id !== currentUser.id,
        );
        if (!mounted) {
          return;
        }
        setConsultants(rows);
        setSelectedConsultant((current) => {
          if (current) {
            return rows.find((consultant) => consultant.id === current.id) ?? rows[0] ?? null;
          }
          return rows[0] ?? null;
        });
        setError(null);
      } catch (requestError) {
        if (!mounted) {
          return;
        }
        setConsultants([]);
        setError(
          requestError instanceof Error
            ? requestError.message
            : "Unable to load consultant profiles.",
        );
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    }

    void loadConsultants();

    return () => {
      mounted = false;
    };
  }, [currentUser.id, token]);

  return (
    <section className="page-section expert-layout">
      <div className="section-header">
        <div>
          <span className="eyebrow">CONSULT EXPERTS</span>
          <h2>Available agricultural consultants</h2>
        </div>
      </div>

      {error ? <div className="form-error">{error}</div> : null}

      <div className="expert-grid-shell">
        <div className="expert-grid">
          {loading ? (
            <article className="expert-card">
              <span className="eyebrow">LOADING</span>
              <h3>Fetching consultant profiles.</h3>
              <p>The directory is loading from the PHP API.</p>
            </article>
          ) : null}

          {!loading && consultants.length === 0 ? (
            <article className="expert-card">
              <span className="eyebrow">NO CONSULTANTS</span>
              <h3>No consultant accounts are active.</h3>
              <p>Register or log in a consultant account to populate this list.</p>
            </article>
          ) : null}

          {consultants.map((consultant) => (
            <article
              key={consultant.id}
              className={`expert-card ${selectedConsultant?.id === consultant.id ? "expert-card-active" : ""}`}
            >
              <div className="expert-card-top">
                <div className="consultant-avatar">
                  {consultant.full_name
                    .split(" ")
                    .map((part) => part[0])
                    .join("")
                    .slice(0, 2)
                    .toUpperCase()}
                </div>
                <div className={`status-dot ${consultant.is_online ? "status-online" : "status-offline"}`} />
              </div>

              <span className="eyebrow">CONSULTANT</span>
              <h3>{consultant.full_name}</h3>
              <div className="tag-row">
                {consultant.specialty_tags.map((tag) => (
                  <span className="tag-pill" key={tag}>
                    {tag}
                  </span>
                ))}
              </div>
              <p>
                Last active: {consultant.last_active}
              </p>
              <div className="expert-card-actions">
                <span className="rating-chip">4.9</span>
                <button
                  className="primary-button"
                  type="button"
                  disabled={!consultant.is_online}
                  onClick={() => setSelectedConsultant(consultant)}
                >
                  Chat
                </button>
              </div>
            </article>
          ))}
        </div>

        <ConsultantChatPanel
          consultant={selectedConsultant}
          currentUser={currentUser}
          token={token}
        />
      </div>
    </section>
  );
}
