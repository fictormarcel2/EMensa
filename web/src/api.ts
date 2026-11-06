export type User = { id: number; alias: string };
export type Mensa = {
  id: string;
  name: string;
  hours: unknown;
  meetingPoints: { id: string; name: string }[];
};
export type Bootstrap = {
  csrfToken: string;
  user: User | null;
  mensas: Mensa[];
  today: string;
  demoAccounts: { alias: string; email: string; password: string }[];
};
export type Slot = {
  id: string;
  time: string;
  end: string;
  selectable: boolean;
  count: number;
  friends: User[];
  publicParticipants: { alias: string }[];
};
export type Dish = {
  id: string;
  name: string;
  description: string;
  image: string;
  priceInternal: number | string;
  priceExternal: number | string;
  diet: string;
  allergens: string[];
};
export type Overview = {
  date: string;
  mensaId: string;
  serviceWindows: { start: string; end: string }[];
  hoursVerifiedAt: string;
  notice: string | null;
  slots: Slot[];
  menu: Dish[];
  menuNotice: string;
};
export type Plan = {
  id: number;
  date: string;
  mensaId: string;
  slotId: string;
  time: string;
  end: string;
  meetingPointId: string | null;
  visibility: "private" | "friends" | "public";
  reminder: boolean;
  reminderStatus: string;
  status: string;
  version: number;
  editable: boolean;
};
export type Friend = User & {
  sharedPlan: null | {
    date: string;
    mensaId: string;
    slotId: string;
    meetingPointId: string | null;
  };
};
export type Friends = { friends: Friend[]; requests: User[]; outgoing: User[] };
export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
  ) {
    super(message);
  }
}
let csrfToken = "";
export function setCsrf(token: string) {
  csrfToken = token;
}
export async function api<T>(
  path: string,
  method = "GET",
  body?: unknown,
): Promise<T> {
  const response = await fetch("/api" + path, {
    method,
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/json",
      ...(method !== "GET" ? { "X-CSRF-Token": csrfToken } : {}),
    },
    ...(body !== undefined ? { body: JSON.stringify(body) } : {}),
  });
  const result = await response.json().catch(() => null);
  if (!response.ok)
    throw new ApiError(
      response.status,
      result?.error?.message ||
        "The service could not complete your request. Please try again.",
    );
  return result as T;
}
export function query(mensaId: string, date: string) {
  return `?mensaId=${encodeURIComponent(mensaId)}&date=${encodeURIComponent(date)}`;
}
