import type {
  AuthResponse,
  ChatMessage,
  Consultant,
  DashboardPayload,
} from "../types";

const API_BASE =
  import.meta.env.VITE_WEAGRI_API_BASE ??
  "http://localhost/WeAgri/api/v1";

async function request<T>(
  path: string,
  options: RequestInit = {},
  token?: string | null,
): Promise<T> {
  const headers = new Headers(options.headers ?? {});
  headers.set("Content-Type", "application/json");

  if (token) {
    headers.set("Authorization", `Bearer ${token}`);
  }

  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers,
  });

  const data = (await response.json()) as T & { ok?: boolean; message?: string };

  if (!response.ok) {
    throw new Error(data.message ?? "Request failed.");
  }

  if ("ok" in data && data.ok === false) {
    throw new Error(data.message ?? "Request failed.");
  }

  return data;
}

export async function login(email: string, password: string): Promise<AuthResponse> {
  return request<AuthResponse>("/login.php", {
    method: "POST",
    body: JSON.stringify({ email, password }),
  });
}

export async function getSession(token: string): Promise<AuthResponse> {
  return request<AuthResponse>("/login.php", { method: "GET" }, token);
}

export async function logout(token: string | null): Promise<void> {
  await request<{ ok: boolean }>("/logout.php", { method: "POST", body: JSON.stringify({}) }, token);
}

export async function getDashboard(token: string | null): Promise<DashboardPayload> {
  return request<DashboardPayload>("/get_dashboard.php", { method: "GET" }, token);
}

export async function getActiveConsultants(token: string): Promise<Consultant[]> {
  const payload = await request<{ ok: boolean; consultants: Array<Omit<Consultant, "specialty_tags"> & { specialty_tags: string[] }> }>(
    "/get_active_consultants.php",
    { method: "GET" },
    token,
  );

  return payload.consultants;
}

export async function getMessages(
  partnerId: number,
  token: string,
  after?: string,
): Promise<{ messages: ChatMessage[]; cursor: string }> {
  const search = new URLSearchParams({ partner_id: String(partnerId) });
  if (after) {
    search.set("after", after);
  }

  return request<{ ok: boolean; messages: ChatMessage[]; cursor: string }>(
    `/get_messages.php?${search.toString()}`,
    { method: "GET" },
    token,
  );
}

export async function sendMessage(
  receiverId: number,
  messageText: string,
  token: string,
): Promise<ChatMessage> {
  const payload = await request<{
    ok: boolean;
    sent_message: ChatMessage;
  }>(
    "/send_message.php",
    {
      method: "POST",
      body: JSON.stringify({
        receiver_id: receiverId,
        message_text: messageText,
      }),
    },
    token,
  );

  return payload.sent_message;
}
