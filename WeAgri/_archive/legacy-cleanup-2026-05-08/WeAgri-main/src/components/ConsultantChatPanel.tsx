import { type FormEvent, useEffect, useMemo, useState } from "react";
import { getMessages, sendMessage } from "../lib/api";
import type { AuthUser, ChatMessage, Consultant } from "../types";

interface ConsultantChatPanelProps {
  consultant: Consultant | null;
  currentUser: AuthUser;
  token: string;
}

function mergeMessages(existing: ChatMessage[], incoming: ChatMessage[]): ChatMessage[] {
  const map = new Map<number, ChatMessage>();
  [...existing, ...incoming].forEach((message) => {
    map.set(message.id, message);
  });

  return Array.from(map.values()).sort((left, right) => {
    if (left.created_at === right.created_at) {
      return left.id - right.id;
    }
    return left.created_at.localeCompare(right.created_at);
  });
}

export function ConsultantChatPanel({
  consultant,
  currentUser,
  token,
}: ConsultantChatPanelProps) {
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [input, setInput] = useState("");
  const [loading, setLoading] = useState(false);
  const [sending, setSending] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [cursor, setCursor] = useState<string>("");

  const title = useMemo(() => {
    if (!consultant) {
      return "Select a consultant";
    }

    return consultant.full_name;
  }, [consultant]);

  useEffect(() => {
    setMessages([]);
    setCursor("");
    setError(null);

    if (!consultant) {
      return;
    }

    let mounted = true;

    async function loadInitialMessages() {
      setLoading(true);
      try {
        const payload = await getMessages(consultant.id, token);
        if (!mounted) {
          return;
        }
        setMessages(payload.messages);
        setCursor(payload.cursor ?? "");
      } catch (requestError) {
        if (!mounted) {
          return;
        }
        setError(
          requestError instanceof Error
            ? requestError.message
            : "Unable to load messages.",
        );
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    }

    void loadInitialMessages();

    return () => {
      mounted = false;
    };
  }, [consultant, token]);

  useEffect(() => {
    if (!consultant) {
      return;
    }

    const interval = window.setInterval(async () => {
      try {
        const payload = await getMessages(consultant.id, token, cursor || undefined);
        if (payload.messages.length > 0) {
          setMessages((current) => mergeMessages(current, payload.messages));
          setCursor(payload.cursor ?? cursor);
        }
      } catch {
        // Keep polling quiet. The visible error state is handled on the initial load.
      }
    }, 2000);

    return () => window.clearInterval(interval);
  }, [consultant, cursor, token]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!consultant || !input.trim() || sending) {
      return;
    }

    const optimisticId = Date.now() * -1;
    const optimisticMessage: ChatMessage = {
      id: optimisticId,
      sender_id: currentUser.id,
      receiver_id: consultant.id,
      message_text: input.trim(),
      created_at: new Date().toISOString().slice(0, 19).replace("T", " "),
      is_read: false,
    };

    setMessages((current) => mergeMessages(current, [optimisticMessage]));
    setInput("");
    setSending(true);
    setError(null);

    try {
      const savedMessage = await sendMessage(consultant.id, optimisticMessage.message_text, token);
      setMessages((current) =>
        mergeMessages(
          current.filter((message) => message.id !== optimisticId),
          [savedMessage],
        ),
      );
      setCursor(savedMessage.created_at);
    } catch (requestError) {
      setMessages((current) => current.filter((message) => message.id !== optimisticId));
      setError(
        requestError instanceof Error
          ? requestError.message
          : "Unable to send message.",
      );
      setInput(optimisticMessage.message_text);
    } finally {
      setSending(false);
    }
  }

  return (
    <aside className={`chat-panel ${consultant ? "chat-panel-open" : ""}`}>
      <header className="chat-panel-header">
        <div className="consultant-avatar">
          {consultant ? consultant.full_name.slice(0, 2).toUpperCase() : "??"}
        </div>
        <div>
          <span className="eyebrow">
            {consultant?.is_online ? "ACTIVE NOW" : "OFFLINE"}
          </span>
          <h3>{title}</h3>
        </div>
      </header>

      <div className="chat-thread">
        {!consultant ? (
          <div className="chat-placeholder">
            Choose an online consultant to open the live thread.
          </div>
        ) : null}

        {loading ? <div className="chat-placeholder">Loading messages...</div> : null}

        {messages.map((message) => {
          const isCurrentUser = message.sender_id === currentUser.id;

          return (
            <article
              key={message.id}
              className={`chat-message ${isCurrentUser ? "chat-message-self" : "chat-message-peer"}`}
            >
              <p>{message.message_text}</p>
              <span>{message.created_at}</span>
            </article>
          );
        })}
      </div>

      <form className="chat-input-row" onSubmit={handleSubmit}>
        <input
          type="text"
          value={input}
          onChange={(event) => setInput(event.target.value)}
          placeholder={consultant ? "Type your message" : "Select a consultant first"}
          disabled={!consultant || sending}
        />
        <button className="primary-button" type="submit" disabled={!consultant || sending}>
          {sending ? "Sending..." : "Send"}
        </button>
      </form>

      {error ? <div className="form-error chat-error">{error}</div> : null}
    </aside>
  );
}
