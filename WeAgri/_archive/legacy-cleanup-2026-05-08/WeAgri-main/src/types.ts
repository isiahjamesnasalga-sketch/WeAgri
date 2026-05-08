export type AppView = "dashboard" | "agrollm" | "experts";

export interface AuthUser {
  id: number;
  full_name: string;
  email: string;
  role: "farmer" | "consultant";
  specialty_tags: string[] | null;
  is_online: boolean;
  last_active: string;
}

export interface DashboardMetrics {
  temperature: number;
  soil_moisture: number;
  crop_health: number;
  open_queries: number;
  timestamp: string;
}

export interface MarketPrice {
  id: number;
  crop_name: string;
  price: number;
  trend: "up" | "down" | "stable";
  updated_at: string;
}

export interface DashboardPayload {
  ok: boolean;
  message?: string;
  metrics: DashboardMetrics;
  market_prices: MarketPrice[];
  insight: string;
}

export interface Consultant {
  id: number;
  full_name: string;
  specialty_tags: string[];
  is_online: boolean;
  last_active: string;
}

export interface ChatMessage {
  id: number;
  sender_id: number;
  receiver_id: number;
  message_text: string;
  created_at: string;
  is_read: boolean;
}

export interface AuthResponse {
  ok: boolean;
  message?: string;
  token?: string;
  user?: AuthUser;
}
